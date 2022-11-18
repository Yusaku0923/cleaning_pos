<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
}
