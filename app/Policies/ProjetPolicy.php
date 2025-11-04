<?php

namespace App\Policies;

use App\Models\Projet;
use App\Models\User;

class ProjetPolicy
{
    /**
     * Determine if the user can view any projects.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the project.
     */
    public function view(User $user, Projet $projet): bool
    {
        // Super admin can view all
        if ($user->role === 'super_admin') {
            return true;
        }

        // Public projects can be viewed by anyone
        if ($projet->visibility === 'public') {
            return true;
        }

        // Private projects can only be viewed by responsable
        if ($projet->visibility === 'private') {
            return $projet->isResponsable($user);
        }

        // Team projects can be viewed by responsable and members
        return $projet->isResponsable($user) || $projet->isMember($user);
    }

    /**
     * Determine if the user can create projects.
     */
    public function create(User $user): bool
    {
        // Allow manager and above to create projects
        return in_array($user->role, [
            'super_admin',
            'manager',
            'admin',
            'responsable_n1',
            'responsable_n2',
        ]);
    }

    /**
     * Determine if the user can update the project.
     */
    public function update(User $user, Projet $projet): bool
    {
        // Super admin can update all
        if ($user->role === 'super_admin') {
            return true;
        }

        // Responsable can always update
        if ($projet->isResponsable($user)) {
            return true;
        }

        // Check member permissions
        return $projet->canUserEdit($user);
    }

    /**
     * Determine if the user can delete the project.
     */
    public function delete(User $user, Projet $projet): bool
    {
        // Super admin can delete all
        if ($user->role === 'super_admin') {
            return true;
        }

        // Responsable can always delete
        if ($projet->isResponsable($user)) {
            return true;
        }

        // Check member permissions
        return $projet->canUserDelete($user);
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
     * Determine if the user can manage members.
     */
    public function manageMembers(User $user, Projet $projet): bool
    {
        // Super admin can manage all
        if ($user->role === 'super_admin') {
            return true;
        }

        // Responsable can always manage members
        if ($projet->isResponsable($user)) {
            return true;
        }

        // Check if member has invite permission
        return $projet->canUserInvite($user);
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
