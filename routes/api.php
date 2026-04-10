<?php

use App\Http\Controllers\Api\AgentApiController;
use Illuminate\Support\Facades\Route;

// Agent API Routes (no CSRF, no auth - uses API key)
Route::post('/agent/heartbeat', [AgentApiController::class, 'heartbeat']);
Route::post('/agent/logs', [AgentApiController::class, 'receiveLogs']);
Route::post('/agent/register', [AgentApiController::class, 'register']);
Route::get('/agent/install.sh', [AgentApiController::class, 'installScript']);
Route::get('/agent/install.ps1', [AgentApiController::class, 'installPowerShellScript']);
