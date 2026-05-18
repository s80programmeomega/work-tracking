<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\SousTache;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Spatie\Permission\Models\Role;

/**
 * Relationship helpers for permission resolution.
 *
 * All authorization decisions are handled by ContextualPermissionGate.
 * This service provides lightweight helpers for querying membership and
 * relationship state — used by ContextualPermissionGate and API resources.
 */
class PermissionService
{
    // ── Workspace ─────────────────────────────────────────────────────────────

    public function isWorkspaceOwner(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_id === $user->id;
    }

    public function isWorkspaceMember(User $user, Workspace $workspace): bool
    {
        return $workspace->members()->where('user_id', $user->id)->exists();
    }

    public function getWorkspaceRoleName(User $user, Workspace $workspace): ?string
    {
        if ($workspace->owner_id === $user->id) {
            return 'owner';
        }

        $member = $workspace->members()->where('user_id', $user->id)->first();

        return $member ? Role::find($member->pivot->role_id)?->name : null;
    }

    // ── Project ────────────────────────────────────────────────────────────────

    public function isProjectResponsable(User $user, Projet $projet): bool
    {
        return $projet->responsable_id === $user->id;
    }

    public function isProjectMember(User $user, Projet $projet): bool
    {
        return $projet->members()->where('user_id', $user->id)->exists()
            || $projet->responsable_id === $user->id;
    }

    public function getProjectRoleName(User $user, Projet $projet): ?string
    {
        $member = $projet->members()->where('user_id', $user->id)->first();

        return $member ? Role::find($member->pivot->role_id)?->name : null;
    }

    // ── Activity ───────────────────────────────────────────────────────────────

    public function isActivityResponsable(User $user, Activite $activite): bool
    {
        return $activite->responsable_id === $user->id;
    }

    public function isActivityMember(User $user, Activite $activite): bool
    {
        return $activite->members()->where('user_id', $user->id)->exists()
            || $activite->responsable_id === $user->id;
    }

    public function getActivityRoleName(User $user, Activite $activite): ?string
    {
        if ($activite->responsable_id === $user->id) {
            return 'cadre';
        }

        $member = $activite->members()->where('user_id', $user->id)->first();

        return $member ? Role::find($member->pivot->role_id)?->name : null;
    }

    public function getActivityPivotFlags(User $user, Activite $activite): array
    {
        $member = $activite->members()->where('user_id', $user->id)->first();
        if (! $member) {
            return [];
        }

        return [
            'can_edit_activity' => (bool) $member->pivot->can_edit_activity,
            'can_delete_activity' => (bool) $member->pivot->can_delete_activity,
            'can_create_tasks' => (bool) $member->pivot->can_create_tasks,
            'can_edit_tasks' => (bool) $member->pivot->can_edit_tasks,
            'can_delete_tasks' => (bool) $member->pivot->can_delete_tasks,
            'can_validate_results' => (bool) $member->pivot->can_validate_results,
            'can_assign_users' => (bool) $member->pivot->can_assign_users,
            'can_delete_member' => (bool) $member->pivot->can_delete_member,
        ];
    }

    // ── Task ───────────────────────────────────────────────────────────────────

    public function isTaskResponsable(User $user, Tache $tache): bool
    {
        return $tache->responsable_id === $user->id;
    }

    public function isTaskAssignee(User $user, Tache $tache): bool
    {
        return $tache->assignees()->where('user_id', $user->id)->exists();
    }

    public function getTaskPivotFlags(User $user, Tache $tache): array
    {
        $assignment = $tache->assignees()->where('user_id', $user->id)->first();
        if (! $assignment) {
            return [];
        }

        return [
            'is_responsable' => (bool) $assignment->pivot->is_responsable,
            'can_edit' => (bool) $assignment->pivot->can_edit,
            'can_complete' => (bool) $assignment->pivot->can_complete,
            'can_validate' => (bool) $assignment->pivot->can_validate,
        ];
    }

    public function getTaskRoleName(User $user, Tache $tache): ?string
    {
        $assignment = $tache->assignees()->where('user_id', $user->id)->first();

        return $assignment ? Role::find($assignment->pivot->role_id)?->name : null;
    }

    // ── Subtask ────────────────────────────────────────────────────────────────

    public function isSousTacheAssignee(User $user, SousTache $sousTache): bool
    {
        return $sousTache->responsable_id === $user->id
            || $this->isTaskAssignee($user, $sousTache->tache);
    }
}
