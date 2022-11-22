<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Invoice;
class InvoiceController extends Controller
{
    public function index() {
        if (!session()->exists('manager_id')) {
           return redirect()->route('home');
        }
        $model = new Invoice();
        $invoices = $model->fetchInvoices(session()->get('manager_id'));
        return view('invoice.index')->with([
            'invoices' => $invoices
        ]);
    }

    public function generate(Request $request) {
        if (isset($request->ids) && session()->exists('manager_name')) {
            // $ids = explode(',', $request->ids);
            $model = new Invoice();
            $invoices = $model->fetchPrintInvoices($request->ids);
        } else {
            return redirect()->route('invoice.index');
        }
        // dd($invoices);
        $pdf = \PDF::loadView('invoice.pdf', [
            'invoices' => $invoices
        ]);
        $pdf->setPaper('A4');
        return $pdf->stream('日報');

    }
}
