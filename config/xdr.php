<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Agent enrollment token
    |--------------------------------------------------------------------------
    | Required for POST /api/agent/register (header X-Enrollment-Token or JSON
    | enrollment_token). Generate a long random secret for production.
    */
    'enrollment_token' => env('XDR_ENROLLMENT_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Data retention (days) — pruned by `php artisan xdr:prune`
    |--------------------------------------------------------------------------
    */
    'retention' => [
        'agent_logs_days' => (int) env('XDR_RETENTION_AGENT_LOGS_DAYS', 30),
        'resolved_alerts_days' => (int) env('XDR_RETENTION_RESOLVED_ALERTS_DAYS', 90),
        'login_attempts_days' => (int) env('XDR_RETENTION_LOGIN_ATTEMPTS_DAYS', 60),
        'audit_logs_days' => (int) env('XDR_RETENTION_AUDIT_LOGS_DAYS', 180),
    ],

];
