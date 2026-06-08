<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],

    'allowed_methods' => ['*'],

    /*
    | Origines autorisées (durcissement Phase 10) : avec supports_credentials=true,
    | un '*' permettait à N'IMPORTE QUEL site des requêtes authentifiées (faille CORS).
    | On restreint aux origines de l'application, pilotées par .env
    | (CORS_ALLOWED_ORIGINS, séparées par des virgules) ; repli sur APP_URL.
    */
    'allowed_origins' => array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', (string) env('APP_URL', 'http://localhost'))))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
