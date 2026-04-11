<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Point central des arcs (votre périmètre / SOC)
    |--------------------------------------------------------------------------
    */
    'home' => [
        'lat' => (float) env('ATTACK_MAP_HOME_LAT', 5.36),
        'lng' => (float) env('ATTACK_MAP_HOME_LNG', -4.008),
        'label' => env('ATTACK_MAP_HOME_LABEL', 'Côte d\'Ivoire'),
    ],

];
