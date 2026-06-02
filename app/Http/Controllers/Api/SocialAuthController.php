<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
    ) {}

    /**
     * Redirige vers la page d'autorisation Google.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    /**
     * Traite le callback Google après autorisation de l'utilisateur.
     * Crée ou lie le compte, émet un token Sanctum, redirige le SPA.
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            Log::warning('Échec du callback Google OAuth', ['error' => $e->getMessage()]);

            return redirect(config('app.frontend_url', '').'/signin?error=social_auth_failed');
        }

        // Règle d'association : lier par email vérifié (jamais créer de doublon)
        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            // L'email existe déjà — lier le fournisseur si pas encore lié
            if (! $user->provider_id) {
                $user->update([
                    'provider' => 'google',
                    'provider_id' => $socialUser->getId(),
                ]);
                Log::info('Compte Google lié à un compte existant', ['user_id' => $user->id]);
            }
        } else {
            // Nouvel utilisateur via Google
            $nameParts = explode(' ', trim((string) ($socialUser->getName() ?? '')), 2);
            $user = User::create([
                'nom' => $nameParts[1] ?? $nameParts[0],
                'prenom' => count($nameParts) > 1 ? $nameParts[0] : null,
                'nom_complet' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'password' => Hash::make(Str::random(32)),
                'provider' => 'google',
                'provider_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
                'is_active' => true,
            ]);

            $user->assignRole('utilisateur');

            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->log('Inscription via Google OAuth');
        }

        if (! $user->is_active) {
            return redirect(config('app.frontend_url', '').'/signin?error=account_inactive');
        }

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties(['provider' => 'google', 'ip' => $request->ip()])
            ->log('Connexion via Google OAuth');

        $tokenData = $this->authService->issueToken($user);

        $frontendUrl = config('app.frontend_url', '');
        $params = http_build_query([
            'token' => $tokenData['token'],
            'expires_at' => $tokenData['expires_at'],
        ]);

        return redirect("{$frontendUrl}/auth/callback?{$params}");
    }
}
