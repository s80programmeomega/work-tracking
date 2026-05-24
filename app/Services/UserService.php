<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Facades\Activity;

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
     * Self-requested account deletion (own account only)
     */
    public function deleteAccount(User $user): bool
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        return $user->delete();
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
    public function updateProfile(User $user, array $data)
    {
        // Handle avatar upload
        if (request()->hasFile('avatar')) {
            $data['avatar'] = $this->handleAvatarUpload($user, request()->file('avatar'));
        }

        // Handle social links
        if (isset($data['social_links'])) {
            $data['social_links'] = json_encode($data['social_links']);
        }

        $user->update($data);

        // Log activity
        Activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties(['attributes' => $data])
            ->log('updated profile');

        return $user->fresh();
    }

    protected function handleAvatarUpload(User $user, $file): string
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $filename = 'avatars/'.$user->id.'/'.time().'.'.$file->getClientOriginalExtension();
        Storage::disk('public')->put($filename, $file->get());

        return $filename;
    }

    /**
     * Change user password
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword)
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw new \Exception('Le mot de passe actuel est incorrect');
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->log('changed password');

        return $user;
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
            'is_active' => ! $user->is_active,
        ]);

        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log('User '.($user->is_active ? 'activated' : 'deactivated'));

        return $user->fresh();
    }

    /**
     * Get user statistics
     */
    public function getUserStats(User $user)
    {
        return [
            'projects_count' => $user->projets()->count(),
            'tasks_count' => $user->taches()->count(),
            'completed_tasks_count' => $user->taches()->where('statut', 'completed')->count(),
            'productivity_rate' => $this->calculateProductivity($user),
            'last_active' => $user->last_login_at,
            'member_since' => $user->created_at?->diffForHumans(),
        ];
    }

    protected function calculateProductivity(User $user)
    {
        $totalTasks = $user->taches()->count();
        $completedTasks = $user->taches()->where('statut', 'completed')->count();

        if ($totalTasks === 0) {
            return 0;
        }

        return round(($completedTasks / $totalTasks) * 100, 1);
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
