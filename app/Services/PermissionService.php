<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Activite;
use App\Models\Document;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;

class PermissionService
{
    // =========================================================================
    // WORKSPACE LEVEL
    // =========================================================================

    public function canManageWorkspace(User $user, Workspace $workspace): bool
    {
        return $user->isSuperAdmin() || $workspace->owner_id === $user->id;
    }

    public function canViewWorkspace(User $user, Workspace $workspace): bool
    {
        if ($user->isSuperAdmin() || $workspace->owner_id === $user->id) {
            return true;
        }

        return $workspace->members()->where('user_id', $user->id)->exists();
    }

    public function canCreateProject(User $user, Workspace $workspace): bool
    {
        if ($user->isSuperAdmin() || $workspace->owner_id === $user->id) {
            return true;
        }

        return $this->workspaceMemberHasPermission($user, $workspace, 'can_create_projects');
    }

    public function canInviteWorkspaceMember(User $user, Workspace $workspace): bool
    {
        if ($user->isSuperAdmin() || $workspace->owner_id === $user->id) {
            return true;
        }

        return $this->workspaceMemberHasPermission($user, $workspace, 'can_invite_members');
    }

    public function canRemoveWorkspaceMember(User $user, Workspace $workspace): bool
    {
        if ($user->isSuperAdmin() || $workspace->owner_id === $user->id) {
            return true;
        }

        return $this->workspaceMemberHasPermission($user, $workspace, 'can_delete_members');
    }

    public function canManageWorkspaceSettings(User $user, Workspace $workspace): bool
    {
        if ($user->isSuperAdmin() || $workspace->owner_id === $user->id) {
            return true;
        }

        return $this->workspaceMemberHasPermission($user, $workspace, 'can_manage_settings');
    }

    // =========================================================================
    // PROJECT LEVEL
    // =========================================================================

    public function canViewProject(User $user, Projet $projet): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $workspace = $projet->workspace;

        return ($workspace && $workspace->isOwnerOrAdmin($user))
            || $projet->isResponsable($user)
            || $projet->isMember($user)
            || $projet->visibility === 'public';
    }

    public function canEditProject(User $user, Projet $projet): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $workspace = $projet->workspace;

        return ($workspace && $workspace->isOwnerOrAdmin($user))
            || $projet->isResponsable($user)
            || $this->projectMemberHasRole($user, $projet, ['owner', 'manager']);
    }

    public function canDeleteProject(User $user, Projet $projet): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $workspace = $projet->workspace;

        return ($workspace && $workspace->owner_id === $user->id)
            || $projet->isResponsable($user);
    }

    public function canManageProjectMembers(User $user, Projet $projet): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $workspace = $projet->workspace;

        return ($workspace && $workspace->isOwnerOrAdmin($user))
            || $projet->isResponsable($user)
            || $this->projectMemberHasRole($user, $projet, ['owner', 'manager']);
    }

    // =========================================================================
    // ACTIVITY LEVEL
    // =========================================================================

    public function canViewActivity(User $user, Activite $activite): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $workspace = $activite->projet?->workspace;

        return ($workspace && $workspace->isOwnerOrAdmin($user))
            || $activite->isResponsable($user)
            || $activite->isMember($user);
    }

    public function canEditActivity(User $user, Activite $activite): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $workspace = $activite->projet?->workspace;

        if ($workspace && $workspace->isOwnerOrAdmin($user)) {
            return true;
        }

        if ($activite->isResponsable($user)) {
            return true;
        }

        return $this->activityMemberHasPermission($user, $activite, 'can_edit_activity');
    }

    public function canDeleteActivity(User $user, Activite $activite): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $workspace = $activite->projet?->workspace;

        return ($workspace && $workspace->isOwnerOrAdmin($user))
            || $activite->isResponsable($user);
    }

    public function canCreateTask(User $user, Activite $activite): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $projet = $activite->projet;
        if (! $projet) {
            return false;
        }

        $workspace = $projet->workspace;

        if ($workspace && $workspace->isOwnerOrAdmin($user)) {
            return true;
        }

        if ($projet->isResponsable($user) || $activite->isResponsable($user)) {
            return true;
        }

        return $this->activityMemberHasPermission($user, $activite, 'can_create_tasks');
    }

    // =========================================================================
    // TASK LEVEL
    // =========================================================================

    public function canViewTask(User $user, Tache $tache): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $activite = $tache->activite;
        $projet = $activite?->projet;
        $workspace = $projet?->workspace;

        if ($workspace && $workspace->isOwnerOrAdmin($user)) {
            return true;
        }

        if ($projet?->isResponsable($user) || $activite?->isResponsable($user)) {
            return true;
        }

        if ($tache->visibility === 'public' && $projet?->isMember($user)) {
            return true;
        }

        return $tache->isAssignedTo($user);
    }

    public function canEditTask(User $user, Tache $tache): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $activite = $tache->activite;
        $projet = $activite?->projet;
        $workspace = $projet?->workspace;

        if ($workspace && $workspace->isOwnerOrAdmin($user)) {
            return true;
        }

        if ($projet?->isResponsable($user) || $activite?->isResponsable($user)) {
            return true;
        }

        if ($this->activityMemberHasPermission($user, $activite, 'can_edit_tasks')) {
            return true;
        }

        $assignment = $tache->assignees()->where('user_id', $user->id)->first();

        return $assignment && ($assignment->pivot->can_edit ?? false);
    }

    public function canDeleteTask(User $user, Tache $tache): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $activite = $tache->activite;
        $projet = $activite?->projet;
        $workspace = $projet?->workspace;

        if ($workspace && $workspace->owner_id === $user->id) {
            return true;
        }

        if ($projet?->isResponsable($user) || $activite?->isResponsable($user)) {
            return true;
        }

        return $this->activityMemberHasPermission($user, $activite, 'can_delete_tasks');
    }

    /**
     * N1 validation — cadre role in the activity context.
     */
    public function canValidateN1(User $user, Tache $tache): bool
    {
        if (! $tache->validation_n1_required || $tache->validated_n1_at) {
            return false;
        }

        $activite = $tache->activite;
        if (! $activite) {
            return false;
        }

        if ($activite->isResponsable($user)) {
            return true;
        }

        return $this->activityMemberHasRole($user, $activite, 'cadre')
            || $this->activityMemberHasPermission($user, $activite, 'can_validate_results');
    }

    /**
     * N2 validation — manager role in the project/workspace context.
     */
    public function canValidateN2(User $user, Tache $tache): bool
    {
        if (! $tache->validation_n2_required || ! $tache->validated_n1_at || $tache->validated_n2_at) {
            return false;
        }

        $activite = $tache->activite;
        $projet = $activite?->projet;
        if (! $projet) {
            return false;
        }

        if ($projet->isResponsable($user)) {
            return true;
        }

        $workspace = $projet->workspace;
        if ($workspace && $workspace->owner_id === $user->id) {
            return true;
        }

        return $this->projectMemberHasRole($user, $projet, ['manager']);
    }

    /**
     * Can create subtasks — is_responsable flag on tache_user.
     */
    public function canCreateSubtask(User $user, Tache $tache): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $activite = $tache->activite;
        $projet = $activite?->projet;
        $workspace = $projet?->workspace;

        if ($workspace && $workspace->isOwnerOrAdmin($user)) {
            return true;
        }

        if ($projet?->isResponsable($user) || $activite?->isResponsable($user)) {
            return true;
        }

        $assignment = $tache->assignees()->where('user_id', $user->id)->first();

        return $assignment && ($assignment->pivot->is_responsable ?? false);
    }

    // =========================================================================
    // DOCUMENT LEVEL
    // =========================================================================

    public function canViewDocument(User $user, Document $document): bool
    {
        if ($user->isSuperAdmin() || $document->uploaded_by === $user->id) {
            return true;
        }

        if ($document->visibility === 'public') {
            return true;
        }

        return app(DocumentAccessResolver::class)->canView($user, $document->documentable_type, $document->documentable_id);
    }

    public function canEditDocument(User $user, Document $document): bool
    {
        return $user->isSuperAdmin() || $document->uploaded_by === $user->id;
    }

    public function canDeleteDocument(User $user, Document $document): bool
    {
        return $this->canEditDocument($user, $document);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private function workspaceMemberHasPermission(User $user, Workspace $workspace, string $permission): bool
    {
        $member = $workspace->members()->where('user_id', $user->id)->first();
        if (! $member) {
            return false;
        }

        $permissions = $member->pivot->permissions ?? [];
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?? [];
        }

        return in_array('all', $permissions) || ($permissions[$permission] ?? false);
    }

    private function projectMemberHasRole(User $user, Projet $projet, array $roles): bool
    {
        $member = $projet->membres()->where('user_id', $user->id)->first();

        return $member && in_array($member->pivot->role, $roles);
    }

    private function activityMemberHasRole(User $user, Activite $activite, string $role): bool
    {
        $member = $activite->membres()->where('user_id', $user->id)->first();

        return $member && $member->pivot->role === $role;
    }

    private function activityMemberHasPermission(User $user, ?Activite $activite, string $permission): bool
    {
        if (! $activite) {
            return false;
        }

        $member = $activite->membres()->where('user_id', $user->id)->first();

        return $member && ($member->pivot->{$permission} ?? false);
    }
}
