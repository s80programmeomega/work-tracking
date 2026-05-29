<?php

namespace App\Http\Resources;

use App\Models\Activite;
use App\Permissions\ContextualPermissionGate;
use App\Permissions\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Role;

/**
 * @property Activite $resource
 *
 * @mixin Activite
 */
class ActiviteResource extends JsonResource
{
    /**
     * @responseField id integer Activite unique identifier.
     * @responseField projet_id integer Parent project ID.
     * @responseField projet ProjetResource|null Nested project (when loaded).
     * @responseField workspace_owner_id integer|null Owner ID of the project workspace (when loaded).
     * @responseField nom string Activite name.
     * @responseField description string|null Optional description.
     * @responseField code string Short unique code.
     * @responseField responsable_id integer Responsible user ID.
     * @responseField responsable UserResource|null Responsible user (when loaded).
     * @responseField date_debut string|null Start date (Y-m-d).
     * @responseField date_fin string|null End date (Y-m-d).
     * @responseField ordre integer Sort order within the project.
     * @responseField status string Status enum: active | completed | archived.
     * @responseField progression integer Completion percentage (0-100).
     * @responseField couleur string|null Hex color code.
     * @responseField metadata object|null Arbitrary key-value metadata.
     * @responseField archived_at string|null ISO datetime of archival.
     * @responseField is_overdue boolean Whether the activite is past its end date.
     * @responseField days_remaining integer|null Calendar days until the end date.
     * @responseField tache_count integer Total number of tasks.
     * @responseField membres object[]|null Members with pivot permissions (when loaded).
     * @responseField membres_count integer|null Total member count (when loaded).
     * @responseField user_permissions object|null Gate-computed permissions for the authenticated user.
     * @responseField created_at string|null ISO datetime of creation.
     * @responseField updated_at string|null ISO datetime of last update.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'projet_id' => $this->projet_id,
            'projet' => new ProjetResource($this->whenLoaded('projet')),
            'workspace_owner_id' => $this->whenLoaded('projet.workspace', function () {
                return $this->projet->workspace->owner_id ?? null;
            }),
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

            // Membres avec permissions
            'membres' => $this->whenLoaded('membres', function () {
                return $this->membres->map(function ($membre) {
                    return [
                        'id' => $membre->id,
                        'nom' => $membre->nom,
                        'email' => $membre->email,
                        'role' => Role::find($membre->pivot->role_id)?->name ?? 'collaborateur',
                        'permissions' => [
                            'can_edit_activity' => (bool) $membre->pivot->can_edit_activity,
                            'can_delete_activity' => (bool) $membre->pivot->can_delete_activity,
                            'can_create_tasks' => (bool) $membre->pivot->can_create_tasks,
                            'can_edit_tasks' => (bool) $membre->pivot->can_edit_tasks,
                            'can_delete_tasks' => (bool) $membre->pivot->can_delete_tasks,
                            'can_validate_results' => (bool) $membre->pivot->can_validate_results,
                            'can_assign_users' => (bool) $membre->pivot->can_assign_users,
                            'can_delete_member' => (bool) $membre->pivot->can_delete_member,
                        ],
                        'joined_at' => $membre->pivot->created_at?->toDateTimeString(),
                    ];
                });
            }),

            'membres_count' => $this->whenLoaded('membres', function () {
                return $this->membres->count();
            }),

            // Permissions de l'utilisateur courant
            'user_permissions' => $this->when($request->user(), function () use ($request) {
                $user = $request->user();
                $gate = app(ContextualPermissionGate::class);
                $activite = $this->resource;

                return [
                    'can_edit_activity' => $gate->userCan($user, Permission::ACTIVITES_EDIT, $activite),
                    'can_delete_activity' => $gate->userCan($user, Permission::ACTIVITES_DELETE, $activite),
                    'can_manage_members' => $gate->userCan($user, Permission::SOUS_TACHES_ASSIGN, $activite),
                    'can_create_tasks' => $gate->userCan($user, Permission::ACTIVITES_CREATE_TASK, $activite),
                    'can_edit_tasks' => $gate->userCan($user, Permission::TACHES_EDIT, $activite),
                    'can_delete_tasks' => $gate->userCan($user, Permission::TACHES_DELETE, $activite),
                    'can_validate_results' => $gate->userCan($user, Permission::ACTIVITES_VALIDATE_N1, $activite),
                    'can_assign_users' => $gate->userCan($user, Permission::SOUS_TACHES_ASSIGN, $activite),
                ];
            }),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

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
            'can_delete_member' => true,
        ];
    }

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
            'can_delete_member' => false,
        ];
    }
}
