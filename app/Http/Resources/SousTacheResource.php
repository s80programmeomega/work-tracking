<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SousTacheResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tache_id' => $this->tache_id,
            'titre' => $this->titre,
            'description' => $this->description,
            'statut' => $this->statut,
            'progression' => $this->progression,
            'poids' => $this->poids,
            'date_echeance' => $this->date_echeance?->format('Y-m-d'),
            'validation_n0_required' => $this->validation_n0_required,
            'validation_n1_required' => $this->validation_n1_required,
            'validation_n2_required' => $this->validation_n2_required,
            'ordre' => $this->ordre,
            'is_overdue' => $this->isOverdue(),
            'responsable' => $this->whenLoaded('responsable', fn () => [
                'id' => $this->responsable->id,
                'nom' => $this->responsable->nom,
                'prenom' => $this->responsable->prenom,
                'avatar' => $this->responsable->avatar,
            ]),
            'intervenants_count' => $this->whenLoaded('intervenants', fn () => $this->intervenants->count()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
