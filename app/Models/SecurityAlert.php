<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityAlert extends Model
{
    protected $fillable = [
        'detection_rule_id',
        'title',
        'description',
        'severity',
        'status',
        'source_ip',
        'target_ip',
        'source_user',
        'target_user',
        'affected_asset',
        'raw_data',
        'evidence',
        'mitre_mapping',
        'event_count',
        'first_seen',
        'last_seen',
        'assigned_to',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'evidence' => 'array',
        'mitre_mapping' => 'array',
        'first_seen' => 'datetime',
        'last_seen' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(DetectionRule::class, 'detection_rule_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['new', 'investigating']);
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public static function getStatuses(): array
    {
        return [
            'new' => ['label' => 'New', 'color' => '#ef4444'],
            'investigating' => ['label' => 'Investigating', 'color' => '#f97316'],
            'resolved' => ['label' => 'Resolved', 'color' => '#22c55e'],
            'false_positive' => ['label' => 'False Positive', 'color' => '#64748b'],
            'escalated' => ['label' => 'Escalated', 'color' => '#8b5cf6'],
        ];
    }
}
