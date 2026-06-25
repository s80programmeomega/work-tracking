<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;

class Document extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    public function toSearchableArray(): array
    {
        $this->loadMissing(['user', 'workspace', 'documentable']);

        // Nom du projet lié si le document est attaché à un projet ou une activité/tâche
        $projetNom = '';
        if ($this->documentable instanceof Projet) {
            $projetNom = $this->documentable->nom ?? '';
        } elseif ($this->documentable instanceof Activite) {
            $projetNom = $this->documentable->projet?->nom ?? '';
        }

        return [
            'id' => (string) $this->id,
            'nom' => $this->nom,
            'description' => $this->description ?? '',
            // Contenu textuel extrait du fichier — tronqué à 5 000 chars pour Typesense
            'content_text' => mb_substr($this->content_text ?? '', 0, 5_000),
            'type' => $this->type ?? '',
            'mime_type' => $this->mime_type ?? '',
            'workspace_id' => (int) $this->workspace_id,
            'workspace_name' => $this->workspace?->nom ?? '',
            'uploader_nom' => $this->user?->nom ?? '',
            'projet_nom' => $projetNom,
            'created_at' => $this->created_at?->timestamp ?? 0,
        ];
    }

    public function searchableAs(): string
    {
        return 'documents';
    }

    protected $fillable = [
        'workspace_id',
        'documentable_type',
        'documentable_id',
        'nom',
        'nom_stockage',
        'extension',
        'mime_type',
        'taille',
        'chemin',
        'disk',
        'description',
        'metadata',
        'hash_sha256',
        'parent_id',
        'version',
        'is_latest_version',
        'thumbnail_path',
        'user_id',
        'download_count',
        'last_downloaded_at',
        'visibility',
        'allow_duplicates',
    ];

    protected $casts = [
        'metadata' => 'array',
        'taille' => 'integer',
        'version' => 'integer',
        'is_latest_version' => 'boolean',
        'allow_duplicates' => 'boolean',
        'download_count' => 'integer',
        'last_downloaded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'url',
        'thumbnail_url',
        'formatted_size',
        'is_image',
        'is_pdf',
        'is_video',
        'is_audio',
    ];

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nom', 'description', 'visibility'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Polymorphic relation to any model
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Uploader of the document
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Parent document (for versioning)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'parent_id');
    }

    /**
     * Child versions
     */
    public function versions(): HasMany
    {
        return $this->hasMany(Document::class, 'parent_id')->orderBy('version', 'desc');
    }

    /**
     * Download history
     */
    public function downloads(): HasMany
    {
        return $this->hasMany(DocumentDownload::class);
    }

    /**
     * Permissions
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(DocumentPermission::class);
    }

    /**
     * Get document URL
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->chemin);
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->thumbnail_path) {
            return null;
        }

        return Storage::disk($this->disk)->url($this->thumbnail_path);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute(): string
    {
        $size = $this->taille;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, 2).' '.$units[$i];
    }

    /**
     * Check if document is an image
     */
    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if document is a PDF
     */
    public function getIsPdfAttribute(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Check if document is a video
     */
    public function getIsVideoAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    /**
     * Check if document is audio
     */
    public function getIsAudioAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'audio/');
    }

    /**
     * Increment download count
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
        $this->update(['last_downloaded_at' => now()]);
    }

    /**
     * Scope: Latest versions only
     */
    public function scopeLatestVersions($query)
    {
        return $query->where('is_latest_version', true);
    }

    /**
     * Scope: By document type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('documentable_type', $type);
    }

    /**
     * Scope: Images only
     */
    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    /**
     * Scope: PDFs only
     */
    public function scopePdfs($query)
    {
        return $query->where('mime_type', 'application/pdf');
    }

    /**
     * Scope: Search by name
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('nom', 'like', "%{$search}%")
            ->orWhere('description', 'like', "%{$search}%");
    }

    /**
     * Scope: Accessible by user
     */
    public function scopeAccessibleBy($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            // Public documents
            $q->where('visibility', 'public')
                // Or owned by user
                ->orWhere('user_id', $user->id)
                // Or has specific permissions
                ->orWhereHas('permissions', function ($permQuery) use ($user) {
                    $permQuery->where('permissionable_type', User::class)
                        ->where('permissionable_id', $user->id)
                        ->where('can_view', true)
                        ->where(function ($expQuery) {
                            $expQuery->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now());
                        });
                });
        });
    }

    public function scopeInWorkspace($query, int $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Check if user can view this document
     */
    public function canBeViewedBy(User $user): bool
    {
        // Owner can always view
        if ($this->user_id === $user->id) {
            return true;
        }

        // Public documents
        if ($this->visibility === 'public') {
            return true;
        }

        // Check specific permissions
        $permission = $this->permissions()
            ->where('permissionable_type', User::class)
            ->where('permissionable_id', $user->id)
            ->where('can_view', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        return $permission !== null;
    }

    /**
     * Check if user can download this document
     */
    public function canBeDownloadedBy(User $user): bool
    {
        // Owner can always download
        if ($this->user_id === $user->id) {
            return true;
        }

        // Check specific permissions
        $permission = $this->permissions()
            ->where('permissionable_type', User::class)
            ->where('permissionable_id', $user->id)
            ->where('can_download', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        return $permission !== null;
    }

    /**
     * Check if user can edit this document
     */
    public function canBeEditedBy(User $user): bool
    {
        // Owner can always edit
        if ($this->user_id === $user->id) {
            return true;
        }

        // Check specific permissions
        $permission = $this->permissions()
            ->where('permissionable_type', User::class)
            ->where('permissionable_id', $user->id)
            ->where('can_edit', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        return $permission !== null;
    }

    /**
     * Check if user can delete this document
     */
    public function canBeDeletedBy(User $user): bool
    {
        // Owner can always delete
        if ($this->user_id === $user->id) {
            return true;
        }

        // Check specific permissions
        $permission = $this->permissions()
            ->where('permissionable_type', User::class)
            ->where('permissionable_id', $user->id)
            ->where('can_delete', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        return $permission !== null;
    }

    /**
     * Delete physical file from storage
     */
    public function deleteFile(): bool
    {
        $deleted = Storage::disk($this->disk)->delete($this->chemin);

        if ($this->thumbnail_path) {
            Storage::disk($this->disk)->delete($this->thumbnail_path);
        }

        return $deleted;
    }
}
