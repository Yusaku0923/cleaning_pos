# エラーログ DB 集積システム 設計書

**日付**: 2026-03-19
**対象プロジェクト**: cleaning_pos (Laravel 8 + Vue.js 2)

## 概要

サーバーサイドで発生した重大な例外を既存の `client_errors` テーブルに保存し、クライアントエラーとともに一元管理する。30日後に自動削除する仕組みも合わせて実装する。

## 前提・現状

クライアントサイドのエラー収集は既に完成している：
- `resources/js/utils/ErrorLogger.js` — グローバルエラーハンドラー、console.error/warn ラップ
- `app/Http/Controllers/Api/ClientErrorController.php` — 保存・一覧 API
- `app/Models/ClientError.php` — Eloquent モデル
- `database/migrations/2026_02_27_142118_create_client_errors_table.php` — テーブル定義
- `routes/api.php` — `POST /api/client-error`、`GET /api/client-errors`（認証済みのみ）

## 要件

1. サーバーサイド例外（500系）を `client_errors` テーブルに保存する
2. バリデーション・認証・CSRF 例外はスキップする（ノイズ排除）
3. 保存済みレコードの 30日後自動削除（書き込み時に間引き実行）
4. エラー確認は既存の `GET /api/client-errors` を使用（新規 UI なし）
5. DB 保存処理自体の例外でアプリが落ちないようにする

## アーキテクチャ

### 変更ファイル

| ファイル | 変更種別 | 内容 |
|---|---|---|
| `app/Exceptions/Handler.php` | 修正 | `reportable()` にサーバー例外保存を実装 |
| `app/Models/ClientError.php` | 修正 | `log()` 静的メソッドを追加（30日削除込み） |

### データフロー

```
[サーバー例外発生]
    ↓
Handler::register() → reportable()
    ↓
スキップ判定（ValidationException等は除外）
    ↓
ClientError::log($data)
    ├── 10% 確率で 30日超レコード削除
    └── ClientError::create($data)
```

## 詳細設計

### 1. ClientError モデル（`app/Models/ClientError.php`）

既存の `$fillable`、`$casts` はそのままに、静的メソッド `log()` を追加する。

```php
public static function log(array $data): void
{
    try {
        // 10% の確率で 30日超レコードを削除（毎回実行を避けパフォーマンスを保つ）
        if (random_int(1, 10) === 1) {
            static::where('created_at', '<', now()->subDays(30))->delete();
        }
        static::create($data);
    } catch (\Throwable $e) {
        // DB 保存失敗はサイレントに無視（ファイルログには記録）
        \Illuminate\Support\Facades\Log::error('ClientError::log failed: ' . $e->getMessage());
    }
}
```

### 2. Handler.php（`app/Exceptions/Handler.php`）

`register()` 内の `reportable()` に実装する。

**スキップ対象例外**：
- `Illuminate\Validation\ValidationException`
- `Illuminate\Auth\AuthenticationException`
- `Illuminate\Auth\Access\AuthorizationException`
- `Illuminate\Session\TokenMismatchException`
- `Symfony\Component\HttpKernel\Exception\HttpException`（`abort(404)` / `abort(403)` 等を除外）

**保存フィールド**：

| フィールド | 値 |
|---|---|
| `error_type` | `"server_error"` |
| `message` | `$e->getMessage()`（空の場合はクラス名） |
| `stack` | `$e->getTraceAsString()` を先頭 3000文字に切り詰め |
| `url` | HTTP リクエスト内なら `request()->url()`、CLI/Queue 内なら `null` |
| `user_agent` | HTTP リクエスト内なら `request()->userAgent()`、CLI/Queue 内なら `null` |
| `manager_id` | `request()->user()?->id`（CLI 時は `null`） |

**実装方針**：
- `reportable()` コールバック全体を try/catch で囲む
- `app()->runningInConsole()` で CLI/Queue コンテキストを判定し、`url` / `user_agent` に合成値が入らないようにする

### 3. error_type の値体系

既存テーブルの `error_type` カラム（`string(20)`）の値：

| 値 | 発生元 | 説明 |
|---|---|---|
| `error` | クライアント | `window.onerror` / `console.error` |
| `warn` | クライアント | `console.warn` |
| `unhandled` | クライアント | 未処理 Promise rejection |
| `server_error` | サーバー | Laravel 例外ハンドラー経由 |

### 4. 30日削除の仕組み

- `ClientError::log()` の冒頭で `random_int(1, 10) === 1`（10% 確率）のときのみ実行
- エラー頻度が低い運用でも確実に削除されるよう、`log()` 内に組み込む
- スケジューラー・cron は不要

## 制約・考慮事項

- DB 保存失敗時はサイレント失敗（ファイルログ `storage/logs/laravel.log` に記録）
- スタックトレースは 3000文字に切り詰め（テキストカラムのサイズ対策）
- `manager_id` は認証不要の API でも `nullable` のため問題なし
- Migration の変更は不要（既存テーブルで対応可能）
- **スタックトレースのセキュリティ**: `stack` フィールドにはファイルパスやフレームワーク内部情報が含まれる。`GET /api/client-errors` は `auth:sanctum` で保護済みのため、閲覧できるのは認証済みマネージャーのみ。本システムはクリーニング店内スタッフが対象であり、この範囲での閲覧は許容する。
- **アイドル期間中の削除未実行**: 店が長期休業でエラーがゼロ件の場合、30日削除は実行されない。再稼働後の最初のエラー時にまとめて削除される（小規模テーブルのため許容）。

## 確認方法

実装後のエラー確認：

```bash
# エラー一覧（認証済みセッション必要）
GET /api/client-errors

# サーバーエラーのみ絞り込み
GET /api/client-errors?error_type=server_error

# 日付絞り込み
GET /api/client-errors?date=2026-03-19
```
