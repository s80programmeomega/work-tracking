<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fournisseur par défaut
    |--------------------------------------------------------------------------
    */
    'default' => env('PAYMENT_DEFAULT_PROVIDER', 'mtn_momo'),

    /*
    |--------------------------------------------------------------------------
    | MTN Mobile Money — Collections (Request to Pay)
    |--------------------------------------------------------------------------
    | Les identifiants (subscription_key, api_user, api_key) sont des SECRETS :
    | ils vivent dans .env, jamais dans le code ni dans git. Sandbox par défaut.
    */
    'mtn_momo' => [
        'base_url' => env('PAYMENT_MOMO_BASE_URL', 'https://sandbox.momodeveloper.mtn.com'),
        'subscription_key' => env('PAYMENT_MOMO_SUBSCRIPTION_KEY'),
        'api_user' => env('PAYMENT_MOMO_API_USER'),
        'api_key' => env('PAYMENT_MOMO_API_KEY'),
        'target_environment' => env('PAYMENT_MOMO_TARGET_ENV', 'sandbox'),
        // URL que MTN appellera pour notifier le résultat (notre webhook).
        'callback_url' => env('PAYMENT_MOMO_CALLBACK_URL'),
        'currency' => env('PAYMENT_MOMO_CURRENCY', 'EUR'), // sandbox MTN n'accepte que EUR
    ],

    /*
    |--------------------------------------------------------------------------
    | Orange Money — Web Payment
    |--------------------------------------------------------------------------
    | Les URLs et payloads varient selon l'opérateur/pays : tout est piloté par
    | .env. consumer_key/secret et merchant_key sont des SECRETS.
    */
    'orange_money' => [
        'base_url' => env('PAYMENT_ORANGE_BASE_URL', 'https://api.orange.com'),
        'token_url' => env('PAYMENT_ORANGE_TOKEN_URL', 'https://api.orange.com/oauth/v3/token'),
        'consumer_key' => env('PAYMENT_ORANGE_CONSUMER_KEY'),
        'consumer_secret' => env('PAYMENT_ORANGE_CONSUMER_SECRET'),
        'merchant_key' => env('PAYMENT_ORANGE_MERCHANT_KEY'),
        'return_url' => env('PAYMENT_ORANGE_RETURN_URL'),
        'cancel_url' => env('PAYMENT_ORANGE_CANCEL_URL'),
        'notif_url' => env('PAYMENT_ORANGE_NOTIF_URL'),
        'currency' => env('PAYMENT_ORANGE_CURRENCY', 'XAF'),
        'lang' => env('PAYMENT_ORANGE_LANG', 'fr'),
    ],

];
