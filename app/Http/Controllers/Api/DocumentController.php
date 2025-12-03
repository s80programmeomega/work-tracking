<?php

namespace App\Http\Controllers\Api;

use App\Models\Document;
use App\Services\DocumentService;
use App\Http\Resources\DocumentResource;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Http\Controllers\Controller;

class DocumentController extends Controller
{
    protected DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

   /**
     * ===================================================================
     * LISTE ET RECHERCHE
     * ===================================================================
     */

    /**
     * Récupère les documents d'une entité (Projet, Activité, Tâche, etc.)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'documentable_type' => 'required|string',
                'documentable_id' => 'required|integer',
                'with_versions' => 'nullable|boolean',
            ]);

            $documents = $this->documentService->getForEntity(
                $request->documentable_type,
                $request->documentable_id,
                [
                    'with_versions' => $request->boolean('with_versions', false),
                ]
            );

            return response()->json([
                'success' => true,
                'data' => DocumentResource::collection($documents),
                'meta' => [
                    'total' => $documents->count(),
                    'entity_type' => class_basename($request->documentable_type),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

 /**
     * Recherche de documents
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'type' => 'nullable|string',
            'user_id' => 'nullable|integer',
            'documentable_type' => 'nullable|string',
            'documentable_id' => 'nullable|integer',
            'mime_type' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $results = $this->documentService->search(
                $request->query,
                $request->only(['type', 'user_id', 'documentable_type', 'documentable_id', 'mime_type', 'per_page'])
            );

            return response()->json([
                'success' => true,
                'data' => DocumentResource::collection($results->items()),
                'meta' => [
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

     /**
     * ===================================================================
     * GESTION PAR WORKSPACE
     * ===================================================================
     */

    /**
     * Récupère tous les documents d'un workspace
     */
    public function workspaceDocuments(Request $request, Workspace $workspace): JsonResponse
    {
        try {
            $request->validate([
                'type' => 'nullable|string',
                'search' => 'nullable|string|min:2',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $documents = $this->documentService->getWorkspaceDocuments(
                $workspace,
                $request->user(),
                $request->only(['type', 'search', 'per_page'])
            );

            return response()->json([
                'success' => true,
                'data' => DocumentResource::collection($documents->items()),
                'meta' => [
                    'current_page' => $documents->currentPage(),
                    'last_page' => $documents->lastPage(),
                    'per_page' => $documents->perPage(),
                    'total' => $documents->total(),
                    'workspace' => [
                        'id' => $workspace->id,
                        'nom' => $workspace->nom,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

     /**
     * Statistiques des documents d'un workspace
     */
    public function workspaceStats(Request $request, Workspace $workspace): JsonResponse
    {
        try {
            $stats = $this->documentService->getWorkspaceStats($workspace, $request->user());

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

  /**
     * ===================================================================
     * UPLOAD DE DOCUMENTS
     * ===================================================================
     */

    /**
     * Upload un ou plusieurs documents
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'documentable_type' => 'required|string',
            'documentable_id' => 'required|integer',
            'files' => 'required|array',
            'files.*' => 'required|file|max:' . config('documents.max_file_size', 10240),
            'description' => 'nullable|string|max:1000',
            'visibility' => 'nullable|in:private,team,public',
            'disk' => 'nullable|string',
            'allow_duplicates' => 'nullable|boolean',
            'custom_metadata' => 'nullable|array',
        ]);

        try {
            $user = $request->user();
            $files = $request->file('files');

            // Vérifier la permission d'upload via Gate
            Gate::authorize('create', [
                Document::class,
                $request->documentable_type,
                $request->documentable_id
            ]);

            $options = [
                'description' => $request->description,
                'visibility' => $request->visibility ?? 'private',
                'disk' => $request->disk ?? config('documents.default_disk', 'public'),
                'allow_duplicates' => $request->boolean('allow_duplicates', false),
                'custom_metadata' => $request->custom_metadata ?? [],
            ];

            // Upload unique ou multiple
            if (count($files) === 1) {
                $document = $this->documentService->upload(
                    $files[0],
                    $request->documentable_type,
                    $request->documentable_id,
                    $user,
                    $options
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Document uploadé avec succès',
                    'data' => new DocumentResource($document),
                ], 201);
            }

            // Upload multiple
            $documents = $this->documentService->uploadMultiple(
                $files,
                $request->documentable_type,
                $request->documentable_id,
                $user,
                $options
            );

            return response()->json([
                'success' => true,
                'message' => count($documents) . ' documents uploadés avec succès',
                'data' => DocumentResource::collection($documents),
            ], 201);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => "Vous n'avez pas la permission d'uploader des documents ici",
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }


     /**
     * ===================================================================
     * CONSULTER UN DOCUMENT
     * ===================================================================
     */

    /**
     * Affiche les détails d'un document
     */
    public function show(Request $request, Document $document): JsonResponse
    {
        try {
            Gate::authorize('view', $document);

            $document->load([
                'user:id,nom,email,avatar',
                'versions',
                'permissions.permissionable',
                'documentable',
            ]);

            return response()->json([
                'success' => true,
                'data' => new DocumentResource($document),
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de voir ce document',
            ], 403);
        }
    }

    /**
     * ===================================================================
     * MODIFIER UN DOCUMENT
     * ===================================================================
     */

    /**
     * Met à jour les métadonnées d'un document
     */
    public function update(Request $request, Document $document): JsonResponse
    {
        $request->validate([
            'nom' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'visibility' => 'nullable|in:private,team,public',
        ]);

        try {
            Gate::authorize('update', $document);

            $document->update($request->only(['nom', 'description', 'visibility']));

            activity()
                ->causedBy($request->user())
                ->performedOn($document)
                ->log('Document mis à jour');

            return response()->json([
                'success' => true,
                'message' => 'Document mis à jour avec succès',
                'data' => new DocumentResource($document->fresh()),
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de modifier ce document',
            ], 403);
        }
    }

   /**
     * ===================================================================
     * SUPPRIMER UN DOCUMENT
     * ===================================================================
     */

    /**
     * Supprime un document (soft delete)
     */
    public function destroy(Request $request, Document $document): JsonResponse
    {
        try {
            Gate::authorize('delete', $document);

            $this->documentService->delete($document);

            return response()->json([
                'success' => true,
                'message' => 'Document supprimé avec succès',
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de supprimer ce document',
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
 /**
     * ===================================================================
     * TÉLÉCHARGER UN DOCUMENT
     * ===================================================================
     */

    /**
     * Télécharge un document
     */
    public function download(Request $request, Document $document): StreamedResponse|JsonResponse
    {
        try {
            Gate::authorize('download', $document);

            // Enregistrer le téléchargement
            $this->documentService->recordDownload($document, $request->user());

            // Incrémenter le compteur
            $document->incrementDownloadCount();

            // Retourner le fichier
            return \Storage::disk($document->disk)->download(
                $document->chemin,
                $document->nom
            );
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de télécharger ce document',
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ===================================================================
     * VERSIONING
     * ===================================================================
     */

    /**
     * Crée une nouvelle version d'un document
     */
    public function createVersion(Request $request, Document $document): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:' . config('documents.max_file_size', 10240),
        ]);

        try {
            Gate::authorize('update', $document);

            $newVersion = $this->documentService->createVersion(
                $document,
                $request->file('file'),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Nouvelle version créée avec succès',
                'data' => new DocumentResource($newVersion),
            ], 201);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de créer une nouvelle version',
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

     /**
     * Récupère toutes les versions d'un document
     */
    public function versions(Request $request, Document $document): JsonResponse
    {
        try {
            Gate::authorize('view', $document);

            $versions = $document->versions()->with('user:id,nom,email')->get();

            return response()->json([
                'success' => true,
                'data' => DocumentResource::collection($versions),
                'meta' => [
                    'current_version' => $document->version,
                    'total_versions' => $versions->count() + 1, // +1 pour le document actuel
                ],
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de voir les versions',
            ], 403);
        }
    }

    
    /**
     * ===================================================================
     * STATISTIQUES
     * ===================================================================
     */

    /**
     * Récupère les statistiques de téléchargement
     */
    public function stats(Request $request, Document $document): JsonResponse
    {
        try {
            Gate::authorize('view', $document);

            $stats = $this->documentService->getDownloadStats($document);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de voir les statistiques',
            ], 403);
        }
    }


    /**
     * ===================================================================
     * GESTION DES PERMISSIONS
     * ===================================================================
     */

    /**
     * Accorde une permission à un utilisateur
     */
    public function grantPermission(Request $request, Document $document): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'can_view' => 'nullable|boolean',
            'can_download' => 'nullable|boolean',
            'can_edit' => 'nullable|boolean',
            'can_delete' => 'nullable|boolean',
            'can_share' => 'nullable|boolean',
            'expires_at' => 'nullable|date|after:now',
        ]);

        try {
            Gate::authorize('managePermissions', $document);

            $targetUser = \App\Models\User::findOrFail($request->user_id);

            $permission = $this->documentService->grantPermission(
                $document,
                $targetUser,
                $request->only(['can_view', 'can_download', 'can_edit', 'can_delete', 'can_share']),
                $request->expires_at ? new \DateTime($request->expires_at) : null
            );

            return response()->json([
                'success' => true,
                'message' => 'Permission accordée avec succès',
                'data' => $permission,
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le propriétaire peut accorder des permissions',
            ], 403);
        }
    }

   /**
     * Partage avec plusieurs utilisateurs
     */
    public function shareWithUsers(Request $request, Document $document): JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'permissions' => 'nullable|array',
            'permissions.can_view' => 'nullable|boolean',
            'permissions.can_download' => 'nullable|boolean',
            'permissions.can_edit' => 'nullable|boolean',
            'permissions.can_delete' => 'nullable|boolean',
            'permissions.can_share' => 'nullable|boolean',
            'expires_at' => 'nullable|date|after:now',
        ]);

        try {
            Gate::authorize('share', $document);

            $this->documentService->shareWithUsers(
                $document,
                $request->user_ids,
                $request->permissions ?? ['can_view' => true, 'can_download' => true],
                $request->expires_at ? new \DateTime($request->expires_at) : null
            );

            return response()->json([
                'success' => true,
                'message' => 'Document partagé avec ' . count($request->user_ids) . ' utilisateur(s)',
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de partager ce document',
            ], 403);
        }
    }
    /**
     * Liste les permissions d'un document
     */
    public function listPermissions(Request $request, Document $document): JsonResponse
    {
        try {
            Gate::authorize('managePermissions', $document);

            $permissions = $document->permissions()
                ->with('permissionable')
                ->active()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $permissions,
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de voir les permissions',
            ], 403);
        }
    }
}
