<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Orchestre le cycle de vie d'un paiement, indépendamment du fournisseur.
 *
 * - startCheckout() : crée la transaction (pending) et lance la collecte.
 * - confirm()       : applique le résultat fournisseur de façon IDEMPOTENTE
 *                     (un callback rejoué ne réactive pas deux fois).
 */
class PaymentService
{
    public function __construct(
        private PaymentProviderRegistry $registry,
        private SubscriptionService $subscription,
    ) {}

    /**
     * Démarre un paiement pour un plan donné.
     */
    public function startCheckout(Workspace $workspace, Plan $plan, User $user, string $providerKey, string $payerPhone): Payment
    {
        $provider = $this->registry->for($providerKey);

        $payment = Payment::create([
            'workspace_id' => $workspace->id,
            'plan_id' => $plan->id,
            'user_id' => $user->id,
            'provider' => $providerKey,
            'amount' => $plan->price,        // montant figé
            'currency' => $plan->currency,
            'status' => Payment::STATUS_PENDING,
        ]);

        $result = $provider->initiate($payment, $payerPhone);

        $payment->update([
            'status' => $result->status,
            'provider_reference' => $result->providerReference,
            'provider_token' => $result->providerToken,
            // On conserve l'URL de redirection (Orange) dans le payload pour le front.
            'payload' => array_merge($result->raw, array_filter(['redirect_url' => $result->redirectUrl])),
        ]);

        // Si le fournisseur confirme immédiatement (rare), on active tout de suite.
        if ($result->status === Payment::STATUS_SUCCEEDED) {
            $this->activate($payment->fresh());
        }

        return $payment->fresh();
    }

    /**
     * Applique un statut fournisseur à un paiement. IDEMPOTENT : si le paiement
     * n'est plus en attente, on ne fait rien (callback rejoué, double notif…).
     */
    public function confirm(Payment $payment, string $newStatus, array $rawPayload = []): Payment
    {
        if (! $payment->isPending()) {
            // Déjà traité → no-op (protège contre la double activation).
            return $payment;
        }

        if ($newStatus === Payment::STATUS_SUCCEEDED) {
            $payment->update([
                'status' => Payment::STATUS_SUCCEEDED,
                'paid_at' => now(),
                'payload' => $rawPayload ?: $payment->payload,
            ]);
            $this->activate($payment);
        } else {
            $payment->update([
                'status' => in_array($newStatus, [Payment::STATUS_FAILED, Payment::STATUS_EXPIRED], true)
                    ? $newStatus
                    : Payment::STATUS_PENDING,
                'payload' => $rawPayload ?: $payment->payload,
            ]);
        }

        return $payment->fresh();
    }

    /**
     * Active l'abonnement payant du workspace à partir d'un paiement réussi.
     * Délègue la transition d'état à SubscriptionService (source de vérité).
     */
    private function activate(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $workspace = $payment->workspace()->lockForUpdate()->first();
            $plan = $payment->plan;

            if ($workspace === null || $plan === null) {
                Log::warning('Activation paiement impossible : workspace/plan manquant', ['payment' => $payment->reference]);

                return;
            }

            $this->subscription->activateFromPayment($workspace, $plan);

            Log::info('Abonnement activé par paiement', [
                'payment' => $payment->reference,
                'workspace_id' => $workspace->id,
                'plan' => $plan->slug,
            ]);
        });
    }
}
