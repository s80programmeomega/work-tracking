<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;
use App\Services\PermissionService;

class WorkspacePolicy
{
    public function __construct(protected PermissionService $permissionService) {}

    public function view(User $user, Workspace $workspace): bool
    {
        return $this->permissionService->canViewWorkspace($user, $workspace);
    }

    public function manage(User $user, Workspace $workspace): bool
    {
        return $this->permissionService->canManageWorkspace($user, $workspace);
    }

    public function createProject(User $user, Workspace $workspace): bool
    {
        return $this->permissionService->canCreateProject($user, $workspace);
    }

    public function inviteMember(User $user, Workspace $workspace): bool
    {
        return $this->permissionService->canInviteWorkspaceMember($user, $workspace);
    }

    public function removeMember(User $user, Workspace $workspace): bool
    {
        return $this->permissionService->canRemoveWorkspaceMember($user, $workspace);
    }

    public function manageSettings(User $user, Workspace $workspace): bool
    {
        return $this->permissionService->canManageWorkspaceSettings($user, $workspace);
    }
}
