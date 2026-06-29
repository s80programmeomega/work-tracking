// resources/js/composables/useWorkspacePermissions.js
import { computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'

/**
 * Workspace permission composable.
 *
 * Reads pre-computed permissions from workspace.user_permissions (set by the backend
 * ContextualPermissionGate). Do not derive permissions from raw pivot data — the
 * backend is the single source of truth.
 *
 * Usage:
 *   const { canCreateProject, canInviteMembers } = useWorkspacePermissions(workspaceRef)
 *
 * @param {Ref|null} workspace - Vue ref containing the workspace object
 */
export function useWorkspacePermissions(workspace = null) {
    const authStore = useAuthStore()

    const currentUser = computed(() => authStore.user)

    const isSuperAdmin = computed(() => currentUser.value?.is_super_admin === true)

    /** True if user owns this workspace */
    const isDirecteur = computed(() => {
        if (!workspace?.value || !currentUser.value) return false
        return workspace.value.owner_id === currentUser.value.id
    })

    /** Role name of the current user in this workspace (from API response) */
    const memberRole = computed(() => {
        if (!workspace?.value?.members || !currentUser.value) return null
        const member = workspace.value.members.find(m => m.id === currentUser.value.id)
        return member?.pivot?.role ?? null
    })

    const isManager      = computed(() => memberRole.value === 'manager' || memberRole.value === 'owner')
    const isCadre        = computed(() => memberRole.value === 'cadre')
    const isCollaborateur = computed(() => memberRole.value === 'collaborateur')
    const isStagiaire    = computed(() => memberRole.value === 'stagiaire')
    const isObservateur  = computed(() => memberRole.value === 'observateur')

    const isMember = computed(() => {
        if (isDirecteur.value) return true
        return memberRole.value !== null
    })

    // Pre-computed permissions object from backend (ContextualPermissionGate)
    const perms = computed(() => workspace?.value?.user_permissions ?? {})

    const canView            = computed(() => perms.value.can_view_workspace ?? false)
    const canManageSettings  = computed(() => perms.value.can_manage_workspace_settings ?? false)
    const canDelete          = computed(() => isDirecteur.value)
    const canInviteMembers   = computed(() => isDirecteur.value || (perms.value.can_invite_members ?? false))
    const canDeleteMembers   = computed(() => isDirecteur.value || (perms.value.can_remove_members ?? false))
    const canCreateProjects  = computed(() => perms.value.can_create_project ?? false)
    const canViewAllProjects    = computed(() => isDirecteur.value || isManager.value)
    const canViewAllActivities  = computed(() => isDirecteur.value || isManager.value)
    const canTransferOwnership  = computed(() => isDirecteur.value)

    // Task 7: Evaluations / Scoring
    const canViewPendingValidations = computed(() => perms.value.can_view_pending_validations ?? false)
    const canViewEvaluationScore    = computed(() => perms.value.can_view_evaluation_score ?? false)

    // Task 9: Agent evaluation sheet
    // canViewFicheEvaluation gates the entry to the sheet page; the controller
    // re-applies a per-target scope check (own / cadre→assignees / manager→activity).
    // canExportFicheEvaluation gates the export action (PDF/Excel) — same scope.
    const canViewFicheEvaluation   = computed(() => perms.value.can_view_fiche_evaluation ?? false)
    const canExportFicheEvaluation = computed(() => perms.value.can_export_fiche_evaluation ?? false)

    // Task 10: Evaluation dashboard + workspace-wide task view
    const canViewEvaluationDashboard  = computed(() => perms.value.can_view_evaluation_dashboard ?? false)
    const canViewWorkspaceTaches      = computed(() => isDirecteur.value || (perms.value.can_view_workspace_taches ?? false))
    const canInlineEditTache          = computed(() => perms.value.can_inline_edit_tache ?? false)

    // Task 12: Documents
    const canManageWorkspaceDocuments = computed(() => perms.value.can_manage_workspace_documents ?? false)

    // Task 13: Subscription
    const canManageSubscription = computed(() => perms.value.can_manage_subscription ?? false)

    // Task 8: Notifications
    const canManageNotificationPreferences = computed(() => perms.value.can_manage_notification_preferences ?? false)

    // Phase 6: Recherche globale — manager et supérieur (owner/directeur inclus)
    const canSearchGlobal = computed(() => isDirecteur.value || (perms.value.can_search_global ?? false))

    // Phase 6: Recherche scopée — cadre, collaborateur, stagiaire (ressources assignées uniquement)
    const canSearchScoped = computed(() => perms.value.can_search_scoped ?? false)

    // Peut accéder à la recherche (toutes tiers confondus)
    const canSearch = computed(() => canSearchGlobal.value || canSearchScoped.value)

    // Phase 11E: Voir la liste des membres et leur activité (owner/directeur + manager)
    const canViewMembers = computed(() => isDirecteur.value || (perms.value.can_view_members ?? false))

    // Phase 7: Centre d'aide — lecture pour tous les rôles ; gestion granulaire par action
    // (owner/directeur par défaut).
    const canReadHelpArticles        = computed(() => perms.value.can_help_articles_read ?? true)
    const canCreateHelpArticles      = computed(() => isDirecteur.value || (perms.value.can_help_articles_create ?? false))
    const canEditHelpArticles        = computed(() => isDirecteur.value || (perms.value.can_help_articles_edit ?? false))
    const canPublishHelpArticles     = computed(() => isDirecteur.value || (perms.value.can_help_articles_publish ?? false))
    const canDeleteHelpArticles      = computed(() => isDirecteur.value || (perms.value.can_help_articles_delete ?? false))
    const canUploadHelpImages        = computed(() => isDirecteur.value || (perms.value.can_help_articles_upload_image ?? false))
    const canManageHelpCategories    = computed(() => isDirecteur.value || (perms.value.can_help_categories_manage ?? false))

    // Helper agrégé (PAS une permission backend) : sert uniquement à afficher/masquer
    // le point d'entrée vers le back-office d'aide. Vrai dès que l'utilisateur détient
    // au moins une permission de gestion granulaire. L'autorisation réelle de chaque
    // action est vérifiée côté serveur par AdminHelpController (help_articles.create/
    // edit/publish/delete/upload_image, help_categories.manage).
    const canManageHelpArticles = computed(() =>
        canCreateHelpArticles.value
        || canEditHelpArticles.value
        || canPublishHelpArticles.value
        || canDeleteHelpArticles.value
        || canManageHelpCategories.value
    )

    // G8: Sidebar gating helpers
    // canViewAllTasks: managers, owners and directeurs can see the full task list.
    const canViewAllTasks = computed(() => isDirecteur.value || isManager.value || isCadre.value)
    // canSubmitResult: any member who is a collaborateur, cadre or above can submit
    // results — corresponds to TACHES_SUBMIT_RESULT permission on the backend.
    const canSubmitResult = computed(() => perms.value.can_submit_result ?? isMember.value)

    /**
     * Whether the current user can perform an action on a specific member.
     * @param {Object} targetMember
     * @param {'view'|'edit'|'delete'} action
     */
    const canPerformMemberAction = (targetMember, action) => {
        if (!targetMember) return false
        if (isDirecteur.value) return true
        if (targetMember.pivot?.role === 'owner') return false
        if (targetMember.id === currentUser.value?.id) return false

        switch (action) {
            case 'view':   return canView.value
            case 'edit':   return canInviteMembers.value
            case 'delete': return canDeleteMembers.value
            default:       return false
        }
    }

    const hasPermission = (permissionName) => {
        const map = {
            view:               canView.value,
            manage_settings:    canManageSettings.value,
            delete:             canDelete.value,
            invite_members:     canInviteMembers.value,
            delete_members:     canDeleteMembers.value,
            create_projects:    canCreateProjects.value,
            view_all_projects:  canViewAllProjects.value,
            transfer_ownership: canTransferOwnership.value,
        }
        return map[permissionName] ?? false
    }

    return {
        currentUser,
        memberRole,

        isSuperAdmin,
        isDirecteur,
        isManager,
        isCadre,
        isCollaborateur,
        isStagiaire,
        isObservateur,
        isMember,

        canView,
        canManageSettings,
        canDelete,
        canInviteMembers,
        canDeleteMembers,
        canCreateProjects,
        canViewAllProjects,
        canViewAllActivities,
        canTransferOwnership,

        // Task 7
        canViewPendingValidations,
        canViewEvaluationScore,

        // Task 9
        canViewFicheEvaluation,
        canExportFicheEvaluation,

        // Task 10
        canViewEvaluationDashboard,
        canViewWorkspaceTaches,
        canInlineEditTache,

        // Task 12
        canManageWorkspaceDocuments,

        // Task 13
        canManageSubscription,

        // Task 8
        canManageNotificationPreferences,

        // Phase 6
        canSearchGlobal,
        canSearchScoped,
        canSearch,

        // Phase 7
        canReadHelpArticles,
        canCreateHelpArticles,
        canEditHelpArticles,
        canPublishHelpArticles,
        canDeleteHelpArticles,
        canUploadHelpImages,
        canManageHelpCategories,
        canManageHelpArticles,

        // G8
        canViewAllTasks,
        canSubmitResult,

        // Phase 11E
        canViewMembers,

        hasPermission,
        canPerformMemberAction,
    }
}
