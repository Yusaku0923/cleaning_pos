<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Invoice;
use App\Models\Store;
use App\Models\Customer;
class InvoiceController extends Controller
{
    public function index() {
        if (!session()->exists('manager_id')) {
           return redirect()->route('home');
        }
        $model = new Invoice();
        $invoices = $model->fetchInvoices(session()->get('manager_id'));
        $store = Store::find(Auth::id());
        $token = $store->createToken(Str::random(10));
        return view('invoice.index')->with([
            'title' => '請　求　書　発　行',
            'invoices' => $invoices,
            'token' => $token->plainTextToken,
        ]);
    }

    public function payment_confimation() {
        if (!session()->exists('customer_id')) {
            return redirect()->route('home');
        }
        $model = new Invoice();
        $invoices = $model->fetchInvoicesNeedsPaymentConfimation(session()->get('customer_id'));

        return view('invoice.payment_confimation')->with([
            'title' => '入　金　確　認',
            'customer' =>  Customer::find(session()->get('customer_id')),
            'invoices' => $invoices,
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
