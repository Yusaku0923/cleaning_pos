#!/usr/bin/env bash
# ローカル/検証用コピーのDBにだけE2E用データを作る（本番では絶対に実行しない）。冪等。
#   ログイン: test@gmail.com / Test123456
#   担当者「E2E担当」・顧客「E2E顧客」(通常)・「E2E請求顧客」(請求)・カテゴリ「E2Eカテゴリ」・衣類「E2E衣類」
# 実行のたびに E2E 顧客の注文・請求を消して初期状態に戻す(E2E 顧客以外は触らない)
# 別プロジェクトを対象にするときは COMPOSE_PROJECT を指定: COMPOSE_PROJECT=cleaning_pos_ux ./setup-test-store.sh
set -euo pipefail
cd "$(dirname "$0")/.."
COMPOSE="docker compose ${COMPOSE_PROJECT:+-p $COMPOSE_PROJECT} -f docker-compose.legacy.yml"
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
// 毎回同じ状態から始めるため、E2E 顧客の注文・請求を消し、集計とタグ採番を初期値に戻す(E2E 顧客のみ)
$ids = Customer::where("manager_id", $m->id)->pluck("id");
// 注文・請求は SoftDeletes なので、論理削除分も含めて物理削除する(クエリビルダ。Invoice の削除フックは旧のバグで落ちる)
$oids = \DB::table("orders")->whereIn("customer_id", $ids)->pluck("id");
\DB::table("order_clothes")->whereIn("order_id", $oids)->delete();
\DB::table("orders")->whereIn("customer_id", $ids)->delete();
\DB::table("invoices")->whereIn("customer_id", $ids)->delete();
Customer::whereIn("id", $ids)->update(["total_sales" => 0, "number_of_visits" => 0]);
TagNumber::where("manager_id", $m->id)->update(["tag_number" => 1000]);
echo "e2e data ok: store {$s->id} manager {$m->id}".PHP_EOL;'
