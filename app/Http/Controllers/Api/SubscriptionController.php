<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Workspace;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Abonnements (Phase 8) — catalogue des plans, état courant, activation
 * manuelle (super_admin) et sélection de plan par le propriétaire.
 *
 * Le traitement réel des paiements (MTN MoMo / Orange Money) arrive en Phase 9 :
 * ici la « sélection » d'un plan payant enregistre l'intention (statut pending),
 * et seul le super_admin active manuellement un abonnement payant.
 */
class SubscriptionController extends Controller
{
    public function __construct(private SubscriptionService $subscription) {}

    /** GET /api/subscription/plans — catalogue public (utilisateur authentifié). */
    public function plans(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'plans' => Plan::active()->get(),
        ]);
    }

    /**
     * GET /api/subscription/current — état d'abonnement du workspace courant.
     */
    public function current(Request $request): JsonResponse
    {
        $workspace = $this->resolveWorkspace($request);
        if (! $workspace) {
            return response()->json(['success' => false, 'message' => 'Aucun workspace courant.'], 404);
        }

        $this->subscription->reconcileStatus($workspace);

        return response()->json([
            'success' => true,
            'subscription' => $this->subscription->summary($workspace->fresh()),
        ]);
    }

    /**
     * POST /api/subscription/select — le propriétaire choisit un plan.
     * Phase 8 : sans paiement réel, on enregistre l'intention (status pending).
     * Le plan gratuit est appliqué immédiatement ; un plan payant attend
     * l'activation (Phase 9 paiement, ou super_admin).
     */
    public function select(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
            'workspace_id' => 'sometimes|integer|exists:workspaces,id',
        ]);

        $workspace = $this->resolveWorkspace($request, $validated['workspace_id'] ?? null);
        if (! $workspace) {
            return response()->json(['success' => false, 'message' => 'Aucun workspace courant.'], 404);
        }

        $user = $request->user();
        if (! $user->isSuperAdmin() && $workspace->owner_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Réservé au propriétaire du workspace.'], 403);
        }

        $plan = Plan::findOrFail($validated['plan_id']);

        if ($plan->is_free) {
            // Plan gratuit : appliqué immédiatement.
            $workspace->forceFill([
                'plan_id' => $plan->id,
                'subscription_mode' => 'trial',
                'subscription_status' => 'free',
                'subscription_ends_at' => null,
            ])->save();

            $message = 'Plan gratuit activé.';
        } else {
            // Plan payant : intention enregistrée, en attente de paiement (Phase 9).
            $workspace->forceFill([
                'plan_id' => $plan->id,
                'subscription_status' => 'pending',
            ])->save();

            $message = 'Plan sélectionné. Le paiement activera votre abonnement.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'subscription' => $this->subscription->summary($workspace->fresh()),
        ]);
    }

    /**
     * POST /api/admin/subscription/{workspace}/activate — super_admin active
     * manuellement un abonnement payant (en attendant Phase 9).
     */
    public function activate(Request $request, Workspace $workspace): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
            'period_days' => 'sometimes|integer|min:1|max:366',
        ]);

        $periodDays = $validated['period_days'] ?? 30;

        $workspace->forceFill([
            'plan_id' => $validated['plan_id'],
            'subscription_mode' => 'paid',
            'subscription_status' => 'active',
            'subscription_ends_at' => now()->addDays($periodDays),
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Abonnement activé.',
            'subscription' => $this->subscription->summary($workspace->fresh()),
        ]);
    }

    /**
     * POST /api/admin/subscription/{workspace}/lock — super_admin pose le verrou
     * dur (HTTP 402 sur toutes les routes non exemptées).
     */
    public function lock(Request $request, Workspace $workspace): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $workspace->forceFill(['subscription_status' => 'locked'])->save();

        return response()->json([
            'success' => true,
            'message' => 'Workspace verrouillé.',
            'subscription' => $this->subscription->summary($workspace->fresh()),
        ]);
    }

    /**
     * POST /api/admin/subscription/{workspace}/unlock — lève le verrou dur
     * (retour au plan gratuit / lapsed).
     */
    public function unlock(Request $request, Workspace $workspace): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $workspace->forceFill(['subscription_status' => 'lapsed'])->save();

        return response()->json([
            'success' => true,
            'message' => 'Workspace déverrouillé.',
            'subscription' => $this->subscription->summary($workspace->fresh()),
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function resolveWorkspace(Request $request, ?int $workspaceId = null): ?Workspace
    {
        if ($workspaceId) {
            return Workspace::find($workspaceId);
        }

        $user = $request->user();

        return $request->route('workspace') instanceof Workspace
            ? $request->route('workspace')
            : $user->currentWorkspace;
    }

    private function ensureSuperAdmin(Request $request): void
    {
        if (! $request->user()->isSuperAdmin()) {
            abort(403, 'Accès réservé au super-admin.');
        }
    }
}
