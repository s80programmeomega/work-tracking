<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TacheResultatResource;
use App\Models\TacheResultat;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 📊 EvaluationController
 * 
 * Gestion de l'historique des validations et statistiques
 */
class EvaluationController extends Controller
{
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