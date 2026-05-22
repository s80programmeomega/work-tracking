<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\SousTache;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use App\Permissions\ContextualPermissionGate;
use App\Permissions\Permission;
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

    // ── Evaluations / Agent sheet (Task 9) ──────────────────────────────────
    //
    // Le permission seul (EVALUATIONS_VIEW_FICHE) dit "ce rôle a le droit de
    // consulter UNE fiche"; il ne dit pas "laquelle". Le scope est appliqué
    // ici, en croisant le rôle de l'acteur et son lien avec la cible:
    //   - lui-même            → toujours autorisé (toute fiche perso)
    //   - super_admin/owner   → workspace entier
    //   - manager             → fiches des membres de son scope d'activité
    //   - cadre               → fiches de ses assignés directs
    //   - autres rôles        → fiche perso uniquement (déjà couvert par le
    //                            premier cas)

    /**
     * Whether $actor can view $target's full evaluation sheet in $workspace.
     */
    public function canViewFicheEvaluation(User $actor, User $target, Workspace $workspace, ContextualPermissionGate $gate): bool
    {
        // Sa propre fiche est toujours visible (sous réserve du droit de
        // base, qui est accordé à toutes les rôles authentifiés).
        if ($actor->id === $target->id) {
            return $gate->userCan($actor, Permission::EVALUATIONS_VIEW_FICHE, $workspace);
        }

        // Acteur sans le droit de base → refus immédiat.
        if (! $gate->userCan($actor, Permission::EVALUATIONS_VIEW_FICHE, $workspace)) {
            return false;
        }

        // Super admin global → toute fiche.
        if ($actor->isSuperAdmin()) {
            return true;
        }

        $actorRole = $this->getWorkspaceRoleName($actor, $workspace);

        // Owner ou directeur → workspace entier.
        if (in_array($actorRole, ['owner', 'directeur'], true)) {
            return $this->isWorkspaceMember($target, $workspace);
        }

        // Manager → fiches des membres de ses activités.
        // Cadre  → fiches des assignés à ses tâches.
        // On garde la règle large (présence dans une activité/tâche commune)
        // pour rester compatible avec la définition de "scope" du plan.
        if (in_array($actorRole, ['manager', 'cadre'], true)) {
            return $this->sharesActiveScopeWith($actor, $target, $workspace);
        }

        // Autres rôles: refus (le cas "soi-même" a déjà été traité).
        return false;
    }

    /** Whether $actor can export $target's evaluation sheet (same scope as view). */
    public function canExportFicheEvaluation(User $actor, User $target, Workspace $workspace, ContextualPermissionGate $gate): bool
    {
        // L'export exige le droit dédié EN PLUS du droit de consultation.
        // Cela exclut automatiquement observateur/stagiaire qui n'ont que
        // VIEW_FICHE.
        if (! $gate->userCan($actor, Permission::EVALUATIONS_EXPORT_FICHE, $workspace)) {
            return false;
        }

        return $this->canViewFicheEvaluation($actor, $target, $workspace, $gate);
    }

    /**
     * Quick scope check: $actor and $target share at least one activité ou
     * tâche where $actor a un rôle d'encadrement (manager / cadre) dans
     * $workspace.
     */
    private function sharesActiveScopeWith(User $actor, User $target, Workspace $workspace): bool
    {
        // Une activité commune où l'acteur est responsable couvre les
        // managers et propriétaires d'activité.
        $sharesActivity = Activite::query()
            ->whereHas('projet', fn ($q) => $q->where('workspace_id', $workspace->id))
            ->where(function ($q) use ($actor) {
                $q->where('responsable_id', $actor->id)
                    ->orWhereHas('members', fn ($u) => $u->where('users.id', $actor->id));
            })
            ->whereHas('taches.assignees', fn ($u) => $u->where('users.id', $target->id))
            ->exists();

        if ($sharesActivity) {
            return true;
        }

        // Une tâche commune où l'acteur est is_responsable couvre les cadres
        // qui supervisent une équipe ad hoc.
        return Tache::query()
            ->whereHas('assignees', fn ($q) => $q->where('users.id', $actor->id)->where('tache_user.is_responsable', true))
            ->whereHas('assignees', fn ($q) => $q->where('users.id', $target->id))
            ->exists();
    }
}
