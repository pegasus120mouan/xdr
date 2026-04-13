<?php

use App\Http\Controllers\Api\AgentApiController;
use App\Http\Controllers\Api\IntegrationApiController;
use Illuminate\Support\Facades\Route;

// Agent API Routes (no CSRF, no auth - uses API key)
Route::post('/agent/heartbeat', [AgentApiController::class, 'heartbeat']);
Route::post('/agent/logs', [AgentApiController::class, 'receiveLogs']);
Route::post('/agent/register', [AgentApiController::class, 'register']);
Route::get('/agent/install.sh', [AgentApiController::class, 'installScript']);
Route::get('/agent/install.ps1', [AgentApiController::class, 'installPowerShellScript']);

// Intégration SIEM / scripts (Bearer XDR_INTEGRATION_TOKEN)
Route::get('/v1/integrations/health', [IntegrationApiController::class, 'health']);
Route::middleware(['xdr.integration', 'throttle:120,1'])->prefix('v1/integrations')->group(function () {
    Route::get('/alerts', [IntegrationApiController::class, 'alerts']);
    Route::get('/blocked-ips', [IntegrationApiController::class, 'blockedIps']);
});
