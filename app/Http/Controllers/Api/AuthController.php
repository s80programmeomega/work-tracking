<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use App\Services\MfaService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private MfaService $mfaService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->register($request->validated());

            return response()->json([
                'message' => __('auth.registration_success'),
                'user' => new UserResource($user),
            ], 201);

        } catch (QueryException $e) {
            Log::error('Registration database error: '.$e->getMessage());

            return response()->json([
                'message' => __('auth.registration_failed'),
            ], 500);

        } catch (\Exception $e) {
            Log::error('Registration error: '.$e->getMessage());

            return response()->json([
                'message' => __('auth.registration_failed'),
            ], 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                $request->only('email', 'password'),
                $request->boolean('remember')
            );

            /** @var User $user */
            $user = $result['user'];

            // Si l'utilisateur a un facteur 2FA actif, émettre un challenge temporaire
            if ($this->mfaService->hasFactor($user)) {
                $challengeToken = $this->mfaService->createChallengeToken($user);

                return response()->json([
                    'two_factor' => true,
                    'challenge_token' => $challengeToken,
                    'email_otp_available' => $user->email_otp_enabled,
                ]);
            }

            // Pas de MFA — émettre le token Sanctum directement
            $tokenData = $this->authService->issueToken($user);

            return response()->json([
                'message' => 'Login successful',
                'data' => [
                    'user' => new UserResource($user),
                    'token' => $tokenData['token'],
                    'token_type' => $tokenData['token_type'],
                    'expires_at' => $tokenData['expires_at'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 401);
        }
    }

    public function logout(): JsonResponse
    {
        try {
            $this->authService->logout();

            return response()->json([
                'message' => 'Logout successful',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Logout failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function me(): JsonResponse
    {
        $user = auth()->user();
        // Backfill du workspace courant pour les sessions existantes (collaborateurs
        // invités, comptes créés avant ce correctif) afin de ne pas les piéger.
        $user->ensureCurrentWorkspace();

        return response()->json([
            'data' => new UserResource($user->load('roles', 'permissions')),
        ]);
    }

    public function refresh(): JsonResponse
    {
        try {
            $result = $this->authService->refreshToken();

            return response()->json([
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token refresh failed',
                'error' => $e->getMessage(),
            ], 401);
        }
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified',
            ], 400);
        }

        $this->authService->verifyEmail($user);

        return response()->json([
            'message' => 'Email verified successfully',
        ]);
    }

    /**
     * Vérifie le code TOTP ou email-OTP lors du challenge MFA post-login.
     * Retourne le token Sanctum définitif si le code est valide.
     */
    public function twoFactorChallenge(Request $request): JsonResponse
    {
        $request->validate([
            'challenge_token' => 'required|string',
            'code' => 'required|string',
            'type' => 'required|in:totp,recovery,email',
        ]);

        $user = $this->mfaService->resolveChallenge($request->challenge_token);

        if (! $user) {
            return response()->json(['message' => __('auth.mfa.challenge_expired')], 422);
        }

        $verified = match ($request->type) {
            'totp', 'recovery' => $this->mfaService->verifyTotp($user, $request->code),
            'email' => $this->mfaService->verifyEmailOtp($user, $request->code),
            default => false,
        };

        if (! $verified) {
            return response()->json(['message' => __('auth.mfa.invalid_code')], 422);
        }

        $this->mfaService->consumeChallenge($request->challenge_token);

        $result = $this->authService->issueToken($user);

        Log::info('Challenge MFA validé', ['user_id' => $user->id, 'type' => $request->type]);

        return response()->json([
            'message' => 'Login successful',
            'data' => [
                'user' => new UserResource($user->load('roles', 'permissions', 'currentWorkspace')),
                'token' => $result['token'],
                'token_type' => $result['token_type'],
                'expires_at' => $result['expires_at'],
            ],
        ]);
    }

    /**
     * Envoie un code OTP par email pour le challenge MFA.
     * Soumis avec le challenge_token provisoire.
     */
    public function twoFactorEmailSend(Request $request): JsonResponse
    {
        $request->validate(['challenge_token' => 'required|string']);

        $user = $this->mfaService->resolveChallenge($request->challenge_token);

        if (! $user) {
            return response()->json(['message' => __('auth.mfa.challenge_expired')], 422);
        }

        if (! $user->email_otp_enabled) {
            return response()->json(['message' => __('auth.mfa.email_otp_not_enabled')], 403);
        }

        $sent = $this->mfaService->sendEmailOtp($user);

        if (! $sent) {
            return response()->json(['message' => __('auth.mfa.email_otp_cooldown')], 429);
        }

        return response()->json(['message' => __('auth.mfa.email_otp_sent')]);
    }

    /**
     * Active ou désactive l'OTP email comme second facteur pour l'utilisateur connecté.
     */
    public function toggleEmailOtp(Request $request): JsonResponse
    {
        $request->validate(['enabled' => 'required|boolean']);

        $user = $request->user();

        // L'OTP email ne peut pas être le seul facteur : exige TOTP confirmé si on active seul
        if ($request->boolean('enabled') && ! $user->two_factor_confirmed_at) {
            return response()->json(['message' => __('auth.mfa.email_otp_requires_totp')], 422);
        }

        $user->update(['email_otp_enabled' => $request->boolean('enabled')]);

        return response()->json([
            'message' => $request->boolean('enabled')
                ? __('auth.mfa.email_otp_enabled')
                : __('auth.mfa.email_otp_disabled'),
            'email_otp_enabled' => $user->email_otp_enabled,
        ]);
    }

    /**
     * Met à jour la langue de l'utilisateur
     */
    public function updateLanguage(Request $request): JsonResponse
    {
        // Validation de la requête
        $request->validate([
            'language' => 'required|in:en,fr', // Seules les langues supportées
        ]);

        try {
            // Récupère l'utilisateur connecté
            $user = auth()->user();

            // Met à jour la langue
            $user->update(['language' => $request->language]);

            return response()->json([
                'message' => __('Language updated successfully'), // Utilise les traductions !
                'language' => $user->language,
            ]);

        } catch (\Exception $e) {
            // Log l'erreur pour le débogage
            Log::error('Language update error: '.$e->getMessage());

            return response()->json([
                'message' => __('Failed to update language'),
            ], 500);
        }
    }
}
