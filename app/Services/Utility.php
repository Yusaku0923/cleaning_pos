<?php
namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Store;
use Laravel\Sanctum\PersonalAccessToken;
use App\Events\CustomerDisplay;

class Utility
{
    const MONTH_END_CUTOFF = 99;

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
        if ((int)$cutoff_date === self::MONTH_END_CUTOFF) {
            $period_start = date('Y-m-01', strtotime($handed_at));
            $period_end = date('Y-m-t', strtotime($handed_at));
        } else {
            $today = date('d', strtotime($handed_at));
            $handed_at = date('Y-m-01', strtotime($handed_at));
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

    /**
     * 画面から /api を叩くための Sanctum トークン。
     * 以前は表示のたびに発行しており personal_access_tokens が際限なく増えていたため、
     * セッション内で再利用し、失効(日次の全削除など)していた場合のみ再発行する。
     */
    public static function fetchApiToken() {
        $plain = session('api_token');
        if ($plain) {
            $existing = PersonalAccessToken::findToken($plain);
            if ($existing && (int)$existing->tokenable_id === (int)Auth::id()) {
                return $plain;
            }
        }

        $store = Store::find(Auth::id());
        $plain = $store->createToken(Str::random(10))->plainTextToken;
        session(['api_token' => $plain]);

        return $plain;
    }

    public static function sendWebSocket($body) {
        broadcast(new CustomerDisplay($body));

        return;
    }
}