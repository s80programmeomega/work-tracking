<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\TacheStatut;
use App\Models\Tache;
use App\Services\TacheService;
use App\Http\Resources\TacheResource;
use App\Models\Activite;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TacheController extends Controller
{
    public function __construct(protected TacheService $tacheService)
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Liste toutes les tâches avec filtres
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Tache::class);

        $filters = $request->only([
            'activite_id',
            'statut',
            'priorite',
            'user_id',
            'overdue',
            'archive_status',
            'week_number',
            'year',
            'validation_status',
        ]);

        $taches = $this->tacheService->getAllTaches($request->user(), $filters);

        return response()->json([
            'data' => TacheResource::collection($taches),
            'meta' => [
                'total' => $taches->count(),
                'filtered' => count($filters) > 0,
            ]
        ]);
    }

    /**
     * ✅ CORRIGÉ : Kanban pour une activité avec logs détaillés
     */
    public function forActivite(Request $request, int $activiteId): JsonResponse
    {
        try {
            $activite = Activite::with(['projet', 'membres'])->findOrFail($activiteId);
            $user = $request->user();

            Log::info('Chargement Kanban', [
                'activite_id' => $activiteId,
                'user_id' => $user->id,
                'is_super_admin' => $user->isSuperAdmin()
            ]);

            // ✅ Vérification d'accès simplifiée
            if (!$user->isSuperAdmin() && !$this->canUserAccessActivite($user, $activite)) {
                Log::warning('Accès refusé au kanban', [
                    'user_id' => $user->id,
                    'activite_id' => $activiteId
                ]);
                return response()->json([
                    'message' => 'Accès non autorisé',
                    'a_faire' => [],
                    'en_cours' => [],
                    'termine' => [],
                    'stats' => ['total' => 0, 'a_faire' => 0, 'en_cours' => 0, 'termine' => 0]
                ], 403);
            }

            // ✅ Récupérer le kanban
            $kanban = $this->tacheService->getKanbanForActivite($activiteId);

            // ✅ GARANTIR la structure complète
            $kanbanData = [
                'a_faire' => $kanban['a_faire'] ?? [],
                'en_cours' => $kanban['en_cours'] ?? [],
                'termine' => $kanban['termine'] ?? [],
            ];

            // ✅ Calculer les stats
            $stats = [
                'total' => count($kanbanData['a_faire']) + count($kanbanData['en_cours']) + count($kanbanData['termine']),
                'a_faire' => count($kanbanData['a_faire']),
                'en_cours' => count($kanbanData['en_cours']),
                'termine' => count($kanbanData['termine']),
            ];

            Log::info('Kanban chargé avec succès', [
                'activite_id' => $activiteId,
                'stats' => $stats
            ]);

            return response()->json([
                'a_faire' => TacheResource::collection($kanbanData['a_faire']),
                'en_cours' => TacheResource::collection($kanbanData['en_cours']),
                'termine' => TacheResource::collection($kanbanData['termine']),
                'stats' => $stats
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Activité non trouvée', ['activite_id' => $activiteId]);
            return response()->json([
                'message' => 'Activité non trouvée',
                'a_faire' => [],
                'en_cours' => [],
                'termine' => [],
                'stats' => ['total' => 0, 'a_faire' => 0, 'en_cours' => 0, 'termine' => 0]
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erreur chargement Kanban', [
                'activite_id' => $activiteId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Erreur lors du chargement du kanban',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
                'a_faire' => [],
                'en_cours' => [],
                'termine' => [],
                'stats' => ['total' => 0, 'a_faire' => 0, 'en_cours' => 0, 'termine' => 0]
            ], 500);
        }
    }

    /**
     * ✅ Mes tâches (assignées à moi)
     */
    public function myTasks(Request $request): JsonResponse
    {
        $filters = $request->only(['week_number', 'year', 'activite_id']);
        $filters['user_id'] = $request->user()->id;

        $taches = $this->tacheService->getAllTaches($request->user(), $filters);

        return response()->json([
            'data' => TacheResource::collection($taches),
        ]);
    }

    /**
     * ✅ Tâches assignées à moi
     */
    public function assignedToMe(Request $request): JsonResponse
    {
        $taches = Tache::assignedTo($request->user()->id)
            ->with(['activite', 'labels', 'assignees'])
            ->active()
            ->ordered()
            ->get();

        return response()->json([
            'data' => TacheResource::collection($taches),
        ]);
    }

    /**
     * ✅ Tâches en attente de validation (que JE peux valider)
     */
    public function pending(Request $request): JsonResponse
    {
        $user = $request->user();

        // N1: Tâches des activités où je suis responsable OU validateur
        $pendingN1 = Tache::pendingValidationN1()
            ->whereHas('activite', function ($q) use ($user) {
                $q->where('responsable_id', $user->id)
                    ->orWhereHas('membres', function ($mq) use ($user) {
                        $mq->where('user_id', $user->id)
                            ->where('can_validate_results', true);
                    });
            })
            ->with([
                'activite.projet',
                'assignees',
                'labels',
                'validatedN1By',
                'validatedN2By'
            ])
            ->get()
            ->filter(function ($tache) use ($user) {
                // Double vérification avec Policy
                return Gate::allows('validateN1', $tache);
            });

        // N2: Tâches des projets où je suis responsable
        $pendingN2 = Tache::pendingValidationN2()
            ->whereHas('activite.projet', function ($q) use ($user) {
                $q->where('responsable_id', $user->id);
            })
            ->with([
                'activite.projet',
                'assignees',
                'labels',
                'validatedN1By',
                'validatedN2By'
            ])
            ->get()
            ->filter(function ($tache) use ($user) {
                return Gate::allows('validateN2', $tache);
            });

        return response()->json([
            'pending_n1' => TacheResource::collection($pendingN1),
            'pending_n2' => TacheResource::collection($pendingN2),
            'counts' => [
                'n1' => $pendingN1->count(),
                'n2' => $pendingN2->count(),
                'total' => $pendingN1->count() + $pendingN2->count(),
            ]
        ]);
    }

    /**
     * ✅ Tâches en retard
     */
    public function overdue(Request $request): JsonResponse
    {
        $user = $request->user();

        $taches = Tache::overdue()
            ->where(function ($q) use ($user) {
                $q->whereHas('assignees', function ($aq) use ($user) {
                    $aq->where('user_id', $user->id);
                })
                    ->orWhereHas('activite', function ($actq) use ($user) {
                        $actq->where('responsable_id', $user->id);
                    });
            })
            ->with(['activite.projet', 'labels', 'assignees'])
            ->ordered()
            ->get();

        return response()->json([
            'data' => TacheResource::collection($taches),
            'count' => $taches->count(),
        ]);
    }

    /**
     * ✅ NOUVEAU : Helper pour vérifier l'accès à une activité
     */
    private function canUserAccessActivite(User $user, Activite $activite): bool
    {
        // Responsable de l'activité
        if ($activite->responsable_id === $user->id) {
            return true;
        }

        // Responsable du projet
        if ($activite->projet && $activite->projet->responsable_id === $user->id) {
            return true;
        }

        // Membre de l'activité
        if ($activite->membres()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Membre du workspace (owner/admin)
        if ($activite->projet && $activite->projet->workspace) {
            $workspace = $activite->projet->workspace;
            $member = $workspace->membres()->where('user_id', $user->id)->first();
            if ($member && in_array($member->pivot->role, ['owner', 'admin'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Créer une tâche
     */

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'activite_id' => 'required|exists:activites,id',
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'objectif' => 'nullable|string',
            'indicateurs_resultats' => 'nullable|string',
            'statut' => 'required|in:a_faire,en_cours,termine',
            'priorite' => 'required|in:faible,moyenne,elevee,critique',
            'echeance' => 'nullable|date|after:today',
            'date_debut' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0|max:999.99',
            'couleur' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'assignee_ids' => 'nullable|array',
            'assignee_ids.*' => 'exists:users,id',
            'label_ids' => 'nullable|array',
            'label_ids.*' => 'exists:labels,id',
            'validation_n1_required' => 'boolean',
            'validation_n2_required' => 'boolean',
            'visibility' => 'nullable|in:public,private,members_only',
        ]);

        try {
            $activite = Activite::with(['projet.workspace', 'membres'])->findOrFail($validated['activite_id']);
            $user = $request->user();

            Log::info('Tentative de création de tâche', [
                'user_id' => $user->id,
                'activite_id' => $validated['activite_id'],
                'titre' => $validated['titre']
            ]);

            // ✅ VÉRIFICATION COMPLÈTE DES PERMISSIONS
            $canCreate = $user->isSuperAdmin() ||
                $activite->responsable_id === $user->id ||
                ($activite->projet && $activite->projet->responsable_id === $user->id) ||
                $activite->membres()
                    ->where('user_id', $user->id)
                    ->where(function ($query) {
                        $query->where('role', 'responsable')
                            ->orWhere('can_create_tasks', true);
                    })
                    ->exists();

            // ✅ Si l'utilisateur est membre du workspace (owner/admin), autoriser
            if (!$canCreate && $activite->projet && $activite->projet->workspace) {
                $workspace = $activite->projet->workspace;
                $workspaceMember = $workspace->membres()->where('user_id', $user->id)->first();
                if ($workspaceMember && in_array($workspaceMember->pivot->role, ['owner', 'admin'])) {
                    $canCreate = true;
                }
            }

            if (!$canCreate) {
                Log::warning('Permission refusée pour création de tâche', [
                    'user_id' => $user->id,
                    'activite_id' => $validated['activite_id'],
                    'is_responsable' => $activite->responsable_id === $user->id,
                    'is_membre' => $activite->membres()->where('user_id', $user->id)->exists()
                ]);

                return response()->json([
                    'message' => 'Vous n\'avez pas la permission de créer des tâches pour cette activité.',
                    'error' => 'permission_denied',
                    'debug' => [
                        'user_id' => $user->id,
                        'activite_responsable_id' => $activite->responsable_id,
                        'is_membre' => $activite->membres()->where('user_id', $user->id)->exists(),
                        'membre_role' => $activite->membres()->where('user_id', $user->id)->first()?->pivot->role ?? 'non-membre'
                    ]
                ], 403);
            }

            // ✅ Créer la tâche
            $tache = $this->tacheService->createTache($validated, $user);

            Log::info('Tâche créée avec succès', [
                'tache_id' => $tache->id,
                'user_id' => $user->id
            ]);

            return response()->json([
                'message' => 'Tâche créée avec succès.',
                'data' => new TacheResource($tache),
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Activité non trouvée', ['activite_id' => $validated['activite_id']]);
            return response()->json([
                'message' => 'Activité non trouvée',
                'error' => 'not_found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erreur création tâche', [
                'user_id' => $user->id ?? null,
                'activite_id' => $validated['activite_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la création de la tâche',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue'
            ], 500);
        }
    }


    /**
     * ✅ NOUVEAU : Endpoint pour vérifier les permissions d'une activité
     */
    public function checkPermissions(Request $request, int $activiteId): JsonResponse
    {
        try {
            $activite = Activite::with(['projet.workspace', 'membres'])->findOrFail($activiteId);
            $user = $request->user();

            $membre = $activite->membres()->where('user_id', $user->id)->first();

            // Vérifier les permissions workspace
            $workspaceRole = null;
            if ($activite->projet && $activite->projet->workspace) {
                $workspaceMember = $activite->projet->workspace->membres()->where('user_id', $user->id)->first();
                $workspaceRole = $workspaceMember ? $workspaceMember->pivot->role : null;
            }

            $permissions = [
                'can_view' => $this->canUserAccessActivite($user, $activite),
                'can_create_tasks' => $user->isSuperAdmin() ||
                    $activite->responsable_id === $user->id ||
                    ($activite->projet && $activite->projet->responsable_id === $user->id) ||
                    ($membre && ($membre->pivot->role === 'responsable' || $membre->pivot->can_create_tasks)) ||
                    in_array($workspaceRole, ['owner', 'admin']),
                'can_edit_tasks' => $user->isSuperAdmin() ||
                    $activite->responsable_id === $user->id ||
                    ($membre && $membre->pivot->can_edit_tasks),
                'can_delete_tasks' => $user->isSuperAdmin() ||
                    $activite->responsable_id === $user->id ||
                    ($membre && $membre->pivot->can_delete_tasks),
                'can_validate_results' => $user->isSuperAdmin() ||
                    $activite->responsable_id === $user->id ||
                    ($membre && $membre->pivot->can_validate_results),
                'can_manage_members' => $user->isSuperAdmin() ||
                    $activite->responsable_id === $user->id ||
                    ($activite->projet && $activite->projet->responsable_id === $user->id),
            ];

            return response()->json([
                'permissions' => $permissions,
                'user_role' => $membre ? $membre->pivot->role : 'non-membre',
                'workspace_role' => $workspaceRole,
                'is_responsable' => $activite->responsable_id === $user->id,
                'is_projet_responsable' => $activite->projet && $activite->projet->responsable_id === $user->id,
                'is_super_admin' => $user->isSuperAdmin(),
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur vérification permissions', [
                'activite_id' => $activiteId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'message' => 'Erreur lors de la vérification des permissions'
            ], 500);
        }
    }
    /**
     * Afficher une tâche
     */
    public function show(Tache $tache): JsonResponse
    {
        $this->authorize('view', $tache);

        $tache->load([
            'activite.projet',
            'assignees',
            'validatedN1By',
            'validatedN2By',
            'labels',
            'resultats',
            'sousTaches',
            'dependencies',
            'createdBy'
        ]);

        return response()->json([
            'data' => new TacheResource($tache),
        ]);
    }

    /**
     * Mettre à jour une tâche
     */
    public function update(Request $request, Tache $tache): JsonResponse
    {
        $this->authorize('update', $tache);

        $validated = $request->validate([
            'titre' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'objectif' => 'nullable|string',
            'indicateurs_resultats' => 'nullable|string',
            'statut' => 'sometimes|in:a_faire,en_cours,termine',
            'priorite' => 'sometimes|in:faible,moyenne,elevee,critique',
            'echeance' => 'nullable|date',
            'date_debut' => 'nullable|date',
            'taux_realisation' => 'sometimes|integer|min:0|max:100',
            'estimated_hours' => 'nullable|numeric|min:0|max:999.99',
            'actual_hours' => 'nullable|numeric|min:0|max:999.99',
            'couleur' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'commentaire' => 'nullable|string',
            'assignee_ids' => 'nullable|array',
            'label_ids' => 'nullable|array',
            'visibility' => 'nullable|in:public,private,members_only',
        ]);

        $tache = $this->tacheService->updateTache($tache, $validated);

        return response()->json([
            'message' => 'Tâche mise à jour avec succès.',
            'data' => new TacheResource($tache),
        ]);
    }

    /**
     * Supprimer une tâche
     */
    public function destroy(Tache $tache): JsonResponse
    {
        $this->authorize('delete', $tache);

        $this->tacheService->deleteTache($tache);

        return response()->json([
            'message' => 'Tâche supprimée avec succès.',
        ]);
    }

    /**
     * ✅ Marquer comme terminé
     */
    public function complete(Request $request, Tache $tache): JsonResponse
    {
        $this->authorize('complete', $tache);

        try {
            $tache->markAsCompleted($request->user());

            return response()->json([
                'message' => 'Tâche marquée comme terminée. En attente de validation.',
                'data' => new TacheResource($tache->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }


    /**
     * ✅ Valider N1 (Responsable activité)
     */
    public function validateN1(Request $request, Tache $tache): JsonResponse
    {
        $this->authorize('validateN1', $tache);

        $validated = $request->validate([
            'commentaire' => 'nullable|string|max:1000',
        ]);

        try {
            $tache->validateN1($request->user(), $validated['commentaire'] ?? null);

            return response()->json([
                'message' => 'Tâche validée (N1) avec succès.',
                'data' => new TacheResource($tache->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * ✅ Valider N2 (Responsable projet)
     */
    public function validateN2(Request $request, Tache $tache): JsonResponse
    {
        $this->authorize('validateN2', $tache);

        $validated = $request->validate([
            'commentaire' => 'nullable|string|max:1000',
        ]);

        try {
            $tache->validateN2($request->user(), $validated['commentaire'] ?? null);

            return response()->json([
                'message' => 'Tâche validée (N2) avec succès. Validation complète.',
                'data' => new TacheResource($tache->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * ✅ Déplacer tâche (Kanban)
     */
    /**
     * ✅ Déplacer tâche (Kanban)
     */
    public function move(Request $request, Tache $tache): JsonResponse
    {
        $this->authorize('update', $tache);

        $validated = $request->validate([
            'statut' => 'required|in:a_faire,en_cours,termine',
            'position' => 'required|integer|min:0',
        ]);

        $tache = $this->tacheService->moveTache(
            $tache,
            TacheStatut::from($validated['statut']),
            $validated['position']
        );

        return response()->json([
            'message' => 'Tâche déplacée avec succès.',
            'data' => new TacheResource($tache),
        ]);
    }

    /**
     * ✅ Archiver tâche
     */
    public function archive(Tache $tache): JsonResponse
    {
        $this->authorize('archive', $tache);

        $tache->archive();

        return response()->json([
            'message' => 'Tâche archivée avec succès.',
            'data' => new TacheResource($tache),
        ]);
    }

    /**
     * ✅ Désarchiver tâche
     */
    public function unarchive(Tache $tache): JsonResponse
    {
        $this->authorize('archive', $tache);

        $tache->unarchive();

        return response()->json([
            'message' => 'Tâche désarchivée avec succès.',
            'data' => new TacheResource($tache),
        ]);
    }

    /**
     * ✅ Assigner utilisateur
     */
    public function assignUser(Request $request, Tache $tache): JsonResponse
    {
        $this->authorize('assignUsers', $tache);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|in:assignee,validator,observer',
            'can_edit' => 'boolean',
            'can_complete' => 'boolean',
            'can_validate' => 'boolean',
        ]);

        $tache = $this->tacheService->assignUser($tache, $validated);

        return response()->json([
            'message' => 'Utilisateur assigné avec succès.',
            'data' => new TacheResource($tache),
        ]);
    }

    /**
     * ✅ Désassigner utilisateur
     */
    public function unassignUser(Tache $tache, int $userId): JsonResponse
    {
        $this->authorize('assignUsers', $tache);

        $tache = $this->tacheService->unassignUser($tache, $userId);

        return response()->json([
            'message' => 'Utilisateur désassigné avec succès.',
            'data' => new TacheResource($tache),
        ]);
    }

    /**
     * ✅ Sous-tâches
     */
    public function subTasks(Tache $tache): JsonResponse
    {
        $sousTaches = $tache->sousTaches()
            ->with(['assignees', 'labels'])
            ->ordered()
            ->get();

        return response()->json([
            'data' => TacheResource::collection($sousTaches),
        ]);
    }

    /**
     * ✅ Créer sous-tâche
     */
    public function createSubTask(Request $request, Tache $tache): JsonResponse
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priorite' => 'required|in:faible,moyenne,elevee,critique',
            'echeance' => 'nullable|date',
            'assignee_ids' => 'nullable|array',
        ]);

        $validated['activite_id'] = $tache->activite_id;
        $validated['parent_tache_id'] = $tache->id;

        $sousTache = $this->tacheService->createTache($validated, $request->user());

        return response()->json([
            'message' => 'Sous-tâche créée avec succès.',
            'data' => new TacheResource($sousTache),
        ], 201);
    }



    /**
     * ✅ Mon rapport hebdomadaire
     */
    public function myWeeklyReport(Request $request): JsonResponse
    {
        $weekNumber = $request->input('week_number');
        $year = $request->input('year');

        $report = $this->tacheService->getWeeklyReport(
            $request->user(),
            $weekNumber,
            $year
        );

        return response()->json($report);
    }

    /**
     * ✅ Rapport hebdomadaire d'un utilisateur (managers)
     */
    public function userWeeklyReport(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        // Vérifier permissions: doit être manager de l'utilisateur
        if (!$request->user()->isSuperAdmin()) {
            $hasAccess = Activite::where('responsable_id', $request->user()->id)
                ->whereHas('membres', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->exists();

            if (!$hasAccess) {
                return response()->json([
                    'message' => 'Vous n\'avez pas accès aux rapports de cet utilisateur'
                ], 403);
            }
        }

        $weekNumber = $request->input('week_number');
        $year = $request->input('year');

        $report = $this->tacheService->getWeeklyReport($user, $weekNumber, $year);

        return response()->json($report);
    }

    /**
     * ✅ Performance d'équipe
     */
    public function teamPerformance(Request $request, int $activiteId): JsonResponse
    {
        $activite = Activite::findOrFail($activiteId);

        // Vérifier permissions
        if (
            !$activite->canUserEdit($request->user()) &&
            $activite->responsable_id !== $request->user()->id
        ) {
            return response()->json([
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $weekNumber = $request->input('week_number');
        $year = $request->input('year');

        $performance = $this->tacheService->getTeamPerformance($activiteId, $weekNumber, $year);

        return response()->json($performance);
    }

    /**
     * ✅ Dashboard d'évaluation
     */
    public function evaluationDashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $weekInfo = TacheService::getWeekInfo();

        // Mes tâches de la semaine
        $myTasks = Tache::assignedTo($user->id)
            ->forWeek($weekInfo['week_number'], $weekInfo['year'])
            ->with(['activite', 'labels'])
            ->get();

        // Validations en attente
        $pendingN1 = Tache::pendingValidationN1()
            ->whereHas('activite', function ($q) use ($user) {
                $q->where('responsable_id', $user->id)
                    ->orWhereHas('membres', function ($mq) use ($user) {
                        $mq->where('user_id', $user->id)
                            ->where('can_validate_results', true);
                    });
            })
            ->count();

        $pendingN2 = Tache::pendingValidationN2()
            ->whereHas('activite.projet', function ($q) use ($user) {
                $q->where('responsable_id', $user->id);
            })
            ->count();

        return response()->json([
            'week_info' => $weekInfo,
            'my_tasks' => [
                'total' => $myTasks->count(),
                'completed' => $myTasks->where('statut', TacheStatut::TERMINE)->count(),
                'in_progress' => $myTasks->where('statut', TacheStatut::EN_COURS)->count(),
                'pending' => $myTasks->where('statut', TacheStatut::A_FAIRE)->count(),
                'overdue' => $myTasks->filter->is_overdue->count(),
            ],
            'pending_validations' => [
                'n1' => $pendingN1,
                'n2' => $pendingN2,
                'total' => $pendingN1 + $pendingN2,
            ],
        ]);
    }

    /**
     * ✅ Export PDF du rapport hebdomadaire
     */
    public function exportWeeklyReportPdf(Request $request): Response
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'week_number' => 'nullable|integer|min:1|max:53',
            'year' => 'nullable|integer|min:2020',
        ]);

        $userId = $validated['user_id'] ?? $request->user()->id;
        $user = User::findOrFail($userId);

        // Vérifier permissions
        if ($userId !== $request->user()->id && !$request->user()->isSuperAdmin()) {
            $hasAccess = Activite::where('responsable_id', $request->user()->id)
                ->whereHas('membres', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->exists();

            if (!$hasAccess) {
                abort(403, 'Accès non autorisé');
            }
        }

        $weekNumber = $validated['week_number'] ?? now()->weekOfYear;
        $year = $validated['year'] ?? now()->year;

        $report = $this->tacheService->getWeeklyReport($user, $weekNumber, $year);

        // Générer PDF avec DomPDF ou Laravel Snappy
        $pdf = PDF::loadView('reports.weekly-tasks', [
            'report' => $report,
            'user' => $user,
        ]);

        return $pdf->download("rapport-hebdomadaire-{$user->nom}-S{$weekNumber}-{$year}.pdf");
    }

}