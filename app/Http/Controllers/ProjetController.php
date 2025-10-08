<?php

namespace App\Http\Controllers;

use App\Http\Requests\Projet\StoreProjetRequest;
use App\Http\Requests\Projet\UpdateProjetRequest;
use App\Http\Resources\ProjetResource;
use App\Models\Projet;
use App\Services\ProjetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjetController extends Controller
{
    public function __construct(
        protected ProjetService $projetService
    ) {}

    /**
     * Display a listing of projects.
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

        $projets = $this->projetService->getUserProjets($request->user(), $filters);

        return ProjetResource::collection($projets);
    }

    /**
     * Store a newly created project.
     */
    public function store(StoreProjetRequest $request): JsonResponse
    {
        $this->authorize('create', Projet::class);

        $projet = $this->projetService->createProjet($request->validated());

        return response()->json([
            'message' => 'Projet créé avec succès.',
            'data' => new ProjetResource($projet),
        ], 201);
    }

    /**
     * Display the specified project.
     */
    public function show(Projet $projet): JsonResponse
    {
        $this->authorize('view', $projet);

        $projet->load(['responsable', 'members', 'tags']);
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

        $projet = $this->projetService->updateProjet($projet, $request->validated());

        return response()->json([
            'message' => 'Projet mis à jour avec succès.',
            'data' => new ProjetResource($projet),
        ]);
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Projet $projet): JsonResponse
    {
        $this->authorize('delete', $projet);

        $this->projetService->deleteProjet($projet);

        return response()->json([
            'message' => 'Projet supprimé avec succès.',
        ]);
    }

    /**
     * Archive a project.
     */
    public function archive(Projet $projet): JsonResponse
    {
        $this->authorize('archive', $projet);

        $projet = $this->projetService->archiveProjet($projet);

        return response()->json([
            'message' => 'Projet archivé avec succès.',
            'data' => new ProjetResource($projet),
        ]);
    }

    /**
     * Unarchive a project.
     */
    public function unarchive(Projet $projet): JsonResponse
    {
        $this->authorize('archive', $projet);

        $projet = $this->projetService->unarchiveProjet($projet);

        return response()->json([
            'message' => 'Projet désarchivé avec succès.',
            'data' => new ProjetResource($projet),
        ]);
    }

    /**
     * Complete a project.
     */
    public function complete(Projet $projet): JsonResponse
    {
        $this->authorize('update', $projet);

        $projet = $this->projetService->completeProjet($projet);

        return response()->json([
            'message' => 'Projet marqué comme terminé.',
            'data' => new ProjetResource($projet),
        ]);
    }

    /**
     * Clone a project.
     */
    public function clone(Request $request, Projet $projet): JsonResponse
    {
        $this->authorize('clone', $projet);

        $overrides = $request->only(['nom', 'date_debut', 'date_fin', 'responsable_id']);
        $newProjet = $this->projetService->cloneProjet($projet, $overrides);

        return response()->json([
            'message' => 'Projet cloné avec succès.',
            'data' => new ProjetResource($newProjet),
        ], 201);
    }

    /**
     * Toggle favorite status.
     */
    public function toggleFavorite(Projet $projet): JsonResponse
    {
        $this->authorize('view', $projet);

        $projet = $this->projetService->toggleFavorite($projet);

        return response()->json([
            'message' => $projet->is_favorite ? 'Projet ajouté aux favoris.' : 'Projet retiré des favoris.',
            'data' => new ProjetResource($projet),
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
            'role' => 'required|in:owner,admin,member,viewer',
            'can_edit' => 'nullable|boolean',
            'can_delete' => 'nullable|boolean',
            'can_invite' => 'nullable|boolean',
        ]);

        $this->projetService->addMember(
            $projet,
            $request->user_id,
            $request->only(['role', 'can_edit', 'can_delete', 'can_invite'])
        );

        return response()->json([
            'message' => 'Membre ajouté avec succès.',
        ]);
    }

    /**
     * Update member permissions.
     */
    public function updateMember(Request $request, Projet $projet, int $userId): JsonResponse
    {
        $this->authorize('manageMembers', $projet);

        $request->validate([
            'role' => 'sometimes|in:owner,admin,member,viewer',
            'can_edit' => 'nullable|boolean',
            'can_delete' => 'nullable|boolean',
            'can_invite' => 'nullable|boolean',
        ]);

        $this->projetService->updateMember(
            $projet,
            $userId,
            $request->only(['role', 'can_edit', 'can_delete', 'can_invite'])
        );

        return response()->json([
            'message' => 'Permissions mises à jour avec succès.',
        ]);
    }

    /**
     * Remove member from project.
     */
    public function removeMember(Projet $projet, int $userId): JsonResponse
    {
        $this->authorize('manageMembers', $projet);

        $this->projetService->removeMember($projet, $userId);

        return response()->json([
            'message' => 'Membre retiré avec succès.',
        ]);
    }

    /**
     * Get dashboard statistics.
     */
    public function dashboardStats(Request $request): JsonResponse
    {
        $stats = $this->projetService->getDashboardStats($request->user());

        return response()->json([
            'data' => $stats,
        ]);
    }
}
