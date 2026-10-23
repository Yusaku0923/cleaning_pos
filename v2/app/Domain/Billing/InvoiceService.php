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
     * 締め日と合わない請求書 (isMismatchCutoff) の注文を、今の締め日で正しい期間の請求書へ移し、
     * 元の請求書を削除 (soft delete) する (旧 align_cutoff_date)。
     * 旧は画面で選んだ 1 件を対象にしたが、v2 は顧客の合わない請求書すべてを対象にする。
     * 移し先を作るときは paid_at を入れない (旧と同じ)。お渡し日がない注文は今日で探す (旧と同じ)。
     */
    public function alignCutoff(Customer $c): void
    {
        DB::transaction(function () use ($c) {
            $invoices = Invoice::where('customer_id', $c->id)->orderBy('id')->get()
                ->filter(fn (Invoice $i) => $this->isMismatchCutoff($i, $c));

            foreach ($invoices as $invoice) {
                foreach (Order::where('invoice_id', $invoice->id)->get() as $order) {
                    $handedAt = $order->handed_at ? CarbonImmutable::parse($order->handed_at) : CarbonImmutable::now();
                    [$start, $end] = InvoicePeriod::for((int) $c->cutoff_date, $handedAt);
                    $target = $this->findOrCreate($c, $start, $end, ['manager_id' => $invoice->manager_id]);
                    $order->invoice_id = $target->id;
                    $order->save();
                }
                $invoice->delete();
            }
        });
    }

    /** 請求書の終了日が顧客の締め日と合わないか (旧 Invoice::isMismatchCutoffDate)。 */
    public function isMismatchCutoff(Invoice $invoice, ?Customer $c = null): bool
    {
        $c ??= Customer::withTrashed()->findOrFail($invoice->customer_id);
        $end = $invoice->period_end;
        $cutoff = (int) $c->cutoff_date === InvoicePeriod::MONTH_END_CUTOFF ? $end->daysInMonth : (int) $c->cutoff_date;

        return $end->day !== $cutoff;
    }

    /** 同じ顧客・同じ期間の請求書 (削除済みは除く。旧 existsTargetInvoice) を返す。なければ作る。 */
    private function findOrCreate(Customer $c, CarbonImmutable $start, CarbonImmutable $end, array $attributes): Invoice
    {
        $existing = Invoice::where('customer_id', $c->id)
            ->whereDate('period_start', $start->toDateString())
            ->whereDate('period_end', $end->toDateString())
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
