<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentLog extends Model
{
    protected $fillable = [
        'agent_id',
        'log_type',
        'source_file',
        'message',
        'severity',
        'facility',
        'hostname',
        'process',
        'pid',
        'raw_data',
        'log_timestamp',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'log_timestamp' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public static function getSeverities(): array
    {
        return [
            'emergency' => ['label' => 'Emergency', 'color' => '#dc2626', 'level' => 0],
            'alert' => ['label' => 'Alert', 'color' => '#ef4444', 'level' => 1],
            'critical' => ['label' => 'Critical', 'color' => '#f97316', 'level' => 2],
            'error' => ['label' => 'Error', 'color' => '#fb923c', 'level' => 3],
            'warning' => ['label' => 'Warning', 'color' => '#eab308', 'level' => 4],
            'notice' => ['label' => 'Notice', 'color' => '#22c55e', 'level' => 5],
            'info' => ['label' => 'Info', 'color' => '#3b82f6', 'level' => 6],
            'debug' => ['label' => 'Debug', 'color' => '#64748b', 'level' => 7],
        ];
    }

    public static function getLogTypes(): array
    {
        return [
            'syslog' => 'System Log',
            'auth' => 'Authentication',
            'apache' => 'Apache',
            'nginx' => 'Nginx',
            'mysql' => 'MySQL',
            'postgresql' => 'PostgreSQL',
            'docker' => 'Docker',
            'kernel' => 'Kernel',
            'cron' => 'Cron',
            'mail' => 'Mail',
            'custom' => 'Custom',
        ];
    }
}
