<?php
namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Store;
use App\Events\CustomerDisplay;

class Utility
{
    public static function convertTagFormat($tag) {
        $formated_tag = '';
        if ($tag >= 10000) {
            $formated_tag = substr((string)$tag, 0, 2);
        } else {
            $formated_tag = substr((string)$tag, 0, 1);
        }
        if ($tag % 1000 < 10) {
            $formated_tag .= '-00' . (string)$tag % 1000;
        } else if ($tag % 1000 < 100) {
            $formated_tag .= '-0' . (string)$tag % 1000;
        } else if ($tag % 1000 < 1000) {
            $formated_tag .= '-' . (string)$tag % 1000;
        }

        return $formated_tag;
    }

    public static function searchInvoicePeriod($cutoff_date, $handed_at = '') {
        if (empty($handed_at)) {
            $handed_at = date('Y-m-d');
        }
        if ($cutoff_date === 99) {
            $period_start = date('Y-m-01', strtotime($handed_at));
            $period_end = date('Y-m-t', strtotime($handed_at));
        } else {
            $today = date('d', strtotime($handed_at));
            if ($cutoff_date < $today) {
                $period_start = date('Y-m-'. ($cutoff_date + 1), strtotime($handed_at));
                $period_end = date('Y-m-' . ($cutoff_date), strtotime($handed_at . ' +1 month'));
            } else {
                $period_start = date('Y-m-'. ($cutoff_date + 1), strtotime($handed_at . '-1 month'));
                $period_end = date('Y-m-' . ($cutoff_date), strtotime($handed_at));
            }
        }

        return [$period_start, $period_end];
    }

    public static function fetchApiToken() {
        $store = Store::find(Auth::id());
        $token = $store->createToken(Str::random(10));

        return $token->plainTextToken;
    }

    public static function sendWebSocket($body) {
        broadcast(new CustomerDisplay($body));

        return;
    }
}