<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

/** 旧 IpaddressController の置き換え。プリンタの IP は settings の printer_ip に持つ。 */
class SettingsController extends Controller
{
    public function editPrinter()
    {
        $ip = array_pad(explode('.', (string) Setting::get('printer_ip', '')), 4, '');

        return view('receipt.edit')->with(['ip' => $ip]);
    }

    public function updatePrinter(Request $request)
    {
        // 旧の画面は 4 つの入力 (ip[0]〜ip[3]) で送る。1 つの文字列にして IPv4 として検証する
        if (! $request->has('printer_ip') && is_array($request->input('ip'))) {
            $request->merge(['printer_ip' => implode('.', array_map('strval', $request->input('ip')))]);
        }
        $validated = $request->validate([
            'printer_ip' => ['required', 'string', 'ipv4'],
        ]);

        Setting::set('printer_ip', $validated['printer_ip']);

        return redirect(pos_url('home'));
    }
}
