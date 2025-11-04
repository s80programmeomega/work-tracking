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

class ProjetController extends Controller
{

    public function __construct(
        protected ProjetService $projetService
    ) {
    }


    /**
     * Display a listing of projects for current workspace.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
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
        ]);

        // Filtrer par workspace actuel de l'utilisateur
        $workspaceId = $request->user()->current_workspace_id;

        if (!$workspaceId) {
            return ProjetResource::collection([]);
        }

        $filters['workspace_id'] = $workspaceId;

        $projets = $this->projetService->getAllProjets($filters);

        return ProjetResource::collection($projets);
    }

    /**
     * Get current user's projects.
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

        $workspaceId = $request->user()->current_workspace_id;

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
        $workspaceId = $request->user()->current_workspace_id;

        if (!$workspaceId) {
            return ProjetResource::collection([]);
        }

        $projets = Projet::where('workspace_id', $workspaceId)
            ->archived()
            ->with(['responsable', 'members', 'tags'])
            ->latest()
            ->paginate($request->input('per_page', 15));

        return ProjetResource::collection($projets);
    }

    /**
     * Store a newly created project.
     */
    public function store(StoreProjetRequest $request): JsonResponse
    {
        $this->authorize('create', Projet::class);

        $data = $request->validated();

        // S'assurer que le workspace_id correspond au workspace actuel
        $workspaceId = $request->user()->current_workspace_id;
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

        if (!$workspace->hasAccess($request->user())) {
            return response()->json([
                'message' => 'Vous n\'avez pas accès à ce workspace.',
            ], 403);
        }

        // Vérifier que l'utilisateur peut créer des projets dans ce workspace
        if (!$workspace->canCreateProjects($request->user())) {
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
     */
    public function show(Projet $projet): JsonResponse
    {
        $this->authorize('view', $projet);

        $projet->load([
            'responsable',
            'members',
            'tags',
            'activites' => function ($query) {
                $query->with([
                    'responsable',
                    'taches' => function ($q) {
                        $q->select('id', 'activite_id', 'titre', 'statut', 'priorite');
                    }
                ]);
            }
        ]);

        $stats = $this->projetService->getProjetStats($projet);

        return response()->json([
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
        ]);

        $overrides = $request->only(['nom', 'date_debut', 'date_fin', 'responsable_id']);
        $overrides['workspace_id'] = $request->user()->current_workspace_id;

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
            ->withPivot(['role', 'can_edit', 'can_delete', 'can_invite'])
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
                $request->only(['role', 'can_edit', 'can_delete', 'can_invite'])
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
     */
    public function dashboardStats(Request $request): JsonResponse
    {
        $workspaceId = $request->user()->current_workspace_id;

        if (!$workspaceId) {
            return response()->json([
                'message' => 'Aucun workspace sélectionné pour cet utilisateur',
                'data' => $this->getEmptyStats(),
            ], 400);
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
}