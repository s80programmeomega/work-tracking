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
            'workspace_id' => $this->workspace_id,
            'workspace' => $this->whenLoaded('workspace', function () {
                return [
                    'id' => $this->workspace->id,
                    'nom' => $this->workspace->nom,
                    'slug' => $this->workspace->slug,
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
            
            // ✅ Activités avec permissions utilisateur
            'activites' => $this->whenLoaded('activites', function () use ($request) {
                return $this->activites->map(function ($activite) use ($request) {
                    $user = $request->user();
                    
                    // Calculer les permissions utilisateur pour cette activité
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
                        
                        // ✅ Membres avec leurs permissions
                        'membres' => $activite->membres->map(function ($membre) {
                            return [
                                'id' => $membre->id,
                                'nom' => $membre->nom,
                                'prenom' => $membre->prenom,
                                'email' => $membre->email,
                                'avatar' => $membre->avatar,
                                'role' => $membre->pivot->role,
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
                        
                        // ✅ Permissions de l'utilisateur courant
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

    /**
     * ✅ Calcule les permissions de l'utilisateur pour une activité spécifique
     */
    private function calculateActivityPermissions($activite, $user): array
    {
        if (!$user) {
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
                'can_delete_activity' => (bool) $membre->pivot->can_delete_activity,// Les membres simples ne peuvent pas supprimer l'activité
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
}