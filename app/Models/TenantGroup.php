<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantGroup extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'type',
        'ip_range_start',
        'ip_range_end',
        'icon',
        'color',
        'sort_order',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TenantGroup::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(TenantGroup::class, 'parent_id')->orderBy('sort_order');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function allAssets()
    {
        $assetIds = $this->assets()->pluck('id')->toArray();
        
        foreach ($this->children as $child) {
            $assetIds = array_merge($assetIds, $child->allAssets()->pluck('id')->toArray());
        }
        
        return Asset::whereIn('id', $assetIds);
    }

    public function getAssetCountAttribute(): int
    {
        return $this->assets()->count() + $this->children->sum('asset_count');
    }

    public function getOnlineCountAttribute(): int
    {
        return $this->assets()->where('status', 'online')->count() + 
               $this->children->sum('online_count');
    }

    public function getAlertingCountAttribute(): int
    {
        return $this->assets()->where('status', 'alerting')->count() + 
               $this->children->sum('alerting_count');
    }

    public static function getTree()
    {
        return self::with(['children' => function ($query) {
            $query->with('children.children.children')->orderBy('sort_order');
        }])
        ->whereNull('parent_id')
        ->orderBy('sort_order')
        ->get();
    }

    public function getPathAttribute(): string
    {
        $path = [$this->name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }
        
        return implode(' / ', $path);
    }
}
