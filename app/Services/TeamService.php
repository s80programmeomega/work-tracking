<?php

namespace App\Services;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\TeamActivity;
use App\Models\TeamPresence;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TeamService
{
    /**
     * Get all teams with optional filters
     */
    public function getTeams(array $filters = [])
    {
        $query = Team::with(['owner', 'project', 'members'])
            ->withCount('members');

        if (isset($filters['visibility'])) {
            $query->where('visibility', $filters['visibility']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'LIKE', "%{$filters['search']}%")
                  ->orWhere('description', 'LIKE', "%{$filters['search']}%");
            });
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get team by UUID
     */
    public function getTeamByUuid(string $uuid)
    {
        return Team::with(['owner', 'project', 'members', 'presences.user'])
            ->withCount('members')
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    /**
     * Get teams for a user
     */
    public function getUserTeams(User $user)
    {
        return $user->teams()
            ->withCount('members')
            ->with(['owner', 'project'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create a new team
     */
    public function createTeam(User $owner, array $data): Team
    {
        return DB::transaction(function () use ($owner, $data) {
            $team = Team::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'owner_id' => $owner->id,
                'project_id' => $data['project_id'] ?? null,
                'visibility' => $data['visibility'] ?? 'private',
                'settings' => $data['settings'] ?? [],
                'is_active' => true,
            ]);

            // Add owner as a member with owner role
            $team->members()->attach($owner->id, [
                'role' => 'owner',
                'joined_at' => now(),
                'notifications_enabled' => true,
            ]);

            // Create presence record
            TeamPresence::create([
                'team_id' => $team->id,
                'user_id' => $owner->id,
                'status' => 'online',
                'last_seen_at' => now(),
            ]);

            // Log activity
            TeamActivity::log($team, $owner, 'team_created');

            return $team->load(['owner', 'members']);
        });
    }

    /**
     * Update team
     */
    public function updateTeam(Team $team, array $data): Team
    {
        $team->update([
            'name' => $data['name'] ?? $team->name,
            'description' => $data['description'] ?? $team->description,
            'visibility' => $data['visibility'] ?? $team->visibility,
            'settings' => $data['settings'] ?? $team->settings,
        ]);

        // Log activity
        TeamActivity::log($team, auth()->user(), 'team_updated');

        return $team->fresh(['owner', 'members']);
    }

    /**
     * Archive team
     */
    public function archiveTeam(Team $team): bool
    {
        $team->update([
            'is_active' => false,
            'archived_at' => now(),
        ]);

        // Log activity
        TeamActivity::log($team, auth()->user(), 'team_archived');

        return true;
    }

    /**
     * Restore archived team
     */
    public function restoreTeam(Team $team): bool
    {
        $team->update([
            'is_active' => true,
            'archived_at' => null,
        ]);

        // Log activity
        TeamActivity::log($team, auth()->user(), 'team_restored');

        return true;
    }

    /**
     * Delete team
     */
    public function deleteTeam(Team $team): bool
    {
        // Log activity before deletion
        TeamActivity::log($team, auth()->user(), 'team_deleted');

        return $team->delete();
    }

    /**
     * Add member to team
     */
    public function addMember(Team $team, User $user, string $role = 'member', ?User $addedBy = null): TeamMember
    {
        // Check if already a member
        if ($team->hasMember($user)) {
            throw new \Exception('User is already a member of this team');
        }

        $team->members()->attach($user->id, [
            'role' => $role,
            'joined_at' => now(),
            'notifications_enabled' => true,
        ]);

        // Create presence record
        TeamPresence::firstOrCreate(
            ['team_id' => $team->id, 'user_id' => $user->id],
            ['status' => 'offline', 'last_seen_at' => now()]
        );

        // Log activity
        TeamActivity::log($team, $addedBy ?? auth()->user(), 'member_added', $user, [
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return $team->teamMembers()->where('user_id', $user->id)->first();
    }

    /**
     * Remove member from team
     */
    public function removeMember(Team $team, User $user, ?User $removedBy = null): bool
    {
        if ($team->isOwner($user)) {
            throw new \Exception('Cannot remove team owner');
        }

        $team->members()->detach($user->id);

        // Remove presence record
        TeamPresence::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->delete();

        // Log activity
        TeamActivity::log($team, $removedBy ?? auth()->user(), 'member_removed', $user, [
            'user_id' => $user->id,
        ]);

        return true;
    }

    /**
     * Update member role
     */
    public function updateMemberRole(Team $team, User $user, string $role, ?User $updatedBy = null): bool
    {
        if ($team->isOwner($user)) {
            throw new \Exception('Cannot change owner role');
        }

        $team->members()->updateExistingPivot($user->id, [
            'role' => $role,
        ]);

        // Log activity
        TeamActivity::log($team, $updatedBy ?? auth()->user(), 'member_role_updated', $user, [
            'user_id' => $user->id,
            'new_role' => $role,
        ]);

        return true;
    }

    /**
     * Update member permissions
     */
    public function updateMemberPermissions(Team $team, User $user, array $permissions): bool
    {
        $team->members()->updateExistingPivot($user->id, [
            'permissions' => $permissions,
        ]);

        return true;
    }

    /**
     * Transfer team ownership
     */
    public function transferOwnership(Team $team, User $newOwner, User $currentOwner): bool
    {
        return DB::transaction(function () use ($team, $newOwner, $currentOwner) {
            // Update team owner
            $team->update(['owner_id' => $newOwner->id]);

            // Update old owner to admin
            $team->members()->updateExistingPivot($currentOwner->id, [
                'role' => 'admin',
            ]);

            // Update new owner role
            $team->members()->updateExistingPivot($newOwner->id, [
                'role' => 'owner',
            ]);

            // Log activity
            TeamActivity::log($team, $currentOwner, 'ownership_transferred', $newOwner, [
                'previous_owner_id' => $currentOwner->id,
                'new_owner_id' => $newOwner->id,
            ]);

            return true;
        });
    }

    /**
     * Upload team avatar
     */
    public function uploadAvatar(Team $team, $file): string
    {
        // Delete old avatar if exists
        if ($team->avatar) {
            Storage::disk('public')->delete($team->avatar);
        }

        $path = $file->store('teams/avatars', 'public');
        $team->update(['avatar' => $path]);

        return $path;
    }

    /**
     * Get team statistics
     */
    public function getTeamStats(Team $team): array
    {
        return [
            'total_members' => $team->members()->count(),
            'online_members' => $team->presences()->online()->count(),
            'total_messages' => $team->messages()->count(),
            'total_announcements' => $team->announcements()->published()->count(),
            'total_resources' => $team->resources()->count(),
            'recent_activities' => $team->activities()
                ->with('user')
                ->recent(168) // Last 7 days
                ->limit(20)
                ->get(),
        ];
    }

    /**
     * Get team activity feed
     */
    public function getActivityFeed(Team $team, int $limit = 50)
    {
        return $team->activities()
            ->with(['user', 'subject'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Update member presence
     */
    public function updateMemberPresence(Team $team, User $user, string $status): void
    {
        $presence = TeamPresence::firstOrCreate(
            ['team_id' => $team->id, 'user_id' => $user->id],
            ['status' => 'offline', 'last_seen_at' => now()]
        );

        $presence->updateStatus($status);
    }

    /**
     * Get online members
     */
    public function getOnlineMembers(Team $team)
    {
        return $team->presences()
            ->online()
            ->with('user')
            ->get()
            ->pluck('user');
    }
}
