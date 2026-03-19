# エラーログ DB 集積システム 実装プラン

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** サーバー例外を `client_errors` テーブルに保存し、30日後に自動削除する仕組みを2ファイルの変更で実現する。

**Architecture:** `ClientError` モデルに `log()` / `pruneExpired()` 静的メソッドを追加し、`Handler.php` の `reportable()` からこれを呼ぶ。ノイズになる例外（バリデーション・認証・HTTP 4xx系）はスキップ。

**Tech Stack:** Laravel 8, PHPUnit, Eloquent ORM, MySQL

---

## ファイル構成

| ファイル | 変更 | 内容 |
|---|---|---|
| `app/Models/ClientError.php` | 修正 | `log()` と `pruneExpired()` 静的メソッドを追加 |
| `app/Exceptions/Handler.php` | 修正 | `reportable()` にサーバー例外保存を実装 |
| `tests/Feature/ClientErrorLogTest.php` | 新規作成 | `log()` / `pruneExpired()` の単体テスト |
| `tests/Feature/ExceptionHandlerTest.php` | 新規作成 | Handler の統合テスト |

---

## Task 1: ClientError モデルに `log()` / `pruneExpired()` を追加

**Files:**
- Modify: `app/Models/ClientError.php`
- Create: `tests/Feature/ClientErrorLogTest.php`

- [ ] **Step 1: テストファイルを作成する**

`tests/Feature/ClientErrorLogTest.php` を新規作成：

```php
<?php

namespace Tests\Feature;

use App\Models\ClientError;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientErrorLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_creates_a_record(): void
    {
        ClientError::log([
            'error_type' => 'server_error',
            'message'    => 'Something went wrong',
            'stack'      => null,
            'url'        => 'http://localhost/test',
            'user_agent' => null,
            'manager_id' => null,
        ]);

        $this->assertDatabaseHas('client_errors', [
            'error_type' => 'server_error',
            'message'    => 'Something went wrong',
        ]);
    }

    public function test_prune_expired_deletes_records_older_than_30_days(): void
    {
        // 31日前のレコードを直接作成
        ClientError::create([
            'error_type' => 'server_error',
            'message'    => 'Old error',
            'created_at' => now()->subDays(31),
            'updated_at' => now()->subDays(31),
        ]);

        // 1日前のレコード（残るべき）
        ClientError::create([
            'error_type' => 'server_error',
            'message'    => 'Recent error',
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ]);

        ClientError::pruneExpired();

        $this->assertDatabaseMissing('client_errors', ['message' => 'Old error']);
        $this->assertDatabaseHas('client_errors', ['message' => 'Recent error']);
    }

    public function test_prune_expired_keeps_records_exactly_30_days_old(): void
    {
        ClientError::create([
            'error_type' => 'server_error',
            'message'    => 'Boundary error',
            'created_at' => now()->subDays(30)->addSecond(),
            'updated_at' => now()->subDays(30)->addSecond(),
        ]);

        ClientError::pruneExpired();

        $this->assertDatabaseHas('client_errors', ['message' => 'Boundary error']);
    }
}
```

- [ ] **Step 2: テストを実行して失敗を確認する**

```bash
cd cleaning_pos && php artisan test --filter=ClientErrorLogTest
```

期待: `Error: Call to undefined method App\Models\ClientError::log()`

- [ ] **Step 3: `ClientError` モデルに `log()` と `pruneExpired()` を実装する**

`app/Models/ClientError.php` を以下に書き換える：

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ClientError extends Model
{
    use HasFactory;

    protected $fillable = [
        'error_type',
        'message',
        'stack',
        'url',
        'user_agent',
        'manager_id',
        'extra_data',
    ];

    protected $casts = [
        'extra_data' => 'array',
    ];

    /**
     * エラーを DB に保存する。10% の確率で 30日超レコードを削除する。
     * DB 保存失敗時はサイレントに無視（ファイルログに記録）。
     */
    public static function log(array $data): void
    {
        try {
            if (random_int(1, 10) === 1) {
                static::pruneExpired();
            }
            static::create($data);
        } catch (\Throwable $e) {
            Log::error('ClientError::log failed: ' . $e->getMessage());
        }
    }

    /**
     * 30日より古いレコードを削除する。
     */
    public static function pruneExpired(): void
    {
        static::where('created_at', '<', now()->subDays(30))->delete();
    }
}
```

- [ ] **Step 4: テストを実行してパスを確認する**

```bash
cd cleaning_pos && php artisan test --filter=ClientErrorLogTest
```

期待: 3テスト全て PASS

- [ ] **Step 5: コミットする**

```bash
git add app/Models/ClientError.php tests/Feature/ClientErrorLogTest.php
git commit -m "feat: add ClientError::log() and pruneExpired() with 30-day retention"
```

---

## Task 2: Handler.php にサーバー例外ロギングを実装する

**Files:**
- Modify: `app/Exceptions/Handler.php`
- Create: `tests/Feature/ExceptionHandlerTest.php`

- [ ] **Step 1: テストファイルを作成する**

`tests/Feature/ExceptionHandlerTest.php` を新規作成：

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ExceptionHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_runtime_exception_is_saved_to_database(): void
    {
        Route::get('/_test/exception', function () {
            throw new \RuntimeException('Test server error');
        });

        $this->get('/_test/exception');

        $this->assertDatabaseHas('client_errors', [
            'error_type' => 'server_error',
            'message'    => 'Test server error',
        ]);
    }

    public function test_validation_exception_is_not_logged(): void
    {
        Route::post('/_test/validation', function (\Illuminate\Http\Request $request) {
            $request->validate(['name' => 'required']);
        });

        $this->postJson('/_test/validation', []);

        $this->assertDatabaseCount('client_errors', 0);
    }

    public function test_http_404_is_not_logged(): void
    {
        Route::get('/_test/abort', function () {
            abort(404);
        });

        $this->get('/_test/abort');

        $this->assertDatabaseCount('client_errors', 0);
    }

    public function test_http_403_is_not_logged(): void
    {
        Route::get('/_test/forbidden', function () {
            abort(403);
        });

        $this->get('/_test/forbidden');

        $this->assertDatabaseCount('client_errors', 0);
    }

    public function test_server_error_stack_is_truncated_to_3000_chars(): void
    {
        Route::get('/_test/deep-exception', function () {
            throw new \RuntimeException('Deep error');
        });

        $this->get('/_test/deep-exception');

        $error = \App\Models\ClientError::first();
        $this->assertNotNull($error);
        $this->assertLessThanOrEqual(3000, strlen($error->stack ?? ''));
    }
}
```

- [ ] **Step 2: テストを実行して失敗を確認する**

```bash
cd cleaning_pos && php artisan test --filter=ExceptionHandlerTest
```

期待: `test_runtime_exception_is_saved_to_database` が FAIL（レコードが作られない）、スキップ系テストは PASS

- [ ] **Step 3: Handler.php を実装する**

`app/Exceptions/Handler.php` を以下に書き換える：

```php
<?php

namespace App\Exceptions;

use App\Models\ClientError;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            $skip = [
                ValidationException::class,
                AuthenticationException::class,
                AuthorizationException::class,
                TokenMismatchException::class,
                HttpException::class,
            ];

            foreach ($skip as $class) {
                if ($e instanceof $class) {
                    return;
                }
            }

            $isHttp = !app()->runningInConsole();

            ClientError::log([
                'error_type' => 'server_error',
                'message'    => $e->getMessage() ?: get_class($e),
                'stack'      => substr($e->getTraceAsString(), 0, 3000),
                'url'        => $isHttp ? request()->url() : null,
                'user_agent' => $isHttp ? request()->userAgent() : null,
                'manager_id' => $isHttp ? request()->user()?->id : null,
            ]);
        });
    }

    public function render($request, Throwable $exception)
    {
        // Tokenエラーの時、ログイン画面にリダイレクトする。
        if ($exception instanceof TokenMismatchException) {
            return redirect(route('login'));
        }

        return parent::render($request, $exception);
    }
}
```

- [ ] **Step 4: テストを実行してパスを確認する**

```bash
cd cleaning_pos && php artisan test --filter=ExceptionHandlerTest
```

期待: 5テスト全て PASS

- [ ] **Step 5: 全テストを実行して既存機能への影響がないことを確認する**

```bash
cd cleaning_pos && php artisan test
```

期待: 全テスト PASS

- [ ] **Step 6: コミットする**

```bash
git add app/Exceptions/Handler.php tests/Feature/ExceptionHandlerTest.php
git commit -m "feat: log server exceptions to client_errors table via Handler.php"
```

---

## 動作確認

実装後に実機で確認する手順：

```bash
# サーバーエラーの一覧（認証済みセッションで curl）
GET /api/client-errors?error_type=server_error

# 本日のエラー
GET /api/client-errors?date=2026-03-19
```

意図的にエラーを発生させる場合（ローカル開発環境のみ）：

```php
// routes/web.php に一時的に追加してテスト後削除
Route::get('/test-error', function () {
    throw new \RuntimeException('手動テストエラー');
})->middleware('auth');
```

---

## 仕様書

`docs/superpowers/specs/2026-03-19-error-logging-design.md`
