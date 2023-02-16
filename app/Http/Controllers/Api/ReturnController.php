<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\OrderClothes;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\Utility;

class ReturnController extends Controller
{
    public function update(Request $request) {
        OrderClothes::whereIn('id', $request->items)
                    ->update(['handed_at'=> date('Y-m-d H:i:s')]);
        $order_id = OrderClothes::where('id', $request->items[0])->value('order_id');
        $never_handed_all = OrderClothes::where('order_id', $order_id)
                                    ->whereNull('handed_at')
                                    ->exists();
        if (!$never_handed_all) {
            $handed_at = empty($request->handed_at) ? date('Y-m-d H:i:s'): date('Y-m-d 00:00:00', strtotime($request->handed_at));
            Log::debug($handed_at);
            Log::debug($request->handed_at);
            $order = Order::find($order_id);
            $order->handed_at = $handed_at;
            $order->save();

            if ($order->is_invoice) {
                $cutoff_date = Customer::where('id', $order->customer_id)->value('cutoff_date');
                list($period_start, $period_end) = Utility::currentInvoicePeriod($cutoff_date, $handed_at);
                // 入金確認が必要なお客様は「paid_at」を埋めない
                if ((boolean)Customer::where('id', $order->customer_id)->value('needs_payment_confimation')) {
                    $paid_at = null;
                } else {
                    $paid_at = $period_end;
                }
                $model = new Invoice();
                $invoice_id = $model->existsTargetInvoice($order->customer_id, $period_start, $period_end);
                if (!is_null($invoice_id)) {
                    $invoice = Invoice::find($invoice_id);
                } else {
                    $invoice = Invoice::query()->create([
                        'manager_id' => $order->manager_id,
                        'customer_id' => $order->customer_id,
                        'period_start' => $period_start,
                        'period_end' => $period_end,
                        'paid_at' => $paid_at,
                    ]);
                    $invoice_id = $invoice->id;
                }
                
                $order->invoice_id = $invoice_id;
                $order->save();
            }
        }

        return response()->json([
            'ok' => true
        ]);
    }
}
