<?php

namespace App\Http\Controllers;

use App\Domain\Order\OrderQueries;
use App\Models\Manager;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/** 旧 DailyReportController (日報と日報 PDF)。担当者は URL (?m=) から。 */
class DailyReportController extends Controller
{
    /** GET /daily_report?date=Y-m-d (省略時は今日) */
    public function index(Request $request, OrderQueries $orders)
    {
        /** @var Manager|null $manager */
        $manager = $request->attributes->get('manager');
        if ($manager === null) {
            return redirect(pos_url('home'));
        }
        $date = ! empty($request->query('date')) ? $this->validDate($request->query('date')) : now()->toDateString();
        [$dailyOrders, $dailySum, $monthlySum] = $orders->fetchDailyOrders($manager->id, $date);

        return view('daily_report.index')->with([
            'title' => '日　報',
            'date' => $date,
            'today' => now()->toDateString(),
            'daily_orders' => $dailyOrders,
            'daily_sum' => $dailySum,
            'monthly_sum' => $monthlySum,
        ]);
    }

    /** GET /daily-report/{date}/pdf (旧 daily_report/generate/{date}) */
    public function pdf(Request $request, string $date, OrderQueries $orders)
    {
        /** @var Manager|null $manager */
        $manager = $request->attributes->get('manager');
        if ($manager === null) {
            return redirect(pos_url('home'));
        }
        $date = $this->validDate($date);
        [$list, $dailySum, $monthlySum] = $orders->fetchDailyOrders($manager->id, $date);
        $dailyOrders = [];
        foreach ($list as $order) {
            foreach ($order['items'] as $item) {
                $dailyOrders[] = [
                    'order_id' => $order['id'],
                    'clothes_id' => $item['clothes_id'],
                    'tag' => $item['tag'],
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'customer_id' => $order['customer_id'],
                    'customer_name' => $order['name'],
                ];
            }
        }

        $pdf = Pdf::loadView('daily_report.pdf', [
            'date' => $date,
            'daily_orders' => $dailyOrders,
            'daily_sum' => $dailySum,
            'monthly_sum' => $monthlySum,
        ]);
        $pdf->setPaper('A4');

        return $this->pdfInline($pdf, '日報', 'daily_report');
    }

    /** 実在する Y-m-d だけ (それ以外は 404。旧は 1970 年の日報になっていた) */
    private function validDate(mixed $date): string
    {
        abort_unless(is_string($date) && preg_match('/\A(\d{4})-(\d{2})-(\d{2})\z/', $date, $m) === 1
            && checkdate((int) $m[2], (int) $m[3], (int) $m[1]), 404);

        return $date;
    }
}
