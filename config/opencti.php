<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenCTI GraphQL API
    |--------------------------------------------------------------------------
    | https://docs.opencti.io/latest/reference/api/
    | Authentification : Authorization: Bearer [clé API]
    | Playground : {OPENCTI_URL}/graphql (selon version / déploiement)
    */

    'enabled' => env('OPENCTI_ENABLED', false),

    /** URL de la plateforme (sans /graphql), ex. https://demo.opencti.io */
    'base_url' => rtrim((string) env('OPENCTI_URL', ''), '/'),

    'token' => env('OPENCTI_TOKEN', ''),

    /** Nombre max d’objets par recherche */
    'default_first' => max(5, min(100, (int) env('OPENCTI_SEARCH_FIRST', 25))),

    'cache_ttl' => (int) env('OPENCTI_CACHE_TTL', 900),

    'rate_per_minute' => max(1, min(120, (int) env('OPENCTI_RATE_PER_MINUTE', 30))),

    'timeout' => (int) env('OPENCTI_TIMEOUT', 25),
];
