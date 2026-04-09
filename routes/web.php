<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DetectionController;
use App\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Detection Rules
    Route::get('/detection/rules', [DetectionController::class, 'rules'])->name('detection.rules');
    Route::patch('/detection/rules/{rule}/toggle', [DetectionController::class, 'toggleRule'])->name('detection.rules.toggle');
    Route::patch('/detection/rules/{rule}', [DetectionController::class, 'updateRule'])->name('detection.rules.update');

    // Security Alerts
    Route::get('/detection/alerts', [DetectionController::class, 'alerts'])->name('detection.alerts');
    Route::get('/detection/alerts/{alert}', [DetectionController::class, 'showAlert'])->name('detection.alerts.show');
    Route::patch('/detection/alerts/{alert}/status', [DetectionController::class, 'updateAlertStatus'])->name('detection.alerts.status');

    // Blocked IPs
    Route::get('/detection/blocked-ips', [DetectionController::class, 'blockedIps'])->name('detection.blocked-ips');
    Route::post('/detection/blocked-ips', [DetectionController::class, 'blockIp'])->name('detection.blocked-ips.block');
    Route::delete('/detection/blocked-ips/{blockedIp}', [DetectionController::class, 'unblockIp'])->name('detection.blocked-ips.unblock');

    // Login Attempts
    Route::get('/detection/login-attempts', [DetectionController::class, 'loginAttempts'])->name('detection.login-attempts');

    // Tenants & Assets
    Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
    Route::post('/tenants/groups', [TenantController::class, 'createGroup'])->name('tenants.groups.create');
    Route::patch('/tenants/groups/{group}', [TenantController::class, 'updateGroup'])->name('tenants.groups.update');
    Route::delete('/tenants/groups/{group}', [TenantController::class, 'deleteGroup'])->name('tenants.groups.delete');
    Route::get('/tenants/assets', [TenantController::class, 'assets'])->name('tenants.assets');
    Route::get('/tenants/assets/{asset}', [TenantController::class, 'showAsset'])->name('tenants.assets.show');
    Route::patch('/tenants/assets/{asset}', [TenantController::class, 'updateAsset'])->name('tenants.assets.update');
    Route::patch('/tenants/assets/{asset}/move', [TenantController::class, 'moveAsset'])->name('tenants.assets.move');
});
