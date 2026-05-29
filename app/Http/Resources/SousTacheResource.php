<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\SousTache;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property SousTache $resource
 *
 * @mixin SousTache
 */
class SousTacheResource extends JsonResource
{
    /**
     * @responseField id integer Sub-task unique identifier.
     * @responseField tache_id integer Parent task ID.
     * @responseField titre string Sub-task title.
     * @responseField description string|null Optional description.
     * @responseField statut string Status enum: a_faire | en_cours | termine.
     * @responseField progression integer Completion percentage (0-100).
     * @responseField poids number Weight used for parent task progress calculation.
     * @responseField date_echeance string|null Due date (Y-m-d).
     * @responseField validation_n0_required boolean Whether N0 validation is required.
     * @responseField validation_n1_required boolean Whether N1 validation is required.
     * @responseField validation_n2_required boolean Whether N2 validation is required.
     * @responseField ordre integer Sort order within the parent task.
     * @responseField is_overdue boolean Whether the sub-task is past its due date.
     * @responseField responsable object|null Responsible user summary (when loaded).
     * @responseField intervenants_count integer|null Number of contributors (when loaded).
     * @responseField created_at string|null ISO 8601 creation datetime.
     * @responseField updated_at string|null ISO 8601 last-update datetime.
     */
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
