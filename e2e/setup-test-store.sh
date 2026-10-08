#!/usr/bin/env bash
# ローカル/検証用コピーのDBにだけE2E用データを作る（本番では絶対に実行しない）。冪等。
#   ログイン: test@gmail.com / Test123456
#   担当者「E2E担当」・顧客「E2E顧客」(通常)・「E2E請求顧客」(請求)・カテゴリ「E2Eカテゴリ」・衣類「E2E衣類」
# 別プロジェクトを対象にするときは COMPOSE_PROJECT を指定: COMPOSE_PROJECT=cleaning_pos_ux ./setup-test-store.sh
set -euo pipefail
cd "$(dirname "$0")/.."
COMPOSE="docker compose ${COMPOSE_PROJECT:+-p $COMPOSE_PROJECT} -f docker-compose.vps.yml --env-file .env.vps"
$COMPOSE exec -T app php artisan tinker --execute='
use App\Models\{Store,Manager,Customer,Category,Clothes,TagNumber};
\Illuminate\Database\Eloquent\Model::unguard();
$s = Store::firstOrNew(["email" => "test@gmail.com"]);
$s->name = "E2E"; $s->password = \Illuminate\Support\Facades\Hash::make("Test123456"); $s->save();
$m = Manager::firstOrCreate(["store_id" => $s->id, "name" => "E2E担当"]);
TagNumber::firstOrCreate(["manager_id" => $m->id], ["tag_number" => 1000]);
foreach ([["E2E顧客", false], ["E2E請求顧客", true]] as [$n, $inv]) {
  Customer::firstOrCreate(["manager_id" => $m->id, "name" => $n], ["name_kana" => "イーツーイー", "is_invoice" => $inv, "cutoff_date" => 99]);
}
$cat = Category::firstOrCreate(["store_id" => $s->id, "name" => "E2Eカテゴリ"]);
Clothes::updateOrCreate(["store_id" => $s->id, "name" => "E2E衣類"], ["category_id" => $cat->id, "name_kana" => "E2E", "price" => 500, "tag_count" => 1]);
echo "e2e data ok: store {$s->id} manager {$m->id}".PHP_EOL;'
