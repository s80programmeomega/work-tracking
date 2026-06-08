<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payment\PaymentProviderRegistry;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Réception des notifications fournisseurs (Phase 9).
 *
 * SÉCURITÉ — ces endpoints sont PUBLICS (appelés par MTN/Orange, sans session).
 * On ne fait JAMAIS confiance au corps du callback : à réception, on RE-INTERROGE
 * le fournisseur via son API (fetchStatus) avec nos propres identifiants, et on
 * n'agit que sur ce statut vérifié. Le callback n'est qu'un signal « va vérifier ».
 */
class PaymentWebhookController extends Controller
{
    public function __construct(
        private PaymentService $payments,
        private PaymentProviderRegistry $registry,
    ) {}

    /** POST /api/webhooks/payment/momo */
    public function momo(Request $request): JsonResponse
    {
        // MTN renvoie referenceId / externalId = notre reference.
        $reference = $request->input('referenceId')
            ?? $request->input('externalId')
            ?? $request->input('reference');

        return $this->handle(Payment::PROVIDER_MTN, is_string($reference) ? $reference : null);
    }

    /** POST /api/webhooks/payment/orange */
    public function orange(Request $request): JsonResponse
    {
        // Orange renvoie order_id / reference = notre reference.
        $reference = $request->input('order_id')
            ?? $request->input('reference');

        return $this->handle(Payment::PROVIDER_ORANGE, is_string($reference) ? $reference : null);
    }

    /**
     * Vérifie le paiement auprès du fournisseur, puis applique le résultat.
     * Renvoie toujours 200 si le paiement existe (les fournisseurs réessaient
     * sur non-2xx) ; on ne divulgue rien de sensible.
     */
    private function handle(string $providerKey, ?string $reference): JsonResponse
    {
        if ($reference === null) {
            return response()->json(['success' => false], 422);
        }

        $payment = Payment::where('reference', $reference)
            ->where('provider', $providerKey)
            ->first();

        if ($payment === null) {
            Log::warning('Webhook paiement : référence inconnue', ['provider' => $providerKey, 'reference' => $reference]);

            return response()->json(['success' => false], 404);
        }

        // Source de vérité : on redemande l'état au fournisseur (jamais le corps reçu).
        $verified = $this->registry->for($providerKey)->fetchStatus($payment);

        $this->payments->confirm($payment, $verified->status, $verified->raw);

        return response()->json(['success' => true]);
    }
}
