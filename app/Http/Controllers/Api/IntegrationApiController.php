<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use App\Models\SecurityAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationApiController extends Controller
{
    public function health(): JsonResponse
    {
        $configured = is_string(config('xdr.integration_token')) && config('xdr.integration_token') !== '';

        return response()->json([
            'status' => 'ok',
            'app' => 'wara-xdr',
            'time' => now()->toIso8601String(),
            'integration_configured' => $configured,
        ]);
    }

    public function alerts(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 25)));

        $query = SecurityAlert::query()
            ->with(['rule:id,name,category,severity'])
            ->orderByDesc('last_seen')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->query('severity'));
        }

        return response()->json($query->paginate($perPage));
    }

    public function blockedIps(): JsonResponse
    {
        $rows = BlockedIp::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('blocked_until')->orWhere('blocked_until', '>', now());
            })
            ->orderByDesc('created_at')
            ->get(['id', 'ip_address', 'reason', 'security_alert_id', 'blocked_until', 'created_at']);

        return response()->json(['data' => $rows]);
    }
}
