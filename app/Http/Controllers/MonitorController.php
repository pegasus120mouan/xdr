<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BlockedIp;
use App\Models\DetectionRule;
use App\Models\SecurityAlert;
use App\Models\TenantGroup;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MonitorController extends Controller
{
    public function monitors()
    {
        $pendingStatuses = ['new', 'investigating', 'escalated'];

        $monitoredAssets = Asset::query()
            ->where('is_monitored', true)
            ->orderBy('hostname')
            ->get(['id', 'hostname', 'ip_address', 'status', 'is_critical', 'risk_level']);

        $alerts = SecurityAlert::query()
            ->whereIn('status', $pendingStatuses)
            ->get(['target_ip', 'affected_asset']);

        $ipSet = [];
        $hostSet = [];
        foreach ($alerts as $alert) {
            if ($alert->target_ip) {
                $ipSet[strtolower(trim($alert->target_ip))] = true;
            }
            if ($alert->affected_asset) {
                $hostSet[strtolower(trim($alert->affected_asset))] = true;
            }
        }

        $underAttackAssetIds = [];
        foreach ($monitoredAssets as $asset) {
            $victim = $asset->status === 'alerting';
            if (! $victim && $asset->ip_address) {
                $victim = isset($ipSet[strtolower(trim($asset->ip_address))]);
            }
            if (! $victim) {
                $victim = isset($hostSet[strtolower(trim($asset->hostname))]);
            }
            if ($victim) {
                $underAttackAssetIds[$asset->id] = true;
            }
        }

        $criticalCount = Asset::where('is_critical', true)->count();
        $riskyCount = Asset::whereIn('risk_level', ['high', 'critical'])->count();

        $blockedActiveQuery = BlockedIp::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('blocked_until')
                    ->orWhere('blocked_until', '>', now());
            });

        $blockedCount = (clone $blockedActiveQuery)->count();
        $blockedDistinctIps = (clone $blockedActiveQuery)->distinct()->count('ip_address');

        $pendingAlertCount = SecurityAlert::whereIn('status', $pendingStatuses)->count();
        $resolvedIncidentCount = SecurityAlert::where('status', 'resolved')->count();
        $totalAlerts = SecurityAlert::count();

        $severityOpen = SecurityAlert::query()
            ->whereIn('status', $pendingStatuses)
            ->selectRaw('severity, count(*) as c')
            ->groupBy('severity')
            ->pluck('c', 'severity');

        $threatCategories = [
            ['key' => 'brute_force', 'short' => 'Brute force'],
            ['key' => 'malware', 'short' => 'Malware'],
            ['key' => 'intrusion', 'short' => 'Intrusion'],
            ['key' => 'data_exfiltration', 'short' => 'Exfiltration'],
            ['key' => 'persistence', 'short' => 'Persistence'],
            ['key' => 'command_control', 'short' => 'C & C'],
        ];

        $alertsByCat = SecurityAlert::query()
            ->whereIn('security_alerts.status', $pendingStatuses)
            ->join('detection_rules as dr', 'dr.id', '=', 'security_alerts.detection_rule_id')
            ->selectRaw('dr.category, count(*) as cnt')
            ->groupBy('dr.category')
            ->pluck('cnt', 'category');

        $recentAlerts = SecurityAlert::query()
            ->with(['rule:id,category'])
            ->orderByDesc('last_seen')
            ->limit(5)
            ->get(['id', 'title', 'severity', 'status', 'affected_asset', 'target_ip', 'last_seen']);

        $openAlertsWithMeta = SecurityAlert::query()
            ->whereIn('status', $pendingStatuses)
            ->orderByDesc('last_seen')
            ->get(['target_ip', 'affected_asset', 'title']);

        $assetAlertHints = [];
        foreach ($monitoredAssets as $asset) {
            foreach ($openAlertsWithMeta as $oa) {
                $match = false;
                if ($asset->ip_address && $oa->target_ip && strcasecmp(trim($oa->target_ip), trim($asset->ip_address)) === 0) {
                    $match = true;
                }
                if (! $match && $oa->affected_asset && strcasecmp(trim($oa->affected_asset), trim($asset->hostname)) === 0) {
                    $match = true;
                }
                if ($match) {
                    $assetAlertHints[$asset->id] = Str::limit($oa->title, 72);
                    break;
                }
            }
        }

        $assetAlertLinks = [];
        foreach ($monitoredAssets as $asset) {
            $assetAlertLinks[$asset->id] = $asset->ip_address
                ? route('detection.alerts', ['target_ip' => $asset->ip_address])
                : route('detection.alerts', ['affected' => $asset->hostname]);
        }

        $oldestTs = collect([
            Asset::min('created_at'),
            SecurityAlert::min('created_at'),
        ])->filter()->min();

        $daysProtected = $oldestTs
            ? max(1, Carbon::parse($oldestTs)->diffInDays(now()))
            : 1;

        $secureState = ($pendingAlertCount === 0 && count($underAttackAssetIds) === 0)
            ? 'secure'
            : 'warn';

        $monitorStats = [
            'days_protected' => $daysProtected,
            'critical' => $criticalCount,
            'risky' => $riskyCount,
            'blocked' => $blockedCount,
            'pending' => $pendingAlertCount,
            'resolved' => $resolvedIncidentCount,
            'total_alerts' => $totalAlerts,
            'online_assets' => Asset::where('status', 'online')->count(),
            'secure_state' => $secureState,
            'active_rules' => DetectionRule::where('is_active', true)->count(),
        ];

        return view('monitors', [
            'monitoredAssets' => $monitoredAssets,
            'monitoredCount' => $monitoredAssets->count(),
            'underAttackAssetIds' => $underAttackAssetIds,
            'showIronDome' => count($underAttackAssetIds) > 0,
            'monitorStats' => $monitorStats,
            'severityOpen' => $severityOpen,
            'threatCategories' => $threatCategories,
            'alertsByCat' => $alertsByCat,
            'recentAlerts' => $recentAlerts,
            'assetAlertHints' => $assetAlertHints,
            'assetAlertLinks' => $assetAlertLinks,
            'blockedDistinctIps' => $blockedDistinctIps,
        ]);
    }

    public function configure()
    {
        $assets = Asset::with('tenantGroup')->orderBy('hostname')->get();
        $assetsByGroup = $assets->groupBy(fn (Asset $a) => $a->tenant_group_id ?? 0);

        $groups = TenantGroup::orderBy('name')->get()->keyBy('id');

        $sections = $assetsByGroup->map(function ($groupAssets, $groupId) use ($groups) {
            $gid = (int) $groupId;
            $group = $gid > 0 ? $groups->get($gid) : null;

            return [
                'group_id' => $gid,
                'group' => $group,
                'title' => $group ? $group->name : 'Sans groupe',
                'path' => $group ? $group->path : null,
                'assets' => $groupAssets,
            ];
        })->values()->sortBy('title')->values();

        $monitoredCount = $assets->where('is_monitored', true)->count();

        return view('monitor.configure', compact('assets', 'sections', 'monitoredCount'));
    }

    public function saveConfigure(Request $request)
    {
        $validated = $request->validate([
            'monitored' => 'array',
            'monitored.*' => 'integer|exists:assets,id',
        ]);

        $ids = $validated['monitored'] ?? [];

        Asset::query()->update(['is_monitored' => false]);
        if ($ids !== []) {
            Asset::whereIn('id', $ids)->update(['is_monitored' => true]);
        }

        return redirect()
            ->route('monitor.configure')
            ->with('success', 'Sélection de surveillance enregistrée.');
    }
}
