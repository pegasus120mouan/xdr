<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BlockedIp;
use App\Models\DetectionRule;
use App\Models\SecurityAlert;
use App\Models\TenantGroup;
use App\Support\AttackMapGeo;
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

    public function attackMap()
    {
        $home = config('attack_map.home');
        $startOfDay = now()->startOfDay();
        $mapWindowStart = now()->subDays(7);

        $todayAlerts = SecurityAlert::query()
            ->where(function ($q) use ($startOfDay) {
                $q->where('last_seen', '>=', $startOfDay)
                    ->orWhere(function ($q2) use ($startOfDay) {
                        $q2->whereNull('last_seen')
                            ->where('created_at', '>=', $startOfDay);
                    });
            })
            ->get();

        $eventsToday = $todayAlerts->count();
        $attacksToday = (int) $todayAlerts->sum(fn (SecurityAlert $a) => max(1, (int) ($a->event_count ?? 0)));

        $threatToday = [
            'high' => $todayAlerts->whereIn('severity', ['high', 'critical'])->count(),
            'medium' => $todayAlerts->where('severity', 'medium')->count(),
            'low' => $todayAlerts->where('severity', 'low')->count(),
        ];

        $recentForMap = SecurityAlert::query()
            ->with(['rule:id,name,category'])
            ->where(function ($q) use ($mapWindowStart) {
                $q->where('last_seen', '>=', $mapWindowStart)
                    ->orWhere(function ($q2) use ($mapWindowStart) {
                        $q2->whereNull('last_seen')
                            ->where('created_at', '>=', $mapWindowStart);
                    });
            })
            ->whereNotNull('source_ip')
            ->where('source_ip', '!=', '')
            ->orderByDesc('last_seen')
            ->limit(80)
            ->get();

        $mapW = 1000;
        $mapH = 500;
        [$hx, $hy] = AttackMapGeo::project((float) $home['lat'], (float) $home['lng'], $mapW, $mapH);

        $countryAgg = [];
        $arcs = [];
        $seenIps = [];

        foreach ($recentForMap as $alert) {
            $ip = strtolower(trim((string) $alert->source_ip));
            if ($ip === '') {
                continue;
            }
            $geo = AttackMapGeo::forIp($ip);
            if ($geo === null) {
                continue;
            }
            $code = $geo['code'];
            if (! isset($countryAgg[$code])) {
                $countryAgg[$code] = ['name' => $geo['name'], 'code' => $code, 'count' => 0];
            }
            $countryAgg[$code]['count']++;

            if (isset($seenIps[$ip])) {
                continue;
            }
            $seenIps[$ip] = true;

            [$sx, $sy] = AttackMapGeo::project($geo['lat'], $geo['lng'], $mapW, $mapH);
            $mx = ($sx + $hx) / 2;
            $bulge = min(140, max(36, abs($sx - $hx) * 0.12 + abs($sy - $hy) * 0.08));
            $my = ($sy + $hy) / 2 - $bulge;

            $arcs[] = [
                'path' => sprintf('M %.2f,%.2f Q %.2f,%.2f %.2f,%.2f', $sx, $sy, $mx, $my, $hx, $hy),
                'severity' => $alert->severity,
            ];
        }

        uasort($countryAgg, fn ($a, $b) => $b['count'] <=> $a['count']);
        $sourceCountries = array_values($countryAgg);
        $srcCounts = array_column($sourceCountries, 'count');
        $maxSrc = $srcCounts === [] ? 1 : max(1, max($srcCounts));
        foreach ($sourceCountries as $i => $row) {
            $sourceCountries[$i]['pct'] = (int) round(100 * $row['count'] / $maxSrc);
        }
        $sourceCountries = array_slice($sourceCountries, 0, 8);

        $typeSource = SecurityAlert::query()
            ->with(['rule:id,name,category'])
            ->where(function ($q) use ($mapWindowStart) {
                $q->where('last_seen', '>=', $mapWindowStart)
                    ->orWhere(function ($q2) use ($mapWindowStart) {
                        $q2->whereNull('last_seen')
                            ->where('created_at', '>=', $mapWindowStart);
                    });
            })
            ->get();

        $attackTypes = $typeSource
            ->groupBy(function (SecurityAlert $a) {
                $name = $a->rule?->name;
                $cat = $a->rule?->category;

                return Str::limit(trim($name ?: '') ?: (string) $cat, 22) ?: 'Autre';
            })
            ->map->count()
            ->sortDesc()
            ->take(8);

        $topTargets = SecurityAlert::query()
            ->where(function ($q) use ($mapWindowStart) {
                $q->where('last_seen', '>=', $mapWindowStart)
                    ->orWhere(function ($q2) use ($mapWindowStart) {
                        $q2->whereNull('last_seen')
                            ->where('created_at', '>=', $mapWindowStart);
                    });
            })
            ->whereNotNull('target_ip')
            ->where('target_ip', '!=', '')
            ->selectRaw('target_ip, MAX(COALESCE(affected_asset, "")) as affected_asset, COUNT(*) as c')
            ->groupBy('target_ip')
            ->orderByDesc('c')
            ->limit(8)
            ->get();

        $recentList = SecurityAlert::query()
            ->with(['rule:id,name,category'])
            ->orderByDesc('last_seen')
            ->limit(25)
            ->get();

        $recentRows = $recentList->map(function (SecurityAlert $a) {
            $geo = $a->source_ip ? AttackMapGeo::forIp(strtolower(trim((string) $a->source_ip))) : null;

            return [
                'time' => $a->last_seen ?? $a->created_at,
                'geo_label' => $geo['name'] ?? '—',
                'geo_code' => $geo['code'] ?? '',
                'source_ip' => $a->source_ip ?: '—',
                'target_ip' => $a->target_ip ?: '—',
                'attack_type' => Str::limit($a->rule?->name ?? $a->rule?->category ?? '—', 40),
                'severity' => $a->severity,
            ];
        });

        return view('monitor.attack-map', [
            'home' => $home,
            'mapSize' => ['w' => $mapW, 'h' => $mapH],
            'homeXY' => ['x' => $hx, 'y' => $hy],
            'eventsToday' => $eventsToday,
            'attacksToday' => $attacksToday,
            'threatToday' => $threatToday,
            'sourceCountries' => $sourceCountries,
            'attackTypes' => $attackTypes,
            'topTargets' => $topTargets,
            'recentRows' => $recentRows,
            'arcs' => array_slice($arcs, 0, 40),
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
