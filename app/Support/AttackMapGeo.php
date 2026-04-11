<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class AttackMapGeo
{
    /**
     * @return array{name: string, code: string, lat: float, lng: float}|null
     */
    public static function forIp(string $ip): ?array
    {
        $ip = trim($ip);
        if ($ip === '' || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }

        return Cache::remember('attack_map_geo:'.md5($ip), 43_200, function () use ($ip) {
            try {
                $response = Http::timeout(2)->get('http://ip-api.com/json/'.$ip, [
                    'fields' => 'status,country,countryCode,lat,lon',
                ]);
                if ($response->successful()) {
                    $r = $response->json();
                    if (($r['status'] ?? '') === 'success') {
                        return [
                            'name' => (string) $r['country'],
                            'code' => (string) $r['countryCode'],
                            'lat' => (float) $r['lat'],
                            'lng' => (float) $r['lon'],
                        ];
                    }
                }
            } catch (\Throwable) {
                // API indisponible : repli déterministe
            }

            return self::syntheticFromIp($ip);
        });
    }

    /**
     * @return array{name: string, code: string, lat: float, lng: float}
     */
    private static function syntheticFromIp(string $ip): array
    {
        $regions = [
            ['name' => 'États-Unis', 'code' => 'US', 'lat' => 39.8283, 'lng' => -98.5795],
            ['name' => 'Royaume-Uni', 'code' => 'GB', 'lat' => 55.3781, 'lng' => -3.4360],
            ['name' => 'Chine', 'code' => 'CN', 'lat' => 35.8617, 'lng' => 104.1954],
            ['name' => 'Allemagne', 'code' => 'DE', 'lat' => 51.1657, 'lng' => 10.4515],
            ['name' => 'Brésil', 'code' => 'BR', 'lat' => -14.2350, 'lng' => -51.9253],
            ['name' => 'Canada', 'code' => 'CA', 'lat' => 56.1304, 'lng' => -106.3468],
            ['name' => 'Russie', 'code' => 'RU', 'lat' => 61.5240, 'lng' => 105.3188],
            ['name' => 'Japon', 'code' => 'JP', 'lat' => 36.2048, 'lng' => 138.2529],
            ['name' => 'Émirats arabes unis', 'code' => 'AE', 'lat' => 23.4241, 'lng' => 53.8478],
            ['name' => 'Afrique du Sud', 'code' => 'ZA', 'lat' => -30.5595, 'lng' => 22.9375],
            ['name' => 'Espagne', 'code' => 'ES', 'lat' => 40.4637, 'lng' => -3.7492],
            ['name' => 'Pakistan', 'code' => 'PK', 'lat' => 30.3753, 'lng' => 69.3451],
        ];
        $i = abs(crc32($ip)) % count($regions);

        return $regions[$i];
    }

    /**
     * @return array{0: float, 1: float}
     */
    public static function project(float $lat, float $lng, float $width, float $height): array
    {
        $x = ($lng + 180) / 360 * $width;
        $y = (90 - $lat) / 180 * $height;

        return [$x, $y];
    }
}
