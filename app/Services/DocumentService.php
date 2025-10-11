<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentDownload;
use App\Models\DocumentPermission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class DocumentService
{
    /**
     * Upload a document
     */
    public function upload(
        UploadedFile $file,
        string $documentableType,
        int $documentableId,
        User $user,
        array $options = []
    ): Document {
        DB::beginTransaction();

        try {
            // Generate file hash for deduplication
            $hash = hash_file('sha256', $file->getRealPath());

            // Check if file already exists
            $existingDocument = Document::where('hash_sha256', $hash)->first();

            if ($existingDocument && ($options['allow_duplicates'] ?? false) === false) {
                // Return existing document if deduplication is enabled
                DB::commit();
                return $existingDocument;
            }

            // Prepare file information
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $mimeType = $file->getMimeType();
            $size = $file->getSize();

            // Generate unique storage name
            $storageName = Str::uuid() . '.' . $extension;

            // Determine storage disk
            $disk = $options['disk'] ?? config('documents.default_disk', 'local');

            // Build storage path
            $path = $this->buildStoragePath($documentableType, $documentableId, $storageName);

            // Store the file
            $storedPath = $file->storeAs(
                dirname($path),
                basename($path),
                ['disk' => $disk]
            );

            // Create document record
            $document = Document::create([
                'documentable_type' => $documentableType,
                'documentable_id' => $documentableId,
                'nom' => $originalName,
                'nom_stockage' => $storageName,
                'extension' => $extension,
                'mime_type' => $mimeType,
                'taille' => $size,
                'chemin' => $storedPath,
                'disk' => $disk,
                'description' => $options['description'] ?? null,
                'metadata' => $options['metadata'] ?? null,
                'hash_sha256' => $hash,
                'user_id' => $user->id,
                'visibility' => $options['visibility'] ?? 'team',
                'version' => 1,
                'is_latest_version' => true,
            ]);

            // Generate thumbnail for images
            if (str_starts_with($mimeType, 'image/')) {
                $this->generateThumbnail($document);
            }

            DB::commit();

            return $document;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Upload multiple documents
     */
    public function uploadMultiple(
        array $files,
        string $documentableType,
        int $documentableId,
        User $user,
        array $options = []
    ): array {
        $documents = [];

        foreach ($files as $file) {
            $documents[] = $this->upload($file, $documentableType, $documentableId, $user, $options);
        }

        return $documents;
    }

    /**
     * Create a new version of a document
     */
    public function createVersion(Document $parent, UploadedFile $file, User $user): Document
    {
        DB::beginTransaction();

        try {
            // Mark parent and all siblings as not latest
            Document::where('parent_id', $parent->parent_id ?? $parent->id)
                ->orWhere('id', $parent->parent_id ?? $parent->id)
                ->update(['is_latest_version' => false]);

            // Upload new version
            $newVersion = $this->upload(
                $file,
                $parent->documentable_type,
                $parent->documentable_id,
                $user,
                [
                    'disk' => $parent->disk,
                    'visibility' => $parent->visibility,
                    'description' => $parent->description,
                ]
            );

            // Set version information
            $newVersion->update([
                'parent_id' => $parent->parent_id ?? $parent->id,
                'version' => ($parent->version ?? 1) + 1,
                'is_latest_version' => true,
            ]);

            DB::commit();

            return $newVersion;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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
     * Record a download event
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
     * Delete a document
     */
    public function delete(Document $document): bool
    {
        DB::beginTransaction();

        try {
            // Delete physical file
            $document->deleteFile();

            // Delete all versions if this is a parent
            if (!$document->parent_id) {
                foreach ($document->versions as $version) {
                    $version->deleteFile();
                    $version->forceDelete();
                }
            }

            // Soft delete the document
            $document->delete();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Grant permission to user
     */
    public function grantPermission(
        Document $document,
        User $user,
        array $permissions = [],
        ?\DateTime $expiresAt = null
    ): DocumentPermission {
        return DocumentPermission::updateOrCreate(
            [
                'document_id' => $document->id,
                'permissionable_type' => User::class,
                'permissionable_id' => $user->id,
            ],
            array_merge([
                'can_view' => true,
                'can_download' => true,
                'can_edit' => false,
                'can_delete' => false,
                'can_share' => false,
            ], $permissions, [
                'expires_at' => $expiresAt,
            ])
        );
    }

    /**
     * Revoke permission from user
     */
    public function revokePermission(Document $document, User $user): bool
    {
        return DocumentPermission::where('document_id', $document->id)
            ->where('permissionable_type', User::class)
            ->where('permissionable_id', $user->id)
            ->delete();
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
     * Generate thumbnail for image
     */
    protected function generateThumbnail(Document $document): void
    {
        if (!$document->is_image) {
            return;
        }

        try {
            $thumbnailWidth = config('documents.thumbnail_width', 300);
            $thumbnailHeight = config('documents.thumbnail_height', 300);

            $image = Image::make(Storage::disk($document->disk)->path($document->chemin));

            // Resize maintaining aspect ratio
            $image->fit($thumbnailWidth, $thumbnailHeight, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            // Generate thumbnail path
            $thumbnailPath = $this->buildThumbnailPath($document->chemin);

            // Save thumbnail
            $thumbnailFullPath = Storage::disk($document->disk)->path($thumbnailPath);
            $image->save($thumbnailFullPath);

            // Update document with thumbnail path
            $document->update(['thumbnail_path' => $thumbnailPath]);
        } catch (\Exception $e) {
            // Log error but don't fail the upload
            logger()->error('Failed to generate thumbnail: ' . $e->getMessage());
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
        return $pathInfo['dirname'] . '/thumbs/' . $pathInfo['basename'];
    }

    /**
     * Search documents
     */
    public function search(string $query, array $filters = [])
    {
        $documents = Document::query()
            ->latestVersions()
            ->search($query);

        // Apply filters
        if (isset($filters['type'])) {
            $documents->byType($filters['type']);
        }

        if (isset($filters['user_id'])) {
            $documents->where('user_id', $filters['user_id']);
        }

        if (isset($filters['documentable_type'])) {
            $documents->where('documentable_type', $filters['documentable_type']);
        }

        if (isset($filters['documentable_id'])) {
            $documents->where('documentable_id', $filters['documentable_id']);
        }

        if (isset($filters['mime_type'])) {
            $documents->where('mime_type', 'like', $filters['mime_type'] . '%');
        }

        return $documents->with(['user:id,name', 'documentable'])
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get documents for entity
     */
    public function getForEntity(string $type, int $id, array $options = [])
    {
        $query = Document::where('documentable_type', $type)
            ->where('documentable_id', $id)
            ->latestVersions();

        if ($options['with_versions'] ?? false) {
            $query->with('versions');
        }

        return $query->with(['user:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
