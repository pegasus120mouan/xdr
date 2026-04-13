<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ThreatMiner API (https://www.threatminer.org/api.php)
    |--------------------------------------------------------------------------
    | JSON API, ~10 requêtes / minute par politique ThreatMiner. Le cache et
    | le rate limiting côté appli réduisent les risques de blocage.
    */

    'enabled' => env('THREATMINER_ENABLED', true),

    'base_url' => rtrim(env('THREATMINER_BASE_URL', 'https://api.threatminer.org/v2'), '/'),

    /** Réponses API mises en cache (secondes) */
    'cache_ttl' => (int) env('THREATMINER_CACHE_TTL', 3600),

    /** Appels sortants max par minute (souvent une seule IP publique pour le serveur) */
    'rate_per_minute' => max(1, min(10, (int) env('THREATMINER_RATE_PER_MINUTE', 8))),

    'timeout' => (int) env('THREATMINER_TIMEOUT', 20),
];
