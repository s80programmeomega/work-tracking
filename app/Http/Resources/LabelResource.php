<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabelResource extends JsonResource
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
                    'name' => $this->creator->name,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
