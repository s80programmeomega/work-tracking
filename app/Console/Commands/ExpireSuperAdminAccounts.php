<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;

class ExpireSuperAdminAccounts extends Command
{
    protected $signature = 'admin:expire-accounts {--dry-run : Show what would happen without making changes}';

    protected $description = 'Suspend or delete superadmin accounts whose admin_expires_at is in the past';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $expired = User::where('is_super_admin', true)
            ->whereNull('is_system_owner')
            ->whereNotNull('admin_expires_at')
            ->where('admin_expires_at', '<=', now())
            ->get();

        if ($expired->isEmpty()) {
            $this->info('Aucun compte superadmin expiré.');

            return self::SUCCESS;
        }

        foreach ($expired as $user) {
            $action = $user->admin_expiry_action;

            if ($dryRun) {
                $this->line("[dry-run] {$user->email} → {$action}");

                continue;
            }

            // Révoquer les accès temporaires : grants et memberships provisionnées.
            $user->getConnection()->table('temporary_access')
                ->where('user_id', $user->id)
                ->delete();
            $user->getConnection()->table('workspace_members')
                ->where('user_id', $user->id)
                ->where('is_temp_access', true)
                ->delete();

            // Invalider toutes les sessions actives immédiatement.
            $user->tokens()->delete();

            // Désactiver dans tous les cas avant l'action finale.
            $user->update(['is_active' => false, 'is_super_admin' => false]);
            $user->syncRoles([]);

            if ($action === 'delete') {
                Log::info('Compte superadmin expiré supprimé', ['user_id' => $user->id, 'email' => $user->email]);
                $user->forceDelete();
            } else {
                Log::info('Compte superadmin expiré suspendu', ['user_id' => $user->id, 'email' => $user->email]);
                $this->line("Suspendu : {$user->email}");
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        if (! $dryRun) {
            $this->info($expired->count().' compte(s) traité(s).');
        }

        return self::SUCCESS;
    }
}
