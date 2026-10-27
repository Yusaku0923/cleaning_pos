<?php

namespace App\Domain\Billing;

use App\Domain\Money\PriceCalculator;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Model;

/**
 * 画面・PDF に渡す請求書の一覧 (旧 Invoice::fetchInvoices / fetchInvoicesNeedsPaymentConfimation / fetchPrintInvoices)。
 * 返す配列の形 (キー・並び・値の型) は旧と同じ。日付は DB の値のまま ('Y-m-d'。取り込んだ '-00' の日付もそのまま)。
 * 店舗は BelongsToStore で絞られる (旧は全店舗を見ていた)。注文・品物は請求書ごとに読まず、まとめて読む。
 */
class InvoiceQueries
{
    /** 旧 fetchPrintInvoices の 1 ページの行数 */
    private const ROWS_PER_PAGE = 30;

    public function __construct(
        private InvoiceService $invoices,
        private PriceCalculator $calc,
    ) {}

    /**
     * 旧 fetchInvoices($manager_id, $condition)。$conditions null = 今月 (終了日が今月) の請求書。
     * $conditions のキー: cutoff_date (旧と同じく !== 0 なら締め日で絞る)、target_month ('YYYY-MM')、customer_name (部分一致)。
     */
    public function fetchInvoices(int $managerId, ?array $conditions = null): array
    {
        $query = Invoice::query()
            ->select('invoices.*', 'customers.name', 'customers.name_kana', 'customers.cutoff_date')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->where('invoices.manager_id', $managerId);
        if ($conditions !== null) {
            if (($conditions['cutoff_date'] ?? null) !== 0) {
                $query->where('customers.cutoff_date', $conditions['cutoff_date'] ?? null);
            }
            if (! empty($conditions['target_month'])) {
                $month = strtotime((string) $conditions['target_month']);
                $query->where('invoices.period_end', '>=', date('Y-m-01', $month))
                    ->where('invoices.period_end', '<=', date('Y-m-t', $month));
            }
            if (! empty($conditions['customer_name'])) {
                $query->where('customers.name', 'like', '%'.$conditions['customer_name'].'%');
            }
        } else {
            $query->where('invoices.period_end', '>=', now()->format('Y-m-01'))
                ->where('invoices.period_end', '<=', now()->format('Y-m-t'));
        }
        $models = $query->orderBy('invoices.period_end', 'asc')->orderBy('invoices.id', 'asc')->get();

        $ids = $models->pluck('id')->all();
        $amounts = $this->amounts($ids);
        $carried = $this->carriedOverAmounts($models);
        $customers = Customer::whereIn('id', $models->pluck('customer_id')->unique()->all())->get()->keyBy('id');

        return $models->map(function (Invoice $invoice) use ($amounts, $carried, $customers) {
            $row = $this->raw($invoice);
            $row['amount'] = $amounts[$invoice->id] ?? 0;
            $customer = $customers[$invoice->customer_id] ?? null;
            // 旧は顧客 (削除済みは見えない) の締め日で判定。見えない顧客は旧と同じく常にズレ
            $row['is_mismatch_cutoff_date'] = $customer === null || $this->invoices->isMismatchCutoff($invoice, $customer);
            $row['carried_over_amount'] = $carried[$invoice->id] ?? 0;

            return $row;
        })->all();
    }

    /** 旧 fetchInvoicesNeedsPaymentConfimation: 顧客の入金未確認 (paid_at なし) の請求書と、その注文・品物。 */
    public function fetchInvoicesNeedingPaymentConfirmation(int $customerId): array
    {
        $models = Invoice::query()
            ->select('invoices.*', 'customers.name', 'customers.name_kana', 'customers.cutoff_date')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->where('invoices.customer_id', $customerId)
            ->whereNull('invoices.paid_at')
            ->orderBy('invoices.period_end', 'asc')->orderBy('invoices.id', 'asc')
            ->get();

        $ids = $models->pluck('id')->all();
        $orders = $this->ordersWithItems($ids, orderByHanded: false);
        $amounts = $this->amounts($ids);
        $carried = $this->carriedOverAmounts($models);

        return $models->map(function (Invoice $invoice) use ($orders, $amounts, $carried) {
            $row = $this->raw($invoice);
            $row['orders'] = $orders[$invoice->id] ?? [];
            $row['amount'] = $amounts[$invoice->id] ?? 0;
            $row['carried_over_amount'] = $carried[$invoice->id] ?? 0;

            return $row;
        })->all();
    }

    /**
     * 旧 fetchPrintInvoices: PDF のページ (30 行ごと) の配列。並びは $ids の順 (旧と同じ FIELD(id, ...)。値はバインドする)。
     * 各ページに tax_amount (請求書全体の内税。端数は config('pos.tax_rounding.invoice')) を足す (旧は Blade で計算していた)。
     *
     * @param  list<int>  $ids
     */
    public function fetchPrintInvoices(array $ids, int $taxRate): array
    {
        $models = Invoice::query()->whereIn('id', $ids)
            ->orderByRaw('FIELD(id, '.implode(', ', array_fill(0, count($ids), '?')).')', $ids)
            ->get();
        $invoiceIds = $models->pluck('id')->all();
        $orders = $this->ordersWithItems($invoiceIds, orderByHanded: true);
        $amounts = $this->amounts($invoiceIds);
        $carried = $this->carriedOverAmounts($models);
        $names = Customer::whereIn('id', $models->pluck('customer_id')->unique()->all())->pluck('name', 'id');
        $mode = (string) config('pos.tax_rounding.invoice');

        $invoices = $models->map(function (Invoice $invoice) use ($orders, $amounts, $carried, $names, $taxRate, $mode) {
            $row = $this->raw($invoice);
            $row['orders'] = $orders[$invoice->id] ?? [];
            $row['amount'] = $amounts[$invoice->id] ?? 0;
            $row['carried_over_amount'] = $carried[$invoice->id] ?? 0;
            $row['customer_name'] = $names[$invoice->customer_id] ?? null;
            $row['tax_amount'] = $this->calc->includedTax((int) $row['amount'], $taxRate, $mode);

            return $row;
        })->all();

        return $this->paginate($invoices);
    }

    /** PDF に出す税率 (旧は Tax::where('store_id', 1))。 */
    public function taxRate(): int
    {
        return (int) (Tax::query()->value('tax') ?? 10);
    }

    /**
     * 旧 fetchPrintInvoices の「PDF出力用にフォーマットを整える(30行毎)」をそのまま。
     * 同じ商品が続く品物は 1 行 (is_detail 2: start_tag〜end_tag × 点数)、単独は is_detail 1、注文の合計行は is_detail 0。
     */
    private function paginate(array $invoices): array
    {
        $formated = [];
        $row = 0;
        $page_count = 1;
        $prev_item = 0;
        $item_count = 0;
        $start_tag = '';
        foreach ($invoices as $invoice) {
            $invoice['page_count'] = $page_count;
            $page = $invoice;
            foreach ($invoice['orders'] as $order) {
                foreach ($order['items'] as $key => $item) {
                    // 単一レコードの場合
                    if ($prev_item != $item['id'] && (array_key_last($order['items']) == $key || $order['items'][$key + 1]['id'] != $item['id'])) {
                        $prev_item = $item['id'];

                        $item['order_id'] = $order['id'];
                        $item['ordered_at'] = $order['created_at'];
                        $item['handed_at'] = ! is_null($order['handed_at']) ? $order['handed_at'] : $order['created_at'];
                        $item['count'] = 1;
                        $item['is_detail'] = 1;

                        $page['row'][] = $item;
                        $row++;

                        // 複数レコードの場合
                    } else {
                        $prev_item = $item['id'];
                        $item_count++;
                        if (empty($start_tag)) {
                            $start_tag = $item['tag'];
                        }
                        if (array_key_last($order['items']) == $key || $order['items'][$key + 1]['id'] != $item['id']) {
                            $item['order_id'] = $order['id'];
                            $item['ordered_at'] = $order['created_at'];
                            $item['handed_at'] = ! is_null($order['handed_at']) ? $order['handed_at'] : $order['created_at'];
                            $item['count'] = $item_count;
                            $item['start_tag'] = $start_tag;
                            $item['end_tag'] = $item['tag'];
                            $item['is_detail'] = 2;

                            $page['row'][] = $item;
                            $row++;

                            $start_tag = '';
                            $item_count = 0;
                        } else {
                            continue;
                        }
                    }

                    if ($row >= self::ROWS_PER_PAGE) {
                        $formated[] = $page;
                        $invoice['page_count']++;
                        $page = $invoice;
                        $row = 0;
                    }
                }
                // 注文ごとに初期化
                $prev_item = 0;

                $order['is_detail'] = 0;
                $page['row'][] = $order;
                $row++;

                if ($row >= self::ROWS_PER_PAGE) {
                    $formated[] = $page;
                    $invoice['page_count']++;
                    $page = $invoice;
                    $row = 0;
                }
            }
            if ($row !== 0) {
                $formated[] = $page;
                $row = 0;
                $page_count = 1;
            }
        }

        return $formated;
    }

    /** モデルの DB の値そのまま (日付を ISO 文字列にしない。旧のモデルは日付を cast していなかった) */
    private function raw(Model $model): array
    {
        return $model->getAttributes();
    }

    /**
     * 請求書ごとの注文 (削除済みは除く) と、その品物 (旧: order_clothes.tag + clothes.*。品物の id は商品の id)。
     *
     * @return array<int, list<array>>
     */
    private function ordersWithItems(array $invoiceIds, bool $orderByHanded): array
    {
        if ($invoiceIds === []) {
            return [];
        }
        $query = Order::whereIn('invoice_id', $invoiceIds);
        if ($orderByHanded) {
            $query->orderBy('handed_at', 'asc');
        }
        $orders = $query->orderBy('id', 'asc')->get()->map(fn (Order $o) => $this->raw($o))->all();

        $items = [];
        $rows = OrderItem::query()
            ->select('order_items.order_id as __order_id', 'order_items.tag', 'clothes.*')
            ->join('clothes', 'order_items.clothes_id', '=', 'clothes.id')
            ->whereIn('order_items.order_id', array_column($orders, 'id'))
            ->orderBy('order_items.id', 'asc')
            ->get();
        foreach ($rows as $item) {
            $attrs = $this->raw($item);
            $orderId = $attrs['__order_id'];
            unset($attrs['__order_id']);
            $items[$orderId][] = $attrs;
        }

        $byInvoice = [];
        foreach ($orders as $order) {
            $order['items'] = $items[$order['id']] ?? [];
            $byInvoice[$order['invoice_id']][] = $order;
        }

        return $byInvoice;
    }

    /**
     * 請求書ごとの注文の合計 (旧 Order::where('invoice_id', ...)->sum('amount')。旧と同じく DB の SUM の値のまま = 文字列)。
     *
     * @return array<int, mixed>
     */
    private function amounts(array $invoiceIds): array
    {
        if ($invoiceIds === []) {
            return [];
        }

        return Order::whereIn('invoice_id', $invoiceIds)
            ->groupBy('invoice_id')
            ->selectRaw('invoice_id, sum(amount) as total')
            ->pluck('total', 'invoice_id')
            ->map(fn ($total) => $total ?: 0) // 旧 Builder::sum と同じ ('0' は 0)
            ->all();
    }

    /**
     * 旧 calcCarryOver: 繰越を受けた請求書 (has_carried_over) ごとの、繰越元 (carry_over_id = その ID) の注文の合計 (整数)。
     *
     * @param  iterable<Invoice>  $invoices
     * @return array<int, int>
     */
    private function carriedOverAmounts(iterable $invoices): array
    {
        $targets = [];
        foreach ($invoices as $invoice) {
            if ($invoice->has_carried_over) {
                $targets[] = $invoice->id;
            }
        }
        if ($targets === []) {
            return [];
        }
        $sources = Invoice::whereIn('carry_over_id', $targets)->get(['id', 'carry_over_id']);
        $amounts = $this->amounts($sources->pluck('id')->all());

        $sum = array_fill_keys($targets, 0);
        foreach ($sources as $source) {
            $sum[$source->carry_over_id] += (int) ($amounts[$source->id] ?? 0);
        }

        return $sum;
    }
}
