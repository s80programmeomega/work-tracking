<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActiviteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'projet_id' => $this->projet_id,
            'projet' => new ProjetResource($this->whenLoaded('projet')),
            'nom' => $this->nom,
            'description' => $this->description,
            'code' => $this->code,
            'responsable_id' => $this->responsable_id,
            'responsable' => new UserResource($this->whenLoaded('responsable')),
            'date_debut' => $this->date_debut?->format('Y-m-d'),
            'date_fin' => $this->date_fin?->format('Y-m-d'),
            'ordre' => $this->ordre,
            'status' => $this->status,
            'progression' => $this->progression,
            'couleur' => $this->couleur,
            'metadata' => $this->metadata,
            'archived_at' => $this->archived_at?->toDateTimeString(),
            'is_overdue' => $this->is_overdue,
            'days_remaining' => $this->days_remaining,
            'tache_count' => $this->tache_count,
            
            // ✅ NOUVEAU : Inclure les membres avec leurs permissions
            'membres' => $this->whenLoaded('membres', function () {
                return $this->membres->map(function ($membre) {
                    return [
                        'id' => $membre->id,
                        'nom' => $membre->nom,
                        'email' => $membre->email,
                        'role' => $membre->pivot->role,
                        'permissions' => [
                            'can_create_tasks' => (bool) $membre->pivot->can_create_tasks,
                            'can_edit_tasks' => (bool) $membre->pivot->can_edit_tasks,
                            'can_delete_tasks' => (bool) $membre->pivot->can_delete_tasks,
                            'can_validate_results' => (bool) $membre->pivot->can_validate_results,
                            'can_assign_users' => (bool) $membre->pivot->can_assign_users,
                        ],
                        'joined_at' => $membre->pivot->created_at?->toDateTimeString(),
                    ];
                });
            }),
            
            // ✅ NOUVEAU : Statistiques des membres
            'membres_count' => $this->whenLoaded('membres', function () {
                return $this->membres->count();
            }),
            
            // ✅ NOUVEAU : Permissions de l'utilisateur courant sur cette activité
            'user_permissions' => $this->when($request->user(), function () use ($request) {
                $user = $request->user();
                
                // Super admin a tous les droits
                if ($user->isSuperAdmin()) {
                    return [
                        'can_edit' => true,
                        'can_delete' => true,
                        'can_manage_members' => true,
                        'can_create_tasks' => true,
                        'can_edit_tasks' => true,
                        'can_delete_tasks' => true,
                        'can_validate_results' => true,
                        'can_assign_users' => true,
                    ];
                }
                
                // Responsable de l'activité
                if ($this->responsable_id === $user->id) {
                    return [
                        'can_edit' => true,
                        'can_delete' => true,
                        'can_manage_members' => true,
                        'can_create_tasks' => true,
                        'can_edit_tasks' => true,
                        'can_delete_tasks' => true,
                        'can_validate_results' => true,
                        'can_assign_users' => true,
                    ];
                }
                
                // Admin du projet
                if ($this->projet && $this->projet->responsable_id === $user->id) {
                    return [
                        'can_edit' => true,
                        'can_delete' => true,
                        'can_manage_members' => true,
                        'can_create_tasks' => true,
                        'can_edit_tasks' => true,
                        'can_delete_tasks' => true,
                        'can_validate_results' => true,
                        'can_assign_users' => true,
                    ];
                }
                
                // Membre de l'activité
                $membre = $this->membres->firstWhere('id', $user->id);
                if ($membre) {
                    return [
                        'can_edit' => (bool) $membre->pivot->can_edit_tasks,
                        'can_delete' => false,
                        'can_manage_members' => (bool) $membre->pivot->can_assign_users,
                        'can_create_tasks' => (bool) $membre->pivot->can_create_tasks,
                        'can_edit_tasks' => (bool) $membre->pivot->can_edit_tasks,
                        'can_delete_tasks' => (bool) $membre->pivot->can_delete_tasks,
                        'can_validate_results' => (bool) $membre->pivot->can_validate_results,
                        'can_assign_users' => (bool) $membre->pivot->can_assign_users,
                    ];
                }
                
                // Aucun accès
                return [
                    'can_edit' => false,
                    'can_delete' => false,
                    'can_manage_members' => false,
                    'can_create_tasks' => false,
                    'can_edit_tasks' => false,
                    'can_delete_tasks' => false,
                    'can_validate_results' => false,
                    'can_assign_users' => false,
                ];
            }),
            
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}