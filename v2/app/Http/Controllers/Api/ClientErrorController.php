<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** 旧と同じ入力のブラウザ JS エラー収集 (未ログインでも送れる。1 IP あたり 60 回/分に制限)。 */
class ClientErrorController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'error_type' => 'required|string|max:20',
            'message' => 'required|string',
            'stack' => 'nullable|string',
            'url' => 'nullable|string|max:500',
            'extra_data' => 'nullable|array',
        ]);

        try {
            if (random_int(1, 10) === 1) {
                // 30 日より古いものを消す (旧と同じ)
                ClientError::where('created_at', '<', now()->subDays(30))->delete();
            }
            ClientError::create([
                'error_type' => $validated['error_type'],
                'message' => $validated['message'],
                'stack' => $validated['stack'] ?? null,
                'url' => $validated['url'] ?? mb_substr((string) $request->header('Referer'), 0, 500),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'manager_id' => $request->user()?->id,
                'extra_data' => $validated['extra_data'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('ClientError log failed: '.$e->getMessage());
        }

        return response()->json(['success' => true]);
    }
}
