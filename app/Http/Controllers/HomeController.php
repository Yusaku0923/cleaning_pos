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
    public function index(Request $request)
    {
        $managers = Manager::where('store_id', Auth::id())->get();
        if ($request->session()->exists('customer_id')) {
            $customer = Customer::find($request->session()->get('customer_id'));
        }

        return view('home')->with([
            'title' => '顧　客　呼　出',
            'managers' => $managers,
            'customer' => $customer ?? [],
        ]);
    }
}
