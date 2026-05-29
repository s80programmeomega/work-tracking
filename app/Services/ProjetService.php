<?php

namespace App\Services;

use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class ProjetService
{
    /**
     * Get ALL projects (SUPER ADMIN ONLY)
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllProjets(array $filters = [])
    {
        $query = Projet::with(['responsable', 'members', 'tags', 'workspace'])
            ->withCount(['activites', 'taches']);

        $this->applyFilters($query, $filters);

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get user's projects (where user is member or responsable)
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserProjets(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Projet::with(['responsable', 'workspace', 'members'])
            ->withCount(['activites', 'members']);

        if (! empty($filters['workspace_id'])) {
            $workspace = Workspace::find($filters['workspace_id']);
            if (! $workspace) {
                return new LengthAwarePaginator([], 0, $filters['per_page'] ?? 15);
            }

            $query->inWorkspace($workspace->id);
        }

        $query->visibleTo($user);

        $this->applyFilters($query, $filters);

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    /**
     * ✅ HELPER : Vérifier si user est Admin du workspace
     */
    private function isWorkspaceAdmin(User $user, Workspace $workspace): bool
    {
        return $workspace->isOwnerOrAdmin($user);
    }

    /**
     * ✅ NOUVEAU : Get global dashboard stats (ALL workspaces - SUPER ADMIN)
     */
    public function getGlobalDashboardStats(): array
    {
        return [
            'total_projets' => Projet::count(),
            'projets_actifs' => Projet::active()->count(),
            'projets_termines' => Projet::completed()->count(),
            'projets_archives' => Projet::archived()->count(),
            'projets_en_retard' => Projet::overdue()->count(),
            'projets_favoris' => Projet::favorite()->count(),
            'total_activites' => DB::table('activites')->count(),
            'total_taches' => DB::table('taches')->count(),
            'taches_terminees' => DB::table('taches')->where('statut', 'termine')->count(),
            'taux_completion' => $this->calculateGlobalCompletionRate(),
            'recent_activities' => $this->getGlobalRecentActivities(),
            'workspaces_count' => DB::table('workspaces')->count(),
        ];
    }

    /**
     * Get dashboard stats for a specific workspace
     */
    public function getWorkspaceDashboardStats(int $workspaceId): array
    {
        $totalProjets = Projet::inWorkspace($workspaceId)->count();

        return [
            'total_projets' => $totalProjets,
            'projets_actifs' => Projet::inWorkspace($workspaceId)->active()->count(),
            'projets_termines' => Projet::inWorkspace($workspaceId)->completed()->count(),
            'projets_archives' => Projet::inWorkspace($workspaceId)->archived()->count(),
            'projets_en_retard' => Projet::inWorkspace($workspaceId)->overdue()->count(),
            'projets_favoris' => Projet::inWorkspace($workspaceId)->favorite()->count(),
            'total_activites' => DB::table('activites')
                ->join('projets', 'activites.projet_id', '=', 'projets.id')
                ->where('projets.workspace_id', $workspaceId)
                ->count(),
            'total_taches' => DB::table('taches')
                ->join('activites', 'taches.activite_id', '=', 'activites.id')
                ->join('projets', 'activites.projet_id', '=', 'projets.id')
                ->where('projets.workspace_id', $workspaceId)
                ->count(),
            'taches_terminees' => DB::table('taches')
                ->join('activites', 'taches.activite_id', '=', 'activites.id')
                ->join('projets', 'activites.projet_id', '=', 'projets.id')
                ->where('projets.workspace_id', $workspaceId)
                ->where('taches.statut', 'termine')
                ->count(),
            'taux_completion' => $this->calculateWorkspaceCompletionRate($workspaceId),
            'recent_activities' => $this->getWorkspaceRecentActivities($workspaceId),
        ];
    }

    /**
     * ✅ Calculate global completion rate
     */
    private function calculateGlobalCompletionRate(): int
    {
        $totalTaches = DB::table('taches')->count();

        if ($totalTaches === 0) {
            return 0;
        }

        $completedTaches = DB::table('taches')->where('statut', 'termine')->count();

        return (int) round(($completedTaches / $totalTaches) * 100);
    }

    /**
     * Calculate workspace completion rate
     */
    private function calculateWorkspaceCompletionRate(int $workspaceId): int
    {
        $totalTaches = DB::table('taches')
            ->join('activites', 'taches.activite_id', '=', 'activites.id')
            ->join('projets', 'activites.projet_id', '=', 'projets.id')
            ->where('projets.workspace_id', $workspaceId)
            ->count();

        if ($totalTaches === 0) {
            return 0;
        }

        $completedTaches = DB::table('taches')
            ->join('activites', 'taches.activite_id', '=', 'activites.id')
            ->join('projets', 'activites.projet_id', '=', 'projets.id')
            ->where('projets.workspace_id', $workspaceId)
            ->where('taches.statut', 'termine')
            ->count();

        return (int) round(($completedTaches / $totalTaches) * 100);
    }

    /**
     * ✅ Get global recent activities
     */
    private function getGlobalRecentActivities(int $limit = 10): array
    {
        return DB::table('activity_log')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get workspace recent activities
     */
    private function getWorkspaceRecentActivities(int $workspaceId, int $limit = 10): array
    {
        return DB::table('activity_log')
            ->where('properties->workspace_id', $workspaceId)
            ->orWhereIn('subject_id', function ($query) use ($workspaceId) {
                $query->select('id')
                    ->from('projets')
                    ->where('workspace_id', $workspaceId);
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Apply filters to a query
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['workspace_id'])) {
            $query->inWorkspace($filters['workspace_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(fn ($q) => $q->where('nom', 'like', "%$search%")
                ->orWhere('code', 'like', "%$search%")
                ->orWhere('description', 'like', "%$search%"));
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['visibility']) && $filters['visibility'] !== 'all') {
            $query->where('visibility', $filters['visibility']);
        }

        if (! empty($filters['responsable_id'])) {
            $query->where('responsable_id', $filters['responsable_id']);
        }

        if (! empty($filters['tags'])) {
            $query->whereHas('tags', fn ($q) => $q->whereIn('projet_tags.id', (array) $filters['tags']));
        }

        if (! empty($filters['is_template'])) {
            $query->where('is_template', filter_var($filters['is_template'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['is_favorite'])) {
            $query->where('is_favorite', filter_var($filters['is_favorite'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['is_overdue'])) {
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

            $data['created_by'] = auth()->id();
            // Create project
            $projet = Projet::create($data);

            // Attach responsable as member with owner role
            if ($projet->responsable_id) {
                $ownerRoleId = Role::findByName('owner', 'web')->id;
                $projet->members()->attach($projet->responsable_id, [
                    'role_id' => $ownerRoleId,
                    'can_edit' => true,
                    'can_delete' => true,
                    'can_invite' => true,
                    'can_delete_member' => true,
                    'can_create_activity' => true,
                    'can_edit_activity' => true,
                    'can_delete_activity' => true,
                ]);
            }

            // Attach additional members
            if (! empty($members)) {
                foreach ($members as $member) {
                    if ($member['user_id'] != $projet->responsable_id) {
                        $roleName = $member['role'] ?? 'collaborateur';
                        $memberRoleId = Role::findByName($roleName, 'web')->id;
                        $projet->members()->attach($member['user_id'], [
                            'role_id' => $memberRoleId,
                            'can_edit' => $member['can_edit'] ?? false,
                            'can_delete' => $member['can_delete'] ?? false,
                            'can_invite' => $member['can_invite'] ?? false,
                            'can_delete_member' => $member['can_delete_member'] ?? false,
                            'can_create_activity' => $member['can_create_activity'] ?? false,
                            'can_edit_activity' => $member['can_edit_activity'] ?? false,
                            'can_delete_activity' => $member['can_delete_activity'] ?? false,
                        ]);
                    }
                }
            }

            // Attach tags
            if (! empty($tags)) {
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
            // ✅ Si le code n'est pas fourni ou est null, ne pas le modifier
            if (! isset($data['code']) || $data['code'] === null || $data['code'] === '') {
                unset($data['code']);
            }

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
            if (! isset($data['progression'])) {
                $projet->updateProgression();
            }

            return $projet->load(['responsable', 'members', 'tags', 'workspace', 'activites']);
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
            if (! isset($overrides['nom'])) {
                $newData['nom'] = $projet->nom.' (Copie)';
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
                    'role_id' => $member->pivot->role_id,
                    'can_edit' => $member->pivot->can_edit,
                    'can_delete' => $member->pivot->can_delete,
                    'can_invite' => $member->pivot->can_invite,
                    'can_delete_member' => $member->pivot->can_delete_member,
                    'can_create_activity' => $member->pivot->can_create_activity,
                    'can_edit_activity' => $member->pivot->can_edit_activity,
                    'can_delete_activity' => $member->pivot->can_delete_activity,
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
        $roleName = $permissions['role'] ?? 'collaborateur';
        $roleId = Role::findByName($roleName, 'web')->id;

        $projet->members()->attach($userId, [
            'role_id' => $roleId,
            'can_edit' => $permissions['can_edit'] ?? false,
            'can_delete' => $permissions['can_delete'] ?? false,
            'can_invite' => $permissions['can_invite'] ?? false,
            'can_delete_member' => $permissions['can_delete_member'] ?? false,
            'can_create_activity' => $permissions['can_create_activity'] ?? false,
            'can_edit_activity' => $permissions['can_edit_activity'] ?? false,
            'can_delete_activity' => $permissions['can_delete_activity'] ?? false,
        ]);
    }

    /**
     * Update member permissions.
     */
    public function updateMember(Projet $projet, int $userId, array $permissions): void
    {
        $pivotData = [];

        if (isset($permissions['role'])) {
            $pivotData['role_id'] = Role::findByName($permissions['role'], 'web')->id;
        }

        foreach (['can_edit', 'can_delete', 'can_invite', 'can_delete_member', 'can_create_activity', 'can_edit_activity', 'can_delete_activity'] as $col) {
            if (isset($permissions[$col])) {
                $pivotData[$col] = $permissions[$col];
            }
        }

        $projet->members()->updateExistingPivot($userId, $pivotData);
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
        $projet->update(['is_favorite' => ! $projet->is_favorite]);

        return $projet->fresh(['responsable', 'members', 'tags']);
    }

    /**
     * Get project statistics
     */
    public function getProjetStats(Projet $projet): array
    {
        return [
            'total_activites' => $projet->activites()->count(),
            'total_taches' => $projet->taches()->count(),
            'taches_terminees' => $projet->taches()->where('statut', 'termine')->count(),
            'taches_en_cours' => $projet->taches()->where('statut', 'en_cours')->count(),
            'taches_en_attente' => $projet->taches()->where('statut', 'en_attente')->count(),
            'progression' => $projet->progression,
            'membre_count' => $projet->members()->count(),
            'is_overdue' => $projet->is_overdue,
            'days_remaining' => $projet->days_remaining,
        ];
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
