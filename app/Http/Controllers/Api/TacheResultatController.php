<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TacheResource;
use App\Models\Tache;
use App\Models\TacheResultat;
use App\Http\Resources\TacheResultatResource;
use App\Models\Document;
use App\Models\DocumentDownload;
use App\Models\DocumentPermission;
use App\Notifications\ResultatRejeteNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 📊 TacheResultatController - Version Améliorée
 * 
 * Gestion complète des résultats avec:
 * - Documents attachés (polymorphic via table documents)
 * - Validation N1/N2 avec gestion du statut individuel
 * - Visualisation des documents existants
 * - Suppression/mise à jour de documents
 * - Notifications appropriées
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
     * ➕ Soumettre mon résultat individuel
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
            'documents_to_delete' => 'nullable|array', // IDs des documents à supprimer
            'documents_to_delete.*' => 'integer|exists:documents,id',
        ]);

        $user = $request->user();

        try {
            DB::beginTransaction();

            // Vérifier que l'utilisateur a terminé sa partie
            // $statutUser = $tache->getStatutForUser($user);
            // if ($statutUser !== 'termine') {
            //     return response()->json([
            //         'message' => 'Vous devez d\'abord terminer votre partie de la tâche'
            //     ], 422);
            // }

            // Créer ou mettre à jour le résultat
            $resultat = $tache->resultatsIndividuels()
                ->where('user_id', $user->id)
                ->first();

            if ($resultat) {
                // Mise à jour
                $resultat->update([
                    'resultats_attendus' => $validated['resultats_attendus'],
                    'resultats_obtenus' => $validated['resultats_obtenus'],
                    'taux_realisation' => $validated['taux_realisation'],
                    'difficultes_rencontrees' => $validated['difficultes_rencontrees'] ?? null,
                    'solutions_envisagees' => $validated['solutions_envisagees'] ?? null,
                    'observations' => $validated['observations'] ?? null,
                    'soumis_le' => now(),
                    'valide_par_n1' => false,
                    'valide_le_n1' => null,
                    'validateur_n1_id' => null,
                    'commentaire_n1' => null,
                    'valide_par_n2' => false,
                    'valide_le_n2' => null,
                    'validateur_n2_id' => null,
                    'commentaire_n2' => null,
                ]);

                Log::info('Résultat mis à jour', [
                    'resultat_id' => $resultat->id,
                    'tache_id' => $tache->id,
                    'user_id' => $user->id
                ]);
            } else {
                // Création
                $resultat = $tache->resultatsIndividuels()->create([
                    'user_id' => $user->id,
                    'is_individual' => true,
                    'resultats_attendus' => $validated['resultats_attendus'],
                    'resultats_obtenus' => $validated['resultats_obtenus'],
                    'taux_realisation' => $validated['taux_realisation'],
                    'difficultes_rencontrees' => $validated['difficultes_rencontrees'] ?? null,
                    'solutions_envisagees' => $validated['solutions_envisagees'] ?? null,
                    'observations' => $validated['observations'] ?? null,
                    'soumis_le' => now(),
                ]);

                Log::info('Nouveau résultat créé', [
                    'resultat_id' => $resultat->id,
                    'tache_id' => $tache->id,
                    'user_id' => $user->id
                ]);
            }

            // Supprimer les documents demandés
            if (!empty($validated['documents_to_delete'])) {
                $documentsToDelete = Document::whereIn('id', $validated['documents_to_delete'])
                    ->where('documentable_type', TacheResultat::class)
                    ->where('documentable_id', $resultat->id)
                    ->get();

                foreach ($documentsToDelete as $doc) {
                    // Supprimer le fichier physique
                    if ($doc->chemin && Storage::disk($doc->disk)->exists($doc->chemin)) {
                        Storage::disk($doc->disk)->delete($doc->chemin);
                    }

                    // Supprimer les permissions associées
                    DocumentPermission::where('document_id', $doc->id)->delete();

                    // Supprimer l'historique des téléchargements
                    DocumentDownload::where('document_id', $doc->id)->delete();

                    $doc->delete();

                    Log::info('Document supprimé', [
                        'document_id' => $doc->id,
                        'resultat_id' => $resultat->id
                    ]);
                }
            }

            // Ajouter nouveaux documents avec permissions
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $this->uploadDocumentWithPermissions($resultat, $file, $tache);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Votre résultat a été soumis avec succès',
                'data' => [
                    'resultat' => new TacheResultatResource($resultat->fresh(['documents', 'user'])),
                    'tache' => new TacheResource($tache->fresh()),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur soumission résultat individuel', [
                'tache_id' => $tache->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la soumission du résultat',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue'
            ], 500);
        }
    }

    /**
     * 📥 Télécharger un document avec suivi
     */
    public function downloadDocument(Tache $tache, TacheResultat $resultat, Document $document)
    {
        Gate::authorize('view', $tache);

        // Vérifier que le document appartient au résultat
        if (
            $document->documentable_type !== TacheResultat::class ||
            $document->documentable_id !== $resultat->id
        ) {
            abort(404, 'Document non trouvé');
        }

        // Vérifier les permissions de téléchargement
        if (!$document->canBeDownloadedBy(auth()->user())) {
            abort(403, 'Vous n\'avez pas la permission de télécharger ce document');
        }

        // Vérifier l'existence du fichier
        if (!Storage::disk($document->disk)->exists($document->chemin)) {
            abort(404, 'Fichier non trouvé');
        }

        try {
            // Enregistrer le téléchargement
            DocumentDownload::create([
                'document_id' => $document->id,
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'download_method' => 'direct',
                'downloaded_at' => now(),
            ]);

            // Incrémenter le compteur
            $document->incrementDownloadCount();

            // Retourner le fichier
            return Storage::disk($document->disk)->download(
                $document->chemin,
                $document->nom . '.' . $document->extension
            );

        } catch (\Exception $e) {
            Log::error('Erreur téléchargement document', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);

            abort(500, 'Erreur lors du téléchargement');
        }
    }

    /**
     * 🔍 Voir un document (prévisualisation)
     */
    public function viewDocument(Tache $tache, TacheResultat $resultat, Document $document)
    {
        Gate::authorize('view', $tache);

        // Vérifier que le document appartient au résultat
        if (
            $document->documentable_type !== TacheResultat::class ||
            $document->documentable_id !== $resultat->id
        ) {
            abort(404, 'Document non trouvé');
        }

        // Vérifier les permissions de visualisation
        if (!$document->canBeViewedBy(auth()->user())) {
            abort(403, 'Vous n\'avez pas la permission de voir ce document');
        }

        // Vérifier l'existence du fichier
        if (!Storage::disk($document->disk)->exists($document->chemin)) {
            abort(404, 'Fichier non trouvé');
        }

        // Pour les images et PDF, on peut retourner une réponse de fichier
        if ($document->is_image || $document->is_pdf) {
            return response()->file(
                Storage::disk($document->disk)->path($document->chemin)
            );
        }

        // Pour les autres types, forcer le téléchargement
        return Storage::disk($document->disk)->download(
            $document->chemin,
            $document->nom . '.' . $document->extension
        );
    }

    /**
     * 📊 Statistiques des documents
     */
    public function documentStats(Tache $tache, TacheResultat $resultat)
    {
        Gate::authorize('view', $tache);

        $documents = $resultat->documents()
            ->withCount('downloads')
            ->with([
                'downloads' => function ($query) {
                    $query->recent(30)->with('user');
                }
            ])
            ->get();

        $stats = [
            'total_documents' => $documents->count(),
            'total_downloads' => $documents->sum('download_count'),
            'recent_downloads' => $documents->sum(function ($doc) {
                return $doc->downloads->count();
            }),
            'documents' => $documents->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'nom' => $doc->nom,
                    'download_count' => $doc->download_count,
                    'recent_downloads' => $doc->downloads->count(),
                    'last_downloaded_at' => $doc->last_downloaded_at,
                    'recent_downloaders' => $doc->downloads->take(5)->map(function ($download) {
                        return [
                            'user' => $download->user?->only(['id', 'nom', 'email']),
                            'downloaded_at' => $download->downloaded_at,
                        ];
                    }),
                ];
            }),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * ✅ Valider N1 avec mise à jour du statut individuel
     */
    public function validateN1(Request $request, Tache $tache, TacheResultat $resultat)
    {
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
            DB::beginTransaction();

            $resultat->validateByN1(auth()->user(), $validated['commentaire'] ?? null);

            Log::info('Résultat validé N1', [
                'resultat_id' => $resultat->id,
                'validateur_id' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Résultat validé (Niveau 1)',
                'data' => new TacheResultatResource($resultat->fresh())
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur validation N1', [
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
     * ✅ Valider N2 avec mise à jour du statut individuel
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
            DB::beginTransaction();

            $resultat->validateByN2(auth()->user(), $validated['commentaire'] ?? null);

            Log::info('Résultat validé N2', [
                'resultat_id' => $resultat->id,
                'validateur_id' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Résultat validé (Niveau 2 - Final)',
                'data' => new TacheResultatResource($resultat->fresh())
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur validation N2', [
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
     * ❌ Rejeter un résultat avec mise à jour du statut
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

        try {
            DB::beginTransaction();

            // Rejeter le résultat
            $resultat->reject(auth()->user(), $validated['commentaire'], $validated['level']);

            // Remettre le statut individuel à "a_faire"
            $tache->updateStatutForUser(
                $resultat->user,
                'a_faire',
                0 // Réinitialiser la progression
            );

            Log::info('Résultat rejeté', [
                'resultat_id' => $resultat->id,
                'level' => $validated['level'],
                'user_id' => $resultat->user_id,
                'nouveau_statut' => 'a_faire'
            ]);

            // Notifier l'utilisateur
            $resultat->user->notify(
                new ResultatRejeteNotification($tache, $resultat, $validated['commentaire'])
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Résultat rejeté. L\'utilisateur devra le soumettre à nouveau.',
                'data' => new TacheResultatResource($resultat->fresh())
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur rejet résultat', [
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

    /**
     * 📁 Récupérer les documents d'un résultat
     */
    public function getDocuments(Tache $tache, TacheResultat $resultat)
    {
        Gate::authorize('view', $tache);

        if ($resultat->tache_id !== $tache->id) {
            abort(404, 'Résultat non trouvé');
        }

        $documents = $resultat->documents()
            ->with('user')
            ->latest()
            ->get()
            ->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'nom' => $doc->nom,
                    'nom_fichier' => $doc->nom_fichier,
                    'taille' => $doc->taille_fichier,
                    'type_fichier' => $doc->type_fichier,
                    'extension' => $doc->extension,
                    'url' => Storage::disk('public')->url($doc->chemin_fichier),
                    'uploaded_by' => $doc->user ? [
                        'id' => $doc->user->id,
                        'nom' => $doc->user->nom,
                    ] : null,
                    'created_at' => $doc->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $documents
        ]);
    }

    /**
     * 🗑️ Supprimer un document d'un résultat
     */
    public function deleteDocument(Tache $tache, TacheResultat $resultat, Document $document)
    {
        Gate::authorize('update', $tache);

        if ($resultat->tache_id !== $tache->id) {
            abort(404);
        }

        // Vérifier que le document appartient bien à ce résultat
        if (
            $document->documentable_type !== TacheResultat::class ||
            $document->documentable_id !== $resultat->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Document non trouvé pour ce résultat'
            ], 404);
        }

        // Ne pas supprimer si le résultat est validé (sauf admin)
        if ($resultat->is_fully_validated && !auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer un document d\'un résultat validé'
            ], 403);
        }

        // Supprimer le fichier physique
        if ($document->chemin_fichier) {
            Storage::disk('public')->delete($document->chemin_fichier);
        }

        $document->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($resultat)
            ->withProperties(['document' => $document->nom])
            ->log('Document supprimé du résultat');

        return response()->json([
            'success' => true,
            'message' => 'Document supprimé avec succès'
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
        $path = $file->storeAs('resultats/taches', $filename, 'public');

        $document = $resultat->documents()->create([
            'nom' => $titre ?? pathinfo($originalName, PATHINFO_FILENAME),
            'nom_stockage' => $filename,                   // ✔ obligatoire
            'extension' => $extension,
            'mime_type' => $file->getMimeType(),           // ✔ correspond au modèle
            'taille' => $file->getSize(),                  // ✔ correspond au modèle
            'chemin' => $path,                             // ✔ correspond au modèle
            'disk' => 'public',                            // ✔ manquait
            'user_id' => auth()->id(),                     // ✔ correspond
            'visibility' => 'private',
            'metadata' => null,

        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($resultat)
            ->withProperties(['document' => $originalName])
            ->log('Document ajouté au résultat');

        return $document;
    }

    /**
     * Notifier les responsables
     */
    private function notifyResponsablesOfResult(Tache $tache, $user, TacheResultat $resultat): void
    {
        $responsables = collect();

        // Responsable activité
        if ($tache->activite->responsable) {
            $responsables->push($tache->activite->responsable);
        }

        // Responsable projet
        if ($tache->activite->projet && $tache->activite->projet->responsable) {
            $responsables->push($tache->activite->projet->responsable);
        }

        // Notifier (sans doublon)
        $responsables->unique('id')
            ->reject(fn($r) => $r->id === $user->id)
            ->each(fn($r) => $r->notify(
                new \App\Notifications\ResultatIndividuelSoumisNotification($tache, $user, $resultat)
            ));
    }


    /**
     * Upload d'un document avec système de permissions
     */
    private function uploadDocumentWithPermissions(TacheResultat $resultat, $file, Tache $tache)
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;

        // Stockage dans le dossier private
        $path = $file->storeAs('resultats/taches', $filename, 'private');

        // Créer le document
        $document = $resultat->documents()->create([
            'nom' => pathinfo($originalName, PATHINFO_FILENAME),
            'nom_stockage' => $filename,
            'extension' => $extension,
            'mime_type' => $file->getMimeType(),
            'taille' => $file->getSize(),
            'chemin' => $path,
            'disk' => 'private', // ← Stockage privé
            'user_id' => auth()->id(),
            'visibility' => 'private',
            'metadata' => [
                'original_name' => $originalName,
                'uploaded_by' => auth()->user()->nom,
                'uploaded_at' => now()->toISOString(),
            ],
        ]);

        // Créer les permissions
        $this->createDocumentPermissions($document, $tache);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($resultat)
            ->withProperties(['document' => $originalName])
            ->log('Document ajouté au résultat');

        return $document;
    }

    /**
     * Créer les permissions pour un document
     */
    private function createDocumentPermissions(Document $document, Tache $tache)
    {
        $permissions = collect();
        $currentUserId = auth()->id();

        // 1. Permission pour l'utilisateur uploader (plein accès)
        $permissions->push([
            'document_id' => $document->id,
            'permissionable_type' => \App\Models\User::class,
            'permissionable_id' => $currentUserId,
            'can_view' => true,
            'can_download' => true,
            'can_edit' => true,
            'can_delete' => true,
            'can_share' => true,
        ]);

        // 2. Responsable d'activité
        if ($tache->activite->responsable && $tache->activite->responsable->id !== $currentUserId) {
            $permissions->push([
                'document_id' => $document->id,
                'permissionable_type' => \App\Models\User::class,
                'permissionable_id' => $tache->activite->responsable->id,
                'can_view' => true,
                'can_download' => true,
                'can_edit' => false,
                'can_delete' => false,
                'can_share' => false,
            ]);
        }

        // 3. Responsable de projet
        if (
            $tache->activite->projet &&
            $tache->activite->projet->responsable &&
            $tache->activite->projet->responsable->id !== $currentUserId
        ) {
            $permissions->push([
                'document_id' => $document->id,
                'permissionable_type' => \App\Models\User::class,
                'permissionable_id' => $tache->activite->projet->responsable->id,
                'can_view' => true,
                'can_download' => true,
                'can_edit' => false,
                'can_delete' => false,
                'can_share' => false,
            ]);
        }

        // 4. Validateurs N1 et N2 (uniques et différents de l'uploader)
        $validateurs = collect();

        if ($tache->validation_n1_required && $tache->validateur_n1_id && $tache->validateur_n1_id !== $currentUserId) {
            $validateurs->push($tache->validateur_n1_id);
        }

        if ($tache->validation_n2_required && $tache->validateur_n2_id && $tache->validateur_n2_id !== $currentUserId) {
            $validateurs->push($tache->validateur_n2_id);
        }
        foreach ($validateurs->unique() as $validateurId) {
            $permissions->push([
                'document_id' => $document->id,
                'permissionable_type' => \App\Models\User::class,
                'permissionable_id' => $validateurId,
                'can_view' => true,
                'can_download' => true,
                'can_edit' => false,
                'can_delete' => false,
                'can_share' => false,
            ]);
        }

        // ❌ Éviter les doublons
        $permissions = $permissions->unique(function ($item) {
            return $item['document_id'] . '-' . $item['permissionable_type'] . '-' . $item['permissionable_id'];
        })->values();

        if ($permissions->isNotEmpty()) {
            DocumentPermission::insert($permissions->all());

            // Log pour debug
            Log::info('Permissions créées pour le document', [
                'document_id' => $document->id,
                'permissions_count' => $permissions->count(),
                'users' => $permissions->pluck('permissionable_id')->toArray()
            ]);
        }

    }



}