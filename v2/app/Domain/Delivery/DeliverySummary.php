<?php

namespace App\Domain\Delivery;

use App\Domain\Money\PriceCalculator;
use App\Models\DeliveryCustomer;
use App\Models\DeliveryDailyEntry;
use App\Models\DeliveryNote;
use Carbon\CarbonInterface;

/**
 * 納品書の集計 (旧 DeliveryNoteController::aggregateEntries / calcTax と Api\DeliveryEntryController::preview の重複を 1 つに)。
 * 期間内の記帳を部署・品目ごとに合計し、税率ごとに税を出す。税の端数は config('pos.tax_rounding.delivery') (既定は切り捨て)。
 */
class DeliverySummary
{
    public function __construct(private readonly PriceCalculator $calculator) {}

    /**
     * @return array{entries: list<array<string, mixed>>, tax_groups: list<array<string, mixed>>, has_finalized: bool}
     */
    public function summarize(DeliveryCustomer $customer, string $start, string $end): array
    {
        $departments = $customer->departments()->with('products')->get();

        // 品目ごとの数量合計と「別の納品書に入っている記帳があるか」を 1 回で集める
        $productIds = $departments->flatMap(fn ($d) => $d->products->pluck('id'))->all();
        $sums = DeliveryDailyEntry::query()
            ->whereIn('delivery_product_id', $productIds)
            ->whereBetween('date', [$start, $end])
            ->groupBy('delivery_product_id')
            ->selectRaw('delivery_product_id, sum(quantity) as qty, count(delivery_note_id) as finalized')
            ->get()
            ->keyBy('delivery_product_id');

        $entries = [];
        $hasFinalized = false;
        foreach ($departments as $dept) {
            $deptData = ['name' => $dept->name, 'products' => [], 'subtotal' => 0];
            foreach ($dept->products as $product) {
                $row = $sums->get($product->id);
                $qty = (int) ($row->qty ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                $hasFinalized = $hasFinalized || (int) $row->finalized > 0;
                $amount = $qty * $product->unit_price;
                $deptData['products'][] = [
                    'name' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => $product->unit_price,
                    'amount' => $amount,
                    'tax_rate' => $product->tax_rate,
                ];
                $deptData['subtotal'] += $amount;
            }
            if ($deptData['products'] !== []) {
                $entries[] = $deptData;
            }
        }

        return [
            'entries' => $entries,
            'tax_groups' => $this->taxGroups($entries),
            'has_finalized' => $hasFinalized,
        ];
    }

    /** 税率ごとの税抜小計・消費税・税込小計 (税率の出てきた順)。 */
    public function taxGroups(array $entries): array
    {
        $groups = [];
        foreach ($entries as $dept) {
            foreach ($dept['products'] as $product) {
                $percent = (int) round($product['tax_rate'] * 100);
                $groups[$percent] ??= ['rate' => $product['tax_rate'], 'subtotal' => 0];
                $groups[$percent]['subtotal'] += $product['amount'];
            }
        }

        $mode = config('pos.tax_rounding.delivery');
        $result = [];
        foreach ($groups as $percent => $group) {
            $tax = $this->calculator->tax($group['subtotal'], $percent, $mode);
            $result[] = [
                'rate' => $group['rate'],
                'subtotal_excl' => $group['subtotal'],
                'tax' => $tax,
                'subtotal_incl' => $group['subtotal'] + $tax,
            ];
        }

        return $result;
    }

    /** メール本文の初期値 (旧と同じ文面)。 */
    public function defaultEmailBody(DeliveryCustomer $customer, CarbonInterface $start, CarbonInterface $end): string
    {
        $s = $start->format('n月j日');
        $e = $end->format('n月j日');

        return "{$customer->name} 御中\n\nいつもお世話になっております。\nあさひ屋クリーニングです。\n\n{$s}〜{$e}分の納品書を添付いたします。\nご確認のほどよろしくお願いいたします。\n\nあさひ屋クリーニング";
    }

    /** 既存の納品書 (同じ取引先・同じ期間)。 */
    public function existingNote(DeliveryCustomer $customer, string $start, string $end): ?DeliveryNote
    {
        return DeliveryNote::where('delivery_customer_id', $customer->id)
            ->where('period_start', $start)
            ->where('period_end', $end)
            ->first();
    }
}
