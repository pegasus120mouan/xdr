<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AlienVault Open Threat Exchange (OTX) API v1
    |--------------------------------------------------------------------------
    | https://otx.alienvault.com — clé : Paramètres → OTX Key
    | En-tête : X-OTX-API-KEY
    | Chemins : /api/v1/indicators/{slug}/{indicator}/{section}
    | (slug = IPv4, IPv6, domain, file, cve, url, … — voir SDK Python OTX)
    */

    'enabled' => env('OTX_ENABLED', false),

    'base_url' => rtrim((string) env('OTX_BASE_URL', 'https://otx.alienvault.com'), '/'),

    'api_key' => env('OTX_API_KEY', ''),

    'timeout' => (int) env('OTX_TIMEOUT', 20),

    'cache_ttl' => (int) env('OTX_CACHE_TTL', 900),

    /** Appels HTTP sortants max / minute (OTX impose des quotas) */
    'rate_per_minute' => max(1, min(120, (int) env('OTX_RATE_PER_MINUTE', 20))),

];
