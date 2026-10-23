<?php

namespace App\Console\Commands;

use App\Models\DeviceToken;
use Illuminate\Console\Command;

class DeviceRevoke extends Command
{
    protected $signature = 'device:revoke {id : device_tokens.id}';

    protected $description = '端末の自動ログイン用トークンを失効させる';

    public function handle(): int
    {
        $device = DeviceToken::query()->find($this->argument('id'));
        if ($device === null) {
            $this->error('トークンが見つかりません。');

            return self::FAILURE;
        }

        if ($device->revoked_at === null) {
            $device->forceFill(['revoked_at' => now()])->save();
        }
        $this->info("失効しました: id={$device->id} name={$device->name}");

        return self::SUCCESS;
    }
}
