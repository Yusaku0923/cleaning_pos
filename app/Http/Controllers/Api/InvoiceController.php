<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;

class InvoiceController extends Controller
{
    public function search(Request $request, Invoice $model) {
        $invoices = $model->fetchInvoices($request->manager_id, $request->conditions);

        return response()->json([
            'invoices' => $invoices,
        ]);
    }
}
