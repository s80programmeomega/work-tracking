<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SousTacheResource;
use App\Models\SousTache;
use App\Models\Tache;
use App\Models\User;
use App\Services\PermissionService;
use App\Services\SousTacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SousTacheController extends Controller
{
    public function __construct(
        protected SousTacheService $sousTacheService,
        protected PermissionService $permissionService,
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * GET /taches/{tache}/sous-taches
     */
    public function index(Tache $tache): JsonResponse
    {
        $this->authorize('view', $tache);

        $sousTaches = $tache->sousTaches()
            ->with(['responsable'])
            ->ordered()
            ->get();

        return response()->json([
            'data' => SousTacheResource::collection($sousTaches),
        ]);
    }

    /**
     * POST /taches/{tache}/sous-taches
     */
    public function store(Request $request, Tache $tache): JsonResponse
    {
        $this->authorize('createSubtask', $tache);

        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'responsable_id' => 'nullable|exists:users,id',
            'date_echeance' => 'nullable|date',
            'poids' => 'nullable|integer|min:0|max:100',
            'ordre' => 'nullable|integer|min:0',
            'validation_n0_required' => 'boolean',
            'validation_n1_required' => 'boolean',
            'validation_n2_required' => 'boolean',
        ]);

        try {
            $sousTache = $this->sousTacheService->create($tache, $validated, $request->user());

            return response()->json([
                'message' => __('sous_taches.success.created'),
                'data' => new SousTacheResource($sousTache),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * PUT /sous-taches/{sousTache}
     */
    public function update(Request $request, SousTache $sousTache): JsonResponse
    {
        $this->authorize('update', $sousTache);

        $validated = $request->validate([
            'titre' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'responsable_id' => 'nullable|exists:users,id',
            'statut' => 'sometimes|in:a_faire,en_cours,en_retard,termine,a_refaire,annule',
            'progression' => 'sometimes|integer|min:0|max:100',
            'date_echeance' => 'nullable|date',
            'poids' => 'sometimes|integer|min:0|max:100',
            'ordre' => 'sometimes|integer|min:0',
            'validation_n0_required' => 'boolean',
            'validation_n1_required' => 'boolean',
            'validation_n2_required' => 'boolean',
        ]);

        try {
            $sousTache = $this->sousTacheService->update($sousTache, $validated, $request->user());

            return response()->json([
                'message' => __('sous_taches.success.updated'),
                'data' => new SousTacheResource($sousTache),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * DELETE /sous-taches/{sousTache}
     */
    public function destroy(Request $request, SousTache $sousTache): JsonResponse
    {
        $this->authorize('delete', $sousTache);

        $this->sousTacheService->delete($sousTache, $request->user());

        return response()->json(['message' => __('sous_taches.success.deleted')]);
    }

    /**
     * POST /sous-taches/{sousTache}/intervenants
     */
    public function assignIntervenant(Request $request, SousTache $sousTache): JsonResponse
    {
        $user = $request->user();

        $this->authorize('assign', $sousTache);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'can_edit' => 'boolean',
            'can_complete' => 'boolean',
        ]);

        $intervenant = User::findOrFail($validated['user_id']);

        $this->sousTacheService->assignIntervenant($sousTache, $intervenant, $validated, $user);

        return response()->json([
            'message' => __('sous_taches.success.intervenant_assigned'),
        ]);
    }
}
