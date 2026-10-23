# 旧 → v2 で許容する差分

旧(Laravel 8 / Vue 2)と v2(Laravel 12 / Vue 3)の E2E・画面比較で、**差が出ても不合格にしない項目**。
「仮」は現時点の想定で、v2 実装・業務確認のうえで確定または撤回する。それ以外の差は不具合として扱う。

## 許容する差分(仮)

| 項目 | 旧の期待値 | v2 の期待値(仮) | 備考 |
|---|---|---|---|
| 税の端数 | 内税10%を `floor`(切り捨て)で計算(例: 500円 → 内消費税 45円) | 端数処理の方式を旧と同じにする。同じにできない場合のみ、差を1円以内として許容 | 画面比較の「注文作成」「会計モーダル」で確認。確定は税計算の実装時 |
| `paid_at`(入金日時) | 入金操作時に記録。請求・未収の入金で扱いが画面ごとに異なる場合がある | 入金したすべての経路で `paid_at` を記録する(旧で空になる経路は v2 で値が入ってよい) | 値そのものは日時なので比較しない(存在の有無のみ) |
| タグ番号の折り返し | `9-999` の次は `0-001`(`0-000` は使わない) | 同じ。ただし折り返し境界で旧に不具合があれば v2 では正しい順序にする | 境界は旧の挙動を確認してから確定 |

## 比較対象外

- **URL の変更**: v2 でパスが変わるのは許容する(例: 担当者・顧客選択が `/manager/update/{id}` や `/customer/select/{id}` ではなく URL 方式になる)。E2E は `tests/helpers.js` の `E2E_TARGET` 分岐で吸収し、画面操作(クリックする要素)は同じにする。
- **HTTP メソッドの変更(GET → POST など)**: 状態を変える操作を GET から POST に直すのは許容する。画面上の操作結果だけを比べる。
- API の内部 URL・レスポンスの形(画面から見えない部分)。
- 実行ごとに変わる値: 現在時刻、日付入力、伝票No.、来店日・来店回数・金額の累計(画面比較では mask で隠す)。
- 取引先名・カテゴリなど本番マスタ由来の文字列(個人情報を含みうるため mask で隠す)。

## 運用

- 許容差分を増やすときは、この表に「旧の期待値・v2 の期待値・理由」を書いてから行う。
- 基準画像は旧の Docker 環境で作る(`docker-compose.legacy.yml`、手順は本ファイル末尾)。

## 旧の基準環境

```bash
# 初回のみ: 本番ダンプを tmp/legacy.sql.gz に置く(git 管理外。個人情報を含むのでコミット禁止)
docker compose -f docker-compose.legacy.yml up -d --build     # http://localhost:8081 (DB は 33061)
bash e2e/setup-test-store.sh                                   # E2E 用データ(test@gmail.com / Test123456)
cd e2e && E2E_BASE_URL=http://localhost:8081 E2E_TARGET=legacy npx playwright test
# 基準画像の作り直し
E2E_BASE_URL=http://localhost:8081 E2E_TARGET=legacy npx playwright test tests/parity --update-snapshots --project=chromium
docker compose -f docker-compose.legacy.yml down               # 停止(DB も消すなら -v)
```
