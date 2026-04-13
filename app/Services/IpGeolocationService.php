<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpGeolocationService
{
    /**
     * @param  list<string>  $ips
     * @return array<string, array{country: string, code: ?string, flag_url: ?string}>
     */
    public function lookupMany(array $ips): array
    {
        $ips = array_values(array_unique(array_filter($ips)));
        $out = [];
        $toFetch = [];

        foreach ($ips as $ip) {
            $ip = trim((string) $ip);
            if ($ip === '' || ! filter_var($ip, FILTER_VALIDATE_IP)) {
                continue;
            }

            if (! $this->isPublicIp($ip)) {
                $out[$ip] = $this->row(null, null, 'Réseau privé / local');

                continue;
            }

            if (! config('geoip.enabled', true)) {
                continue;
            }

            $cacheKey = 'ipgeo:'.md5($ip);
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                $out[$ip] = $cached;
            } else {
                $toFetch[] = $ip;
            }
        }

        if (! config('geoip.enabled', true) || $toFetch === []) {
            return $out;
        }

        $url = rtrim((string) config('geoip.batch_url'), '/');
        $timeout = (int) config('geoip.timeout', 15);
        $ttl = (int) config('geoip.cache_ttl', 604800);

        foreach (array_chunk($toFetch, 100) as $chunk) {
            $payload = array_map(fn (string $ip) => ['query' => $ip], $chunk);

            try {
                $response = Http::timeout($timeout)
                    ->acceptJson()
                    ->asJson()
                    ->post($url.'?fields=status,message,country,countryCode,query', $payload);
            } catch (\Throwable $e) {
                Log::warning('GeoIP batch request failed', ['message' => $e->getMessage()]);
                foreach ($chunk as $ip) {
                    $row = $this->row(null, null, 'Géo. indisponible');
                    Cache::put('ipgeo:'.md5($ip), $row, now()->addMinutes(10));
                    $out[$ip] = $row;
                }

                continue;
            }

            if (! $response->successful()) {
                foreach ($chunk as $ip) {
                    $row = $this->row(null, null, 'Géo. indisponible');
                    Cache::put('ipgeo:'.md5($ip), $row, now()->addMinutes(10));
                    $out[$ip] = $row;
                }

                continue;
            }

            $rows = $response->json();
            if (! is_array($rows)) {
                foreach ($chunk as $ip) {
                    $row = $this->row(null, null, 'Géo. indisponible');
                    Cache::put('ipgeo:'.md5($ip), $row, now()->addMinutes(10));
                    $out[$ip] = $row;
                }

                continue;
            }

            foreach ($rows as $item) {
                if (! is_array($item) || empty($item['query'])) {
                    continue;
                }
                $ip = $item['query'];
                if (($item['status'] ?? '') !== 'success') {
                    $row = $this->row(null, null, 'Inconnu');
                    Cache::put('ipgeo:'.md5($ip), $row, now()->addHours(1));
                    $out[$ip] = $row;

                    continue;
                }

                $code = ! empty($item['countryCode']) ? strtoupper((string) $item['countryCode']) : null;
                $country = ! empty($item['country']) ? (string) $item['country'] : ($code ?? 'Inconnu');
                $row = $this->row($code, $this->flagUrl($code), $country);
                Cache::put('ipgeo:'.md5($ip), $row, now()->addSeconds($ttl));
                $out[$ip] = $row;
            }

            foreach ($chunk as $ip) {
                if (! isset($out[$ip])) {
                    $row = $this->row(null, null, 'Inconnu');
                    Cache::put('ipgeo:'.md5($ip), $row, now()->addHours(1));
                    $out[$ip] = $row;
                }
            }
        }

        return $out;
    }

    protected function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    /**
     * @return array{country: string, code: ?string, flag_url: ?string}
     */
    protected function row(?string $code, ?string $flagUrl, string $country): array
    {
        return [
            'country' => $country,
            'code' => $code,
            'flag_url' => $flagUrl,
        ];
    }

    protected function flagUrl(?string $countryCode): ?string
    {
        if ($countryCode === null || strlen($countryCode) !== 2) {
            return null;
        }

        $cc = strtolower($countryCode);

        return 'https://flagcdn.com/w20/'.$cc.'.png';
    }
}
