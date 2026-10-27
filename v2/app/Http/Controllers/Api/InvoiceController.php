<?php

namespace App\Http\Controllers\Api;

use App\Domain\Billing\InvoiceQueries;
use App\Domain\Billing\InvoiceService;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Manager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/** 旧 Api\InvoiceController (請求書発行画面の検索・繰越・締日整列) と、入金確認画面の「入金確認」。URL・キー名は旧と同じ。 */
class InvoiceController extends Controller
{
    /** POST /api/invoice/search {manager_id, conditions: {cutoff_date, target_month, customer_name}|null} → {invoices} */
    public function search(Request $request, InvoiceQueries $invoices): JsonResponse
    {
        $request->validate([
            'manager_id' => ['required', 'integer'],
            'conditions' => ['nullable', 'array'],
            'conditions.cutoff_date' => ['nullable', 'integer'],
            'conditions.target_month' => ['nullable', 'string', 'max:20'],
            'conditions.customer_name' => ['nullable', 'string', 'max:255'],
        ]);
        $manager = Manager::findOrFail((int) $request->input('manager_id'));

        // 締め日は送られた型のまま渡す (旧と同じく数値の 0 だけが「絞らない」)
        return response()->json([
            'invoices' => $invoices->fetchInvoices($manager->id, $request->input('conditions')),
        ]);
    }

    /** POST /api/invoice/carry_over {invoices: [請求書ID]} → {ok: true}。1 件ずつ旧と同じ繰越をする。 */
    public function carryOver(Request $request, InvoiceService $service): JsonResponse
    {
        foreach ($this->selected($request) as $invoice) {
            $service->carryOver($invoice);
        }

        return response()->json(['ok' => true]);
    }

    /** POST /api/invoice/align_cutoff_date {invoices: [請求書ID]} → {ok: true}。旧は配列を受けて 1 件ずつ処理する。 */
    public function alignCutoffDate(Request $request, InvoiceService $service): JsonResponse
    {
        foreach ($this->selected($request) as $invoice) {
            $service->alignCutoff($invoice);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * POST /api/invoice/payment_confirmation {invoice_id, payment} → {ok: true}。請求書の paid_at に今日を入れる。
     * 旧の画面は請求書 ID を order_id として /api/payment に送っていた (同じ ID の別の注文が入金済みになり、請求書は未確認のまま)。
     * 入力した金額 (payment) は旧と同じく受け取るが、請求書には入金額の列がないので保存しない。
     */
    public function paymentConfirmation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'invoice_id' => ['required', 'integer'],
            'payment' => ['nullable', 'integer', 'min:0'],
        ]);
        $invoice = Invoice::findOrFail((int) $data['invoice_id']);
        $invoice->forceFill(['paid_at' => now()->toDateString()])->save();

        return response()->json(['ok' => true]);
    }

    /** 選んだ請求書 (自店舗の、削除されていないもの)。1 件でも見えなければ何もせず 404。 */
    private function selected(Request $request): Collection
    {
        $data = $request->validate([
            'invoices' => ['required', 'array', 'min:1', 'max:500'],
            'invoices.*' => ['integer', 'min:1'],
        ]);
        $ids = array_values(array_unique(array_map('intval', $data['invoices'])));
        $invoices = Invoice::whereIn('id', $ids)->get()->keyBy('id');
        abort_if($invoices->count() !== count($ids), 404);

        return collect($ids)->map(fn (int $id) => $invoices[$id]);
    }
}
