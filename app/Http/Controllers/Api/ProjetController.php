<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projet\StoreProjetRequest;
use App\Http\Requests\Projet\UpdateProjetRequest;
use App\Http\Resources\ProjetResource;
use App\Models\Projet;
use App\Models\User;
use App\Services\ProjetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\FacadesLog;
use App\Models\Activite;
use App\Models\Tache;

class ProjetController extends Controller
{

    public function __construct(
        protected ProjetService $projetService
    ) {
    }

    /**
     * Display a listing of ALL projects (SUPER ADMIN ONLY).
     * Shows projects from ALL workspaces.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // Cette route est protégée par le middleware super_admin
        $filters = $request->only([
            'search',
            'status',
            'visibility',
            'responsable_id',
            'tags',
            'is_template',
            'is_favorite',
            'is_overdue',
            'per_page',
            'workspace_id', // ✅ Permet au super_admin de filtrer par workspace
        ]);

        // Si le super_admin spécifie un workspace_id, on filtre par ce workspace
        if ($request->has('workspace_id') && $request->workspace_id) {
            $filters['workspace_id'] = $request->workspace_id;
        }

        $projets = $this->projetService->getAllProjets($filters);

        return ProjetResource::collection($projets);
    }

    /**
     * Get current user's projects (FOR ALL USERS).
     * Shows only projects where user is member or responsable.
     */
    public function myProjets(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'search',
            'status',
            'visibility',
            'tags',
            'is_template',
            'is_favorite',
            'is_overdue',
            'per_page',
        ]);

        // ✅ Filtre par workspace actuel
        $workspaceId = $request->input('workspace_id') ?? $request->user()->current_workspace_id;

        if (!$workspaceId) {
            return ProjetResource::collection([]);
        }

        $filters['workspace_id'] = $workspaceId;

        $projets = $this->projetService->getUserProjets($request->user(), $filters);

        return ProjetResource::collection($projets);
    }

    /**
     * Get archived projects.
     */
    public function archived(Request $request): AnonymousResourceCollection
    {
        // ✅ Utilise le workspace fourni ou le workspace actuel
        $workspaceId = $request->input('workspace_id') ?? $request->user()->current_workspace_id;

        if (!$workspaceId) {
            return ProjetResource::collection([]);
        }

        $query = Projet::where('workspace_id', $workspaceId)
            ->archived()
            ->with(['responsable', 'members', 'tags'])
            ->latest();

        // ✅ Si super_admin sans workspace_id spécifié, voir tous les projets archivés
        if ($request->user()->isSuperAdmin() && !$request->has('workspace_id')) {
            $query = Projet::archived()
                ->with(['responsable', 'members', 'tags', 'workspace'])
                ->latest();
        }

        $projets = $query->paginate($request->input('per_page', 15));

        return ProjetResource::collection($projets);
    }

    /**
     * Store a newly created project.
     */
    public function store(StoreProjetRequest $request): JsonResponse
    {
        $this->authorize('create', Projet::class);

        $data = $request->validated();

        // ✅ Utilise le workspace fourni ou le workspace actuel
        $workspaceId = $data['workspace_id'] ?? $request->user()->current_workspace_id;

        if (!$workspaceId) {
            return response()->json([
                'message' => 'Vous devez sélectionner un workspace actif.',
            ], 422);
        }

        // Vérifier que l'utilisateur a accès au workspace
        $workspace = \App\Models\Workspace::find($workspaceId);
        if (!$workspace) {
            return response()->json([
                'message' => 'Le workspace sélectionné n\'existe pas.',
            ], 404);
        }

        // ✅ Super admin peut créer dans n'importe quel workspace
        if (!$request->user()->isSuperAdmin() && !$workspace->hasAccess($request->user())) {
            return response()->json([
                'message' => 'Vous n\'avez pas accès à ce workspace.',
            ], 403);
        }

        // Vérifier que l'utilisateur peut créer des projets dans ce workspace
        if (!$request->user()->isSuperAdmin() && !$workspace->canCreateProjects($request->user())) {
            return response()->json([
                'message' => 'Vous n\'avez pas la permission de créer des projets dans ce workspace.',
            ], 403);
        }

        $data['workspace_id'] = $workspaceId;

        try {
            $projet = $this->projetService->createProjet($data);

            return response()->json([
                'message' => 'Projet créé avec succès.',
                'data' => new ProjetResource($projet),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création du projet.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified project.
     * ✅ AMÉLIORATION : Retourne uniquement les activités où l'utilisateur est membre ou responsable
     */
    public function show(Projet $projet): JsonResponse
    {
        $this->authorize('view', $projet);

        $userId = auth()->id();
        $user = auth()->user();

        // ✅ Charger le projet avec ses relations
        $projet->load([
            'responsable',
            // ✅ CORRECTION : Charger members avec withPivot, pas 'members.pivot'
            'members' => function ($query) {
                $query->withPivot([
                    'role',
                    'can_edit',
                    'can_delete',
                    'can_invite',
                    'can_delete_member',
                    'can_create_activity',
                    'can_edit_activity',
                    'can_delete_activity',
                    'created_at',
                ]);
            },
            'tags',
            'workspace',
            'activites' => function ($query) use ($userId, $user, $projet) {
                // ✅ FILTRE : Uniquement les activités où l'utilisateur est impliqué
                // SAUF si super admin OU responsable du projet
                if (!$user->isSuperAdmin() && $projet->responsable_id !== $userId) {
                    $query->where(function ($q) use ($userId) {
                        // Responsable de l'activité
                        $q->where('responsable_id', $userId)
                            // OU membre de l'activité
                            ->orWhereHas('membres', function ($mq) use ($userId) {
                            $mq->where('user_id', $userId);
                        });
                    });
                }

                // Charger les relations nécessaires
                $query->with([
                    'responsable:id,nom,prenom,email,avatar',
                    'membres' => function ($mq) {
                    $mq->select('users.id', 'users.nom', 'users.prenom', 'users.email', 'users.avatar')
                        ->withPivot([
                            'role',
                            'can_create_tasks',
                            'can_edit_tasks',
                            'can_delete_tasks',
                            'can_validate_results',
                            'can_assign_users',
                            'created_at'
                        ]);
                },
                    'taches' => function ($tq) {
                    $tq->select('id', 'activite_id', 'titre', 'statut', 'priorite', 'echeance');
                }
                ])
                    ->withCount('taches')
                    ->orderBy('ordre');
            }
        ]);

        // ✅ Calculer les statistiques basées sur les activités FILTRÉES
        $stats = [
            'activites_count' => $projet->activites->count(),
            'taches_count' => $projet->activites->sum('tache_count'),
            'taches_terminees' => $projet->activites->sum(function ($activite) {
                return $activite->taches->where('statut', 'completed')->count();
            }),
            'membres_count' => $projet->members->count(),
            'progression_moyenne' => $projet->activites->avg('progression') ?? 0,
        ];

        return response()->json([
            'success' => true,
            'data' => new ProjetResource($projet),
            'stats' => $stats,
        ]);
    }

    /**
     * Update the specified project.
     */
    public function update(UpdateProjetRequest $request, Projet $projet): JsonResponse
    {
        $this->authorize('update', $projet);

        try {
            $projet = $this->projetService->updateProjet($projet, $request->validated());

            return response()->json([
                'message' => 'Projet mis à jour avec succès.',
                'data' => new ProjetResource($projet),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour du projet.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Projet $projet): JsonResponse
    {
        $this->authorize('delete', $projet);

        try {
            $this->projetService->deleteProjet($projet);

            return response()->json([
                'message' => 'Projet supprimé avec succès.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression du projet.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Archive a project.
     */
    public function archive(Projet $projet): JsonResponse
    {
        $this->authorize('update', $projet);

        try {
            $projet = $this->projetService->archiveProjet($projet);

            return response()->json([
                'message' => 'Projet archivé avec succès.',
                'data' => new ProjetResource($projet),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'archivage du projet.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unarchive a project.
     */
    public function unarchive(Projet $projet): JsonResponse
    {
        $this->authorize('update', $projet);

        try {
            $projet = $this->projetService->unarchiveProjet($projet);

            return response()->json([
                'message' => 'Projet désarchivé avec succès.',
                'data' => new ProjetResource($projet),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors du désarchivage du projet.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Complete a project.
     */
    public function complete(Projet $projet): JsonResponse
    {
        $this->authorize('update', $projet);

        try {
            $projet = $this->projetService->completeProjet($projet);

            return response()->json([
                'message' => 'Projet marqué comme terminé.',
                'data' => new ProjetResource($projet),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la finalisation du projet.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clone a project.
     */
    public function clone(Request $request, Projet $projet): JsonResponse
    {
        $this->authorize('view', $projet);

        $request->validate([
            'nom' => 'nullable|string|max:255',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after:date_debut',
            'responsable_id' => 'nullable|exists:users,id',
            'workspace_id' => 'nullable|exists:workspaces,id', // ✅ Permet de cloner dans un autre workspace
        ]);

        $overrides = $request->only(['nom', 'date_debut', 'date_fin', 'responsable_id', 'workspace_id']);

        // ✅ Par défaut, clone dans le même workspace que l'original
        $overrides['workspace_id'] = $overrides['workspace_id'] ?? $projet->workspace_id;

        try {
            $newProjet = $this->projetService->cloneProjet($projet, $overrides);

            return response()->json([
                'message' => 'Projet cloné avec succès.',
                'data' => new ProjetResource($newProjet),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors du clonage du projet.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle favorite status.
     */
    public function toggleFavorite(Projet $projet): JsonResponse
    {
        $this->authorize('view', $projet);

        try {
            $projet = $this->projetService->toggleFavorite($projet);

            return response()->json([
                'message' => $projet->is_favorite ? 'Projet ajouté aux favoris.' : 'Projet retiré des favoris.',
                'data' => new ProjetResource($projet),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour des favoris.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get project members.
     */
    public function getMembers(Projet $projet): JsonResponse
    {
        $this->authorize('view', $projet);

        $members = $projet->members()
            ->withPivot([
                'role',
                'can_edit',
                'can_delete',
                'can_invite',
                'can_delete_member',
                'can_create_activity',
                'can_edit_activity',
                'can_delete_activity',
            ])
            ->get();

        return response()->json([
            'data' => $members,
        ]);
    }

    /**
     * Add member to project.
     */
    public function addMember(Request $request, Projet $projet): JsonResponse
    {
        $this->authorize('manageMembers', $projet);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:admin,member,viewer',
            'can_edit' => 'nullable|boolean',
            'can_delete' => 'nullable|boolean',
            'can_invite' => 'nullable|boolean',
        ]);

        // Vérifier que l'utilisateur fait partie du workspace
        $workspace = $projet->workspace;
        if (!$workspace->hasMember($request->user_id)) {
            return response()->json([
                'message' => 'L\'utilisateur doit d\'abord être membre du workspace.',
            ], 422);
        }

        // Vérifier si déjà membre
        if ($projet->members()->where('user_id', $request->user_id)->exists()) {
            return response()->json([
                'message' => 'Cet utilisateur est déjà membre du projet.',
            ], 422);
        }

        try {
            $this->projetService->addMember(
                $projet,
                $request->user_id,
                $request->only(['role', 'can_edit', 'can_delete', 'can_invite'])
            );

            return response()->json([
                'message' => 'Membre ajouté avec succès.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'ajout du membre.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update member permissions.
     */
    public function updateMember(Request $request, Projet $projet, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $projet);

        $request->validate([
            'role' => 'sometimes|in:admin,member,viewer',
            'can_edit' => 'nullable|boolean',
            'can_delete' => 'nullable|boolean',
            'can_invite' => 'nullable|boolean',
            'can_delete_member' => 'nullable|boolean',
            'can_create_activity' => 'nullable|boolean',
            'can_edit_activity' => 'nullable|boolean',
            'can_delete_activity' => 'nullable|boolean',
        ]);

        // Vérifier si membre
        if (!$projet->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Cet utilisateur n\'est pas membre du projet.',
            ], 404);
        }

        try {
            $this->projetService->updateMember(
                $projet,
                $user->id,
                $request->only([
                    'role',
                    'can_edit',
                    'can_delete',
                    'can_invite',
                    'can_delete_member',
                    'can_create_activity',
                    'can_edit_activity',
                    'can_delete_activity',
                ])
            );

            return response()->json([
                'message' => 'Permissions mises à jour avec succès.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour des permissions.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove member from project.
     */
    public function removeMember(Projet $projet, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $projet);

        // Vérifier si membre
        if (!$projet->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Cet utilisateur n\'est pas membre du projet.',
            ], 404);
        }

        try {
            // Révoquer tous les accès (tâches, documents, etc.)
            $projet->revokeAccess($user);

            return response()->json([
                'message' => 'Membre retiré avec succès. Tous ses accès ont été révoqués.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors du retrait du membre.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getMemberRemovalImpact(Projet $projet, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $projet);

        if (
            (int) $projet->responsable_id !== (int) $user->id &&
            !$projet->members()->where('users.id', $user->id)->exists()
        ) {
            return response()->json([
                'message' => 'Cet utilisateur n\'est pas rattaché à ce projet.',
            ], 404);
        }

        $activities = $projet->activites()
            ->where('responsable_id', $user->id)
            ->select('id', 'nom', 'code', 'responsable_id')
            ->orderBy('nom')
            ->get();

        $responsableTasks = Tache::query()
            ->whereHas('activite', function ($q) use ($projet) {
                $q->where('projet_id', $projet->id);
            })
            ->where('responsable_id', $user->id)
            ->select('id', 'titre', 'code', 'activite_id', 'responsable_id')
            ->orderBy('titre')
            ->get();

        $assignedTasksCount = DB::table('tache_user')
            ->join('taches', 'taches.id', '=', 'tache_user.tache_id')
            ->join('activites', 'activites.id', '=', 'taches.activite_id')
            ->where('activites.projet_id', $projet->id)
            ->where('tache_user.user_id', $user->id)
            ->count();

        $candidateIds = $projet->members()
            ->where('users.id', '!=', $user->id)
            ->pluck('users.id')
            ->toArray();

        if (
            $projet->responsable_id &&
            (int) $projet->responsable_id !== (int) $user->id &&
            !in_array((int) $projet->responsable_id, $candidateIds, true)
        ) {
            $candidateIds[] = (int) $projet->responsable_id;
        }

        if (
            $projet->workspace &&
            $projet->workspace->owner_id &&
            (int) $projet->workspace->owner_id !== (int) $user->id &&
            !in_array((int) $projet->workspace->owner_id, $candidateIds, true)
        ) {
            $candidateIds[] = (int) $projet->workspace->owner_id;
        }

        $candidates = User::query()
            ->whereIn('id', $candidateIds)
            ->select('id', 'nom', 'email', 'avatar')
            ->orderBy('nom')
            ->get();

        return response()->json([
            'data' => [
                'is_project_responsable' => (int) $projet->responsable_id === (int) $user->id,
                'activities_count' => $activities->count(),
                'activities' => $activities,
                'responsable_tasks_count' => $responsableTasks->count(),
                'responsable_tasks' => $responsableTasks,
                'assigned_tasks_count' => $assignedTasksCount,
                'requires_transfer' => (
                    (int) $projet->responsable_id === (int) $user->id ||
                    $activities->count() > 0 ||
                    $responsableTasks->count() > 0
                ),
                'candidates' => $candidates,
            ],
        ]);
    }

    public function removeMemberWithTransfer(Request $request, Projet $projet, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $projet);

        $request->validate([
            'transfer_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $isProjectResponsable = (int) $projet->responsable_id === (int) $user->id;
        $isProjectMember = $projet->members()->where('users.id', $user->id)->exists();

        if (!$isProjectResponsable && !$isProjectMember) {
            return response()->json([
                'message' => 'Cet utilisateur n\'est pas rattaché à ce projet.',
            ], 404);
        }

        $activitiesCount = $projet->activites()
            ->where('responsable_id', $user->id)
            ->count();

        $responsableTasksCount = Tache::query()
            ->whereHas('activite', function ($q) use ($projet) {
                $q->where('projet_id', $projet->id);
            })
            ->where('responsable_id', $user->id)
            ->count();

        $requiresTransfer = $isProjectResponsable || $activitiesCount > 0 || $responsableTasksCount > 0;

        $transferToUserId = $request->input('transfer_to_user_id');

        if ($requiresTransfer && !$transferToUserId) {
            return response()->json([
                'message' => 'Le transfert des responsabilités est obligatoire avant le retrait de ce membre.',
            ], 422);
        }

        if ($transferToUserId) {
            if ((int) $transferToUserId === (int) $user->id) {
                return response()->json([
                    'message' => 'Le remplaçant doit être différent du membre retiré.',
                ], 422);
            }

            $isValidReplacement =
                $projet->members()->where('users.id', $transferToUserId)->exists()
                || (int) optional($projet->responsable)->id === (int) $transferToUserId
                || (int) optional($projet->workspace)->owner_id === (int) $transferToUserId;

            if (!$isValidReplacement) {
                return response()->json([
                    'message' => 'Le remplaçant doit être membre du projet, responsable du projet ou propriétaire du workspace.',
                ], 422);
            }
        }

        try {
            DB::transaction(function () use ($projet, $user, $transferToUserId, $isProjectResponsable) {
                // 1. Transférer le responsable du projet
                if ($isProjectResponsable && $transferToUserId) {
                    $projet->update([
                        'responsable_id' => $transferToUserId,
                    ]);
                }

                // 2. Transférer les activités dont il est responsable
                $projet->activites()
                    ->where('responsable_id', $user->id)
                    ->update([
                        'responsable_id' => $transferToUserId,
                    ]);

                // 3. Transférer les tâches du projet dont il est responsable
                Tache::query()
                    ->whereHas('activite', function ($q) use ($projet) {
                        $q->where('projet_id', $projet->id);
                    })
                    ->where('responsable_id', $user->id)
                    ->update([
                        'responsable_id' => $transferToUserId,
                    ]);

                // 4. Retirer l'utilisateur des assignations de tâches du projet
                DB::table('tache_user')
                    ->join('taches', 'taches.id', '=', 'tache_user.tache_id')
                    ->join('activites', 'activites.id', '=', 'taches.activite_id')
                    ->where('activites.projet_id', $projet->id)
                    ->where('tache_user.user_id', $user->id)
                    ->delete();

                // 5. Retirer l'utilisateur des membres d'activités du projet si la table existe
                if (\Schema::hasTable('activite_user')) {
                    DB::table('activite_user')
                        ->join('activites', 'activites.id', '=', 'activite_user.activite_id')
                        ->where('activites.projet_id', $projet->id)
                        ->where('activite_user.user_id', $user->id)
                        ->delete();
                }

                // 6. Retirer du projet (pivot projet_user)
                $projet->members()->detach($user->id);
            });

            return response()->json([
                'message' => 'Membre retiré du projet avec succès.',
                'data' => [
                    'projet_id' => $projet->id,
                    'user_id' => $user->id,
                    'transferred_to_user_id' => $transferToUserId,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur retrait membre projet avec transfert', [
                'projet_id' => $projet->id,
                'user_id' => $user->id,
                'transfer_to_user_id' => $transferToUserId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors du retrait du membre.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get project activities.
     */
    public function getActivites(Projet $projet): JsonResponse
    {
        $this->authorize('view', $projet);

        $activites = $projet->activites()
            ->with(['responsable', 'taches'])
            ->orderBy('ordre')
            ->get();

        return response()->json([
            'data' => $activites,
        ]);
    }

    /**
     * Get project tasks.
     */
    public function getTaches(Projet $projet): JsonResponse
    {
        $this->authorize('view', $projet);

        $taches = $projet->taches()
            ->with(['activite', 'assignees', 'soustaches'])
            ->get();

        return response()->json([
            'data' => $taches,
        ]);
    }

    /**
     * Get project statistics.
     */
    public function getStatistics(Projet $projet): JsonResponse
    {
        $this->authorize('view', $projet);

        try {
            $stats = $this->projetService->getProjetStats($projet);

            return response()->json([
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors du chargement des statistiques.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get dashboard statistics for current workspace.
     * ✅ AMÉLIORÉ : Support multi-workspace
     */
    public function dashboardStats(Request $request): JsonResponse
    {
        // ✅ Utilise le workspace fourni ou le workspace actuel
        $workspaceId = $request->input('workspace_id') ?? $request->user()->current_workspace_id;

        // ✅ Si super_admin sans workspace spécifié, stats globales
        if ($request->user()->isSuperAdmin() && !$workspaceId) {
            try {
                $stats = $this->projetService->getGlobalDashboardStats();
                return response()->json([
                    'data' => $stats,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Erreur lors du chargement des statistiques globales.',
                    'error' => $e->getMessage(),
                    'data' => $this->getEmptyStats(),
                ], 500);
            }
        }

        if (!$workspaceId) {
            return response()->json([
                'data' => $this->getEmptyStats(),
            ]);
        }

        try {
            $stats = $this->projetService->getWorkspaceDashboardStats($workspaceId);

            return response()->json([
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors du chargement des statistiques.',
                'error' => $e->getMessage(),
                'data' => $this->getEmptyStats(),
            ], 500);
        }
    }

    /**
     * Get empty stats structure.
     */
    private function getEmptyStats(): array
    {
        return [
            'total_projets' => 0,
            'projets_actifs' => 0,
            'projets_termines' => 0,
            'projets_archives' => 0,
            'projets_en_retard' => 0,
            'projets_favoris' => 0,
            'total_activites' => 0,
            'total_taches' => 0,
            'taches_terminees' => 0,
            'taux_completion' => 0,
            'recent_activities' => [],
        ];
    }

    /**
     * Get project performance report for weekly evaluation.
     */
    public function performanceReport(Request $request, Projet $projet): JsonResponse
    {
        $this->authorize('view', $projet);

        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date', now()->startOfWeek());
        $endDate = $request->input('end_date', now()->endOfWeek());

        try {
            $report = $this->projetService->generatePerformanceReport($projet, $startDate, $endDate);

            return response()->json([
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la génération du rapport.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get tasks accessible by user in project.
     */
    public function accessibleTasks(Request $request, Projet $projet): JsonResponse
    {
        $this->authorize('view', $projet);

        try {
            $user = $request->user();
            $tasks = $projet->accessibleTachesFor($user)
                ->with(['assignees', 'activite', 'soustaches'])
                ->get();

            return response()->json([
                'data' => $tasks,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors du chargement des tâches accessibles.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function accessible()
    {
        $user = auth()->user();

        $projets = Projet::with('workspace')
            ->whereHas('workspace', function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('members', fn($m) => $m->where('user_id', $user->id));
            })
            ->get();

        return response()->json($projets);
    }




}