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
    | Mode factice (développement uniquement)
    |--------------------------------------------------------------------------
    | À true, le flux de paiement est simulé localement (aucun appel MTN/Orange,
    | succès systématique) pour parcourir l'UI sans identifiants. IGNORÉ en
    | production par PaymentProviderRegistry (verrou de sécurité).
    */
    'fake' => env('PAYMENT_FAKE', false),

    /*
    |--------------------------------------------------------------------------
    | Délai d'attente HTTP (secondes)
    |--------------------------------------------------------------------------
    | Les bacs à sable mobile money (surtout MTN) peuvent être lents. Un délai
    | trop court provoque de faux échecs (cURL 28). 30 s par défaut.
    */
    'http_timeout' => (int) env('PAYMENT_HTTP_TIMEOUT', 30),

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
        // URLs complètes : varient selon le pays/environnement (ex. .../cm/v1/... pour
        // le Cameroun, un segment « dev » pour le bac à sable). Pilotées par .env.
        'webpayment_url' => env('PAYMENT_ORANGE_WEBPAYMENT_URL', 'https://api.orange.com/orange-money-webpay/dev/v1/webpayment'),
        'status_url' => env('PAYMENT_ORANGE_STATUS_URL', 'https://api.orange.com/orange-money-webpay/dev/v1/transactionstatus'),
        'consumer_key' => env('PAYMENT_ORANGE_CONSUMER_KEY'),
        'consumer_secret' => env('PAYMENT_ORANGE_CONSUMER_SECRET'),
        'merchant_key' => env('PAYMENT_ORANGE_MERCHANT_KEY'),
        'return_url' => env('PAYMENT_ORANGE_RETURN_URL'),
        'cancel_url' => env('PAYMENT_ORANGE_CANCEL_URL'),
        'notif_url' => env('PAYMENT_ORANGE_NOTIF_URL'),
        // Bac à sable Orange : devise de test « OUV » (comme EUR chez MTN sandbox).
        'currency' => env('PAYMENT_ORANGE_CURRENCY', 'OUV'),
        'lang' => env('PAYMENT_ORANGE_LANG', 'fr'),
    ],

];
