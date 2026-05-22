<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Task 8b — Gestion des souscriptions Web Push côté client.
 *
 * Flux côté navigateur :
 *   1. Le frontend récupère la clé publique VAPID via GET /webpush/vapid-key
 *   2. L'utilisateur accorde la permission de notification (prompt navigateur)
 *   3. Le navigateur s'enregistre auprès de son push service (FCM, Mozilla, Apple)
 *      et récupère un PushSubscription objet contenant endpoint + p256dh + auth
 *   4. Le frontend POST /webpush/subscribe pour persister cette souscription côté serveur
 *   5. Lors d'une déconnexion / révocation, le frontend appelle DELETE /webpush/unsubscribe
 *
 * Tous les endpoints requièrent l'authentification Sanctum (configurée dans routes/api.php).
 */
class PushSubscriptionController extends Controller
{
    /**
     * GET /webpush/vapid-key
     *
     * Retourne la clé publique VAPID nécessaire au navigateur pour s'inscrire
     * auprès de son push service. Cette clé est publique par design — pas de
     * risque de la transmettre au client.
     */
    public function vapidKey(): JsonResponse
    {
        $publicKey = config('webpush.vapid.public_key');

        if (! $publicKey) {
            return response()->json([
                'message' => 'Web Push non configuré sur cet environnement (VAPID_PUBLIC_KEY manquante).',
            ], 503);
        }

        return response()->json([
            'public_key' => $publicKey,
        ]);
    }

    /**
     * POST /webpush/subscribe
     *
     * Persiste une souscription Web Push pour l'utilisateur authentifié.
     *
     * Comportement « upsert » : si l'endpoint existe déjà pour ce user, on
     * met à jour les clés et on remet active = true. Cela permet à un
     * navigateur de re-souscrire après une révocation sans créer de doublon.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'url', 'max:500'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'content_encoding' => ['nullable', 'string', 'in:aesgcm,aes128gcm'],
        ]);

        $user = $request->user();

        $subscription = PushSubscription::updateOrCreate(
            [
                'user_id' => $user->id,
                'endpoint' => $validated['endpoint'],
            ],
            [
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => $validated['content_encoding'] ?? 'aesgcm',
                'user_agent' => $request->userAgent(),
                'device_type' => $this->detectDeviceType($request->userAgent()),
                'active' => true,
                'last_used_at' => null,
            ],
        );

        Log::info('Souscription Web Push enregistrée', [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'device_type' => $subscription->device_type,
            'is_new' => $subscription->wasRecentlyCreated,
        ]);

        return response()->json([
            'message' => 'Souscription enregistrée avec succès.',
            'subscription_id' => $subscription->id,
        ], $subscription->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * DELETE /webpush/unsubscribe
     *
     * Désactive la souscription correspondant à l'endpoint passé.
     * On désactive plutôt qu'on supprime — préserve l'historique pour audit
     * et permet de réactiver si l'utilisateur change d'avis.
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'url', 'max:500'],
        ]);

        $user = $request->user();

        $deactivated = PushSubscription::query()
            ->where('user_id', $user->id)
            ->where('endpoint', $validated['endpoint'])
            ->update(['active' => false]);

        Log::info('Souscription Web Push désactivée', [
            'user_id' => $user->id,
            'rows_affected' => $deactivated,
        ]);

        return response()->json([
            'message' => 'Souscription désactivée.',
            'deactivated' => $deactivated > 0,
        ]);
    }

    /**
     * GET /webpush/subscriptions
     *
     * Liste les souscriptions actives de l'utilisateur — utile pour une UI
     * « mes appareils inscrits aux notifications push ».
     */
    public function index(Request $request): JsonResponse
    {
        $subscriptions = $request->user()
            ->pushSubscriptions()
            ->active()
            ->orderByDesc('last_used_at')
            ->get(['id', 'device_type', 'user_agent', 'last_used_at', 'created_at']);

        return response()->json([
            'data' => $subscriptions,
        ]);
    }

    /**
     * Détection minimaliste du type d'appareil depuis le User-Agent.
     * Pas de bibliothèque tierce — on couvre les 4 grands cas suffisants
     * pour une UI « X appareils inscrits ». Un parser complet (jenssegers/agent)
     * pourrait être ajouté plus tard si besoin.
     */
    private function detectDeviceType(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'unknown';
        }

        $ua = strtolower($userAgent);

        return match (true) {
            str_contains($ua, 'mobi') => 'mobile',
            str_contains($ua, 'tablet') || str_contains($ua, 'ipad') => 'tablet',
            str_contains($ua, 'mac') || str_contains($ua, 'windows') || str_contains($ua, 'linux') => 'desktop',
            default => 'unknown',
        };
    }
}
