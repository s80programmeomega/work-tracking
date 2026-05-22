<?php

/*
|--------------------------------------------------------------------------
| Web Push — Task 8b
|--------------------------------------------------------------------------
|
| Configuration centralisée pour l'envoi de notifications Web Push.
|
| Les clés VAPID sont générées une seule fois via `php artisan webpush:generate-vapid`
| puis stockées dans .env. La clé publique est exposée au frontend via l'API
| (/api/webpush/vapid-key) pour permettre l'inscription des navigateurs.
| La clé privée reste côté serveur et signe chaque push.
|
*/

return [
    'vapid' => [
        // mailto: ou https:// — identifie notre application auprès des push services.
        // Utilisé en cas de problème pour que les opérateurs des push services
        // puissent nous contacter.
        'subject' => env('VAPID_SUBJECT', 'mailto:teams@cerdafrica.org'),

        // Clé publique VAPID (encodée Base64 URL-safe) — visible côté frontend
        'public_key' => env('VAPID_PUBLIC_KEY'),

        // Clé privée VAPID — STRICTEMENT côté serveur, jamais exposée
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    'options' => [
        // Time-To-Live en secondes — le push service garde le message en file
        // pendant ce temps si le destinataire est hors ligne. 4 heures = bon
        // compromis entre fiabilité et obsolescence pour des notifications de
        // workflow (au-delà, le contexte du message risque d'avoir évolué).
        'ttl' => env('WEBPUSH_TTL', 14400),

        // Urgence du message — valeurs : 'very-low', 'low', 'normal', 'high'.
        // 'normal' réveille l'appareil mais sans urgence — adapté à nos
        // notifications de validation.
        'urgency' => env('WEBPUSH_URGENCY', 'normal'),
    ],
];
