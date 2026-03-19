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
