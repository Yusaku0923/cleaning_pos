<?php

namespace App\Domain\Order;

use App\Domain\Billing\InvoicePeriod;
use App\Domain\Billing\InvoiceService;
use App\Domain\Money\PriceCalculator;
use App\Domain\Tag\TagAllocator;
use App\Models\Clothes;
use App\Models\Customer;
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
}
