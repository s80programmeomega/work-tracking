<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TacheResultatResource;
use App\Models\TacheResultat;
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
            ]
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
                'responsable_activite_id' => $resultat->tache->activite->responsable_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Seul le responsable de l\'activité peut valider ce résultat (N1)'
            ], 403);
        }

        // Vérifications supplémentaires
        if (!$resultat->soumis_le) {
            return response()->json([
                'success' => false,
                'message' => 'Ce résultat n\'a pas encore été soumis'
            ], 422);
        }

        if ($resultat->valide_par_n1) {
            return response()->json([
                'success' => false,
                'message' => 'Ce résultat a déjà été validé N1'
            ], 422);
        }

        if ($resultat->user_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas valider votre propre résultat'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $resultat->validateByN1($user, $validated['commentaire'] ?? null);

            Log::info('✅ Validation N1 effectuée', [
                'resultat_id' => $resultat->id,
                'validateur_id' => $user->id,
                'user_id' => $resultat->user_id,
                'tache_id' => $resultat->tache_id
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
                    'tache.activite.projet'
                ]))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ Erreur validation N1', [
                'resultat_id' => $resultat->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
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
        if (!$resultat->tache->activite->projet || 
            $resultat->tache->activite->projet->responsable_id !== $user->id) {
            
            Log::warning('Tentative de validation N2 non autorisée', [
                'user_id' => $user->id,
                'resultat_id' => $resultat->id,
                'responsable_projet_id' => $resultat->tache->activite->projet?->responsable_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Seul le responsable du projet peut valider ce résultat (N2)'
            ], 403);
        }

        // Vérifications supplémentaires
        if (!$resultat->valide_par_n1) {
            return response()->json([
                'success' => false,
                'message' => 'Ce résultat doit d\'abord être validé N1'
            ], 422);
        }

        if ($resultat->valide_par_n2) {
            return response()->json([
                'success' => false,
                'message' => 'Ce résultat a déjà été validé N2'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $resultat->validateByN2($user, $validated['commentaire'] ?? null);

            Log::info('✅ Validation N2 effectuée', [
                'resultat_id' => $resultat->id,
                'validateur_id' => $user->id,
                'user_id' => $resultat->user_id,
                'tache_id' => $resultat->tache_id
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
                    'tache.activite.projet'
                ]))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ Erreur validation N2', [
                'resultat_id' => $resultat->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
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
                    'message' => 'Seul le responsable de l\'activité peut rejeter ce résultat (N1)'
                ], 403);
            }
        } else {
            if (!$resultat->tache->activite->projet || 
                $resultat->tache->activite->projet->responsable_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seul le responsable du projet peut rejeter ce résultat (N2)'
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
                'user_id' => $resultat->user_id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Résultat rejeté. L\'utilisateur devra le soumettre à nouveau.',
                'data' => new TacheResultatResource($resultat->fresh())
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ Erreur rejet résultat', [
                'resultat_id' => $resultat->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
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
                ]
            ]
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
            ]
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
        if ($resultat->tache->activite->projet && 
            $resultat->tache->activite->projet->responsable_id === $user->id) {
            return true;
        }

        return false;
    }

    private function canValidateN1($user, TacheResultat $resultat): bool
    {
        return $resultat->soumis_le &&
               !$resultat->valide_par_n1 &&
               $resultat->user_id !== $user->id &&
               $resultat->tache->activite->responsable_id === $user->id;
    }

    private function canValidateN2($user, TacheResultat $resultat): bool
    {
        return $resultat->valide_par_n1 &&
               !$resultat->valide_par_n2 &&
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

        if ($resultat->tache->activite->projet && 
            $resultat->tache->activite->projet->responsable_id === $user->id) {
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
                'documents'
            ])
            ->whereNotNull('soumis_le');

        // Filtrer par permissions de l'utilisateur
        if (!$user->isSuperAdmin()) {
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
        if ($userId !== $user->id && !$user->isSuperAdmin()) {
            return response()->json([
                'message' => 'Accès non autorisé'
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
            'data' => $stats
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
        if (!$user->isSuperAdmin()) {
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
                Carbon::now()->endOfWeek()
            ])->count(),
            'taux_validation_moyen' => $resultats->count() > 0
                ? round(($resultats->where('is_fully_validated', true)->count() / $resultats->count()) * 100)
                : 0,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}