<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\Category;
use App\Models\Clothes;
use App\Models\Tax;
use App\Models\Store;
use App\Models\Customer;

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
        $category_clothes = Category::with('clothes')->get();
        $model = new Clothes;
        if (Order::where('customer_id', $customer->id)->exists()) {
            $often_ordered = $model->fetchOftenOrdered($customer->id);
        } else {
            $often_ordered = [];
        }
        $tax = Tax::where('store_id', Auth::id())->value('tax');
        $store = Store::find(Auth::id());
        $token = $store->createToken(Str::random(10));

        return view('orders.create')->with([
            'title' => '預　り　入　力',
            'manager_id' => session()->get('manager_id'),
            'customer_id' => session()->get('customer_id'),
            'customer_name' => $customer->name,
            'is_invoice' => $customer->is_invoice,
            'auth_token' => $token->plainTextToken,
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
    public function destroy($order_id)
    {
        Order::find($order_id)->delete();

        return redirect()->route('home');
    }
}
