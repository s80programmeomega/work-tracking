<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tache;
use App\Models\User;
use App\Permissions\ContextualPermissionGate;
use App\Permissions\Permission;

class TachePolicy
{
    public function __construct(protected ContextualPermissionGate $gate) {}

    public function view(User $user, Tache $tache): bool
    {
        return $this->gate->userCan($user, Permission::TACHES_VIEW, $tache);
    }

    public function update(User $user, Tache $tache): bool
    {
        return $this->gate->userCan($user, Permission::TACHES_EDIT, $tache);
    }

    public function delete(User $user, Tache $tache): bool
    {
        return $this->gate->userCan($user, Permission::TACHES_DELETE, $tache);
    }

    public function validateN1(User $user, Tache $tache): bool
    {
        return $this->gate->userCan($user, Permission::TACHES_VALIDATE_N1, $tache);
    }

    public function validateN2(User $user, Tache $tache): bool
    {
        return $this->gate->userCan($user, Permission::TACHES_VALIDATE_N2, $tache);
    }

    public function createSubtask(User $user, Tache $tache): bool
    {
        return $this->gate->userCan($user, Permission::TACHES_CREATE_SUBTASK, $tache);
    }

    public function submitResult(User $user, Tache $tache): bool
    {
        return $this->gate->userCan($user, Permission::TACHES_SUBMIT_RESULT, $tache);
    }

    public function approveN0(User $user, Tache $tache): bool
    {
        return $this->gate->userCan($user, Permission::TACHES_APPROVE_N0, $tache);
    }
}
