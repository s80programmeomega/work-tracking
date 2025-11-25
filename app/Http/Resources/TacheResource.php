<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TacheResource extends JsonResource
{


    public function toArray(Request $request): array
    {
        $user = $request->user();
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

            // ✅ NOUVEAU : Mon statut personnel
            'my_status' => $this->when($user && $this->isAssignedTo($user), function () use ($user) {
                $pivot = $this->assignees()->where('user_id', $user->id)->first();

                return [
                    'statut' => $pivot?->pivot->statut_individuel ?? $this->statut->value,
                    'progression' => $pivot?->pivot->progression_individuelle ?? 0,
                    'started_at' => $pivot?->pivot->started_at?->format('Y-m-d H:i:s'),
                    'completed_at' => $pivot?->pivot->completed_at?->format('Y-m-d H:i:s'),
                    'notes_personnelles' => $pivot?->pivot->notes_personnelles,
                    'can_move' => true, // L'utilisateur peut toujours bouger sa propre carte
                ];
            }),

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
                'n1_validated_by' => $this->when($this->validatedN1By, function () {
                    return [
                        'id' => $this->validatedN1By->id,
                        'nom' => $this->validatedN1By->nom,
                    ];
                }),
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

            // ✅ NOUVEAU : Assignés avec leurs statuts individuels
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
                        // ✅ Statut individuel
                        'statut_individuel' => $user->pivot->statut_individuel,
                        'progression_individuelle' => $user->pivot->progression_individuelle,
                        'started_at' => $user->pivot->started_at?->format('Y-m-d H:i:s'),
                        'completed_at' => $user->pivot->completed_at?->format('Y-m-d H:i:s'),
                    ],
                ];
            }),

            // ✅ NOUVEAU : Statistiques d'équipe
            'team_stats' => $this->when($this->assignees->count() > 1, function () {
                $stats = $this->getStatistiquesAssignes();
                $termine = collect($stats)->where('statut', 'termine')->count();
                $enCours = collect($stats)->where('statut', 'en_cours')->count();
                $aFaire = collect($stats)->where('statut', 'a_faire')->count();
                $total = count($stats);

                return [
                    'termine_count' => $termine,
                    'en_cours_count' => $enCours,
                    'a_faire_count' => $aFaire,
                    'total' => $total,
                    'completion_percentage' => $total > 0 ? round(($termine / $total) * 100) : 0,
                    'tous_ont_termine' => $this->tousLesAssignesOntTermine(),
                ];
            }),

            // ✅ NOUVEAU : Mon résultat individuel
            'my_result' => $this->when($user && $this->isAssignedTo($user), function () use ($user) {
                $resultat = $this->getResultatForUser($user);

                return $resultat ? [
                    'id' => $resultat->id,
                    'is_individual' => $resultat->is_individual,
                    'resultats_attendus' => $resultat->resultats_attendus,
                    'resultats_obtenus' => $resultat->resultats_obtenus,
                    'taux_realisation' => $resultat->taux_realisation,
                    'difficultes_rencontrees' => $resultat->difficultes_rencontrees,
                    'solutions_envisagees' => $resultat->solutions_envisagees,
                    'observations' => $resultat->observations,
                    'soumis_le' => $resultat->soumis_le?->format('Y-m-d H:i:s'),
                    'valide_par_n1' => $resultat->valide_par_n1,
                    'valide_le_n1' => $resultat->valide_le_n1?->format('Y-m-d H:i:s'),
                    'commentaire_n1' => $resultat->commentaire_n1,
                    'valide_par_n2' => $resultat->valide_par_n2,
                    'valide_le_n2' => $resultat->valide_le_n2?->format('Y-m-d H:i:s'),
                    'commentaire_n2' => $resultat->commentaire_n2,
                    'is_fully_validated' => $resultat->is_fully_validated,
                    'validation_status' => $resultat->validation_status,
                    'documents_count' => $resultat->documents()->count(),
                    'created_at' => $resultat->created_at->format('Y-m-d H:i:s'),
                ] : null;
            }),

            // ✅ NOUVEAU : Résultats de tous les assignés (visible par responsables)
            'all_results' => $this->when(
                $user && ($this->activite->responsable_id === $user->id ||
                    ($this->activite->projet && $this->activite->projet->responsable_id === $user->id)),
                function () {
                    return $this->getResultatsIndividuels()->map(function ($resultat) {
                        return [
                            'id' => $resultat->id,
                            'user' => [
                                'id' => $resultat->user->id,
                                'nom' => $resultat->user->nom,
                                'avatar' => $resultat->user->avatar,
                            ],
                            'is_individual' => $resultat->is_individual,
                            'resultats_obtenus' => $resultat->resultats_obtenus,
                            'taux_realisation' => $resultat->taux_realisation,
                            'soumis_le' => $resultat->soumis_le?->format('Y-m-d H:i:s'),
                            'valide_par_n1' => $resultat->valide_par_n1,
                            'valide_par_n2' => $resultat->valide_par_n2,
                            'is_fully_validated' => $resultat->is_fully_validated,
                            'validation_status' => $resultat->validation_status,
                            'documents_count' => $resultat->documents()->count(),
                        ];
                    });
                }
            ),

            // ✅ AJOUTER : Statistiques des résultats
            'resultats_stats' => $this->when($this->assignees->count() > 1, function () {
                return $this->getStatsResultats();
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
                    'can_move_my_card' => $this->isAssignedTo($user),
                    'can_submit_result' => $this->isAssignedTo($user) &&
                        $this->getStatutForUser($user) === 'termine',
                ];
            }),
        ];
    }
}