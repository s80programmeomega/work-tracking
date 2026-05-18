<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SousTache;
use App\Models\Tache;
use App\Models\User;
use App\Permissions\ContextualPermissionGate;
use App\Permissions\Permission;

class SousTachePolicy
{
    public function __construct(protected ContextualPermissionGate $gate) {}

    public function view(User $user, SousTache $sousTache): bool
    {
        return $this->gate->userCan($user, Permission::SOUS_TACHES_VIEW, $sousTache);
    }

    public function create(User $user, Tache $tache): bool
    {
        return $this->gate->userCan($user, Permission::TACHES_CREATE_SUBTASK, $tache);
    }

    public function update(User $user, SousTache $sousTache): bool
    {
        return $this->gate->userCan($user, Permission::SOUS_TACHES_EDIT, $sousTache);
    }

    public function delete(User $user, SousTache $sousTache): bool
    {
        return $this->gate->userCan($user, Permission::SOUS_TACHES_DELETE, $sousTache);
    }

    public function assign(User $user, SousTache $sousTache): bool
    {
        return $this->gate->userCan($user, Permission::SOUS_TACHES_ASSIGN, $sousTache);
    }
}
