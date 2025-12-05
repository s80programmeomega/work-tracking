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
use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;

class DocumentController extends Controller
{
    protected DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Récupère les documents d'une entité (Projet, Activité, Tâche, etc.)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'documentable_type' => 'required|string',
                'documentable_id' => 'required|integer',
                'with_versions' => 'sometimes|boolean', // ✅ FIX: sometimes au lieu de nullable
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
     * Récupère tous les documents d'un workspace
     */
    public function workspaceDocuments(Request $request, Workspace $workspace): JsonResponse
    {
        try {
            $request->validate([
                'type' => 'sometimes|string',
                'search' => 'sometimes|string|min:2',
                'per_page' => 'sometimes|integer|min:1|max:100',
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
     * Upload un ou plusieurs documents
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'documentable_type' => 'required|string',
            'documentable_id' => 'required|integer',
            'files' => 'required|array',
            'files.*' => 'required|file|max:' . config('documents.max_file_size', 10240),
            'description' => 'sometimes|string|max:1000',
            'visibility' => 'sometimes|in:private,team,public',
            'disk' => 'sometimes|string',
            'allow_duplicates' => 'sometimes|boolean',
            'custom_metadata' => 'sometimes|array',
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
     * Met à jour les métadonnées d'un document
     */
    public function update(Request $request, Document $document): JsonResponse
    {
        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'visibility' => 'sometimes|in:private,team,public',
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
            return Storage::disk($document->disk)->download(
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
     * Liste les versions d'un document
     */
    public function versions(Document $document)
    {
        try {
            $versions = Document::where(function ($q) use ($document) {
                $q->where('parent_id', $document->id)
                    ->orWhere('id', $document->parent_id)
                    ->orWhere(function ($q) use ($document) {
                        if ($document->parent_id) {
                            $q->where('parent_id', $document->parent_id);
                        }
                    });
            })
                ->where('id', '!=', $document->id)
                ->with('user:id,nom,email,avatar')
                ->orderByDesc('version')
                ->get();

            // Ajouter le document actuel
            $versions->prepend($document);

            return response()->json([
                'success' => true,
                'data' => $versions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère toutes les versions d'un document
     */
    public function _versions(Request $request, Document $document): JsonResponse
    {
        try {
            Gate::authorize('view', $document);

            $versions = $document->versions()->with('user:id,nom,email')->get();

            return response()->json([
                'success' => true,
                'data' => DocumentResource::collection($versions),
                'meta' => [
                    'current_version' => $document->version,
                    'total_versions' => $versions->count() + 1,
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
     * Statistiques globales
     */
    public function globalStats(Request $request)
    {
        $user = auth()->user();

        try {
            // Compter les workspaces accessibles
            $workspacesCount = Workspace::where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('members', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })->count();

            // Compter les projets accessibles
            $projectsCount = Projet::whereHas('workspace', function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('members', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })->count();

            // Compter les activités accessibles
            $activitiesCount = Activite::whereHas('projet.workspace', function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('members', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })->count();

            // Compter les tâches accessibles
            $tasksCount = Tache::whereHas('activite.projet.workspace', function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('members', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })->count();

            // Stats documents
            $documentsQuery = Document::accessibleBy($user);
            $documentsCount = $documentsQuery->count();
            $totalSize = $documentsQuery->sum('taille');

            return response()->json([
                'success' => true,
                'data' => [
                    'workspaces' => $workspacesCount,
                    'projects' => $projectsCount,
                    'activities' => $activitiesCount,
                    'tasks' => $tasksCount,
                    'documents' => $documentsCount,
                    'totalSize' => $totalSize
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Documents récents de l'utilisateur
     */
    public function recent(Request $request)
    {
        $user = auth()->user();

        try {
            $documents = Document::accessibleBy($user)
                ->with(['user:id,nom,email,avatar', 'documentable'])
                ->latest('created_at')
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $documents
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Documents partagés avec l'utilisateur
     */
    public function sharedWithMe(Request $request)
    {
        $user = auth()->user();

        try {
            $documents = Document::whereHas('permissions', function ($q) use ($user) {
                $q->where('permissionable_type', User::class)
                    ->where('permissionable_id', $user->id)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    });
            })
                ->with([
                    'user:id,nom,email,avatar',
                    'documentable',
                    'permissions' => function ($q) use ($user) {
                        $q->where('permissionable_type', User::class)
                            ->where('permissionable_id', $user->id);
                    }
                ])
                ->latest('created_at')
                ->get();

            // Ajouter la permission directement sur chaque document
            $documents->each(function ($doc) {
                $doc->permission = $doc->permissions->first();
            });

            return response()->json([
                'success' => true,
                'data' => $documents
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Documents créés par l'utilisateur
     */
    public function myDocuments(Request $request)
    {
        $user = auth()->user();

        try {
            $documents = Document::where('user_id', $user->id)
                ->with(['user:id,nom,email,avatar', 'documentable'])
                ->withCount('permissions as shared_with_count')
                ->latest('created_at')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $documents
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Récupère la hiérarchie d'une entité
     */
    public function hierarchy(Request $request)
    {
        $entityType = $request->input('documentable_type');
        $entityId = $request->input('documentable_id');

        try {
            $hierarchy = [];
            $entity = $this->documentService->getEntity($entityType, $entityId);

            if (!$entity) {
                return response()->json(['success' => false, 'message' => 'Entité non trouvée'], 404);
            }

            // Construire la hiérarchie selon le type
            switch ($entityType) {
                case 'App\\Models\\TacheResultat':
                    $tache = $entity->tache;
                    $activite = $tache?->activite;
                    $projet = $activite?->projet;
                    $workspace = $projet?->workspace;

                    if ($workspace)
                        $hierarchy[] = ['type' => 'Workspace', 'label' => $workspace->nom];
                    if ($projet)
                        $hierarchy[] = ['type' => 'Projet', 'label' => $projet->nom];
                    if ($activite)
                        $hierarchy[] = ['type' => 'Activité', 'label' => $activite->nom];
                    if ($tache)
                        $hierarchy[] = ['type' => 'Tâche', 'label' => $tache->titre];
                    $hierarchy[] = ['type' => 'Résultat', 'label' => 'Résultat'];
                    break;

                case 'App\\Models\\Tache':
                    $activite = $entity->activite;
                    $projet = $activite?->projet;
                    $workspace = $projet?->workspace;

                    if ($workspace)
                        $hierarchy[] = ['type' => 'Workspace', 'label' => $workspace->nom];
                    if ($projet)
                        $hierarchy[] = ['type' => 'Projet', 'label' => $projet->nom];
                    if ($activite)
                        $hierarchy[] = ['type' => 'Activité', 'label' => $activite->nom];
                    $hierarchy[] = ['type' => 'Tâche', 'label' => $entity->titre];
                    break;

                case 'App\\Models\\Activite':
                    $projet = $entity->projet;
                    $workspace = $projet?->workspace;

                    if ($workspace)
                        $hierarchy[] = ['type' => 'Workspace', 'label' => $workspace->nom];
                    if ($projet)
                        $hierarchy[] = ['type' => 'Projet', 'label' => $projet->nom];
                    $hierarchy[] = ['type' => 'Activité', 'label' => $entity->nom];
                    break;

                case 'App\\Models\\Projet':
                    $workspace = $entity->workspace;

                    if ($workspace)
                        $hierarchy[] = ['type' => 'Workspace', 'label' => $workspace->nom];
                    $hierarchy[] = ['type' => 'Projet', 'label' => $entity->nom];
                    break;

                case 'App\\Models\\Workspace':
                    $hierarchy[] = ['type' => 'Workspace', 'label' => $entity->nom];
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => $hierarchy
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Accorde une permission à un utilisateur
     */
    public function grantPermission(Request $request, Document $document): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'can_view' => 'sometimes|boolean',
            'can_download' => 'sometimes|boolean',
            'can_edit' => 'sometimes|boolean',
            'can_delete' => 'sometimes|boolean',
            'can_share' => 'sometimes|boolean',
            'expires_at' => 'sometimes|date|after:now',
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
     * Révoque une permission
     */
    public function revokePermission(Request $request, Document $document): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            Gate::authorize('managePermissions', $document);

            $targetUser = \App\Models\User::findOrFail($request->user_id);

            $this->documentService->revokePermission($document, $targetUser);

            return response()->json([
                'success' => true,
                'message' => 'Permission révoquée avec succès',
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de gérer les permissions',
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
            'permissions' => 'sometimes|array',
            'permissions.can_view' => 'sometimes|boolean',
            'permissions.can_download' => 'sometimes|boolean',
            'permissions.can_edit' => 'sometimes|boolean',
            'permissions.can_delete' => 'sometimes|boolean',
            'permissions.can_share' => 'sometimes|boolean',
            'expires_at' => 'sometimes|date|after:now',
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

    /**
     * Recherche de documents
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'type' => 'sometimes|string',
            'user_id' => 'sometimes|integer',
            'documentable_type' => 'sometimes|string',
            'documentable_id' => 'sometimes|integer',
            'mime_type' => 'sometimes|string',
            'per_page' => 'sometimes|integer|min:1|max:100',
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
}