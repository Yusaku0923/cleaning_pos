<?php

namespace App\Models;

/**
 * 顧客表示の端末としての店舗 (display ガード)。行は stores と同じ。
 * POS の Store と違い remember_token を使い、放置でセッションが切れても remember cookie で入り直す。
 * (Store は remember_token を使わないので、POS のログアウトでこのトークンが作り直されることはない)
 */
class DisplayStore extends Store
{
    protected $table = 'stores';

    public function getRememberTokenName()
    {
        return 'remember_token';
    }
}
