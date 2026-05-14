<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TacheResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        // ✅ Helper pour formater les dates en toute sécurité
        $formatDate = function ($date) {
            if (! $date) {
                return null;
            }
            if (is_string($date)) {
                return $date;
            }
            if (method_exists($date, 'format')) {
                return $date->format('Y-m-d H:i:s');
            }

            return null;
        };

        $formatDateOnly = function ($date) {
            if (! $date) {
                return null;
            }
            if (is_string($date)) {
                return $date;
            }
            if (method_exists($date, 'format')) {
                return $date->format('Y-m-d');
            }

            return null;
        };

        // ✅ Helper pour formater les dates du pivot
        $formatPivotDate = function ($date) {
            if (! $date) {
                return null;
            }
            if (is_string($date)) {
                return $date;
            }
            if (method_exists($date, 'format')) {
                return $date->format('Y-m-d H:i:s');
            }

            return null;
        };

        return [
            'id' => $this->id,
            'code' => $this->code,

            // ✅ Activité avec vérification null
            'activite_id' => $this->activite_id,
            'activite' => $this->when($this->activite, function () {
                return [
                    'id' => $this->activite->id,
                    'nom' => $this->activite->nom,
                    'code' => $this->activite->code,
                    'projet_id' => $this->activite->projet_id,
                    'projet_nom' => $this->activite->projet?->nom,
                    'responsable_id' => $this->activite->responsable_id,
                ];
            }),
            // Responsable de la tâche
            'responsable_id' => $this->responsable_id,
            'responsable' => $this->when($this->responsable, function () {
                return [
                    'id' => $this->responsable->id,
                    'nom' => $this->responsable->nom,
                    'email' => $this->responsable->email,
                    'avatar' => $this->responsable->avatar,
                ];
            }),

            // Dans la section 'permissions', ajouter :
            'is_responsable' => $user ? $this->isResponsable($user) : false,
            // Informations de base
            'titre' => $this->titre,
            'description' => $this->description,
            'objectif' => $this->objectif,
            'indicateurs_resultats' => $this->indicateurs_resultats,
            'commentaire' => $this->commentaire,

            // Fichiers et liens
            'attachments' => TacheAttachmentResource::collection($this->whenLoaded('attachments')),
            'external_links' => TacheExternalLinkResource::collection($this->whenLoaded('externalLinks')),

            // Statut global
            'statut' => $this->statut->value,
            'statut_label' => $this->statut->label(),
            'statut_color' => $this->statut->color(),

            'priorite' => $this->priorite->value,
            'priorite_label' => $this->priorite->label(),
            'priorite_color' => $this->priorite->color(),
            'priorite_icon' => $this->priorite->icon(),

            // ✅ MON statut individuel avec helper de formatage
            'my_status' => $this->when($user, function () use ($user, $formatPivotDate) {
                if (! $this->isAssignedTo($user)) {
                    return null;
                }

                $pivot = $this->assignees()
                    ->where('user_id', $user->id)
                    ->withPivot([
                        'statut_individuel',
                        'progression_individuelle',
                        'started_at',
                        'completed_at',
                        'notes_personnelles',
                        'role',
                        'can_edit',
                        'can_complete',
                        'can_validate',
                    ])
                    ->first();

                if (! $pivot) {
                    return null;
                }

                return [
                    'statut' => $pivot->pivot->statut_individuel ?? $this->statut->value,
                    'progression' => $pivot->pivot->progression_individuelle ?? 0,
                    'started_at' => $formatPivotDate($pivot->pivot->started_at),
                    'completed_at' => $formatPivotDate($pivot->pivot->completed_at),
                    'notes_personnelles' => $pivot->pivot->notes_personnelles,
                    'can_edit' => (bool) ($pivot->pivot->can_edit ?? false),
                    'can_complete' => (bool) ($pivot->pivot->can_complete ?? true),
                    'can_validate' => (bool) ($pivot->pivot->can_validate ?? false),
                    'role' => $pivot->pivot->role ?? 'collaborateur',
                    'can_move' => true,
                    'can_submit_result' => ($pivot->pivot->statut_individuel ?? $this->statut->value) === 'termine',
                ];
            }),

            // Dates
            'date_debut' => $formatDateOnly($this->date_debut),
            'echeance' => $formatDateOnly($this->echeance),
            'date_fin_reelle' => $formatDateOnly($this->date_fin_reelle),

            // Suivi hebdomadaire
            'week_number' => $this->week_number,
            'year' => $this->year,

            // Progression globale
            'taux_realisation' => $this->taux_realisation,
            'estimated_hours' => $this->estimated_hours,
            'actual_hours' => $this->actual_hours,

            // ✅ CORRECTION CRITIQUE: Validation avec vérifications null-safe
            'validation' => [
                'n1_required' => $this->validation_n1_required,
                'n1_validated_at' => $formatDate($this->validated_n1_at),
                'n1_validated_by' => $this->when($this->validatedN1By && $this->validatedN1By->id, function () {
                    return [
                        'id' => $this->validatedN1By->id,
                        'nom' => $this->validatedN1By->nom,
                    ];
                }),
                'n1_commentaire' => $this->commentaire_n1,

                'n2_required' => $this->validation_n2_required,
                'n2_validated_at' => $formatDate($this->validated_n2_at),
                'n2_validated_by' => $this->when($this->validatedN2By && $this->validatedN2By->id, function () {
                    return [
                        'id' => $this->validatedN2By->id,
                        'nom' => $this->validatedN2By->nom,
                    ];
                }),
                'n2_commentaire' => $this->commentaire_n2,

                'status' => $this->validation_status,
                'is_fully_validated' => $this->isFullyValidated(),
            ],

            // ✅ Assignés avec helper de formatage
            'assignees' => $this->whenLoaded('assignees', function () use ($formatPivotDate) {
                return $this->assignees->map(function ($assignedUser) use ($formatPivotDate) {
                    return [
                        'id' => $assignedUser->id,
                        'nom' => $assignedUser->nom,
                        'email' => $assignedUser->email,
                        'avatar' => $assignedUser->avatar,
                        'pivot' => [
                            'role' => $assignedUser->pivot->role ?? 'collaborateur',
                            'can_edit' => (bool) ($assignedUser->pivot->can_edit ?? false),
                            'can_complete' => (bool) ($assignedUser->pivot->can_complete ?? true),
                            'can_validate' => (bool) ($assignedUser->pivot->can_validate ?? false),
                            'statut_individuel' => $assignedUser->pivot->statut_individuel ?? 'a_faire',
                            'progression_individuelle' => $assignedUser->pivot->progression_individuelle ?? 0,
                            'started_at' => $formatPivotDate($assignedUser->pivot->started_at),
                            'completed_at' => $formatPivotDate($assignedUser->pivot->completed_at),
                        ],
                    ];
                });
            }, []),

            // ✅ Statistiques d'équipe avec gestion d'erreur
            'team_stats' => $this->when($this->assignees->count() > 1, function () {
                try {
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
                } catch (\Exception $e) {
                    return [
                        'termine_count' => 0,
                        'en_cours_count' => 0,
                        'a_faire_count' => 0,
                        'total' => 0,
                        'completion_percentage' => 0,
                        'tous_ont_termine' => false,
                    ];
                }
            }),

            // ✅ Mon résultat
            'my_result' => $this->when($user && $this->isAssignedTo($user), function () use ($user, $formatDate) {
                $resultat = $this->monResultat($user);

                return $resultat ? [
                    'id' => $resultat->id,
                    'is_individual' => $resultat->is_individual,
                    'resultats_attendus' => $resultat->resultats_attendus,
                    'resultats_obtenus' => $resultat->resultats_obtenus,
                    'taux_realisation' => $resultat->taux_realisation,
                    'difficultes_rencontrees' => $resultat->difficultes_rencontrees,
                    'solutions_envisagees' => $resultat->solutions_envisagees,
                    'observations' => $resultat->observations,
                    'soumis_le' => $formatDate($resultat->soumis_le),
                    'valide_par_n1' => $resultat->valide_par_n1,
                    'valide_le_n1' => $formatDate($resultat->valide_le_n1),
                    'commentaire_n1' => $resultat->commentaire_n1,
                    'valide_par_n2' => $resultat->valide_par_n2,
                    'valide_le_n2' => $formatDate($resultat->valide_le_n2),
                    'commentaire_n2' => $resultat->commentaire_n2,
                    'is_fully_validated' => $resultat->is_fully_validated ?? false,
                    'validation_status' => $resultat->validation_status ?? 'not_submitted',
                    'documents_count' => $resultat->documents()->count(),
                    'created_at' => $formatDate($resultat->created_at),
                ] : null;
            }),

            // ✅ Tous les résultats avec vérifications complètes
            'all_results' => $this->when(
                $user && $this->activite && (
                    $this->activite->responsable_id === $user->id ||
                    ($this->activite->projet && $this->activite->projet->responsable_id === $user->id) ||
                    $user->isSuperAdmin()
                ),
                function () use ($formatDate) {
                    return $this->whenLoaded('resultatsIndividuels', function () use ($formatDate) {
                        return $this->resultatsIndividuels->map(function ($resultat) use ($formatDate) {
                            return [
                                'id' => $resultat->id,
                                'user' => $resultat->user ? [
                                    'id' => $resultat->user->id,
                                    'nom' => $resultat->user->nom,
                                    'avatar' => $resultat->user->avatar,
                                ] : null,
                                'is_individual' => $resultat->is_individual,
                                'resultats_obtenus' => $resultat->resultats_obtenus,
                                'taux_realisation' => $resultat->taux_realisation,
                                'soumis_le' => $formatDate($resultat->soumis_le),
                                'valide_par_n1' => $resultat->valide_par_n1,
                                'valide_par_n2' => $resultat->valide_par_n2,
                                'is_fully_validated' => $resultat->is_fully_validated ?? false,
                                'validation_status' => $resultat->validation_status ?? 'pending',
                                'documents_count' => $resultat->documents()->count(),
                            ];
                        });
                    }, []);
                }
            ),

            // Labels
            'labels' => LabelResource::collection($this->whenLoaded('labels')),

            // Sous-tâches
            'sous_taches_count' => $this->whenLoaded('sousTaches', fn () => $this->sousTaches->count()),

            // Apparence
            'position' => $this->position,
            'couleur' => $this->couleur,
            'cover_image' => $this->cover_image,
            // 'cover_image' => $this->getFileUrlAttribute,
            'visibility' => $this->visibility,

            // État et indicateurs
            'is_overdue' => $this->is_overdue,
            'can_be_completed' => $this->can_be_completed,
            'can_be_started' => $this->canBeStarted(),

            // Archive
            'archive_status' => $this->archive_status,
            'archived_at' => $formatDate($this->archived_at),

            // Métadonnées
            'created_by' => $this->created_by,
            'created_at' => $formatDate($this->created_at),
            'updated_at' => $formatDate($this->updated_at),

            // ✅ Permissions avec vérifications null-safe
            'permissions' => $this->when($user, function () use ($user) {
                return [
                    'can_view' => $this->isAccessibleBy($user),
                    'can_edit' => $this->canBeEditedBy($user),
                    'can_update' => $this->canBeEditedBy($user),
                    'can_complete' => $this->canBeCompletedBy($user),
                    'can_delete' => $user->can('delete', $this->resource),
                    'can_validate_n1' => $this->activite ? $this->canBeValidatedN1By($user) : false,
                    'can_validate_n2' => $this->activite ? $this->canBeValidatedN2By($user) : false,
                    'can_move_my_card' => $this->isAssignedTo($user),
                    'can_submit_result' => $this->isAssignedTo($user) &&
                        $this->getStatutForUser($user) === 'termine' &&
                        ! $this->monResultat($user),
                    'can_create_subtask' => $user->can('createSubtask', $this->resource),
                ];
            }),
            // ✅ NOUVEAUX CHAMPS pour la fiche d'évaluation
            'evaluation' => $this->when($user, function () use ($user) {
                return [
                    // Doit être affiché sur la fiche
                    'should_show' => $this->shouldShowOnEvaluation($user),

                    // Statut de validation détaillé
                    'validation_status' => $this->getValidationStatusForUser($user),

                    // Labels lisibles
                    'validation_status_label' => $this->getValidationStatusLabel($user),

                    // Validation complète ou non
                    'is_validation_complete' => $this->isValidationCompleteForUser($user),

                    // Peut soumettre un résultat
                    'can_submit_result' => $this->getStatutForUser($user) === 'termine'
                        && ! $this->monResultat($user)?->soumis_le,

                    // Peut éditer le résultat (non validé N1)
                    'can_edit_result' => $this->monResultat($user)
                        && ! $this->monResultat($user)->valide_par_n1,
                ];
            }),
        ];
    }

    protected function getValidationStatusLabel(User $user): string
    {
        $status = $this->getValidationStatusForUser($user);

        return match ($status) {
            'not_submitted' => 'Résultat non soumis',
            'pending_n1' => 'En attente validation N1',
            'pending_n2' => 'En attente validation N2',
            'fully_validated' => 'Validé complètement',
            default => 'Inconnu'
        };
    }

    public function getFileUrlAttribute(): string
    {
        return asset('uploads/'.$this->cover_image);
    }
}
