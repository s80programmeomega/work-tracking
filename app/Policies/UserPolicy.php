<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine if user can view any users
     */
    public function viewAny(User $user): bool
    {
        // Super admin and managers can view users
        return in_array($user->role, ['super_admin', 'manager', 'responsable_n1']);
    }

    /**
     * Determine if user can view specific user
     */
    public function view(User $user, User $targetUser): bool
    {
        // Users can always view their own profile
        if ($user->id === $targetUser->id) {
            return true;
        }

        // Super admin and managers can view other users
        return in_array($user->role, ['super_admin', 'manager', 'responsable_n1']);
    }

    /**
     * Determine if user can create users
     */
    public function create(User $user): bool
    {
        // Only super admin and managers can create users
        return in_array($user->role, ['super_admin', 'manager']);
    }

    /**
     * Determine if user can update specific user
     */
    public function update(User $user, User $targetUser): bool
    {
        // Users can update their own profile
        if ($user->id === $targetUser->id) {
            return true;
        }

        // Check role hierarchy
        return in_array($user->role, ['super_admin', 'manager']) && $user->canManageUser($targetUser);
    }

    /**
     * Determine if user can delete specific user
     */
    public function delete(User $user, User $targetUser): bool
    {
        // Cannot delete yourself
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Only super admin and managers can delete users
        return in_array($user->role, ['super_admin', 'manager']) && $user->canManageUser($targetUser);
    }
}
