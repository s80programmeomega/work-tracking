<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription as PushSubscriptionData;
use Minishlink\WebPush\WebPush;

/**
 * Task 8b — Canal de livraison Web Push.
 *
 * Pour utiliser ce canal :
 *   1. Le destinataire doit avoir au moins une PushSubscription active.
 *   2. La notification doit implémenter une méthode `toWebPush($notifiable)`
 *      retournant un tableau payload : ['title' => ..., 'body' => ..., 'url' => ...].
 *   3. Le canal sérialise le payload en JSON et appelle WebPush::sendOneNotification()
 *      pour chaque souscription active du user.
 *
 * Cycle d'erreurs :
 *   - 410 Gone           → l'endpoint navigateur n'est plus valide (utilisateur
 *                          a désactivé le push) → on désactive la PushSubscription.
 *   - 404 Not Found      → idem, endpoint disparu côté push service.
 *   - 5xx                → problème transitoire côté push service → on log et continue.
 *   - autres             → log warning, ne casse pas la chaîne notification.
 *
 * Le canal ne bloque jamais la file d'attente — un push qui échoue ne fait
 * pas échouer la notification globale (mail/database/broadcast continuent).
 */
class WebPushChannel
{
    /**
     * Send the notification through Web Push.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof User) {
            return;
        }

        // Récupération du payload : la notification doit fournir toWebPush().
        // S'il manque, on tombe sur toArray() et on construit un payload minimal.
        $payload = $this->buildPayload($notifiable, $notification);

        // Pas de payload exploitable → rien à envoyer
        if (empty($payload['title']) && empty($payload['body'])) {
            return;
        }

        $subscriptions = $notifiable->pushSubscriptions()->active()->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $webPush = $this->makeWebPushClient();
        if (! $webPush) {
            return;
        }

        foreach ($subscriptions as $sub) {
            try {
                $this->queueSubscription($webPush, $sub, $payload);
            } catch (\Throwable $e) {
                Log::warning('WebPush: souscription invalide ignorée', [
                    'subscription_id' => $sub->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Flush — envoi en parallèle de tous les pushes mis en file.
        foreach ($webPush->flush() as $report) {
            $this->handleReport($report, $payload['title'] ?? '');
        }
    }

    /**
     * Construire le payload à transmettre au navigateur (JSON sérialisé côté service worker).
     */
    private function buildPayload(User $notifiable, Notification $notification): array
    {
        // Si la notification expose toWebPush(), c'est la source autoritaire
        if (method_exists($notification, 'toWebPush')) {
            return (array) $notification->toWebPush($notifiable);
        }

        // Fallback : on dérive un payload lisible depuis toArray()
        $data = method_exists($notification, 'toArray') ? (array) $notification->toArray($notifiable) : [];

        $title = $data['tache_titre']
            ?? $data['title']
            ?? $data['document_nom']
            ?? $data['projet_nom']
            ?? 'Notification';

        $body = $data['message']
            ?? $data['commentaire']
            ?? $data['assigned_by']
            ?? $data['auteur_nom']
            ?? $data['shared_by']
            ?? '';

        return [
            'title' => $title,
            'body' => $body,
            'url' => $data['url'] ?? '/notifications',
            'tag' => $data['type'] ?? 'default',
            'icon' => url('/favicon.ico'),
            'data' => $data,
        ];
    }

    /**
     * Instancie un client WebPush à partir de la config VAPID.
     * Retourne null si la config est manquante (les clés VAPID n'ont pas été générées) —
     * dans ce cas le canal est inerte et logge un warning.
     */
    private function makeWebPushClient(): ?WebPush
    {
        $auth = [
            'VAPID' => [
                'subject' => config('webpush.vapid.subject'),
                'publicKey' => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ],
        ];

        if (empty($auth['VAPID']['publicKey']) || empty($auth['VAPID']['privateKey'])) {
            Log::warning('WebPush désactivé — clés VAPID manquantes dans la config', [
                'reason' => 'vapid_keys_missing',
            ]);

            return null;
        }

        $defaultOptions = [
            'TTL' => (int) config('webpush.options.ttl', 14400),
            'urgency' => config('webpush.options.urgency', 'normal'),
        ];

        return new WebPush($auth, $defaultOptions);
    }

    /**
     * Met en file un envoi push pour une souscription donnée.
     */
    private function queueSubscription(WebPush $webPush, PushSubscription $sub, array $payload): void
    {
        $subscription = PushSubscriptionData::create([
            'endpoint' => $sub->endpoint,
            'publicKey' => $sub->public_key,
            'authToken' => $sub->auth_token,
            'contentEncoding' => $sub->content_encoding ?: 'aesgcm',
        ]);

        $webPush->queueNotification($subscription, json_encode($payload));
    }

    /**
     * Traite chaque rapport de livraison. Désactive les souscriptions
     * définitivement perdues (410 Gone / 404 Not Found).
     */
    private function handleReport($report, string $title): void
    {
        $endpoint = $report->getRequest()->getUri()->__toString();
        $subscription = PushSubscription::query()->where('endpoint', $endpoint)->first();

        if ($report->isSuccess()) {
            // Marquer la souscription comme utilisée — utile pour de futures
            // statistiques d'engagement et pour la rotation des inactives.
            $subscription?->markAsUsed();
            Log::info('Push Web envoyé', [
                'subscription_id' => $subscription?->id,
                'title' => $title,
            ]);

            return;
        }

        $statusCode = $report->getResponse()?->getStatusCode();

        // 410 Gone ou 404 → l'endpoint n'existe plus côté push service
        // Le navigateur a désactivé l'inscription ou l'utilisateur a révoqué.
        // On désactive la souscription pour éviter de réessayer indéfiniment.
        if (in_array($statusCode, [404, 410], true) && $subscription) {
            $subscription->deactivate();
            Log::info('Souscription Web Push désactivée — endpoint expiré', [
                'subscription_id' => $subscription->id,
                'status_code' => $statusCode,
                'reason' => 'endpoint_gone',
            ]);

            return;
        }

        Log::warning('Échec de livraison Web Push', [
            'subscription_id' => $subscription?->id,
            'status_code' => $statusCode,
            'reason' => $report->getReason(),
        ]);
    }
}
