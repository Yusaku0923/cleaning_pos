<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;

class DailyReportController extends Controller
{
    public function index(Request $request) {
        $date = !empty($request->query('date')) ? $request->date : date('Y-m-d');
        // /?date=2022-03-01
        $model = new Order();
        list($daily_orders, $daily_sum, $monthly_sum) = $model->fetchDailyOrders($date);

        // dd($daily_orders);

        return view('daily_report.index')->with([
            'title' => '日　報',
            'date' => $date,
            'daily_orders' => $daily_orders,
            'daily_sum' => $daily_sum,
            'monthly_sum' => $monthly_sum,
        ]);
    }

    public function generate($date) {
        $model = new Order();
        list($daily_orders, $daily_sum, $monthly_sum) = $model->fetchDailyOrders($date);

        $pdf = \PDF::loadView('daily_report.pdf', [
            'date' => $date,
            'daily_orders' => $daily_orders,
            'daily_sum' => $daily_sum,
            'monthly_sum' => $monthly_sum,
        ]);
        $pdf->setPaper('A4');
        return $pdf->stream('日報');
    }
}
