<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloque les requêtes qui dépasseraient une limite du plan d'essai.
 *
 * Usage dans les routes :
 *   ->middleware('subscription.limits:add_member')
 *   ->middleware('subscription.limits:upload_file')
 *
 * Le paramètre indique quelle limite vérifier. Si omis, vérifie uniquement
 * l'expiration de l'essai.
 */
class CheckSubscriptionLimits
{
    public function __construct(protected SubscriptionService $subscriptionService) {}

    public function handle(Request $request, Closure $next, string $limitType = 'trial'): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $workspace = $user->currentWorkspace;

        if (! $workspace) {
            return $next($request);
        }

        // Super admin contourne toujours les limites.
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Vérifie l'expiration de l'essai en premier (toujours).
        if ($this->subscriptionService->isTrialExpired($workspace)) {
            return response()->json([
                'success' => false,
                'message' => __('subscription.errors.trial_expired'),
                'subscription_status' => 'trial_expired',
            ], 403);
        }

        // Vérifie la limite spécifique selon le paramètre.
        $blocked = match ($limitType) {
            'add_member' => ! $this->subscriptionService->canAddMember($workspace),
            'upload_file' => ! $this->subscriptionService->canUploadFile(
                $workspace,
                (int) $request->header('Content-Length', 0)
            ),
            default => false,
        };

        if ($blocked) {
            return response()->json([
                'success' => false,
                'message' => __("subscription.errors.limit_reached.{$limitType}"),
                'subscription_status' => 'limit_reached',
                'limit_type' => $limitType,
            ], 403);
        }

        return $next($request);
    }
}
