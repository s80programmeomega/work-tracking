// resources/js/composables/useProjetPermissions.js
import { computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'

/**
 * Project-level permission composable.
 *
 * Contextual roles on projet_user pivot:
 *   owner > manager > cadre > collaborateur > stagiaire > observateur
 *
 * Usage:
 *   const { canEditProject, canManageMembers } = useProjetPermissions(projetRef)
 *
 * @param {Ref|null} projet - A Vue ref containing the project object (with members array)
 */
export function useProjetPermissions(projet = null) {
    const authStore = useAuthStore()

    const currentUser = computed(() => authStore.user)

    const isSuperAdmin = computed(() => currentUser.value?.is_super_admin === true)

    /** Pivot role of the current user in this project */
    const memberRole = computed(() => {
        if (!projet?.value?.members || !currentUser.value) return null
        const member = projet.value.members.find(m => m.id === currentUser.value.id)
        return member?.pivot?.role ?? null
    })

    /** True if user is the project responsable */
    const isResponsable = computed(() => {
        if (!projet?.value || !currentUser.value) return false
        return projet.value.responsable_id === currentUser.value.id
    })

    const isOwner         = computed(() => memberRole.value === 'owner')
    const isManager       = computed(() => memberRole.value === 'manager')
    const isCadre         = computed(() => memberRole.value === 'cadre')
    const isCollaborateur = computed(() => memberRole.value === 'collaborateur')
    const isObservateur   = computed(() => memberRole.value === 'observateur')

    /** True if user has any membership in this project */
    const isMember = computed(() => {
        if (isSuperAdmin.value || isResponsable.value) return true
        return memberRole.value !== null
    })

    // ==================== PERMISSIONS ====================

    const canView = computed(() => {
        if (isSuperAdmin.value || isResponsable.value) return true
        return isMember.value || projet?.value?.visibility === 'public'
    })

    const canEdit = computed(() => {
        if (isSuperAdmin.value || isResponsable.value) return true
        return isOwner.value || isManager.value
    })

    const canDelete = computed(() => {
        if (isSuperAdmin.value || isResponsable.value) return true
        return isOwner.value
    })

    /** Can invite/manage project members */
    const canManageMembers = computed(() => {
        if (isSuperAdmin.value || isResponsable.value) return true
        return isOwner.value || isManager.value
    })

    /** Can create activities inside this project */
    const canCreateActivity = computed(() => {
        if (isSuperAdmin.value || isResponsable.value) return true
        return isOwner.value || isManager.value || isCadre.value
    })

    return {
        currentUser,
        memberRole,

        // Role flags
        isSuperAdmin,
        isResponsable,
        isOwner,
        isManager,
        isCadre,
        isCollaborateur,
        isObservateur,
        isMember,

        // Permissions
        canView,
        canEdit,
        canDelete,
        canManageMembers,
        canCreateActivity,
    }
}
