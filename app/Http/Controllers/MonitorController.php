<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\SecurityAlert;
use App\Models\TenantGroup;
use Illuminate\Http\Request;

class MonitorController extends Controller
{
    public function monitors()
    {
        $monitoredAssets = Asset::query()
            ->where('is_monitored', true)
            ->orderBy('hostname')
            ->get(['id', 'hostname', 'ip_address', 'status']);

        $alerts = SecurityAlert::query()
            ->whereIn('status', ['new', 'investigating', 'escalated'])
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

        return view('monitors', [
            'monitoredAssets' => $monitoredAssets,
            'monitoredCount' => $monitoredAssets->count(),
            'underAttackAssetIds' => $underAttackAssetIds,
            'showIronDome' => count($underAttackAssetIds) > 0,
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
