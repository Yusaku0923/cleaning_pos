<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use App\Services\Utility;

class Invoice extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($invoice) {
            $invoice->orders()->delete();
        });
    }

    public function fetchInvoices($manager_id, $condition = null) {
        $query = Invoice::query();
        $query->select('invoices.*', 'customers.name', 'customers.name_kana', 'customers.cutoff_date');
        $query->join('customers', 'invoices.customer_id', '=', 'customers.id');
        $query->where('invoices.manager_id', $manager_id);
        if (!is_null($condition)) {
            if ($condition['cutoff_date'] !== 0) $query->where('customers.cutoff_date', $condition['cutoff_date']);
            if (!empty($condition['target_month'])) {
                $query->where('invoices.period_end', '>=', date('Y-m-01', strtotime($condition['target_month'])));
                $query->where('invoices.period_end', '<=', date('Y-m-t', strtotime($condition['target_month'])));
            }
            if (!empty($condition['customer_name'])) $query->where('customers.name', 'like', '%'.$condition['customer_name'].'%');
        } else {
            $query->where('invoices.period_end', '>=', date('Y-m-01'));
            $query->where('invoices.period_end', '<=', date('Y-m-t'));
        }
        $query->orderBy('invoices.period_end', 'asc');
        $invoices = $query->get()->toArray();

        foreach ($invoices as $key => $invoice) {
            $invoices[$key]['amount'] = Order::where('invoice_id', $invoice['id'])->sum('amount');
        }

        return $invoices;
    }

    public function fetchInvoicesNeedsPaymentConfimation($customer_id) {
        $query = Invoice::query();
        $query->select('invoices.*', 'customers.name', 'customers.name_kana', 'customers.cutoff_date');
        $query->join('customers', 'invoices.customer_id', '=', 'customers.id');
        $query->where('invoices.customer_id', $customer_id);
        $query->whereNull('invoices.paid_at');
        $query->orderBy('invoices.period_end', 'asc');
        $invoices = $query->get()->toArray();

        foreach ($invoices as $key => $invoice) {
            $query = Order::query();
            $query->where('invoice_id', $invoice['id']);
            $orders = $query->get()->toArray();

            foreach ($orders as $i => $order) {
                $query = OrderClothes::query();
                $query->select('order_clothes.tag', 'clothes.*');
                $query->join('clothes', 'order_clothes.clothes_id', '=', 'clothes.id');
                $query->where('order_clothes.order_id', $order['id']);
                $orders[$i]['items'] = $query->get()->toArray();
            }

            $invoices[$key]['orders'] = $orders;
        }

        foreach ($invoices as $key => $invoice) {
            $invoices[$key]['amount'] = Order::where('invoice_id', $invoice['id'])->sum('amount');
        }

        return $invoices;
    }

    public function fetchPrintInvoices($ids) {
        $ids_array = explode(',', $ids);
        $query = Invoice::query();
        $query->whereIn('id', $ids_array);
        $query->orderByRaw("FIELD(id, $ids)");
        $invoices = $query->get()->toArray();

        foreach ($invoices as $key => $invoice) {
            $query = Order::query();
            $query->where('invoice_id', $invoice['id']);
            $query->orderBy('created_at', 'asc');
            $orders = $query->get()->toArray();

            foreach ($orders as $i => $order) {
                $query = OrderClothes::query();
                $query->select('order_clothes.tag', 'clothes.*');
                $query->join('clothes', 'order_clothes.clothes_id', '=', 'clothes.id');
                $query->where('order_clothes.order_id', $order['id']);
                $orders[$i]['items'] = $query->get()->toArray();
            }

            $invoices[$key]['orders'] = $orders;
            $invoices[$key]['amount'] = Order::where('invoice_id', $invoice['id'])->sum('amount');
        }

        // PDF出力用にフォーマットを整える(30行毎)
        $formated = [];
        $row = 0;
        $page_count = 1;
        $prev_item = 0;
        $item_count = 0;
        $start_tag = '';
        foreach ($invoices as $invoice) {
            $invoice['page_count'] = $page_count;
            $invoice['customer_name'] = Customer::where('id', $invoice['customer_id'])->value('name');
            $page = $invoice;
            foreach ($invoice['orders'] as $order) {
                foreach ($order['items'] as $key => $item) {
                    // 単一レコードの場合
                    if ($prev_item != $item['id'] && (array_key_last($order['items']) == $key || $order['items'][$key + 1]['id'] != $item['id'])) {
                        $prev_item = $item['id'];

                        $item['order_id'] = $order['id'];
                        $item['ordered_at'] = $order['created_at'];
                        $item['count'] = 1;
                        $item['is_detail'] = 1;

                        $page['row'][] = $item;
                        $row++;

                    // 複数レコードの場合
                    } else {
                        $prev_item = $item['id'];
                        $item_count++;
                        if (empty($start_tag)) $start_tag = $item['tag'];
                        if (array_key_last($order['items']) == $key || $order['items'][$key + 1]['id'] != $item['id']) {
                            $item['order_id'] = $order['id'];
                            $item['ordered_at'] = $order['created_at'];
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

                    if ($row >= 30) {
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

                if ($row >= 30) {
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

    public function existsTargetInvoice($customer_id, $period_start, $period_end) {

        $invoice = Invoice::where('customer_id', $customer_id)
                            ->where('period_start', $period_start)
                            ->where('period_end', $period_end)
                            ->first();

        return !empty($invoice) ? $invoice->id : null;
    }
}
