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
     * Determine if user can view the activity.
     */
    public function view(User $user, Activite $activite): bool
    {
        // All authenticated users can view activities
        return true;
    }

    /**
     * Determine if user can create activities.
     */
    public function create(User $user): bool
    {
        // All authenticated users can create activities
        return true;
    }

    /**
     * Determine if user can update the activity.
     */
    public function update(User $user, Activite $activite): bool
    {
        // All authenticated users can update activities
        return true;
    }

    /**
     * Determine if user can delete the activity.
     */
    public function delete(User $user, Activite $activite): bool
    {
        // All authenticated users can delete activities
        return true;
    }
}
