<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TacheResource;
use App\Models\Tache;
use App\Models\TacheResultat;
use App\Http\Resources\TacheResultatResource;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 📊 TacheResultatController
 * 
 * Gestion des résultats hebdomadaires avec:
 * - Upload de documents (polymorphic)
 * - Double validation N1/N2
 * - Soumission et rejet
 */
class TacheResultatController extends Controller
{
    /**
     * 📋 Liste des résultats d'une tâche
     */
    public function index(Tache $tache)
    {
        Gate::authorize('view', $tache);

        $resultats = $tache->resultats()
            ->with(['user', 'validateurN1', 'validateurN2', 'documents'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => TacheResultatResource::collection($resultats)
        ]);
    }

    /**
     * ➕ Créer un résultat
     */
    public function store(Request $request, Tache $tache)
    {
        Gate::authorize('update', $tache);

        $validated = $request->validate([
            'resultats_attendus' => 'required|string',
            'resultats_obtenus' => 'required|string',
            'taux_realisation' => 'required|integer|min:0|max:100',
            'difficultes_rencontrees' => 'nullable|string',
            'solutions_envisagees' => 'nullable|string',
            'observations' => 'nullable|string',
            'documents.*' => 'nullable|file|max:10240', // 10MB
        ]);

        return DB::transaction(function () use ($validated, $tache, $request) {
            // Créer le résultat
            $resultat = $tache->resultats()->create([
                'user_id' => auth()->id(),
                'resultats_attendus' => $validated['resultats_attendus'],
                'resultats_obtenus' => $validated['resultats_obtenus'],
                'taux_realisation' => $validated['taux_realisation'],
                'difficultes_rencontrees' => $validated['difficultes_rencontrees'] ?? null,
                'solutions_envisagees' => $validated['solutions_envisagees'] ?? null,
                'observations' => $validated['observations'] ?? null,
            ]);

            // Upload des documents si présents
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $this->uploadDocument($resultat, $file);
                }
            }

            activity()
                ->causedBy(auth()->user())
                ->performedOn($resultat)
                ->withProperties(['tache_id' => $tache->id])
                ->log('Résultat créé');

            return response()->json([
                'success' => true,
                'message' => 'Résultat enregistré avec succès',
                'data' => new TacheResultatResource($resultat->load(['user', 'documents']))
            ], 201);
        });
    }

    /**
     * 👁️ Voir un résultat
     */
    public function show(Tache $tache, TacheResultat $resultat)
    {
        Gate::authorize('view', $tache);

        if ($resultat->tache_id !== $tache->id) {
            abort(404, 'Résultat non trouvé');
        }

        return response()->json([
            'success' => true,
            'data' => new TacheResultatResource(
                $resultat->load(['user', 'validateurN1', 'validateurN2', 'documents'])
            )
        ]);
    }

    /**
     * ✏️ Modifier un résultat
     */
    public function update(Request $request, Tache $tache, TacheResultat $resultat)
    {
        Gate::authorize('update', $tache);

        if ($resultat->tache_id !== $tache->id) {
            abort(404);
        }

        // Ne pas modifier un résultat validé
        if ($resultat->is_fully_validated && !auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de modifier un résultat entièrement validé'
            ], 403);
        }

        $validated = $request->validate([
            'resultats_attendus' => 'sometimes|string',
            'resultats_obtenus' => 'sometimes|string',
            'taux_realisation' => 'sometimes|integer|min:0|max:100',
            'difficultes_rencontrees' => 'nullable|string',
            'solutions_envisagees' => 'nullable|string',
            'observations' => 'nullable|string',
            'documents.*' => 'nullable|file|max:10240',
        ]);

        return DB::transaction(function () use ($validated, $resultat, $request) {
            $resultat->update($validated);

            // Upload nouveaux documents
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $this->uploadDocument($resultat, $file);
                }
            }

            activity()
                ->causedBy(auth()->user())
                ->performedOn($resultat)
                ->withProperties(['changes' => $validated])
                ->log('Résultat modifié');

            return response()->json([
                'success' => true,
                'message' => 'Résultat mis à jour',
                'data' => new TacheResultatResource($resultat->fresh(['user', 'documents']))
            ]);
        });
    }

    /**
     * 🗑️ Supprimer un résultat
     */
    public function destroy(Tache $tache, TacheResultat $resultat)
    {
        Gate::authorize('update', $tache);

        if ($resultat->tache_id !== $tache->id) {
            abort(404);
        }

        if ($resultat->is_fully_validated && !auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer un résultat validé'
            ], 403);
        }

        // Supprimer les documents
        foreach ($resultat->documents as $document) {
            if ($document->chemin_fichier) {
                Storage::disk('public')->delete($document->chemin_fichier);
            }
            $document->delete();
        }

        $resultat->delete();

        return response()->json([
            'success' => true,
            'message' => 'Résultat supprimé'
        ]);
    }

    /**
     * 📤 Soumettre un résultat pour validation
     */
    public function submit(Request $request, Tache $tache, TacheResultat $resultat)
    {
        Gate::authorize('update', $tache);

        if ($resultat->tache_id !== $tache->id) {
            abort(404);
        }

        if ($resultat->soumis_le) {
            return response()->json([
                'success' => false,
                'message' => 'Ce résultat a déjà été soumis'
            ], 400);
        }

        $resultat->submit();

        return response()->json([
            'success' => true,
            'message' => 'Résultat soumis pour validation',
            'data' => new TacheResultatResource($resultat->fresh())
        ]);
    }

    /**
     * ✅ MODIFIÉ : Soumettre mon résultat individuel (utilise TacheResultat existant)
     */
    public function submitMyResult(Request $request, Tache $tache): JsonResponse
    {
        $validated = $request->validate([
            'resultats_attendus' => 'required|string|min:10',
            'resultats_obtenus' => 'required|string|min:10',
            'taux_realisation' => 'required|integer|min:0|max:100',
            'difficultes_rencontrees' => 'nullable|string',
            'solutions_envisagees' => 'nullable|string',
            'observations' => 'nullable|string',
            'documents.*' => 'nullable|file|max:10240',
        ]);

        $user = $request->user();

        try {
            // Vérifier que l'utilisateur a terminé sa partie
            $statutUser = $tache->getStatutForUser($user);
            if ($statutUser !== 'termine') {
                return response()->json([
                    'message' => 'Vous devez d\'abord terminer votre partie de la tâche'
                ], 422);
            }

            // Créer ou mettre à jour le résultat
            $resultat = $tache->soumettreResultatIndividuel($user, $validated);

            // Gérer les documents
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $this->uploadDocument($resultat, $file);
                }
            }

            // Notifier les responsables
            $this->notifyResponsablesOfResult($tache, $user, $resultat);

            return response()->json([
                'message' => 'Votre résultat a été soumis avec succès',
                'data' => [
                    'resultat' => new TacheResultatResource($resultat->fresh(['documents'])),
                    'tache' => new TacheResource($tache->fresh()),
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erreur soumission résultat individuel', [
                'tache_id' => $tache->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * ✅ Valider N1 (Responsable Activité)
     */
    public function validateN1(Request $request, Tache $tache, TacheResultat $resultat)
    {
        // Vérifier permission via le modèle
        if (!$resultat->canBeValidatedByN1(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de valider ce résultat (N1)'
            ], 403);
        }

        if ($resultat->tache_id !== $tache->id) {
            abort(404);
        }

        $validated = $request->validate([
            'commentaire' => 'nullable|string|max:1000',
        ]);

        try {
            $resultat->validateByN1(auth()->user(), $validated['commentaire'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Résultat validé (Niveau 1)',
                'data' => new TacheResultatResource($resultat->fresh())
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * ✅✅ Valider N2 (Responsable Projet)
     */
    public function validateN2(Request $request, Tache $tache, TacheResultat $resultat)
    {
        if (!$resultat->canBeValidatedByN2(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de valider ce résultat (N2)'
            ], 403);
        }

        if ($resultat->tache_id !== $tache->id) {
            abort(404);
        }

        $validated = $request->validate([
            'commentaire' => 'nullable|string|max:1000',
        ]);

        try {
            $resultat->validateByN2(auth()->user(), $validated['commentaire'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Résultat validé (Niveau 2 - Final)',
                'data' => new TacheResultatResource($resultat->fresh())
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * ❌ Rejeter un résultat
     */
    public function reject(Request $request, Tache $tache, TacheResultat $resultat)
    {
        $validated = $request->validate([
            'commentaire' => 'required|string|max:1000',
            'level' => 'required|in:n1,n2',
        ]);

        if ($validated['level'] === 'n1' && !$resultat->canBeValidatedByN1(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Permission refusée'
            ], 403);
        }

        if ($validated['level'] === 'n2' && !$resultat->canBeValidatedByN2(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Permission refusée'
            ], 403);
        }

        $resultat->reject(auth()->user(), $validated['commentaire'], $validated['level']);

        return response()->json([
            'success' => true,
            'message' => 'Résultat rejeté',
            'data' => new TacheResultatResource($resultat->fresh())
        ]);
    }

    /**
     * 📜 Historique via Activity Log
     */
    public function history(Tache $tache, TacheResultat $resultat)
    {
        Gate::authorize('view', $tache);

        if ($resultat->tache_id !== $tache->id) {
            abort(404);
        }

        $activities = activity()
            ->forSubject($resultat)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }

    /**
     * 📊 Résultats en attente de validation
     */
    public function pendingValidations(Request $request)
    {
        $user = $request->user();

        $resultats = TacheResultat::query()
            ->with(['tache.activite.projet', 'user', 'documents'])
            ->requiringValidationFrom($user)
            ->latest('soumis_le')
            ->get();

        $pendingN1 = $resultats->where('valide_par_n1', false);
        $pendingN2 = $resultats->where('valide_par_n1', true)->where('valide_par_n2', false);

        return response()->json([
            'success' => true,
            'data' => [
                'pending_n1' => TacheResultatResource::collection($pendingN1),
                'pending_n2' => TacheResultatResource::collection($pendingN2),
                'counts' => [
                    'n1' => $pendingN1->count(),
                    'n2' => $pendingN2->count(),
                    'total' => $resultats->count(),
                ]
            ]
        ]);
    }

    // ==================== MÉTHODES PRIVÉES ====================

    /**
     * Upload d'un document (polymorphic relation)
     */
    private function uploadDocument(TacheResultat $resultat, $file, ?string $titre = null)
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $path = $file->storeAs('resultats', $filename, 'public');

        $document = $resultat->documents()->create([
            'nom' => $titre ?? $originalName,
            'nom_fichier' => $originalName,
            'chemin_fichier' => $path,
            'taille_fichier' => $file->getSize(),
            'type_fichier' => $file->getMimeType(),
            'extension' => $extension,
            'uploaded_by' => auth()->id(),
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($resultat)
            ->withProperties(['document' => $originalName])
            ->log('Document ajouté au résultat');

        return $document;
    }
}