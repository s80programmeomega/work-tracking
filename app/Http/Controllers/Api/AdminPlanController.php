<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\StorePlanRequest;
use App\Http\Requests\Plan\UpdatePlanRequest;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestion des plans d'abonnement (Phase 8) — réservé au super_admin.
 * Les plans sont une configuration plateforme globale (pas par workspace),
 * d'où le garde super_admin sur toutes les actions d'écriture.
 *
 * Garde-fous suppression : on n'autorise pas la suppression d'un plan
 * utilisé par au moins un workspace, ni du plan gratuit de repli (nécessaire
 * à la rétrogradation après expiration — cf. SubscriptionService::effectivePlan).
 */
class AdminPlanController extends Controller
{
    /** GET /api/admin/plans — tous les plans (actifs et inactifs). */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $plans = Plan::query()
            ->withCount('workspaces')
            ->orderBy('position')
            ->get();

        return response()->json(['success' => true, 'plans' => $plans]);
    }

    /** POST /api/admin/plans */
    public function store(StorePlanRequest $request): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $data = $request->validated();
        $data['currency'] = $data['currency'] ?? 'XAF';
        $data['billing_period'] = $data['billing_period'] ?? 'monthly';

        $plan = Plan::create($data);

        return response()->json(['success' => true, 'plan' => $plan], 201);
    }

    /** PUT /api/admin/plans/{plan} */
    public function update(UpdatePlanRequest $request, Plan $plan): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $plan->update($request->validated());

        return response()->json(['success' => true, 'plan' => $plan->fresh()]);
    }

    /** DELETE /api/admin/plans/{plan} */
    public function destroy(Request $request, Plan $plan): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        if ($plan->is_free) {
            return response()->json([
                'success' => false,
                'message' => __('subscription.plan_errors.cannot_delete_free'),
            ], 422);
        }

        if ($plan->workspaces()->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('subscription.plan_errors.cannot_delete_in_use'),
            ], 422);
        }

        $plan->delete();

        return response()->json(['success' => true]);
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        if (! $request->user()->isSuperAdmin()) {
            abort(403, 'Accès réservé au super-admin.');
        }
    }
}
