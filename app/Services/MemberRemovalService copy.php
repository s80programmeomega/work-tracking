<?php

namespace App\Services;

use App\Models\Projet;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MemberRemovalService
{
    /**
     * ✅ Retirer un membre d'un workspace avec transfert de projets
     */
    public function removeFromWorkspace(
        Workspace $workspace, 
        User $user, 
        ?User $newResponsable = null
    ): array {
        DB::beginTransaction();

        try {
            $stats = [
                'projects_transferred' => 0,
                'projects_removed_from' => 0,
                'tasks_unassigned' => 0,
                'documents_revoked' => 0,
            ];

            // 1. Gérer les projets où il est responsable
            $projetsResponsable = $workspace->projets()
                ->where('responsable_id', $user->id)
                ->get();

            foreach ($projetsResponsable as $projet) {
                if ($newResponsable) {
                    // Transférer à un nouveau responsable
                    $projet->update(['responsable_id' => $newResponsable->id]);

                    // Ajouter le nouveau responsable comme membre admin
                    if (!$projet->members()->where('user_id', $newResponsable->id)->exists()) {
                        $projet->members()->attach($newResponsable->id, [
                            'role' => 'admin',
                            'can_edit' => true,
                            'can_delete' => true,
                            'can_invite' => true,
                        ]);
                    }

                    $stats['projects_transferred']++;
                } else {
                    // Transférer au owner du workspace par défaut
                    $projet->update(['responsable_id' => $workspace->owner_id]);

                    if (!$projet->members()->where('user_id', $workspace->owner_id)->exists()) {
                        $projet->members()->attach($workspace->owner_id, [
                            'role' => 'admin',
                            'can_edit' => true,
                            'can_delete' => true,
                            'can_invite' => true,
                        ]);
                    }

                    $stats['projects_transferred']++;
                }

                // Log le transfert
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($projet)
                    ->withProperties([
                        'old_responsable' => $user->id,
                        'new_responsable' => $newResponsable?->id ?? $workspace->owner_id,
                        'reason' => 'member_removed_from_workspace'
                    ])
                    ->log('project_responsable_transferred');
            }

            // 2. Retirer de tous les projets du workspace
            foreach ($workspace->projets as $projet) {
                if ($projet->members()->where('user_id', $user->id)->exists()) {
                    $this->removeFromProjet($projet, $user);
                    $stats['projects_removed_from']++;
                }
            }

            // 3. Retirer du workspace
            $workspace->members()->detach($user->id);

            // 4. Log l'action globale
            activity()
                ->causedBy(auth()->user())
                ->performedOn($workspace)
                ->withProperties([
                    'removed_user' => $user->id,
                    'stats' => $stats,
                ])
                ->log('member_removed_from_workspace');

            DB::commit();

            return $stats;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error removing member from workspace', [
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * ✅ Retirer un membre d'un projet (utilisé en interne)
     */
    public function removeFromProjet(Projet $projet, User $user): array
    {
        $stats = [
            'tasks_unassigned' => 0,
            'documents_revoked' => 0,
        ];

        // 1. Retirer des tâches
        foreach ($projet->activites as $activite) {
            foreach ($activite->taches as $tache) {
                if ($tache->assignees()->where('user_id', $user->id)->exists()) {
                    $tache->assignees()->detach($user->id);
                    $stats['tasks_unassigned']++;
                }
            }
        }

        // 2. Révoquer les permissions sur les documents
        $documentsCount = DB::table('document_permissions')
            ->where('permissionable_type', User::class)
            ->where('permissionable_id', $user->id)
            ->whereIn('document_id', function ($query) use ($projet) {
                $query->select('id')
                    ->from('documents')
                    ->where('documentable_type', Projet::class)
                    ->where('documentable_id', $projet->id);
            })
            ->delete();

        $stats['documents_revoked'] = $documentsCount;

        // 3. Retirer du projet
        $projet->members()->detach($user->id);

        // 4. Log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($projet)
            ->withProperties([
                'removed_user' => $user->id,
                'stats' => $stats,
            ])
            ->log('member_removed_from_projet');

        return $stats;
    }

    /**
     * ✅ Retirer un membre d'un projet spécifique (API publique)
     */
    public function removeUserFromProjet(
        Projet $projet, 
        User $user,
        ?User $newResponsable = null
    ): array {
        DB::beginTransaction();

        try {
            $stats = [];

            // Si c'est le responsable, transférer le projet
            if ($projet->responsable_id === $user->id) {
                if ($newResponsable) {
                    $projet->update(['responsable_id' => $newResponsable->id]);

                    // Ajouter le nouveau responsable comme admin
                    if (!$projet->members()->where('user_id', $newResponsable->id)->exists()) {
                        $projet->members()->attach($newResponsable->id, [
                            'role' => 'admin',
                            'can_edit' => true,
                            'can_delete' => true,
                            'can_invite' => true,
                        ]);
                    }

                    $stats['project_transferred'] = true;
                    $stats['new_responsable'] = $newResponsable->nom;
                } else {
                    // Transférer au owner du workspace
                    $workspace = $projet->workspace;
                    $projet->update(['responsable_id' => $workspace->owner_id]);

                    if (!$projet->members()->where('user_id', $workspace->owner_id)->exists()) {
                        $projet->members()->attach($workspace->owner_id, [
                            'role' => 'admin',
                            'can_edit' => true,
                            'can_delete' => true,
                            'can_invite' => true,
                        ]);
                    }

                    $stats['project_transferred'] = true;
                    $stats['new_responsable'] = 'Owner du workspace';
                }

                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($projet)
                    ->withProperties([
                        'old_responsable' => $user->id,
                        'new_responsable' => $newResponsable?->id ?? $workspace->owner_id,
                    ])
                    ->log('project_responsable_transferred');
            }

            // Retirer du projet
            $removalStats = $this->removeFromProjet($projet, $user);
            $stats = array_merge($stats, $removalStats);

            DB::commit();

            return $stats;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error removing user from projet', [
                'projet_id' => $projet->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * ✅ Obtenir les projets où l'utilisateur est responsable
     */
    public function getUserProjectsAsResponsable(User $user, Workspace $workspace): \Illuminate\Support\Collection
    {
        return $workspace->projets()
            ->where('responsable_id', $user->id)
            ->with(['activites', 'members'])
            ->get();
    }

    /**
     * ✅ Obtenir les candidats pour le transfert
     */
    public function getTransferCandidates(Workspace $workspace, User $excludeUser): \Illuminate\Support\Collection
    {
        return $workspace->members()
            ->where('user_id', '!=', $excludeUser->id)
            ->whereIn('role', ['owner', 'admin', 'member'])
            ->get();
    }
}