<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Activite;
use App\Models\User;
use App\Services\PermissionService;

class ActivitePolicy
{
    public function __construct(protected PermissionService $permissionService) {}

    public function view(User $user, Activite $activite): bool
    {
        return $this->permissionService->canViewActivity($user, $activite);
    }

    public function update(User $user, Activite $activite): bool
    {
        return $this->permissionService->canEditActivity($user, $activite);
    }

    public function delete(User $user, Activite $activite): bool
    {
        return $this->permissionService->canDeleteActivity($user, $activite);
    }

    public function createTask(User $user, Activite $activite): bool
    {
        return $this->permissionService->canCreateTask($user, $activite);
    }
}
