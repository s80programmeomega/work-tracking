<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Payment;
use InvalidArgumentException;

/**
 * Annuaire des fournisseurs : résout une clé ("mtn_momo"/"orange_money") vers
 * l'implémentation correspondante. Unique endroit où la correspondance vit.
 */
class PaymentProviderRegistry
{
    public function __construct(
        private MtnMomoProvider $mtn,
        private OrangeMoneyProvider $orange,
    ) {}

    public function for(string $key): PaymentProviderInterface
    {
        return match ($key) {
            Payment::PROVIDER_MTN => $this->mtn,
            Payment::PROVIDER_ORANGE => $this->orange,
            default => throw new InvalidArgumentException("Fournisseur de paiement inconnu : {$key}"),
        };
    }

    /** @return list<string> */
    public function supportedKeys(): array
    {
        return [Payment::PROVIDER_MTN, Payment::PROVIDER_ORANGE];
    }
}
