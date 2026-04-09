<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedIp extends Model
{
    protected $fillable = [
        'ip_address',
        'reason',
        'security_alert_id',
        'blocked_by',
        'blocked_until',
        'is_active',
    ];

    protected $casts = [
        'blocked_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function alert(): BelongsTo
    {
        return $this->belongsTo(SecurityAlert::class, 'security_alert_id');
    }

    public function blockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public static function isBlocked(string $ip): bool
    {
        return self::where('ip_address', $ip)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('blocked_until')
                    ->orWhere('blocked_until', '>', now());
            })
            ->exists();
    }

    public static function block(string $ip, string $reason, ?int $alertId = null, ?int $userId = null, ?int $hours = null): self
    {
        return self::updateOrCreate(
            ['ip_address' => $ip],
            [
                'reason' => $reason,
                'security_alert_id' => $alertId,
                'blocked_by' => $userId,
                'blocked_until' => $hours ? now()->addHours($hours) : null,
                'is_active' => true,
            ]
        );
    }

    public static function unblock(string $ip): bool
    {
        return self::where('ip_address', $ip)->update(['is_active' => false]) > 0;
    }
}
