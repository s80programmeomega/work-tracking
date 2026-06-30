<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkspaceMessage;
use App\Services\PermissionService;

class WorkspaceMessagePolicy
{
    public function __construct(protected PermissionService $permissionService) {}

    /** Seul l'expéditeur peut modifier son propre message. */
    public function update(User $user, WorkspaceMessage $message): bool
    {
        return $user->id === $message->user_id;
    }

    /** L'expéditeur ou un manager du workspace peut supprimer. */
    public function delete(User $user, WorkspaceMessage $message): bool
    {
        if ($user->id === $message->user_id) {
            return true;
        }

        $message->loadMissing('channel.workspace');
        $workspace = $message->channel?->workspace;
        if (! $workspace) {
            return false;
        }

        $role = $this->permissionService->getWorkspaceRoleName($user, $workspace);

        return in_array($role, ['owner', 'manager'], true);
    }

    /** Seul un manager+ du workspace peut épingler. */
    public function pin(User $user, WorkspaceMessage $message): bool
    {
        $message->loadMissing('channel.workspace');
        $workspace = $message->channel?->workspace;
        if (! $workspace) {
            return false;
        }

        $role = $this->permissionService->getWorkspaceRoleName($user, $workspace);

        return in_array($role, ['owner', 'manager'], true);
    }
}
