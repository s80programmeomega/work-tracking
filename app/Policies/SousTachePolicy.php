<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SousTache;
use App\Models\Tache;
use App\Models\User;
use App\Services\PermissionService;

class SousTachePolicy
{
    public function __construct(protected PermissionService $permissionService) {}

    public function view(User $user, SousTache $sousTache): bool
    {
        return $this->permissionService->canViewSousTache($user, $sousTache);
    }

    public function create(User $user, Tache $tache): bool
    {
        return $this->permissionService->canCreateSousTache($user, $tache);
    }

    public function update(User $user, SousTache $sousTache): bool
    {
        return $this->permissionService->canEditSousTache($user, $sousTache);
    }

    public function delete(User $user, SousTache $sousTache): bool
    {
        return $this->permissionService->canDeleteSousTache($user, $sousTache);
    }
}
