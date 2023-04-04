<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ipaddress;

class IpaddressController extends Controller
{
    public function edit() {
        $ip = Ipaddress::find(1);
        $ip = explode('.', $ip->ipaddress);

        return view('receipt.edit')->with([
            'ip' => $ip
        ]);
    }

    public function update(Request $request) {
        $ip = Ipaddress::find(1);
        $ip->ipaddress = implode('.', $request->ip);
        $ip->save();

        return redirect()->route('home');
    }
}
