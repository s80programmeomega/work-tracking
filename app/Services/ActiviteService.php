<?php

namespace App\Services;

use App\Models\Activite;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ActiviteService
{
    /**
     * Get all activities with filters and pagination
     * ✅ AMÉLIORATION : Support multi-workspace avec permissions
     */
    public function getAllActivites(array $filters = []): LengthAwarePaginator
    {
        $query = Activite::query()
            ->with(['projet.workspace', 'responsable', 'membres']); // ✅ Ajouter membres

        // Apply filters
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['projet_id'])) {
            $query->forProjet($filters['projet_id']);
        }

        if (!empty($filters['responsable_id'])) {
            $query->forUser($filters['responsable_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['is_overdue'])) {
            $query->overdue();
        }

        // ✅ Filtre par workspace
        if (!empty($filters['workspace_id'])) {
            $query->whereHas('projet', function ($q) use ($filters) {
                $q->where('workspace_id', $filters['workspace_id']);
            });
        }

        // Sorting
        $query->orderByPosition();

        // Pagination
        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Get activities for a specific project
     */
    public function getActivitiesForProjet(int $projetId, array $filters = []): LengthAwarePaginator
    {
        $filters['projet_id'] = $projetId;
        return $this->getAllActivites($filters);
    }

    /**
     * Get activities for a user
     * ✅ AMÉLIORATION : Intégration des membres d'activité
     */
    public function getUserActivites(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Activite::query()
            ->with(['projet.workspace', 'responsable', 'membres']);

        // ✅ AMÉLIORATION : Inclure les activités où l'utilisateur est membre
        $query->where(function ($q) use ($user) {
            // Responsable de l'activité
            $q->where('responsable_id', $user->id)
                // OU membre de l'activité
                ->orWhereHas('membres', function ($mq) use ($user) {
                    $mq->where('user_id', $user->id);
                });
        });

        // ✅ Filtrer par projets accessibles
        $query->whereHas('projet', function ($q) use ($user, $filters) {
            $q->accessibleBy($user->id);

            // ✅ APPLIQUER LE FILTRE WORKSPACE_ID
            if (!empty($filters['workspace_id'])) {
                $q->where('workspace_id', $filters['workspace_id']);
            }
        });

        // Apply filters
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['projet_id'])) {
            $query->forProjet($filters['projet_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['is_overdue'])) {
            $query->overdue();
        }

        // ✅ Filtre par workspace
        if (!empty($filters['workspace_id'])) {
            $query->whereHas('projet', function ($q) use ($filters) {
                $q->where('workspace_id', $filters['workspace_id']);
            });
        }

        // Sorting
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Get activities accessible by user (pas seulement ses activités)
     */
    public function getAccessibleActivites(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Activite::query()
            ->with(['projet.workspace', 'responsable', 'membres']);

        // ✅ Filtrer par projets accessibles à l'utilisateur
        $query->whereHas('projet', function ($q) use ($user, $filters) {
            $q->accessibleBy($user->id);

            // Filtre workspace
            if (!empty($filters['workspace_id'])) {
                $q->where('workspace_id', $filters['workspace_id']);
            }
        });

        // Apply other filters
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['projet_id'])) {
            $query->forProjet($filters['projet_id']);
        }

        if (!empty($filters['responsable_id'])) {
            $query->forUser($filters['responsable_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['is_overdue'])) {
            $query->overdue();
        }

        // Sorting
        $query->orderByPosition();

        // Pagination
        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Create new activity
     * ✅ AMÉLIORATION : Support des membres initiaux
     */
    public function createActivite(array $data): Activite
    {
        // Set default order to last position
        if (!isset($data['ordre'])) {
            $maxOrder = Activite::where('projet_id', $data['projet_id'])->max('ordre') ?? -1;
            $data['ordre'] = $maxOrder + 1;
        }

        $data['created_by'] = auth()->id();

        DB::beginTransaction();
        try {
            // Extraire les membres avant de créer l'activité
            $membres = $data['membres'] ?? [];
            unset($data['membres']);

            $activite = Activite::create($data);

            // ✅ Ajouter les membres si fournis
            if (!empty($membres)) {
                foreach ($membres as $membre) {
                    $activite->membres()->attach($membre['user_id'], [
                        'role' => $membre['role'],
                        'can_create_tasks' => $membre['can_create_tasks'] ?? false,
                        'can_edit_tasks' => $membre['can_edit_tasks'] ?? false,
                        'can_delete_tasks' => $membre['can_delete_tasks'] ?? false,
                        'can_validate_results' => $membre['can_validate_results'] ?? false,
                        'can_assign_users' => $membre['can_assign_users'] ?? false,
                    ]);
                }
            }

            $activite->load(['projet', 'responsable', 'membres']);

            DB::commit();

            return $activite;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update activity
     */
    public function updateActivite(Activite $activite, array $data): Activite
    {
        $activite->update($data);

        $activite->load(['projet', 'responsable', 'membres']);

        return $activite->fresh();
    }

    /**
     * Delete activity
     */
    public function deleteActivite(Activite $activite): void
    {
        // ✅ Les membres seront supprimés automatiquement grâce au CASCADE
        $activite->delete();
    }

    /**
     * Archive activity
     */
    public function archiveActivite(Activite $activite): Activite
    {
        $activite->archive();
        return $activite->fresh();
    }

    /**
     * Unarchive activity
     */
    public function unarchiveActivite(Activite $activite): Activite
    {
        $activite->unarchive();
        return $activite->fresh();
    }

    /**
     * Duplicate activity
     * ✅ AMÉLIORATION : Option pour copier les membres
     */
    public function duplicateActivite(Activite $activite, array $overrides = []): Activite
    {
        $data = $activite->toArray();

        // Remove unique fields
        unset($data['id'], $data['code'], $data['created_at'], $data['updated_at'], $data['deleted_at']);

        // Apply overrides
        $data = array_merge($data, $overrides);

        // Append "(Copy)" to name
        if (!isset($overrides['nom'])) {
            $data['nom'] = $activite->nom . ' (Copie)';
        }

        // Set new order
        $maxOrder = Activite::where('projet_id', $data['projet_id'])->max('ordre') ?? -1;
        $data['ordre'] = $maxOrder + 1;

        DB::beginTransaction();
        try {
            $newActivite = $this->createActivite($data);

            // ✅ Copier les membres si demandé (par défaut: true)
            if ($overrides['copy_members'] ?? true) {
                foreach ($activite->membres as $membre) {
                    $newActivite->membres()->attach($membre->id, [
                        'role' => $membre->pivot->role,
                        'can_create_tasks' => $membre->pivot->can_create_tasks,
                        'can_edit_tasks' => $membre->pivot->can_edit_tasks,
                        'can_delete_tasks' => $membre->pivot->can_delete_tasks,
                        'can_validate_results' => $membre->pivot->can_validate_results,
                        'can_assign_users' => $membre->pivot->can_assign_users,
                    ]);
                }
            }

            DB::commit();

            return $newActivite->fresh(['membres']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reorder activities
     */
    public function reorderActivites(array $orderedIds): void
    {
        Activite::reorder($orderedIds);
    }

    /**
     * Get activity statistics
     */
    public function getActiviteStats(Activite $activite): array
    {
        $tacheCount = $activite->taches()->count();
        $tachesCompleted = $activite->taches()->where('statut', 'termine')->count();
        $tachesInProgress = $activite->taches()->where('statut', 'en_cours')->count();
        $tachesOverdue = $activite->taches()->overdue()->count();

        return [
            'tache_count' => $tacheCount,
            'taches_completed' => $tachesCompleted,
            'taches_in_progress' => $tachesInProgress,
            'taches_pending' => $tacheCount - $tachesCompleted - $tachesInProgress,
            'taches_overdue' => $tachesOverdue,
            'completion_rate' => $tacheCount > 0 ? round(($tachesCompleted / $tacheCount) * 100, 2) : 0,
        ];
    }

    /**
     * ✅ NOUVELLE MÉTHODE : Ajouter un membre à une activité
     */
    public function addMember(Activite $activite, int $userId, array $permissions): void
    {
        $activite->membres()->attach($userId, [
            'role' => $permissions['role'],
            'can_create_tasks' => $permissions['can_create_tasks'] ?? false,
            'can_edit_tasks' => $permissions['can_edit_tasks'] ?? false,
            'can_delete_tasks' => $permissions['can_delete_tasks'] ?? false,
            'can_validate_results' => $permissions['can_validate_results'] ?? false,
            'can_assign_users' => $permissions['can_assign_users'] ?? false,
        ]);
    }

    /**
     * ✅ NOUVELLE MÉTHODE : Mettre à jour les permissions d'un membre
     */
    public function updateMemberPermissions(Activite $activite, int $userId, array $permissions): void
    {
        $activite->membres()->updateExistingPivot($userId, $permissions);
    }

    /**
     * ✅ NOUVELLE MÉTHODE : Retirer un membre
     */
    public function removeMember(Activite $activite, int $userId): void
    {
        $activite->membres()->detach($userId);
    }

    /**
     * ✅ NOUVELLE MÉTHODE : Vérifier si un utilisateur peut gérer les membres
     */
    public function canManageMembers(Activite $activite, User $user): bool
    {
        // Super admin
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Responsable de l'activité
        if ($activite->responsable_id === $user->id) {
            return true;
        }

        // Admin du projet
        if ($activite->projet->canUserEdit($user)) {
            return true;
        }

        // Membre avec permission can_assign_users
        return $activite->membres()
            ->where('user_id', $user->id)
            ->wherePivot('can_assign_users', true)
            ->exists();
    }

    /**
     * ✅ NOUVELLE MÉTHODE : Vérifier si un utilisateur peut éditer une activité
     */
    public function canEditActivite(Activite $activite, User $user): bool
    {
        // Super admin
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Responsable de l'activité
        if ($activite->responsable_id === $user->id) {
            return true;
        }

        // Admin du projet
        if ($activite->projet->canUserEdit($user)) {
            return true;
        }

        // Membre avec permission can_edit_tasks
        return $activite->membres()
            ->where('user_id', $user->id)
            ->wherePivot('can_edit_tasks', true)
            ->exists();
    }

    /**
     * ✅ NOUVELLE MÉTHODE : Obtenir les statistiques d'un workspace
     */
    public function getWorkspaceActivitesStats(int $workspaceId): array
    {
        $activites = Activite::whereHas('projet', function ($q) use ($workspaceId) {
            $q->where('workspace_id', $workspaceId);
        })->get();

        return [
            'total' => $activites->count(),
            'active' => $activites->where('status', 'active')->count(),
            'archived' => $activites->where('status', 'archived')->count(),
            'overdue' => $activites->filter(fn($a) => $a->is_overdue)->count(),
            'avg_progression' => $activites->avg('progression') ?? 0,
        ];
    }
}