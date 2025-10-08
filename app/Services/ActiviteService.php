<?php

namespace App\Services;

use App\Models\Activite;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ActiviteService
{
    /**
     * Get all activities with filters and pagination
     */
    public function getAllActivites(array $filters = []): LengthAwarePaginator
    {
        $query = Activite::query()
            ->with(['projet', 'responsable']);

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
     */
    public function getUserActivites(User $user, array $filters = []): LengthAwarePaginator
    {
        $filters['responsable_id'] = $user->id;
        return $this->getAllActivites($filters);
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
        return [
            'tache_count' => $activite->tache_count,
            // TODO: Add more stats when Tache model is created
            // 'taches_completed' => $activite->taches()->where('statut', 'termine')->count(),
            // 'taches_in_progress' => $activite->taches()->where('statut', 'en_cours')->count(),
            // 'taches_overdue' => $activite->taches()->overdue()->count(),
        ];
    }
}
