<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjetResource extends JsonResource
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
            'nom' => $this->nom,
            'description' => $this->description,
            'code' => $this->code,
            'date_debut' => $this->date_debut?->format('Y-m-d'),
            'date_fin' => $this->date_fin?->format('Y-m-d'),
            'responsable_id' => $this->responsable_id,
            'responsable' => new UserResource($this->whenLoaded('responsable')),
            'status' => $this->status,
            'visibility' => $this->visibility,
            'couleur' => $this->couleur,
            'budget' => $this->budget,
            'progression' => $this->progression,
            'is_template' => $this->is_template,
            'is_favorite' => $this->is_favorite,
            'objectifs' => $this->objectifs,
            'metadata' => $this->metadata,
            'archived_at' => $this->archived_at?->format('Y-m-d H:i:s'),

            // Computed attributes
            'is_overdue' => $this->is_overdue,
            'days_remaining' => $this->days_remaining,
            'member_count' => $this->when($this->relationLoaded('members'), function () {
                return $this->members->count();
            }),

            // Relationships
            'members' => ProjetMemberResource::collection($this->whenLoaded('members')),
            'tags' => ProjetTagResource::collection($this->whenLoaded('tags')),

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
