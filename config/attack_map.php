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

    /*
    |--------------------------------------------------------------------------
    | Fenêtre « live » de la cybermap (arcs + classement pays)
    |--------------------------------------------------------------------------
    | Les flux ne restent affichés que tant qu’il y a des alertes dans cette
    | fenêtre. Sans agent / sans nouvelles détections, la carte se vide.
    */
    'live_window_minutes' => (int) env('ATTACK_MAP_LIVE_WINDOW_MINUTES', 30),

];
