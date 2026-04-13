<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class ThreatMinerService
{
    /**
     * @return array{type: string, value: string}|null
     */
    public function classifyIndicator(string $raw): ?array
    {
        $q = trim($raw);

        if ($q === '') {
            return null;
        }

        if (filter_var($q, FILTER_VALIDATE_IP)) {
            return ['type' => 'ip', 'value' => $q];
        }

        if (preg_match('/^[a-fA-F0-9]{32}$/', $q)) {
            return ['type' => 'sample', 'value' => strtolower($q)];
        }

        if (preg_match('/^[a-fA-F0-9]{64}$/', $q)) {
            return ['type' => 'sample', 'value' => strtolower($q)];
        }

        if (preg_match('/^(?!-)(?:[a-zA-Z0-9-]{1,63}\.)+[a-zA-Z]{2,63}$/', $q) && str_contains($q, '.')) {
            return ['type' => 'domain', 'value' => strtolower($q)];
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function query(string $indicatorType, string $value, int $rt): array
    {
        if (! config('threatminer.enabled', true)) {
            return ['ok' => false, 'reason' => 'disabled'];
        }

        $path = match ($indicatorType) {
            'ip' => 'host.php',
            'domain' => 'domain.php',
            'sample' => 'sample.php',
            default => null,
        };

        if ($path === null) {
            return ['ok' => false, 'reason' => 'unsupported_type'];
        }

        $maxRt = $indicatorType === 'sample' ? 7 : 6;
        $rt = max(1, min($rt, $maxRt));

        $cacheKey = 'threatminer:'.sha1($path.'|'.$value.'|'.$rt);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return array_merge($cached, ['cached' => true]);
        }

        $rateKey = 'threatminer:outbound';
        $perMinute = (int) config('threatminer.rate_per_minute', 8);

        if (RateLimiter::tooManyAttempts($rateKey, $perMinute)) {
            return [
                'ok' => false,
                'reason' => 'rate_limited',
                'retry_after' => RateLimiter::availableIn($rateKey),
            ];
        }

        RateLimiter::hit($rateKey, 60);

        $url = config('threatminer.base_url').'/'.$path;
        $timeout = (int) config('threatminer.timeout', 20);

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->get($url, [
                    'q' => $value,
                    'rt' => $rt,
                ]);
        } catch (\Throwable $e) {
            Log::warning('ThreatMiner request failed', ['message' => $e->getMessage()]);

            return ['ok' => false, 'reason' => 'http_exception', 'message' => $e->getMessage()];
        }

        if (! $response->successful()) {
            return [
                'ok' => false,
                'reason' => 'http_error',
                'http_status' => $response->status(),
            ];
        }

        $json = $response->json();
        if (! is_array($json)) {
            return ['ok' => false, 'reason' => 'invalid_json', 'message' => 'Réponse ThreatMiner non interprétable.'];
        }

        $payload = [
            'ok' => true,
            'cached' => false,
            'endpoint' => $path,
            'indicator_type' => $indicatorType,
            'value' => $value,
            'rt' => $rt,
            'status_code' => $json['status_code'] ?? null,
            'status_message' => $json['status_message'] ?? null,
            'results' => $json['results'] ?? null,
        ];

        Cache::put($cacheKey, $payload, now()->addSeconds((int) config('threatminer.cache_ttl', 3600)));

        return $payload;
    }

    public static function rtLabelsFor(string $indicatorType): array
    {
        return match ($indicatorType) {
            'ip' => [
                1 => 'WHOIS',
                2 => 'Passive DNS',
                3 => 'URIs',
                4 => 'Related samples (hash)',
                5 => 'SSL certificates (hash)',
                6 => 'Report tagging',
            ],
            'domain' => [
                1 => 'WHOIS',
                2 => 'Passive DNS',
                3 => 'URI samples',
                4 => 'Related samples (hash)',
                5 => 'Subdomains',
                6 => 'Report tagging',
            ],
            'sample' => [
                1 => 'Metadata',
                2 => 'HTTP traffic',
                3 => 'Hosts (domains & IPs)',
                4 => 'Mutants',
                5 => 'Registry keys',
                6 => 'AV detections',
                7 => 'Report tagging',
            ],
            default => [],
        };
    }
}
