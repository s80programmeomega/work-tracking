<?php

namespace App\Http\Controllers;

use App\Enums\TacheStatut;
use App\Http\Requests\Tache\StoreTacheRequest;
use App\Http\Requests\Tache\UpdateTacheRequest;
use App\Http\Resources\TacheResource;
use App\Models\Tache;
use App\Services\TacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TacheController extends Controller
{
    public function __construct(
        protected TacheService $tacheService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all tasks with optional filters
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['activite_id', 'statut', 'priorite', 'user_id', 'overdue']);
        $taches = $this->tacheService->getAllTaches($filters);

        return TacheResource::collection($taches);
    }

    /**
     * Get Kanban board for specific activity
     */
    public function forActivite(int $activiteId): JsonResponse
    {
        $kanban = $this->tacheService->getKanbanForActivite($activiteId);

        return response()->json([
            'a_faire' => TacheResource::collection($kanban['a_faire']),
            'en_cours' => TacheResource::collection($kanban['en_cours']),
            'termine' => TacheResource::collection($kanban['termine']),
        ]);
    }

    /**
     * Get tasks assigned to current user
     */
    public function myTaches(Request $request): AnonymousResourceCollection
    {
        $taches = $this->tacheService->getMyTaches($request->user()->id);

        return TacheResource::collection($taches);
    }

    /**
     * Create a new task
     */
    public function store(StoreTacheRequest $request): JsonResponse
    {
        $tache = $this->tacheService->createTache($request->validated());

        return response()->json([
            'message' => 'Tâche créée avec succès.',
            'data' => new TacheResource($tache),
        ], 201);
    }

    /**
     * Get a specific task
     */
    public function show(Tache $tache): TacheResource
    {
        return new TacheResource($tache->load(['activite', 'assignees', 'validateur', 'labels', 'dependencies', 'dependents']));
    }

    /**
     * Update a task
     */
    public function update(UpdateTacheRequest $request, Tache $tache): JsonResponse
    {
        $tache = $this->tacheService->updateTache($tache, $request->validated());

        return response()->json([
            'message' => 'Tâche mise à jour avec succès.',
            'data' => new TacheResource($tache),
        ]);
    }

    /**
     * Delete a task
     */
    public function destroy(Tache $tache): JsonResponse
    {
        $this->tacheService->deleteTache($tache);

        return response()->json([
            'message' => 'Tâche supprimée avec succès.',
        ]);
    }

    /**
     * Move task to different status/position (Kanban)
     */
    public function move(Request $request, Tache $tache): JsonResponse
    {
        $request->validate([
            'statut' => ['required', 'in:' . implode(',', TacheStatut::values())],
            'ordre' => ['required', 'integer', 'min:0'],
        ]);

        $tache = $this->tacheService->moveTache(
            $tache,
            TacheStatut::from($request->statut),
            $request->ordre
        );

        return response()->json([
            'message' => 'Tâche déplacée avec succès.',
            'data' => new TacheResource($tache),
        ]);
    }

    /**
     * Reorder task within same status
     */
    public function reorder(Request $request, Tache $tache): JsonResponse
    {
        $request->validate([
            'ordre' => ['required', 'integer', 'min:0'],
        ]);

        $tache = $this->tacheService->moveTache(
            $tache,
            $tache->statut,
            $request->ordre
        );

        return response()->json([
            'message' => 'Tâche réordonnée avec succès.',
            'data' => new TacheResource($tache),
        ]);
    }

    /**
     * Duplicate a task
     */
    public function duplicate(Tache $tache): JsonResponse
    {
        $newTache = $this->tacheService->duplicateTache($tache);

        return response()->json([
            'message' => 'Tâche dupliquée avec succès.',
            'data' => new TacheResource($newTache),
        ], 201);
    }

    /**
     * Archive a task
     */
    public function archive(Tache $tache): JsonResponse
    {
        $tache = $this->tacheService->archiveTache($tache);

        return response()->json([
            'message' => 'Tâche archivée avec succès.',
        ]);
    }

    /**
     * Validate task by superior
     */
    public function validateTask(Request $request, Tache $tache): JsonResponse
    {
        $tache = $this->tacheService->validateTache($tache, $request->user());

        return response()->json([
            'message' => 'Tâche validée avec succès.',
            'data' => new TacheResource($tache),
        ]);
    }

    /**
     * Assign user to task
     */
    public function assignUser(Request $request, Tache $tache): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $tache = $this->tacheService->assignUser($tache, $request->user_id);

        return response()->json([
            'message' => 'Utilisateur assigné avec succès.',
            'data' => new TacheResource($tache),
        ]);
    }

    /**
     * Unassign user from task
     */
    public function unassignUser(Request $request, Tache $tache): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $tache = $this->tacheService->unassignUser($tache, $request->user_id);

        return response()->json([
            'message' => 'Utilisateur désassigné avec succès.',
            'data' => new TacheResource($tache),
        ]);
    }

    /**
     * Update task progress
     */
    public function updateProgress(Request $request, Tache $tache): JsonResponse
    {
        $request->validate([
            'taux_realisation' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $tache = $this->tacheService->updateProgress($tache, $request->taux_realisation);

        return response()->json([
            'message' => 'Progression mise à jour avec succès.',
            'data' => new TacheResource($tache),
        ]);
    }
}
