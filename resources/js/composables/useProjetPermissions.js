// resources/js/composables/useProjetPermissions.js
import { computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'

/**
 * Project-level permission composable.
 *
 * Reads pre-computed permissions from projet.user_permissions (set by the backend
 * ContextualPermissionGate via ProjetResource). Do not derive permissions from raw
 * pivot data — the backend is the single source of truth.
 *
 * Usage:
 *   const { canEdit, canManageMembers } = useProjetPermissions(projetRef)
 *
 * @param {Ref|null} projet - Vue ref containing the project object
 */
export function useProjetPermissions(projet = null) {
    const authStore = useAuthStore()

    const currentUser = computed(() => authStore.user)

    const isSuperAdmin = computed(() => currentUser.value?.is_super_admin === true)

    /** True if user is the project responsable */
    const isResponsable = computed(() => {
        if (!projet?.value || !currentUser.value) return false
        return projet.value.responsable_id === currentUser.value.id
    })

    /** Role name of the current user in this project (from API response) */
    const memberRole = computed(() => {
        if (!projet?.value?.members || !currentUser.value) return null
        const member = projet.value.members.find(m => m.id === currentUser.value.id)
        return member?.pivot?.role ?? member?.role ?? null
    })

    const isOwner         = computed(() => memberRole.value === 'owner')
    const isManager       = computed(() => memberRole.value === 'manager')
    const isCadre         = computed(() => memberRole.value === 'cadre')
    const isCollaborateur = computed(() => memberRole.value === 'collaborateur')
    const isObservateur   = computed(() => memberRole.value === 'observateur')

    const isMember = computed(() => {
        if (isResponsable.value) return true
        return memberRole.value !== null
    })

    // Pre-computed permissions object from backend (ContextualPermissionGate)
    const perms = computed(() => projet?.value?.user_permissions ?? {})

    const canView           = computed(() => perms.value.can_view ?? false)
    const canEdit           = computed(() => perms.value.can_edit ?? false)
    const canDelete         = computed(() => perms.value.can_delete ?? false)
    const canManageMembers  = computed(() => perms.value.can_manage_members ?? false)
    const canCreateActivity = computed(() => perms.value.can_create_activity ?? false)

    // Task 12: Documents
    const canViewDocuments   = computed(() => perms.value.can_view_documents ?? false)
    const canUploadDocuments = computed(() => perms.value.can_upload_documents ?? false)
    const canDeleteDocuments = computed(() => perms.value.can_delete_documents ?? false)
    const canShareDocuments  = computed(() => perms.value.can_share_documents ?? false)

    return {
        currentUser,
        memberRole,

        isSuperAdmin,
        isResponsable,
        isOwner,
        isManager,
        isCadre,
        isCollaborateur,
        isObservateur,
        isMember,

        canView,
        canEdit,
        canDelete,
        canManageMembers,
        canCreateActivity,

        // Task 12
        canViewDocuments,
        canUploadDocuments,
        canDeleteDocuments,
        canShareDocuments,
    }
}
