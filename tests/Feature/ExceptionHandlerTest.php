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
