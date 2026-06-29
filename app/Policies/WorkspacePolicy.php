<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;
use App\Permissions\ContextualPermissionGate;
use App\Permissions\Permission;
use App\Services\PermissionService;

class WorkspacePolicy
{
    public function __construct(protected ContextualPermissionGate $gate) {}

    public function view(User $user, Workspace $workspace): bool
    {
        return $this->gate->userCan($user, Permission::WORKSPACES_VIEW, $workspace);
    }

    public function manage(User $user, Workspace $workspace): bool
    {
        return $this->gate->userCan($user, Permission::WORKSPACES_MANAGE_SETTINGS, $workspace);
    }

    public function createProject(User $user, Workspace $workspace): bool
    {
        return $this->gate->userCan($user, Permission::WORKSPACES_CREATE_PROJECT, $workspace);
    }

    public function inviteMember(User $user, Workspace $workspace): bool
    {
        return $this->gate->userCan($user, Permission::WORKSPACES_INVITE_MEMBER, $workspace);
    }

    public function removeMember(User $user, Workspace $workspace): bool
    {
        return $this->gate->userCan($user, Permission::WORKSPACES_REMOVE_MEMBER, $workspace);
    }

    public function manageSettings(User $user, Workspace $workspace): bool
    {
        return $this->gate->userCan($user, Permission::WORKSPACES_MANAGE_SETTINGS, $workspace);
    }

    public function manageMembers(User $user, Workspace $workspace): bool
    {
        return $this->gate->userCan($user, Permission::WORKSPACES_INVITE_MEMBER, $workspace)
            || $this->gate->userCan($user, Permission::WORKSPACES_REMOVE_MEMBER, $workspace);
    }

    public function banMember(User $user, Workspace $workspace): bool
    {
        return $this->gate->userCan($user, Permission::WORKSPACES_BAN_MEMBER, $workspace);
    }

    public function viewMembers(User $user, Workspace $workspace): bool
    {
        return $this->gate->userCan($user, Permission::WORKSPACES_VIEW_MEMBERS, $workspace);
    }

    /** Accès au canal responsibles : directeur, manager, cadre du workspace. */
    public function accessResponsibles(User $user, Workspace $workspace): bool
    {
        $roleName = app(PermissionService::class)->getWorkspaceRoleName($user, $workspace);

        return in_array($roleName, ['owner', 'manager', 'cadre'], true);
    }
}
