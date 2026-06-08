<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Workspace;
use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garde d'abonnement GLOBALE — exécutée sur chaque requête API authentifiée
 * (Phases 8 & 9). Logique : essai/abonnement échu → rétrogradation au plan
 * gratuit (accès limité, géré ailleurs) ; seul le verrou dur ('locked')
 * bloque ici avec un HTTP 402 (Payment Required).
 *
 * Routes exemptées (toujours autorisées même verrouillé) : authentification,
 * abonnement (consulter/payer un plan), webhooks de paiement, déconnexion.
 * Le super_admin contourne toujours.
 */
class CheckSubscriptionStatus
{
    public function __construct(private SubscriptionService $subscription) {}

    /**
     * Préfixes de chemin (après /api/) toujours accessibles, sinon un
     * workspace verrouillé ne pourrait jamais payer ni se déconnecter.
     *
     * @var list<string>
     */
    private array $exemptPrefixes = [
        'auth',                 // login, logout, refresh, 2FA…
        'subscription',         // consulter les plans, sélectionner, activer
        'admin/subscription',   // gestion super_admin
        'payment',              // Phase 9 — initier un paiement
        'webhooks',             // Phase 9 — callbacks fournisseurs
        'user',                 // profil de base / déconnexion
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Non authentifié ou super_admin → laisser passer.
        if (! $user || $user->isSuperAdmin()) {
            return $next($request);
        }

        // Routes exemptées (paiement, auth…) → toujours accessibles.
        if ($this->isExempt($request)) {
            return $next($request);
        }

        $workspace = $request->route('workspace') instanceof Workspace
            ? $request->route('workspace')
            : $user->currentWorkspace;

        if (! $workspace) {
            return $next($request);
        }

        // Transition de cycle de vie paresseuse (essai/abonnement échu → lapsed).
        $this->subscription->reconcileStatus($workspace);

        // Verrou dur → accès payant requis.
        if ($this->subscription->isLocked($workspace)) {
            return response()->json([
                'success' => false,
                'message' => __('subscription.errors.locked'),
                'subscription_status' => 'locked',
            ], Response::HTTP_PAYMENT_REQUIRED); // 402
        }

        return $next($request);
    }

    private function isExempt(Request $request): bool
    {
        // Chemin sans le préfixe applicatif éventuel.
        $path = ltrim($request->path(), '/');           // ex. "api/subscription/plans"
        $path = preg_replace('#^api/#', '', $path) ?? $path;

        foreach ($this->exemptPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }
}
