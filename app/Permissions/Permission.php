<?php

declare(strict_types=1);

namespace App\Permissions;

/**
 * Single source of truth for all permission string constants.
 * Used by ContextualPermissionGate, Policies, RolePermissionSeeder, and API Resources.
 * Mirrored in resources/js/permissions/Permission.js for frontend use.
 */
final class Permission
{
    // ── Workspace ─────────────────────────────────────────────────────────
    const WORKSPACES_VIEW = 'workspaces.view';

    const WORKSPACES_CREATE_PROJECT = 'workspaces.create_project';

    const WORKSPACES_INVITE_MEMBER = 'workspaces.invite_member';

    const WORKSPACES_REMOVE_MEMBER = 'workspaces.remove_member';

    const WORKSPACES_MANAGE_SETTINGS = 'workspaces.manage_settings';

    // ── Project ───────────────────────────────────────────────────────────
    const PROJETS_VIEW = 'projets.view';

    const PROJETS_EDIT = 'projets.edit';

    const PROJETS_DELETE = 'projets.delete';

    const PROJETS_MANAGE_MEMBERS = 'projets.manage_members';

    // ── Activity ──────────────────────────────────────────────────────────
    const ACTIVITES_VIEW = 'activites.view';

    const ACTIVITES_EDIT = 'activites.edit';

    const ACTIVITES_DELETE = 'activites.delete';

    const ACTIVITES_CREATE_TASK = 'activites.create_task';

    const ACTIVITES_VALIDATE_N1 = 'activites.validate_n1';

    // ── Task ──────────────────────────────────────────────────────────────
    const TACHES_VIEW = 'taches.view';

    const TACHES_EDIT = 'taches.edit';

    const TACHES_DELETE = 'taches.delete';

    const TACHES_SUBMIT_RESULT = 'taches.submit_result';

    const TACHES_APPROVE_N0 = 'taches.approve_n0';

    const TACHES_CREATE_SUBTASK = 'taches.create_subtask';

    const TACHES_VALIDATE_N1 = 'taches.validate_n1';

    const TACHES_VALIDATE_N2 = 'taches.validate_n2';

    const TACHES_COMMENT = 'taches.comment';

    // ── Subtask ───────────────────────────────────────────────────────────
    const SOUS_TACHES_VIEW = 'sous_taches.view';

    const SOUS_TACHES_EDIT = 'sous_taches.edit';

    const SOUS_TACHES_DELETE = 'sous_taches.delete';

    const SOUS_TACHES_ASSIGN = 'sous_taches.assign';

    // ── Documents ─────────────────────────────────────────────────────────
    const DOCUMENTS_VIEW = 'documents.view';

    const DOCUMENTS_UPLOAD = 'documents.upload';

    const DOCUMENTS_DELETE = 'documents.delete';

    const DOCUMENTS_SHARE = 'documents.share';

    // ── Results / N0 circuit ──────────────────────────────────────────────
    const RESULTATS_APPROUVER_N0 = 'resultats.approuver_n0';

    const RESULTATS_RENVOYER_N0 = 'resultats.renvoyer_n0';

    const RESULTATS_ACTIVER_BYPASS = 'resultats.activer_bypass';

    // ── Reports ───────────────────────────────────────────────────────────
    const REPORTS_VIEW = 'reports.view';

    const REPORTS_CREATE = 'reports.create';

    // ── Evaluations / Scoring (Task 7) ─────────────────────────────────────
    /** See the pending-validations dashboard listing TacheResultats awaiting N1/N2 action. */
    const EVALUATIONS_VIEW_PENDING = 'evaluations.view_pending';

    /** See score totals for users (own score for all roles; others' scores gated by role + scope). */
    const EVALUATIONS_VIEW_SCORE = 'evaluations.view_score';

    // ── Evaluations / Agent sheet (Task 9) ──────────────────────────────────
    /**
     * See the full agent evaluation sheet (8-criterion breakdown + 4 sections).
     * Scope rules — applied per-row by the controller, not by the seed:
     *   - own sheet: every authenticated role
     *   - cadre: their direct assignees
     *   - manager: their activity scope
     *   - owner / directeur / super_admin: workspace-wide
     *   - observateur, stagiaire: own only, read-only
     */
    const EVALUATIONS_VIEW_FICHE = 'evaluations.view_fiche';

    /** Export the agent sheet (PDF/Excel). Same role gates as VIEW_FICHE. */
    const EVALUATIONS_EXPORT_FICHE = 'evaluations.export_fiche';

    // ── Notifications (Task 8) ─────────────────────────────────────────────
    /** Manage workspace-level notification policy (defaults, mandatory channels). */
    const NOTIFICATIONS_MANAGE_PREFERENCES = 'notifications.manage_preferences';

    // ── Users (platform admin) ────────────────────────────────────────────
    const USERS_VIEW = 'users.view';

    const USERS_CREATE = 'users.create';

    const USERS_UPDATE = 'users.update';

    const USERS_DELETE = 'users.delete';

    const USERS_ASSIGN = 'users.assign';

    /**
     * All permission strings — used by the seeder to create Permission records.
     */
    public static function all(): array
    {
        return [
            self::WORKSPACES_VIEW,
            self::WORKSPACES_CREATE_PROJECT,
            self::WORKSPACES_INVITE_MEMBER,
            self::WORKSPACES_REMOVE_MEMBER,
            self::WORKSPACES_MANAGE_SETTINGS,

            self::PROJETS_VIEW,
            self::PROJETS_EDIT,
            self::PROJETS_DELETE,
            self::PROJETS_MANAGE_MEMBERS,

            self::ACTIVITES_VIEW,
            self::ACTIVITES_EDIT,
            self::ACTIVITES_DELETE,
            self::ACTIVITES_CREATE_TASK,
            self::ACTIVITES_VALIDATE_N1,

            self::TACHES_VIEW,
            self::TACHES_EDIT,
            self::TACHES_DELETE,
            self::TACHES_SUBMIT_RESULT,
            self::TACHES_APPROVE_N0,
            self::TACHES_CREATE_SUBTASK,
            self::TACHES_VALIDATE_N1,
            self::TACHES_VALIDATE_N2,
            self::TACHES_COMMENT,

            self::SOUS_TACHES_VIEW,
            self::SOUS_TACHES_EDIT,
            self::SOUS_TACHES_DELETE,
            self::SOUS_TACHES_ASSIGN,

            self::DOCUMENTS_VIEW,
            self::DOCUMENTS_UPLOAD,
            self::DOCUMENTS_DELETE,
            self::DOCUMENTS_SHARE,

            self::RESULTATS_APPROUVER_N0,
            self::RESULTATS_RENVOYER_N0,
            self::RESULTATS_ACTIVER_BYPASS,

            self::REPORTS_VIEW,
            self::REPORTS_CREATE,

            self::EVALUATIONS_VIEW_PENDING,
            self::EVALUATIONS_VIEW_SCORE,
            self::EVALUATIONS_VIEW_FICHE,
            self::EVALUATIONS_EXPORT_FICHE,

            self::NOTIFICATIONS_MANAGE_PREFERENCES,

            self::USERS_VIEW,
            self::USERS_CREATE,
            self::USERS_UPDATE,
            self::USERS_DELETE,
            self::USERS_ASSIGN,
        ];
    }

    /**
     * Default permission sets per contextual role.
     * Used by RolePermissionSeeder — overridable via admin UI at runtime.
     */
    public static function forRole(string $role): array
    {
        return match ($role) {
            // owner gets everything except actions reserved to specific task participants:
            //   - submit_result + activer_bypass are assignee-only (you must be the result author)
            //   - approve_n0 + resultats.approuver_n0 + resultats.renvoyer_n0 are task_responsable-only
            //     (you must have tache_user.is_responsable = true on the task)
            // These are granted contextually through the task_responsable virtual role.
            'owner' => array_diff(self::all(), [
                self::TACHES_SUBMIT_RESULT,
                self::TACHES_APPROVE_N0,
                self::RESULTATS_ACTIVER_BYPASS,
                self::RESULTATS_APPROUVER_N0,
                self::RESULTATS_RENVOYER_N0,
            ]),

            'manager' => [
                self::WORKSPACES_VIEW,
                self::WORKSPACES_CREATE_PROJECT,
                self::WORKSPACES_INVITE_MEMBER,
                self::WORKSPACES_REMOVE_MEMBER,
                self::WORKSPACES_MANAGE_SETTINGS,
                self::PROJETS_VIEW,
                self::PROJETS_EDIT,
                self::PROJETS_DELETE,
                self::PROJETS_MANAGE_MEMBERS,
                self::ACTIVITES_VIEW,
                self::ACTIVITES_EDIT,
                self::ACTIVITES_DELETE,
                self::ACTIVITES_CREATE_TASK,
                self::ACTIVITES_VALIDATE_N1,
                self::TACHES_VIEW,
                self::TACHES_EDIT,
                self::TACHES_DELETE,
                self::TACHES_CREATE_SUBTASK,
                self::TACHES_VALIDATE_N2,
                self::TACHES_COMMENT,
                self::SOUS_TACHES_VIEW,
                self::SOUS_TACHES_EDIT,
                self::SOUS_TACHES_DELETE,
                self::SOUS_TACHES_ASSIGN,
                self::DOCUMENTS_VIEW,
                self::DOCUMENTS_UPLOAD,
                self::DOCUMENTS_DELETE,
                self::DOCUMENTS_SHARE,
                // RESULTATS_RENVOYER_N0 removed — manager is N2 reviewer, not N0 gatekeeper.
                // N0 actions are granted via the task_responsable virtual role when
                // tache_user.is_responsable = true.
                self::REPORTS_VIEW,
                self::REPORTS_CREATE,
                self::EVALUATIONS_VIEW_PENDING,
                self::EVALUATIONS_VIEW_SCORE,
                // Task 9 — managers voient + exportent les fiches de leur scope.
                self::EVALUATIONS_VIEW_FICHE,
                self::EVALUATIONS_EXPORT_FICHE,
                // NOTIFICATIONS_MANAGE_PREFERENCES intentionally omitted — workspace-level
                // notification policy is reserved for owner/directeur (granted via the
                // owner contextual role's array_diff('all() except task-participant actions')).
            ],

            'cadre' => [
                self::WORKSPACES_VIEW,
                self::PROJETS_VIEW,
                self::ACTIVITES_VIEW,
                self::ACTIVITES_EDIT,
                self::ACTIVITES_CREATE_TASK,
                self::ACTIVITES_VALIDATE_N1,
                self::TACHES_VIEW,
                self::TACHES_EDIT,
                self::TACHES_CREATE_SUBTASK,
                self::TACHES_VALIDATE_N1,
                self::TACHES_COMMENT,
                self::SOUS_TACHES_VIEW,
                self::SOUS_TACHES_EDIT,
                self::SOUS_TACHES_DELETE,
                self::SOUS_TACHES_ASSIGN,
                self::DOCUMENTS_VIEW,
                self::DOCUMENTS_UPLOAD,
                // RESULTATS_RENVOYER_N0 removed — cadre is N1 reviewer, not N0 gatekeeper.
                // N0 actions are granted via the task_responsable virtual role when
                // tache_user.is_responsable = true.
                self::REPORTS_VIEW,
                self::EVALUATIONS_VIEW_PENDING,
                self::EVALUATIONS_VIEW_SCORE,
                // Task 9 — cadres voient + exportent les fiches de leurs assignés.
                self::EVALUATIONS_VIEW_FICHE,
                self::EVALUATIONS_EXPORT_FICHE,
            ],

            'collaborateur' => [
                self::WORKSPACES_VIEW,
                self::PROJETS_VIEW,
                self::ACTIVITES_VIEW,
                self::TACHES_VIEW,
                self::TACHES_SUBMIT_RESULT,
                self::TACHES_COMMENT,
                self::SOUS_TACHES_VIEW,
                self::SOUS_TACHES_EDIT,
                self::DOCUMENTS_VIEW,
                self::DOCUMENTS_UPLOAD,
                self::RESULTATS_APPROUVER_N0,
                self::RESULTATS_RENVOYER_N0,
                self::RESULTATS_ACTIVER_BYPASS,
                self::EVALUATIONS_VIEW_SCORE,
                // Task 9 — voient leur propre fiche (scope verrouillé en controller).
                // Pas d'export: réservé au cadre/manager/owner.
                self::EVALUATIONS_VIEW_FICHE,
            ],

            'stagiaire' => [
                self::WORKSPACES_VIEW,
                self::PROJETS_VIEW,
                self::ACTIVITES_VIEW,
                self::TACHES_VIEW,
                self::TACHES_SUBMIT_RESULT,
                self::TACHES_COMMENT,
                self::SOUS_TACHES_VIEW,
                self::SOUS_TACHES_EDIT,   // close gap with collaborateur — stagiaires need to update their own sous-tâche progression
                self::DOCUMENTS_VIEW,
                self::DOCUMENTS_UPLOAD,   // close gap with collaborateur — stagiaires need to attach deliverables
                self::RESULTATS_ACTIVER_BYPASS,
                self::EVALUATIONS_VIEW_SCORE,
                // Task 9 — fiche perso en lecture seule, pas d'export.
                self::EVALUATIONS_VIEW_FICHE,
            ],

            'observateur' => [
                self::WORKSPACES_VIEW,
                self::PROJETS_VIEW,
                self::ACTIVITES_VIEW,
                self::TACHES_VIEW,
                self::SOUS_TACHES_VIEW,
                self::DOCUMENTS_VIEW,
                self::EVALUATIONS_VIEW_SCORE,
                // Task 9 — fiche perso en lecture seule, pas d'export.
                self::EVALUATIONS_VIEW_FICHE,
            ],

            // Virtual role — derived from tache_user.is_responsable = true
            'task_responsable' => [
                self::TACHES_APPROVE_N0,
                self::TACHES_CREATE_SUBTASK,
                self::SOUS_TACHES_ASSIGN,
                self::RESULTATS_APPROUVER_N0,
                self::RESULTATS_RENVOYER_N0,
            ],

            default => [],
        };
    }

    /**
     * Map from activite_user / tache_user boolean pivot columns
     * to the permission string they grant when true.
     */
    public static function pivotOverrideMap(): array
    {
        return [
            // activite_user columns
            'can_edit_activity' => self::ACTIVITES_EDIT,
            'can_delete_activity' => self::ACTIVITES_DELETE,
            'can_create_tasks' => self::ACTIVITES_CREATE_TASK,
            'can_edit_tasks' => self::TACHES_EDIT,
            'can_delete_tasks' => self::TACHES_DELETE,
            'can_validate_results' => self::ACTIVITES_VALIDATE_N1,
            'can_assign_users' => self::SOUS_TACHES_ASSIGN,
            // tache_user columns
            'can_edit' => self::TACHES_EDIT,
            'can_validate' => self::TACHES_VALIDATE_N1,
        ];
    }
}
