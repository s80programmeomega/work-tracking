<?php

namespace App\Http\Controllers;

use App\Http\Requests\Activite\StoreActiviteRequest;
use App\Http\Requests\Activite\UpdateActiviteRequest;
use App\Http\Resources\ActiviteResource;
use App\Models\Activite;
use App\Services\ActiviteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActiviteController extends Controller
{
    public function __construct(
        protected ActiviteService $activiteService
    ) {}

    /**
     * Display a listing of activities.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'search',
            'projet_id',
            'responsable_id',
            'status',
            'is_overdue',
            'per_page',
        ]);

        $activites = $this->activiteService->getAllActivites($filters);

        return ActiviteResource::collection($activites);
    }

    /**
     * Get activities for a specific project.
     */
    public function forProjet(Request $request, int $projetId): AnonymousResourceCollection
    {
        $filters = $request->only([
            'search',
            'responsable_id',
            'status',
            'is_overdue',
            'per_page',
        ]);

        $activites = $this->activiteService->getActivitiesForProjet($projetId, $filters);

        return ActiviteResource::collection($activites);
    }

    /**
     * Get current user's activities.
     */
    public function myActivites(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'search',
            'projet_id',
            'status',
            'is_overdue',
            'per_page',
        ]);

        $activites = $this->activiteService->getUserActivites($request->user(), $filters);

        return ActiviteResource::collection($activites);
    }

    /**
     * Store a newly created activity.
     */
    public function store(StoreActiviteRequest $request): JsonResponse
    {
        $activite = $this->activiteService->createActivite($request->validated());

        return response()->json([
            'message' => 'Activité créée avec succès.',
            'data' => new ActiviteResource($activite),
        ], 201);
    }

    /**
     * Display the specified activity.
     */
    public function show(Activite $activite): JsonResponse
    {
        $activite->load(['projet', 'responsable']);
        $stats = $this->activiteService->getActiviteStats($activite);

        return response()->json([
            'data' => new ActiviteResource($activite),
            'stats' => $stats,
        ]);
    }

    /**
     * Update the specified activity.
     */
    public function update(UpdateActiviteRequest $request, Activite $activite): JsonResponse
    {
        $activite = $this->activiteService->updateActivite($activite, $request->validated());

        return response()->json([
            'message' => 'Activité mise à jour avec succès.',
            'data' => new ActiviteResource($activite),
        ]);
    }

    /**
     * Remove the specified activity.
     */
    public function destroy(Activite $activite): JsonResponse
    {
        $this->activiteService->deleteActivite($activite);

        return response()->json([
            'message' => 'Activité supprimée avec succès.',
        ]);
    }

    /**
     * Archive activity.
     */
    public function archive(Activite $activite): JsonResponse
    {
        $activite = $this->activiteService->archiveActivite($activite);

        return response()->json([
            'message' => 'Activité archivée avec succès.',
            'data' => new ActiviteResource($activite),
        ]);
    }

    /**
     * Unarchive activity.
     */
    public function unarchive(Activite $activite): JsonResponse
    {
        $activite = $this->activiteService->unarchiveActivite($activite);

        return response()->json([
            'message' => 'Activité désarchivée avec succès.',
            'data' => new ActiviteResource($activite),
        ]);
    }

    /**
     * Duplicate activity.
     */
    public function duplicate(Request $request, Activite $activite): JsonResponse
    {
        $overrides = $request->only(['nom', 'projet_id']);
        $newActivite = $this->activiteService->duplicateActivite($activite, $overrides);

        return response()->json([
            'message' => 'Activité dupliquée avec succès.',
            'data' => new ActiviteResource($newActivite),
        ], 201);
    }

    /**
     * Reorder activities (drag & drop).
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['required', 'exists:activites,id'],
        ]);

        $this->activiteService->reorderActivites($request->ordered_ids);

        return response()->json([
            'message' => 'Activités réordonnées avec succès.',
        ]);
    }
}
