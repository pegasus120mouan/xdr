<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Asset extends Model
{
    protected $fillable = [
        'hostname',
        'ip_address',
        'mac_address',
        'type',
        'os_type',
        'os_version',
        'tenant_group_id',
        'status',
        'risk_level',
        'is_critical',
        'is_monitored',
        'agent_version',
        'last_seen',
        'agent_installed_at',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'is_critical' => 'boolean',
        'is_monitored' => 'boolean',
        'last_seen' => 'datetime',
        'agent_installed_at' => 'datetime',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    public function tenantGroup(): BelongsTo
    {
        return $this->belongsTo(TenantGroup::class);
    }

    public function agent(): HasOne
    {
        return $this->hasOne(Agent::class, 'ip_address', 'ip_address');
    }

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopeOffline($query)
    {
        return $query->where('status', 'offline');
    }

    public function scopeAlerting($query)
    {
        return $query->where('status', 'alerting');
    }

    public function scopeByRisk($query, string $level)
    {
        return $query->where('risk_level', $level);
    }

    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    public static function getTypes(): array
    {
        return [
            'workstation' => ['label' => 'Workstation', 'icon' => '💻'],
            'server' => ['label' => 'Server', 'icon' => '🖥️'],
            'laptop' => ['label' => 'Laptop', 'icon' => '💻'],
            'mobile' => ['label' => 'Mobile', 'icon' => '📱'],
            'iot' => ['label' => 'IoT Device', 'icon' => '📡'],
            'network' => ['label' => 'Network Device', 'icon' => '🌐'],
            'other' => ['label' => 'Other', 'icon' => '❓'],
        ];
    }

    public static function getOsTypes(): array
    {
        return [
            'windows' => ['label' => 'Windows', 'icon' => '🪟'],
            'linux' => ['label' => 'Linux', 'icon' => '🐧'],
            'macos' => ['label' => 'macOS', 'icon' => '🍎'],
            'android' => ['label' => 'Android', 'icon' => '🤖'],
            'ios' => ['label' => 'iOS', 'icon' => '📱'],
            'other' => ['label' => 'Other', 'icon' => '❓'],
        ];
    }

    public static function getStatuses(): array
    {
        return [
            'online' => ['label' => 'Online', 'color' => '#22c55e'],
            'offline' => ['label' => 'Offline', 'color' => '#64748b'],
            'alerting' => ['label' => 'Alerting', 'color' => '#f97316'],
            'unknown' => ['label' => 'Unknown', 'color' => '#94a3b8'],
        ];
    }
}
