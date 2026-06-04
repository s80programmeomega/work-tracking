<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

/**
 * Expose les actions Fortify 2FA via l'API REST (auth:sanctum).
 *
 * Fortify enregistre ses routes 2FA sous le middleware 'web' (session) qui
 * est incompatible avec l'authentification par token Sanctum de ce SPA.
 * Ce contrôleur appelle directement les classes d'action Fortify sans
 * passer par la confirmation de mot de passe basée sur la session.
 */
class TwoFactorManagementController extends Controller
{
    /**
     * Active la 2FA TOTP pour l'utilisateur connecté.
     * Retourne le QR code SVG et la clé secrète pour la saisie manuelle.
     */
    public function enable(Request $request): JsonResponse
    {
        $user = $request->user();

        app(EnableTwoFactorAuthentication::class)($user);

        // Recharge le modèle pour récupérer les colonnes fraîchement remplies
        $user->refresh();

        Log::info('2FA TOTP activé', ['user_id' => $user->id]);

        return response()->json([
            'svg' => $user->twoFactorQrCodeSvg(),
            'secretKey' => decrypt($user->two_factor_secret),
        ]);
    }

    /**
     * Confirme la 2FA avec un code TOTP valide.
     * Après confirmation, le facteur est considéré comme actif.
     */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        try {
            app(ConfirmTwoFactorAuthentication::class)($request->user(), $request->code);
        } catch (ValidationException) {
            return response()->json(['message' => __('auth.mfa.invalid_code')], 422);
        }

        Log::info('2FA TOTP confirmé', ['user_id' => $request->user()->id]);

        return response()->json(['message' => 'Two-factor authentication confirmed.', 'confirmed' => true]);
    }

    /**
     * Désactive la 2FA pour l'utilisateur connecté.
     * Efface le secret, les codes de récupération et la confirmation.
     * Désactive aussi l'OTP email car il requiert le TOTP actif.
     */
    public function disable(Request $request): JsonResponse
    {
        $user = $request->user();

        app(DisableTwoFactorAuthentication::class)($user);

        // L'OTP email ne peut pas exister sans TOTP — désactivation automatique
        if ($user->email_otp_enabled) {
            $user->update(['email_otp_enabled' => false]);
        }

        Log::info('2FA TOTP désactivé', ['user_id' => $user->id]);

        return response()->json(['message' => 'Two-factor authentication disabled.']);
    }

    /**
     * Retourne le QR code SVG de l'utilisateur (doit avoir activé la 2FA).
     */
    public function qrCode(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->two_factor_secret) {
            return response()->json(['message' => '2FA non activée.'], 422);
        }

        return response()->json(['svg' => $user->twoFactorQrCodeSvg()]);
    }

    /**
     * Retourne la clé secrète TOTP déchiffrée (pour saisie manuelle dans l'appli).
     */
    public function secretKey(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->two_factor_secret) {
            return response()->json(['message' => '2FA non activée.'], 422);
        }

        return response()->json(['secretKey' => decrypt($user->two_factor_secret)]);
    }

    /**
     * Retourne les codes de récupération actuels de l'utilisateur.
     */
    public function recoveryCodes(Request $request): JsonResponse
    {
        return response()->json($request->user()->recoveryCodes());
    }

    /**
     * Génère de nouveaux codes de récupération (invalide les anciens).
     */
    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        $user = $request->user();

        app(GenerateNewRecoveryCodes::class)($user);

        Log::info('Codes de récupération 2FA régénérés', ['user_id' => $user->id]);

        return response()->json($user->recoveryCodes());
    }
}
