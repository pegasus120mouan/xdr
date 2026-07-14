<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BlockedIp;
use App\Models\SecurityAlert;
use App\Models\TenantGroup;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $groupsQuery = TenantGroup::query()->orderBy('name');
        TenantContext::scopeGroups($groupsQuery, $user);
        $groups = $groupsQuery->get();

        $tenantId = $request->filled('tenant') ? (int) $request->input('tenant') : null;

        if ($tenantId !== null && ! TenantContext::userCanAccessGroup($user, $tenantId)) {
            abort(403, 'Tenant hors de votre espace.');
        }

        if ($tenantId === null && ! TenantContext::isUnrestricted($user)) {
            $tenantId = (int) $user->tenant_group_id;
        }

        $selectedGroup = $tenantId ? TenantGroup::find($tenantId) : null;

        $scopeIds = $selectedGroup
            ? TenantContext::descendantIdsIncludingSelf((int) $selectedGroup->id)
            : TenantContext::accessibleGroupIds($user);

        $assetsQuery = Asset::query();
        $alertsQuery = SecurityAlert::query();

        if ($scopeIds !== null) {
            $assetsQuery->whereIn('tenant_group_id', $scopeIds);
            $alertsQuery->whereIn('tenant_group_id', $scopeIds);
        } elseif ($selectedGroup) {
            $assetsQuery->whereIn('tenant_group_id', TenantContext::descendantIdsIncludingSelf((int) $selectedGroup->id));
            $alertsQuery->whereIn('tenant_group_id', TenantContext::descendantIdsIncludingSelf((int) $selectedGroup->id));
        } else {
            TenantContext::scopeAssets($assetsQuery, $user);
            TenantContext::scopeAlerts($alertsQuery, $user);
        }

        $periodDays = max(1, min(90, (int) $request->input('days', 30)));
        $since = now()->subDays($periodDays);

        $stats = [
            'assets_total' => (clone $assetsQuery)->count(),
            'assets_online' => (clone $assetsQuery)->where('status', 'online')->count(),
            'assets_offline' => (clone $assetsQuery)->where('status', 'offline')->count(),
            'assets_alerting' => (clone $assetsQuery)->where('status', 'alerting')->count(),
            'assets_critical' => (clone $assetsQuery)->where('is_critical', true)->count(),
            'assets_risky' => (clone $assetsQuery)->whereIn('risk_level', ['critical', 'high', 'medium'])->count(),
            'alerts_total' => (clone $alertsQuery)->where('last_seen', '>=', $since)->count(),
            'alerts_open' => (clone $alertsQuery)->whereIn('status', ['new', 'investigating', 'escalated'])->count(),
            'alerts_critical' => (clone $alertsQuery)
                ->where('severity', 'critical')
                ->whereIn('status', ['new', 'investigating', 'escalated'])
                ->count(),
            'alerts_resolved' => (clone $alertsQuery)
                ->where('status', 'resolved')
                ->where('resolved_at', '>=', $since)
                ->count(),
        ];

        $blockedQuery = BlockedIp::query()->where('is_active', true);
        if ($scopeIds !== null) {
            $blockedQuery->whereHas('alert', fn ($q) => $q->whereIn('tenant_group_id', $scopeIds));
        } elseif (! TenantContext::isUnrestricted($user)) {
            $ids = TenantContext::accessibleGroupIds($user) ?? [];
            $blockedQuery->whereHas('alert', fn ($q) => $q->whereIn('tenant_group_id', $ids));
        }
        $stats['blocked_ips'] = $blockedQuery->count();

        $severityBreakdown = (clone $alertsQuery)
            ->where('last_seen', '>=', $since)
            ->selectRaw('severity, count(*) as total')
            ->groupBy('severity')
            ->pluck('total', 'severity');

        $recentAlerts = (clone $alertsQuery)
            ->with('tenantGroup')
            ->orderByDesc('last_seen')
            ->limit(15)
            ->get();

        $riskyAssets = (clone $assetsQuery)
            ->with('tenantGroup')
            ->where(function ($q) {
                $q->where('is_critical', true)
                    ->orWhereIn('risk_level', ['critical', 'high', 'medium'])
                    ->orWhere('status', 'alerting');
            })
            ->orderByRaw("CASE risk_level WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END")
            ->orderBy('hostname')
            ->limit(15)
            ->get();

        return view('reports.index', [
            'groups' => $groups,
            'selectedGroup' => $selectedGroup,
            'tenantId' => $tenantId,
            'periodDays' => $periodDays,
            'stats' => $stats,
            'severityBreakdown' => $severityBreakdown,
            'recentAlerts' => $recentAlerts,
            'riskyAssets' => $riskyAssets,
            'generatedAt' => now(),
        ]);
    }

    public function updateLogo(Request $request, TenantGroup $group)
    {
        $user = $request->user();
        if (! TenantContext::userCanAccessGroup($user, (int) $group->id)) {
            abort(403);
        }

        if (! $user->isAdmin()) {
            abort(403, 'Seul un administrateur peut modifier le logo.');
        }

        $validated = $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,webp,svg|max:2048',
        ]);

        if ($group->logo_path) {
            Storage::disk('public')->delete($group->logo_path);
        }

        $path = $validated['logo']->store('tenant-logos', 'public');
        $group->update(['logo_path' => $path]);
        TenantContext::forgetCacheForGroup((int) $group->id);

        return back()->with('success', 'Logo du tenant mis à jour.');
    }

    public function deleteLogo(Request $request, TenantGroup $group)
    {
        $user = $request->user();
        if (! TenantContext::userCanAccessGroup($user, (int) $group->id)) {
            abort(403);
        }

        if (! $user->isAdmin()) {
            abort(403, 'Seul un administrateur peut modifier le logo.');
        }

        if ($group->logo_path) {
            Storage::disk('public')->delete($group->logo_path);
            $group->update(['logo_path' => null]);
            TenantContext::forgetCacheForGroup((int) $group->id);
        }

        return back()->with('success', 'Logo du tenant supprimé.');
    }
}
