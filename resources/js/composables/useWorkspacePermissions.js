// resources/js/composables/useWorkspacePermissions.js
import { computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'

/**
 * Workspace permission composable.
 *
 * Role hierarchy (highest to lowest):
 *   super_admin > directeur (owner) > manager > cadre > collaborateur > stagiaire > observateur
 *
 * Usage:
 *   const { canCreateProject, isDirecteur } = useWorkspacePermissions(workspaceRef)
 *
 * @param {Ref|null} workspace - A Vue ref containing the workspace object (with members array)
 */
export function useWorkspacePermissions(workspace = null) {
    const authStore = useAuthStore()

    // The authenticated user
    const currentUser = computed(() => authStore.user)

    // ==================== ROLE CHECKS ====================

    /** Platform-level super admin — bypasses all permission checks */
    const isSuperAdmin = computed(() => currentUser.value?.is_super_admin === true)

    /** User owns this workspace (directeur global role + owner pivot role) */
    const isDirecteur = computed(() => {
        if (!workspace?.value || !currentUser.value) return false
        return workspace.value.owner_id === currentUser.value.id
    })

    /** Pivot role of the current user in this workspace */
    const memberRole = computed(() => {
        if (!workspace?.value?.members || !currentUser.value) return null
        const member = workspace.value.members.find(m => m.id === currentUser.value.id)
        return member?.pivot?.role ?? null
    })

    /** Raw JSON permissions stored on the pivot */
    const memberPermissions = computed(() => {
        if (!workspace?.value?.members || !currentUser.value) return {}
        const member = workspace.value.members.find(m => m.id === currentUser.value.id)
        if (!member?.pivot) return {}

        let perms = member.pivot.permissions ?? {}
        if (typeof perms === 'string') {
            try { perms = JSON.parse(perms) } catch { perms = {} }
        }

        // 'all' shorthand used for owner pivot
        if (perms === 'all' || (Array.isArray(perms) && perms[0] === 'all')) {
            return {
                can_view_all_projects: true,
                can_create_projects: true,
                can_invite_members: true,
                can_delete_members: true,
                can_manage_settings: true,
                can_transfer_ownership: true,
            }
        }

        return {
            can_view_all_projects:   perms.can_view_all_projects   ?? false,
            can_create_projects:     perms.can_create_projects     ?? false,
            can_invite_members:      perms.can_invite_members      ?? false,
            can_delete_members:      perms.can_delete_members      ?? false,
            can_manage_settings:     perms.can_manage_settings     ?? false,
            can_transfer_ownership:  perms.can_transfer_ownership  ?? false,
        }
    })

    const isManager      = computed(() => memberRole.value === 'manager')
    const isCadre        = computed(() => memberRole.value === 'cadre')
    const isCollaborateur = computed(() => memberRole.value === 'collaborateur')
    const isStagiaire    = computed(() => memberRole.value === 'stagiaire')
    const isObservateur  = computed(() => memberRole.value === 'observateur')

    /** True if user has any active membership in this workspace */
    const isMember = computed(() => {
        if (isSuperAdmin.value || isDirecteur.value) return true
        return memberRole.value !== null
    })

    // ==================== PERMISSIONS ====================

    /** Can view the workspace at all */
    const canView = computed(() => isSuperAdmin.value || isDirecteur.value || isMember.value)

    /** Can modify workspace settings */
    const canManageSettings = computed(() => {
        if (isSuperAdmin.value || isDirecteur.value) return true
        return memberPermissions.value.can_manage_settings
    })

    /** Can delete the workspace */
    const canDelete = computed(() => isSuperAdmin.value || isDirecteur.value)

    /** Can invite new members */
    const canInviteMembers = computed(() => {
        if (isSuperAdmin.value || isDirecteur.value) return true
        return memberPermissions.value.can_invite_members
    })

    /** Can remove members */
    const canDeleteMembers = computed(() => {
        if (isSuperAdmin.value || isDirecteur.value) return true
        return memberPermissions.value.can_delete_members
    })

    /** Can create projects inside this workspace */
    const canCreateProjects = computed(() => {
        if (isSuperAdmin.value || isDirecteur.value) return true
        // Observateurs can never create projects
        if (isObservateur.value) return false
        return memberPermissions.value.can_create_projects
    })

    /** Can see all projects (not just ones they're assigned to) */
    const canViewAllProjects = computed(() => {
        if (isSuperAdmin.value || isDirecteur.value) return true
        return memberPermissions.value.can_view_all_projects
    })

    /** Can transfer workspace ownership */
    const canTransferOwnership = computed(() => {
        if (isSuperAdmin.value || isDirecteur.value) return true
        return memberPermissions.value.can_transfer_ownership
    })

    // ==================== MEMBER ACTION HELPERS ====================

    /**
     * Whether the current user can perform an action on a specific member.
     * @param {Object} targetMember - The member object (with pivot.role)
     * @param {'view'|'edit'|'delete'} action
     */
    const canPerformMemberAction = (targetMember, action) => {
        if (!targetMember) return false
        if (isSuperAdmin.value || isDirecteur.value) return true

        // Cannot act on the workspace owner
        if (targetMember.pivot?.role === 'owner') return false
        // Cannot act on yourself
        if (targetMember.id === currentUser.value?.id) return false

        switch (action) {
            case 'view':   return canView.value
            case 'edit':   return canInviteMembers.value
            case 'delete': return canDeleteMembers.value
            default:       return false
        }
    }

    /**
     * Check a single permission by name.
     * @param {string} permissionName
     */
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
        // User info
        currentUser,
        memberRole,
        memberPermissions,

        // Role flags
        isSuperAdmin,
        isDirecteur,
        isManager,
        isCadre,
        isCollaborateur,
        isStagiaire,
        isObservateur,
        isMember,

        // Permissions
        canView,
        canManageSettings,
        canDelete,
        canInviteMembers,
        canDeleteMembers,
        canCreateProjects,
        canViewAllProjects,
        canTransferOwnership,

        // Helpers
        hasPermission,
        canPerformMemberAction,
    }
}
