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
import { i18n } from '@/locales'

export const Permission = Object.freeze({
    // Workspace
    WORKSPACES_VIEW:            'workspaces.view',
    WORKSPACES_CREATE_PROJECT:  'workspaces.create_project',
    WORKSPACES_INVITE_MEMBER:   'workspaces.invite_member',
    WORKSPACES_REMOVE_MEMBER:   'workspaces.remove_member',
    WORKSPACES_MANAGE_SETTINGS:  'workspaces.manage_settings',
    WORKSPACES_VIEW_MEMBERS:     'workspaces.view_members',

    // Project
    PROJETS_VIEW:               'projets.view',
    PROJETS_EDIT:               'projets.edit',
    PROJETS_DELETE:             'projets.delete',
    PROJETS_MANAGE_MEMBERS:     'projets.manage_members',
    PROJETS_VIEW_ALL:           'projets.view_all',
    PROJETS_MANAGE_TEAMS:       'projets.manage_teams',

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

    // Evaluations / Agent sheet (Task 9)
    EVALUATIONS_VIEW_FICHE:     'evaluations.view_fiche',
    EVALUATIONS_EXPORT_FICHE:   'evaluations.export_fiche',

    // Evaluations / Dashboard (Task 10)
    EVALUATIONS_VIEW_DASHBOARD:          'evaluations.view_dashboard',
    EVALUATIONS_VIEW_WORKSPACE_TACHES:   'evaluations.view_workspace_taches',
    TACHES_INLINE_EDIT:                  'taches.inline_edit',

    // Notifications (Task 8)
    NOTIFICATIONS_MANAGE_PREFERENCES: 'notifications.manage_preferences',

    // Subscription (Task 13)
    SUBSCRIPTION_MANAGE: 'subscription.manage',

    // Recherche (Phase 6)
    SEARCH_GLOBAL: 'search.global',
    SEARCH_SCOPED: 'search.scoped',

    // Centre d'aide (Phase 7) — permissions granulaires par action
    HELP_ARTICLES_READ:         'help_articles.read',
    HELP_ARTICLES_CREATE:       'help_articles.create',
    HELP_ARTICLES_EDIT:         'help_articles.edit',
    HELP_ARTICLES_PUBLISH:      'help_articles.publish',
    HELP_ARTICLES_DELETE:       'help_articles.delete',
    HELP_ARTICLES_UPLOAD_IMAGE: 'help_articles.upload_image',
    HELP_CATEGORIES_MANAGE:     'help_categories.manage',

    // Users
    USERS_VIEW:                 'users.view',
    USERS_CREATE:               'users.create',
    USERS_UPDATE:               'users.update',
    USERS_DELETE:               'users.delete',
    USERS_ASSIGN:               'users.assign',
})

/**
 * Human-readable labels for every role in the app (3 global Spatie roles +
 * 7 contextual roles stored via role_id on pivot tables), by locale.
 *
 * Role identifier strings ('manager', 'cadre', etc.) are the permanent
 * internal contract used everywhere in gating logic — they never change.
 * Only these labels change when the displayed name needs to change.
 *
 * Mirror of app/Permissions/RoleLabel.php + lang/{fr,en}/roles.php — keep in sync.
 */
const RoleLabelsByLocale = Object.freeze({
    fr: {
        super_admin:      'Super Administrateur',
        directeur:        'Directeur',
        utilisateur:      'Utilisateur',
        owner:            'Propriétaire',
        manager:          'Manager',
        cadre:            'Cadre',
        task_responsable: 'Responsable de tâche',
        collaborateur:    'Collaborateur',
        stagiaire:        'Stagiaire',
        observateur:      'Observateur',
    },
    en: {
        super_admin:      'Super Admin',
        directeur:        'Director',
        utilisateur:      'User',
        owner:            'Owner',
        manager:          'Manager',
        cadre:            'Team Lead',
        task_responsable: 'Task Owner',
        collaborateur:    'Collaborator',
        stagiaire:        'Intern',
        observateur:      'Observer',
    },
})

/**
 * French-only labels for contextual roles, kept for call sites that don't
 * need locale awareness. Prefer getRoleLabel() for new code.
 */
export const RoleLabels = Object.freeze(RoleLabelsByLocale.fr)

/**
 * Resolve a role's display label for the given locale (defaults to the
 * active app locale). Falls back to French, then to the raw role string.
 */
export function getRoleLabel(role, locale) {
    const activeLocale = locale ?? i18n.global.locale.value
    return RoleLabelsByLocale[activeLocale]?.[role]
        ?? RoleLabelsByLocale.fr[role]
        ?? role
}

/**
 * Ordered list of contextual roles (highest to lowest privilege).
 */
export const RoleHierarchy = [
    'owner',
    'manager',
    'cadre',
    'task_responsable',
    'collaborateur',
    'stagiaire',
    'observateur',
]
