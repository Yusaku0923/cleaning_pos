<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeletePersonalAccessToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:DeletePersonalAccessToken';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '7日以上前に発行された API トークンを削除する';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // 全削除(truncate)だと、開きっぱなしの画面のトークンが深夜に突然無効になるため、
        // 古いものだけ消す。トークンはセッション内で再利用される(Utility::fetchApiToken)。
        $deleted = DB::table('personal_access_tokens')
            ->where('created_at', '<', now()->subDays(7))
            ->delete();
        $this->info("deleted {$deleted} tokens");
    }
}
