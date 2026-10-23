<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\CustomerDisplay;
use Illuminate\Console\Command;

class DisplayIssueToken extends Command
{
    protected $signature = 'display:issue-token {--store= : 店舗 ID (省略時は最初の店舗)}';

    protected $description = '顧客表示の端末用ログイン URL を発行する (前の URL は使えなくなる。平文はこの1回だけ表示)';

    public function handle(): int
    {
        $storeId = $this->option('store');
        $store = $storeId !== null
            ? Store::query()->find($storeId)
            : Store::query()->orderBy('id')->first();

        if ($store === null) {
            $this->error('店舗が見つかりません。');

            return self::FAILURE;
        }

        $plain = CustomerDisplay::issueToken($store);

        $this->info("発行しました: store={$store->id} {$store->name}");
        $this->line('顧客表示のログイン URL (この表示は1回だけ。控えてください):');
        $this->line(rtrim((string) config('app.url'), '/').'/display/login/'.$plain);

        return self::SUCCESS;
    }
}
