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
    | Fenêtre « live » (KPIs SOC Monitors)
    |--------------------------------------------------------------------------
    */
    'live_window_minutes' => (int) env('ATTACK_MAP_LIVE_WINDOW_MINUTES', 30),

    /*
    |--------------------------------------------------------------------------
    | Fenêtre d’affichage des arcs (carte)
    |--------------------------------------------------------------------------
    | Les arcs / Top Attackers utilisent cette fenêtre (jours). Sinon la carte
    | reste vide dès qu’il n’y a plus d’alerte dans les 30 dernières minutes,
    | alors que le backlog (Reports) contient encore des attaques.
    */
    'display_days' => (int) env('ATTACK_MAP_DISPLAY_DAYS', 30),

];
