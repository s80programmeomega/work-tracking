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

    // ── Reports ───────────────────────────────────────────────────────────
    const REPORTS_VIEW = 'reports.view';

    const REPORTS_CREATE = 'reports.create';

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

            self::REPORTS_VIEW,
            self::REPORTS_CREATE,

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
            'owner' => self::all(),

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
                self::TACHES_APPROVE_N0,
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
                self::RESULTATS_APPROUVER_N0,
                self::RESULTATS_RENVOYER_N0,
                self::REPORTS_VIEW,
                self::REPORTS_CREATE,
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
                self::TACHES_APPROVE_N0,
                self::TACHES_CREATE_SUBTASK,
                self::TACHES_VALIDATE_N1,
                self::TACHES_COMMENT,
                self::SOUS_TACHES_VIEW,
                self::SOUS_TACHES_EDIT,
                self::SOUS_TACHES_DELETE,
                self::SOUS_TACHES_ASSIGN,
                self::DOCUMENTS_VIEW,
                self::DOCUMENTS_UPLOAD,
                self::RESULTATS_APPROUVER_N0,
                self::RESULTATS_RENVOYER_N0,
                self::REPORTS_VIEW,
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
            ],

            'stagiaire' => [
                self::WORKSPACES_VIEW,
                self::PROJETS_VIEW,
                self::ACTIVITES_VIEW,
                self::TACHES_VIEW,
                self::TACHES_SUBMIT_RESULT,
                self::TACHES_COMMENT,
                self::SOUS_TACHES_VIEW,
                self::DOCUMENTS_VIEW,
            ],

            'observateur' => [
                self::WORKSPACES_VIEW,
                self::PROJETS_VIEW,
                self::ACTIVITES_VIEW,
                self::TACHES_VIEW,
                self::SOUS_TACHES_VIEW,
                self::DOCUMENTS_VIEW,
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
