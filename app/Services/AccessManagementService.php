<?php

namespace App\Services;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service de gestion centralisée des accès et permissions
 *
 * Ce service gère:
 * - Transfert de responsabilités lors du départ d'un membre
 * - Accès temporaires pour consultants
 * - Nettoyage des accès expirés
 * - Vérifications de permissions
 */
class AccessManagementService
{
    /**
     * Retire un membre du workspace et transfère ses responsabilités
     *
     * @param  User|null  $newResponsable  Nouveau responsable (owner du workspace par défaut)
     * @return array Statistiques du transfert
     */
    public function removeMemberWithTransfer(
        Workspace $workspace,
        User $userToRemove,
        ?User $newResponsable = null
    ): array {
        return DB::transaction(function () use ($workspace, $userToRemove, $newResponsable) {
            $stats = [
                'projects_transferred' => 0,
                'activities_transferred' => 0,
                'tasks_reassigned' => 0,
                'project_memberships_removed' => 0,
            ];

            // Définir le nouveau responsable (owner par défaut)
            $newResponsable = $newResponsable ?? $workspace->owner;

            // =====================================================
            // 1. PROJETS DONT IL EST RESPONSABLE
            // =====================================================
            $projets = $workspace->projets()
                ->where('responsable_id', $userToRemove->id)
                ->get();

            foreach ($projets as $projet) {
                // Transférer la responsabilité
                $projet->update([
                    'responsable_id' => $newResponsable->id,
                    // created_by reste inchangé pour la traçabilité ✅
                ]);

                if (! $projet->members()->where('user_id', $newResponsable->id)->exists()) {
                    $projet->members()->attach($newResponsable->id, [
                        'role' => 'manager',
                        'can_edit' => true,
                        'can_delete' => true,
                        'can_invite' => true,
                    ]);
                }

                $stats['projects_transferred']++;

                // =====================================================
                // 2. ACTIVITÉS DONT IL EST RESPONSABLE
                // =====================================================
                foreach ($projet->activites as $activite) {
                    if ($activite->responsable_id === $userToRemove->id) {
                        $activite->update([
                            'responsable_id' => $newResponsable->id,
                        ]);

                        // Ajouter le nouveau responsable comme membre de l'activité
                        if (! $activite->members()->where('user_id', $newResponsable->id)->exists()) {
                            $activite->members()->attach($newResponsable->id, [
                                'role' => 'responsable',
                                'can_create_tasks' => true,
                                'can_edit_tasks' => true,
                                'can_delete_tasks' => true,
                                'can_validate_results' => true,
                                'can_assign_users' => true,
                            ]);
                        }

                        $stats['activities_transferred']++;
                    }

                    // Retirer de la liste des membres de l'activité
                    $activite->members()->detach($userToRemove->id);

                    // =====================================================
                    // 3. TÂCHES ASSIGNÉES
                    // =====================================================
                    foreach ($activite->taches as $tache) {
                        if ($tache->assignees()->where('user_id', $userToRemove->id)->exists()) {
                            $tache->assignees()->detach($userToRemove->id);
                            $stats['tasks_reassigned']++;

                            // Optionnel : Réassigner au nouveau responsable
                            // $tache->assignees()->attach($newResponsable->id, [
                            //     'role' => 'assignee',
                            //     'can_edit' => true,
                            //     'can_complete' => true,
                            // ]);
                        }
                    }
                }

                // Retirer de la liste des membres du projet
                $projet->members()->detach($userToRemove->id);
                $stats['project_memberships_removed']++;
            }

            // =====================================================
            // 4. PROJETS OÙ IL EST SIMPLEMENT MEMBRE
            // =====================================================
            foreach ($workspace->projets as $projet) {
                if ($projet->members()->where('user_id', $userToRemove->id)->exists()) {
                    $projet->members()->detach($userToRemove->id);
                    $stats['project_memberships_removed']++;
                }
            }

            // =====================================================
            // 5. RETIRER DU WORKSPACE
            // =====================================================
            $workspace->members()->detach($userToRemove->id);

            // =====================================================
            // 6. RÉVOQUER LES ACCÈS TEMPORAIRES
            // =====================================================
            DB::table('temporary_access')
                ->where('user_id', $userToRemove->id)
                ->whereIn('accessible_type', [Projet::class, Activite::class, Tache::class])
                ->whereIn('accessible_id', function ($query) use ($workspace) {
                    $query->select('id')
                        ->from('projets')
                        ->where('workspace_id', $workspace->id);
                })
                ->delete();

            // =====================================================
            // 7. LOG DE L'ACTION
            // =====================================================
            activity()
                ->causedBy(auth()->user())
                ->performedOn($workspace)
                ->withProperties([
                    'removed_user_id' => $userToRemove->id,
                    'removed_user_name' => $userToRemove->nom,
                    'new_responsable_id' => $newResponsable->id,
                    'new_responsable_name' => $newResponsable->nom,
                    'stats' => $stats,
                ])
                ->log('member_removed_with_transfer');

            Log::info('Member removed from workspace with transfer', [
                'workspace_id' => $workspace->id,
                'user_removed' => $userToRemove->id,
                'new_responsable' => $newResponsable->id,
                'stats' => $stats,
            ]);

            return $stats;
        });
    }

    /**
     * Accorde un accès temporaire à une ressource
     *
     * @param  User  $user  Utilisateur recevant l'accès
     * @param  mixed  $accessible  Projet, Activite ou Tache
     * @param  string  $role  Rôle accordé
     * @param  Carbon  $expiresAt  Date d'expiration
     * @param  string|null  $reason  Justification
     * @param  array  $permissions  Permissions spécifiques
     */
    public function grantTemporaryAccess(
        User $user,
        $accessible,
        string $role,
        Carbon $expiresAt,
        ?string $reason = null,
        array $permissions = []
    ): void {
        // Vérifier que l'objet est valide
        $validTypes = [Projet::class, Activite::class, Tache::class];
        $accessibleType = get_class($accessible);

        if (! in_array($accessibleType, $validTypes)) {
            throw new \InvalidArgumentException("Type d'accès non supporté: {$accessibleType}");
        }

        // Créer l'accès temporaire
        DB::table('temporary_access')->insert([
            'user_id' => $user->id,
            'accessible_type' => $accessibleType,
            'accessible_id' => $accessible->id,
            'role' => $role,
            'permissions' => json_encode($permissions),
            'reason' => $reason,
            'expires_at' => $expiresAt,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($accessible)
            ->withProperties([
                'user_id' => $user->id,
                'user_name' => $user->nom,
                'role' => $role,
                'expires_at' => $expiresAt->toDateTimeString(),
                'reason' => $reason,
            ])
            ->log('temporary_access_granted');

        // Notification (à implémenter)
        // $user->notify(new TemporaryAccessGrantedNotification($accessible, $expiresAt, $reason));
    }

    /**
     * Révoque un accès temporaire
     */
    public function revokeTemporaryAccess(int $temporaryAccessId): bool
    {
        $access = DB::table('temporary_access')->find($temporaryAccessId);

        if (! $access) {
            return false;
        }

        // Log avant suppression
        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'user_id' => $access->user_id,
                'accessible' => "{$access->accessible_type}#{$access->accessible_id}",
                'reason' => 'Révoqué manuellement',
            ])
            ->log('temporary_access_revoked');

        return DB::table('temporary_access')
            ->where('id', $temporaryAccessId)
            ->delete() > 0;
    }

    /**
     * Nettoie les accès temporaires expirés
     *
     * @return int Nombre d'accès supprimés
     */
    public function cleanExpiredAccess(): int
    {
        $expired = DB::table('temporary_access')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $access) {
            // Log avant suppression
            activity()
                ->causedBy(User::find($access->created_by))
                ->withProperties([
                    'user_id' => $access->user_id,
                    'accessible' => "{$access->accessible_type}#{$access->accessible_id}",
                    'expired_at' => $access->expires_at,
                ])
                ->log('temporary_access_expired');
        }

        $count = DB::table('temporary_access')
            ->where('expires_at', '<', now())
            ->delete();

        Log::info('Cleaned expired temporary access', ['count' => $count]);

        return $count;
    }

    /**
     * Liste les accès temporaires actifs pour un utilisateur
     *
     * @return Collection
     */
    public function getUserTemporaryAccess(User $user)
    {
        return DB::table('temporary_access')
            ->where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();
    }

    /**
     * Liste les accès temporaires pour une ressource
     *
     * @param  mixed  $accessible
     * @return Collection
     */
    public function getResourceTemporaryAccess($accessible)
    {
        return DB::table('temporary_access')
            ->where('accessible_type', get_class($accessible))
            ->where('accessible_id', $accessible->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();
    }

    /**
     * Transfère la propriété d'un workspace
     */
    public function transferWorkspaceOwnership(Workspace $workspace, User $newOwner): void
    {
        DB::transaction(function () use ($workspace, $newOwner) {
            $oldOwner = $workspace->owner;

            // Mettre à jour le propriétaire
            $workspace->update(['owner_id' => $newOwner->id]);

            // Mettre à jour les rôles dans workspace_members
            // Ancien owner devient manager
            $workspace->members()->updateExistingPivot($oldOwner->id, [
                'role' => 'manager',
                'permissions' => json_encode([
                    'can_view_all_projects' => true,
                    'can_create_projects' => true,
                    'can_invite_members' => true,
                    'can_manage_settings' => true,
                    'can_transfer_ownership' => false,
                    'can_delete_members' => true,
                ]),
            ]);

            // Nouveau owner
            if (! $workspace->members()->where('user_id', $newOwner->id)->exists()) {
                $workspace->members()->attach($newOwner->id, [
                    'role' => 'owner',
                    'permissions' => json_encode([
                        'can_view_all_projects' => true,
                        'can_create_projects' => true,
                        'can_delete_projects' => true,
                        'can_invite_members' => true,
                        'can_manage_settings' => true,
                        'can_transfer_ownership' => true,
                    ]),
                    'invited_at' => now(),
                ]);
            } else {
                $workspace->members()->updateExistingPivot($newOwner->id, [
                    'role' => 'owner',
                    'permissions' => json_encode([
                        'can_view_all_projects' => true,
                        'can_create_projects' => true,
                        'can_delete_projects' => true,
                        'can_invite_members' => true,
                        'can_manage_settings' => true,
                        'can_transfer_ownership' => true,
                    ]),
                ]);
            }

            // Log
            activity()
                ->causedBy(auth()->user())
                ->performedOn($workspace)
                ->withProperties([
                    'old_owner_id' => $oldOwner->id,
                    'new_owner_id' => $newOwner->id,
                ])
                ->log('workspace_ownership_transferred');
        });
    }

    /**
     * Vérifie si un utilisateur peut effectuer une action sur une ressource
     *
     * @param  mixed  $resource
     */
    public function can(User $user, $resource, string $action): bool
    {
        return $user->isSuperAdmin();
    }
}
