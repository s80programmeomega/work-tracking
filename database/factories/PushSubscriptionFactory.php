<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PushSubscription>
 *
 * Task 8b — produit des souscriptions Web Push de forme réaliste:
 *   - endpoint au format Chrome (FCM) par défaut, état "active"
 *   - states Firefox / Edge pour couvrir le multi-appareils
 *   - state inactive() pour reproduire les souscriptions soft-disabled
 *     (410 Gone / désabonnement utilisateur)
 *
 * Les "clés" produites ressemblent à du base64url mais ne sont pas
 * valides cryptographiquement: suffisant pour les tests qui ne
 * tapent pas sur un vrai push service.
 */
class PushSubscriptionFactory extends Factory
{
    protected $model = PushSubscription::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/'.Str::random(40),
            'public_key' => $this->fakeBase64(88),
            'auth_token' => $this->fakeBase64(24),
            'content_encoding' => 'aesgcm',
            'user_agent' => fake()->userAgent(),
            'device_type' => fake()->randomElement(['desktop-chrome', 'mobile-android', 'desktop-firefox']),
            'active' => true,
            'last_used_at' => null,
        ];
    }

    /** Souscription désactivée (410 Gone ou opt-out utilisateur). */
    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }

    /** Souscription Firefox (endpoint Mozilla). */
    public function firefox(): static
    {
        return $this->state([
            'endpoint' => 'https://updates.push.services.mozilla.com/wpush/v2/'.Str::random(40),
            'device_type' => 'desktop-firefox',
        ]);
    }

    /** Souscription Edge (endpoint Microsoft). */
    public function edge(): static
    {
        return $this->state([
            'endpoint' => 'https://wns2-par02p.notify.windows.com/w/?token='.Str::random(40),
            'device_type' => 'desktop-edge',
        ]);
    }

    /** Souscription qui a déjà servi (utile pour tester last_used_at). */
    public function recentlyUsed(): static
    {
        return $this->state(['last_used_at' => now()->subMinutes(fake()->numberBetween(1, 60))]);
    }

    /** Souscription rattachée à un utilisateur précis. */
    public function forUser(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }

    /**
     * Fabrique une chaîne ressemblant à du base64url de la longueur demandée.
     * Pas valide cryptographiquement — uniquement pour les fixtures de test.
     */
    private function fakeBase64(int $length): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';

        return substr(str_repeat($alphabet, (int) ceil($length / strlen($alphabet))), 0, $length);
    }
}
