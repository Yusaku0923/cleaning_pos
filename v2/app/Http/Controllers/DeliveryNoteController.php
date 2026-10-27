<?php

namespace App\Http\Controllers;

use App\Domain\Delivery\DeliverySummary;
use App\Mail\DeliveryNoteMail;
use App\Models\DeliveryCustomer;
use App\Models\DeliveryDailyEntry;
use App\Models\DeliveryEmailLog;
use App\Models\DeliveryNote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/** 旧 DeliveryNoteController (納品書の管理画面・PDF・メール・スマホ入力の入口)。集計は DeliverySummary。 */
class DeliveryNoteController extends Controller
{
    public function __construct(private readonly DeliverySummary $summary) {}

    /** iPad管理トップ */
    public function index()
    {
        $customers = DeliveryCustomer::all();

        return view('delivery.index', compact('customers'));
    }

    /** 集計・PDFプレビューページ */
    public function notes(DeliveryCustomer $customer)
    {
        return view('delivery.notes', compact('customer'));
    }

    /**
     * PDFダウンロード (旧と同じ GET)。同じ期間の納品書があればその番号で、なければ採番して作り、
     * 期間内の未確定の記帳をその納品書に結びつける (確定)。
     */
    public function downloadPdf(Request $request, DeliveryCustomer $customer)
    {
        $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'cutoff_date' => 'nullable|date',
        ]);

        $periodStart = $request->period_start;
        $periodEnd = $request->period_end;
        $cutoffDate = $request->cutoff_date ?? $periodEnd;

        $note = $this->summary->existingNote($customer, $periodStart, $periodEnd)
            ?? DeliveryNote::createWithNumber($customer->id, $periodStart, $periodEnd);

        $summary = $this->summary->summarize($customer, $periodStart, $periodEnd);
        $entries = $summary['entries'];
        $taxGroups = $summary['tax_groups'];

        DeliveryDailyEntry::whereHas('product.department', function ($q) use ($customer) {
            $q->where('delivery_customer_id', $customer->id);
        })
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->whereNull('delivery_note_id')
            ->update(['delivery_note_id' => $note->id]);

        $pdf = Pdf::loadView('delivery.pdf', compact('customer', 'note', 'entries', 'taxGroups', 'periodStart', 'periodEnd', 'cutoffDate'));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream("納品書_{$customer->name}_No{$note->note_number}.pdf");
    }

    /** メール送信確認・本文編集ページ */
    public function emailForm(Request $request, DeliveryCustomer $customer)
    {
        $note = $this->noteOf($request, $customer);
        $defaultBody = $this->summary->defaultEmailBody($customer, $note->period_start, $note->period_end);

        return view('delivery.email_form', compact('customer', 'note', 'defaultBody'));
    }

    /** メール送信実行。結果 (成功・失敗) は送信履歴に残す。 */
    public function sendEmail(Request $request, DeliveryCustomer $customer)
    {
        $request->validate(['body' => 'required|string']);
        $note = $this->noteOf($request, $customer);
        abort_if(blank($customer->email), 422, 'メールアドレスが設定されていません');

        $summary = $this->summary->summarize($customer, $note->period_start->toDateString(), $note->period_end->toDateString());

        $status = 'success';
        try {
            Mail::to($customer->email)->send(
                new DeliveryNoteMail($customer, $note, $summary['entries'], $summary['tax_groups'], $request->body)
            );
        } catch (\Throwable $e) {
            report($e);
            $status = 'failed';
        }

        DeliveryEmailLog::create([
            'delivery_note_id' => $note->id,
            'delivery_customer_id' => $customer->id,
            'period_start' => $note->period_start->toDateString(),
            'period_end' => $note->period_end->toDateString(),
            'sent_to' => $customer->email,
            'sent_at' => now(),
            'status' => $status,
        ]);

        $message = $status === 'success' ? '送信しました' : '送信に失敗しました';

        return redirect(pos_url('delivery.email_logs'))->with('message', $message);
    }

    /** 送信履歴 */
    public function emailLogs()
    {
        $logs = DeliveryEmailLog::with(['customer', 'note'])->orderByDesc('sent_at')->orderByDesc('id')->paginate(20);

        return view('delivery.email_logs', compact('logs'));
    }

    /** SP選択画面 */
    public function spChoice()
    {
        return view('delivery.sp_choice');
    }

    /** SP入力画面コンテナ */
    public function spEntry()
    {
        $customers = DeliveryCustomer::with('departments.products')->get();

        return view('delivery.sp_entry', compact('customers'));
    }

    /** note_id は、その取引先 (自店舗) の納品書だけ受け付ける。 */
    private function noteOf(Request $request, DeliveryCustomer $customer): DeliveryNote
    {
        $request->validate([
            'note_id' => ['required', Rule::exists('delivery_notes', 'id')->where('delivery_customer_id', $customer->id)],
        ]);

        return DeliveryNote::findOrFail($request->note_id);
    }
}
