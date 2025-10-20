<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActiviteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'projet_id' => $this->projet_id,
            'projet' => new ProjetResource($this->whenLoaded('projet')),
            'nom' => $this->nom,
            'description' => $this->description,
            'code' => $this->code,
            'responsable_id' => $this->responsable_id,
            'responsable' => new UserResource($this->whenLoaded('responsable')),
            'date_debut' => $this->date_debut?->format('Y-m-d'),
            'date_fin' => $this->date_fin?->format('Y-m-d'),
            'ordre' => $this->ordre,
            'status' => $this->status,
            'progression' => $this->progression,
            'couleur' => $this->couleur,
            'metadata' => $this->metadata,
            'archived_at' => $this->archived_at?->toDateTimeString(),
            'is_overdue' => $this->is_overdue,
            'days_remaining' => $this->days_remaining,
            'tache_count' => $this->tache_count,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
