<?php

namespace App\Services;

use App\Mail\TwoFactorEmailCodeMail;
use App\Models\TwoFactorEmailCode;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class MfaService
{
    public function __construct(private TwoFactorAuthenticationProvider $totp) {}

    /** Durée de validité du code email-OTP (minutes) */
    private const EMAIL_OTP_TTL_MINUTES = 10;

    /** Durée de validité du jeton de challenge (minutes) */
    private const CHALLENGE_TOKEN_TTL_MINUTES = 5;

    /** Nombre max de tentatives sur un code email-OTP */
    private const MAX_ATTEMPTS = 5;

    /**
     * Vérifie si l'utilisateur a au moins un facteur 2FA actif.
     */
    public function hasFactor(User $user): bool
    {
        return $user->two_factor_confirmed_at !== null
            || $user->email_otp_enabled;
    }

    /**
     * Génère un jeton de challenge temporaire (5 min) et le stocke en cache.
     * Retourné au SPA à la place du token Sanctum lors d'un login avec 2FA actif.
     * Le flag $remember est conservé dans le cache pour être transmis à issueToken().
     */
    public function createChallengeToken(User $user, bool $remember = false): string
    {
        $token = Str::random(64);

        Cache::put(
            $this->challengeKey($token),
            ['user_id' => $user->id, 'remember' => $remember, 'created_at' => now()->timestamp],
            now()->addMinutes(self::CHALLENGE_TOKEN_TTL_MINUTES)
        );

        return $token;
    }

    /**
     * Résout le jeton de challenge et retourne l'utilisateur correspondant,
     * ou null si le jeton est invalide/expiré.
     */
    public function resolveChallenge(string $token): ?User
    {
        $data = Cache::get($this->challengeKey($token));

        if (! $data) {
            return null;
        }

        return User::find($data['user_id']);
    }

    /**
     * Retourne le flag "remember" stocké dans le challenge, ou false par défaut.
     */
    public function challengeRemember(string $token): bool
    {
        $data = Cache::get($this->challengeKey($token));

        return (bool) ($data['remember'] ?? false);
    }

    /**
     * Invalide un jeton de challenge après utilisation réussie.
     */
    public function consumeChallenge(string $token): void
    {
        Cache::forget($this->challengeKey($token));
    }

    /**
     * Vérifie un code TOTP ou un code de récupération pour l'utilisateur donné.
     */
    public function verifyTotp(User $user, string $code): bool
    {
        // Tentative code TOTP
        if ($user->two_factor_confirmed_at && $this->totp->verify(decrypt($user->two_factor_secret), $code)) {
            return true;
        }

        // Tentative code de récupération
        $valid = collect($user->recoveryCodes())
            ->contains(fn (string $r) => hash_equals(trim($r), trim($code)));

        if ($valid) {
            // Consomme le code de récupération utilisé
            $remaining = collect($user->recoveryCodes())
                ->reject(fn (string $r) => hash_equals(trim($r), trim($code)))
                ->values()
                ->all();

            $user->forceFill([
                'two_factor_recovery_codes' => encrypt(json_encode($remaining)),
            ])->save();

            Log::info('Code de récupération 2FA utilisé', ['user_id' => $user->id]);
        }

        return $valid;
    }

    /**
     * Envoie un code OTP par email à l'utilisateur.
     * Invalide tout code existant non expiré avant d'en créer un nouveau.
     * Retourne false si un cooldown de 60 secondes n'est pas encore écoulé.
     */
    public function sendEmailOtp(User $user): bool
    {
        // Cooldown 60 secondes pour éviter l'abus
        if (Cache::has($this->cooldownKey($user))) {
            return false;
        }

        // Invalide les anciens codes actifs
        TwoFactorEmailCode::where('user_id', $user->id)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->update(['used' => true]);

        // Génère un code à 6 chiffres
        $plainCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        TwoFactorEmailCode::create([
            'user_id' => $user->id,
            'code' => Hash::make($plainCode),
            'expires_at' => now()->addMinutes(self::EMAIL_OTP_TTL_MINUTES),
            'created_at' => now(),
        ]);

        Mail::to($user->email)->queue(new TwoFactorEmailCodeMail($user, $plainCode));

        Cache::put($this->cooldownKey($user), true, now()->addSeconds(60));

        Log::info('Code OTP email envoyé', ['user_id' => $user->id]);

        return true;
    }

    /**
     * Vérifie le code OTP email soumis par l'utilisateur.
     * Retourne true si valide, false sinon.
     * Incrémente le compteur de tentatives et invalide le code si max atteint.
     */
    public function verifyEmailOtp(User $user, string $code): bool
    {
        $record = TwoFactorEmailCode::where('user_id', $user->id)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $record) {
            return false;
        }

        if ($record->isExhausted()) {
            $record->update(['used' => true]);

            Log::warning('Code OTP email épuisé — max tentatives atteint', ['user_id' => $user->id]);

            return false;
        }

        // Comparaison en temps constant pour éviter les timing attacks
        if (! Hash::check($code, $record->code)) {
            $record->increment('attempts');

            return false;
        }

        $record->update(['used' => true]);

        return true;
    }

    // ── Clés cache internes ────────────────────────────────────────────────

    private function challengeKey(string $token): string
    {
        return "mfa:challenge:{$token}";
    }

    private function cooldownKey(User $user): string
    {
        return "mfa:email_otp_cooldown:{$user->id}";
    }
}
