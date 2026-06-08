<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Payment;

/**
 * Contrat commun à tous les fournisseurs de paiement (MTN MoMo, Orange Money…).
 *
 * L'application ne dialogue qu'avec cette interface : ajouter un fournisseur
 * revient à écrire une nouvelle classe l'implémentant, sans rien changer
 * ailleurs (patron Strategy).
 */
interface PaymentProviderInterface
{
    /** Clé du fournisseur (constante Payment::PROVIDER_*). */
    public function key(): string;

    /**
     * Démarre une collecte auprès du fournisseur.
     *
     * @param  Payment  $payment  Notre transaction (déjà en base, statut pending)
     * @param  string  $payerPhone  Numéro du payeur (MSISDN) — utilisé par MTN
     */
    public function initiate(Payment $payment, string $payerPhone): PaymentResult;

    /**
     * Interroge le fournisseur sur l'état réel d'un paiement (source de vérité).
     * Utilisé pour VÉRIFIER un callback avant de faire confiance à son contenu.
     */
    public function fetchStatus(Payment $payment): PaymentResult;
}
