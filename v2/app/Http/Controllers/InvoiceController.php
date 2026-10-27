<?php

namespace App\Http\Controllers;

use App\Domain\Billing\InvoiceQueries;
use App\Models\Customer;
use App\Models\CustomerInformation;
use App\Models\Invoice;
use App\Models\Manager;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 旧 InvoiceController (請求書発行・入金確認・請求書 PDF)。担当者・顧客は URL (?m=&c=) から。
 * PDF の GET は表示だけ。発行済みの記録 (issued_at) は POST /invoice/issue (画面が PDF を開く前に呼ぶ)。
 */
class InvoiceController extends Controller
{
    /** 1 回に PDF にできる請求書の数 (画面で選べる数より十分大きく) */
    private const MAX_IDS = 500;

    public function index(Request $request, InvoiceQueries $invoices)
    {
        /** @var Manager|null $manager */
        $manager = $request->attributes->get('manager');
        if ($manager === null) {
            return redirect(pos_url('home'));
        }

        return view('invoice.index')->with([
            'title' => '請　求　書　発　行',
            'invoices' => $invoices->fetchInvoices($manager->id),
        ]);
    }

    /** 旧 payment_confimation (URL は旧と同じ /invoice/payment_confimation) */
    public function paymentConfirmation(Request $request, InvoiceQueries $invoices)
    {
        /** @var Customer|null $customer */
        $customer = $request->attributes->get('customer');
        if ($customer === null) {
            return redirect(pos_url('home'));
        }

        return view('invoice.payment_confirmation')->with([
            'title' => '入　金　確　認',
            'customer' => $customer,
            'invoices' => $invoices->fetchInvoicesNeedingPaymentConfirmation($customer->id),
            'customer_info' => CustomerInformation::forScreen($customer),
        ]);
    }

    /**
     * GET /invoice/pdf?ids=3,1,2 (旧 generate)。ids の順に PDF にする。表示だけで issued_at は変えない。
     * ids は「正の整数をカンマで区切った文字列」だけ受け付ける (それ以外は 422)。他店舗の請求書が含まれれば 404。
     */
    public function pdf(Request $request, InvoiceQueries $invoices)
    {
        if ($request->attributes->get('manager') === null) {
            return redirect(pos_url('invoice.index'));
        }
        $raw = $request->query('ids');
        if (! is_string($raw) || preg_match('/\A[1-9][0-9]{0,18}(,[1-9][0-9]{0,18})*\z/', $raw) !== 1) {
            abort(422, '請求書の指定が正しくありません。');
        }
        $ids = array_values(array_unique(array_map('intval', explode(',', $raw))));
        abort_if(count($ids) > self::MAX_IDS, 422, '請求書が多すぎます。');
        $this->assertVisible($ids);

        $taxRate = $invoices->taxRate();
        $pdf = Pdf::loadView('invoice.pdf', [
            'invoices' => $invoices->fetchPrintInvoices($ids, $taxRate),
            'tax' => $taxRate,
        ]);
        $pdf->setPaper('A4');

        return $this->pdfInline($pdf, '日報', 'invoice'); // ファイル名は旧と同じ
    }

    /** POST /invoice/issue {ids: [請求書ID]} → {ok: true}。issued_at に今日を入れる (旧は PDF の GET で入れていた)。 */
    public function issue(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:'.self::MAX_IDS],
            'ids.*' => ['integer', 'min:1'],
        ]);
        $ids = array_values(array_unique(array_map('intval', $data['ids'])));
        $this->assertVisible($ids);

        Invoice::whereIn('id', $ids)->update(['issued_at' => now()->toDateString()]);

        return response()->json(['ok' => true]);
    }

    /** 全部が自店舗の (削除されていない) 請求書でなければ 404 */
    private function assertVisible(array $ids): void
    {
        abort_if(Invoice::whereIn('id', $ids)->count() !== count($ids), 404);
    }
}
