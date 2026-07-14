<?php

use App\Http\Controllers\Api\AgentApiController;
use App\Http\Controllers\Api\IntegrationApiController;
use Illuminate\Support\Facades\Route;

// Agent scripts (public download)
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/agent/install.sh', [AgentApiController::class, 'installScript']);
    Route::get('/agent/install.ps1', [AgentApiController::class, 'installPowerShellScript']);
    Route::get('/agent/uninstall.sh', [AgentApiController::class, 'uninstallScript']);
    Route::get('/agent/uninstall.ps1', [AgentApiController::class, 'uninstallPowerShellScript']);
    Route::get('/agent/update.sh', [AgentApiController::class, 'updateScript']);
    Route::get('/agent/scan.sh', [AgentApiController::class, 'scanScript']);
});

// Enrollment (strict)
Route::post('/agent/register', [AgentApiController::class, 'register'])
    ->middleware('throttle:10,1');

// Authenticated agent ingest
Route::middleware('throttle:120,1')->group(function () {
    Route::post('/agent/heartbeat', [AgentApiController::class, 'heartbeat']);
    Route::post('/agent/logs', [AgentApiController::class, 'receiveLogs']);
    Route::post('/agent/unregister', [AgentApiController::class, 'unregister']);
    Route::post('/agent/scan/trigger', [AgentApiController::class, 'triggerScan']);
    Route::get('/agent/scan/check', [AgentApiController::class, 'checkScan']);
    Route::post('/agent/scan/results', [AgentApiController::class, 'receiveScanResults']);
});

// Intégration SIEM / scripts (Bearer XDR_INTEGRATION_TOKEN)
Route::get('/v1/integrations/health', [IntegrationApiController::class, 'health']);
Route::middleware(['xdr.integration', 'throttle:120,1'])->prefix('v1/integrations')->group(function () {
    Route::get('/alerts', [IntegrationApiController::class, 'alerts']);
    Route::get('/blocked-ips', [IntegrationApiController::class, 'blockedIps']);
});
