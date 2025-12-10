<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
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
            'visibility' => $this->visibility,
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
            'versions' => DocumentResource::collection($this->whenLoaded('versions')),
            'permissions' => $this->whenLoaded('permissions'),
            'documentable' => $this->whenLoaded('documentable'),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
