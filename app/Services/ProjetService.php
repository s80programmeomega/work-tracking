<?php

namespace App\Services;

use App\Models\Projet;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProjetService
{
    /**
     * Get all projects with filters and pagination.
     */
    public function getAllProjets(array $filters = []): LengthAwarePaginator
    {
        $query = Projet::query()
            ->with(['responsable', 'members', 'tags']);

        $this->applyFilters($query, $filters);

        $perPage = $filters['per_page'] ?? 15;
        return $query->latest()->paginate($perPage);
    }

    /**
     * Get projects for a specific user.
     */
    public function getUserProjets(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Projet::query()
            ->with(['responsable', 'members', 'tags'])
            ->forUser($user->id);

        $this->applyFilters($query, $filters);

        $perPage = $filters['per_page'] ?? 15;
        return $query->latest()->paginate($perPage);
    }

    /**
     * Apply filters to query.
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['visibility'])) {
            $query->where('visibility', $filters['visibility']);
        }

        if (!empty($filters['responsable_id'])) {
            $query->where('responsable_id', $filters['responsable_id']);
        }

        if (!empty($filters['tags'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->whereIn('projet_tags.id', (array) $filters['tags']);
            });
        }

        if (isset($filters['is_template'])) {
            $query->where('is_template', filter_var($filters['is_template'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['is_favorite'])) {
            $query->where('is_favorite', filter_var($filters['is_favorite'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['is_overdue']) && filter_var($filters['is_overdue'], FILTER_VALIDATE_BOOLEAN)) {
            $query->overdue();
        }
    }

    /**
     * Create a new project.
     */
    public function createProjet(array $data): Projet
    {
        return DB::transaction(function () use ($data) {
            // Extract relationships
            $members = $data['members'] ?? [];
            $tags = $data['tags'] ?? [];
            unset($data['members'], $data['tags']);

            // Create project
            $projet = Projet::create($data);

            // Attach members
            if (!empty($members)) {
                foreach ($members as $member) {
                    $projet->members()->attach($member['user_id'], [
                        'role' => $member['role'] ?? 'member',
                        'can_edit' => $member['can_edit'] ?? false,
                        'can_delete' => $member['can_delete'] ?? false,
                        'can_invite' => $member['can_invite'] ?? false,
                    ]);
                }
            }

            // Attach tags
            if (!empty($tags)) {
                $projet->tags()->attach($tags);
            }

            return $projet->load(['responsable', 'members', 'tags']);
        });
    }

    /**
     * Update a project.
     */
    public function updateProjet(Projet $projet, array $data): Projet
    {
        return DB::transaction(function () use ($projet, $data) {
            // Extract relationships
            $tags = $data['tags'] ?? null;
            unset($data['tags']);

            // Update project
            $projet->update($data);

            // Sync tags if provided
            if ($tags !== null) {
                $projet->tags()->sync($tags);
            }

            return $projet->load(['responsable', 'members', 'tags']);
        });
    }

    /**
     * Delete a project.
     */
    public function deleteProjet(Projet $projet): bool
    {
        return $projet->delete();
    }

    /**
     * Archive a project.
     */
    public function archiveProjet(Projet $projet): Projet
    {
        $projet->archive();
        return $projet->fresh();
    }

    /**
     * Unarchive a project.
     */
    public function unarchiveProjet(Projet $projet): Projet
    {
        $projet->unarchive();
        return $projet->fresh();
    }

    /**
     * Complete a project.
     */
    public function completeProjet(Projet $projet): Projet
    {
        $projet->complete();
        return $projet->fresh();
    }

    /**
     * Clone a project.
     */
    public function cloneProjet(Projet $projet, array $overrides = []): Projet
    {
        return DB::transaction(function () use ($projet, $overrides) {
            $newData = array_merge($projet->only([
                'nom',
                'description',
                'responsable_id',
                'visibility',
                'couleur',
                'budget',
                'objectifs',
                'metadata',
            ]), $overrides);

            // Add suffix to name
            if (!isset($overrides['nom'])) {
                $newData['nom'] = $projet->nom . ' (Copie)';
            }

            // Create new project
            $newProjet = Projet::create($newData);

            // Clone members
            foreach ($projet->members as $member) {
                $newProjet->members()->attach($member->id, [
                    'role' => $member->pivot->role,
                    'can_edit' => $member->pivot->can_edit,
                    'can_delete' => $member->pivot->can_delete,
                    'can_invite' => $member->pivot->can_invite,
                ]);
            }

            // Clone tags
            $newProjet->tags()->attach($projet->tags->pluck('id'));

            return $newProjet->load(['responsable', 'members', 'tags']);
        });
    }

    /**
     * Add member to project.
     */
    public function addMember(Projet $projet, int $userId, array $permissions = []): void
    {
        $projet->members()->attach($userId, [
            'role' => $permissions['role'] ?? 'member',
            'can_edit' => $permissions['can_edit'] ?? false,
            'can_delete' => $permissions['can_delete'] ?? false,
            'can_invite' => $permissions['can_invite'] ?? false,
        ]);
    }

    /**
     * Update member permissions.
     */
    public function updateMember(Projet $projet, int $userId, array $permissions): void
    {
        $projet->members()->updateExistingPivot($userId, $permissions);
    }

    /**
     * Remove member from project.
     */
    public function removeMember(Projet $projet, int $userId): void
    {
        $projet->members()->detach($userId);
    }

    /**
     * Toggle favorite status.
     */
    public function toggleFavorite(Projet $projet): Projet
    {
        $projet->update(['is_favorite' => !$projet->is_favorite]);
        return $projet->fresh();
    }

    /**
     * Get project statistics.
     */
    public function getProjetStats(Projet $projet): array
    {
        return [
            'member_count' => $projet->members()->count(),
            'tag_count' => $projet->tags()->count(),
            // TODO: Add activites and taches stats when models are created
            'activite_count' => 0,
            'tache_count' => 0,
            'completed_tache_count' => 0,
        ];
    }

    /**
     * Get dashboard statistics.
     */
    public function getDashboardStats(User $user): array
    {
        return [
            'total_projets' => Projet::forUser($user->id)->count(),
            'active_projets' => Projet::forUser($user->id)->active()->count(),
            'archived_projets' => Projet::forUser($user->id)->archived()->count(),
            'completed_projets' => Projet::forUser($user->id)->completed()->count(),
            'overdue_projets' => Projet::forUser($user->id)->overdue()->count(),
            'favorite_projets' => Projet::forUser($user->id)->favorite()->count(),
        ];
    }
}
