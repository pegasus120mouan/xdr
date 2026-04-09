<?php

namespace App\Services;

use App\Models\BlockedIp;
use App\Models\DetectionRule;
use App\Models\LoginAttempt;
use App\Models\SecurityAlert;
use Illuminate\Http\Request;

class BruteForceDetector
{
    protected array $rules = [];

    public function __construct()
    {
        $this->loadRules();
    }

    protected function loadRules(): void
    {
        $this->rules = DetectionRule::active()
            ->byCategory('brute_force')
            ->get()
            ->keyBy('slug')
            ->toArray();
    }

    public function recordAttempt(Request $request, string $email, bool $successful, ?string $failureReason = null, ?int $userId = null): LoginAttempt
    {
        return LoginAttempt::create([
            'ip_address' => $request->ip(),
            'email' => $email,
            'user_agent' => $request->userAgent(),
            'successful' => $successful,
            'failure_reason' => $failureReason,
            'user_id' => $userId,
            'attempted_at' => now(),
        ]);
    }

    public function isIpBlocked(string $ip): bool
    {
        return BlockedIp::isBlocked($ip);
    }

    public function analyze(Request $request, string $email): ?SecurityAlert
    {
        $ip = $request->ip();

        // Vérifier chaque règle de brute force
        foreach ($this->rules as $rule) {
            $alert = $this->checkRule($rule, $ip, $email, $request);
            if ($alert) {
                return $alert;
            }
        }

        return null;
    }

    protected function checkRule(array $rule, string $ip, string $email, Request $request): ?SecurityAlert
    {
        $threshold = $rule['threshold'];
        $timeWindow = $rule['time_window'] / 60; // Convertir en minutes

        $conditions = $rule['conditions'];
        $failedAttempts = 0;

        // Vérifier les conditions
        if (in_array('ip_based', $conditions)) {
            $failedAttempts = max($failedAttempts, LoginAttempt::getFailedAttemptsCount($ip, $timeWindow));
        }

        if (in_array('email_based', $conditions)) {
            $failedAttempts = max($failedAttempts, LoginAttempt::getFailedAttemptsForEmail($email, $timeWindow));
        }

        if ($failedAttempts >= $threshold) {
            return $this->createAlert($rule, $ip, $email, $failedAttempts, $request);
        }

        return null;
    }

    protected function createAlert(array $rule, string $ip, string $email, int $eventCount, Request $request): SecurityAlert
    {
        // Vérifier si une alerte existe déjà pour cette IP et cette règle
        $existingAlert = SecurityAlert::where('detection_rule_id', $rule['id'])
            ->where('source_ip', $ip)
            ->where('status', 'new')
            ->where('created_at', '>=', now()->subSeconds($rule['cooldown']))
            ->first();

        if ($existingAlert) {
            // Mettre à jour l'alerte existante
            $existingAlert->update([
                'event_count' => $existingAlert->event_count + 1,
                'last_seen' => now(),
            ]);
            return $existingAlert;
        }

        // Créer une nouvelle alerte
        $alert = SecurityAlert::create([
            'detection_rule_id' => $rule['id'],
            'title' => "Brute Force Attack Detected - {$rule['name']}",
            'description' => "Multiple failed login attempts detected from IP {$ip} targeting {$email}. {$eventCount} attempts in the last " . ($rule['time_window'] / 60) . " minutes.",
            'severity' => $rule['severity'],
            'status' => 'new',
            'source_ip' => $ip,
            'source_user' => $email,
            'raw_data' => [
                'user_agent' => $request->userAgent(),
                'email' => $email,
                'failed_attempts' => $eventCount,
                'time_window' => $rule['time_window'],
            ],
            'evidence' => LoginAttempt::getRecentAttempts($ip, 60)->toArray(),
            'mitre_mapping' => [
                'tactics' => $rule['mitre_tactics'] ?? ['TA0006'],
                'techniques' => $rule['mitre_techniques'] ?? ['T1110'],
            ],
            'event_count' => $eventCount,
            'first_seen' => now(),
            'last_seen' => now(),
        ]);

        // Exécuter les actions définies dans la règle
        $this->executeActions($rule['actions'], $ip, $alert);

        return $alert;
    }

    protected function executeActions(array $actions, string $ip, SecurityAlert $alert): void
    {
        foreach ($actions as $action) {
            switch ($action['type']) {
                case 'block_ip':
                    $duration = $action['duration'] ?? null; // en heures
                    BlockedIp::block($ip, "Blocked due to brute force attack", $alert->id, null, $duration);
                    break;

                case 'notify':
                    // TODO: Implémenter les notifications (email, webhook, etc.)
                    break;

                case 'log':
                    \Log::warning("Brute force attack detected", [
                        'ip' => $ip,
                        'alert_id' => $alert->id,
                    ]);
                    break;
            }
        }
    }

    public function getStats(): array
    {
        $today = now()->startOfDay();

        return [
            'total_attempts_today' => LoginAttempt::where('attempted_at', '>=', $today)->count(),
            'failed_attempts_today' => LoginAttempt::where('attempted_at', '>=', $today)->where('successful', false)->count(),
            'blocked_ips' => BlockedIp::where('is_active', true)->count(),
            'active_alerts' => SecurityAlert::whereHas('rule', fn($q) => $q->where('category', 'brute_force'))
                ->whereIn('status', ['new', 'investigating'])
                ->count(),
        ];
    }
}
