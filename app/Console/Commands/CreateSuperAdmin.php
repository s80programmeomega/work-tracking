<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CreateSuperAdmin extends Command
{
    protected $signature = 'admin:create-superadmin
                            {--email= : Email address of the superadmin account}
                            {--name= : Display name}
                            {--expires-in=30 : Days until account expires (0 = permanent)}
                            {--expiry-action=suspend : Action on expiry: suspend or delete}
                            {--system-owner : Create as the permanent system owner account (can only be done once)}';

    protected $description = 'Create a superadmin account (temporary by default, permanent with --expires-in=0, system owner with --system-owner)';

    public function handle(): int
    {
        $email = $this->option('email') ?? $this->ask('Email address');
        $name = $this->option('name') ?? $this->ask('Display name');
        $expiresIn = (int) $this->option('expires-in');
        $expiryAction = $this->option('expiry-action');
        $isSystemOwner = (bool) $this->option('system-owner');

        if (! in_array($expiryAction, ['suspend', 'delete'], true)) {
            $this->error('--expiry-action must be "suspend" or "delete".');

            return self::FAILURE;
        }

        if ($isSystemOwner) {
            return $this->createSystemOwner($email, $name);
        }

        return $this->createRegularSuperAdmin($email, $name, $expiresIn, $expiryAction);
    }

    private function createSystemOwner(string $email, string $name): int
    {
        if (User::whereNotNull('is_system_owner')->exists()) {
            $existing = User::whereNotNull('is_system_owner')->first();
            $this->error('Un compte system owner existe déjà : '.$existing->email);

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error('Un utilisateur avec cet email existe déjà.');

            return self::FAILURE;
        }

        $password = Str::random(16);

        $user = User::create([
            'nom' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        // is_system_owner is not fillable — set it directly
        $user->getConnection()->table('users')->where('id', $user->id)->update(['is_system_owner' => true]);

        $role = Role::where('name', 'super_admin')->first();
        if ($role) {
            $user->assignRole($role);
        }

        $this->info('✓ Compte system owner créé : '.$email);
        $this->line('  Mot de passe temporaire : <fg=yellow>'.$password.'</>');
        $this->warn('  Conservez ce mot de passe en lieu sûr. Il ne sera plus affiché.');

        return self::SUCCESS;
    }

    private function createRegularSuperAdmin(string $email, string $name, int $expiresIn, string $expiryAction): int
    {
        $existing = User::where('email', $email)->first();

        if ($existing) {
            if ($existing->isSuperAdmin()) {
                $this->info('Compte superadmin existant : '.$email.' — aucune modification.');

                return self::SUCCESS;
            }

            $this->error('Un utilisateur avec cet email existe déjà mais n\'est pas superadmin.');

            return self::FAILURE;
        }

        $password = Str::random(16);
        $expiresAt = $expiresIn > 0 ? now()->addDays($expiresIn) : null;

        $user = User::create([
            'nom' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_super_admin' => true,
            'admin_expires_at' => $expiresAt,
            'admin_expiry_action' => $expiryAction,
            'is_active' => true,
        ]);

        $role = Role::where('name', 'super_admin')->first();
        if ($role) {
            $user->assignRole($role);
        }

        $expiryLabel = $expiresAt
            ? 'Expire le '.$expiresAt->toDateString().' (action : '.$expiryAction.')'
            : 'Compte permanent (pas d\'expiration)';

        $this->info('✓ Compte superadmin créé : '.$email);
        $this->line('  Mot de passe temporaire : <fg=yellow>'.$password.'</>');
        $this->line('  '.$expiryLabel);
        $this->warn('  Conservez ce mot de passe en lieu sûr. Il ne sera plus affiché.');

        return self::SUCCESS;
    }
}
