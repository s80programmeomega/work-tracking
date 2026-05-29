<?php

namespace App\Http\Resources;

use App\Models\ProjetTag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ProjetTag $resource
 *
 * @mixin ProjetTag
 */
class ProjetTagResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    /**
     * @responseField id integer Tag unique identifier.
     * @responseField nom string Tag name.
     * @responseField couleur string Hex color code (e.g. #FF5733).
     * @responseField description string|null Optional description.
     * @responseField projets_count integer|null Number of projects using this tag (when loaded).
     * @responseField created_at string|null ISO datetime of creation.
     * @responseField updated_at string|null ISO datetime of last update.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'couleur' => $this->couleur,
            'description' => $this->description,
            'projets_count' => $this->when($this->relationLoaded('projets'), function () {
                return $this->projets->count();
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
