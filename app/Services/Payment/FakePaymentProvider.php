<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Payment;

/**
 * Fournisseur FACTICE — uniquement pour le développement/démonstration locale.
 *
 * Permet de parcourir le flux de paiement dans le navigateur SANS identifiants
 * MTN/Orange ni réseau : l'initiation renvoie « pending », puis fetchStatus
 * renvoie « succeeded » (le front, en sondant, voit donc le paiement aboutir).
 *
 * SÉCURITÉ : ne doit JAMAIS être actif en production. Le registre refuse de le
 * fournir hors environnement local/test (voir PaymentProviderRegistry).
 */
class FakePaymentProvider implements PaymentProviderInterface
{
    public function key(): string
    {
        // Réutilise la clé demandée par l'appelant ; sans importance en mode factice.
        return Payment::PROVIDER_MTN;
    }

    public function initiate(Payment $payment, string $payerPhone): PaymentResult
    {
        // Toujours « en attente » : le front sondera puis verra le succès.
        return new PaymentResult(
            status: Payment::STATUS_PENDING,
            providerReference: 'FAKE-'.$payment->reference,
            raw: ['fake' => true],
        );
    }

    public function fetchStatus(Payment $payment): PaymentResult
    {
        // Succès systématique en mode factice.
        return new PaymentResult(
            status: Payment::STATUS_SUCCEEDED,
            providerReference: 'FAKE-'.$payment->reference,
            raw: ['fake' => true, 'status' => 'SUCCESS'],
        );
    }
}
