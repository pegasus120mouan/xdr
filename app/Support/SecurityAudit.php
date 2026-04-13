<?php

namespace App\Support;

use App\Models\SecurityAuditLog;
use Illuminate\Support\Str;

class SecurityAudit
{
    /**
     * @param  array<string, mixed>|null  $properties
     */
    public static function log(string $action, ?array $properties = null, ?string $subjectType = null, ?int $subjectId = null): void
    {
        SecurityAuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) (request()->userAgent() ?? ''), 500),
            'created_at' => now(),
        ]);
    }
}
