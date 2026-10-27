<?php

namespace App\Domain\Order;

use App\Domain\Billing\InvoicePeriod;
use App\Domain\Billing\InvoiceService;
use App\Domain\Money\PriceCalculator;
use App\Domain\Tag\TagAllocator;
use App\Models\Clothes;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * 注文の登録 (旧 Api\OrdersController::store)。
 * 注文・品物 (タグ)・タグ採番・請求書・顧客の集計を 1 トランザクションで行う (途中で失敗したら何も残らない)。
 * 金額は商品マスタの単価から計算する (画面から送られた合計は使わない)。
 * お渡し・入金・取り消しもここ (旧 Api\ReturnController / Api\OrdersController::payment / delete)。
 */
class OrderService
{
    /** タグを発行しない品物の印 (旧と同じ) */
    public const NO_TAG = '0-000';

    public function __construct(
        private readonly TagAllocator $tags,
        private readonly PriceCalculator $prices,
        private readonly InvoiceService $invoices,
    ) {}

    public function store(StoreOrderData $d): Order
    {
        // 商品は 1 回で読む (店舗のスコープ付き。他店舗・削除済みは見えない)
        $ids = array_column($d->items, 'clothes_id');
        $clothes = Clothes::whereIn('id', $ids)->get()->keyBy('id');
        foreach ($d->items as $i => $item) {
            if (! $clothes->has($item['clothes_id'])) {
                throw ValidationException::withMessages(["items.{$i}.clothes_id" => '商品が見つかりません。']);
            }
        }

        $subtotal = $this->prices->orderTotal(
            array_map(fn ($item) => $this->prices->lineTotal((int) $clothes[$item['clothes_id']]->price, $item['qty']), $d->items),
            null, null,
        );
        try {
            $reduction = $this->prices->reduction($subtotal, $d->discountRatio, $d->discountAmount);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['discount_amount' => '値引額が合計を超えています。']);
        }
        $total = $subtotal - $reduction;

        $cashPaid = ! $d->isInvoice && ! $d->notPaid;
        if ($cashPaid && $d->deposit < $total) {
            throw ValidationException::withMessages(['deposit' => 'お預り金額が合計に足りません。']);
        }

        $now = CarbonImmutable::now();
        $orderDate = $d->orderedOn?->startOfDay() ?? $now;

        // 支払い日時 (旧と同じ): 現金 = 注文日 (指定なしは今)、未収 = null、
        // 請求書払い = 入金確認ありの顧客は請求期間の終了日、なしは null
        $paidAt = $cashPaid ? $orderDate : null;
        if ($d->isInvoice && $d->customer->needs_payment_confirmation) {
            [, $periodEnd] = InvoicePeriod::for((int) $d->customer->cutoff_date, $orderDate);
            $paidAt = $periodEnd;
        }

        return DB::transaction(function () use ($d, $clothes, $total, $reduction, $paidAt, $orderDate, $now) {
            // 返却確認しない請求書払いは、注文日の期間の請求書に今すぐ載せる
            $invoice = ($d->isInvoice && ! $d->checkReturn)
                ? $this->invoices->findOrCreateFor($d->customer, $orderDate)
                : null;
            $handedAt = $d->checkReturn ? null : $now;

            $order = new Order;
            $order->forceFill([
                'store_id' => Auth::id(), // 入力の store_id は使わない
                'manager_id' => $d->manager->id,
                'customer_id' => $d->customer->id,
                'invoice_id' => $invoice?->id,
                'is_invoice' => $d->isInvoice,
                'amount' => $total,
                'reduction' => $reduction,
                'discount' => $d->discountRatio ?? 0,
                'payment' => $d->deposit,
                'paid_at' => $paidAt,
                'handed_at' => $handedAt,
            ]);
            if ($d->orderedOn !== null) {
                $order->created_at = $orderDate;
            }
            $order->save();

            // 品物 1 点につきタグ max(1, tag_count) 枚。1 枚目は商品の行、2 枚目以降は 999 の行 (旧と同じ)
            $perUnit = fn (Clothes $c) => max(1, (int) $c->tag_count);
            $needed = 0;
            foreach ($d->items as $item) {
                if ($item['issue_tag']) {
                    $needed += $item['qty'] * $perUnit($clothes[$item['clothes_id']]);
                }
            }
            $tags = $this->tags->allocate($d->manager->id, $needed);

            $rows = [];
            $next = 0;
            foreach ($d->items as $item) {
                $c = $clothes[$item['clothes_id']];
                for ($q = 0; $q < $item['qty']; $q++) {
                    for ($t = 0; $t < $perUnit($c); $t++) {
                        $rows[] = [
                            'order_id' => $order->id,
                            'clothes_id' => $t === 0 ? $c->id : Clothes::MULTI_TAG_CLOTHES_ID,
                            'tag' => $item['issue_tag'] ? $tags[$next++]->toString() : self::NO_TAG,
                            'handed_at' => $handedAt,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }
            OrderItem::query()->insert($rows);

            Customer::whereKey($d->customer->id)->incrementEach([
                'total_sales' => $total,
                'number_of_visits' => 1,
            ]);

            return $order->setRelation('items', OrderItem::where('order_id', $order->id)->orderBy('id')->get());
        });
    }

    /**
     * 品物を渡す (旧 Api\ReturnController::update)。品物はこの注文のものだけ (呼ぶ側で確認済み)。
     * 全部渡し終わったら注文も渡し済みにする (markReturned)。日付の指定があればその日の 00:00、なければ今 (旧と同じ)。
     */
    public function handItems(Order $o, array $itemIds, ?CarbonImmutable $handedOn = null): void
    {
        DB::transaction(function () use ($o, $itemIds, $handedOn) {
            $o = Order::whereKey($o->id)->lockForUpdate()->firstOrFail();
            OrderItem::where('order_id', $o->id)->whereIn('id', $itemIds)->update(['handed_at' => CarbonImmutable::now()]);

            if (! OrderItem::where('order_id', $o->id)->whereNull('handed_at')->exists()) {
                $this->markReturned($o, $handedOn?->startOfDay() ?? CarbonImmutable::now());
            }
        });
    }

    /**
     * 注文を渡し済みにする。請求書払いなら、お渡し日の期間の請求書に載せる。
     * 請求書は注文経路と同じ InvoiceService::findOrCreateFor で探す・作る (同じ顧客・同じ日なら同じ請求書・同じ paid_at)。
     */
    public function markReturned(Order $o, CarbonImmutable $at): void
    {
        DB::transaction(function () use ($o, $at) {
            $o->handed_at = $at;
            if ($o->is_invoice) {
                $customer = Customer::withTrashed()->findOrFail($o->customer_id);
                $o->invoice_id = $this->invoices->findOrCreateFor($customer, $at)->id;
            }
            $o->save();
        });
    }

    /** 入金 (旧 Api\OrdersController::payment): お預り金額と入金日時 (今) を入れる。 */
    public function pay(Order $o, int $amount): void
    {
        $o->forceFill(['payment' => $amount, 'paid_at' => CarbonImmutable::now()])->save();
    }

    /**
     * 注文の取り消し (旧 destroy / Api\OrdersController::delete) を 1 トランザクションで。
     * - 注文と品物を論理削除し、顧客の累計売上・来店回数を戻す (注文の顧客で。旧は画面から送られた顧客で、全体検索・直前預り取り消しでは戻していなかった)。
     * - 請求書に載っていた注文なら、請求書に他の注文が残っていないときだけ請求書も論理削除する (旧の意図。旧はここで 500 になり請求書が残っていた)。
     *   ただし発行済み (issued_at あり) と、繰越の元・先になっている請求書は空でも残す (渡した請求書・繰越額の記録を消さない)。
     */
    public function delete(Order $o): void
    {
        DB::transaction(function () use ($o) {
            $o = Order::whereKey($o->id)->lockForUpdate()->firstOrFail();
            $invoiceId = $o->invoice_id;

            OrderItem::where('order_id', $o->id)->delete();
            $o->delete();
            Customer::withTrashed()->whereKey($o->customer_id)->decrementEach([
                'total_sales' => (int) $o->amount,
                'number_of_visits' => 1,
            ]);

            if ($invoiceId === null) {
                return;
            }
            $invoice = Invoice::whereKey($invoiceId)->lockForUpdate()->first();
            if ($invoice === null
                || Order::where('invoice_id', $invoice->id)->exists()
                || $invoice->issued_at !== null
                || $invoice->has_carried_over
                || $invoice->carry_over_id !== null
                || Invoice::where('carry_over_id', $invoice->id)->exists()) {
                return;
            }
            $invoice->delete();
        });
    }
}
