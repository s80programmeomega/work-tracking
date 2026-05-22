<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TacheResource;
use App\Http\Resources\TacheResultatResource;
use App\Models\Activite;
use App\Models\Tache;
use App\Models\TacheResultat;
use App\Models\User;
use App\Models\Workspace;
use App\Permissions\ContextualPermissionGate;
use App\Permissions\Permission;
use App\Services\EvaluationScoreService;
use App\Services\PermissionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🎯 EvaluationController - Gestion stricte des validations
 *
 * Règles :
 * - N1 : UNIQUEMENT le responsable de l'activité
 * - N2 : UNIQUEMENT le responsable du projet
 * - Consultation : Responsables N1 et N2 peuvent consulter tous les résultats
 */
class EvaluationController extends Controller
{
    public function __construct(
        protected EvaluationScoreService $scoreService,
    ) {}

    /**
     * Task 7: Pending validations dashboard data.
     *
     * Returns the N1 and N2 results awaiting the caller's action, sorted by
     * remaining deadline (most urgent first). Each row is enriched with:
     *   - hours_remaining       (float — based on N0 action_n0_le + workspace timeout setting)
     *   - is_urgent             (bool — true when hours_remaining < 24)
     *   - had_bypass            (bool — was bypass_active when the result reached N1)
     *   - escalades_abusives    (bool — does the assignee have the flag set on tache_user)
     *
     * Permission: EVALUATIONS_VIEW_PENDING (cadre/manager/owner/directeur).
     * Scope is enforced by responsable_id filters — cadre sees only their
     * activity's results, manager sees only their projects', owner sees all.
     */
    public function pendingValidationsDashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        // Hard role gate. Scoped data filtering still applies below.
        $gate = app(ContextualPermissionGate::class);
        $workspace = $user->currentWorkspace;
        if (! $user->isSuperAdmin()
            && (! $workspace || ! $gate->userCan($user, Permission::EVALUATIONS_VIEW_PENDING, $workspace))) {
            return response()->json([
                'success' => false,
                'message' => __('evaluation.errors.cannot_view_pending'),
            ], 403);
        }

        $timeoutHours = (int) ($workspace?->getSetting('validation_timeout_hours', 48) ?? 48);

        $pendingN1 = TacheResultat::query()
            ->with(['tache.activite.projet', 'user'])
            ->where('statut', 'en_validation_n1')
            ->whereHas('tache.activite', function ($q) use ($user) {
                if ($user->isSuperAdmin()) {
                    return;
                }
                // cadre/owner: see their activity's results
                $q->where('responsable_id', $user->id);
            })
            ->get();

        $pendingN2 = TacheResultat::query()
            ->with(['tache.activite.projet', 'user', 'validateurN1'])
            ->where('statut', 'en_validation_n2')
            ->whereHas('tache.activite.projet', function ($q) use ($user) {
                if ($user->isSuperAdmin()) {
                    return;
                }
                $q->where('responsable_id', $user->id);
            })
            ->get();

        $now = now();

        $enrich = function (TacheResultat $r) use ($now, $timeoutHours) {
            $deadlineBase = $r->action_n0_le ?? $r->soumis_le ?? $r->created_at;
            $deadline = $deadlineBase ? $deadlineBase->copy()->addHours($timeoutHours) : null;
            $hoursRemaining = $deadline ? $now->diffInHours($deadline, false) : null;

            $assigneePivot = $r->tache->assignees()
                ->where('user_id', $r->user_id)
                ->first()?->pivot;

            return [
                'id' => $r->id,
                'tache' => [
                    'id' => $r->tache->id,
                    'titre' => $r->tache->titre,
                    'code' => $r->tache->code,
                    'projet' => $r->tache->activite?->projet?->nom,
                    'activite' => $r->tache->activite?->nom,
                ],
                'assignee' => [
                    'id' => $r->user?->id,
                    'nom' => $r->user?->nom,
                    'email' => $r->user?->email,
                ],
                'statut' => $r->statut,
                'soumis_le' => $r->soumis_le?->toIso8601String(),
                'action_n0' => $r->action_n0,
                'action_n0_le' => $r->action_n0_le?->toIso8601String(),
                'had_bypass' => (bool) $r->bypass_active,
                'motif_bypass' => $r->motif_bypass,
                'commentaire_n0' => $r->commentaire_n0,
                'escalades_abusives' => (bool) ($assigneePivot->escalades_abusives ?? false),
                'bypass_count' => (int) ($assigneePivot->bypass_count ?? 0),
                'hours_remaining' => $hoursRemaining,
                'is_urgent' => $hoursRemaining !== null && $hoursRemaining < 24,
                'deadline' => $deadline?->toIso8601String(),
            ];
        };

        $sortByRemaining = function (array $row) {
            // null deadlines bubble to the end
            return $row['hours_remaining'] ?? PHP_INT_MAX;
        };

        $n1Rows = $pendingN1->map($enrich)->sortBy($sortByRemaining)->values();
        $n2Rows = $pendingN2->map($enrich)->sortBy($sortByRemaining)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'pending_n1' => $n1Rows,
                'pending_n2' => $n2Rows,
                'counts' => [
                    'n1' => $n1Rows->count(),
                    'n2' => $n2Rows->count(),
                    'urgent' => $n1Rows->where('is_urgent', true)->count() + $n2Rows->where('is_urgent', true)->count(),
                    'total' => $n1Rows->count() + $n2Rows->count(),
                ],
                'timeout_hours' => $timeoutHours,
            ],
        ]);
    }

    /**
     * Task 7: total evaluation score for the current user (or for a target user
     * when the caller has the right to view it).
     *
     * Scope rule (enforced here, not in the permission constant):
     *   - super_admin / directeur / owner: any user
     *   - manager / cadre: any user with EVALUATIONS_VIEW_SCORE in their scope
     *     (simplification for this iteration — caller passes target=user_id and we
     *     trust the workspace gate; Task 9 will refine to "only my assignees")
     *   - everyone else: only their own score
     */
    /**
     * Task 9: full agent evaluation sheet for a target user over a period.
     *
     * Endpoint: GET /api/evaluations/personnel/{user}/score
     *
     * Query params (all optional):
     *   - start: ISO date Y-m-d. Defaults to today-30 days.
     *   - end:   ISO date Y-m-d. Defaults to today.
     *
     * Permission check happens in two layers:
     *   1. Hard role gate: caller must have EVALUATIONS_VIEW_FICHE
     *      in their current workspace (covers manager/cadre/owner/observateur…).
     *   2. Per-target scope: PermissionService::canViewFicheEvaluation
     *      enforces "own only / cadre→assignees / manager→activity /
     *      owner→workspace" — the actual fiche of $target must be in
     *      the caller's reach.
     *
     * Response shape mirrors EvaluationScoreService::calculerScore() and
     * adds the target's identity for the UI header.
     */
    public function agentSheet(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'start' => 'sometimes|nullable|date_format:Y-m-d',
            'end' => 'sometimes|nullable|date_format:Y-m-d|after_or_equal:start',
        ]);

        $actor = $request->user();
        $workspace = $actor->currentWorkspace;

        if (! $workspace) {
            return response()->json([
                'success' => false,
                'message' => __('evaluation.errors.no_workspace'),
            ], 403);
        }

        $permissionService = app(PermissionService::class);
        $gate = app(ContextualPermissionGate::class);

        if (! $permissionService->canViewFicheEvaluation($actor, $user, $workspace, $gate)) {
            return response()->json([
                'success' => false,
                'message' => __('evaluation.errors.cannot_view_fiche'),
            ], 403);
        }

        $start = $validated['start'] ?? now()->subDays(30)->toDateString();
        $end = $validated['end'] ?? now()->toDateString();

        $sheet = $this->scoreService->calculerScore($user, $start, $end);

        // Indicators metadata for the UI header — actor permissions are
        // baked in so the frontend doesn't need to re-derive them.
        $canExport = $permissionService->canExportFicheEvaluation($actor, $user, $workspace, $gate);

        return response()->json([
            'success' => true,
            'data' => array_merge($sheet, [
                'user' => [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'nom_complet' => $user->nom_complet ?? trim(($user->prenom ?? '').' '.($user->nom ?? '')),
                    'email' => $user->email,
                    'avatar' => $user->avatar ?? null,
                ],
                'meta' => [
                    'can_export' => $canExport,
                    'is_self' => $actor->id === $user->id,
                ],
            ]),
        ]);
    }

    public function userScore(Request $request): JsonResponse
    {
        $user = $request->user();
        $workspace = $user->currentWorkspace;

        $gate = app(ContextualPermissionGate::class);
        $canViewScore = $user->isSuperAdmin()
            || ($workspace && $gate->userCan($user, Permission::EVALUATIONS_VIEW_SCORE, $workspace));

        if (! $canViewScore) {
            return response()->json(['success' => false, 'message' => __('evaluation.errors.cannot_view_score')], 403);
        }

        $targetId = (int) $request->query('user_id', $user->id);

        // Restrict non-privileged callers to their own score
        $isPrivileged = $user->isSuperAdmin()
            || ($workspace && $gate->userCan($user, Permission::EVALUATIONS_VIEW_PENDING, $workspace));

        if ($targetId !== $user->id && ! $isPrivileged) {
            return response()->json(['success' => false, 'message' => __('evaluation.errors.cannot_view_others_score')], 403);
        }

        $target = User::findOrFail($targetId);

        $start = $request->query('start');
        $end = $request->query('end');
        $total = $this->scoreService->totalForUser($target, $start, $end);

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $target->id,
                'user_nom' => $target->nom,
                'total' => $total,
                'period' => [
                    'start' => $start ?? now()->startOfMonth()->toDateString(),
                    'end' => $end ?? now()->endOfMonth()->toDateString(),
                ],
            ],
        ]);
    }

    /**
     * 📋 Résultats en attente de validation
     * Affiche uniquement ce que l'utilisateur peut réellement valider
     */
    public function pendingValidations(Request $request): JsonResponse
    {
        $user = $request->user();

        // 🔵 N1 : Résultats des activités dont je suis responsable
        $pendingN1 = TacheResultat::query()
            ->with(['tache.activite.projet', 'user', 'documents'])
            ->whereNotNull('soumis_le')
            ->where('valide_par_n1', false)
            ->whereHas('tache.activite', function ($q) use ($user) {
                $q->where('responsable_id', $user->id);
            })
            ->latest('soumis_le')
            ->get();

        // 🟢 N2 : Résultats des projets dont je suis responsable (et validés N1)
        $pendingN2 = TacheResultat::query()
            ->with(['tache.activite.projet', 'user', 'validateurN1', 'documents'])
            ->where('valide_par_n1', true)
            ->where('valide_par_n2', false)
            ->whereHas('tache.activite.projet', function ($q) use ($user) {
                $q->where('responsable_id', $user->id);
            })
            ->latest('valide_le_n1')
            ->get();

        // 📊 Statistiques
        $counts = [
            'n1' => $pendingN1->count(),
            'n2' => $pendingN2->count(),
            'total' => $pendingN1->count() + $pendingN2->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'pending_n1' => TacheResultatResource::collection($pendingN1),
                'pending_n2' => TacheResultatResource::collection($pendingN2),
                'counts' => $counts,
            ],
        ]);
    }

    /**
     * ✅ Validation N1 - STRICT
     */
    public function validateN1(Request $request, TacheResultat $resultat): JsonResponse
    {
        $validated = $request->validate([
            'commentaire' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        // ⚠️ VÉRIFICATION STRICTE : Uniquement responsable de l'activité
        if ($resultat->tache->activite->responsable_id !== $user->id) {
            Log::warning('Tentative de validation N1 non autorisée', [
                'user_id' => $user->id,
                'resultat_id' => $resultat->id,
                'responsable_activite_id' => $resultat->tache->activite->responsable_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Seul le responsable de l\'activité peut valider ce résultat (N1)',
            ], 403);
        }

        // Vérifications supplémentaires
        if (! $resultat->soumis_le) {
            return response()->json([
                'success' => false,
                'message' => 'Ce résultat n\'a pas encore été soumis',
            ], 422);
        }

        if ($resultat->valide_par_n1) {
            return response()->json([
                'success' => false,
                'message' => 'Ce résultat a déjà été validé N1',
            ], 422);
        }

        if ($resultat->user_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas valider votre propre résultat',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $resultat->validateByN1($user, $validated['commentaire'] ?? null);

            Log::info('✅ Validation N1 effectuée', [
                'resultat_id' => $resultat->id,
                'validateur_id' => $user->id,
                'user_id' => $resultat->user_id,
                'tache_id' => $resultat->tache_id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Résultat validé (Niveau 1) avec succès',
                'data' => new TacheResultatResource($resultat->fresh([
                    'user',
                    'validateurN1',
                    'validateurN2',
                    'documents',
                    'tache.activite.projet',
                ])),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ Erreur validation N1', [
                'resultat_id' => $resultat->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * ✅ Validation N2 - STRICT
     */
    public function validateN2(Request $request, TacheResultat $resultat): JsonResponse
    {
        $validated = $request->validate([
            'commentaire' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        // ⚠️ VÉRIFICATION STRICTE : Uniquement responsable du projet
        if (
            ! $resultat->tache->activite->projet ||
            $resultat->tache->activite->projet->responsable_id !== $user->id
        ) {

            Log::warning('Tentative de validation N2 non autorisée', [
                'user_id' => $user->id,
                'resultat_id' => $resultat->id,
                'responsable_projet_id' => $resultat->tache->activite->projet?->responsable_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Seul le responsable du projet peut valider ce résultat (N2)',
            ], 403);
        }

        // Vérifications supplémentaires
        if (! $resultat->valide_par_n1) {
            return response()->json([
                'success' => false,
                'message' => 'Ce résultat doit d\'abord être validé N1',
            ], 422);
        }

        if ($resultat->valide_par_n2) {
            return response()->json([
                'success' => false,
                'message' => 'Ce résultat a déjà été validé N2',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $resultat->validateByN2($user, $validated['commentaire'] ?? null);

            Log::info('✅ Validation N2 effectuée', [
                'resultat_id' => $resultat->id,
                'validateur_id' => $user->id,
                'user_id' => $resultat->user_id,
                'tache_id' => $resultat->tache_id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Résultat validé (Niveau 2 - Final) avec succès',
                'data' => new TacheResultatResource($resultat->fresh([
                    'user',
                    'validateurN1',
                    'validateurN2',
                    'documents',
                    'tache.activite.projet',
                ])),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ Erreur validation N2', [
                'resultat_id' => $resultat->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * ❌ Rejeter un résultat - STRICT
     */
    public function reject(Request $request, TacheResultat $resultat): JsonResponse
    {
        $validated = $request->validate([
            'commentaire' => 'required|string|max:1000',
            'level' => 'required|in:n1,n2',
        ]);

        $user = $request->user();
        $level = $validated['level'];

        // ⚠️ VÉRIFICATION STRICTE selon le niveau
        if ($level === 'n1') {
            if ($resultat->tache->activite->responsable_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seul le responsable de l\'activité peut rejeter ce résultat (N1)',
                ], 403);
            }
        } else {
            if (
                ! $resultat->tache->activite->projet ||
                $resultat->tache->activite->projet->responsable_id !== $user->id
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seul le responsable du projet peut rejeter ce résultat (N2)',
                ], 403);
            }
        }

        try {
            DB::beginTransaction();

            $resultat->reject($user, $validated['commentaire'], $level);

            // Remettre le statut individuel à "a_faire"
            $resultat->tache->updateStatutForUser(
                $resultat->user,
                'a_faire',
                0
            );

            Log::info('❌ Résultat rejeté', [
                'resultat_id' => $resultat->id,
                'level' => $level,
                'rejecteur_id' => $user->id,
                'user_id' => $resultat->user_id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Résultat rejeté. L\'utilisateur devra le soumettre à nouveau.',
                'data' => new TacheResultatResource($resultat->fresh()),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ Erreur rejet résultat', [
                'resultat_id' => $resultat->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * 👁️ Consulter tous les résultats (pour responsables)
     */
    public function myResponsibilities(Request $request): JsonResponse
    {
        $user = $request->user();

        // Résultats des activités dont je suis responsable
        $asResponsableActivite = TacheResultat::query()
            ->with(['tache.activite.projet', 'user', 'validateurN1', 'validateurN2', 'documents'])
            ->whereHas('tache.activite', function ($q) use ($user) {
                $q->where('responsable_id', $user->id);
            })
            ->latest('soumis_le')
            ->get();

        // Résultats des projets dont je suis responsable
        $asResponsableProjet = TacheResultat::query()
            ->with(['tache.activite.projet', 'user', 'validateurN1', 'validateurN2', 'documents'])
            ->whereHas('tache.activite.projet', function ($q) use ($user) {
                $q->where('responsable_id', $user->id);
            })
            ->latest('soumis_le')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'as_responsable_activite' => TacheResultatResource::collection($asResponsableActivite),
                'as_responsable_projet' => TacheResultatResource::collection($asResponsableProjet),
                'stats' => [
                    'activites' => $asResponsableActivite->count(),
                    'projets' => $asResponsableProjet->count(),
                    'total' => $asResponsableActivite->count() + $asResponsableProjet->count(),
                ],
            ],
        ]);
    }

    /**
     * 📊 Vérifier mes permissions
     */
    public function checkPermissions(Request $request, TacheResultat $resultat): JsonResponse
    {
        $user = $request->user();

        $permissions = [
            'can_view' => $this->canView($user, $resultat),
            'can_validate_n1' => $this->canValidateN1($user, $resultat),
            'can_validate_n2' => $this->canValidateN2($user, $resultat),
            'can_reject_n1' => $this->canRejectN1($user, $resultat),
            'can_reject_n2' => $this->canRejectN2($user, $resultat),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'permissions' => $permissions,
                'user_role' => $this->getUserRole($user, $resultat),
            ],
        ]);
    }

    // ==================== MÉTHODES PRIVÉES ====================

    private function canView($user, TacheResultat $resultat): bool
    {
        // C'est son résultat
        if ($resultat->user_id === $user->id) {
            return true;
        }

        // Responsable de l'activité
        if ($resultat->tache->activite->responsable_id === $user->id) {
            return true;
        }

        // Responsable du projet
        if (
            $resultat->tache->activite->projet &&
            $resultat->tache->activite->projet->responsable_id === $user->id
        ) {
            return true;
        }

        return false;
    }

    private function canValidateN1($user, TacheResultat $resultat): bool
    {
        return $resultat->soumis_le &&
            ! $resultat->valide_par_n1 &&
            $resultat->user_id !== $user->id &&
            $resultat->tache->activite->responsable_id === $user->id;
    }

    private function canValidateN2($user, TacheResultat $resultat): bool
    {
        return $resultat->valide_par_n1 &&
            ! $resultat->valide_par_n2 &&
            $resultat->tache->activite->projet &&
            $resultat->tache->activite->projet->responsable_id === $user->id;
    }

    private function canRejectN1($user, TacheResultat $resultat): bool
    {
        return $this->canValidateN1($user, $resultat);
    }

    private function canRejectN2($user, TacheResultat $resultat): bool
    {
        return $this->canValidateN2($user, $resultat);
    }

    private function getUserRole($user, TacheResultat $resultat): string
    {
        if ($resultat->user_id === $user->id) {
            return 'auteur';
        }

        if ($resultat->tache->activite->responsable_id === $user->id) {
            return 'responsable_activite';
        }

        if (
            $resultat->tache->activite->projet &&
            $resultat->tache->activite->projet->responsable_id === $user->id
        ) {
            return 'responsable_projet';
        }

        return 'aucun';
    }

    /**
     * ✅ Historique des validations
     */
    public function validationHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|in:validated,rejected',
            'level' => 'nullable|in:n1,n2',
            'period' => 'nullable|in:today,week,month,all',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $user = $request->user();

        $query = TacheResultat::query()
            ->with([
                'tache.activite.projet',
                'user',
                'validateurN1',
                'validateurN2',
                'documents',
            ])
            ->whereNotNull('soumis_le');

        // Filtrer par permissions de l'utilisateur
        if (! $user->isSuperAdmin()) {
            $query->where(function ($q) use ($user) {
                // Résultats que l'utilisateur peut valider (N1)
                $q->whereHas('tache.activite', function ($aq) use ($user) {
                    $aq->where('responsable_id', $user->id)
                        ->orWhereHas('membres', function ($mq) use ($user) {
                            $mq->where('user_id', $user->id)
                                ->where('can_validate_results', true);
                        });
                })
                    // Ou résultats que l'utilisateur peut valider (N2)
                    ->orWhereHas('tache.activite.projet', function ($pq) use ($user) {
                        $pq->where('responsable_id', $user->id);
                    })
                    // Ou ses propres résultats
                    ->orWhere('user_id', $user->id);
            });
        }

        // Filtrer par statut
        if (isset($validated['status'])) {
            if ($validated['status'] === 'validated') {
                $query->where(function ($q) use ($validated) {
                    if (isset($validated['level'])) {
                        if ($validated['level'] === 'n1') {
                            $q->where('valide_par_n1', true);
                        } else {
                            $q->where('valide_par_n2', true);
                        }
                    } else {
                        $q->where('valide_par_n1', true)
                            ->orWhere('valide_par_n2', true);
                    }
                });
            } else {
                $query->whereNotNull('rejete_le');
            }
        }

        // Filtrer par période
        if (isset($validated['period'])) {
            switch ($validated['period']) {
                case 'today':
                    $query->where(function ($q) {
                        $q->whereDate('valide_le_n1', Carbon::today())
                            ->orWhereDate('valide_le_n2', Carbon::today())
                            ->orWhereDate('rejete_le', Carbon::today());
                    });
                    break;
                case 'week':
                    $query->where(function ($q) {
                        $q->whereBetween('valide_le_n1', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                            ->orWhereBetween('valide_le_n2', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                            ->orWhereBetween('rejete_le', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    });
                    break;
                case 'month':
                    $query->where(function ($q) {
                        $q->whereBetween('valide_le_n1', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                            ->orWhereBetween('valide_le_n2', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                            ->orWhereBetween('rejete_le', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
                    });
                    break;
            }
        }

        // Filtrer par utilisateur (auteur du résultat)
        if (isset($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        $resultats = $query->latest('updated_at')->limit(100)->get();

        // Transformer en format historique
        $history = $resultats->map(function ($resultat) {
            $item = [
                'id' => $resultat->id,
                'tache' => [
                    'id' => $resultat->tache->id,
                    'titre' => $resultat->tache->titre,
                    'code' => $resultat->tache->code,
                ],
                'user' => [
                    'id' => $resultat->user->id,
                    'nom' => $resultat->user->nom,
                    'avatar' => $resultat->user->avatar,
                ],
                'taux_realisation' => $resultat->taux_realisation,
                'resultats_obtenus' => $resultat->resultats_obtenus,
            ];

            // Déterminer le dernier événement
            $lastValidation = null;
            $lastDate = null;

            if ($resultat->valide_le_n2) {
                $lastValidation = 'n2';
                $lastDate = $resultat->valide_le_n2;
                $item['status'] = 'validated';
                $item['level'] = 'n2';
                $item['validated_at'] = $resultat->valide_le_n2;
                $item['validator'] = [
                    'id' => $resultat->validateurN2->id,
                    'nom' => $resultat->validateurN2->nom,
                ];
                $item['commentaire'] = $resultat->commentaire_n2;
            } elseif ($resultat->valide_le_n1) {
                $lastValidation = 'n1';
                $lastDate = $resultat->valide_le_n1;
                $item['status'] = 'validated';
                $item['level'] = 'n1';
                $item['validated_at'] = $resultat->valide_le_n1;
                $item['validator'] = [
                    'id' => $resultat->validateurN1->id,
                    'nom' => $resultat->validateurN1->nom,
                ];
                $item['commentaire'] = $resultat->commentaire_n1;
            } elseif ($resultat->rejete_le) {
                $item['status'] = 'rejected';
                $item['level'] = $resultat->niveau_rejet ?? 'n1';
                $item['rejected_at'] = $resultat->rejete_le;
                $item['validator'] = $resultat->rejete_par ? [
                    'id' => $resultat->rejete_par,
                    'nom' => $resultat->rejetePar?->nom,
                ] : null;
                $item['commentaire'] = $resultat->motif_rejet;
            }

            return $item;
        })->filter(); // Enlever les nulls

        return response()->json([
            'success' => true,
            'data' => $history->values(),
            'count' => $history->count(),
        ]);
    }

    /**
     * ✅ Statistiques de validation pour un utilisateur
     */
    public function userValidationStats(Request $request, int $userId): JsonResponse
    {
        $user = $request->user();

        // Vérifier les permissions
        if ($userId !== $user->id && ! $user->isSuperAdmin()) {
            return response()->json([
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $resultats = TacheResultat::forUser($userId)
            ->whereNotNull('soumis_le')
            ->get();

        $stats = [
            'total_soumis' => $resultats->count(),
            'valides_n1' => $resultats->where('valide_par_n1', true)->count(),
            'valides_n2' => $resultats->where('valide_par_n2', true)->count(),
            'en_attente' => $resultats->where('valide_par_n1', false)->count(),
            'rejetes' => $resultats->whereNotNull('rejete_le')->count(),
            'taux_validation' => $resultats->count() > 0
                ? round(($resultats->where('valide_par_n1', true)->count() / $resultats->count()) * 100)
                : 0,
            'taux_realisation_moyen' => $resultats->avg('taux_realisation') ?? 0,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * ✅ Statistiques globales de validation
     */
    public function globalValidationStats(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = TacheResultat::query()->whereNotNull('soumis_le');

        // Filtrer selon les permissions
        if (! $user->isSuperAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('tache.activite', function ($aq) use ($user) {
                    $aq->where('responsable_id', $user->id);
                })
                    ->orWhereHas('tache.activite.projet', function ($pq) use ($user) {
                        $pq->where('responsable_id', $user->id);
                    });
            });
        }

        $resultats = $query->get();

        $stats = [
            'total' => $resultats->count(),
            'en_attente_n1' => $resultats->where('valide_par_n1', false)->whereNull('rejete_le')->count(),
            'en_attente_n2' => $resultats->where('valide_par_n1', true)->where('valide_par_n2', false)->whereNull('rejete_le')->count(),
            'valides_complet' => $resultats->where('is_fully_validated', true)->count(),
            'rejetes' => $resultats->whereNotNull('rejete_le')->count(),
            'this_week' => $resultats->whereBetween('soumis_le', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ])->count(),
            'taux_validation_moyen' => $resultats->count() > 0
                ? round(($resultats->where('is_fully_validated', true)->count() / $resultats->count()) * 100)
                : 0,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * 📋 MON RAPPORT HEBDOMADAIRE AMÉLIORÉ
     *
     * Logique selon le scénario de Jean :
     * - Affiche les tâches confiées pendant la semaine sélectionnée
     * - Affiche les tâches des semaines précédentes non terminées OU terminées mais non validées
     * - Masque les tâches complètement validées des semaines passées
     */
    public function myWeeklyReportImproved(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'week_number' => 'nullable|integer|min:1|max:53',
            'year' => 'nullable|integer|min:1990',
        ]);

        $weekNumber = $validated['week_number'] ?? now()->weekOfYear;
        $year = $validated['year'] ?? now()->year;
        $user = $request->user();

        // Dates de la semaine sélectionnée
        $weekStart = $this->getWeekStartDate($year, $weekNumber);
        $weekEnd = $this->getWeekEndDate($year, $weekNumber);

        Log::info('📋 Chargement fiche évaluation améliorée', [
            'user_id' => $user->id,
            'week' => $weekNumber,
            'year' => $year,
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
        ]);

        // Récupérer TOUTES les tâches assignées à l'utilisateur (sans filtre de date)
        $taches = Tache::with([
            'activite.projet.workspace',
            'assignees' => function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->withPivot([
                        'statut_individuel',
                        'progression_individuelle',
                        'started_at',
                        'completed_at',
                        'created_at', // 🔑 Date d'affectation cruciale
                    ]);
            },
            'labels',
            'resultatsIndividuels' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            },
        ])
            ->whereHas('assignees', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->active() // Exclut les archivées
            ->ordered()
            ->get();

        // 🎯 LOGIQUE DE FILTRAGE SELON LE SCÉNARIO
        $tasksToDisplay = $taches->filter(function ($tache) use ($weekStart, $weekEnd, $user) {
            $assignee = $tache->assignees->first();
            if (! $assignee) {
                return false;
            }

            // Date d'affectation de la tâche à l'utilisateur
            $affectationDate = Carbon::parse($assignee->pivot->created_at);
            $weekStartCarbon = Carbon::parse($weekStart);
            $weekEndCarbon = Carbon::parse($weekEnd);

            // ✅ CAS 1 : Tâche affectée PENDANT cette semaine → TOUJOURS afficher
            if ($affectationDate->between($weekStartCarbon, $weekEndCarbon)) {
                Log::debug('Tâche affectée cette semaine', [
                    'tache_id' => $tache->id,
                    'affectation' => $affectationDate->format('Y-m-d'),
                ]);

                return true;
            }

            // ✅ CAS 2 : Tâche affectée AVANT cette semaine
            if ($affectationDate->lt($weekStartCarbon)) {
                $statutIndividuel = $assignee->pivot->statut_individuel;

                // Si pas terminé → AFFICHER (tâche en cours)
                if ($statutIndividuel !== 'termine') {
                    Log::debug('Tâche non terminée d\'avant', [
                        'tache_id' => $tache->id,
                        'statut' => $statutIndividuel,
                    ]);

                    return true;
                }

                // Si terminé → vérifier la validation
                $monResultat = $tache->monResultat($user);

                // Pas de résultat soumis → AFFICHER
                if (! $monResultat || ! $monResultat->soumis_le) {
                    Log::debug('Tâche terminée mais résultat non soumis', [
                        'tache_id' => $tache->id,
                    ]);

                    return true;
                }

                // Vérifier si validation complète
                $validationComplete = false;

                if ($tache->validation_n2_required) {
                    // N1 ET N2 requis → validé seulement si les 2 sont validés
                    $validationComplete = $monResultat->valide_par_n1 && $monResultat->valide_par_n2;
                } else {
                    // Seulement N1 requis → validé si N1 validé
                    $validationComplete = $monResultat->valide_par_n1;
                }

                // Si validation complète, vérifier QUAND elle a eu lieu
                if ($validationComplete) {
                    $validationDate = $tache->validation_n2_required
                        ? Carbon::parse($monResultat->valide_le_n2)
                        : Carbon::parse($monResultat->valide_le_n1);

                    // 🔑 RÈGLE IMPORTANTE : Masquer si validé AVANT ou PENDANT la semaine sélectionnée
                    // Jean voit la tâche 3 validée dans la semaine du 01-07/12
                    // Mais NE LA VOIT PLUS dans la semaine du 08-14/12
                    if ($validationDate->lte($weekEndCarbon)) {
                        Log::debug('Tâche validée avant/pendant cette semaine → masquée', [
                            'tache_id' => $tache->id,
                            'validation_date' => $validationDate->format('Y-m-d'),
                            'week_end' => $weekEndCarbon->format('Y-m-d'),
                        ]);

                        return false; // ❌ NE PAS afficher
                    }
                }

                // Pas complètement validée → AFFICHER
                Log::debug('Tâche pas encore validée → affichée', [
                    'tache_id' => $tache->id,
                    'valide_n1' => $monResultat->valide_par_n1,
                    'valide_n2' => $monResultat->valide_par_n2,
                ]);

                return true;
            }

            // ❌ CAS 3 : Tâche affectée APRÈS cette semaine → NE PAS afficher
            return false;
        });

        // Calculer les statistiques détaillées
        $stats = $this->calculateDetailedStats($tasksToDisplay, $user, Carbon::parse($weekEnd));

        // Grouper par activité
        $byActivite = $tasksToDisplay->groupBy('activite_id')->map(function ($tasks, $activiteId) {
            $activite = $tasks->first()->activite;

            return [
                'activite' => [
                    'id' => $activite->id,
                    'nom' => $activite->nom,
                    'code' => $activite->code,
                    'projet_nom' => $activite->projet?->nom,
                    'workspace_nom' => $activite->projet?->workspace?->nom,
                ],
                'taches' => TacheResource::collection($tasks),
                'stats' => [
                    'total' => $tasks->count(),
                    'a_faire' => $tasks->filter(function ($t) {
                        $assignee = $t->assignees->first();

                        return $assignee && $assignee->pivot->statut_individuel === 'a_faire';
                    })->count(),
                    'en_cours' => $tasks->filter(function ($t) {
                        $assignee = $t->assignees->first();

                        return $assignee && $assignee->pivot->statut_individuel === 'en_cours';
                    })->count(),
                    'termine' => $tasks->filter(function ($t) {
                        $assignee = $t->assignees->first();

                        return $assignee && $assignee->pivot->statut_individuel === 'termine';
                    })->count(),
                ],
            ];
        })->values();

        Log::info('✅ Fiche évaluation chargée', [
            'user_id' => $user->id,
            'total_tasks' => $stats['total'],
            'activites' => $byActivite->count(),
        ]);

        return response()->json([
            'week_info' => [
                'week_number' => $weekNumber,
                'year' => $year,
                'start_date' => $weekStart,
                'end_date' => $weekEnd,
                'is_current_week' => $weekNumber === now()->weekOfYear && $year === now()->year,
            ],
            'all_tasks' => TacheResource::collection($tasksToDisplay),
            'by_activite' => $byActivite,
            'stats' => $stats,
            'scenario_info' => [
                'message' => 'Les tâches complètement validées disparaissent des semaines suivantes',
                'logic' => [
                    'show_if_assigned_this_week' => true,
                    'show_if_not_completed' => true,
                    'show_if_completed_but_not_validated' => true,
                    'hide_if_fully_validated_before_week_end' => true,
                ],
            ],
        ]);
    }

    /**
     * 🛠️ Helper: Calculer les statistiques détaillées
     */
    private function calculateDetailedStats($tasks, $user, $weekEnd)
    {
        $total = $tasks->count();

        $a_faire = $tasks->filter(function ($t) {
            $assignee = $t->assignees->first();

            return $assignee && $assignee->pivot->statut_individuel === 'a_faire';
        })->count();

        $en_cours = $tasks->filter(function ($t) {
            $assignee = $t->assignees->first();

            return $assignee && $assignee->pivot->statut_individuel === 'en_cours';
        })->count();

        $termine = $tasks->filter(function ($t) {
            $assignee = $t->assignees->first();

            return $assignee && $assignee->pivot->statut_individuel === 'termine';
        })->count();

        $avec_resultat = $tasks->filter(function ($t) {
            return $t->resultatsIndividuels->isNotEmpty() &&
                $t->resultatsIndividuels->first()->soumis_le;
        })->count();

        $valide_n1 = $tasks->filter(function ($t) {
            $r = $t->resultatsIndividuels->first();

            return $r && $r->valide_par_n1;
        })->count();

        $valide_n2 = $tasks->filter(function ($t) {
            $r = $t->resultatsIndividuels->first();

            return $r && $r->valide_par_n2;
        })->count();

        // 🚨 EN RETARD : échéance passée ET pas terminé individuellement
        $en_retard = $tasks->filter(function ($t) {
            $assignee = $t->assignees->first();

            return $t->is_overdue &&
                $assignee &&
                $assignee->pivot->statut_individuel !== 'termine';
        })->count();

        $estimated_hours = $tasks->sum(fn ($t) => (float) $t->estimated_hours);
        $actual_hours = $tasks->sum(fn ($t) => (float) $t->actual_hours);

        return [
            'total' => $total,
            'a_faire' => $a_faire,
            'en_cours' => $en_cours,
            'termine' => $termine,
            'avec_resultat' => $avec_resultat,
            'valide_n1' => $valide_n1,
            'valide_n2' => $valide_n2,
            'en_retard' => $en_retard,
            'completionRate' => $total > 0 ? round(($termine / $total) * 100) : 0,
            'validationRate' => $avec_resultat > 0 ? round(($valide_n1 / $avec_resultat) * 100) : 0,
            'estimated_hours' => number_format($estimated_hours, 1),
            'actual_hours' => number_format($actual_hours, 1),
        ];
    }

    /**
     * 🛠️ Helper: Obtenir la date de début de semaine (Lundi)
     */
    private function getWeekStartDate(int $year, int $week): string
    {
        $dto = new \DateTime;
        $dto->setISODate($year, $week);

        return $dto->format('Y-m-d');
    }

    /**
     * 🛠️ Helper: Obtenir la date de fin de semaine (Dimanche)
     */
    private function getWeekEndDate(int $year, int $week): string
    {
        $dto = new \DateTime;
        $dto->setISODate($year, $week, 7);

        return $dto->format('Y-m-d');
    }

    /**
     * 📊 RAPPORT DE PERFORMANCE D'UN WORKSPACE
     * À ajouter dans EvaluationController.php
     *
     * Permet aux managers de voir les performances de tous les membres du workspace
     */
    public function workspacePerformanceReport(Request $request, int $workspaceId): JsonResponse
    {
        $validated = $request->validate([
            'week_number' => 'nullable|integer|min:1|max:53',
            'year' => 'nullable|integer|min:1990',
            'user_id' => 'nullable|exists:users,id', // Filtrer par utilisateur spécifique
        ]);

        $workspace = Workspace::with(['membres'])->findOrFail($workspaceId);
        $user = $request->user();

        // Vérifier les permissions
        if (! $this->canViewWorkspacePerformance($user, $workspace)) {
            return response()->json([
                'message' => 'Vous n\'avez pas la permission de consulter ce rapport',
            ], 403);
        }

        $weekNumber = $validated['week_number'] ?? now()->weekOfYear;
        $year = $validated['year'] ?? now()->year;
        $weekStart = $this->getWeekStartDate($year, $weekNumber);
        $weekEnd = $this->getWeekEndDate($year, $weekNumber);

        // Liste des membres à analyser
        $membresQuery = $workspace->membres();

        if (isset($validated['user_id'])) {
            $membresQuery->where('users.id', $validated['user_id']);
        }

        $membres = $membresQuery->get();

        // Analyser chaque membre
        $performanceData = $membres->map(function ($membre) use ($weekStart, $weekEnd, $weekNumber, $year, $workspace) {
            return $this->generateUserPerformanceReport($membre, $weekStart, $weekEnd, $weekNumber, $year, $workspace);
        })->sortByDesc('performance_score')->values();

        // Statistiques globales du workspace
        $globalStats = $this->calculateWorkspaceGlobalStats($performanceData);

        return response()->json([
            'workspace' => [
                'id' => $workspace->id,
                'nom' => $workspace->nom,
                'total_membres' => $membres->count(),
            ],
            'week_info' => [
                'week_number' => $weekNumber,
                'year' => $year,
                'start_date' => $weekStart,
                'end_date' => $weekEnd,
                'is_current_week' => $weekNumber === now()->weekOfYear && $year === now()->year,
            ],
            'membres_performance' => $performanceData,
            'global_stats' => $globalStats,
            'classement' => $this->generateRanking($performanceData),
        ]);
    }

    /**
     * 📈 DÉTAIL PERFORMANCE D'UN MEMBRE
     */
    public function memberDetailedPerformance(Request $request, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'week_number' => 'nullable|integer|min:1|max:53',
            'year' => 'nullable|integer|min:1990',
            'workspace_id' => 'nullable|exists:workspaces,id',
        ]);

        $membre = User::findOrFail($userId);
        $currentUser = $request->user();

        // Vérifier permissions
        if (! $this->canViewUserPerformance($currentUser, $membre, $validated['workspace_id'] ?? null)) {
            return response()->json([
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $weekNumber = $validated['week_number'] ?? now()->weekOfYear;
        $year = $validated['year'] ?? now()->year;
        $weekStart = $this->getWeekStartDate($year, $weekNumber);
        $weekEnd = $this->getWeekEndDate($year, $weekNumber);

        $performance = $this->generateUserPerformanceReport($membre, $weekStart, $weekEnd, $weekNumber, $year);

        // Historique des 4 dernières semaines
        $historique = $this->generatePerformanceHistory($membre, $weekNumber, $year, 4);

        return response()->json([
            'user' => [
                'id' => $membre->id,
                'nom' => $membre->nom,
                'email' => $membre->email,
                'avatar' => $membre->avatar,
            ],
            'week_info' => [
                'week_number' => $weekNumber,
                'year' => $year,
                'start_date' => $weekStart,
                'end_date' => $weekEnd,
            ],
            'performance' => $performance,
            'historique' => $historique,
            'tendances' => $this->calculatePerformanceTrends($historique),
        ]);
    }

    // ==================== MÉTHODES PRIVÉES ====================

    /**
     * Générer le rapport de performance d'un utilisateur
     */
    private function generateUserPerformanceReport($user, $weekStart, $weekEnd, $weekNumber, $year, $workspace = null)
    {
        // Récupérer les tâches selon la logique améliorée
        $taches = Tache::with([
            'activite.projet',
            'assignees' => function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->withPivot(['statut_individuel', 'progression_individuelle', 'created_at', 'completed_at']);
            },
            'resultatsIndividuels' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            },
        ])
            ->whereHas('assignees', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->active()
            ->get()
            ->filter(function ($tache) use ($user, $weekStart, $weekEnd) {
                // Utiliser la même logique que myWeeklyReport
                return $this->shouldDisplayTaskInWeek($tache, $user, $weekStart, $weekEnd);
            });

        // Si workspace spécifié, filtrer par workspace
        if ($workspace) {
            $taches = $taches->filter(function ($tache) use ($workspace) {
                return $tache->activite->projet &&
                    $tache->activite->projet->workspace_id === $workspace->id;
            });
        }

        $stats = $this->calculateDetailedStats($taches, $user, Carbon::parse($weekEnd));

        // Analyse des retards
        $retards = $taches->filter(function ($t) {
            $assignee = $t->assignees->first();

            return $t->is_overdue && $assignee && $assignee->pivot->statut_individuel !== 'termine';
        });

        // 🎯 Score de performance (0-100)
        $performanceScore = $this->calculatePerformanceScore($stats, $retards->count());

        // 📊 Analyse du temps
        $timeAnalysis = $this->analyzeTime($stats);

        return [
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'email' => $user->email,
                'avatar' => $user->avatar,
            ],
            'stats' => $stats,
            'performance_score' => $performanceScore,
            'performance_level' => $this->getPerformanceLevel($performanceScore),
            'time_analysis' => $timeAnalysis,
            'taches_en_retard' => TacheResource::collection($retards),
            'retards_count' => $retards->count(),
            'recommandations' => $this->generateRecommendations($stats, $retards->count()),
        ];
    }

    /**
     * Calculer le score de performance (0-100)
     */
    private function calculatePerformanceScore($stats, $retardsCount): int
    {
        $score = 50; // Base

        // ✅ Points positifs
        $score += ($stats['termine'] * 8); // Tâches terminées
        $score += ($stats['valide_n1'] * 5); // Validations N1
        $score += ($stats['valide_n2'] * 3); // Validations N2
        $score += (min($stats['completionRate'], 100) * 0.3); // Taux de complétion

        // ❌ Pénalités
        $score -= ($stats['a_faire'] * 3); // Tâches à faire
        $score -= ($retardsCount * 15); // GROS MALUS pour retards

        // Pénalité si beaucoup d'heures sans terminer
        if ($stats['actual_hours'] > $stats['estimated_hours'] && $stats['termine'] < $stats['total'] / 2) {
            $score -= 10;
        }

        return max(0, min(100, round($score)));
    }

    /**
     * Déterminer le niveau de performance
     */
    private function getPerformanceLevel($score): array
    {
        if ($score >= 90) {
            return ['label' => 'Excellent', 'color' => 'green', 'emoji' => '🌟'];
        } elseif ($score >= 75) {
            return ['label' => 'Très bien', 'color' => 'blue', 'emoji' => '👍'];
        } elseif ($score >= 60) {
            return ['label' => 'Bien', 'color' => 'yellow', 'emoji' => '👌'];
        } elseif ($score >= 40) {
            return ['label' => 'À améliorer', 'color' => 'orange', 'emoji' => '⚠️'];
        } else {
            return ['label' => 'Critique', 'color' => 'red', 'emoji' => '🚨'];
        }
    }

    /**
     * Analyser le temps de travail
     */
    private function analyzeTime($stats): array
    {
        $estimated = (float) $stats['estimated_hours'];
        $actual = (float) $stats['actual_hours'];

        $efficiency = $estimated > 0 ? round(($estimated / $actual) * 100) : 100;
        $overrun = $actual - $estimated;
        $overrunPercent = $estimated > 0 ? round(($overrun / $estimated) * 100) : 0;

        return [
            'estimated_hours' => $estimated,
            'actual_hours' => $actual,
            'efficiency' => min(100, $efficiency),
            'overrun_hours' => round($overrun, 1),
            'overrun_percent' => $overrunPercent,
            'status' => $overrun <= 0 ? 'on_track' : ($overrun < $estimated * 0.2 ? 'slight_overrun' : 'significant_overrun'),
        ];
    }

    /**
     * Générer des recommandations personnalisées
     */
    private function generateRecommandations($stats, $retardsCount): array
    {
        $recommendations = [];

        // 🚨 PRIORITÉ 1 : Retards
        if ($retardsCount > 0) {
            $recommendations[] = [
                'type' => 'urgent',
                'priority' => 1,
                'message' => "🚨 URGENT: Vous avez {$retardsCount} tâche(s) en retard. Priorisez-les immédiatement.",
                'action' => 'Concentrez-vous sur les tâches en retard avant d\'en commencer de nouvelles.',
            ];
        }

        // ⚠️ PRIORITÉ 2 : Trop de tâches à faire
        if ($stats['a_faire'] > 3) {
            $recommendations[] = [
                'type' => 'warning',
                'priority' => 2,
                'message' => "⚠️ Vous avez {$stats['a_faire']} tâches à faire. Commencez-en quelques-unes pour éviter l'accumulation.",
                'action' => 'Démarrez au moins 2-3 tâches cette semaine pour maintenir un bon rythme.',
            ];
        }

        // 💡 PRIORITÉ 3 : Tâches terminées non soumises
        $non_soumis = $stats['termine'] - $stats['avec_resultat'];
        if ($non_soumis > 0) {
            $recommendations[] = [
                'type' => 'info',
                'priority' => 3,
                'message' => "💡 Vous avez {$non_soumis} tâche(s) terminée(s) dont les résultats n'ont pas été soumis.",
                'action' => 'Soumettez vos résultats pour validation dès que possible.',
            ];
        }

        // ✅ FÉLICITATIONS
        if ($stats['valide_n1'] >= 3 && $retardsCount === 0) {
            $recommendations[] = [
                'type' => 'success',
                'priority' => 4,
                'message' => "✅ Excellent travail! {$stats['valide_n1']} résultats validés et aucun retard.",
                'action' => 'Continuez sur cette lancée!',
            ];
        }

        // Trier par priorité
        usort($recommendations, fn ($a, $b) => $a['priority'] - $b['priority']);

        return $recommendations;
    }

    /**
     * Calculer les statistiques globales du workspace
     */
    private function calculateWorkspaceGlobalStats($performanceData): array
    {
        $totalMembres = $performanceData->count();

        if ($totalMembres === 0) {
            return [
                'total_membres' => 0,
                'avg_performance_score' => 0,
                'total_taches' => 0,
                'total_terminees' => 0,
                'total_en_retard' => 0,
                'completion_rate' => 0,
            ];
        }

        $totalTaches = $performanceData->sum('stats.total');
        $totalTerminees = $performanceData->sum('stats.termine');
        $totalEnRetard = $performanceData->sum('retards_count');
        $avgScore = $performanceData->avg('performance_score');

        return [
            'total_membres' => $totalMembres,
            'avg_performance_score' => round($avgScore, 1),
            'total_taches' => $totalTaches,
            'total_terminees' => $totalTerminees,
            'total_en_retard' => $totalEnRetard,
            'completion_rate' => $totalTaches > 0 ? round(($totalTerminees / $totalTaches) * 100) : 0,
            'membres_excellent' => $performanceData->filter(fn ($p) => $p['performance_score'] >= 90)->count(),
            'membres_bien' => $performanceData->filter(fn ($p) => $p['performance_score'] >= 60 && $p['performance_score'] < 90)->count(),
            'membres_a_ameliorer' => $performanceData->filter(fn ($p) => $p['performance_score'] < 60)->count(),
        ];
    }

    /**
     * Générer le classement
     */
    private function generateRanking($performanceData): array
    {
        return $performanceData->map(function ($perf, $index) {
            return [
                'rank' => $index + 1,
                'user_id' => $perf['user']['id'],
                'nom' => $perf['user']['nom'],
                'score' => $perf['performance_score'],
                'taches_terminees' => $perf['stats']['termine'],
                'en_retard' => $perf['retards_count'],
            ];
        })->values()->all();
    }

    /**
     * Générer l'historique de performance
     */
    private function generatePerformanceHistory($user, $currentWeek, $currentYear, $weeksCount): array
    {
        $history = [];

        for ($i = 0; $i < $weeksCount; $i++) {
            $week = $currentWeek - $i;
            $year = $currentYear;

            // Gérer le passage d'année
            if ($week < 1) {
                $week = 52 + $week;
                $year--;
            }

            $weekStart = $this->getWeekStartDate($year, $week);
            $weekEnd = $this->getWeekEndDate($year, $week);

            $perf = $this->generateUserPerformanceReport($user, $weekStart, $weekEnd, $week, $year);

            $history[] = [
                'week_number' => $week,
                'year' => $year,
                'performance_score' => $perf['performance_score'],
                'taches_total' => $perf['stats']['total'],
                'taches_terminees' => $perf['stats']['termine'],
                'en_retard' => $perf['retards_count'],
            ];
        }

        return array_reverse($history);
    }

    /**
     * Calculer les tendances
     */
    private function calculatePerformanceTrends($historique): array
    {
        if (count($historique) < 2) {
            return ['trend' => 'stable', 'variation' => 0];
        }

        $scores = array_column($historique, 'performance_score');
        $latest = end($scores);
        $previous = $scores[count($scores) - 2];

        $variation = $latest - $previous;

        $trend = 'stable';
        if ($variation > 5) {
            $trend = 'improving';
        } elseif ($variation < -5) {
            $trend = 'declining';
        }

        return [
            'trend' => $trend,
            'variation' => round($variation, 1),
            'emoji' => $trend === 'improving' ? '📈' : ($trend === 'declining' ? '📉' : '➡️'),
        ];
    }

    /**
     * Vérifier si l'utilisateur peut voir les performances du workspace
     */
    private function canViewWorkspacePerformance($user, $workspace): bool
    {
        $gate = app(ContextualPermissionGate::class);

        if ($gate->userCan($user, Permission::WORKSPACES_VIEW, $workspace)) {
            return true;
        }

        // Responsable d'un projet du workspace
        return $workspace->projets()
            ->where('responsable_id', $user->id)
            ->exists();
    }

    /**
     * Vérifier si l'utilisateur peut voir la performance d'un membre
     */
    private function canViewUserPerformance($currentUser, $targetUser, $workspaceId = null): bool
    {
        if ($currentUser->id === $targetUser->id) {
            return true;
        }

        if ($currentUser->isSuperAdmin()) {
            return true;
        }

        // Si workspace spécifié, vérifier les permissions workspace
        if ($workspaceId) {
            $workspace = Workspace::find($workspaceId);
            if ($workspace && $this->canViewWorkspacePerformance($currentUser, $workspace)) {
                return true;
            }
        }

        // Vérifier si responsable d'activité avec le membre
        $isResponsable = Activite::where('responsable_id', $currentUser->id)
            ->whereHas('membres', function ($q) use ($targetUser) {
                $q->where('user_id', $targetUser->id);
            })
            ->exists();

        return $isResponsable;
    }

    /**
     * Helper pour déterminer si une tâche doit être affichée (réutilisé)
     */
    private function shouldDisplayTaskInWeek($tache, $user, $weekStart, $weekEnd): bool
    {
        $assignee = $tache->assignees->first();
        if (! $assignee) {
            return false;
        }

        $affectationDate = Carbon::parse($assignee->pivot->created_at);
        $weekStartCarbon = Carbon::parse($weekStart);
        $weekEndCarbon = Carbon::parse($weekEnd);

        if ($affectationDate->between($weekStartCarbon, $weekEndCarbon)) {
            return true;
        }

        if ($affectationDate->lt($weekStartCarbon)) {
            $statutIndividuel = $assignee->pivot->statut_individuel;

            if ($statutIndividuel !== 'termine') {
                return true;
            }

            $monResultat = $tache->monResultat($user);

            if (! $monResultat || ! $monResultat->soumis_le) {
                return true;
            }

            $validationComplete = $tache->validation_n2_required
                ? ($monResultat->valide_par_n1 && $monResultat->valide_par_n2)
                : $monResultat->valide_par_n1;

            if ($validationComplete) {
                $validationDate = $tache->validation_n2_required
                    ? Carbon::parse($monResultat->valide_le_n2)
                    : Carbon::parse($monResultat->valide_le_n1);

                return $validationDate->gt($weekEndCarbon);
            }

            return true;
        }

        return false;
    }
}
