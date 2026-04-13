<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\TenantGroup;
use App\Support\SecurityAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $tree = TenantGroup::getTree();
        $selectedGroup = null;
        $assets = collect();

        if ($request->filled('group')) {
            $selectedGroup = TenantGroup::with('children')->find($request->group);
            if ($selectedGroup) {
                $assets = $selectedGroup->assets()->orderBy('hostname')->paginate(20);
            }
        } else {
            // Show all assets by default
            $assets = Asset::with('tenantGroup')->orderBy('hostname')->paginate(20);
        }

        $stats = [
            'total_assets' => Asset::count(),
            'online' => Asset::where('status', 'online')->count(),
            'offline' => Asset::where('status', 'offline')->count(),
            'alerting' => Asset::where('status', 'alerting')->count(),
            'total_groups' => TenantGroup::count(),
        ];

        return view('tenants.index', compact('tree', 'selectedGroup', 'assets', 'stats'));
    }

    public function createGroup(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:tenant_groups,id',
            'type' => 'required|in:folder,group,ip_range',
            'description' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']).'-'.Str::random(4);

        TenantGroup::create($validated);

        return back()->with('success', 'Group created successfully.');
    }

    public function updateGroup(Request $request, TenantGroup $group)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:tenant_groups,id',
        ]);

        $group->update($validated);

        return back()->with('success', 'Group updated successfully.');
    }

    public function deleteGroup(TenantGroup $group)
    {
        if ($group->is_system) {
            return back()->with('error', 'Cannot delete system group.');
        }

        $gid = $group->id;
        $gname = $group->name;

        // Move assets to uncategorized
        $uncategorized = TenantGroup::where('slug', 'uncategorized')->first();
        if ($uncategorized) {
            Asset::where('tenant_group_id', $group->id)->update(['tenant_group_id' => $uncategorized->id]);
        }

        // Move children to parent
        TenantGroup::where('parent_id', $group->id)->update(['parent_id' => $group->parent_id]);

        $group->delete();

        SecurityAudit::log('tenant_group.deleted', [
            'group_id' => $gid,
            'name' => $gname,
        ], TenantGroup::class, $gid);

        return back()->with('success', 'Group deleted successfully.');
    }

    public function assets(Request $request)
    {
        $query = Asset::with('tenantGroup');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('hostname', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('risk')) {
            $query->where('risk_level', $request->risk);
        }

        if ($request->filled('group')) {
            $query->where('tenant_group_id', $request->group);
        }

        $assets = $query->orderBy('hostname')->paginate(30);
        $groups = TenantGroup::orderBy('name')->get();
        $types = Asset::getTypes();
        $statuses = Asset::getStatuses();

        $stats = [
            'total' => Asset::count(),
            'online' => Asset::where('status', 'online')->count(),
            'offline' => Asset::where('status', 'offline')->count(),
            'alerting' => Asset::where('status', 'alerting')->count(),
            'critical' => Asset::where('is_critical', true)->count(),
            'risky' => Asset::whereIn('risk_level', ['high', 'critical'])->count(),
        ];

        return view('tenants.assets', compact('assets', 'groups', 'types', 'statuses', 'stats'));
    }

    public function showAsset(Asset $asset)
    {
        $asset->load('tenantGroup');
        $groups = TenantGroup::orderBy('name')->get();

        return view('tenants.asset-detail', compact('asset', 'groups'));
    }

    public function updateAsset(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'hostname' => 'required|string|max:255',
            'tenant_group_id' => 'nullable|exists:tenant_groups,id',
            'is_critical' => 'boolean',
        ]);

        $validated['is_critical'] = $request->boolean('is_critical');

        $asset->update($validated);

        return back()->with('success', 'Asset updated successfully.');
    }

    public function moveAsset(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'tenant_group_id' => 'nullable|exists:tenant_groups,id',
        ]);

        $asset->update($validated);

        return back()->with('success', 'Asset moved successfully.');
    }

    public function getTreeJson()
    {
        $tree = TenantGroup::getTree();

        return response()->json($this->buildTreeJson($tree));
    }

    private function buildTreeJson($groups, $level = 0): array
    {
        $result = [];
        foreach ($groups as $group) {
            $item = [
                'id' => $group->id,
                'name' => $group->name,
                'type' => $group->type,
                'asset_count' => $group->assets()->count(),
                'children' => $this->buildTreeJson($group->children, $level + 1),
            ];
            $result[] = $item;
        }

        return $result;
    }
}
