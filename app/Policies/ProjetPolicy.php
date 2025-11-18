<?php

namespace App\Policies;

use App\Models\Projet;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\DB;

class ProjetPolicy
{
    use HandlesAuthorization;

    /**
     * Super Admin bypass
     */
    public function before(User $user, $ability)
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
    }

    /**
     * Determine if the user can view any projects.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }


    /**
     * ✅ VOIR un projet
     */
    public function view(User $user, Projet $projet): bool
    {
       return $projet->isResponsable($user)
            || $projet->isMember($user)
            || ($projet->workspace && $projet->workspace->isOwnerOrAdmin($user))
            || $projet->visibility === 'public';

    }


    /**
     * ✅ CRÉER un projet
     */
    public function create(User $user): bool
    {
        // Vérifié dans le controller avec workspace->canCreateProjects()
        return true;
    }

    /**
     * ✅ MODIFIER un projet
     */
    public function update(User $user, Projet $projet): bool
    {
         return $projet->isResponsable($user)
            || ($projet->workspace && $projet->workspace->isOwnerOrAdmin($user))
            || $projet->canUserEdit($user)
            || $this->hasTemporaryAccess($user, $projet);
    }

    private function hasTemporaryAccess(User $user, Projet $projet): bool
    {
        return DB::table('temporary_access')
            ->where('user_id', $user->id)
            ->where('accessible_type', Projet::class)
            ->where('accessible_id', $projet->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
    /**
     * ✅ SUPPRIMER un projet
     */
    public function delete(User $user, Projet $projet): bool
    {
         return $projet->isResponsable($user)
            || ($projet->workspace && $projet->workspace->isOwnerOrAdmin($user))
            || $projet->canUserDelete($user);
    }

    /**
     * Determine if the user can restore the project.
     */
    public function restore(User $user, Projet $projet): bool
    {
        return $this->delete($user, $projet);
    }

    /**
     * Determine if the user can permanently delete the project.
     */
    public function forceDelete(User $user, Projet $projet): bool
    {
        // Only super admin can force delete
        return $user->isSuperAdmin();

    }

    /**
     * Determine if the user can archive the project.
     */
    public function archive(User $user, Projet $projet): bool
    {
        // Only responsable can archive
        return $projet->isResponsable($user);
    }

    /**
     * ✅ GÉRER les membres du projet
     */
    public function manageMembers(User $user, Projet $projet): bool
    {
         return $projet->isResponsable($user)
            || $projet->isMember($user) && $projet->canUserInvite($user)
            || ($projet->workspace && $projet->workspace->isOwnerOrAdmin($user));
    }

    /**
     * Determine if the user can clone the project.
     */
    public function clone(User $user, Projet $projet): bool
    {
        // User must be able to view the project and create new projects
        return $this->view($user, $projet) && $this->create($user);
    }
}
