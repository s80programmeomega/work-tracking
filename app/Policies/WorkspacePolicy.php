<?php
// app/Policies/WorkspacePolicy.php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\Response;

class WorkspacePolicy
{
    /**
     * Determine if the user can view workspace members
     */
    public function viewMembers(User $user, Workspace $workspace): bool
    {
         // Super admin a toujours tous les droits
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Le propriétaire peut voir les membres
        if ($workspace->owner_id === $user->id) {
            return true;
        }

        // Vérifier si l'utilisateur est membre du workspace
        $member = $workspace->members()->where('user_id', $user->id)->first();
        
        if (!$member) {
            return false;
        }

        // Tous les membres peuvent voir les autres membres (ajustez selon vos besoins)
        return in_array($member->pivot->role, ['owner', 'admin', 'member', 'viewer']);
    }

    /**
     * Determine if the user can manage workspace members (invite, remove, update)
     */
    public function manageMembers(User $user, Workspace $workspace): bool
    {
        // Super admin a toujours tous les droits
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Le propriétaire peut tout gérer
        if ($workspace->owner_id === $user->id) {
            return true;
        }

        // Vérifier si l'utilisateur est membre du workspace
        $member = $workspace->members()->where('user_id', $user->id)->first();
        
        if (!$member) {
            return false;
        }

        // Seuls les owners, super_admins et admins peuvent gérer les membres
        return in_array($member->pivot->role, ['owner', 'super_admin', 'admin']);
    }

    /**
     * Determine if the user can view the workspace
     */
    public function view(User $user, Workspace $workspace): bool
    {
        // Super admin a toujours tous les droits
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $workspace->owner_id === $user->id || 
               $workspace->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine if the user can update the workspace
     */
    public function update(User $user, Workspace $workspace): bool
    {
        return $user->isSuperAdmin() || $workspace->owner_id === $user->id;
    }

    /**
     * Determine if the user can delete the workspace
     */
    public function delete(User $user, Workspace $workspace): bool
    {
        return $user->isSuperAdmin() || $workspace->owner_id === $user->id;
    }
}