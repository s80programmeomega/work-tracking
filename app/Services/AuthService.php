<?php

namespace App\Services;

use App\Models\User;
use App\Enums\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthService
{
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);

            // Assign default role
            $defaultRole = $data['role'] ?? Role::CADRE->value;
            $user->assignRole($defaultRole);

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->log('User registered');

            return $user;
        });
    }

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

        // Update last login
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        // Create API token
        $token = $user->createToken('auth_token', ['*'], now()->addDays(7))->plainTextToken;

        // Log activity
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties(['ip' => request()->ip()])
            ->log('User logged in');

        return [
            'user' => $user->load('roles', 'permissions'),
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => now()->addDays(7)->toISOString(),
        ];
    }

    public function logout(): void
    {
        $user = Auth::user();

        // Revoke current token
        if ($user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        // Log activity
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log('User logged out');

        Auth::guard('web')->logout();
    }

    public function refreshToken(): array
    {
        $user = Auth::user();

        // Revoke old token
        if ($user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        // Create new token
        $token = $user->createToken('auth_token', ['*'], now()->addDays(7))->plainTextToken;

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => now()->addDays(7)->toISOString(),
        ];
    }

    public function verifyEmail(User $user): void
    {
        $user->markEmailAsVerified();

        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log('Email verified');
    }
}
