<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Support\Facades\App;
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
        private FakePaymentProvider $fake,
    ) {}

    public function for(string $key): PaymentProviderInterface
    {
        // Mode factice (dev/démo) : JAMAIS en production, même si la config l'active.
        if ($this->fakeEnabled()) {
            return $this->fake;
        }

        return match ($key) {
            Payment::PROVIDER_MTN => $this->mtn,
            Payment::PROVIDER_ORANGE => $this->orange,
            default => throw new InvalidArgumentException("Fournisseur de paiement inconnu : {$key}"),
        };
    }

    /** Le fournisseur factice est-il activé ? (config + verrou anti-production) */
    public function fakeEnabled(): bool
    {
        return (bool) config('payment.fake', false) && ! App::environment('production');
    }

    /** @return list<string> */
    public function supportedKeys(): array
    {
        return [Payment::PROVIDER_MTN, Payment::PROVIDER_ORANGE];
    }
}
