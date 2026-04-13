<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Token Bearer pour l’API d’intégration (SIEM, webhooks sortants, scripts)
    |--------------------------------------------------------------------------
    | Routes : GET /api/v1/integrations/… — header Authorization: Bearer <token>
    */

    'integration_token' => env('XDR_INTEGRATION_TOKEN'),

];
