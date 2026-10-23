<?php

namespace App\Domain\Billing;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * 請求書の作成・繰越・締め日合わせ。
 * 旧: OrdersController::store / ReturnController (請求書を探す・作る)、Invoice::existsTargetInvoice、
 * Api\InvoiceController::carry_over / align_cutoff_date、Invoice::isMismatchCutoffDate。
 */
class InvoiceService
{
    /**
     * $handedAt (お渡し日) が入る請求期間の請求書を返す。なければ作る。
     * 入金確認ありの顧客は作成時に paid_at = 期間の終了日 (旧の注文経由と同じ。返却経由も v2 はこれに揃える)。
     * 担当者は顧客の担当者 (旧は注文の担当者だが、本番データでは常に顧客の担当者と同じ)。
     */
    public function findOrCreateFor(Customer $c, CarbonImmutable $handedAt): Invoice
    {
        [$start, $end] = InvoicePeriod::for((int) $c->cutoff_date, $handedAt);

        return $this->findOrCreate($c, $start, $end, [
            'paid_at' => $c->needs_payment_confirmation ? $end->toDateString() : null,
        ]);
    }

    /**
     * 請求書 $from を次の期間の請求書へ繰り越す (旧 carry_over の 1 件分)。繰越先を返す。
     * 繰越先は $from の終了日の翌日が入る期間。なければ作る (paid_at は入れない。旧と同じ)。
     * $from が既に繰越を受けていたら、その繰越元もまとめて繰越先へ付け替える。
     */
    public function carryOver(Invoice $from): Invoice
    {
        return DB::transaction(function () use ($from) {
            // 旧は ID で読み直していた。呼ぶ側の古いインスタンスでも DB の今の値で判断する
            $from = Invoice::whereKey($from->id)->lockForUpdate()->firstOrFail();
            $customer = Customer::withTrashed()->findOrFail($from->customer_id);
            $nextStart = CarbonImmutable::parse($from->period_end->toDateString())->addDay();
            [$start, $end] = InvoicePeriod::for((int) $customer->cutoff_date, $nextStart);

            $next = $this->findOrCreate($customer, $start, $end, ['manager_id' => $from->manager_id]);
            $next->has_carried_over = true;
            $next->save();

            if ($from->has_carried_over) {
                Invoice::where('carry_over_id', $from->id)->update(['carry_over_id' => $next->id]);
                $from->has_carried_over = false;
            }
            $from->carry_over_id = $next->id;
            $from->save();

            return $next;
        });
    }

    /**
     * 選んだ請求書 1 件の注文を、今の締め日で正しい期間の請求書へ移し、その請求書を削除 (soft delete) する
     * (旧 Api\InvoiceController::align_cutoff_date。画面では締日ズレの請求書を 1 件だけ選べる)。
     * 注文ごとに、その顧客の締め日とお渡し日 (なければ今日。旧と同じ) で期間を出し、請求書を探す・作る
     * (作るときは担当者・顧客は選んだ請求書のもの、paid_at は入れない。旧と同じ)。
     * 移し先がこれから消す請求書自身になる場合 (締め日 0 は常に「ズレ」と判定される) は、それを使わず新しく作る。
     */
    public function alignCutoff(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            foreach (Order::where('invoice_id', $invoice->id)->get() as $order) {
                $customer = Customer::withTrashed()->findOrFail($order->customer_id);
                $handedAt = $order->handed_at ? CarbonImmutable::parse($order->handed_at) : CarbonImmutable::now();
                [$start, $end] = InvoicePeriod::for((int) $customer->cutoff_date, $handedAt);
                $target = $this->findOrCreate($customer, $start, $end, [
                    'manager_id' => $invoice->manager_id,
                    'customer_id' => $invoice->customer_id,
                ], exceptId: $invoice->id);
                $order->invoice_id = $target->id;
                $order->save();
            }

            $invoice->delete();
        });
    }

    /** 請求書の終了日が顧客の締め日と合わないか (旧 Invoice::isMismatchCutoffDate)。 */
    public function isMismatchCutoff(Invoice $invoice, ?Customer $c = null): bool
    {
        $c ??= Customer::withTrashed()->findOrFail($invoice->customer_id);
        $cutoff = (int) $c->cutoff_date;
        if ($cutoff === InvoicePeriod::UNSET_CUTOFF) {
            return true; // 旧: 日付が 0 になることはないので常にズレ (締め日を選んでもらう合図になる)
        }
        $end = CarbonImmutable::parse($invoice->period_end->toDateString());

        // 99 は月末、29〜31 は短い月なら月末 (InvoicePeriod と同じ。1〜28 と 99 は旧と同じ判定)
        return $end->day !== InvoicePeriod::periodEnd($end, $cutoff)->day;
    }

    /**
     * 同じ顧客・同じ期間の請求書 (削除済みは除く。旧 existsTargetInvoice) を返す。なければ作る。
     * 旧から取り込んだ締め日 0 の請求書は終了日が「翌月の 00 日」('2026-07-00' = 6 月末) のまま入っているので、
     * 期間の終了日が月末のときは、その形の請求書も同じ期間として扱う (保存されている値は変えない)。
     */
    private function findOrCreate(Customer $c, CarbonImmutable $start, CarbonImmutable $end, array $attributes, ?int $exceptId = null): Invoice
    {
        $ends = [$end->toDateString()];
        if ($end->isLastOfMonth()) {
            $ends[] = $end->addDay()->format('Y-m').'-00';
        }

        $existing = Invoice::where('customer_id', $c->id)
            ->where('period_start', $start->toDateString())
            // DATE 列を文字列で比べる ('-00' の日付も strict モードでそのまま一致させる)
            ->where(function ($q) use ($ends) {
                foreach ($ends as $e) {
                    $q->orWhereRaw('CAST(period_end AS CHAR) = ?', [$e]);
                }
            })
            ->when($exceptId !== null, fn ($q) => $q->whereKeyNot($exceptId))
            ->orderBy('id')
            ->first();

        return $existing ?? Invoice::create($attributes + [
            'manager_id' => $c->manager_id,
            'customer_id' => $c->id,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
        ]);
    }
}
