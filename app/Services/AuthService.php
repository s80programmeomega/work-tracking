<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    /**
     * Register a new user without creating a workspace.
     * The user is assigned the default 'utilisateur' role.
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $nom = trim(($data['prenom'] ?? '').' '.($data['nom'] ?? ''));

            $user = User::create([
                'nom_complet' => $nom,
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);

            $user->assignRole(Role::UTILISATEUR->value);

            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->log('User registered');

            return $user;
        });
    }

    /**
     * Authentifie l'utilisateur et gère le workspace courant
     */
    public function login(array $credentials, bool $remember = false): array
    {
        if (! Auth::attempt($credentials, $remember)) {
            throw new \Exception('Invalid credentials');
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            throw new \Exception(__('auth.account_inactive'));
        }

        // Update last login info
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        // Garantit un workspace courant si l'utilisateur en possède/membre d'un
        // (évite de piéger un collaborateur invité sur /workspaces/create).
        $user->ensureCurrentWorkspace();

        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'workspace_id' => $user->current_workspace_id,
                'ip' => request()->ip(),
            ])->log('Connexion réussie');

        return [
            'user' => $user->load('roles', 'permissions', 'currentWorkspace'),
            'requires_mfa' => false, // positionné à true par AuthController si un facteur est actif
        ];
    }

    /**
     * Emet un token Sanctum pour l'utilisateur.
     * Sans "Se souvenir" : expire dans 24 h. Avec : expire dans 15 jours.
     * Les sessions multiples sont autorisees — chaque appareil conserve son propre token.
     */
    public function issueToken(User $user, bool $remember = false): array
    {
        $expiresAt = $remember ? now()->addDays(15) : now()->addHours(24);
        $newToken = $user->createToken('auth_token', ['*'], $expiresAt);
        DB::table('personal_access_tokens')
            ->where('id', $newToken->accessToken->id)
            ->update(['user_agent' => request()->userAgent()]);

        return [
            'token' => $newToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toISOString(),
        ];
    }

    /**
     * Déconnexion
     */
    public function logout(): void
    {
        $user = Auth::user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log('User logged out');

        Auth::guard('web')->logout();
    }

    /**
     * Révoque toutes les sessions actives de l'utilisateur courant.
     */
    public function logoutAll(): void
    {
        $user = Auth::user();

        if ($user) {
            $user->tokens()->where('name', 'auth_token')->delete();

            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->log('Toutes les sessions révoquées');
        }

        Auth::guard('web')->logout();
    }

    /**
     * Rafraîchir le token d'accès.
     * Préserve la durée "Se souvenir de moi" (15 jours) si le token actuel l'était.
     */
    public function refreshToken(): array
    {
        $user = Auth::user();

        if (! $user) {
            throw new \RuntimeException('Unauthenticated.');
        }

        // Détecter si le token actuel était un token "Se souvenir de moi" (expiry > 24 h restantes)
        /** @var PersonalAccessToken $currentToken */
        $currentToken = $user->currentAccessToken();
        $remember = $currentToken->expires_at && $currentToken->expires_at->diffInHours(now()) > 24;

        $currentToken->delete();

        $expiresAt = $remember ? now()->addDays(15) : now()->addHours(24);
        $newToken = $user->createToken('auth_token', ['*'], $expiresAt);
        DB::table('personal_access_tokens')
            ->where('id', $newToken->accessToken->id)
            ->update(['user_agent' => request()->userAgent()]);

        return [
            'token' => $newToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toISOString(),
        ];
    }

    /**
     * Validation de l'adresse e-mail
     */
    public function verifyEmail(User $user): void
    {
        $user->markEmailAsVerified();

        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log('Email verified');
    }
}
