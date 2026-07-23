<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Asset;
use App\Models\TenantGroup;
use App\Support\SecurityAudit;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tree = TenantContext::getTreeForUser($user);
        $selectedGroup = null;
        $allowedIds = TenantContext::accessibleGroupIds($user);

        $assetsQuery = Asset::with('tenantGroup');
        TenantContext::scopeAssets($assetsQuery, $user);

        if ($request->filled('group')) {
            $groupId = (int) $request->group;
            if (! TenantContext::userCanAccessGroup($user, $groupId)) {
                abort(403, 'Accès refusé à ce tenant.');
            }
            $selectedGroup = TenantGroup::with('children')->find($groupId);
            if ($selectedGroup) {
                $assets = $selectedGroup->assets()->orderBy('hostname')->paginate(20);
            } else {
                $assets = $assetsQuery->orderBy('hostname')->paginate(20);
            }
        } else {
            $assets = $assetsQuery->orderBy('hostname')->paginate(20);
        }

        $statsBase = Asset::query();
        TenantContext::scopeAssets($statsBase, $user);
        $groupsBase = TenantGroup::query();
        TenantContext::scopeGroups($groupsBase, $user);

        $stats = [
            'total_assets' => (clone $statsBase)->count(),
            'online' => (clone $statsBase)->where('status', 'online')->count(),
            'offline' => (clone $statsBase)->where('status', 'offline')->count(),
            'alerting' => (clone $statsBase)->where('status', 'alerting')->count(),
            'total_groups' => $groupsBase->count(),
        ];

        $isTenantScoped = $allowedIds !== null;
        $tenantLabel = $user->tenantGroup?->name;
        // Global enrollment secret — only surface to admins in the deploy UI
        $enrollmentToken = $user->isAdmin()
            ? (string) config('xdr.enrollment_token', '')
            : '';

        return view('tenants.index', compact(
            'tree',
            'selectedGroup',
            'assets',
            'stats',
            'isTenantScoped',
            'tenantLabel',
            'enrollmentToken'
        ));
    }

    public function createGroup(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:tenant_groups,id',
            'type' => 'required|in:folder,group,ip_range',
            'description' => 'nullable|string',
        ]);

        $parentId = $validated['parent_id'] ?? null;
        if ($parentId !== null && ! TenantContext::userCanAccessGroup($user, (int) $parentId)) {
            abort(403, 'Impossible de créer un groupe hors de votre espace.');
        }

        // Tenant user must attach under their root if no parent given
        if ($parentId === null && ! TenantContext::isUnrestricted($user)) {
            $validated['parent_id'] = $user->tenant_group_id;
        }

        $validated['slug'] = Str::slug($validated['name']).'-'.Str::random(4);

        $group = TenantGroup::create($validated);
        TenantContext::forgetCacheForGroup($group->parent_id ? (int) $group->parent_id : $group->id);

        return back()->with('success', 'Group created successfully.');
    }

    public function updateGroup(Request $request, TenantGroup $group)
    {
        $user = $request->user();
        if (! TenantContext::userCanAccessGroup($user, (int) $group->id)) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:tenant_groups,id',
        ]);

        if (isset($validated['parent_id']) && $validated['parent_id'] !== null
            && ! TenantContext::userCanAccessGroup($user, (int) $validated['parent_id'])) {
            abort(403, 'Parent hors de votre espace.');
        }

        // Tenant users cannot move their root group out of tree
        if (! TenantContext::isUnrestricted($user) && (int) $group->id === (int) $user->tenant_group_id) {
            unset($validated['parent_id']);
        }

        $group->update($validated);
        TenantContext::forgetCacheForGroup((int) $group->id);

        return back()->with('success', 'Group updated successfully.');
    }

    public function deleteGroup(TenantGroup $group)
    {
        $user = request()->user();
        if (! TenantContext::userCanAccessGroup($user, (int) $group->id)) {
            abort(403);
        }

        if ($group->is_system) {
            return back()->with('error', 'Cannot delete system group.');
        }

        if (! TenantContext::isUnrestricted($user) && (int) $group->id === (int) $user->tenant_group_id) {
            return back()->with('error', 'Impossible de supprimer le groupe racine de votre espace.');
        }

        $gid = $group->id;
        $gname = $group->name;
        $parentId = $group->parent_id;

        $uncategorized = TenantGroup::where('slug', 'uncategorized')->first();
        if ($uncategorized && TenantContext::isUnrestricted($user)) {
            Asset::where('tenant_group_id', $group->id)->update(['tenant_group_id' => $uncategorized->id]);
        } elseif ($parentId) {
            Asset::where('tenant_group_id', $group->id)->update(['tenant_group_id' => $parentId]);
        }

        TenantGroup::where('parent_id', $group->id)->update(['parent_id' => $group->parent_id]);

        $group->delete();
        TenantContext::forgetCacheForGroup($parentId ? (int) $parentId : $gid);

        SecurityAudit::log('tenant_group.deleted', [
            'group_id' => $gid,
            'name' => $gname,
        ], TenantGroup::class, $gid);

        return back()->with('success', 'Group deleted successfully.');
    }

    public function assets(Request $request)
    {
        $user = $request->user();
        $query = Asset::with('tenantGroup');
        TenantContext::scopeAssets($query, $user);

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
            $groupId = (int) $request->group;
            if (! TenantContext::userCanAccessGroup($user, $groupId)) {
                abort(403);
            }
            $query->where('tenant_group_id', $groupId);
        }

        $assets = $query->orderBy('hostname')->paginate(30);
        $groups = TenantGroup::query()->orderBy('name');
        TenantContext::scopeGroups($groups, $user);
        $groups = $groups->get();
        $types = Asset::getTypes();
        $statuses = Asset::getStatuses();

        $statsBase = Asset::query();
        TenantContext::scopeAssets($statsBase, $user);

        $stats = [
            'total' => (clone $statsBase)->count(),
            'online' => (clone $statsBase)->where('status', 'online')->count(),
            'offline' => (clone $statsBase)->where('status', 'offline')->count(),
            'alerting' => (clone $statsBase)->where('status', 'alerting')->count(),
            'critical' => (clone $statsBase)->where('is_critical', true)->count(),
            'risky' => (clone $statsBase)->whereIn('risk_level', ['high', 'critical'])->count(),
        ];

        return view('tenants.assets', compact('assets', 'groups', 'types', 'statuses', 'stats'));
    }

    public function showAsset(Asset $asset)
    {
        if (! TenantContext::userCanAccessAsset(auth()->user(), $asset)) {
            abort(403, 'Accès refusé à cette machine.');
        }

        $asset->load('tenantGroup');
        $groups = TenantGroup::query()->orderBy('name');
        TenantContext::scopeGroups($groups, auth()->user());
        $groups = $groups->get();

        $metrics = $asset->hostMetrics();
        if ($metrics === null) {
            $linked = Agent::query()->where('asset_id', $asset->id)->first()
                ?? Agent::query()->where('hostname', $asset->hostname)->orderByDesc('last_heartbeat')->first();
            if ($linked && is_array($linked->metadata['metrics'] ?? null)) {
                $metrics = $linked->metadata['metrics'];
            }
        }

        return view('tenants.asset-detail', compact('asset', 'groups', 'metrics'));
    }

    public function updateAsset(Request $request, Asset $asset)
    {
        if (! TenantContext::userCanAccessAsset($request->user(), $asset)) {
            abort(403);
        }

        $validated = $request->validate([
            'hostname' => 'required|string|max:255',
            'tenant_group_id' => 'nullable|exists:tenant_groups,id',
            'is_critical' => 'boolean',
        ]);

        if (array_key_exists('tenant_group_id', $validated) && $validated['tenant_group_id'] !== null
            && ! TenantContext::userCanAccessGroup($request->user(), (int) $validated['tenant_group_id'])) {
            abort(403, 'Groupe cible hors de votre espace.');
        }

        $validated['is_critical'] = $request->boolean('is_critical');

        $asset->update($validated);

        return back()->with('success', 'Asset updated successfully.');
    }

    public function moveAsset(Request $request, Asset $asset)
    {
        if (! TenantContext::userCanAccessAsset($request->user(), $asset)) {
            abort(403);
        }

        $validated = $request->validate([
            'tenant_group_id' => 'nullable|exists:tenant_groups,id',
        ]);

        if (($validated['tenant_group_id'] ?? null) !== null
            && ! TenantContext::userCanAccessGroup($request->user(), (int) $validated['tenant_group_id'])) {
            abort(403, 'Groupe cible hors de votre espace.');
        }

        $asset->update($validated);

        return back()->with('success', 'Asset moved successfully.');
    }

    public function getTreeJson()
    {
        $tree = TenantContext::getTreeForUser(auth()->user());

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
