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

    'enabled' => env('IP_GEO_ENABLED', true),

    /** Durée de cache par IP (secondes) */
    'cache_ttl' => (int) env('IP_GEO_CACHE_TTL', 604800),

    'batch_url' => env('IP_GEO_BATCH_URL', 'http://ip-api.com/batch'),

    'timeout' => (int) env('IP_GEO_TIMEOUT', 15),
];
