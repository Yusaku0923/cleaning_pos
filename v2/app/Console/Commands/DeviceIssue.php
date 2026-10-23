<?php

namespace App\Console\Commands;

use App\Models\DeviceToken;
use App\Models\Store;
use Illuminate\Console\Command;

class DeviceIssue extends Command
{
    protected $signature = 'device:issue {name : 端末名 (例: レジ iPad)} {--store= : 店舗 ID (省略時は最初の店舗)}';

    protected $description = '端末の自動ログイン用トークンを発行する (平文はこの1回だけ表示)';

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

        [$device, $plain] = DeviceToken::issue($store, (string) $this->argument('name'));

        $this->info("発行しました: id={$device->id} name={$device->name} store={$store->name}");
        $this->line('自動ログイン URL (この表示は1回だけ。控えてください):');
        $this->line(rtrim((string) config('app.url'), '/').'/login/device/'.$plain);

        return self::SUCCESS;
    }
}
