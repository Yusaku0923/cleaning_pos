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
