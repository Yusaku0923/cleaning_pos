<?php

namespace App\Http\Controllers;

use App\Models\DeliveryCustomer;
use App\Models\DeliveryDailyEntry;
use App\Models\DeliveryNote;
use App\Models\DeliveryEmailLog;
use App\Mail\DeliveryNoteMail;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;

class DeliveryNoteController extends Controller
{
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

    /** PDFダウンロード */
    public function downloadPdf(Request $request, DeliveryCustomer $customer)
    {
        $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        $periodStart = $request->period_start;
        $periodEnd = $request->period_end;

        // 既存の納品書を検索、なければcreateWithNumberで採番して作成
        $note = DeliveryNote::where('delivery_customer_id', $customer->id)
            ->where('period_start', $periodStart)
            ->where('period_end', $periodEnd)
            ->first()
            ?? DeliveryNote::createWithNumber($customer->id, $periodStart, $periodEnd);

        // 対象期間のエントリを集計
        $entries = $this->aggregateEntries($customer, $periodStart, $periodEnd);

        // エントリにdelivery_note_idをセット（未確定のもののみ）
        DeliveryDailyEntry::whereHas('product.department', function ($q) use ($customer) {
            $q->where('delivery_customer_id', $customer->id);
        })
        ->whereBetween('date', [$periodStart, $periodEnd])
        ->whereNull('delivery_note_id')
        ->update(['delivery_note_id' => $note->id]);

        // 消費税計算
        $taxGroups = $this->calcTax($entries);

        $pdf = Pdf::loadView('delivery.pdf', compact('customer', 'note', 'entries', 'taxGroups', 'periodStart', 'periodEnd'));
        $pdf->setPaper('A4', 'portrait');

        $filename = "納品書_{$customer->name}_No{$note->note_number}.pdf";
        return $pdf->download($filename);
    }

    /** メール送信確認・本文編集ページ */
    public function emailForm(Request $request, DeliveryCustomer $customer)
    {
        $request->validate([
            'note_id' => 'required|exists:delivery_notes,id',
        ]);

        $note = DeliveryNote::findOrFail($request->note_id);
        $defaultBody = $this->buildEmailBody($customer, $note);
        return view('delivery.email_form', compact('customer', 'note', 'defaultBody'));
    }

    /** メール送信実行 */
    public function sendEmail(Request $request, DeliveryCustomer $customer)
    {
        $request->validate([
            'note_id' => 'required|exists:delivery_notes,id',
            'body' => 'required|string',
        ]);

        $note = DeliveryNote::findOrFail($request->note_id);
        $entries = $this->aggregateEntries($customer, $note->period_start->toDateString(), $note->period_end->toDateString());
        $taxGroups = $this->calcTax($entries);

        $status = 'success';
        try {
            Mail::to($customer->email)->send(
                new DeliveryNoteMail($customer, $note, $entries, $taxGroups, $request->body)
            );
        } catch (\Exception $e) {
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
        return redirect()->route('delivery.email_logs')->with('message', $message);
    }

    /** 送信履歴 */
    public function emailLogs()
    {
        $logs = DeliveryEmailLog::with(['customer', 'note'])->orderByDesc('sent_at')->paginate(20);
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

    /** 期間内エントリを部署・商品でグループ集計 */
    public function aggregateEntries(DeliveryCustomer $customer, string $start, string $end): array
    {
        $departments = $customer->departments()->with('products')->get();
        $result = [];

        foreach ($departments as $dept) {
            $deptData = ['name' => $dept->name, 'products' => [], 'subtotal' => 0];

            foreach ($dept->products as $product) {
                $qty = DeliveryDailyEntry::where('delivery_product_id', $product->id)
                    ->whereBetween('date', [$start, $end])
                    ->sum('quantity');

                if ($qty > 0) {
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
            }

            if (!empty($deptData['products'])) {
                $result[] = $deptData;
            }
        }

        return $result;
    }

    /** 消費税計算（税率グループごと・切り捨て） */
    private function calcTax(array $entries): array
    {
        $groups = [];
        foreach ($entries as $dept) {
            foreach ($dept['products'] as $product) {
                $rate = $product['tax_rate'];
                $groups[$rate] = ($groups[$rate] ?? 0) + $product['amount'];
            }
        }

        $result = [];
        foreach ($groups as $rate => $subtotal) {
            $tax = (int) floor($subtotal * $rate);
            $result[] = [
                'rate' => $rate,
                'subtotal_excl' => $subtotal,
                'tax' => $tax,
                'subtotal_incl' => $subtotal + $tax,
            ];
        }

        return $result;
    }

    private function buildEmailBody(DeliveryCustomer $customer, DeliveryNote $note): string
    {
        $start = $note->period_start->format('n月j日');
        $end = $note->period_end->format('n月j日');
        return "{$customer->name} 御中\n\nいつもお世話になっております。\nあさひ屋クリーニングです。\n\n{$start}〜{$end}分の納品書を添付いたします。\nご確認のほどよろしくお願いいたします。\n\nあさひ屋クリーニング";
    }
}
