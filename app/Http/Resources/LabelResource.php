<?php

namespace App\Http\Resources;

use App\Models\Label;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Label $resource
 *
 * @mixin Label
 */
class LabelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    /**
     * @responseField id integer Label unique identifier.
     * @responseField projet_id integer|null Parent project ID; null for global labels.
     * @responseField nom string Label name.
     * @responseField couleur string Hex background color (e.g. #FF5733).
     * @responseField text_color string|null Computed hex text color for contrast.
     * @responseField description string|null Optional description.
     * @responseField ordre integer Sort order.
     * @responseField is_global boolean Whether the label is available across all projects.
     * @responseField usage_count integer How many times this label has been applied.
     * @responseField taches_count integer|null Number of tasks using this label (when counted).
     * @responseField projet object|null Parent project summary (id, nom) — when loaded.
     * @responseField creator object|null Creator user summary (id, name) — when loaded.
     * @responseField created_at string|null ISO datetime of creation.
     * @responseField updated_at string|null ISO datetime of last update.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'projet_id' => $this->projet_id,
            'nom' => $this->nom,
            'couleur' => $this->couleur,
            'text_color' => $this->text_color,
            'description' => $this->description,
            'ordre' => $this->ordre,
            'is_global' => $this->is_global,
            'usage_count' => $this->usage_count,
            'taches_count' => $this->whenCounted('taches'),
            'projet' => $this->whenLoaded('projet', function () {
                return [
                    'id' => $this->projet->id,
                    'nom' => $this->projet->nom,
                ];
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->nom,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
