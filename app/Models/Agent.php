<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Agent extends Model
{
    protected $fillable = [
        'agent_id',
        'name',
        'hostname',
        'ip_address',
        'tenant_group_id',
        'asset_id',
        'os_type',
        'os_version',
        'agent_version',
        'status',
        'last_heartbeat',
        'registered_at',
        'api_key',
        'config',
        'metadata',
    ];

    protected $casts = [
        'last_heartbeat' => 'datetime',
        'registered_at' => 'datetime',
        'config' => 'array',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'api_key',
    ];

    public function tenantGroup(): BelongsTo
    {
        return $this->belongsTo(TenantGroup::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AgentLog::class);
    }

    public static function generateAgentId(): string
    {
        return 'AGT-' . strtoupper(Str::random(12));
    }

    public static function generateApiKey(): string
    {
        return Str::random(64);
    }

    public function isOnline(): bool
    {
        return $this->last_heartbeat && $this->last_heartbeat->diffInMinutes(now()) < 5;
    }

    public function updateHeartbeat(): void
    {
        $this->update([
            'last_heartbeat' => now(),
            'status' => 'active',
        ]);
    }
}
