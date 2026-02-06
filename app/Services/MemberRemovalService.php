<?php

namespace App\Services;

use App\Models\{User, Workspace, Projet, Activite, Tache};
use Illuminate\Support\Facades\{DB, Log};
use Illuminate\Support\Collection;

/**
 * Service de gestion du retrait de membres avec transfert de responsabilités
 * 
 * Hiérarchie : Workspace > Projet > Activité > Tâche
 * - Retrait du Workspace = retrait de TOUT (projets, activités, tâches)
 * - Retrait d'un Projet = retrait des activités et tâches du projet uniquement
 * - Retrait d'une Activité = retrait des tâches de l'activité uniquement
 * - Retrait d'une Tâche = simple désassignation
 */
class MemberRemovalService
{
    /**
     * Retire un membre du WORKSPACE et transfère toutes ses responsabilités
     * C'est le niveau le plus haut : on retire TOUT
     *
     * @param Workspace $workspace
     * @param User $userToRemove
     * @param User|null $newResponsable Nouveau responsable (owner par défaut)
     * @return array Statistiques détaillées du transfert
     */
    public function removeFromWorkspace(
        Workspace $workspace,
        User $userToRemove,
        ?User $newResponsable = null
    ): array {
        // Validation : on ne peut pas retirer le owner
        if ($workspace->owner_id === $userToRemove->id) {
            throw new \Exception('Impossible de retirer le propriétaire du workspace. Transférez d\'abord la propriété.');
        }

        return DB::transaction(function () use ($workspace, $userToRemove, $newResponsable) {
            $stats = [
                'projets_transferred' => 0,
                'projets_membership_removed' => 0,
                'activites_transferred' => 0,
                'activites_membership_removed' => 0,
                'taches_reassigned' => 0,
                'taches_unassigned' => 0,
                'temporary_access_revoked' => 0,
            ];

            // Définir le nouveau responsable (owner par défaut)
            $newResponsable = $newResponsable ?? $workspace->owner;

            // =====================================================
            // 1. PROJETS : Transfert ou retrait
            // =====================================================
            $allProjets = $workspace->projets;

            foreach ($allProjets as $projet) {
                // 1a. Si responsable du projet → TRANSFERT
                if ($projet->responsable_id === $userToRemove->id) {
                    $this->transferProjetResponsability($projet, $newResponsable);
                    $stats['projets_transferred']++;
                }

                // 1b. Traiter les ACTIVITÉS du projet
                foreach ($projet->activites as $activite) {
                    // Si responsable de l'activité → TRANSFERT
                    if ($activite->responsable_id === $userToRemove->id) {
                        $this->transferActiviteResponsability($activite, $newResponsable);
                        $stats['activites_transferred']++;
                    }

                    // Retirer des membres de l'activité
                    if ($activite->members()->where('user_id', $userToRemove->id)->exists()) {
                        $activite->members()->detach($userToRemove->id);
                        $stats['activites_membership_removed']++;
                    }

                    // 1c. Traiter les TÂCHES de l'activité
                    $tachesStats = $this->handleTachesForUser($activite->taches, $userToRemove, $newResponsable);
                    $stats['taches_reassigned'] += $tachesStats['reassigned'];
                    $stats['taches_unassigned'] += $tachesStats['unassigned'];
                }

                // Retirer des membres du projet
                if ($projet->members()->where('user_id', $userToRemove->id)->exists()) {
                    $projet->members()->detach($userToRemove->id);
                    $stats['projets_membership_removed']++;
                }
            }

            // =====================================================
            // 2. RETIRER DU WORKSPACE
            // =====================================================
            $workspace->members()->detach($userToRemove->id);

            // =====================================================
            // 3. RÉVOQUER LES ACCÈS TEMPORAIRES
            // =====================================================
            $stats['temporary_access_revoked'] = $this->revokeTemporaryAccess($workspace, $userToRemove);

            // =====================================================
            // 4. LOG DE L'ACTION
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
                ->log('member_removed_from_workspace_with_transfer');

            Log::info("Member removed from workspace with full transfer", [
                'workspace_id' => $workspace->id,
                'user_removed' => $userToRemove->id,
                'new_responsable' => $newResponsable->id,
                'stats' => $stats,
            ]);

            return $stats;
        });
    }

    /**
     * Retire un membre d'un PROJET uniquement (pas du workspace)
     * Ne touche que les activités et tâches de CE projet
     *
     * @param Projet $projet
     * @param User $userToRemove
     * @param User|null $newResponsable
     * @return array
     */
    public function removeFromProjet(
        Projet $projet,
        User $userToRemove,
        ?User $newResponsable = null
    ): array {
        return DB::transaction(function () use ($projet, $userToRemove, $newResponsable) {
            $stats = [
                'projet_transferred' => 0,
                'activites_transferred' => 0,
                'activites_membership_removed' => 0,
                'taches_reassigned' => 0,
                'taches_unassigned' => 0,
            ];

            // Par défaut : workspace owner
            $newResponsable = $newResponsable ?? $projet->workspace->owner;

            // 1. Si responsable du projet → TRANSFERT
            if ($projet->responsable_id === $userToRemove->id) {
                $this->transferProjetResponsability($projet, $newResponsable);
                $stats['projet_transferred'] = 1;
            }

            // 2. Traiter les ACTIVITÉS
            foreach ($projet->activites as $activite) {
                if ($activite->responsable_id === $userToRemove->id) {
                    $this->transferActiviteResponsability($activite, $newResponsable);
                    $stats['activites_transferred']++;
                }

                if ($activite->members()->where('user_id', $userToRemove->id)->exists()) {
                    $activite->members()->detach($userToRemove->id);
                    $stats['activites_membership_removed']++;
                }

                // 3. Traiter les TÂCHES
                $tachesStats = $this->handleTachesForUser($activite->taches, $userToRemove, $newResponsable);
                $stats['taches_reassigned'] += $tachesStats['reassigned'];
                $stats['taches_unassigned'] += $tachesStats['unassigned'];
            }

            // 4. Retirer du projet
            $projet->members()->detach($userToRemove->id);

            // Log
            activity()
                ->causedBy(auth()->user())
                ->performedOn($projet)
                ->withProperties([
                    'removed_user_id' => $userToRemove->id,
                    'new_responsable_id' => $newResponsable->id,
                    'stats' => $stats,
                ])
                ->log('member_removed_from_projet_with_transfer');

            return $stats;
        });
    }

    /**
     * Retire un membre d'une ACTIVITÉ uniquement
     * Ne touche que les tâches de CETTE activité
     *
     * @param Activite $activite
     * @param User $userToRemove
     * @param User|null $newResponsable
     * @return array
     */
    public function removeFromActivite(
        Activite $activite,
        User $userToRemove,
        ?User $newResponsable = null
    ): array {
        return DB::transaction(function () use ($activite, $userToRemove, $newResponsable) {
            $stats = [
                'activite_transferred' => 0,
                'taches_reassigned' => 0,
                'taches_unassigned' => 0,
            ];

            // Par défaut : responsable du projet
            $newResponsable = $newResponsable ?? $activite->projet->responsable;

            // 1. Si responsable de l'activité → TRANSFERT
            if ($activite->responsable_id === $userToRemove->id) {
                $this->transferActiviteResponsability($activite, $newResponsable);
                $stats['activite_transferred'] = 1;
            }

            // 2. Traiter les TÂCHES
            $tachesStats = $this->handleTachesForUser($activite->taches, $userToRemove, $newResponsable);
            $stats['taches_reassigned'] += $tachesStats['reassigned'];
            $stats['taches_unassigned'] += $tachesStats['unassigned'];

            // 3. Retirer de l'activité
            $activite->members()->detach($userToRemove->id);

            // Log
            activity()
                ->causedBy(auth()->user())
                ->performedOn($activite)
                ->withProperties([
                    'removed_user_id' => $userToRemove->id,
                    'new_responsable_id' => $newResponsable->id,
                    'stats' => $stats,
                ])
                ->log('member_removed_from_activite_with_transfer');

            return $stats;
        });
    }

    /**
     * Traite les tâches d'un utilisateur
     * RÈGLE IMPORTANTE : Ne transférer que les tâches NON TERMINÉES
     *
     * @param Collection $taches
     * @param User $userToRemove
     * @param User $newResponsable
     * @return array
     */
    private function handleTachesForUser($taches, User $userToRemove, User $newResponsable): array
    {
        $stats = [
            'reassigned' => 0,
            'unassigned' => 0,
        ];

        foreach ($taches as $tache) {
            if ($tache->assignees()->where('user_id', $userToRemove->id)->exists()) {
                
                // ⚠️ RÈGLE CRUCIALE : Ne transférer que les tâches non terminées
                if (!in_array($tache->statut, ['termine', 'completed', 'done'])) {
                    // Tâche NON terminée → TRANSFERT au nouveau responsable
                    $tache->assignees()->detach($userToRemove->id);
                    
                    if (!$tache->assignees()->where('user_id', $newResponsable->id)->exists()) {
                        $tache->assignees()->attach($newResponsable->id, [
                            'role' => 'assignee',
                            'can_edit' => true,
                            'can_complete' => true,
                            'assigned_at' => now(),
                            'assigned_by' => auth()->id(),
                        ]);
                    }
                    
                    $stats['reassigned']++;
                } else {
                    // Tâche terminée → Simple désassignation (garde l'historique)
                    $tache->assignees()->detach($userToRemove->id);
                    $stats['unassigned']++;
                }
            }
        }

        return $stats;
    }

    /**
     * Transfère la responsabilité d'un projet
     */
    private function transferProjetResponsability(Projet $projet, User $newResponsable): void
    {
        $projet->update([
            'responsable_id' => $newResponsable->id,
            // created_by reste inchangé pour la traçabilité
        ]);

        // Ajouter comme admin du projet si pas déjà membre
        if (!$projet->members()->where('user_id', $newResponsable->id)->exists()) {
            $projet->members()->attach($newResponsable->id, [
                'role' => 'admin',
                'can_edit' => true,
                'can_delete' => true,
                'can_invite' => true,
            ]);
        } else {
            // Mettre à jour le rôle à admin
            $projet->members()->updateExistingPivot($newResponsable->id, [
                'role' => 'admin',
            ]);
        }
    }

    /**
     * Transfère la responsabilité d'une activité
     */
    private function transferActiviteResponsability(Activite $activite, User $newResponsable): void
    {
        $activite->update([
            'responsable_id' => $newResponsable->id,
        ]);

        // Ajouter comme membre avec permissions complètes si pas déjà membre
        if (!$activite->members()->where('user_id', $newResponsable->id)->exists()) {
            $activite->members()->attach($newResponsable->id, [
                'role' => 'responsable',
                'can_create_tasks' => true,
                'can_edit_tasks' => true,
                'can_delete_tasks' => true,
                'can_validate_results' => true,
                'can_assign_users' => true,
            ]);
        } else {
            // Mettre à jour les permissions
            $activite->members()->updateExistingPivot($newResponsable->id, [
                'role' => 'responsable',
                'can_create_tasks' => true,
                'can_edit_tasks' => true,
                'can_delete_tasks' => true,
                'can_validate_results' => true,
                'can_assign_users' => true,
            ]);
        }
    }

    /**
     * Révoque tous les accès temporaires d'un utilisateur dans un workspace
     */
    private function revokeTemporaryAccess(Workspace $workspace, User $user): int
    {
        return DB::table('temporary_access')
            ->where('user_id', $user->id)
            ->where(function ($query) use ($workspace) {
                // Accès aux projets du workspace
                $query->where(function ($q) use ($workspace) {
                    $q->where('accessible_type', Projet::class)
                        ->whereIn('accessible_id', $workspace->projets()->pluck('id'));
                })
                // Accès aux activités du workspace
                ->orWhere(function ($q) use ($workspace) {
                    $q->where('accessible_type', Activite::class)
                        ->whereIn('accessible_id', function ($subQuery) use ($workspace) {
                            $subQuery->select('id')
                                ->from('activites')
                                ->whereIn('projet_id', $workspace->projets()->pluck('id'));
                        });
                })
                // Accès aux tâches du workspace
                ->orWhere(function ($q) use ($workspace) {
                    $q->where('accessible_type', Tache::class)
                        ->whereIn('accessible_id', function ($subQuery) use ($workspace) {
                            $subQuery->select('id')
                                ->from('taches')
                                ->whereIn('activite_id', function ($subSubQuery) use ($workspace) {
                                    $subSubQuery->select('id')
                                        ->from('activites')
                                        ->whereIn('projet_id', $workspace->projets()->pluck('id'));
                                });
                        });
                });
            })
            ->delete();
    }

    /**
     * Récupère les projets où un utilisateur est responsable dans un workspace
     */
    public function getUserProjectsAsResponsable(User $user, Workspace $workspace): Collection
    {
        return $workspace->projets()
            ->where('responsable_id', $user->id)
            ->withCount(['activites', 'members'])
            ->get();
    }

    /**
     * Récupère les candidats possibles pour un transfert
     * (Membres du workspace, sauf l'utilisateur à retirer)
     */
    public function getTransferCandidates(Workspace $workspace, User $excludeUser): Collection
    {
        return $workspace->members()
            ->where('user_id', '!=', $excludeUser->id)
            ->where('user_id', '!=', $workspace->owner_id) // Exclure le owner (il est déjà la valeur par défaut)
            ->whereHas('roles', function ($query) {
                // Uniquement les membres avec des rôles suffisants
                $query->whereIn('name', ['admin', 'manager', 'member']);
            })
            ->select(['users.id', 'users.nom', 'users.email', 'users.avatar'])
            ->get();
    }

    /**
     * Obtient un aperçu de ce qui sera impacté par le retrait
     */
    public function getRemovalPreview(Workspace $workspace, User $user): array
    {
        $projetsAsResponsable = $workspace->projets()
            ->where('responsable_id', $user->id)
            ->count();

        $projetsAsMember = $workspace->projets()
            ->whereHas('members', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->count();

        $activitesAsResponsable = DB::table('activites')
            ->whereIn('projet_id', $workspace->projets()->pluck('id'))
            ->where('responsable_id', $user->id)
            ->count();

        $tachesNonTerminees = DB::table('taches')
            ->whereIn('activite_id', function ($query) use ($workspace) {
                $query->select('id')
                    ->from('activites')
                    ->whereIn('projet_id', $workspace->projets()->pluck('id'));
            })
            ->whereExists(function ($query) use ($user) {
                $query->select(DB::raw(1))
                    ->from('tache_user')
                    ->whereColumn('tache_user.tache_id', 'taches.id')
                    ->where('tache_user.user_id', $user->id);
            })
            ->whereNotIn('statut', ['termine', 'completed', 'done'])
            ->count();

        return [
            'projets_as_responsable' => $projetsAsResponsable,
            'projets_as_member' => $projetsAsMember,
            'activites_as_responsable' => $activitesAsResponsable,
            'taches_non_terminees' => $tachesNonTerminees,
            'requires_transfer' => $projetsAsResponsable > 0 || $activitesAsResponsable > 0,
        ];
    }
}