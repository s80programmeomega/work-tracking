<?php

namespace App\Policies;

use App\Models\Projet;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjetPolicy
{
    use HandlesAuthorization;

    /**
     * Super Admin bypass
     */
    public function before(User $user, $ability)
    {
        if ($user->hasRole('super_admin')) {
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
        // Responsable du projet
        if ($projet->responsable_id === $user->id) {
            return true;
        }

        // Membre du projet
        if ($projet->members()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Owner ou Admin du workspace
        if ($projet->workspace) {
            $workspace = $projet->workspace;

            // Owner
            if ($workspace->owner_id === $user->id) {
                return true;
            }

            // Admin
            $member = $workspace->members()->where('user_id', $user->id)->first();
            if ($member && in_array($member->pivot->role, ['super_admin', 'admin'])) {
                return true;
            }
        }

        // Projet public
        if ($projet->visibility === 'public') {
            return true;
        }

        return false;
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
        // Responsable
        if ($projet->responsable_id === $user->id) {
            return true;
        }

        // Owner/Admin du workspace
        if ($projet->workspace) {
            $workspace = $projet->workspace;
            
            if ($workspace->owner_id === $user->id) {
                return true;
            }
            
            $member = $workspace->members()->where('user_id', $user->id)->first();
            if ($member && in_array($member->pivot->role, ['super_admin', 'admin'])) {
                return true;
            }
        }

        // Membre avec permission can_edit
        $member = $projet->members()->where('user_id', $user->id)->first();
        if ($member && $member->pivot->can_edit) {
            return true;
        }

        return false;
    }

    /**
     * ✅ SUPPRIMER un projet
     */
    public function delete(User $user, Projet $projet): bool
    {
        // Responsable
        if ($projet->responsable_id === $user->id) {
            return true;
        }

        // Owner du workspace
        if ($projet->workspace && $projet->workspace->owner_id === $user->id) {
            return true;
        }

        // Membre avec permission can_delete
        $member = $projet->members()->where('user_id', $user->id)->first();
        if ($member && $member->pivot->can_delete) {
            return true;
        }

        return false;
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
        return $user->role === 'super_admin';
    }

    /**
     * Determine if the user can archive the project.
     */
    public function archive(User $user, Projet $projet): bool
    {
        // Super admin can archive all
        if ($user->role === 'super_admin') {
            return true;
        }

        // Only responsable can archive
        return $projet->isResponsable($user);
    }

  /**
     * ✅ GÉRER les membres du projet
     */
    public function manageMembers(User $user, Projet $projet): bool
    {
        // Responsable
        if ($projet->responsable_id === $user->id) {
            return true;
        }

        // Owner/Admin du workspace
        if ($projet->workspace) {
            $workspace = $projet->workspace;
            
            if ($workspace->owner_id === $user->id) {
                return true;
            }
            
            $member = $workspace->members()->where('user_id', $user->id)->first();
            if ($member && in_array($member->pivot->role, ['super_admin', 'admin'])) {
                return true;
            }
        }

        // Membre avec permission can_invite
        $member = $projet->members()->where('user_id', $user->id)->first();
        if ($member && $member->pivot->can_invite) {
            return true;
        }

        return false;
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
