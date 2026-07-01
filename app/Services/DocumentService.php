<?php

namespace App\Services;

use App\Models\Activite;
use App\Models\Document;
use App\Models\DocumentDownload;
use App\Models\DocumentPermission;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\TacheResultat;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Service de gestion des documents
 *
 * Gère l'upload, le téléchargement, les versions et les permissions des documents
 */
class DocumentService
{
    protected DocumentAccessResolver $accessResolver;

    protected ImageManager $imageManager;

    public function __construct(DocumentAccessResolver $accessResolver)
    {
        $this->accessResolver = $accessResolver;
        $this->imageManager = new ImageManager(new Driver);
    }

    /**
     * ===================================================================
     * UPLOAD DE DOCUMENTS
     * ===================================================================
     */

    /**
     * Upload un document unique
     */
    public function upload(
        UploadedFile $file,
        string $entityType,
        int $entityId,
        User $user,
        array $options = []
    ): Document {
        // Vérifier que l'user peut uploader sur cette entité
        if (! $this->accessResolver->canUpload($user, $entityType, $entityId)) {
            throw new \Exception("Vous n'avez pas la permission d'uploader des documents ici");
        }

        // Vérifier l'entité
        $entity = $this->getEntity($entityType, $entityId);

        if (! $entity) {
            throw new \Exception('Entité non trouvée');
        }

        // Vérifier le workspace (null autorisé pour les CVs utilisateur)
        $workspace = $this->getWorkspaceFromEntity($entity, $entityType);

        if (! $workspace && $entityType !== User::class) {
            throw new \Exception('Workspace non trouvé');
        }

        // Vérifier les doublons si non autorisés
        if (! ($options['allow_duplicates'] ?? false)) {
            $hash = hash_file('sha256', $file->getRealPath());

            $existing = Document::where('documentable_type', $entityType)
                ->where('documentable_id', $entityId)
                ->where('hash_sha256', $hash)
                ->first();

            if ($existing) {
                throw new \Exception('Ce fichier existe déjà');
            }
        }

        return DB::transaction(function () use ($file, $entityType, $entityId, $user, $options, $workspace) {
            // Générer les noms de fichier
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $storageName = Str::uuid().'.'.$extension;

            // Définir le chemin de stockage (organisé par workspace et type)
            $disk = $options['disk'] ?? config('documents.default_disk', 'private');
            $basePath = $workspace
                ? $this->getStoragePath($workspace, $entityType, $entityId)
                : $this->getUserCvStoragePath($entityId);
            $storagePath = $basePath.'/'.$storageName;

            // Uploader le fichier
            $path = $file->storeAs($basePath, $storageName, $disk);

            if (! $path) {
                throw new \Exception("Erreur lors de l'upload du fichier");
            }

            // Créer le document
            $document = Document::create([
                'workspace_id' => $workspace?->id,
                'documentable_type' => $entityType,
                'documentable_id' => $entityId,
                'nom' => $originalName,
                'nom_stockage' => $storageName,
                'extension' => $extension,
                'mime_type' => $file->getMimeType(),
                'taille' => $file->getSize(),
                'chemin' => $path,
                'disk' => $disk,
                'description' => $options['description'] ?? null,
                'hash_sha256' => hash_file('sha256', $file->getRealPath()),
                'user_id' => $user->id,
                'version' => 1,
                'is_latest_version' => true,
                'metadata' => $this->extractMetadata($file, $options),
            ]);

            // Générer une miniature si c'est une image
            // if ($document->is_image) {
            //     $this->generateThumbnail($document);
            // }

            // Log de l'action
            activity()
                ->causedBy($user)
                ->performedOn($document)
                ->withProperties([
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'workspace_id' => $workspace?->id,
                ])
                ->log('Document uploadé');

            return $document;
        });
    }

    /**
     * Upload multiple documents
     */
    public function uploadMultiple(
        array $files,
        string $entityType,
        int $entityId,
        User $user,
        array $options = []
    ): array {
        $documents = [];
        $errors = [];

        foreach ($files as $file) {
            try {
                $documents[] = $this->upload($file, $entityType, $entityId, $user, $options);
            } catch (\Exception $e) {
                $errors[] = [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ];
            }
        }

        if (! empty($errors)) {
            throw new \Exception("Certains fichiers n'ont pas pu être uploadés : ".json_encode($errors));
        }

        return $documents;
    }

    /**
     * Créer une nouvelle version d'un document
     */
    public function createVersion(
        Document $document,
        UploadedFile $file,
        User $user
    ): Document {
        // Vérifier les permissions
        if (! $this->accessResolver->canEdit($user, $document)) {
            throw new \Exception("Vous n'avez pas la permission de créer une nouvelle version");
        }

        return DB::transaction(function () use ($document, $file, $user) {
            // Marquer l'ancienne version comme non-latest
            $document->update(['is_latest_version' => false]);

            // Upload la nouvelle version
            $newVersion = $this->upload(
                $file,
                $document->documentable_type,
                $document->documentable_id,
                $user,
                [
                    'description' => $document->description,
                    'disk' => $document->disk,
                ]
            );

            // Mettre à jour les champs de versioning
            $newVersion->update([
                'parent_id' => $document->id,
                'version' => $document->version + 1,
            ]);

            // Log
            activity()
                ->causedBy($user)
                ->performedOn($newVersion)
                ->withProperties(['previous_version' => $document->id])
                ->log('Nouvelle version créée');

            return $newVersion;
        });
    }

    /**
     * Download a document
     */
    public function download(Document $document, User $user): string
    {
        // Record download
        $this->recordDownload($document, $user);

        // Increment download count
        $document->incrementDownloadCount();

        // Return file path
        return Storage::disk($document->disk)->path($document->chemin);
    }

    /**
     * ===================================================================
     * TÉLÉCHARGEMENT
     * ===================================================================
     */

    /**
     * Enregistrer un téléchargement
     */
    public function recordDownload(Document $document, User $user, string $method = 'direct'): DocumentDownload
    {
        return DocumentDownload::create([
            'document_id' => $document->id,
            'user_id' => $user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'download_method' => $method,
            'downloaded_at' => now(),
        ]);
    }

    /**
     * ===================================================================
     * SUPPRESSION
     * ===================================================================
     */

    /**
     * Supprimer un document (soft delete)
     */
    public function delete(Document $document): bool
    {
        return DB::transaction(function () use ($document) {
            // Soft delete du document
            $document->delete();

            // Log
            activity()
                ->causedBy(auth()->user())
                ->performedOn($document)
                ->log('Document supprimé');

            return true;
        });
    }

    /**
     * Supprimer définitivement un document
     */
    public function forceDelete(Document $document): bool
    {
        return DB::transaction(function () use ($document) {
            // Supprimer le fichier physique
            $document->deleteFile();

            // Supprimer les permissions
            $document->permissions()->delete();

            // Supprimer l'historique des téléchargements
            $document->downloads()->delete();

            // Supprimer le document
            $document->forceDelete();

            // Log
            activity()
                ->causedBy(auth()->user())
                ->log('Document supprimé définitivement : '.$document->nom);

            return true;
        });
    }

    /**
     * ===================================================================
     * PERMISSIONS
     * ===================================================================
     */

    /**
     * Accorder une permission à un utilisateur
     */
    public function grantPermission(
        Document $document,
        User $targetUser,
        array $permissions,
        ?\DateTime $expiresAt = null
    ): DocumentPermission {
        return DocumentPermission::updateOrCreate(
            [
                'document_id' => $document->id,
                'permissionable_type' => User::class,
                'permissionable_id' => $targetUser->id,
            ],
            [
                'can_view' => $permissions['can_view'] ?? false,
                'can_download' => $permissions['can_download'] ?? false,
                'can_edit' => $permissions['can_edit'] ?? false,
                'can_delete' => $permissions['can_delete'] ?? false,
                'can_share' => $permissions['can_share'] ?? false,
                'expires_at' => $expiresAt,
            ]
        );
    }

    /**
     * Révoquer une permission
     */
    public function revokePermission(Document $document, User $targetUser): bool
    {
        return DocumentPermission::where('document_id', $document->id)
            ->where('permissionable_type', User::class)
            ->where('permissionable_id', $targetUser->id)
            ->delete() > 0;
    }

    /**
     * Partager avec plusieurs utilisateurs
     */
    public function shareWithUsers(
        Document $document,
        array $userIds,
        array $permissions = [],
        ?\DateTime $expiresAt = null
    ): void {
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $this->grantPermission($document, $user, $permissions, $expiresAt);
            }
        }
    }

    /**
     * Get download statistics for a document
     */
    public function getDownloadStats(Document $document): array
    {
        $downloads = DocumentDownload::where('document_id', $document->id);

        return [
            'total_downloads' => $downloads->count(),
            'unique_users' => $downloads->distinct('user_id')->count('user_id'),
            'downloads_last_7_days' => $downloads->recent(7)->count(),
            'downloads_last_30_days' => $downloads->recent(30)->count(),
            'top_downloaders' => $downloads
                ->select('user_id', DB::raw('count(*) as download_count'))
                ->groupBy('user_id')
                ->orderByDesc('download_count')
                ->limit(5)
                ->with('user:id,name')
                ->get(),
            'downloads_by_method' => $downloads
                ->select('download_method', DB::raw('count(*) as count'))
                ->groupBy('download_method')
                ->get(),
        ];
    }

    /**
     * Generate thumbnail for image (INTERVENTION IMAGE V3)
     */
    protected function generateThumbnail(Document $document): void
    {
        if (! $document->is_image) {
            return;
        }

        try {
            $sourcePath = Storage::disk($document->disk)->path($document->chemin);

            $image = $this->imageManager->read($sourcePath)
                ->cover(300, 300);

            $thumbnailPath = $this->buildThumbnailPath($document->chemin);
            $thumbnailFullPath = Storage::disk($document->disk)->path($thumbnailPath);

            $image->save($thumbnailFullPath, quality: 80);

            $document->update(['thumbnail_path' => $thumbnailPath]);
        } catch (\Exception $e) {
            logger()->error('Thumbnail generation failed: '.$e->getMessage());
        }
    }

    /**
     * Build storage path for document
     */
    protected function buildStoragePath(string $type, int $id, string $filename): string
    {
        $typeSlug = Str::slug(class_basename($type));
        $date = now()->format('Y/m');

        return "documents/{$typeSlug}/{$id}/{$date}/{$filename}";
    }

    /**
     * Build thumbnail path from original path
     */
    protected function buildThumbnailPath(string $originalPath): string
    {
        $pathInfo = pathinfo($originalPath);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'];

        // Structure : documents/workspace_1/projet/123/2024/12/thumbs/uuid.jpg
        return $directory.'/thumbs/'.$filename.'.'.$extension;
    }

    /**
     * ===================================================================
     * RÉCUPÉRATION DES DOCUMENTS
     * ===================================================================
     */

    /**
     * Récupère les documents d'une entité
     */
    public function getForEntity(
        string $entityType,
        int $entityId,
        array $options = []
    ) {
        $user = auth()->user();

        // Vérifier que l'entité existe et que l'user a accès
        $entity = $this->getEntity($entityType, $entityId);

        if (! $entity) {
            throw new \Exception('Entité non trouvée');
        }

        // Vérifier l'accès au workspace parent
        $workspace = $this->getWorkspaceFromEntity($entity, $entityType);

        if (! $workspace) {
            throw new \Exception('Workspace non trouvé pour cette entité');
        }

        if (! $this->userCanAccessWorkspace($user, $workspace)) {
            throw new \Exception("Vous n'avez pas accès à ce workspace");
        }

        // Construire la requête
        $query = Document::where('documentable_type', $entityType)
            ->where('documentable_id', $entityId)
            ->accessibleBy($user);

        // Inclure les versions si demandé
        if ($options['with_versions'] ?? false) {
            $query->with('versions');
        }

        // Inclure les relations standard
        $query->with(['user:id,nom,email,avatar', 'permissions']);

        // Trier par date de création (plus récent en premier)
        $query->latest('created_at');

        return $query->get();
    }

    /**
     * Recherche de documents
     */
    public function search(string $query, array $filters = [])
    {
        $user = auth()->user();

        $documentsQuery = Document::query()
            ->accessibleBy($user)
            ->inWorkspace($user->current_workspace_id)
            ->search($query);

        // Filtres optionnels
        if (! empty($filters['type'])) {
            $documentsQuery->byType($filters['type']);
        }

        if (! empty($filters['mime_type'])) {
            $documentsQuery->where('mime_type', 'like', $filters['mime_type'].'%');
        }

        if (! empty($filters['user_id'])) {
            $documentsQuery->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['documentable_type']) && ! empty($filters['documentable_id'])) {
            $documentsQuery->where('documentable_type', $filters['documentable_type'])
                ->where('documentable_id', $filters['documentable_id']);
        }

        // Inclure les relations
        $documentsQuery->with(['user:id,nom,email,avatar', 'documentable']);

        // Pagination
        $perPage = $filters['per_page'] ?? 15;

        return $documentsQuery->paginate($perPage);
    }

    /**
     * ===================================================================
     * HELPERS
     * ===================================================================
     */

    /**
     * Récupère une entité par son type et son ID
     */
    public function getEntity(string $type, int $id)
    {
        return match ($type) {
            Workspace::class => Workspace::find($id),
            Projet::class => Projet::find($id),
            Activite::class => Activite::find($id),
            Tache::class => Tache::find($id),
            TacheResultat::class => TacheResultat::find($id),
            User::class => User::find($id),
            default => null,
        };
    }

    /**
     * Récupère le workspace parent d'une entité
     */
    protected function getWorkspaceFromEntity($entity, string $entityType): ?Workspace
    {
        if (! $entity) {
            return null;
        }

        return match ($entityType) {
            Workspace::class => $entity,
            Projet::class => $entity->workspace,
            Activite::class => $entity->projet?->workspace,
            Tache::class => $entity->activite?->projet?->workspace,
            TacheResultat::class => $entity->tache?->activite?->projet?->workspace,
            User::class => null,
            default => null,
        };
    }

    protected function getUserCvStoragePath(int $userId): string
    {
        return "users/{$userId}/cv";
    }

    /**
     * Vérifie si un user a accès à un workspace
     */
    protected function userCanAccessWorkspace(User $user, Workspace $workspace): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($workspace->owner_id === $user->id) {
            return true;
        }

        return $workspace->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Génère le chemin de stockage pour un document
     */
    protected function getStoragePath(Workspace $workspace, string $entityType, int $entityId): string
    {
        $entityTypeName = match ($entityType) {
            Workspace::class => 'workspace',
            Projet::class => 'projet',
            Activite::class => 'activite',
            Tache::class => 'tache',
            TacheResultat::class => 'resultat',
            default => 'other',
        };

        return sprintf(
            'workspaces/%s/%s/%s',
            $workspace->id,
            $entityTypeName,
            $entityId
        );
    }

    /**
     * Extrait les métadonnées d'un fichier
     */
    protected function extractMetadata(UploadedFile $file, array $options = []): array
    {
        $metadata = [
            'original_name' => $file->getClientOriginalName(),
            'uploaded_at' => now()->toISOString(),
        ];

        // Métadonnées d'image
        if (str_starts_with($file->getMimeType(), 'image/')) {
            try {
                $imageInfo = getimagesize($file->getRealPath());

                if ($imageInfo) {
                    $metadata['width'] = $imageInfo[0];
                    $metadata['height'] = $imageInfo[1];
                    $metadata['type'] = $imageInfo[2];
                }
            } catch (\Exception $e) {
                // Ignorer les erreurs
            }
        }

        // Métadonnées personnalisées
        if (! empty($options['custom_metadata'])) {
            $metadata = array_merge($metadata, $options['custom_metadata']);
        }

        return $metadata;
    }

    /**
     * ===================================================================
     * GESTION PAR WORKSPACE
     * ===================================================================
     */

    /**
     * Récupère tous les documents d'un workspace
     */
    public function getWorkspaceDocuments(Workspace $workspace, User $user, array $filters = [])
    {
        if (! $this->userCanAccessWorkspace($user, $workspace)) {
            throw new \Exception("Vous n'avez pas accès à ce workspace");
        }

        $query = Document::query()
            ->accessibleBy($user)
            ->where(function ($q) use ($workspace) {
                // Documents directs du workspace
                $q->where('documentable_type', Workspace::class)
                    ->where('documentable_id', $workspace->id);

                // Documents des projets du workspace
                $projetIds = $workspace->projets()->pluck('id');
                if ($projetIds->isNotEmpty()) {
                    $q->orWhere(function ($pq) use ($projetIds) {
                        $pq->where('documentable_type', Projet::class)
                            ->whereIn('documentable_id', $projetIds);
                    });
                }

                // Documents des activités du workspace
                $activiteIds = Activite::whereIn('projet_id', $projetIds)->pluck('id');
                if ($activiteIds->isNotEmpty()) {
                    $q->orWhere(function ($aq) use ($activiteIds) {
                        $aq->where('documentable_type', Activite::class)
                            ->whereIn('documentable_id', $activiteIds);
                    });
                }

                // Documents des tâches du workspace
                $tacheIds = Tache::whereIn('activite_id', $activiteIds)->pluck('id');
                if ($tacheIds->isNotEmpty()) {
                    $q->orWhere(function ($tq) use ($tacheIds) {
                        $tq->where('documentable_type', Tache::class)
                            ->whereIn('documentable_id', $tacheIds);
                    });
                }
            });

        // Filtres
        if (! empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'].' 23:59:59');
        }

        $query->with(['user:id,nom,email,avatar', 'documentable']);
        $query->latest('created_at');

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Statistiques des documents d'un workspace
     */
    public function getWorkspaceStats(Workspace $workspace, User $user): array
    {
        if (! $this->userCanAccessWorkspace($user, $workspace)) {
            throw new \Exception("Vous n'avez pas accès à ce workspace");
        }

        $allDocuments = $this->getWorkspaceDocuments($workspace, $user, ['per_page' => 999999]);

        return [
            'total_documents' => $allDocuments->total(),
            'total_size' => $allDocuments->sum('taille'),
            'total_downloads' => $allDocuments->sum('download_count'),
            'by_type' => $allDocuments->groupBy('documentable_type')->map->count(),
            'by_mime_type' => $allDocuments->groupBy('mime_type')->map->count(),
            'recent_uploads' => $allDocuments->sortByDesc('created_at')->take(10)->values(),
        ];
    }
}
