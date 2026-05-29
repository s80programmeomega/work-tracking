<?php

// app/Http/Resources/TacheAttachmentResource.php

namespace App\Http\Resources;

use App\Models\TacheAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property TacheAttachment $resource
 *
 * @mixin TacheAttachment
 */
class TacheAttachmentResource extends JsonResource
{
    /**
     * @responseField id integer Attachment unique identifier.
     * @responseField tache_id integer Parent task ID.
     * @responseField file_name string Storage filename.
     * @responseField original_name string Original filename as uploaded.
     * @responseField file_size integer Size in bytes.
     * @responseField formatted_file_size string Human-readable size (e.g. "1.2 MB").
     * @responseField mime_type string MIME type (e.g. "application/pdf").
     * @responseField file_url string Public URL to download the file.
     * @responseField uploaded_by object|null Uploader summary (id, nom, email) — when loaded.
     * @responseField created_at string|null ISO datetime of upload.
     * @responseField updated_at string|null ISO datetime of last update.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tache_id' => $this->tache_id,
            'file_name' => $this->file_name,
            'original_name' => $this->original_name,
            'file_size' => $this->file_size,
            'formatted_file_size' => $this->formatted_file_size,
            'mime_type' => $this->mime_type,
            'file_url' => $this->file_url,
            'uploaded_by' => $this->whenLoaded('uploadedBy', function () {
                return [
                    'id' => $this->uploadedBy->id,
                    'nom' => $this->uploadedBy->nom,
                    'email' => $this->uploadedBy->email,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
