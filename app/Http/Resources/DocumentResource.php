<?php

namespace App\Http\Resources;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Document $resource
 *
 * @mixin Document
 */
class DocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    /**
     * @responseField id integer Document unique identifier.
     * @responseField documentable_type string Fully-qualified class name of the owning model.
     * @responseField documentable_id integer ID of the owning model instance.
     * @responseField nom string Display filename.
     * @responseField nom_stockage string Storage filename (may differ from display name).
     * @responseField extension string File extension (e.g. pdf, png).
     * @responseField mime_type string MIME type.
     * @responseField taille integer File size in bytes.
     * @responseField formatted_size string Human-readable file size (e.g. "1.2 MB").
     * @responseField chemin string Storage path.
     * @responseField disk string Storage disk name.
     * @responseField url string|null Public access URL.
     * @responseField thumbnail_url string|null Thumbnail URL for image/PDF files.
     * @responseField description string|null Optional description.
     * @responseField metadata object|null Arbitrary key-value metadata.
     * @responseField hash_sha256 string|null SHA-256 hash for integrity verification.
     * @responseField version integer Version number (1-based).
     * @responseField is_latest_version boolean Whether this is the latest version.
     * @responseField parent_id integer|null ID of the previous version document.
     * @responseField download_count integer Total number of downloads.
     * @responseField last_downloaded_at string|null ISO 8601 datetime of last download.
     * @responseField is_image boolean Whether the file is an image.
     * @responseField is_pdf boolean Whether the file is a PDF.
     * @responseField is_video boolean Whether the file is a video.
     * @responseField is_audio boolean Whether the file is audio.
     * @responseField user object Uploader summary (id, name).
     * @responseField versions DocumentResource[]|null Previous versions (when loaded).
     * @responseField permissions object[]|null Share permissions (when loaded).
     * @responseField documentable object|null Owning model (when loaded).
     * @responseField created_at string|null ISO 8601 creation datetime.
     * @responseField updated_at string|null ISO 8601 last-update datetime.
     * @responseField deleted_at string|null ISO 8601 soft-deletion datetime.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'documentable_type' => $this->documentable_type,
            'documentable_id' => $this->documentable_id,
            'nom' => $this->nom,
            'nom_stockage' => $this->nom_stockage,
            'extension' => $this->extension,
            'mime_type' => $this->mime_type,
            'taille' => $this->taille,
            'formatted_size' => $this->formatted_size,
            'chemin' => $this->chemin,
            'disk' => $this->disk,
            'url' => $this->url,
            'thumbnail_url' => $this->thumbnail_url,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'hash_sha256' => $this->hash_sha256,
            'version' => $this->version,
            'is_latest_version' => $this->is_latest_version,
            'parent_id' => $this->parent_id,
            'download_count' => $this->download_count,
            'last_downloaded_at' => $this->last_downloaded_at?->toISOString(),
            'is_image' => $this->is_image,
            'is_pdf' => $this->is_pdf,
            'is_video' => $this->is_video,
            'is_audio' => $this->is_audio,

            // User relationship
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->nom,
            ],

            // Conditional relationships
            'versions' => $this->whenLoaded('versions', fn () => DocumentResource::collection($this->versions)),
            'permissions' => $this->whenLoaded('permissions'),
            'documentable' => $this->whenLoaded('documentable'),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
