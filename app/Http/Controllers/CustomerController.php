<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Models\CustomerInformation;
use App\Http\Requests\Customers\StoreRequest;
use App\Http\Requests\Customers\UpdateRequest;
use App\Services\Utility;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function search()
    {
        return view('customers.search')->with([
            'title' => '顧　客　検　索',
            'manager_id' => session()->get('manager_id')
        ]);
    }

    public function select($id) {
        session()->put('customer_id', $id);
    
        $query = CustomerInformation::query();
        $query->where('customer_id', $id);
        $query->orderBy('created_at', 'asc');
        $info = $query->get()->toArray();
        session()->put('customer_info', $info);
        Utility::sendWebSocket(
            [
                'event' => 'customer',
                'name' => Customer::where('id', $id)->value('name')
            ]
        );

        return redirect()->route('home');
    }

    public function clear() {
        session()->forget('customer_id');
        session()->forget('customer_info');
        return redirect()->route('home');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('customers.create')->with([
            'title' => '新　規　登　録'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
        $customer = Customer::create([
            'manager_id' => $request->session()->get('manager_id'),
            'name' => $request->name,
            'name_kana' => mb_convert_kana($request->name_kana, 'rnk'),
            'phone_number' => $request->phone_number,
            'birth_day' => $request->birth_day ?? NULL,
            'sex' => $request->sex ?? NULL,
        ]);

        // TODO:遷移先選択
        session()->put('customer_id', $customer->id);
        session()->put('customer_info', []);
        Utility::sendWebSocket(
            [
                'event' => 'customer',
                'name' => Customer::where('id', $customer->id)->value('name')
            ]
        );
        return redirect()->route('home');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
    public function update(UpdateRequest $request, $id)
    {
        $customer = Customer::find($id);
        $customer->name = $request->name;
        $customer->name_kana = mb_convert_kana($request->name_kana, 'rnk');
        $customer->phone_number = $request->phone_number;
        $customer->is_invoice = (boolean)$request->is_invoice;
        $customer->needs_payment_confimation = (boolean)$request->check_payment;
        $customer->cutoff_date = $request->cutoff_date;
        $customer->save();

        return redirect()->route('home');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
