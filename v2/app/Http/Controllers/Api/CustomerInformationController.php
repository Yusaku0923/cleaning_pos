<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerInformation;
use Illuminate\Http\Request;

/** 旧 Api\CustomerInformationController (CS 情報の追加・削除)。セッションには書かない。 */
class CustomerInformationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => ['required', 'integer', $this->ownCustomer()],
            'information' => ['required', 'string', 'max:255'],
        ]);

        $model = new CustomerInformation;
        $model->customer_id = (int) $request->customer_id;
        $model->information = $request->information;
        $model->save();

        return response()->json([
            'info' => $this->fetch((int) $request->customer_id),
        ]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer'],
            'customer_id' => ['required', 'integer', $this->ownCustomer()],
        ]);

        CustomerInformation::where('customer_id', (int) $request->customer_id)
            ->findOrFail((int) $request->id)
            ->delete();

        return response()->json([
            'info' => $this->fetch((int) $request->customer_id),
        ]);
    }

    /** 自店舗の顧客だけ (Customer のスコープで解決できること)。 */
    private function ownCustomer(): \Closure
    {
        return function ($attr, $value, $fail) {
            if (Customer::find($value) === null) {
                $fail('顧客が見つかりません。');
            }
        };
    }

    private function fetch(int $customerId): array
    {
        return CustomerInformation::where('customer_id', $customerId)
            ->orderBy('created_at', 'asc')
            ->get()->toArray();
    }
}
