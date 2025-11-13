<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activite\StoreActiviteRequest;
use App\Http\Requests\Activite\UpdateActiviteRequest;
use App\Http\Resources\ActiviteResource;
use App\Models\Activite;
use App\Models\Projet;
use App\Models\User;
use App\Services\ActiviteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActiviteController extends Controller
{
    public function __construct(
        protected ActiviteService $activiteService
    ) {}

    /**
     * Display a listing of ALL activities (SUPER ADMIN ONLY).
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
            'workspace_id', // ✅ Permet de filtrer par workspace
        ]);

        $activites = $this->activiteService->getAllActivites($filters);

        return ActiviteResource::collection($activites);
    }

    /**
     * Get activities for a specific project.
     */
    public function forProjet(Request $request, int $projetId): AnonymousResourceCollection
    {
        $projet = Projet::findOrFail($projetId);
        $user = $request->user();

        // Vérifier l'accès au projet
        if (!$user->isSuperAdmin() && !$projet->hasAccess($user)) {
            abort(403, 'Vous n\'avez pas accès à ce projet');
        }

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

        // ✅ Filtre par workspace actuel
        $workspaceId = $request->input('workspace_id') ?? $request->user()->current_workspace_id;
        if ($workspaceId) {
            $filters['workspace_id'] = $workspaceId;
        }

        $activites = $this->activiteService->getUserActivites($request->user(), $filters);

        return ActiviteResource::collection($activites);
    }

    /**
     * Get overdue activities.
     */
    public function enRetard(Request $request): JsonResponse
    {
        $user = $request->user();
        $workspaceId = $request->input('workspace_id') ?? $user->current_workspace_id;

        $query = Activite::with(['projet', 'responsable'])
            ->whereHas('projet', function ($q) use ($user, $workspaceId) {
                $q->accessibleBy($user->id);
                if ($workspaceId) {
                    $q->where('workspace_id', $workspaceId);
                }
            })
            ->overdue();

        $activites = $query->paginate($request->get('per_page', 15));

        return response()->json($activites);
    }

    /**
     * Store a newly created activity.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'projet_id' => 'required|exists:projets,id',
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'responsable_id' => 'required|exists:users,id',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'status' => 'in:active,archived',
            'progression' => 'integer|min:0|max:100',
            'couleur' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'metadata' => 'nullable|array',
        ]);

        $user = $request->user();
        $projet = Projet::findOrFail($validated['projet_id']);

        // ✅ Vérifier que l'utilisateur a accès au projet
        if (!$user->isSuperAdmin() && !$projet->hasAccess($user)) {
            return response()->json([
                'message' => 'Vous n\'avez pas accès à ce projet'
            ], 403);
        }

        // ✅ Vérifier que l'utilisateur peut créer des activités
        if (!$user->isSuperAdmin() && !$projet->canUserEdit($user)) {
            return response()->json([
                'message' => 'Vous n\'avez pas la permission de créer des activités dans ce projet'
            ], 403);
        }

        // ✅ VALIDATION CRITIQUE : Le responsable doit être membre du projet
        $responsable = User::find($validated['responsable_id']);
        if (!$projet->isMember($responsable) && $projet->responsable_id !== $responsable->id) {
            throw ValidationException::withMessages([
                'responsable_id' => ['Le responsable doit être membre du projet']
            ]);
        }

        // Définir l'ordre (dernière position)
        $validated['ordre'] = Activite::where('projet_id', $validated['projet_id'])->max('ordre') + 1;
        $validated['created_by'] = $user->id;

        DB::beginTransaction();
        try {
            $activite = Activite::create($validated);
            
            DB::commit();

            return response()->json([
                'message' => 'Activité créée avec succès',
                'data' => $activite->load(['projet', 'responsable'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la création de l\'activité',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified activity.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $activite = Activite::with(['projet', 'responsable', 'taches'])->findOrFail($id);
        $user = $request->user();

        // Vérifier l'accès au projet parent
        if (!$user->isSuperAdmin() && !$activite->projet->hasAccess($user)) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        // Statistiques
        $stats = [
            'taches_count' => $activite->taches()->count(),
            'taches_terminees' => $activite->taches()->where('statut', 'termine')->count(),
            'taches_en_cours' => $activite->taches()->where('statut', 'en_cours')->count(),
            'taches_en_attente' => $activite->taches()->where('statut', 'en_attente')->count(),
        ];

        return response()->json([
            'data' => $activite,
            'stats' => $stats,
        ]);
    }

    /**
     * Update the specified activity.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $activite = Activite::findOrFail($id);
        $user = $request->user();
        $projet = $activite->projet;

        // Vérifier l'accès au projet
        if (!$user->isSuperAdmin() && !$projet->hasAccess($user)) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        // Vérifier les permissions d'édition
        if (!$user->isSuperAdmin() && !$projet->canUserEdit($user) && $activite->responsable_id !== $user->id) {
            return response()->json([
                'message' => 'Vous n\'avez pas la permission de modifier cette activité'
            ], 403);
        }

        $validated = $request->validate([
            'nom' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'responsable_id' => 'sometimes|required|exists:users,id',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'status' => 'in:active,archived',
            'progression' => 'integer|min:0|max:100',
            'couleur' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'metadata' => 'nullable|array',
        ]);

        // ✅ Si changement de responsable, vérifier qu'il est membre du projet
        if (isset($validated['responsable_id']) && $validated['responsable_id'] !== $activite->responsable_id) {
            $newResponsable = User::find($validated['responsable_id']);
            if (!$projet->isMember($newResponsable) && $projet->responsable_id !== $newResponsable->id) {
                throw ValidationException::withMessages([
                    'responsable_id' => ['Le nouveau responsable doit être membre du projet']
                ]);
            }
        }

        DB::beginTransaction();
        try {
            $activite->update($validated);
            
            DB::commit();

            return response()->json([
                'message' => 'Activité mise à jour avec succès',
                'data' => $activite->load(['projet', 'responsable'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la mise à jour de l\'activité',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified activity.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $activite = Activite::findOrFail($id);
        $user = $request->user();
        $projet = $activite->projet;

        // Vérifier l'accès et les permissions
        if (!$user->isSuperAdmin() && (!$projet->hasAccess($user) || !$projet->canUserDelete($user))) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        // Vérifier s'il y a des tâches associées
        $tacheCount = $activite->taches()->count();
        if ($tacheCount > 0) {
            return response()->json([
                'message' => "Impossible de supprimer cette activité car elle contient {$tacheCount} tâche(s)"
            ], 422);
        }

        $activite->delete();

        return response()->json([
            'message' => 'Activité supprimée avec succès'
        ]);
    }

    /**
     * Toggle archive/unarchive activity.
     */
    public function toggleArchive(Request $request, $id): JsonResponse
    {
        $activite = Activite::findOrFail($id);
        $user = $request->user();

        if (!$user->isSuperAdmin() && (!$activite->projet->hasAccess($user) || !$activite->projet->canUserEdit($user))) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        if ($activite->status === 'archived') {
            $activite->unarchive();
            $message = 'Activité désarchivée';
        } else {
            $activite->archive();
            $message = 'Activité archivée';
        }

        return response()->json([
            'message' => $message,
            'data' => $activite->fresh()
        ]);
    }

    /**
     * Archive activity.
     */
    public function archive(Request $request, Activite $activite): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->isSuperAdmin() && (!$activite->projet->hasAccess($user) || !$activite->projet->canUserEdit($user))) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $activite->archive();

        return response()->json([
            'message' => 'Activité archivée avec succès.',
            'data' => new ActiviteResource($activite->fresh()),
        ]);
    }

    /**
     * Unarchive activity.
     */
    public function unarchive(Request $request, Activite $activite): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->isSuperAdmin() && (!$activite->projet->hasAccess($user) || !$activite->projet->canUserEdit($user))) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $activite->unarchive();

        return response()->json([
            'message' => 'Activité désarchivée avec succès.',
            'data' => new ActiviteResource($activite->fresh()),
        ]);
    }

    /**
     * Duplicate activity.
     */
    public function duplicate(Request $request, $id): JsonResponse
    {
        $activite = Activite::findOrFail($id);
        $user = $request->user();

        if (!$user->isSuperAdmin() && (!$activite->projet->hasAccess($user) || !$activite->projet->canUserEdit($user))) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $validated = $request->validate([
            'nom' => 'nullable|string|max:255',
            'projet_id' => 'nullable|exists:projets,id',
        ]);

        DB::beginTransaction();
        try {
            $newActivite = $activite->replicate();
            $newActivite->nom = $validated['nom'] ?? $activite->nom . ' (Copie)';
            $newActivite->code = null; // Le code sera régénéré
            $newActivite->progression = 0;
            
            if (isset($validated['projet_id'])) {
                $newProjet = Projet::findOrFail($validated['projet_id']);
                if (!$user->isSuperAdmin() && !$newProjet->hasAccess($user)) {
                    throw new \Exception('Accès non autorisé au projet cible');
                }
                $newActivite->projet_id = $validated['projet_id'];
            }

            $newActivite->ordre = Activite::where('projet_id', $newActivite->projet_id)->max('ordre') + 1;
            $newActivite->save();

            DB::commit();

            return response()->json([
                'message' => 'Activité dupliquée avec succès',
                'data' => $newActivite->load(['projet', 'responsable'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Reorder activities.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'activite_ids' => 'required|array',
            'activite_ids.*' => 'exists:activites,id',
        ]);

        DB::beginTransaction();
        try {
            Activite::reorder($validated['activite_ids']);
            DB::commit();

            return response()->json(['message' => 'Ordre mis à jour']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de la réorganisation'], 422);
        }
    }

    /**
     * Get available members for an activity.
     * Returns members of the parent project.
     */
    public function availableMembers(Request $request, $projetId): JsonResponse
    {
        $projet = Projet::findOrFail($projetId);
        $user = $request->user();

        if (!$user->isSuperAdmin() && !$projet->hasAccess($user)) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        // ✅ Récupérer tous les membres du projet
        $members = $projet->members()
            ->select('users.id', 'users.nom', 'users.email')
            ->get();
        
        // ✅ Ajouter le responsable du projet s'il n'est pas déjà membre
        if ($projet->responsable && !$members->contains('id', $projet->responsable->id)) {
            $members->prepend([
                'id' => $projet->responsable->id,
                'nom' => $projet->responsable->nom,
                'email' => $projet->responsable->email,
            ]);
        }

        return response()->json(['data' => $members]);
    }
}