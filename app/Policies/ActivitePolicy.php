<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Activite;
use App\Models\User;
use App\Permissions\ContextualPermissionGate;
use App\Permissions\Permission;

class ActivitePolicy
{
    public function __construct(protected ContextualPermissionGate $gate) {}

    public function view(User $user, Activite $activite): bool
    {
        return $this->gate->userCan($user, Permission::ACTIVITES_VIEW, $activite);
    }

    public function update(User $user, Activite $activite): bool
    {
        return $this->gate->userCan($user, Permission::ACTIVITES_EDIT, $activite);
    }

    public function delete(User $user, Activite $activite): bool
    {
        return $this->gate->userCan($user, Permission::ACTIVITES_DELETE, $activite);
    }

    public function createTask(User $user, Activite $activite): bool
    {
        return $this->gate->userCan($user, Permission::ACTIVITES_CREATE_TASK, $activite);
    }

    public function validateN1(User $user, Activite $activite): bool
    {
        return $this->gate->userCan($user, Permission::ACTIVITES_VALIDATE_N1, $activite);
    }
}
