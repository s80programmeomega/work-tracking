<?php

namespace App\Http\Resources;

use App\Models\Tache;
use App\Models\User;
use App\Permissions\ContextualPermissionGate;
use App\Permissions\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Role;

/**
 * @property Tache $resource
 *
 * @mixin Tache
 */
class TacheResource extends JsonResource
{
    /**
     * @responseField id integer Task unique identifier.
     * @responseField code string Unique task code.
     * @responseField activite_id integer Parent activite ID.
     * @responseField activite object|null Parent activite summary.
     * @responseField responsable_id integer|null Responsible user ID.
     * @responseField responsable object|null Responsible user summary (when present).
     * @responseField is_responsable boolean Whether the authenticated user is the responsable.
     * @responseField titre string Task title.
     * @responseField description string|null Optional description.
     * @responseField objectif string|null Objective statement.
     * @responseField indicateurs_resultats string|null Result indicators definition.
     * @responseField commentaire string|null General comment.
     * @responseField attachments TacheAttachmentResource[] File attachments (when loaded).
     * @responseField external_links TacheExternalLinkResource[] External links (when loaded).
     * @responseField statut string Status enum value: a_faire | en_cours | termine | en_retard | a_refaire | en_attente | annule.
     * @responseField statut_label string Human-readable status label.
     * @responseField statut_color string Color identifier for status display.
     * @responseField priorite string Priority enum value: faible | moyenne | elevee | critique.
     * @responseField priorite_label string Human-readable priority label.
     * @responseField priorite_color string Color identifier for priority display.
     * @responseField priorite_icon string Icon identifier for priority display.
     * @responseField my_status object|null Authenticated user's individual pivot status (when assigned).
     * @responseField date_debut string|null Start date (Y-m-d).
     * @responseField echeance string|null Due date (Y-m-d).
     * @responseField date_fin_reelle string|null Actual completion date (Y-m-d).
     * @responseField week_number integer|null ISO week number.
     * @responseField year integer|null Year of the week number.
     * @responseField taux_realisation integer Overall completion percentage (0-100).
     * @responseField estimated_hours number|null Estimated work hours.
     * @responseField actual_hours number|null Actual hours logged.
     * @responseField validation object Validation circuit state (n1_required, n1_validated_at, n2_required, ...).
     * @responseField assignees object[] Assigned users with pivot data (when loaded).
     * @responseField team_stats object|null Team completion statistics (when more than one assignee).
     * @responseField my_result object|null Authenticated user's result submission (when assigned and result exists).
     * @responseField all_results object[]|null All individual results (visible to managers only).
     * @responseField labels LabelResource[] Applied labels (when loaded).
     * @responseField sous_taches_count integer Number of sub-tasks.
     * @responseField position integer|null Kanban column sort position.
     * @responseField couleur string|null Hex color code.
     * @responseField cover_image string|null Cover image filename.
     * @responseField is_overdue boolean Whether the task is past its due date.
     * @responseField can_be_completed boolean Whether all conditions to complete the task are met.
     * @responseField can_be_started boolean Whether the task can be started.
     * @responseField archive_status string|null Archive state identifier.
     * @responseField archived_at string|null ISO datetime of archival.
     * @responseField created_by integer|null ID of the user who created the task.
     * @responseField created_at string|null ISO datetime of creation.
     * @responseField updated_at string|null ISO datetime of last update.
     * @responseField permissions object|null Gate-computed permissions for the authenticated user.
     * @responseField evaluation object|null Evaluation display hints for the authenticated user.
     */
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
                        'role_id',
                        'is_responsable',
                        'statut_individuel',
                        'progression_individuelle',
                        'started_at',
                        'completed_at',
                        'notes_personnelles',
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
                    'role' => Role::find($pivot->pivot->role_id)?->name ?? 'collaborateur',
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

            // Validation — dérivée des résultats soumis (les colonnes validated_n1_at
            // au niveau tâche ne sont jamais écrites ; la source de vérité est TacheResultat).
            'validation' => (function () use ($formatDate) {
                $n1Resultat = $this->resultats->where('valide_par_n1', true)->first();
                $n2Resultat = $this->resultats->where('valide_par_n2', true)->first();

                $n1ValidateurN1Id = $n1Resultat?->validateur_n1_id;
                $n1ValidateurN1 = $n1ValidateurN1Id ? $this->resultats
                    ->where('valide_par_n1', true)
                    ->first()
                    ?->validateurN1 : null;

                $n2ValidateurN2 = $n2Resultat?->validateurN2 ?? null;

                return [
                    'n1_required' => $this->validation_n1_required,
                    'n1_validated_at' => $formatDate($n1Resultat?->valide_le_n1),
                    'n1_validated_by' => $n1ValidateurN1 ? ['id' => $n1ValidateurN1->id, 'nom' => $n1ValidateurN1->nom] : null,
                    'n1_commentaire' => $n1Resultat?->commentaire_n1,

                    'n2_required' => $this->validation_n2_required,
                    'n2_validated_at' => $formatDate($n2Resultat?->valide_le_n2),
                    'n2_validated_by' => $n2ValidateurN2 ? ['id' => $n2ValidateurN2->id, 'nom' => $n2ValidateurN2->nom] : null,
                    'n2_commentaire' => $n2Resultat?->commentaire_n2,

                    'status' => $n2Resultat ? 'fully_validated' : ($n1Resultat ? 'pending_n2' : ($this->resultats->where('soumis_le', '!=', null)->isNotEmpty() ? 'pending_n1' : 'not_validated')),
                    'is_fully_validated' => (bool) $n2Resultat,
                ];
            })(),

            // ✅ Assignés avec helper de formatage
            'assignees' => $this->whenLoaded('assignees', function () use ($formatPivotDate) {
                return $this->assignees->map(function ($assignedUser) use ($formatPivotDate) {
                    return [
                        'id' => $assignedUser->id,
                        'nom' => $assignedUser->nom,
                        'email' => $assignedUser->email,
                        'avatar' => $assignedUser->avatar,
                        'pivot' => [
                            'role' => Role::find($assignedUser->pivot->role_id)?->name ?? 'collaborateur',
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
                    // Statut courant du résultat — nécessaire au front pour
                    // gater l'affichage du bandeau anti-sabotage (Task 6).
                    'statut' => $resultat->statut,
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

                    // Validation N0 — utilisée par l'UI pour afficher le
                    // commentaire de renvoi et permettre la saisie du motif
                    // de bypass quand le résultat a été renvoyé.
                    'validation_n0' => [
                        'soumis_n0_le' => $formatDate($resultat->soumis_n0_le),
                        'action' => $resultat->action_n0,
                        'commentaire' => $resultat->commentaire_n0,
                        'action_le' => $formatDate($resultat->action_n0_le),
                    ],

                    // Bypass anti-sabotage (Task 6) — état exposé pour gater
                    // l'affichage du bandeau "Bypass activé" côté front.
                    'bypass' => [
                        'active' => (bool) $resultat->bypass_active,
                        'motif' => $resultat->motif_bypass,
                        'bypass_le' => $formatDate($resultat->bypass_le),
                        'bypass_count' => (int) $resultat->bypass_count,
                    ],
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
            // Prefer withCount('sousTaches') (cheap COUNT query). Falls back to the loaded
            // collection when with('sousTaches') was used instead. Returns 0 if neither.
            'sous_taches_count' => $this->resource->sous_taches_count
                ?? ($this->relationLoaded('sousTaches') ? $this->sousTaches->count() : 0),

            // Apparence
            'position' => $this->position,
            'couleur' => $this->couleur,
            'cover_image' => $this->cover_image,

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

            // Permissions computed by ContextualPermissionGate (DB-driven, admin-editable)
            'permissions' => $this->when($user, function () use ($user) {
                $gate = app(ContextualPermissionGate::class);
                $tache = $this->resource;

                return [
                    'can_view' => $gate->userCan($user, Permission::TACHES_VIEW, $tache),
                    'can_edit' => $gate->userCan($user, Permission::TACHES_EDIT, $tache),
                    'can_inline_edit' => $gate->userCan($user, Permission::TACHES_INLINE_EDIT, $tache),
                    'can_complete' => $gate->userCan($user, Permission::TACHES_EDIT, $tache),
                    'can_validate_n1' => $gate->userCan($user, Permission::TACHES_VALIDATE_N1, $tache),
                    'can_validate_n2' => $gate->userCan($user, Permission::TACHES_VALIDATE_N2, $tache),
                    'can_move_my_card' => $this->isAssignedTo($user),
                    'can_submit_result' => $gate->userCan($user, Permission::TACHES_SUBMIT_RESULT, $tache),
                    'can_approve_n0' => $gate->userCan($user, Permission::TACHES_APPROVE_N0, $tache),
                    'can_create_subtask' => $gate->userCan($user, Permission::TACHES_CREATE_SUBTASK, $tache),
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
