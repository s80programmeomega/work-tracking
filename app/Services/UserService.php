<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    /**
     * Get paginated users with filters
     */
    public function getUsers(array $filters = []): LengthAwarePaginator
    {
        $query = User::with(['roles', 'permissions'])
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->search($search);
            })
            ->when($filters['role'] ?? null, function ($q, $role) {
                $q->byRole($role);
            })
            ->when($filters['team_id'] ?? null, function ($q, $teamId) {
                $q->byTeam($teamId);
            })
            ->when(isset($filters['is_active']), function ($q) use ($filters) {
                $q->where('is_active', $filters['is_active']);
            })
            ->when($filters['sort_by'] ?? 'created_at', function ($q, $sortBy) use ($filters) {
                $direction = $filters['sort_direction'] ?? 'desc';
                $q->orderBy($sortBy, $direction);
            });

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Create new user
     */
    public function createUser(array $data): User
    {
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        // Assign role using Spatie
        if (isset($data['role'])) {
            $user->assignRole($data['role']);
        }

        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log('User created');

        return $user->fresh(['roles']);
    }

    /**
     * Update user
     */
    public function updateUser(User $user, array $data): User
    {
        // Remove password if not being updated
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        // Update role if provided
        if (isset($data['role']) && auth()->user()->can('user.assign_role')) {
            $user->syncRoles([$data['role']]);
        }

        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log('User updated');

        return $user->fresh(['roles']);
    }

    /**
     * Delete user
     */
    public function deleteUser(User $user): bool
    {
        // Prevent self-deletion
        if (auth()->id() === $user->id) {
            throw new \Exception('Cannot delete your own account');
        }

        // Delete avatar if exists
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log('User deleted');

        return $user->delete();
    }

    /**
     * Update user profile
     */
    public function updateProfile(User $user, array $data): User
    {
        // Handle avatar upload
        if (isset($data['avatar']) && $data['avatar'] instanceof \Illuminate\Http\UploadedFile) {
            // Delete old avatar
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $data['avatar'] = $data['avatar']->store('avatars', 'public');
        }

        $user->update($data);

        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log('Profile updated');

        return $user->fresh();
    }

    /**
     * Change user password
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw new \Exception('Current password is incorrect');
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log('Password changed');

        return true;
    }

    /**
     * Update notification preferences
     */
    public function updateNotificationPreferences(User $user, array $preferences): User
    {
        $user->update([
            'notification_preferences' => $preferences,
        ]);

        return $user->fresh();
    }

    /**
     * Toggle user active status
     */
    public function toggleActiveStatus(User $user): User
    {
        $user->update([
            'is_active' => !$user->is_active,
        ]);

        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log('User ' . ($user->is_active ? 'activated' : 'deactivated'));

        return $user->fresh();
    }

    /**
     * Get user statistics
     */
    public function getUserStats(User $user): array
    {
        // TODO: Implement when Projet, Activite, Tache models are created
        return [
            'total_projets' => 0,
            'total_activites' => 0,
            'total_taches' => 0,
            'taches_completed' => 0,
            'taches_in_progress' => 0,
            'taches_pending' => 0,
        ];

        // Will be implemented later:
        // return [
        //     'total_projets' => $user->projets()->count(),
        //     'total_activites' => $user->activites()->count(),
        //     'total_taches' => $user->taches()->count(),
        //     'taches_completed' => $user->taches()->where('statut', 'termine')->count(),
        //     'taches_in_progress' => $user->taches()->where('statut', 'en_cours')->count(),
        //     'taches_pending' => $user->taches()->where('statut', 'a_faire')->count(),
        // ];
    }

    /**
     * Get user activity log
     */
    public function getUserActivity(User $user, int $limit = 20): Collection
    {
        return $user->activities()
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Search users
     */
    public function searchUsers(string $query, int $limit = 10): Collection
    {
        return User::search($query)
            ->active()
            ->limit($limit)
            ->get(['id', 'nom', 'email', 'avatar', 'fonction']);
    }
}
