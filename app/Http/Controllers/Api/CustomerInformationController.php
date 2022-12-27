<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CustomerInformation;
use Illuminate\Support\Facades\Log;

class CustomerInformationController extends Controller
{
    public function store(Request $request, CustomerInformation $model) {
        $model->customer_id = session()->get('customer_id');
        $model->information = $request->information;
        $model->save();

        $info = $this->setSession();

        return response()->json([
            'info' => $info
        ]);
    }

    public function delete(Request $request) {
        CustomerInformation::find($request->id)->delete();

        $info = $this->setSession();

        return response()->json([
            'info' => $info
        ]);
    }

    private function setSession() {
        $query = CustomerInformation::query();
        $query->where('customer_id', session()->get('customer_id'));
        $query->orderBy('created_at', 'asc');
        $info = $query->get()->toArray();
        session()->put('customer_info', $info);

        return $info;
    }
}
