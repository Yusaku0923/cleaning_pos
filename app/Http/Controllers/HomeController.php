<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Manager;
use App\Models\Customer;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $managers = Manager::where('store_id', Auth::id())->get();
        if (session()->exists('customer_id')) {
            $customer = Customer::find(session()->get('customer_id'));

            // 担当者が設定されいていない、もしくは顧客-担当者が一致しない場合は顧客セッション削除
            if (!session()->exists('manager_id') || session()->get('manager_id') !== $customer->manager_id) {
                session()->forget('customer_id');
                $customer = [];
            }
        }

        return view('home')->with([
            'title' => '顧　客　呼　出',
            'managers' => $managers,
            'customer' => $customer ?? [],
        ]);
    }

    public function menu()
    {
        return view('menu')->with([
            'title' => 'マスタ編集メニュー',
        ]);
    }
}
