<?php

// app/Http/Resources/TacheExternalLinkResource.php

namespace App\Http\Resources;

use App\Models\TacheExternalLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property TacheExternalLink $resource
 *
 * @mixin TacheExternalLink
 */
class TacheExternalLinkResource extends JsonResource
{
    /**
     * @responseField id integer Link unique identifier.
     * @responseField tache_id integer Parent task ID.
     * @responseField title string Display title of the external link.
     * @responseField url string Full URL of the external resource.
     * @responseField created_by object|null Creator summary (id, nom, email) — when loaded.
     * @responseField created_at string|null ISO datetime of creation.
     * @responseField updated_at string|null ISO datetime of last update.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tache_id' => $this->tache_id,
            'title' => $this->title,
            'url' => $this->url,
            'created_by' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'nom' => $this->createdBy->nom,
                    'email' => $this->createdBy->email,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
