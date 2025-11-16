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
    ) {
    }

    /**
     * Display a listing of ALL activities (SUPER ADMIN ONLY).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $filters = $request->only([
            'search',
            'projet_id',
            'responsable_id',
            'status',
            'is_overdue',
            'per_page',
            'workspace_id', // ✅ Permet de filtrer par workspace
        ]);

        // ✅ Si Super Admin : toutes les activités
        if ($user->isSuperAdmin()) {
            $activites = $this->activiteService->getAllActivites($filters);
        } else {
            // ✅ Sinon : activités des projets accessibles
            $activites = $this->activiteService->getAccessibleActivites($user, $filters);
        }

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
     * ✅ AMÉLIORATION : Filtrage par workspace et permissions
     */
    public function myActivites(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'search',
            'projet_id',
            'status',
            'is_overdue',
            'per_page',
            'workspace_id',
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
     * ✅ AMÉLIORATION : Filtrage par workspace
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
            'membres' => 'nullable|array',
            'membres.*.user_id' => 'required|exists:users,id',
            'membres.*.role' => 'required|in:responsable,collaborator,viewer',
            'membres.*.can_create_tasks' => 'boolean',
            'membres.*.can_edit_tasks' => 'boolean',
            'membres.*.can_delete_tasks' => 'boolean',
            'membres.*.can_validate_results' => 'boolean',
            'membres.*.can_assign_users' => 'boolean',
        ]);

        $user = $request->user();
        $projet = Projet::findOrFail($validated['projet_id']);

        // Vérifier l'accès
        if (!$user->isSuperAdmin() && !$projet->hasAccess($user)) {
            return response()->json([
                'message' => 'Vous n\'avez pas accès à ce projet'
            ], 403);
        }

        if (!$user->isSuperAdmin() && !$projet->canUserEdit($user)) {
            return response()->json([
                'message' => 'Vous n\'avez pas la permission de créer des activités'
            ], 403);
        }

        // Validation responsable
        $responsable = User::find($validated['responsable_id']);
        if (!$projet->isMember($responsable) && $projet->responsable_id !== $responsable->id) {
            throw ValidationException::withMessages([
                'responsable_id' => ['Le responsable doit être membre du projet']
            ]);
        }

        // Validation membres
        if (isset($validated['membres'])) {
            foreach ($validated['membres'] as $membre) {
                $membreUser = User::find($membre['user_id']);
                if (!$projet->isMember($membreUser) && $projet->responsable_id !== $membreUser->id) {
                    throw ValidationException::withMessages([
                        'membres' => ['Tous les membres doivent appartenir au projet']
                    ]);
                }
            }
        }

        $validated['ordre'] = Activite::where('projet_id', $validated['projet_id'])->max('ordre') + 1;
        $validated['created_by'] = $user->id;

        DB::beginTransaction();
        try {
            $activite = Activite::create($validated);

            // ✅ CORRECTION : Ajouter TOUJOURS le responsable comme membre
            $activite->membres()->attach($validated['responsable_id'], [
                'role' => 'responsable',
                'can_create_tasks' => true,
                'can_edit_tasks' => true,
                'can_delete_tasks' => true,
                'can_validate_results' => true,
                'can_assign_users' => true,
            ]);

            // Ajouter les autres membres
            if (isset($validated['membres'])) {
                foreach ($validated['membres'] as $membre) {
                    // Ne pas ajouter deux fois le responsable
                    if ($membre['user_id'] != $validated['responsable_id']) {
                        $activite->membres()->attach($membre['user_id'], [
                            'role' => $membre['role'],
                            'can_create_tasks' => $membre['can_create_tasks'] ?? false,
                            'can_edit_tasks' => $membre['can_edit_tasks'] ?? false,
                            'can_delete_tasks' => $membre['can_delete_tasks'] ?? false,
                            'can_validate_results' => $membre['can_validate_results'] ?? false,
                            'can_assign_users' => $membre['can_assign_users'] ?? false,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Activité créée avec succès',
                'data' => $activite->load(['projet', 'responsable', 'membres'])
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
     * ✅ AMÉLIORATION : Inclure les membres dans la réponse
     */
    public function show(Request $request, $id): JsonResponse
    {
        $activite = Activite::with(['projet', 'responsable', 'taches', 'membres'])->findOrFail($id);
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

        // ✅ AMÉLIORATION : Vérifier aussi si l'utilisateur est membre de l'activité avec can_edit_tasks
        $isMemberWithEditPermission = $activite->membres()
            ->where('user_id', $user->id)
            ->wherePivot('can_edit_tasks', true)
            ->exists();

        // Vérifier les permissions d'édition
        if (
            !$user->isSuperAdmin()
            && !$projet->canUserEdit($user)
            && $activite->responsable_id !== $user->id
            && !$isMemberWithEditPermission
        ) {
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
                'data' => $activite->load(['projet', 'responsable', 'membres'])
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

        if (!$user->isSuperAdmin() && !$activite->projet->hasAccess($user)) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $validated = $request->validate([
            'nom' => 'nullable|string|max:255',
            'projet_id' => 'nullable|exists:projets,id',
            'copy_members' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $newActivite = $activite->replicate();
            $newActivite->nom = $validated['nom'] ?? $activite->nom . ' (Copie)';
            $newActivite->code = null;
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

            // ✅ TOUJOURS copier le responsable
            $newActivite->membres()->attach($newActivite->responsable_id, [
                'role' => 'responsable',
                'can_create_tasks' => true,
                'can_edit_tasks' => true,
                'can_delete_tasks' => true,
                'can_validate_results' => true,
                'can_assign_users' => true,
            ]);

            // Copier les autres membres si demandé
            if ($validated['copy_members'] ?? true) {
                foreach ($activite->membres as $membre) {
                    if ($membre->id != $newActivite->responsable_id) {
                        $newActivite->membres()->attach($membre->id, [
                            'role' => $membre->pivot->role,
                            'can_create_tasks' => $membre->pivot->can_create_tasks,
                            'can_edit_tasks' => $membre->pivot->can_edit_tasks,
                            'can_delete_tasks' => $membre->pivot->can_delete_tasks,
                            'can_validate_results' => $membre->pivot->can_validate_results,
                            'can_assign_users' => $membre->pivot->can_assign_users,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Activité dupliquée avec succès',
                'data' => $newActivite->load(['projet', 'responsable', 'membres'])
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

    // ==================== ✅ NOUVELLES MÉTHODES POUR GESTION DES MEMBRES ====================

    /**
     * Get members of an activity
     */
    public function getMembers(Request $request, $id): JsonResponse
    {
        $activite = Activite::with('membres')->findOrFail($id);
        $user = $request->user();

        // Vérifier l'accès à l'activité
        if (!$user->isSuperAdmin() && !$activite->projet->hasAccess($user)) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        return response()->json([
            'data' => $activite->membres->map(function ($membre) {
                return [
                    'id' => $membre->id,
                    'nom' => $membre->nom,
                    'email' => $membre->email,
                    'role' => $membre->pivot->role,
                    'can_create_tasks' => $membre->pivot->can_create_tasks,
                    'can_edit_tasks' => $membre->pivot->can_edit_tasks,
                    'can_delete_tasks' => $membre->pivot->can_delete_tasks,
                    'can_validate_results' => $membre->pivot->can_validate_results,
                    'can_assign_users' => $membre->pivot->can_assign_users,
                ];
            })
        ]);
    }

    /**
     * Add a member to an activity
     */
    public function addMember(Request $request, $id): JsonResponse
    {
        $activite = Activite::findOrFail($id);
        $user = $request->user();
        $projet = $activite->projet;

        // Vérifier les permissions
        $canManage = $user->isSuperAdmin()
            || $projet->canUserEdit($user)
            || $activite->responsable_id === $user->id
            || $activite->membres()->where('user_id', $user->id)
                ->wherePivot('can_assign_users', true)
                ->exists();

        if (!$canManage) {
            return response()->json(['message' => 'Permission refusée'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:responsable,collaborator,viewer',
            'can_create_tasks' => 'boolean',
            'can_edit_tasks' => 'boolean',
            'can_delete_tasks' => 'boolean',
            'can_validate_results' => 'boolean',
            'can_assign_users' => 'boolean',
        ]);

        // Vérifier que le membre est dans le projet
        $membreUser = User::find($validated['user_id']);
        if (!$projet->isMember($membreUser) && $projet->responsable_id !== $membreUser->id) {
            return response()->json([
                'message' => 'Le membre doit appartenir au projet'
            ], 422);
        }

        // Vérifier si déjà membre
        if ($activite->membres()->where('user_id', $validated['user_id'])->exists()) {
            return response()->json([
                'message' => 'Ce membre est déjà assigné à l\'activité'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $activite->membres()->attach($validated['user_id'], [
                'role' => $validated['role'],
                'can_create_tasks' => $validated['can_create_tasks'] ?? false,
                'can_edit_tasks' => $validated['can_edit_tasks'] ?? false,
                'can_delete_tasks' => $validated['can_delete_tasks'] ?? false,
                'can_validate_results' => $validated['can_validate_results'] ?? false,
                'can_assign_users' => $validated['can_assign_users'] ?? false,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Membre ajouté avec succès',
                'data' => $activite->load('membres')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de l\'ajout du membre',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update member permissions
     */
    public function updateMember(Request $request, $activiteId, $userId): JsonResponse
    {
        $activite = Activite::findOrFail($activiteId);
        $user = $request->user();

        // Vérifier les permissions
        $canManage = $user->isSuperAdmin()
            || $activite->projet->canUserEdit($user)
            || $activite->responsable_id === $user->id
            || $activite->membres()->where('user_id', $user->id)
                ->wherePivot('can_assign_users', true)
                ->exists();

        if (!$canManage) {
            return response()->json(['message' => 'Permission refusée'], 403);
        }

        $validated = $request->validate([
            'role' => 'sometimes|in:responsable,collaborator,viewer',
            'can_create_tasks' => 'sometimes|boolean',
            'can_edit_tasks' => 'sometimes|boolean',
            'can_delete_tasks' => 'sometimes|boolean',
            'can_validate_results' => 'sometimes|boolean',
            'can_assign_users' => 'sometimes|boolean',
        ]);

        // Vérifier que le membre existe
        if (!$activite->membres()->where('user_id', $userId)->exists()) {
            return response()->json([
                'message' => 'Ce membre n\'est pas assigné à l\'activité'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $activite->membres()->updateExistingPivot($userId, $validated);

            DB::commit();

            return response()->json([
                'message' => 'Permissions mises à jour',
                'data' => $activite->load('membres')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a member from an activity
     */
    public function removeMember(Request $request, $activiteId, $userId): JsonResponse
    {
        $activite = Activite::findOrFail($activiteId);
        $user = $request->user();

        // Vérifier les permissions
        $canManage = $user->isSuperAdmin()
            || $activite->projet->canUserEdit($user)
            || $activite->responsable_id === $user->id
            || $activite->membres()->where('user_id', $user->id)
                ->wherePivot('can_assign_users', true)
                ->exists();

        if (!$canManage) {
            return response()->json(['message' => 'Permission refusée'], 403);
        }

        // Ne pas permettre de retirer le responsable
        if ($activite->responsable_id == $userId) {
            return response()->json([
                'message' => 'Impossible de retirer le responsable de l\'activité'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $activite->membres()->detach($userId);

            DB::commit();

            return response()->json([
                'message' => 'Membre retiré avec succès'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors du retrait du membre',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tasks of an activity
     * ✅ Méthode existante mais ajout de vérifications
     */
    public function getTaches(Request $request, $id): JsonResponse
    {
        $activite = Activite::with('taches.assignee')->findOrFail($id);
        $user = $request->user();

        // Vérifier l'accès
        if (!$user->isSuperAdmin() && !$activite->projet->hasAccess($user)) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        return response()->json([
            'data' => $activite->taches
        ]);
    }
}