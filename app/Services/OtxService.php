<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class OtxService
{
    /**
     * @return array{type: string, slug: string, value: string}|null
     */
    public function classifyIndicator(string $raw): ?array
    {
        $q = trim($raw);
        if ($q === '') {
            return null;
        }

        if (filter_var($q, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return ['type' => 'IPv4', 'slug' => 'IPv4', 'value' => $q];
        }

        if (filter_var($q, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return ['type' => 'IPv6', 'slug' => 'IPv6', 'value' => $q];
        }

        if (preg_match('/^CVE-\d{4}-\d+$/i', $q)) {
            return ['type' => 'CVE', 'slug' => 'cve', 'value' => strtoupper($q)];
        }

        if (preg_match('/^[a-fA-F0-9]{32}$/', $q)) {
            return ['type' => 'FileHash-MD5', 'slug' => 'file', 'value' => strtolower($q)];
        }

        if (preg_match('/^[a-fA-F0-9]{40}$/', $q)) {
            return ['type' => 'FileHash-SHA1', 'slug' => 'file', 'value' => strtolower($q)];
        }

        if (preg_match('/^[a-fA-F0-9]{64}$/', $q)) {
            return ['type' => 'FileHash-SHA256', 'slug' => 'file', 'value' => strtolower($q)];
        }

        if (preg_match('#^https?://#i', $q)) {
            return ['type' => 'URL', 'slug' => 'url', 'value' => $q];
        }

        if (preg_match('/^(?!-)(?:[a-zA-Z0-9-]{1,63}\.)+[a-zA-Z]{2,63}$/', $q) && str_contains($q, '.')) {
            return ['type' => 'domain', 'slug' => 'domain', 'value' => strtolower($q)];
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function defaultSectionsFor(string $type, bool $extended): array
    {
        $sections = ['general'];
        if (! $extended) {
            return $sections;
        }

        return match ($type) {
            'IPv4', 'IPv6' => ['general', 'reputation'],
            'domain', 'hostname' => ['general', 'passive_dns'],
            'URL' => ['general', 'url_list'],
            'FileHash-MD5', 'FileHash-SHA1', 'FileHash-SHA256' => ['general', 'analysis'],
            'CVE' => ['general'],
            default => ['general'],
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function lookup(string $indicator, bool $extended = false): array
    {
        if (! config('otx.enabled', false)) {
            return ['ok' => false, 'reason' => 'disabled'];
        }

        $apiKey = (string) config('otx.api_key', '');
        if ($apiKey === '') {
            return [
                'ok' => false,
                'reason' => 'missing_key',
                'message' => 'Définissez OTX_API_KEY dans .env (OTX → Settings → OTX Key).',
            ];
        }

        $classified = $this->classifyIndicator($indicator);
        if ($classified === null) {
            return [
                'ok' => false,
                'reason' => 'indicator_not_supported',
                'message' => 'OTX accepte une IPv4/IPv6, un domaine, une URL http(s), un hash MD5/SHA1/SHA256 ou un CVE (CVE-YYYY-nnn).',
            ];
        }

        $sections = $this->defaultSectionsFor($classified['type'], $extended);
        $cacheKey = 'otx:'.sha1($classified['slug'].'|'.$classified['value'].'|'.implode(',', $sections));
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return array_merge($cached, ['cached' => true]);
        }

        $base = (string) config('otx.base_url', 'https://otx.alienvault.com');
        $timeout = (int) config('otx.timeout', 20);
        $rateKey = 'otx:outbound';
        $perMinute = (int) config('otx.rate_per_minute', 20);

        $data = [
            'ok' => true,
            'cached' => false,
            'classified' => $classified,
            'sections' => [],
            'browse_url' => $this->browseUrl($classified),
        ];

        foreach ($sections as $section) {
            if (RateLimiter::tooManyAttempts($rateKey, $perMinute)) {
                $data['ok'] = false;
                $data['reason'] = 'rate_limited';
                $data['retry_after'] = RateLimiter::availableIn($rateKey);
                unset($data['sections']);

                return $data;
            }
            RateLimiter::hit($rateKey, 60);

            $path = sprintf(
                '/api/v1/indicators/%s/%s/%s',
                $classified['slug'],
                rawurlencode($classified['value']),
                $section
            );

            try {
                $response = Http::timeout($timeout)
                    ->withHeaders([
                        'X-OTX-API-KEY' => $apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($base.$path);
            } catch (\Throwable $e) {
                Log::warning('OTX request failed', ['path' => $path, 'message' => $e->getMessage()]);

                return [
                    'ok' => false,
                    'reason' => 'http_exception',
                    'message' => $e->getMessage(),
                    'classified' => $classified,
                ];
            }

            if ($response->status() === 404) {
                $data['sections'][$section] = null;
                $data['section_http'][$section] = 404;

                continue;
            }

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'reason' => 'http_error',
                    'http_status' => $response->status(),
                    'body' => Str::limit($response->body(), 400),
                    'classified' => $classified,
                ];
            }

            $json = $response->json();
            $data['sections'][$section] = is_array($json) ? $json : null;
            $data['section_http'][$section] = $response->status();
        }

        Cache::put($cacheKey, $data, now()->addSeconds((int) config('otx.cache_ttl', 900)));

        return $data;
    }

    /**
     * @param  array{type: string, slug: string, value: string}  $classified
     */
    protected function browseUrl(array $classified): string
    {
        $v = $classified['value'];
        $map = [
            'IPv4' => ['ip', $v],
            'IPv6' => ['IPv6', $v],
            'domain' => ['domain', $v],
            'URL' => ['url', $v],
            'FileHash-MD5', 'FileHash-SHA1', 'FileHash-SHA256' => ['file', $v],
            'CVE' => ['cve', $v],
        ];
        $pair = $map[$classified['type']] ?? null;
        if ($pair === null) {
            return 'https://otx.alienvault.com/browse/global/all?q='.rawurlencode($v);
        }

        return 'https://otx.alienvault.com/indicator/'.$pair[0].'/'.rawurlencode($pair[1]);
    }
}
