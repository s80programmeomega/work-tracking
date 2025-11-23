<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TacheResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,

            // Activité
            'activite_id' => $this->activite_id,
            'activite' => [
                'id' => $this->activite->id,
                'nom' => $this->activite->nom,
                'code' => $this->activite->code,
                'projet_id' => $this->activite->projet_id,
                'projet_nom' => $this->activite->projet->nom ?? null,
            ],

            // Informations de base
            'titre' => $this->titre,
            'description' => $this->description,
            'objectif' => $this->objectif,
            'indicateurs_resultats' => $this->indicateurs_resultats,
            'commentaire' => $this->commentaire,
            'attachments' => TacheAttachmentResource::collection($this->whenLoaded('attachments')),
            'external_links' => TacheExternalLinkResource::collection($this->whenLoaded('externalLinks')),
            // Statut et priorité
            'statut' => $this->statut->value,
            'statut_label' => $this->statut->label(),
            'statut_color' => $this->statut->color(),
            'priorite' => $this->priorite->value,
            'priorite_label' => $this->priorite->label(),
            'priorite_color' => $this->priorite->color(),
            'priorite_icon' => $this->priorite->icon(),

            // Dates
            'date_debut' => $this->date_debut?->format('Y-m-d'),
            'echeance' => $this->echeance?->format('Y-m-d'),
            'date_fin_reelle' => $this->date_fin_reelle?->format('Y-m-d'),

            // ✅ Suivi hebdomadaire
            'week_number' => $this->week_number,
            'year' => $this->year,

            // Progression
            'taux_realisation' => $this->taux_realisation,
            'estimated_hours' => $this->estimated_hours,
            'actual_hours' => $this->actual_hours,
            'time_variance' => $this->when(
                $this->estimated_hours && $this->actual_hours,
                function () {
                    return $this->actual_hours - $this->estimated_hours;
                }
            ),

            // ✅ DOUBLE VALIDATION
            'validation' => [
                'n1_required' => $this->validation_n1_required,
                'n1_validated_at' => $this->validated_n1_at?->format('Y-m-d H:i:s'),
                'n1_validated_by' => $this->when($this->validatedN1By, [
                    'id' => $this->validatedN1By?->id,
                    'nom' => $this->validatedN1By?->nom,
                ]),
                'n1_commentaire' => $this->commentaire_n1,

                'n2_required' => $this->validation_n2_required,
                'n2_validated_at' => $this->validated_n2_at?->format('Y-m-d H:i:s'),
                'n2_validated_by' => $this->when($this->validatedN2By, [
                    'id' => $this->validatedN2By?->id,
                    'nom' => $this->validatedN2By?->nom,
                ]),
                'n2_commentaire' => $this->commentaire_n2,

                'status' => $this->validation_status,
                'is_fully_validated' => $this->isFullyValidated(),
            ],

            // Assignés
            'assignees' => $this->assignees->map(function ($user) {
                return [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'pivot' => [
                        'role' => $user->pivot->role,
                        'can_edit' => (bool) $user->pivot->can_edit,
                        'can_complete' => (bool) $user->pivot->can_complete,
                        'can_validate' => (bool) $user->pivot->can_validate,
                    ],
                ];
            }),

            // Labels
            'labels' => LabelResource::collection($this->whenLoaded('labels')),

            // Sous-tâches
            'parent_tache_id' => $this->parent_tache_id,
            'is_subtask' => (bool) $this->parent_tache_id,
            'sous_taches_count' => $this->whenLoaded('sousTaches', function () {
                return $this->sousTaches->count();
            }),

            // Résultats
            'resultats_count' => $this->whenLoaded('resultats', function () {
                return $this->resultats->count();
            }),

            // Apparence
            'position' => $this->position,
            'couleur' => $this->couleur,
            'cover_image' => $this->cover_image,
            'visibility' => $this->visibility,

            // État et indicateurs
            'is_overdue' => $this->is_overdue,
            'can_be_completed' => $this->can_be_completed,
            'can_be_started' => $this->canBeStarted(),
            'verrou_reevaluation' => $this->verrou_reevaluation,

            // Archive
            'archive_status' => $this->archive_status,
            'archived_at' => $this->archived_at?->format('Y-m-d H:i:s'),

            // Métadonnées
            'metadata' => $this->metadata,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

            // ✅ Permissions pour l'utilisateur actuel
            'permissions' => $this->when($request->user(), function () use ($request) {
                $user = $request->user();
                return [
                    'can_view' => $this->isAccessibleBy($user),
                    'can_edit' => $this->canBeEditedBy($user),
                    'can_complete' => $this->canBeCompletedBy($user),
                    'can_validate_n1' => $this->canBeValidatedN1By($user),
                    'can_validate_n2' => $this->canBeValidatedN2By($user),
                ];
            }),
        ];
    }
}