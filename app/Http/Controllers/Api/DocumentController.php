<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentResource;
use App\Jobs\ExtractDocumentTextJob;
use App\Models\Activite;
use App\Models\Document;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\DocumentDeletedNotification;
use App\Notifications\DocumentPermissionGrantedNotification;
use App\Notifications\DocumentSharedNotification;
use App\Notifications\DocumentUploadedNotification;
use App\Permissions\ContextualPermissionGate;
use App\Permissions\Permission;
use App\Services\DocumentAccessResolver;
use App\Services\DocumentService;
use App\Services\PermissionService;
use App\Services\SubscriptionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentService $documentService,
        protected SubscriptionService $subscriptionService,
        protected PermissionService $permissionService,
    ) {}

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
            $gate = app(ContextualPermissionGate::class);
            abort_unless(
                $gate->userCan($request->user(), Permission::DOCUMENTS_MANAGE_WORKSPACE, $workspace),
                403,
                __('documents.errors.unauthorized_view')
            );

            $request->validate([
                'type' => 'sometimes|string',
                'search' => 'sometimes|string|min:2',
                'per_page' => 'sometimes|integer|min:1|max:100',
                'date_from' => 'sometimes|date_format:Y-m-d',
                'date_to' => 'sometimes|date_format:Y-m-d',
            ]);

            $documents = $this->documentService->getWorkspaceDocuments(
                $workspace,
                $request->user(),
                $request->only(['type', 'search', 'per_page', 'date_from', 'date_to'])
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
        $maxMB = (int) round(config('documents.max_file_size', 10240) / 1024);

        $request->validate([
            'documentable_type' => 'required|string',
            'documentable_id' => 'required|integer',
            'files' => 'required|array|min:1',
            'files.*' => 'file|max:'.config('documents.max_file_size', 10240),
            'description' => 'sometimes|string|max:1000',
            'visibility' => 'sometimes|in:private,team,public',
            'disk' => 'sometimes|string',
            'allow_duplicates' => 'sometimes|boolean',
            'custom_metadata' => 'sometimes|array',
        ], [
            'files.required' => 'Veuillez sélectionner au moins un fichier.',
            'files.min' => 'Veuillez sélectionner au moins un fichier.',
            'files.*.file' => 'Le fichier ":attribute" n\'est pas un fichier valide.',
            'files.*.max' => "Le fichier \":attribute\" dépasse la taille maximale de {$maxMB} MB.",
            'description.max' => 'La description ne peut pas dépasser 1000 caractères.',
            'visibility.in' => 'La visibilité doit être "privé", "équipe" ou "public".',
        ]);

        $user = $request->user();

        if (! app(DocumentAccessResolver::class)->canUpload($user, $request->documentable_type, $request->documentable_id)) {
            return response()->json([
                'success' => false,
                'message' => "Vous n'avez pas la permission d'uploader des documents ici",
            ], 403);
        }

        try {
            $files = $request->file('files');

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

                $this->notifyDocumentUploaded($document, $user);
                // Extraction asynchrone du contenu textuel pour l'indexation Typesense
                ExtractDocumentTextJob::dispatch($document);

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

            foreach ($documents as $doc) {
                $this->notifyDocumentUploaded($doc, $user);
                // Extraction asynchrone du contenu textuel pour l'indexation Typesense
                ExtractDocumentTextJob::dispatch($doc);
            }

            return response()->json([
                'success' => true,
                'message' => count($documents).' documents uploadés avec succès',
                'data' => DocumentResource::collection($documents),
            ], 201);

        } catch (AuthorizationException $e) {
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
            $this->authorize('view', $document);

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
        } catch (AuthorizationException $e) {
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
            $this->authorize('update', $document);

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
        } catch (AuthorizationException $e) {
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
            $this->authorize('delete', $document);

            $user = $request->user();
            $documentNom = $document->nom;
            $documentable = $document->documentable;

            // Avertir le responsable du projet si le suppresseur n'est pas le propriétaire du document
            $shouldNotify = $document->user_id !== $user->id;

            $this->documentService->delete($document);

            if ($shouldNotify && $documentable instanceof Projet && $documentable->responsable_id) {
                $responsable = User::find($documentable->responsable_id);
                if ($responsable && $responsable->id !== $user->id) {
                    $responsable->notify(new DocumentDeletedNotification($documentNom, $user));
                }
            }

            Log::warning('Suppression de document', [
                'user_id' => $user->id,
                'document_nom' => $documentNom,
                'suppresseur' => $user->nom_complet,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document supprimé avec succès',
            ]);
        } catch (AuthorizationException $e) {
            Log::warning('Tentative non autorisée de suppression de document', [
                'user_id' => $request->user()?->id,
                'document_id' => $document->id,
                'reason' => 'unauthorized',
            ]);

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
            $this->authorize('view', $document);

            // Enregistrer le téléchargement
            $this->documentService->recordDownload($document, $request->user());

            // Incrémenter le compteur
            $document->incrementDownloadCount();

            // Retourner le fichier
            return Storage::disk($document->disk)->download(
                $document->chemin,
                $document->nom
            );
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de télécharger ce document',
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement : '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crée une nouvelle version d'un document
     */
    public function createVersion(Request $request, Document $document): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:'.config('documents.max_file_size', 10240),
        ]);

        $user = $request->user();

        if (! app(DocumentAccessResolver::class)->canEdit($user, $document)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de créer une nouvelle version',
            ], 403);
        }

        try {
            $newVersion = $this->documentService->createVersion(
                $document,
                $request->file('file'),
                $user
            );

            return response()->json([
                'success' => true,
                'message' => 'Nouvelle version créée avec succès',
                'data' => new DocumentResource($newVersion),
            ], 201);
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

            // Ajouter le document actuel avec son user chargé
            $document->loadMissing('user:id,nom,email,avatar');
            $versions->prepend($document);

            return response()->json([
                'success' => true,
                'data' => $versions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupère toutes les versions d'un document
     */
    public function _versions(Request $request, Document $document): JsonResponse
    {
        try {
            $this->authorize('view', $document);

            $versions = $document->versions()->with('user:id,nom,email')->get();

            return response()->json([
                'success' => true,
                'data' => DocumentResource::collection($versions),
                'meta' => [
                    'current_version' => $document->version,
                    'total_versions' => $versions->count() + 1,
                ],
            ]);
        } catch (AuthorizationException $e) {
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
            $this->authorize('view', $document);

            $stats = $this->documentService->getDownloadStats($document);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (AuthorizationException $e) {
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
        $workspaceId = $user->current_workspace_id;

        try {
            $projectsCount = Projet::where('workspace_id', $workspaceId)->count();

            $activitiesCount = Activite::whereHas('projet', fn ($q) => $q->where('workspace_id', $workspaceId))->count();

            $tasksCount = Tache::whereHas('activite.projet', fn ($q) => $q->where('workspace_id', $workspaceId))->count();

            // Stats documents
            $documentsQuery = Document::accessibleBy($user)->inWorkspace($workspaceId);
            $documentsCount = $documentsQuery->count();
            $totalSize = $documentsQuery->sum('taille');

            // Quota stockage depuis le plan d'abonnement
            $workspace = Workspace::find($workspaceId);
            $subscriptionSummary = $workspace ? $this->subscriptionService->summary($workspace) : null;
            $maxStorageMb = $subscriptionSummary['limits']['storage_mb'] ?? 100;
            $usedStorageBytes = Document::inWorkspace($workspaceId)->sum('taille');

            return response()->json([
                'success' => true,
                'data' => [
                    'workspaces' => 1,
                    'projects' => $projectsCount,
                    'activities' => $activitiesCount,
                    'tasks' => $tasksCount,
                    'documents' => $documentsCount,
                    'totalSize' => $totalSize,
                    'usedStorageBytes' => $usedStorageBytes,
                    'maxStorageMb' => $maxStorageMb,
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
     * Documents récents de l'utilisateur
     */
    public function recent(Request $request)
    {
        $user = auth()->user();

        try {
            $documents = Document::accessibleBy($user)
                ->inWorkspace($user->current_workspace_id)
                ->with(['user:id,nom,email,avatar', 'documentable'])
                ->latest('created_at')
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $documents,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
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
            $documents = Document::inWorkspace($user->current_workspace_id)
                ->whereHas('permissions', function ($q) use ($user) {
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
                    },
                ])
                ->latest('created_at')
                ->get();

            // Ajouter la permission directement sur chaque document
            $documents->each(function ($doc) {
                $doc->permission = $doc->permissions->first();
            });

            return response()->json([
                'success' => true,
                'data' => $documents,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
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
                ->inWorkspace($user->current_workspace_id)
                ->with(['user:id,nom,email,avatar', 'documentable'])
                ->withCount('permissions as shared_with_count')
                ->latest('created_at')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $documents,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
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

            if (! $entity) {
                return response()->json(['success' => false, 'message' => 'Entité non trouvée'], 404);
            }

            // Construire la hiérarchie selon le type
            switch ($entityType) {
                case 'App\\Models\\TacheResultat':
                    $tache = $entity->tache;
                    $activite = $tache?->activite;
                    $projet = $activite?->projet;
                    $workspace = $projet?->workspace;

                    if ($workspace) {
                        $hierarchy[] = ['type' => 'Workspace', 'label' => $workspace->nom];
                    }
                    if ($projet) {
                        $hierarchy[] = ['type' => 'Projet', 'label' => $projet->nom];
                    }
                    if ($activite) {
                        $hierarchy[] = ['type' => 'Activité', 'label' => $activite->nom];
                    }
                    if ($tache) {
                        $hierarchy[] = ['type' => 'Tâche', 'label' => $tache->titre];
                    }
                    $hierarchy[] = ['type' => 'Résultat', 'label' => 'Résultat'];
                    break;

                case 'App\\Models\\Tache':
                    $activite = $entity->activite;
                    $projet = $activite?->projet;
                    $workspace = $projet?->workspace;

                    if ($workspace) {
                        $hierarchy[] = ['type' => 'Workspace', 'label' => $workspace->nom];
                    }
                    if ($projet) {
                        $hierarchy[] = ['type' => 'Projet', 'label' => $projet->nom];
                    }
                    if ($activite) {
                        $hierarchy[] = ['type' => 'Activité', 'label' => $activite->nom];
                    }
                    $hierarchy[] = ['type' => 'Tâche', 'label' => $entity->titre];
                    break;

                case 'App\\Models\\Activite':
                    $projet = $entity->projet;
                    $workspace = $projet?->workspace;

                    if ($workspace) {
                        $hierarchy[] = ['type' => 'Workspace', 'label' => $workspace->nom];
                    }
                    if ($projet) {
                        $hierarchy[] = ['type' => 'Projet', 'label' => $projet->nom];
                    }
                    $hierarchy[] = ['type' => 'Activité', 'label' => $entity->nom];
                    break;

                case 'App\\Models\\Projet':
                    $workspace = $entity->workspace;

                    if ($workspace) {
                        $hierarchy[] = ['type' => 'Workspace', 'label' => $workspace->nom];
                    }
                    $hierarchy[] = ['type' => 'Projet', 'label' => $entity->nom];
                    break;

                case 'App\\Models\\Workspace':
                    $hierarchy[] = ['type' => 'Workspace', 'label' => $entity->nom];
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => $hierarchy,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
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
            'expires_at' => 'sometimes|nullable|date|after:today|before:+5 years',
        ], [
            'expires_at.date' => 'La date d\'expiration est invalide.',
            'expires_at.after' => 'La date d\'expiration doit être dans le futur.',
            'expires_at.before' => 'La date d\'expiration ne peut pas dépasser 5 ans.',
        ]);

        try {
            $this->authorize('share', $document);

            $targetUser = User::findOrFail($request->user_id);

            $permission = $this->documentService->grantPermission(
                $document,
                $targetUser,
                $request->only(['can_view', 'can_download', 'can_edit', 'can_delete', 'can_share']),
                $request->expires_at ? new \DateTime($request->expires_at) : null
            );

            $targetUser->notify(new DocumentPermissionGrantedNotification($document, $request->user()));

            return response()->json([
                'success' => true,
                'message' => 'Permission accordée avec succès',
                'data' => $permission,
            ]);
        } catch (AuthorizationException $e) {
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
            $this->authorize('share', $document);

            $targetUser = User::findOrFail($request->user_id);

            $this->documentService->revokePermission($document, $targetUser);

            return response()->json([
                'success' => true,
                'message' => 'Permission révoquée avec succès',
            ]);
        } catch (AuthorizationException $e) {
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
            $this->authorize('update', $document);

            $this->documentService->shareWithUsers(
                $document,
                $request->user_ids,
                $request->permissions ?? ['can_view' => true, 'can_download' => true],
                $request->expires_at ? new \DateTime($request->expires_at) : null
            );

            return response()->json([
                'success' => true,
                'message' => 'Document partagé avec '.count($request->user_ids).' utilisateur(s)',
            ]);
        } catch (AuthorizationException $e) {
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
            $this->authorize('update', $document);

            $permissions = $document->permissions()
                ->with('permissionable')
                ->active()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $permissions,
            ]);
        } catch (AuthorizationException $e) {
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
                $request->input('query'),
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
     * Partage un document par email à un destinataire externe (ne doit pas nécessairement être un utilisateur).
     */
    public function shareByEmail(Request $request, Document $document): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $this->authorize('share', $document);

            $user = $request->user();
            $email = $request->string('email')->lower()->toString();

            Notification::route('mail', $email)
                ->notify(new DocumentSharedNotification($document, $user, $email));

            Log::info('Partage de document par email', [
                'user_id' => $user->id,
                'document_id' => $document->id,
                'shared_to_email' => $email,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('documents.share.success'),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('documents.errors.unauthorized_share'),
            ], 403);
        }
    }

    /**
     * Notifie les membres cadre/manager du projet quand un document est uploadé.
     */
    private function notifyDocumentUploaded(Document $document, User $uploader): void
    {
        $documentable = $document->documentable;

        if (! $documentable instanceof Projet) {
            return;
        }

        $recipients = $documentable->members()
            ->wherePivotIn('role_id', function ($q) {
                $q->select('id')
                    ->from('roles')
                    ->whereIn('name', ['cadre', 'manager', 'owner']);
            })
            ->where('users.id', '!=', $uploader->id)
            ->get();

        foreach ($recipients as $member) {
            $member->notify(new DocumentUploadedNotification($document, $uploader));
        }
    }
}
