<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BlockedIp;
use App\Models\DetectionRule;
use App\Models\SecurityAlert;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $assets = Asset::query();
        TenantContext::scopeAssets($assets, $user);

        $alerts = SecurityAlert::query();
        TenantContext::scopeAlerts($alerts, $user);

        $openStatuses = ['new', 'investigating', 'escalated'];

        $pendingAlerts = (clone $alerts)->whereIn('status', $openStatuses)->count();
        $criticalOpen = (clone $alerts)->whereIn('status', $openStatuses)->where('severity', 'critical')->count();
        $highOpen = (clone $alerts)->whereIn('status', $openStatuses)->where('severity', 'high')->count();
        $mediumOpen = (clone $alerts)->whereIn('status', $openStatuses)->where('severity', 'medium')->count();
        $lowOpen = (clone $alerts)->whereIn('status', $openStatuses)->whereIn('severity', ['low', 'info'])->count();

        $riskyAssets = (clone $assets)
            ->where(function ($q) {
                $q->where('is_critical', true)
                    ->orWhereIn('risk_level', ['critical', 'high', 'medium'])
                    ->orWhere('status', 'alerting');
            })
            ->count();

        $assetsTotal = (clone $assets)->count();
        $assetsOnline = (clone $assets)->where('status', 'online')->count();
        $assetsOffline = (clone $assets)->where('status', 'offline')->count();
        $assetsAlerting = (clone $assets)->where('status', 'alerting')->count();

        $resolvedWeek = (clone $alerts)
            ->where('status', 'resolved')
            ->where('resolved_at', '>=', now()->subDays(7))
            ->count();

        $alertsPeriod = (clone $alerts)->where('last_seen', '>=', now()->subDays(30))->count();
        $fixRate = $alertsPeriod > 0
            ? round(($resolvedWeek / max(1, $alertsPeriod)) * 100, 1)
            : 0.0;

        $blockedQuery = BlockedIp::query()->where('is_active', true);
        $scopeIds = TenantContext::accessibleGroupIds($user);
        if ($scopeIds !== null) {
            $blockedQuery->whereHas('alert', fn ($q) => $q->whereIn('tenant_group_id', $scopeIds));
        }
        $blockedIps = $blockedQuery->count();

        $blockingEnabled = DetectionRule::query()
            ->where('category', 'brute_force')
            ->where('is_active', true)
            ->get()
            ->contains(
                fn (DetectionRule $rule) => collect($rule->actions ?? [])->contains(
                    fn (array $a) => ($a['type'] ?? '') === 'block_ip'
                )
            );

        $trendLabels = [];
        $trendData = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $trendLabels[] = $day->format('m-d');
            $dayAlerts = SecurityAlert::query();
            TenantContext::scopeAlerts($dayAlerts, $user);
            $trendData[] = $dayAlerts
                ->whereDate('last_seen', $day)
                ->count();
        }

        $donut = [
            'pending' => $pendingAlerts,
            'risky' => $riskyAssets,
            'critical' => $criticalOpen,
            'blocked' => $blockedIps,
        ];
        $donutTotal = max(1, array_sum($donut));

        return view('dashboard', [
            'stats' => [
                'pending_alerts' => $pendingAlerts,
                'critical_open' => $criticalOpen,
                'high_open' => $highOpen,
                'medium_open' => $mediumOpen,
                'low_open' => $lowOpen,
                'risky_assets' => $riskyAssets,
                'assets_total' => $assetsTotal,
                'assets_online' => $assetsOnline,
                'assets_offline' => $assetsOffline,
                'assets_alerting' => $assetsAlerting,
                'resolved_week' => $resolvedWeek,
                'fix_rate' => $fixRate,
                'blocked_ips' => $blockedIps,
                'alerts_30d' => $alertsPeriod,
            ],
            'blockingEnabled' => $blockingEnabled,
            'donut' => $donut,
            'donutTotal' => $donutTotal,
            'trendLabels' => $trendLabels,
            'trendData' => $trendData,
            'periodFrom' => now()->subDays(30)->format('Y-m-d H:i:s'),
            'periodTo' => now()->format('Y-m-d H:i:s'),
        ]);
    }
}
