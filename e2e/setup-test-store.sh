#!/usr/bin/env bash
# ローカル/検証用コピーのDBにだけE2E用ログインを作る（本番では絶対に実行しない）
set -euo pipefail
cd "$(dirname "$0")/.."
COMPOSE="docker compose -f docker-compose.vps.yml --env-file .env.vps"
$COMPOSE exec -T app php artisan tinker --execute='
$s = \App\Models\Store::firstOrNew(["email" => "test@gmail.com"]);
$s->name = "E2E"; $s->password = \Illuminate\Support\Facades\Hash::make("Test123456"); $s->save();
echo "store ok: ".$s->id.PHP_EOL;'
