<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BlockedIp;
use App\Models\DetectionRule;
use App\Models\SecurityAlert;
use App\Models\TenantGroup;
use App\Support\AttackMapGeo;
use App\Support\SecurityAudit;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MonitorController extends Controller
{
    public function monitors()
    {
        $user = auth()->user();
        $pendingStatuses = ['new', 'investigating', 'escalated'];
        $liveMinutes = max(1, (int) config('attack_map.live_window_minutes', 30));
        $liveSince = now()->subMinutes($liveMinutes);

        $scopeLive = function ($query) use ($liveSince) {
            $query->where(function ($q) use ($liveSince) {
                $q->where('last_seen', '>=', $liveSince)
                    ->orWhere(function ($q2) use ($liveSince) {
                        $q2->whereNull('last_seen')
                            ->where('created_at', '>=', $liveSince);
                    });
            });
        };

        $monitoredAssetsQuery = Asset::query()
            ->with('tenantGroup:id,name')
            ->where('is_monitored', true)
            ->orderBy('hostname');
        TenantContext::scopeAssets($monitoredAssetsQuery, $user);
        $monitoredAssets = $monitoredAssetsQuery->get([
            'id', 'hostname', 'ip_address', 'status', 'is_critical', 'risk_level', 'tenant_group_id',
        ]);

        // Menaces / Iron Dome : uniquement alertes de la fenêtre live
        $alertsQuery = SecurityAlert::query()
            ->whereIn('status', $pendingStatuses);
        $scopeLive($alertsQuery);
        TenantContext::scopeAlerts($alertsQuery, $user);
        $alerts = $alertsQuery->get(['target_ip', 'affected_asset']);

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

        $assetStats = Asset::query();
        TenantContext::scopeAssets($assetStats, $user);
        $criticalCount = (clone $assetStats)->where('is_critical', true)->count();
        $riskyCount = (clone $assetStats)->whereIn('risk_level', ['high', 'critical'])->count();

        $blockedActiveQuery = BlockedIp::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('blocked_until')
                    ->orWhere('blocked_until', '>', now());
            });

        $blockedCount = (clone $blockedActiveQuery)->count();
        $blockedDistinctIps = (clone $blockedActiveQuery)->distinct()->count('ip_address');

        $alertStats = SecurityAlert::query();
        TenantContext::scopeAlerts($alertStats, $user);

        $pendingBacklog = (clone $alertStats)->whereIn('status', $pendingStatuses)->count();
        $pendingAlertCount = (clone $alertStats)->whereIn('status', $pendingStatuses);
        $scopeLive($pendingAlertCount);
        $pendingAlertCount = $pendingAlertCount->count();

        $resolvedIncidentCount = (clone $alertStats)->where('status', 'resolved');
        $scopeLive($resolvedIncidentCount);
        $resolvedIncidentCount = $resolvedIncidentCount->count();

        $totalAlertsLive = (clone $alertStats);
        $scopeLive($totalAlertsLive);
        $totalAlertsLive = $totalAlertsLive->count();

        $severityOpenQuery = SecurityAlert::query()
            ->whereIn('status', $pendingStatuses)
            ->selectRaw('severity, count(*) as c')
            ->groupBy('severity');
        $scopeLive($severityOpenQuery);
        TenantContext::scopeAlerts($severityOpenQuery, $user);
        $severityOpen = $severityOpenQuery->pluck('c', 'severity');

        $threatCategories = [
            ['key' => 'brute_force', 'short' => 'Brute force'],
            ['key' => 'malware', 'short' => 'Malware'],
            ['key' => 'intrusion', 'short' => 'Intrusion'],
            ['key' => 'data_exfiltration', 'short' => 'Exfiltration'],
            ['key' => 'persistence', 'short' => 'Persistence'],
            ['key' => 'command_control', 'short' => 'C & C'],
        ];

        $alertsByCatQuery = SecurityAlert::query()
            ->whereIn('security_alerts.status', $pendingStatuses)
            ->join('detection_rules as dr', 'dr.id', '=', 'security_alerts.detection_rule_id')
            ->selectRaw('dr.category, count(*) as cnt')
            ->groupBy('dr.category')
            ->where(function ($q) use ($liveSince) {
                $q->where('security_alerts.last_seen', '>=', $liveSince)
                    ->orWhere(function ($q2) use ($liveSince) {
                        $q2->whereNull('security_alerts.last_seen')
                            ->where('security_alerts.created_at', '>=', $liveSince);
                    });
            });
        $tenantIds = TenantContext::accessibleGroupIds($user);
        if ($tenantIds !== null) {
            $alertsByCatQuery->whereIn('security_alerts.tenant_group_id', $tenantIds);
        }
        $alertsByCat = $alertsByCatQuery->pluck('cnt', 'category');

        $recentAlertsQuery = SecurityAlert::query()
            ->with(['rule:id,category'])
            ->orderByDesc('last_seen')
            ->limit(8);
        $scopeLive($recentAlertsQuery);
        TenantContext::scopeAlerts($recentAlertsQuery, $user);
        $recentAlerts = $recentAlertsQuery->get(['id', 'title', 'severity', 'status', 'affected_asset', 'target_ip', 'last_seen']);

        $openAlertsWithMetaQuery = SecurityAlert::query()
            ->whereIn('status', $pendingStatuses)
            ->orderByDesc('last_seen');
        $scopeLive($openAlertsWithMetaQuery);
        TenantContext::scopeAlerts($openAlertsWithMetaQuery, $user);
        $openAlertsWithMeta = $openAlertsWithMetaQuery->get(['target_ip', 'affected_asset', 'title']);

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

        $oldestAsset = Asset::query();
        TenantContext::scopeAssets($oldestAsset, $user);
        $oldestAlert = SecurityAlert::query();
        TenantContext::scopeAlerts($oldestAlert, $user);

        $oldestTs = collect([
            $oldestAsset->min('created_at'),
            $oldestAlert->min('created_at'),
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
            'pending_backlog' => $pendingBacklog,
            'resolved' => $resolvedIncidentCount,
            'total_alerts' => $totalAlertsLive,
            'online_assets' => (clone $assetStats)->where('status', 'online')->count(),
            'secure_state' => $secureState,
            'active_rules' => DetectionRule::where('is_active', true)->count(),
            'live_window_minutes' => $liveMinutes,
        ];

        $cyberMap = $this->buildCyberMapData($user);

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
            'liveWindowMinutes' => $liveMinutes,
            'cyberMap' => $cyberMap,
        ]);
    }

    /**
     * Payload carte cybermenaces (arcs, pays, KPIs) — style Kaspersky Cybermap.
     *
     * @return array<string, mixed>
     */
    private function buildCyberMapData($user): array
    {
        $home = config('attack_map.home');
        $liveMinutes = max(1, (int) config('attack_map.live_window_minutes', 30));
        $mapWindowStart = now()->subMinutes($liveMinutes);

        // KPIs + arcs : même fenêtre live (ex. 30 min) — pas d’historique 7j
        $todayAlertsQuery = SecurityAlert::query()
            ->where(function ($q) use ($mapWindowStart) {
                $q->where('last_seen', '>=', $mapWindowStart)
                    ->orWhere(function ($q2) use ($mapWindowStart) {
                        $q2->whereNull('last_seen')
                            ->where('created_at', '>=', $mapWindowStart);
                    });
            });
        TenantContext::scopeAlerts($todayAlertsQuery, $user);
        $todayAlerts = $todayAlertsQuery->get();

        $eventsToday = $todayAlerts->count();
        $attacksToday = (int) $todayAlerts->sum(fn (SecurityAlert $a) => max(1, (int) ($a->event_count ?? 0)));

        $threatToday = [
            'high' => $todayAlerts->whereIn('severity', ['high', 'critical'])->count(),
            'medium' => $todayAlerts->where('severity', 'medium')->count(),
            'low' => $todayAlerts->where('severity', 'low')->count(),
        ];

        $recentForMapQuery = SecurityAlert::query()
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
            ->limit(80);
        TenantContext::scopeAlerts($recentForMapQuery, $user);
        $recentForMap = $recentForMapQuery->get();

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
                'sx' => $sx,
                'sy' => $sy,
                'country' => $geo['name'],
                'code' => $code,
                'ip' => $ip,
            ];
        }

        $arcs = array_slice($arcs, 0, 40);

        $originByCode = [];
        foreach ($arcs as $a) {
            $c = $a['code'];
            if (! isset($originByCode[$c])) {
                $originByCode[$c] = [
                    'name' => $a['country'],
                    'code' => $c,
                    'sx' => [],
                    'sy' => [],
                    'ips' => [],
                ];
            }
            $originByCode[$c]['sx'][] = $a['sx'];
            $originByCode[$c]['sy'][] = $a['sy'];
            $originByCode[$c]['ips'][] = $a['ip'];
        }
        $originMarkers = [];
        foreach ($originByCode as $row) {
            $n = count($row['sx']);
            $ips = array_values(array_unique($row['ips']));
            $originMarkers[] = [
                'name' => $row['name'],
                'code' => $row['code'],
                'sx' => $n > 0 ? array_sum($row['sx']) / $n : 0,
                'sy' => $n > 0 ? array_sum($row['sy']) / $n : 0,
                'ip_count' => count($ips),
                'ips_preview' => array_slice($ips, 0, 4),
            ];
        }
        usort($originMarkers, fn ($a, $b) => $b['ip_count'] <=> $a['ip_count']);

        uasort($countryAgg, fn ($a, $b) => $b['count'] <=> $a['count']);
        $sourceCountries = array_values($countryAgg);
        $srcCounts = array_column($sourceCountries, 'count');
        $maxSrc = $srcCounts === [] ? 1 : max(1, max($srcCounts));
        foreach ($sourceCountries as $i => $row) {
            $sourceCountries[$i]['pct'] = (int) round(100 * $row['count'] / $maxSrc);
        }
        $sourceCountries = array_slice($sourceCountries, 0, 10);

        $typeSourceQuery = SecurityAlert::query()
            ->with(['rule:id,name,category'])
            ->where(function ($q) use ($mapWindowStart) {
                $q->where('last_seen', '>=', $mapWindowStart)
                    ->orWhere(function ($q2) use ($mapWindowStart) {
                        $q2->whereNull('last_seen')
                            ->where('created_at', '>=', $mapWindowStart);
                    });
            });
        TenantContext::scopeAlerts($typeSourceQuery, $user);
        $typeSource = $typeSourceQuery->get();

        $attackTypes = $typeSource
            ->groupBy(function (SecurityAlert $a) {
                $name = $a->rule?->name;
                $cat = $a->rule?->category;

                return Str::limit(trim($name ?: '') ?: (string) $cat, 22) ?: 'Autre';
            })
            ->map->count()
            ->sortDesc()
            ->take(8);

        $topTargetsQuery = SecurityAlert::query()
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
            ->limit(8);
        TenantContext::scopeAlerts($topTargetsQuery, $user);
        $topTargets = $topTargetsQuery->get();

        $recentListQuery = SecurityAlert::query()
            ->with(['rule:id,name,category'])
            ->orderByDesc('last_seen')
            ->limit(25);
        TenantContext::scopeAlerts($recentListQuery, $user);
        $recentList = $recentListQuery->get();

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

        return [
            'home' => $home,
            'liveWindowMinutes' => $liveMinutes,
            'mapSize' => ['w' => $mapW, 'h' => $mapH],
            'homeXY' => ['x' => $hx, 'y' => $hy],
            'eventsToday' => $eventsToday,
            'attacksToday' => $attacksToday,
            'threatToday' => $threatToday,
            'sourceCountries' => $sourceCountries,
            'attackTypes' => $attackTypes,
            'topTargets' => $topTargets,
            'recentRows' => $recentRows,
            'arcs' => $arcs,
            'originMarkers' => $originMarkers,
        ];
    }

    public function attackMap()
    {
        $user = auth()->user();
        $map = $this->buildCyberMapData($user);

        return view('monitor.attack-map', $map);
    }

    public function configure()
    {
        $user = auth()->user();
        $assetsQuery = Asset::with('tenantGroup')->orderBy('hostname');
        TenantContext::scopeAssets($assetsQuery, $user);
        $assets = $assetsQuery->get();
        $assetsByGroup = $assets->groupBy(fn (Asset $a) => $a->tenant_group_id ?? 0);

        $groupsQuery = TenantGroup::orderBy('name');
        TenantContext::scopeGroups($groupsQuery, $user);
        $groups = $groupsQuery->get()->keyBy('id');

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
        $user = $request->user();
        $validated = $request->validate([
            'monitored' => 'array',
            'monitored.*' => 'integer|exists:assets,id',
        ]);

        $ids = $validated['monitored'] ?? [];

        // Ne pas toucher aux actifs hors du périmètre tenant
        $allowedAssetIds = Asset::query();
        TenantContext::scopeAssets($allowedAssetIds, $user);
        $allowedAssetIds = $allowedAssetIds->pluck('id')->all();

        $ids = array_values(array_intersect(array_map('intval', $ids), $allowedAssetIds));

        Asset::query()->whereIn('id', $allowedAssetIds)->update(['is_monitored' => false]);
        if ($ids !== []) {
            Asset::whereIn('id', $ids)->update(['is_monitored' => true]);
        }

        SecurityAudit::log('monitor.assets_monitored_updated', [
            'monitored_asset_ids' => $ids,
            'count' => count($ids),
        ]);

        return redirect()
            ->route('monitor.configure')
            ->with('success', 'Sélection de surveillance enregistrée.');
    }
}
