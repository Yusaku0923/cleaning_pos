<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Laravel\Ui\Presets\React;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function search()
    {
        return view('customers.search');
    }

    public function select($id) {
        // TODO: アクセス権限確認
        session()->put('customer_id', $id);
        return redirect()->route('home');
    }

    public function clear() {
        // TODO: アクセス権限確認
        session()->forget('customer_id');
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
    public function store(Request $request)
    {
        $customer = Customer::create([
            'manager_id' => $request->session()->get('manager_id'),
            'name' => $request->name,
            'name_kana' => $request->name_kana,
            'birth_day' => $request->birth_day ?? NULL,
            'sex' => $request->sex ?? NULL,
            'cutoff_date' => $request->cutoff_date ?? NULL,
        ]);
        // $customer->save();

        // TODO:遷移先選択
        return redirect()->route('home')->with([
            'customer_id' => $customer->id
        ]);
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
    public function destroy($id)
    {
        //
    }
}
