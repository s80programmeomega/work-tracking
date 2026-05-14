<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Projet;
use App\Models\User;
use App\Services\PermissionService;

class ProjetPolicy
{
    public function __construct(protected PermissionService $permissionService) {}

    public function view(User $user, Projet $projet): bool
    {
        return $this->permissionService->canViewProject($user, $projet);
    }

    public function update(User $user, Projet $projet): bool
    {
        return $this->permissionService->canEditProject($user, $projet);
    }

    public function delete(User $user, Projet $projet): bool
    {
        return $this->permissionService->canDeleteProject($user, $projet);
    }

    public function manageMembers(User $user, Projet $projet): bool
    {
        return $this->permissionService->canManageProjectMembers($user, $projet);
    }
}
