<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Events\Realtime\SessionsAllRevoked;
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

            // Invalider toutes les sessions actives immédiatement, y compris en temps réel
            // (sans ça, une session déjà ouverte reste utilisable jusqu'au prochain rechargement).
            $user->tokens()->delete();
            broadcast(new SessionsAllRevoked($user));

            // Désactiver dans tous les cas avant l'action finale.
            // is_active=false bloque déjà la connexion : suffisant pour verrouiller l'accès
            // sans détruire les grants nécessaires à une réactivation (action 'suspend').
            $user->update(['is_active' => false, 'is_super_admin' => false]);
            $user->syncRoles([]);

            if ($action === 'delete') {
                // Suppression définitive : les grants n'ont plus de raison d'exister.
                $user->getConnection()->table('temporary_access')
                    ->where('user_id', $user->id)
                    ->delete();
                $user->getConnection()->table('workspace_members')
                    ->where('user_id', $user->id)
                    ->where('is_temp_access', true)
                    ->delete();

                Log::info('Compte superadmin expiré supprimé', ['user_id' => $user->id, 'email' => $user->email]);
                $user->forceDelete();
            } else {
                // Suspension : les grants temporary_access et workspace_members sont conservés
                // pour que reactivateTempAdmin() puisse restaurer l'accès workspace + droits.
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
