<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Projet;
use App\Models\User;
use App\Permissions\ContextualPermissionGate;
use App\Permissions\Permission;

class ProjetPolicy
{
    public function __construct(protected ContextualPermissionGate $gate) {}

    public function view(User $user, Projet $projet): bool
    {
        return $this->gate->userCan($user, Permission::PROJETS_VIEW, $projet);
    }

    public function update(User $user, Projet $projet): bool
    {
        return $this->gate->userCan($user, Permission::PROJETS_EDIT, $projet);
    }

    public function delete(User $user, Projet $projet): bool
    {
        return $this->gate->userCan($user, Permission::PROJETS_DELETE, $projet);
    }

    public function manageMembers(User $user, Projet $projet): bool
    {
        return $this->gate->userCan($user, Permission::PROJETS_MANAGE_MEMBERS, $projet);
    }
}
