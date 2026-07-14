<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Géolocalisation IP (drapeaux / pays)
    |--------------------------------------------------------------------------
    | Utilisé sur la page Attack Events. API batch ip-api.com (non-SSL, usage
    | non commercial — voir https://ip-api.com/docs/batch). Mettre false pour
    | désactiver les appels sortants.
    */

    // ip-api.com free tier is non-commercial — disabled by default in production.
    'enabled' => filter_var(
        env('IP_GEO_ENABLED', env('APP_ENV') === 'production' ? false : true),
        FILTER_VALIDATE_BOOLEAN
    ),

    /** Durée de cache par IP (secondes) */
    'cache_ttl' => (int) env('IP_GEO_CACHE_TTL', 604800),

    'batch_url' => env('IP_GEO_BATCH_URL', 'http://ip-api.com/batch'),

    'timeout' => (int) env('IP_GEO_TIMEOUT', 15),
];
