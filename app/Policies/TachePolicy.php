<?php

namespace App\Policies;

use App\Models\Tache;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TachePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view tasks
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Tache $tache): bool
    {
        return true; // All authenticated users can view tasks
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create tasks
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Tache $tache): bool
    {
        return true; // All authenticated users can update tasks
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Tache $tache): bool
    {
        return true; // All authenticated users can delete tasks
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Tache $tache): bool
    {
        return true; // All authenticated users can restore tasks
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Tache $tache): bool
    {
        return true; // All authenticated users can force delete tasks
    }
}
