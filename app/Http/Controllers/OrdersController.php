<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Services\Utility;
use App\Models\Order;
use App\Models\Category;
use App\Models\Clothes;
use App\Models\Tax;
use App\Models\Store;
use App\Models\Customer;
use App\Models\TagNumber;

class OrdersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!session()->has('manager_id') || !session()->has('customer_id')) {
            return redirect()->route('home');
        }
        $customer = Customer::find(session()->get('customer_id'));
        if (!(boolean)$customer->is_invoice) {
            Utility::sendWebSocket(
                [
                    'event' => 'order',
                    'name' => $customer->name
                ]
            );
        }

        $category_clothes = Category::with('clothes')->where('id', '!=',  1)->get();
        $model = new Clothes;
        if (Order::where('customer_id', $customer->id)->exists()) {
            $often_ordered = $model->fetchOftenOrdered($customer->id);
        } else {
            $often_ordered = [];
        }
        $tax = Tax::where('store_id', Auth::id())->value('tax');
        $latest_tag = TagNumber::where('manager_id', session()->get('manager_id'))->value('tag_number');

        return view('orders.create')->with([
            'title' => '預　り　入　力',
            'manager_id' => session()->get('manager_id'),
            'customer_id' => session()->get('customer_id'),
            'customer_name' => $customer->name,
            'is_invoice' => $customer->is_invoice,
            'check_return' => $customer->needs_return_confimation,
            'latest_tag' => $latest_tag,
            'list' => $category_clothes,
            'often_ordered' => $often_ordered,
            'tax' => (1 + $tax / 100),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        if (!session()->has('manager_id') || !session()->has('customer_id')) {
            return redirect()->route('home');
        }

        $model = new Order();
        $orders = $model->fetchOrders(session()->get('customer_id'), 20);
        $customer = Customer::find(session()->get('customer_id'));

        return view('orders.show')->with([
            'title' => '預　り　一　覧',
            'customer' => $customer,
            'orders' => $orders,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        Order::find($request->order_id)->delete();

        return redirect()->route('home');
    }
}
