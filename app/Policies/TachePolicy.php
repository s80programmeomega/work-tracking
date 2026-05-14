<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tache;
use App\Models\User;
use App\Services\PermissionService;

class TachePolicy
{
    public function __construct(protected PermissionService $permissionService) {}

    public function view(User $user, Tache $tache): bool
    {
        return $this->permissionService->canViewTask($user, $tache);
    }

    public function update(User $user, Tache $tache): bool
    {
        return $this->permissionService->canEditTask($user, $tache);
    }

    public function delete(User $user, Tache $tache): bool
    {
        return $this->permissionService->canDeleteTask($user, $tache);
    }

    public function validateN1(User $user, Tache $tache): bool
    {
        return $this->permissionService->canValidateN1($user, $tache);
    }

    public function validateN2(User $user, Tache $tache): bool
    {
        return $this->permissionService->canValidateN2($user, $tache);
    }

    public function createSubtask(User $user, Tache $tache): bool
    {
        return $this->permissionService->canCreateSubtask($user, $tache);
    }
}
