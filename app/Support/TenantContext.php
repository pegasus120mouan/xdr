<?php

namespace App\Support;

use App\Models\TenantGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class TenantContext
{
    /**
     * Utilisateur avec accès plateforme complète (MSSP / admin global).
     */
    public static function isUnrestricted(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        return $user->tenant_group_id === null;
    }

    /**
     * IDs de groupes accessibles (groupe assigné + descendants).
     *
     * @return list<int>|null null = aucune restriction
     */
    public static function accessibleGroupIds(?User $user = null): ?array
    {
        $user ??= auth()->user();

        if (! $user || $user->tenant_group_id === null) {
            return null;
        }

        $rootId = (int) $user->tenant_group_id;

        return Cache::remember(
            'tenant_access_ids:'.$rootId,
            now()->addMinutes(10),
            fn () => self::descendantIdsIncludingSelf($rootId)
        );
    }

    /**
     * @return list<int>
     */
    public static function descendantIdsIncludingSelf(int $groupId): array
    {
        $ids = [$groupId];
        $frontier = [$groupId];

        while ($frontier !== []) {
            $children = TenantGroup::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $frontier = [];
            foreach ($children as $childId) {
                if (! in_array($childId, $ids, true)) {
                    $ids[] = $childId;
                    $frontier[] = $childId;
                }
            }
        }

        return $ids;
    }

    public static function userCanAccessGroup(?User $user, ?int $groupId): bool
    {
        if (self::isUnrestricted($user)) {
            return true;
        }

        if ($groupId === null) {
            return false;
        }

        $ids = self::accessibleGroupIds($user);

        return is_array($ids) && in_array($groupId, $ids, true);
    }

    public static function userCanAccessAsset(?User $user, $asset): bool
    {
        if (self::isUnrestricted($user)) {
            return true;
        }

        $gid = is_object($asset) ? ($asset->tenant_group_id ?? null) : null;

        return self::userCanAccessGroup($user, $gid !== null ? (int) $gid : null);
    }

    public static function userCanAccessAgent(?User $user, $agent): bool
    {
        if (self::isUnrestricted($user)) {
            return true;
        }

        $gid = is_object($agent) ? ($agent->tenant_group_id ?? null) : null;

        return self::userCanAccessGroup($user, $gid !== null ? (int) $gid : null);
    }

    public static function userCanAccessAlert(?User $user, $alert): bool
    {
        if (self::isUnrestricted($user)) {
            return true;
        }

        $gid = is_object($alert) ? ($alert->tenant_group_id ?? null) : null;

        return self::userCanAccessGroup($user, $gid !== null ? (int) $gid : null);
    }

    public static function scopeAssets(Builder $query, ?User $user = null): Builder
    {
        $ids = self::accessibleGroupIds($user);
        if ($ids === null) {
            return $query;
        }

        return $query->whereIn('tenant_group_id', $ids);
    }

    public static function scopeAgents(Builder $query, ?User $user = null): Builder
    {
        $ids = self::accessibleGroupIds($user);
        if ($ids === null) {
            return $query;
        }

        return $query->whereIn('tenant_group_id', $ids);
    }

    public static function scopeAlerts(Builder $query, ?User $user = null): Builder
    {
        $ids = self::accessibleGroupIds($user);
        if ($ids === null) {
            return $query;
        }

        return $query->whereIn('tenant_group_id', $ids);
    }

    public static function scopeGroups(Builder $query, ?User $user = null): Builder
    {
        $ids = self::accessibleGroupIds($user);
        if ($ids === null) {
            return $query;
        }

        return $query->whereIn('id', $ids);
    }

    /**
     * Arbre limité à l’espace du tenant (racines = nœuds dont le parent est hors périmètre).
     */
    public static function getTreeForUser(?User $user = null)
    {
        $user ??= auth()->user();
        $ids = self::accessibleGroupIds($user);

        if ($ids === null) {
            return TenantGroup::getTree();
        }

        $groups = TenantGroup::query()
            ->whereIn('id', $ids)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('id');

        foreach ($groups as $group) {
            $group->setRelation(
                'children',
                $groups
                    ->filter(fn (TenantGroup $g) => (int) $g->parent_id === (int) $group->id)
                    ->sortBy('sort_order')
                    ->values()
            );
        }

        return $groups
            ->filter(function (TenantGroup $g) use ($groups) {
                return $g->parent_id === null || ! $groups->has((int) $g->parent_id);
            })
            ->sortBy('sort_order')
            ->values();
    }

    public static function forgetCacheForGroup(?int $groupId): void
    {
        if ($groupId === null) {
            return;
        }

        // Invalide le cache pour ce nœud et remonte jusqu’à la racine
        $current = $groupId;
        $seen = [];
        while ($current && ! isset($seen[$current])) {
            $seen[$current] = true;
            Cache::forget('tenant_access_ids:'.$current);
            $current = TenantGroup::query()->whereKey($current)->value('parent_id');
            $current = $current !== null ? (int) $current : null;
        }
    }
}
