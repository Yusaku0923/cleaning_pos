<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Utility;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function search(Request $request, Invoice $model) {
        $invoices = $model->fetchInvoices($request->manager_id, $request->conditions);

        return response()->json([
            'invoices' => $invoices,
        ]);
    }

    public function carry_over(Request $request, Invoice $model) {
        $invoices = $request->invoices;
        foreach ($invoices as $invoice_id) {
            $invoice = Invoice::find($invoice_id);
            // 翌月の請求書の期間を取得
            $cutoff_date = Customer::where('id', $invoice->customer_id)->value('cutoff_date');
            $next_start = date('Y-m-d', strtotime($invoice->period_end . ' +1 day'));
            list($period_start, $period_end) = Utility::currentInvoicePeriod($cutoff_date, $next_start);
            // 対象の翌月の請求書を検索
            $next_id = $model->existsTargetInvoice($invoice->customer_id, $period_start, $period_end);
            if (!is_null($next_id)) {
                // 存在していた場合はIDを取得
                $next_invoice = Invoice::find($next_id);
                $next_invoice->has_carried_over = true;
                $next_invoice->save();
            } else {
                // 存在しない場合は作成し、ID取得
                $model = new Invoice();
                $model->manager_id = $invoice->manager_id;
                $model->customer_id = $invoice->customer_id;
                $model->period_start = $period_start;
                $model->period_end = $period_end;
                $model->has_carried_over = true;
                $model->save();

                $next_id = $model->id;
            }

            if ($invoice->has_carried_over) {
                // 選択した請求書が既に繰り越されていた場合、それに紐づくレコードを更新
                $children = Invoice::where('carry_over_id', $invoice->id)->get();
                foreach ($children as $child) {
                    $child->carry_over_id = $next_id;
                    $child->save();
                }
                // 繰越フラグを折る
                $invoice->has_carried_over = false;
            }

            // 選択した請求書の親レコードを更新
            $invoice->carry_over_id = $next_id;
            $invoice->save();
        }

        return response()->json([
            'ok' => true,
            'message' => '繰越処理が完了しました'
        ]);
    }
}
