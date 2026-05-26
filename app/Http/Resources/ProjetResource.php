<?php

namespace App\Http\Resources;

use App\Permissions\ContextualPermissionGate;
use App\Permissions\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Role;

class ProjetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // ✅ CALCUL DE LA PROGRESSION AUTOMATIQUE BASÉE SUR LA MOYENNE DES ACTIVITÉS
        $progressionAuto = $this->calculateAutoProgressionFromActivities();

        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'workspace' => $this->whenLoaded('workspace', function () {
                return [
                    'id' => $this->workspace->id,
                    'nom' => $this->workspace->nom,
                    'slug' => $this->workspace->slug,
                    'owner_id' => $this->workspace->owner_id,
                ];
            }),
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

            // ✅ PROGRESSION AUTOMATIQUE BASÉE SUR LA MOYENNE DES ACTIVITÉS
            'progression' => $progressionAuto,
            'progression_calculee' => $progressionAuto,
            'progression_manuelle' => $this->progression, // Ancienne valeur

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

            // ✅ Statistiques des activités et tâches
            'activites_count' => $this->when($this->relationLoaded('activites'), function () {
                return $this->activites->count();
            }, function () {
                return $this->activites()->count();
            }),

            'taches_count' => $this->when($this->relationLoaded('activites'), function () {
                return $this->activites->sum(function ($activite) {
                    return $activite->tache_count ?? $activite->taches()->count();
                });
            }, function () {
                return $this->taches()->count();
            }),

            // ✅ DÉTAILS DU CALCUL DE PROGRESSION (pour le debug)
            'progression_details' => $this->when($this->relationLoaded('activites'), function () {
                $activites = $this->activites;
                $details = [];
                $totalPoids = 0;
                $totalProgressionPonderee = 0;

                foreach ($activites as $activite) {
                    $poids = $this->calculateActivityWeight($activite);
                    $progression = $activite->progression ?? 0;
                    $contribution = $progression * $poids;

                    $details[] = [
                        'activite_id' => $activite->id,
                        'activite_nom' => $activite->nom,
                        'progression' => $progression,
                        'poids' => round($poids, 2),
                        'contribution' => round($contribution, 2),
                        'taches_count' => $activite->tache_count ?? 0,
                        'is_overdue' => $activite->is_overdue,
                    ];

                    $totalPoids += $poids;
                    $totalProgressionPonderee += $contribution;
                }

                return [
                    'activites' => $details,
                    'total_poids' => round($totalPoids, 2),
                    'total_progression_ponderee' => round($totalProgressionPonderee, 2),
                    'moyenne_ponderee' => $totalPoids > 0 ? round($totalProgressionPonderee / $totalPoids, 2) : 0,
                ];
            }),

            // Relationships
            'members' => ProjetMemberResource::collection($this->whenLoaded('members')),
            'tags' => ProjetTagResource::collection($this->whenLoaded('tags')),

            // Gate-computed permissions for this user on this project
            'user_permissions' => $this->when($request->user(), function () use ($request) {
                $user = $request->user();
                $gate = app(ContextualPermissionGate::class);
                $projet = $this->resource;

                return [
                    'can_view' => $gate->userCan($user, Permission::PROJETS_VIEW, $projet),
                    'can_edit' => $gate->userCan($user, Permission::PROJETS_EDIT, $projet),
                    'can_delete' => $gate->userCan($user, Permission::PROJETS_DELETE, $projet),
                    'can_manage_members' => $gate->userCan($user, Permission::PROJETS_MANAGE_MEMBERS, $projet),
                    'can_create_activity' => $gate->userCan($user, Permission::ACTIVITES_CREATE_TASK, $projet),
                    'can_view_documents' => $gate->userCan($user, Permission::DOCUMENTS_VIEW, $projet),
                    'can_upload_documents' => $gate->userCan($user, Permission::DOCUMENTS_UPLOAD, $projet),
                    'can_delete_documents' => $gate->userCan($user, Permission::DOCUMENTS_DELETE, $projet),
                    'can_share_documents' => $gate->userCan($user, Permission::DOCUMENTS_SHARE, $projet),
                ];
            }),

            // ✅ Activités avec permissions utilisateur
            'activites' => $this->whenLoaded('activites', function () use ($request) {
                return $this->activites->map(function ($activite) use ($request) {
                    $user = $request->user();

                    $userPermissions = $this->calculateActivityPermissions($activite, $user);

                    return [
                        'id' => $activite->id,
                        'projet_id' => $activite->projet_id,
                        'nom' => $activite->nom,
                        'description' => $activite->description,
                        'code' => $activite->code,
                        'responsable_id' => $activite->responsable_id,
                        'responsable' => $activite->responsable ? [
                            'id' => $activite->responsable->id,
                            'nom' => $activite->responsable->nom,
                            'prenom' => $activite->responsable->prenom,
                            'email' => $activite->responsable->email,
                            'avatar' => $activite->responsable->avatar,
                        ] : null,
                        'date_debut' => $activite->date_debut?->format('Y-m-d'),
                        'date_fin' => $activite->date_fin?->format('Y-m-d'),
                        'ordre' => $activite->ordre,
                        'status' => $activite->status,
                        'progression' => $activite->progression,
                        'couleur' => $activite->couleur,
                        'is_overdue' => $activite->is_overdue,
                        'days_remaining' => $activite->days_remaining,
                        'tache_count' => $activite->tache_count ?? 0,
                        'membres_count' => $activite->membres_count ?? $activite->membres()->count(),

                        // ✅ POIDS CALCULÉ pour cette activité
                        'poids_calcule' => $this->calculateActivityWeight($activite),

                        'membres' => $activite->membres->map(function ($membre) {
                            return [
                                'id' => $membre->id,
                                'nom' => $membre->nom,
                                'prenom' => $membre->prenom,
                                'email' => $membre->email,
                                'avatar' => $membre->avatar,
                                'role' => Role::find($membre->pivot->role_id)?->name ?? 'collaborateur',
                                'permissions' => [
                                    'can_edit_activity' => (bool) $membre->pivot->can_edit_activity,
                                    'can_delete_activity' => (bool) $membre->pivot->can_delete_activity,
                                    'can_create_tasks' => (bool) $membre->pivot->can_create_tasks,
                                    'can_edit_tasks' => (bool) $membre->pivot->can_edit_tasks,
                                    'can_delete_tasks' => (bool) $membre->pivot->can_delete_tasks,
                                    'can_validate_results' => (bool) $membre->pivot->can_validate_results,
                                    'can_assign_users' => (bool) $membre->pivot->can_assign_users,
                                ],
                                'joined_at' => $membre->pivot->created_at?->toDateTimeString(),
                            ];
                        }),
                        'membres_count' => $activite->membres->count(),

                        'user_permissions' => $userPermissions,

                        'created_at' => $activite->created_at?->toDateTimeString(),
                        'updated_at' => $activite->updated_at?->toDateTimeString(),
                    ];
                });
            }),

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    // Progression Projet = Σ(Progression Activité × Poids Activité) / Σ(Poids)

    /**
     * ✅ CALCULE LA PROGRESSION AUTOMATIQUE À PARTIR DES ACTIVITÉS
     */
    private function calculateAutoProgressionFromActivities(): int
    {
        // Si le projet est terminé ou archivé
        if (in_array($this->status, ['completed', 'archived'])) {
            return $this->progression ?? 100;
        }

        // Si les activités sont chargées
        if ($this->relationLoaded('activites') && $this->activites->isNotEmpty()) {
            $activitesActives = $this->activites->where('status', 'active');

            if ($activitesActives->isEmpty()) {
                return 0;
            }

            $totalProgressionPonderee = 0;
            $totalPoids = 0;

            foreach ($activitesActives as $activite) {
                $poids = $this->calculateActivityWeight($activite);
                $progressionActivite = $activite->progression ?? 0;

                $totalProgressionPonderee += $progressionActivite * $poids;
                $totalPoids += $poids;
            }

            if ($totalPoids > 0) {
                return (int) round($totalProgressionPonderee / $totalPoids);
            }
        }

        // Fallback
        return $this->progression ?? 0;
    }

    /**
     * ✅ CALCULE LE POIDS D'UNE ACTIVITÉ (identique au modèle)
     */
    private function calculateActivityWeight($activite): float
    {
        $poids = 1.0;

        // Nombre de tâches
        $tacheCount = $activite->tache_count ?? 0;
        if ($tacheCount > 20) {
            $poids += 1.0;
        } elseif ($tacheCount > 10) {
            $poids += 0.7;
        } elseif ($tacheCount > 5) {
            $poids += 0.4;
        } elseif ($tacheCount > 0) {
            $poids += 0.2;
        }

        // Durée
        if ($activite->date_debut && $activite->date_fin) {
            $dureeJours = $activite->date_debut->diffInDays($activite->date_fin);
            if ($dureeJours > 90) {
                $poids += 0.8;
            } elseif ($dureeJours > 30) {
                $poids += 0.4;
            }
        }

        // Retard
        if ($activite->is_overdue) {
            $poids += 0.5;
        }

        // Membres
        $membresCount = $activite->membres_count ?? $activite->membres()->count();
        if ($membresCount > 5) {
            $poids += 0.6;
        } elseif ($membresCount > 2) {
            $poids += 0.3;
        }

        return $poids;
    }

    /**
     * Retourne les permissions complètes
     */
    private function getFullPermissions(): array
    {
        return [
            'can_edit_activity' => true,
            'can_delete_activity' => true,
            'can_manage_members' => true,
            'can_create_tasks' => true,
            'can_edit_tasks' => true,
            'can_delete_tasks' => true,
            'can_validate_results' => true,
            'can_assign_users' => true,
        ];
    }

    /**
     * Retourne les permissions par défaut (aucun accès)
     */
    private function getDefaultPermissions(): array
    {
        return [
            'can_edit_activity' => false,
            'can_delete_activity' => false,
            'can_manage_members' => false,
            'can_create_tasks' => false,
            'can_edit_tasks' => false,
            'can_delete_tasks' => false,
            'can_validate_results' => false,
            'can_assign_users' => false,
        ];
    }

    /**
     * ✅ Calcule les permissions de l'utilisateur pour une activité spécifique
     */
    private function calculateActivityPermissions($activite, $user): array
    {
        if (! $user) {
            return $this->getDefaultPermissions();
        }

        // ✅ Super admin a tous les droits
        if ($user->isSuperAdmin()) {
            return $this->getFullPermissions();
        }

        // ✅ Responsable de l'activité a tous les droits
        if ($activite->responsable_id === $user->id) {
            return $this->getFullPermissions();
        }

        // ✅ Responsable du projet a tous les droits
        if ($this->resource->responsable_id === $user->id) {
            return $this->getFullPermissions();
        }

        // ✅ Membre de l'activité : permissions basées sur le pivot
        $membre = $activite->membres->firstWhere('id', $user->id);
        if ($membre) {
            return [
                'can_edit_activity' => (bool) $membre->pivot->can_edit_activity,
                'can_delete_activity' => (bool) $membre->pivot->can_delete_activity, // Les membres simples ne peuvent pas supprimer l'activité
                'can_manage_members' => (bool) $membre->pivot->can_assign_users,
                'can_create_tasks' => (bool) $membre->pivot->can_create_tasks,
                'can_edit_tasks' => (bool) $membre->pivot->can_edit_tasks,
                'can_delete_tasks' => (bool) $membre->pivot->can_delete_tasks,
                'can_validate_results' => (bool) $membre->pivot->can_validate_results,
                'can_assign_users' => (bool) $membre->pivot->can_assign_users,
            ];
        }

        // ✅ Aucun accès par défaut
        return $this->getDefaultPermissions();
    }
}
