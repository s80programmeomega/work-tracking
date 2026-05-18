<?php

namespace App\Traits;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

trait HasWorkspacePermissions
{
    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin === true;
    }

    /**
     * Get user role in workspace
     */
    public function getWorkspaceRole(Workspace $workspace): ?string
    {
        if ($this->isSuperAdmin()) {
            return 'super_admin';
        }

        if ($workspace->owner_id === $this->id) {
            return 'owner';
        }

        $member = $workspace->members()->where('user_id', $this->id)->first();

        return $member ? (Role::find($member->pivot->role_id)?->name) : null;
    }

    /**
     * Get workspace permissions
     */
    public function getWorkspacePermissions(Workspace $workspace): array
    {
        if ($this->isSuperAdmin()) {
            return ['all']; // Super admin can do everything
        }

        $role = $this->getWorkspaceRole($workspace);

        if ($role === 'owner') {
            return $this->getDefaultWorkspacePermissions('owner');
        }

        $member = $workspace->members()->where('user_id', $this->id)->first();
        if (! $member) {
            return [];
        }

        return $this->getDefaultWorkspacePermissions($role);
    }

    /**
     * Default permissions by role
     */
    private function getDefaultWorkspacePermissions(string $role): array
    {
        return match ($role) {
            'owner' => [
                'can_view_all_projects',
                'can_create_projects',
                'can_delete_projects',
                'can_invite_members',
                'can_manage_settings',
                'can_transfer_ownership',
                'can_delete_members',
            ],
            'admin' => [
                'can_view_all_projects',
                'can_create_projects',
                'can_invite_members',
                'can_manage_settings',
            ],
            'member' => [
                'can_create_projects', // Can be overridden in JSON
            ],
            'viewer' => [],
            default => []
        };
    }

    /**
     * Check specific workspace permission
     */
    public function canInWorkspace(Workspace $workspace, string $permission): bool
    {
        $permissions = $this->getWorkspacePermissions($workspace);

        return in_array('all', $permissions) || in_array($permission, $permissions);
    }

    /**
     * Check if user can see all workspace projects
     */
    public function canSeeAllWorkspaceProjects(Workspace $workspace): bool
    {
        return $this->canInWorkspace($workspace, 'can_view_all_projects');
    }

    /**
     * Get accessible projects in workspace
     */
    public function getAccessibleProjects(Workspace $workspace)
    {
        if ($this->canSeeAllWorkspaceProjects($workspace)) {
            return $workspace->projets();
        }

        // Only projects where user is responsable or member
        return $workspace->projets()->where(function ($q) {
            $q->where('responsable_id', $this->id)
                ->orWhereHas('members', fn ($mq) => $mq->where('user_id', $this->id));
        });
    }

    /**
     * Check if user has temporary access
     */
    public function hasTemporaryAccess($accessible): bool
    {
        return DB::table('temporary_access')
            ->where('user_id', $this->id)
            ->where('accessible_type', get_class($accessible))
            ->where('accessible_id', $accessible->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
}
