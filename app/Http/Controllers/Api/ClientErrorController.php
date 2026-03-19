<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientError;
use Illuminate\Http\Request;

class ClientErrorController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'error_type' => 'required|string|max:20',
            'message' => 'required|string',
            'stack' => 'nullable|string',
            'url' => 'nullable|string|max:500',
            'extra_data' => 'nullable|array',
        ]);

        ClientError::log([
            'error_type' => $validated['error_type'],
            'message'    => $validated['message'],
            'stack'      => $validated['stack'] ?? null,
            'url'        => $validated['url'] ?? $request->header('Referer'),
            'user_agent' => $request->userAgent(),
            'manager_id' => $request->user()?->id,
            'extra_data' => $validated['extra_data'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    public function index(Request $request)
    {
        $query = ClientError::query();

        if ($request->has('error_type')) {
            $query->where('error_type', $request->error_type);
        }

        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $errors = $query->orderBy('created_at', 'desc')
                        ->limit(100)
                        ->get();

        return response()->json($errors);
    }
}
