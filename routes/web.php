<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DetectionController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\ResponseController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\ThreatHuntingController;
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

    Route::get('/monitor/monitors', [MonitorController::class, 'monitors'])->name('monitor.monitors');
    Route::get('/monitor/attack-map', [MonitorController::class, 'attackMap'])->name('monitor.attack-map');

    Route::get('/monitor/configure', [MonitorController::class, 'configure'])->name('monitor.configure');
    Route::post('/monitor/configure', [MonitorController::class, 'saveConfigure'])->name('monitor.configure.save');

    // Detection Rules
    Route::get('/detection/rules', [DetectionController::class, 'rules'])->name('detection.rules');
    Route::patch('/detection/rules/{rule}/toggle', [DetectionController::class, 'toggleRule'])->name('detection.rules.toggle');
    Route::patch('/detection/rules/{rule}', [DetectionController::class, 'updateRule'])->name('detection.rules.update');

    // Security Alerts (route statique avant le binding {alert})
    Route::get('/detection/alerts/attack-events', [DetectionController::class, 'alertsAttackEvents'])->name('detection.alerts.attack-events');
    Route::get('/detection/alerts', [DetectionController::class, 'alerts'])->name('detection.alerts');
    Route::get('/detection/alerts/{alert}', [DetectionController::class, 'showAlert'])->name('detection.alerts.show');
    Route::patch('/detection/alerts/{alert}/status', [DetectionController::class, 'updateAlertStatus'])->name('detection.alerts.status');

    // Blocked IPs
    Route::get('/detection/blocked-ips', [DetectionController::class, 'blockedIps'])->name('detection.blocked-ips');
    Route::post('/detection/blocked-ips', [DetectionController::class, 'blockIp'])->name('detection.blocked-ips.block');
    Route::delete('/detection/blocked-ips/{blockedIp}', [DetectionController::class, 'unblockIp'])->name('detection.blocked-ips.unblock');

    // Login Attempts
    Route::get('/detection/login-attempts', [DetectionController::class, 'loginAttempts'])->name('detection.login-attempts');

    // Automated responses (SOAR-style playbooks tied to detection rules)
    Route::get('/responses', [ResponseController::class, 'index'])->name('responses.index');
    Route::get('/responses/auto-containment', [ResponseController::class, 'autoContainment'])->name('responses.auto-containment');
    Route::get('/responses/soar', [ResponseController::class, 'soar'])->name('responses.soar');

    // Threat hunting (investigator, pivots, cross-source search)
    Route::get('/threat-hunting', [ThreatHuntingController::class, 'index'])->name('threat-hunting.index');

    // Tenants & Assets
    Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
    Route::post('/tenants/groups', [TenantController::class, 'createGroup'])->name('tenants.groups.create');
    Route::patch('/tenants/groups/{group}', [TenantController::class, 'updateGroup'])->name('tenants.groups.update');
    Route::delete('/tenants/groups/{group}', [TenantController::class, 'deleteGroup'])->name('tenants.groups.delete');
    Route::get('/tenants/assets', [TenantController::class, 'assets'])->name('tenants.assets');
    Route::get('/tenants/assets/{asset}', [TenantController::class, 'showAsset'])->name('tenants.assets.show');
    Route::patch('/tenants/assets/{asset}', [TenantController::class, 'updateAsset'])->name('tenants.assets.update');
    Route::patch('/tenants/assets/{asset}/move', [TenantController::class, 'moveAsset'])->name('tenants.assets.move');

    // Agents
    Route::get('/agents', [AgentController::class, 'index'])->name('agents.index');
    Route::get('/agents/create', [AgentController::class, 'create'])->name('agents.create');
    Route::post('/agents', [AgentController::class, 'store'])->name('agents.store');
    Route::get('/agents/{agent}', [AgentController::class, 'show'])->name('agents.show');
    Route::get('/agents/{agent}/logs', [AgentController::class, 'logs'])->name('agents.logs');
    Route::get('/agents/{agent}/install.sh', [AgentController::class, 'installScript'])->name('agents.install-script');
    Route::delete('/agents/{agent}', [AgentController::class, 'delete'])->name('agents.delete');
});
