<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\InitiatePaymentRequest;
use App\Models\Payment;
use App\Models\Plan;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Démarrage et suivi d'un paiement d'abonnement (Phase 9).
 *
 * NB : ces routes sont sous le préfixe `payment`, EXEMPTÉ de la garde
 * subscription.status — un workspace verrouillé/expiré doit pouvoir payer.
 */
class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    /**
     * POST /api/payment/initiate — le propriétaire lance un paiement pour un plan.
     */
    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $user = $request->user();
        $workspace = $user->currentWorkspace;

        if ($workspace === null) {
            return response()->json(['success' => false, 'message' => 'Aucun workspace courant.'], 404);
        }

        // Seul le propriétaire (ou un super_admin) peut payer pour le workspace.
        if (! $user->isSuperAdmin() && $workspace->owner_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Réservé au propriétaire du workspace.'], 403);
        }

        $plan = Plan::findOrFail($request->integer('plan_id'));

        if ($plan->is_free) {
            return response()->json(['success' => false, 'message' => 'Le plan gratuit ne nécessite pas de paiement.'], 422);
        }

        $payment = $this->payments->startCheckout(
            $workspace,
            $plan,
            $user,
            $request->string('provider')->toString(),
            $request->string('payer_phone')->toString(),
        );

        return response()->json([
            'success' => $payment->status !== Payment::STATUS_FAILED,
            'payment' => $this->present($payment),
            // Pour Orange : URL où rediriger l'utilisateur. Null pour MTN (push téléphone).
            'redirect_url' => $payment->payload['redirect_url'] ?? null,
        ], $payment->status === Payment::STATUS_FAILED ? 502 : 201);
    }

    /**
     * GET /api/payment/{reference}/status — état courant d'un paiement.
     * Le front interroge cette route pendant l'attente (flux push MTN).
     */
    public function status(Request $request, string $reference): JsonResponse
    {
        $payment = Payment::where('reference', $reference)->firstOrFail();
        $user = $request->user();

        // On ne révèle un paiement qu'à un membre du workspace concerné.
        $isMember = $payment->workspace
            && ($payment->workspace->owner_id === $user->id
                || $payment->workspace->members()->where('user_id', $user->id)->exists());

        if (! $isMember && ! $user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        }

        return response()->json(['success' => true, 'payment' => $this->present($payment)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Payment $payment): array
    {
        return [
            'reference' => $payment->reference,
            'provider' => $payment->provider,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'status' => $payment->status,
            'paid_at' => $payment->paid_at?->toISOString(),
        ];
    }
}
