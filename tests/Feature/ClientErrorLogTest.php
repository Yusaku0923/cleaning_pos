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
        // 31日前に移動してレコードを作成
        $this->travel(-31)->days();
        ClientError::create(['error_type' => 'server_error', 'message' => 'Old error']);
        $this->travelBack();

        // 現在時刻でレコードを作成（残るべき）
        ClientError::create(['error_type' => 'server_error', 'message' => 'Recent error']);

        ClientError::pruneExpired();

        $this->assertDatabaseMissing('client_errors', ['message' => 'Old error']);
        $this->assertDatabaseHas('client_errors', ['message' => 'Recent error']);
    }

    public function test_prune_expired_keeps_records_exactly_30_days_old(): void
    {
        // 30日前より1秒後（境界値：残るべき）
        $this->travelTo(now()->subDays(30)->addSecond());
        ClientError::create(['error_type' => 'server_error', 'message' => 'Boundary error']);
        $this->travelBack();

        ClientError::pruneExpired();

        $this->assertDatabaseHas('client_errors', ['message' => 'Boundary error']);
    }
}
