<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetectionRule extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'severity',
        'conditions',
        'actions',
        'threshold',
        'time_window',
        'cooldown',
        'is_active',
        'is_system',
        'mitre_tactics',
        'mitre_techniques',
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'mitre_tactics' => 'array',
        'mitre_techniques' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function alerts(): HasMany
    {
        return $this->hasMany(SecurityAlert::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public static function getCategories(): array
    {
        return [
            'brute_force' => 'Brute Force',
            'malware' => 'Malware',
            'intrusion' => 'Intrusion',
            'data_exfiltration' => 'Data Exfiltration',
            'privilege_escalation' => 'Privilege Escalation',
            'lateral_movement' => 'Lateral Movement',
            'persistence' => 'Persistence',
            'command_control' => 'Command & Control',
        ];
    }

    public static function getSeverities(): array
    {
        return [
            'critical' => ['label' => 'Critical', 'color' => '#ef4444'],
            'high' => ['label' => 'High', 'color' => '#f97316'],
            'medium' => ['label' => 'Medium', 'color' => '#eab308'],
            'low' => ['label' => 'Low', 'color' => '#22c55e'],
        ];
    }
}
