<?php
namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Store;

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

    public static function currentInvoicePeriod($cutoff_date) {
        if ($cutoff_date === 99) {
            $period_start = date('Y-m-01');
            $period_end = date('Y-m-t');
        } else {
            $today = date('d');
            if ($cutoff_date < $today) {
                $period_start = date('Y-m-'. ($cutoff_date + 1));
                $period_end = date('Y-m-' . ($cutoff_date), strtotime('+1 month'));
            } else {
                $period_start = date('Y-m-'. ($cutoff_date + 1), strtotime('-1 month'));
                $period_end = date('Y-m-' . ($cutoff_date));
            }
        }

        return [$period_start, $period_end];
    }

    public static function fetchApiToken() {
        $store = Store::find(Auth::id());
        $token = $store->createToken(Str::random(10));

        return $token->plainTextToken;
    }
}