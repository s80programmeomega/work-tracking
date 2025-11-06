<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use App\Enums\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthService
{
    /**
     * Enregistre un nouvel utilisateur et crée son workspace par défaut
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // 🔹 Construire le nom complet à partir de firstname et lastname
            $nom = trim(
                ($data['prenom'] ?? '') . ' ' . ($data['nom'] ?? '')
            );
            // 1. Création de l'utilisateur
            $user = User::create([
                'nom_complet' => $nom,
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);

            // ✅ 2. Rôle par défaut
            $defaultRole = $data['role'] ?? Role::ADMIN->value;
            $user->assignRole($defaultRole);

            // ✅ 3. Création automatique du workspace personnel
            $workspace = Workspace::create([
                'nom' => "{$user->nom} Workspace",
                'description' => 'Espace de travail personnel de ' . $user->nom,
                'owner_id' => $user->id,
                'is_active' => true,
            ]);

            // ✅ 4. Ajout dans la table pivot (membre propriétaire)
            $workspace->members()->attach($user->id, [
                'role' => 'owner',
                'permissions' => json_encode(['all']),
                'invited_at' => now(),
                'invited_by' => $user->id,
            ]);

            // ✅ 5. Définir ce workspace comme courant
            $user->update(['current_workspace_id' => $workspace->id]);

            // ✅ 6. Log d'activité
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties(['workspace_id' => $workspace->id])
                ->log('User registered and workspace created');

            return $user;
        });
    }

    /**
     * Authentifie l'utilisateur et gère le workspace courant
     */
    public function login(array $credentials, bool $remember = false): array
    {
        if (!Auth::attempt($credentials, $remember)) {
            throw new \Exception('Invalid credentials');
        }

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            throw new \Exception('Account is inactive');
        }

        // ✅ Mettre à jour les infos de connexion
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        // ✅ Vérifier si un workspace courant existe
        if (!$user->current_workspace_id) {
            // Si l'utilisateur est déjà membre d'un workspace
            $workspace = $user->workspaces()->first();

            if (!$workspace) {
                // Sinon, créer un workspace personnel
                $workspace = Workspace::create([
                    'nom' => "{$user->name} Workspace",
                    'description' => 'Espace de travail personnel de ' . $user->name,
                    'owner_id' => $user->id,
                    'is_active' => true,
                ]);

                // Lier comme membre propriétaire
                $workspace->members()->attach($user->id, [
                    'role' => 'owner',
                    'permissions' => json_encode(['all']),
                    'invited_at' => now(),
                    'invited_by' => $user->id,
                ]);
            }

            // Mettre à jour le workspace courant
            $user->update(['current_workspace_id' => $workspace->id]);
        }

        // ✅ Générer le token Sanctum
        // $token = $user->createToken('auth_token', ['*'], now()->addDays(7))->plainTextToken;
        $token = $user->createToken('auth_token', ['*'])->plainTextToken;

        // ✅ Log d'activité
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'workspace_id' => $user->current_workspace_id,
                'ip' => request()->ip(),
            ])->log('User logged in');

        return [
            'user' => $user->load('roles', 'permissions', 'currentWorkspace'),
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
