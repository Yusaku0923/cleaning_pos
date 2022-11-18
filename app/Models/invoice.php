<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Services\Utility;

class Invoice extends Model
{
    use HasFactory;

    protected $guarded = [];

    private function searchItems() {
    }

    public function fetchInvoices($manager_id, $request = null) {
        $query = Invoice::query();
        $query->select('invoices.*', 'customers.name', 'customers.name_kana', 'customers.cutoff_date');
        $query->join('customers', 'invoices.customer_id', '=', 'customers.id');
        $query->where('invoices.manager_id', $manager_id);
        if (!is_null($request)) {
            if ($request->cutoff_date !== 0) $query->where('customers.cutoff_date', $request->cutoff_date);
            if ($request->target_month !== '') {
                $query->where('invoices.period_end', '>=', date('Y-m-01', strtotime($request->target_month)));
                $query->where('invoices.period_end', '<=', date('Y-m-t', strtotime($request->target_month)));
            }
            if ($request->customer_name !== '') $query->where('customers.name', 'like', '%'.$request->customer_name.'%');
        } else {
            $query->where('invoices.period_end', '>=', date('Y-m-01'));
            $query->where('invoices.period_end', '<=', date('Y-m-t'));
        }
        $query->orderBy('invoices.period_end', 'asc');
        $invoices = $query->get()->toArray();

        return $invoices;
    }

    public function existsTargetInvoice($customer_id) {
        $cutoff_date = Customer::find($customer_id)->value('cutoff_date');
        list($period_start, $period_end) = Utility::currentInvoicePeriod($cutoff_date);
        $invoice = Invoice::where('customer_id', $customer_id)
                            ->where('period_start', $period_start)
                            ->where('period_end', $period_end)
                            ->first();
        return !empty($invoice) ? $invoice->id : null;
    }
}
