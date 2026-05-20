/**
 * Permission constants — JS mirror of app/Permissions/Permission.php
 *
 * Keep in sync with the PHP constants. These are the names stored in the
 * Spatie `permissions` table and checked by ContextualPermissionGate.
 *
 * Usage:
 *   import { Permission } from '@/permissions/Permission'
 *   if (tache.permissions.can_edit) { ... }
 *
 * Note: the frontend reads pre-computed `permissions` / `user_permissions` objects
 * from API responses. These constants are provided for reference and for the
 * future admin UI (Task 14) that writes to role_has_permissions.
 */
export const Permission = Object.freeze({
    // Workspace
    WORKSPACES_VIEW:            'workspaces.view',
    WORKSPACES_CREATE_PROJECT:  'workspaces.create_project',
    WORKSPACES_INVITE_MEMBER:   'workspaces.invite_member',
    WORKSPACES_REMOVE_MEMBER:   'workspaces.remove_member',
    WORKSPACES_MANAGE_SETTINGS: 'workspaces.manage_settings',

    // Project
    PROJETS_VIEW:               'projets.view',
    PROJETS_EDIT:               'projets.edit',
    PROJETS_DELETE:             'projets.delete',
    PROJETS_MANAGE_MEMBERS:     'projets.manage_members',

    // Activity
    ACTIVITES_VIEW:             'activites.view',
    ACTIVITES_EDIT:             'activites.edit',
    ACTIVITES_DELETE:           'activites.delete',
    ACTIVITES_CREATE_TASK:      'activites.create_task',
    ACTIVITES_VALIDATE_N1:      'activites.validate_n1',

    // Task
    TACHES_VIEW:                'taches.view',
    TACHES_EDIT:                'taches.edit',
    TACHES_DELETE:              'taches.delete',
    TACHES_SUBMIT_RESULT:       'taches.submit_result',
    TACHES_APPROVE_N0:          'taches.approve_n0',
    TACHES_CREATE_SUBTASK:      'taches.create_subtask',
    TACHES_VALIDATE_N1:         'taches.validate_n1',
    TACHES_VALIDATE_N2:         'taches.validate_n2',
    TACHES_COMMENT:             'taches.comment',

    // Subtask
    SOUS_TACHES_VIEW:           'sous_taches.view',
    SOUS_TACHES_EDIT:           'sous_taches.edit',
    SOUS_TACHES_DELETE:         'sous_taches.delete',
    SOUS_TACHES_ASSIGN:         'sous_taches.assign',

    // Documents
    DOCUMENTS_VIEW:             'documents.view',
    DOCUMENTS_UPLOAD:           'documents.upload',
    DOCUMENTS_DELETE:           'documents.delete',
    DOCUMENTS_SHARE:            'documents.share',

    // Results / N0 + bypass
    RESULTATS_APPROUVER_N0:     'resultats.approuver_n0',
    RESULTATS_RENVOYER_N0:      'resultats.renvoyer_n0',
    RESULTATS_ACTIVER_BYPASS:   'resultats.activer_bypass',

    // Reports
    REPORTS_VIEW:               'reports.view',
    REPORTS_CREATE:             'reports.create',

    // Evaluations / Scoring (Task 7)
    EVALUATIONS_VIEW_PENDING:   'evaluations.view_pending',
    EVALUATIONS_VIEW_SCORE:     'evaluations.view_score',

    // Users
    USERS_VIEW:                 'users.view',
    USERS_CREATE:               'users.create',
    USERS_UPDATE:               'users.update',
    USERS_DELETE:               'users.delete',
    USERS_ASSIGN:               'users.assign',
})

/**
 * Human-readable labels for each contextual role.
 * Used in the admin UI role picker and member management modals.
 */
export const RoleLabels = Object.freeze({
    owner:         'Propriétaire',
    manager:       'Manager',
    cadre:         'Cadre',
    collaborateur: 'Collaborateur',
    stagiaire:     'Stagiaire',
    observateur:   'Observateur',
})

/**
 * Ordered list of contextual roles (highest to lowest privilege).
 */
export const RoleHierarchy = [
    'owner',
    'manager',
    'cadre',
    'collaborateur',
    'stagiaire',
    'observateur',
]
