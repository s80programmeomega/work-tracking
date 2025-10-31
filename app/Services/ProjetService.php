<?php

namespace App\Services;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjetService
{
    /**
     * Get all projects with filters and pagination.
     */
    public function getAllProjets(array $filters = []): LengthAwarePaginator
    {
        $query = Projet::query()
            ->with(['responsable', 'workspace']);
 
        // Appliquer les filtres
        // $this->applyFilters($query, $filters);

        // Ajouter les counts
        // $query->withCount(['activites', 'members']);

        // $perPage = $filters['per_page'] ?? 15;
        return $query->latest()->paginate(12);
    }

    /**
     * Get projects for a specific user.
     */
    public function getUserProjets(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Projet::query()
            ->with(['responsable', 'workspace'])
            ->where(function ($q) use ($user) {
                $q->where('responsable_id', $user->id)
                    ->orWhereHas('members', function ($memberQuery) use ($user) {
                        $memberQuery->where('user_id', $user->id);
                    });
            });

        $this->applyFilters($query, $filters);

        $query->withCount(['activites', 'members']);

        $perPage = $filters['per_page'] ?? 15;
        return $query->latest()->paginate($perPage);
    }

    /**
     * Apply filters to query.
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        // Workspace filter
        if (!empty($filters['workspace_id'])) {
            $query->where('workspace_id', $filters['workspace_id']);
        }

        // Search filter
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $searchTerm = $filters['search'];
                $q->where('nom', 'like', "%{$searchTerm}%")
                    ->orWhere('code', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Visibility filter
        if (!empty($filters['visibility'])) {
            $query->where('visibility', $filters['visibility']);
        }

        // Responsable filter
        if (!empty($filters['responsable_id'])) {
            $query->where('responsable_id', $filters['responsable_id']);
        }

        // Tags filter
        if (!empty($filters['tags'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->whereIn('projet_tags.id', (array) $filters['tags']);
            });
        }

        // Template filter
        if (isset($filters['is_template'])) {
            $query->where('is_template', filter_var($filters['is_template'], FILTER_VALIDATE_BOOLEAN));
        }

        // Favorite filter
        if (isset($filters['is_favorite'])) {
            $query->where('is_favorite', filter_var($filters['is_favorite'], FILTER_VALIDATE_BOOLEAN));
        }

        // Overdue filter
        if (isset($filters['is_overdue']) && filter_var($filters['is_overdue'], FILTER_VALIDATE_BOOLEAN)) {
            $query->where('date_fin', '<', now())
                ->whereNotIn('status', ['completed', 'archived']);
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

            // Attach responsable as member with admin role
            if ($projet->responsable_id) {
                $projet->members()->attach($projet->responsable_id, [
                    'role' => 'admin',
                    'can_edit' => true,
                    'can_delete' => true,
                    'can_invite' => true,
                ]);
            }

            // Attach additional members
            if (!empty($members)) {
                foreach ($members as $member) {
                    if ($member['user_id'] != $projet->responsable_id) {
                        $projet->members()->attach($member['user_id'], [
                            'role' => $member['role'] ?? 'member',
                            'can_edit' => $member['can_edit'] ?? false,
                            'can_delete' => $member['can_delete'] ?? false,
                            'can_invite' => $member['can_invite'] ?? false,
                        ]);
                    }
                }
            }

            // Attach tags
            if (!empty($tags)) {
                $projet->tags()->attach($tags);
            }

            return $projet->load(['responsable', 'members', 'tags', 'workspace']);
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

            // Recalculate progression if needed
            if (!isset($data['progression'])) {
                $projet->updateProgression();
            }

            return $projet->load(['responsable', 'members', 'tags', 'workspace']);
        });
    }

    /**
     * Delete a project.
     */
    public function deleteProjet(Projet $projet): bool
    {
        return DB::transaction(function () use ($projet) {
            // Detach all members
            $projet->members()->detach();

            // Detach all tags
            $projet->tags()->detach();

            // Delete project (soft delete)
            return $projet->delete();
        });
    }

    /**
     * Archive a project.
     */
    public function archiveProjet(Projet $projet): Projet
    {
        $projet->archive();
        return $projet->fresh(['responsable', 'members', 'tags']);
    }

    /**
     * Unarchive a project.
     */
    public function unarchiveProjet(Projet $projet): Projet
    {
        $projet->unarchive();
        return $projet->fresh(['responsable', 'members', 'tags']);
    }

    /**
     * Complete a project.
     */
    public function completeProjet(Projet $projet): Projet
    {
        $projet->complete();
        return $projet->fresh(['responsable', 'members', 'tags']);
    }

    /**
     * Clone a project.
     */
    public function cloneProjet(Projet $projet, array $overrides = []): Projet
    {
        return DB::transaction(function () use ($projet, $overrides) {
            $newData = array_merge($projet->only([
                'workspace_id',
                'description',
                'responsable_id',
                'visibility',
                'couleur',
                'budget',
                'objectifs',
                'metadata',
            ]), $overrides);

            // Add suffix to name if not provided
            if (!isset($overrides['nom'])) {
                $newData['nom'] = $projet->nom . ' (Copie)';
            }

            // Reset some fields
            $newData['status'] = 'pending';
            $newData['progression'] = 0;
            $newData['is_favorite'] = false;

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

            return $newProjet->load(['responsable', 'members', 'tags', 'workspace']);
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
        $existingPermissions = $projet->members()
            ->where('user_id', $userId)
            ->first()
            ?->pivot
                ?->only(['role', 'can_edit', 'can_delete', 'can_invite']) ?? [];

        $newPermissions = array_merge($existingPermissions, $permissions);

        $projet->members()->updateExistingPivot($userId, $newPermissions);
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
        return $projet->fresh(['responsable', 'members', 'tags']);
    }

    /**
     * Get project statistics.
     */
    public function getProjetStats(Projet $projet): array
    {
        $activitesCount = $projet->activites()->count();
        $tachesCount = $projet->taches()->count();
        $tachesTermineesCount = $projet->taches()->where('statut', 'termine')->count();

        return [
            'activites_count' => $activitesCount,
            'taches_count' => $tachesCount,
            'taches_terminees' => $tachesTermineesCount,
            'taches_en_cours' => $projet->taches()->where('statut', 'en_cours')->count(),
            'taches_en_attente' => $projet->taches()->where('statut', 'en_attente')->count(),
            'taches_en_retard' => $projet->taches()
                ->where('echeance', '<', now())
                ->whereNotIn('statut', ['termine', 'annule'])
                ->count(),
            'member_count' => $projet->members()->count(),
            'progression_reelle' => $projet->progression ?? 0,
            'progression_calculee' => $projet->calculateProgression(),
            'budget_utilise' => 0, // À implémenter selon votre logique
            'budget_restant' => $projet->budget ?? 0,
        ];
    }

    /**
     * Get workspace dashboard statistics.
     */
    public function getWorkspaceDashboardStats(int $workspaceId): array
    { 
        $projetsQuery = Projet::where('workspace_id', $workspaceId);

        $stats = [
            'total_projets' => (clone $projetsQuery)->count(),
            'projets_actifs' => (clone $projetsQuery)->where('status', 'active')->count(),
            'projets_termines' => (clone $projetsQuery)->where('status', 'completed')->count(),
            'projets_archives' => (clone $projetsQuery)->where('status', 'archived')->count(),
            'projets_en_retard' => (clone $projetsQuery)
                ->where('date_fin', '<', now())
                ->whereNotIn('status', ['completed', 'archived'])
                ->count(),
            'projets_favoris' => (clone $projetsQuery)->where('is_favorite', true)->count(),
        ];
 
        // Statistiques des activités
        $stats['total_activites'] = Activite::whereHas('projet', function ($q) use ($workspaceId) {
            $q->where('workspace_id', $workspaceId);
        })->count();

        // Statistiques des tâches
        $tachesStats = Tache::whereHas('activite.projet', function ($q) use ($workspaceId) {
            $q->where('workspace_id', $workspaceId);
        })->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN statut = "termine" THEN 1 ELSE 0 END) as terminees
        ')->first();

        $stats['total_taches'] = $tachesStats->total ?? 0;
        $stats['taches_terminees'] = $tachesStats->terminees ?? 0;
        $stats['taux_completion'] = $stats['total_taches'] > 0
            ? round(($stats['taches_terminees'] / $stats['total_taches']) * 100, 1)
            : 0;

        // Activités récentes (dernières modifications de projets)
        $stats['recent_activities'] = Projet::where('workspace_id', $workspaceId)
            ->with('responsable')
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(function ($projet) {
                return [
                    'id' => $projet->id,
                    'type' => 'projet_updated',
                    'projet_nom' => $projet->nom,
                    'responsable' => $projet->responsable->nom ?? 'N/A',
                    'updated_at' => $projet->updated_at->diffForHumans(),
                ];
            });

        return $stats;
    }

    /**
     * Generate performance report for weekly evaluation.
     */
    public function generatePerformanceReport(Projet $projet, $startDate, $endDate): array
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        // Tâches créées pendant la période
        $tachesCreees = Tache::whereHas('activite', function ($q) use ($projet) {
            $q->where('projet_id', $projet->id);
        })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Tâches complétées pendant la période
        $tachesCompletees = Tache::whereHas('activite', function ($q) use ($projet) {
            $q->where('projet_id', $projet->id);
        })
            ->where('statut', 'termine')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();

        // Tâches en retard
        $tachesEnRetard = Tache::whereHas('activite', function ($q) use ($projet) {
            $q->where('projet_id', $projet->id);
        })
            ->where('echeance', '<', now())
            ->whereNotIn('statut', ['termine', 'annule'])
            ->count();

        // Progression moyenne
        $progressionDebut = $this->getProjetProgressionAtDate($projet, $startDate);
        $progressionFin = $projet->progression ?? 0;

        return [
            'periode' => [
                'debut' => $startDate->format('Y-m-d'),
                'fin' => $endDate->format('Y-m-d'),
            ],
            'taches' => [
                'creees' => $tachesCreees,
                'completees' => $tachesCompletees,
                'en_retard' => $tachesEnRetard,
                'taux_completion' => $tachesCreees > 0
                    ? round(($tachesCompletees / $tachesCreees) * 100, 1)
                    : 0,
            ],
            'progression' => [
                'debut_periode' => $progressionDebut,
                'fin_periode' => $progressionFin,
                'evolution' => $progressionFin - $progressionDebut,
            ],
            'membres_actifs' => $this->getActiveMembersCount($projet, $startDate, $endDate),
        ];
    }

    /**
     * Get project progression at a specific date.
     */
    private function getProjetProgressionAtDate(Projet $projet, Carbon $date): int
    {
        // Cette méthode nécessiterait un système d'historique
        // Pour l'instant, on retourne 0 si la date est dans le passé
        return 0;
    }

    /**
     * Get active members count during period.
     */
    private function getActiveMembersCount(Projet $projet, Carbon $startDate, Carbon $endDate): int
    {
        // Compter les membres qui ont eu une activité pendant la période
        // (création/modification de tâches, etc.)
        return $projet->members()->count();
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
