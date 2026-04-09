<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentLog;
use App\Models\DetectionRule;
use App\Models\SecurityAlert;
use Illuminate\Support\Facades\Log;

class LogAnalyzer
{
    protected array $rules = [];
    protected array $ruleCache = [];

    public function __construct()
    {
        $this->loadRules();
    }

    /**
     * Load all active detection rules
     */
    public function loadRules(): void
    {
        $this->rules = DetectionRule::where('is_active', true)
            ->orderBy('severity', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Analyze a batch of logs from an agent
     */
    public function analyzeLogs(Agent $agent, array $logs): array
    {
        $alerts = [];

        foreach ($logs as $log) {
            $message = $log['message'] ?? '';
            $logType = $log['log_type'] ?? 'syslog';
            $severity = $log['severity'] ?? 'info';

            foreach ($this->rules as $rule) {
                if ($this->matchesRule($rule, $message, $logType, $agent)) {
                    $alert = $this->createAlert($agent, $rule, $log);
                    if ($alert) {
                        $alerts[] = $alert;
                    }
                }
            }
        }

        return $alerts;
    }

    /**
     * Check if a log message matches a detection rule
     */
    protected function matchesRule(array $rule, string $message, string $logType, Agent $agent): bool
    {
        $conditions = $rule['conditions'] ?? [];
        
        // Check log type filter if specified
        if (!empty($conditions['log_types'])) {
            if (!in_array($logType, $conditions['log_types'])) {
                return false;
            }
        }

        // Check pattern matching
        $patterns = $conditions['patterns'] ?? [];
        if (empty($patterns)) {
            return false;
        }

        $matchType = $conditions['match_type'] ?? 'any'; // 'any' or 'all'
        $matchedCount = 0;

        foreach ($patterns as $pattern) {
            if ($this->patternMatches($pattern, $message)) {
                $matchedCount++;
                if ($matchType === 'any') {
                    return true;
                }
            }
        }

        if ($matchType === 'all' && $matchedCount === count($patterns)) {
            return true;
        }

        return false;
    }

    /**
     * Check if a pattern matches the message
     */
    protected function patternMatches(string $pattern, string $message): bool
    {
        // Check if it's a regex pattern
        if (str_starts_with($pattern, '/') && preg_match('/^\/.*\/[a-z]*$/i', $pattern)) {
            return @preg_match($pattern, $message) === 1;
        }

        // Simple case-insensitive substring match
        return stripos($message, $pattern) !== false;
    }

    /**
     * Create a security alert from a matched rule
     */
    protected function createAlert(Agent $agent, array $rule, array $log): ?SecurityAlert
    {
        // Check for duplicate alerts (same rule, same agent, within time window)
        $existingAlert = SecurityAlert::where('detection_rule_id', $rule['id'])
            ->where('source_ip', $agent->ip_address)
            ->where('status', '!=', 'resolved')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->first();

        if ($existingAlert) {
            // Update existing alert count
            $existingAlert->increment('event_count');
            $existingAlert->update(['last_seen' => now()]);
            return null;
        }

        // Create new alert
        $alert = SecurityAlert::create([
            'detection_rule_id' => $rule['id'],
            'title' => $rule['name'] . ' - ' . $agent->hostname,
            'description' => $this->buildAlertDescription($rule, $log, $agent),
            'severity' => $rule['severity'],
            'source_ip' => $agent->ip_address,
            'source_user' => $this->extractUsername($log['message'] ?? ''),
            'affected_asset' => $agent->hostname,
            'raw_data' => $log,
            'status' => 'new',
            'event_count' => 1,
            'first_seen' => now(),
            'last_seen' => now(),
        ]);

        Log::info("Security alert created: {$alert->title}", [
            'alert_id' => $alert->id,
            'rule_id' => $rule['id'],
            'agent_id' => $agent->agent_id,
        ]);

        return $alert;
    }

    /**
     * Build alert description
     */
    protected function buildAlertDescription(array $rule, array $log, Agent $agent): string
    {
        $description = $rule['description'] ?? 'Security event detected';
        $description .= "\n\n**Source:** {$agent->hostname} ({$agent->ip_address})";
        $description .= "\n**Log Type:** " . ($log['log_type'] ?? 'unknown');
        $description .= "\n**Message:** " . substr($log['message'] ?? '', 0, 500);
        
        return $description;
    }

    /**
     * Extract username from log message
     */
    protected function extractUsername(string $message): ?string
    {
        // Common patterns for usernames in logs
        $patterns = [
            '/user[=:\s]+([a-zA-Z0-9_.-]+)/i',
            '/for\s+(?:invalid\s+user\s+)?([a-zA-Z0-9_.-]+)/i',
            '/from\s+([a-zA-Z0-9_.-]+)@/i',
            '/authentication\s+failure.*user=([a-zA-Z0-9_.-]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Analyze logs in real-time as they come in
     */
    public function analyzeInRealtime(Agent $agent, array $log): ?SecurityAlert
    {
        $message = $log['message'] ?? '';
        $logType = $log['log_type'] ?? 'syslog';

        foreach ($this->rules as $rule) {
            if ($this->matchesRule($rule, $message, $logType, $agent)) {
                return $this->createAlert($agent, $rule, $log);
            }
        }

        return null;
    }

    /**
     * Get detection statistics
     */
    public function getStats(): array
    {
        return [
            'total_rules' => count($this->rules),
            'active_rules' => count(array_filter($this->rules, fn($r) => $r['is_active'])),
            'alerts_today' => SecurityAlert::whereDate('created_at', today())->count(),
            'alerts_new' => SecurityAlert::where('status', 'new')->count(),
        ];
    }
}
