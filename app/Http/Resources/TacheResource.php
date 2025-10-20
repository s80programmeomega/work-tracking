<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TacheResource extends JsonResource
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
            'activite_id' => $this->activite_id,
            'activite' => [
                'id' => $this->activite->id,
                'nom' => $this->activite->nom,
                'projet_id' => $this->activite->projet_id,
            ],
            'titre' => $this->titre,
            'code' => $this->code,
            'description' => $this->description,
            'objectif' => $this->objectif,
            'indicateurs_resultats' => $this->indicateurs_resultats,
            'statut' => $this->statut->value,
            'statut_label' => $this->statut->label(),
            'statut_color' => $this->statut->color(),
            'priorite' => $this->priorite->value,
            'priorite_label' => $this->priorite->label(),
            'priorite_color' => $this->priorite->color(),
            'priorite_icon' => $this->priorite->icon(),
            'echeance' => $this->echeance?->format('Y-m-d'),
            'date_debut' => $this->date_debut?->format('Y-m-d'),
            'date_fin_reelle' => $this->date_fin_reelle?->format('Y-m-d'),
            'taux_realisation' => $this->taux_realisation,
            'validation_superieur' => $this->validation_superieur,
            'verrou_reevaluation' => $this->verrou_reevaluation,
            'validated_at' => $this->validated_at?->format('Y-m-d H:i:s'),
            'commentaire' => $this->commentaire,
            'validateur_id' => $this->validateur_id,
            'validateur' => $this->when($this->validateur, [
                'id' => $this->validateur?->id,
                'nom' => $this->validateur?->nom,
                'email' => $this->validateur?->email,
            ]),
            'assignees' => $this->assignees->map(function ($user) {
                return [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                ];
            }),
            'labels' => LabelResource::collection($this->whenLoaded('labels')),
            'position' => $this->position,
            'couleur' => $this->couleur,
            'cover_image' => $this->cover_image,
            'metadata' => $this->metadata,
            'estimated_hours' => $this->estimated_hours,
            'actual_hours' => $this->actual_hours,
            'archive_status' => $this->archive_status,
            'archived_at' => $this->archived_at?->format('Y-m-d H:i:s'),
            'is_overdue' => $this->isOverdue(),
            'can_be_started' => $this->canBeStarted(),
            'dependencies' => $this->whenLoaded('dependencies', function() {
                return $this->dependencies->map(fn($dep) => [
                    'id' => $dep->id,
                    'titre' => $dep->titre,
                    'code' => $dep->code,
                    'statut' => $dep->statut->value,
                ]);
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
        ];
    }
}
