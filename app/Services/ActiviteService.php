<?php

namespace App\Services;

use App\Models\Activite;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ActiviteService
{
    /**
     * Get all activities with filters and pagination
     * ✅ Support multi-workspace
     */
    public function getAllActivites(array $filters = []): LengthAwarePaginator
    {
        $query = Activite::query()
            ->with(['projet.workspace', 'responsable']);

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
     * ✅ Support multi-workspace
     */
    public function getUserActivites(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Activite::query()
            ->with(['projet.workspace', 'responsable'])
            ->where('responsable_id', $user->id)
            ->whereHas('projet', function ($q) use ($user) {
                $q->accessibleBy($user->id);
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
     * Create new activity
     */
    public function createActivite(array $data): Activite
    {
        // Set default order to last position
        if (!isset($data['ordre'])) {
            $maxOrder = Activite::where('projet_id', $data['projet_id'])->max('ordre') ?? -1;
            $data['ordre'] = $maxOrder + 1;
        }
            $data['created_by'] = auth()->id();

        $activite = Activite::create($data);

        $activite->load(['projet', 'responsable']);

        return $activite;
    }

    /**
     * Update activity
     */
    public function updateActivite(Activite $activite, array $data): Activite
    {
        $activite->update($data);

        $activite->load(['projet', 'responsable']);

        return $activite->fresh();
    }

    /**
     * Delete activity
     */
    public function deleteActivite(Activite $activite): void
    {
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

        return $this->createActivite($data);
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
}