<?php

declare(strict_types=1);

namespace App\Services\Payment;

/**
 * Résultat normalisé renvoyé par un fournisseur de paiement.
 *
 * Chaque fournisseur traduit sa réponse propriétaire vers cet objet commun,
 * pour que le reste de l'application n'ait jamais à connaître les détails
 * MTN/Orange. `status` utilise toujours les constantes Payment::STATUS_*.
 */
final class PaymentResult
{
    /**
     * @param  string  $status  Une constante Payment::STATUS_* (pending|succeeded|failed|expired)
     * @param  string|null  $redirectUrl  URL où rediriger l'utilisateur (Orange) ; null si paiement par push (MTN)
     * @param  string|null  $providerReference  Identifiant de transaction côté fournisseur
     * @param  string|null  $providerToken  Jeton fournisseur (pay_token Orange, etc.)
     * @param  array<string, mixed>  $raw  Charge utile brute, expurgée des secrets, pour le débogage
     */
    public function __construct(
        public string $status,
        public ?string $redirectUrl = null,
        public ?string $providerReference = null,
        public ?string $providerToken = null,
        public array $raw = [],
    ) {}
}
