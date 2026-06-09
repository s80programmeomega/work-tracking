<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
            throw new \Exception('Account is inactive');
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
     * Émet un token Sanctum 7 jours pour l'utilisateur donné.
     * Appelé après authentification réussie (sans MFA ou après challenge MFA validé).
     */
    public function issueToken(User $user): array
    {
        // Révocation des sessions existantes : une seule session active par utilisateur.
        // Empêche deux onglets du même navigateur de maintenir des sessions parallèles.
        $user->tokens()->where('name', 'auth_token')->delete();

        $token = $user->createToken('auth_token', ['*'], now()->addDays(7))->plainTextToken;

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => now()->addDays(7)->toISOString(),
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
     * Rafraîchir le token d’accès
     */
    public function refreshToken(): array
    {
        $user = Auth::user();

        if ($user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        $token = $user->createToken('auth_token', ['*'], now()->addDays(7))->plainTextToken;

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => now()->addDays(7)->toISOString(),
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
