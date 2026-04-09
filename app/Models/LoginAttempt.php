<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginAttempt extends Model
{
    protected $fillable = [
        'ip_address',
        'email',
        'user_agent',
        'country',
        'city',
        'successful',
        'failure_reason',
        'user_id',
        'attempted_at',
    ];

    protected $casts = [
        'successful' => 'boolean',
        'attempted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getFailedAttemptsCount(string $ip, int $minutes = 5): int
    {
        return self::where('ip_address', $ip)
            ->where('successful', false)
            ->where('attempted_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    public static function getFailedAttemptsForEmail(string $email, int $minutes = 5): int
    {
        return self::where('email', $email)
            ->where('successful', false)
            ->where('attempted_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    public static function getRecentAttempts(string $ip, int $minutes = 60): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('ip_address', $ip)
            ->where('attempted_at', '>=', now()->subMinutes($minutes))
            ->orderBy('attempted_at', 'desc')
            ->get();
    }
}
