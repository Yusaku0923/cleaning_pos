<?php

namespace App\Http\Controllers;

use App\Models\Manager;
use Illuminate\Http\Request;

/** 旧 ManagerController。セッションには書かず、担当者を URL に付けたホームへリダイレクトするだけ。 */
class ManagerController extends Controller
{
    public function update(Request $request, $manager_id)
    {
        $manager = Manager::findOrFail($manager_id);

        // 旧: 担当者を変えても顧客セッションは残り、ホームで担当者が一致しなければ外れた。
        $customer = $request->attributes->get('customer');
        $params = ['m' => $manager->id];
        if ($customer !== null && (int) $customer->manager_id === (int) $manager->id) {
            $params['c'] = $customer->id;
        }

        return redirect()->route('home', $params);
    }
}
