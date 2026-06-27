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
        if (! $this->canViewWithVisibility($user, $projet)) {
            return false;
        }

        return $this->gate->userCan($user, Permission::PROJETS_VIEW, $projet);
    }

    private function canViewWithVisibility(User $user, Projet $projet): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($projet->workspace && $projet->workspace->owner_id === $user->id) {
            return true;
        }

        if ($projet->responsable_id === $user->id) {
            return true;
        }

        // Manager ou supérieur dans le workspace : accès à toutes les visibilités
        if ($user->hasRoleLevel('manager') && ($projet->workspace?->isMember($user) ?? false)) {
            return true;
        }

        return match ($projet->visibility) {
            'public' => $projet->workspace?->isMember($user) ?? false,
            'team' => $projet->members()->where('user_id', $user->id)->exists()
                || $projet->teams()->whereHas('members', fn ($m) => $m->where('user_id', $user->id))->exists(),
            'private' => false,
            default => false,
        };
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
