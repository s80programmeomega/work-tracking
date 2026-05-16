<?php

declare(strict_types=1);

namespace App\Permissions;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\SousTache;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

/**
 * Single resolver for all contextual permission checks.
 *
 * Walks the resource hierarchy (Workspace → Projet → Activite → Tache),
 * collects all role_ids the user holds at each level, loads their Spatie
 * permissions from role_has_permissions (DB-driven, admin-editable at runtime),
 * merges pivot boolean overrides, and returns a deduplicated permission set.
 *
 * super_admin is handled upstream by Gate::before() and never reaches this class.
 */
class ContextualPermissionGate
{
    /** @var array<int, list<string>> Request-scoped cache: role_id → permission names */
    private array $rolePermissionCache = [];

    /**
     * Check whether a user has a specific permission on the given resource.
     * Works for Workspace, Projet, Activite, Tache, and SousTache.
     */
    public function userCan(User $user, string $permission, Model $resource): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $permissions = $this->resolveEffectivePermissions($user, $resource);

        return in_array($permission, $permissions, true);
    }

    /**
     * Resolve the full deduplicated permission set for a user on a resource.
     * Useful for computing the `permissions` key in API resources.
     *
     * @return list<string>
     */
    public function resolveEffectivePermissions(User $user, Model $resource): array
    {
        $roleIds = $this->collectRoleIds($user, $resource);
        $perms = $this->loadPermissionsForRoles($roleIds);
        $perms = $this->applyPivotOverrides($user, $resource, $perms);

        return array_values(array_unique($perms));
    }

    // =========================================================================
    // ROLE ID COLLECTION — walks hierarchy upward from resource
    // =========================================================================

    /**
     * Collect all Spatie role IDs the user holds for the given resource,
     * walking up the hierarchy. Each level that has a pivot row for this user
     * contributes its role_id. The workspace owner_id check auto-adds 'owner'.
     * tache_user.is_responsable adds the virtual 'task_responsable' role.
     *
     * @return list<int>
     */
    private function collectRoleIds(User $user, Model $resource): array
    {
        $roleIds = [];

        match (true) {
            $resource instanceof SousTache => $this->collectForSousTache($user, $resource, $roleIds),
            $resource instanceof Tache => $this->collectForTache($user, $resource, $roleIds),
            $resource instanceof Activite => $this->collectForActivite($user, $resource, $roleIds),
            $resource instanceof Projet => $this->collectForProjet($user, $resource, $roleIds),
            $resource instanceof Workspace => $this->collectForWorkspace($user, $resource, $roleIds),
            default => null,
        };

        return array_values(array_unique($roleIds));
    }

    private function collectForWorkspace(User $user, Workspace $workspace, array &$roleIds): void
    {
        if ($workspace->owner_id === $user->id) {
            $roleIds[] = $this->roleIdFor('owner');

            return;
        }

        $member = $workspace->members()->where('user_id', $user->id)->first();
        if ($member) {
            $roleIds[] = (int) $member->pivot->role_id;
        }
    }

    private function collectForProjet(User $user, Projet $projet, array &$roleIds): void
    {
        // Workspace level
        $workspace = $projet->workspace;
        if ($workspace) {
            $this->collectForWorkspace($user, $workspace, $roleIds);
        }

        // Project level
        $pm = $projet->members()->where('user_id', $user->id)->first();
        if ($pm) {
            $roleIds[] = (int) $pm->pivot->role_id;
        }

        // project responsable inherits owner-equivalent at project scope
        if ($projet->responsable_id === $user->id) {
            $roleIds[] = $this->roleIdFor('owner');
        }
    }

    private function collectForActivite(User $user, Activite $activite, array &$roleIds): void
    {
        // Project and workspace levels
        $projet = $activite->projet;
        if ($projet) {
            $this->collectForProjet($user, $projet, $roleIds);
        }

        // Activity level
        $am = $activite->members()->where('user_id', $user->id)->first();
        if ($am) {
            $roleIds[] = (int) $am->pivot->role_id;
        }

        // Activity responsable inherits owner-equivalent at activity scope
        if ($activite->responsable_id === $user->id) {
            $roleIds[] = $this->roleIdFor('owner');
        }
    }

    private function collectForTache(User $user, Tache $tache, array &$roleIds): void
    {
        // Activity and above
        $activite = $tache->activite;
        if ($activite) {
            $this->collectForActivite($user, $activite, $roleIds);
        }

        // Task level assignment
        $tm = $tache->assignees()->where('user_id', $user->id)->first();
        if ($tm) {
            $roleIds[] = (int) $tm->pivot->role_id;

            // is_responsable flag → virtual task_responsable role
            if ($tm->pivot->is_responsable) {
                $roleIds[] = $this->roleIdFor('task_responsable');
            }
        }

        // Task responsable_id inherits cadre-equivalent (can create subtasks, approve N0)
        if ($tache->responsable_id === $user->id) {
            $roleIds[] = $this->roleIdFor('task_responsable');
        }
    }

    private function collectForSousTache(User $user, SousTache $sousTache, array &$roleIds): void
    {
        // Delegates entirely to parent task
        $tache = $sousTache->tache;
        if ($tache) {
            $this->collectForTache($user, $tache, $roleIds);
        }
    }

    // =========================================================================
    // PERMISSION LOADING — reads Spatie role_has_permissions (cached per request)
    // =========================================================================

    /**
     * Load permission names for the given role IDs.
     * Results are cached per role_id for the lifetime of this request.
     *
     * @param  list<int>  $roleIds
     * @return list<string>
     */
    private function loadPermissionsForRoles(array $roleIds): array
    {
        $permissions = [];

        foreach ($roleIds as $roleId) {
            if (! isset($this->rolePermissionCache[$roleId])) {
                $role = Role::find($roleId);
                $this->rolePermissionCache[$roleId] = $role
                    ? $role->permissions->pluck('name')->all()
                    : [];
            }
            foreach ($this->rolePermissionCache[$roleId] as $perm) {
                $permissions[$perm] = true;
            }
        }

        return array_keys($permissions);
    }

    // =========================================================================
    // PIVOT BOOLEAN OVERRIDES — individual exceptions beyond role defaults
    // =========================================================================

    /**
     * Merge per-row boolean permission overrides from activite_user and tache_user
     * into the existing permission set. These grant extra permissions to specific
     * members beyond what their role normally allows.
     *
     * @param  list<string>  $permissions
     * @return list<string>
     */
    private function applyPivotOverrides(User $user, Model $resource, array $permissions): array
    {
        $map = Permission::pivotOverrideMap();
        $set = array_flip($permissions);

        if ($resource instanceof Activite || $resource instanceof Tache || $resource instanceof SousTache) {
            $activite = match (true) {
                $resource instanceof Activite => $resource,
                $resource instanceof Tache => $resource->activite,
                $resource instanceof SousTache => $resource->tache?->activite,
                default => null,
            };

            if ($activite) {
                $am = $activite->members()->where('user_id', $user->id)->first();
                if ($am) {
                    foreach ($map as $column => $permName) {
                        if ($am->pivot->{$column} ?? false) {
                            $set[$permName] = true;
                        }
                    }
                }
            }
        }

        if ($resource instanceof Tache || $resource instanceof SousTache) {
            $tache = $resource instanceof Tache ? $resource : $resource->tache;
            if ($tache) {
                $tm = $tache->assignees()->where('user_id', $user->id)->first();
                if ($tm) {
                    foreach (['can_edit' => Permission::TACHES_EDIT, 'can_validate' => Permission::TACHES_VALIDATE_N1] as $col => $perm) {
                        if ($tm->pivot->{$col} ?? false) {
                            $set[$perm] = true;
                        }
                    }
                }
            }
        }

        return array_keys($set);
    }

    // =========================================================================
    // ROLE ID LOOKUP — cached by name
    // =========================================================================

    /** @var array<string, int> */
    private array $roleIdByName = [];

    private function roleIdFor(string $name): int
    {
        if (! isset($this->roleIdByName[$name])) {
            $role = Role::where('name', $name)->where('guard_name', 'web')->first();
            $this->roleIdByName[$name] = $role ? (int) $role->id : 0;
        }

        return $this->roleIdByName[$name];
    }
}
