<?php

namespace App\Policies;

use App\Models\Activite;
use App\Models\User;

class ActivitePolicy
{
    /**
     * Determine if user can view any activities.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view activities
    }
  /**
     * Voir une activité :
     * - super admin global
     * - owner/admin du workspace parent
     * - membre assigné à l’activité
     */
    public function view(User $user, Activite $activite): bool
    {
         // 1. Super admin global
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Récupération du workspace via le projet
        $workspace = $activite->projet?->workspace;

        if (!$workspace) {
            return false;
        }

        // 2. Owner ou Admin du workspace
        if ($workspace->isOwnerOrAdmin($user)) {
            return true;
        }

        // 3. Membre assigné à l’activité
        return $activite->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine if user can create activities.
     */
    public function create(User $user): bool
    {
        $workspace = $activite->projet?->workspace;

        if (!$workspace) {
            return false;
        }

        return $workspace->isOwnerOrAdmin($user);
    }

   
    /**
     * Modifier une activité :
     * - super admin
     * - owner / admin du workspace
     * - créateur de l’activité
     */
    public function update(User $user, Activite $activite): bool
    {
        // Super admin
        if ($user->isSuperAdmin()) {
            return true;
        }

        $workspace = $activite->projet?->workspace;

        if (!$workspace) {
            return false;
        }

        // Owner/Admin
        if ($workspace->isOwnerOrAdmin($user)) {
            return true;
        }

        // Créateur de l’activité
        return $activite->created_by === $user->id;
    }

    /**
     * Supprimer une activité :
     * - super admin
     * - owner / admin du workspace
     */
    public function delete(User $user, Activite $activite): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $workspace = $activite->projet?->workspace;

        if (!$workspace) {
            return false;
        }

        return $workspace->isOwnerOrAdmin($user);
    }
}
