// resources/js/composables/useActivitePermissions.js
import { computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'

/**
 * Activity-level permission composable.
 *
 * Reads pre-computed permissions from activite.user_permissions (set by the backend
 * ContextualPermissionGate via ActiviteResource). Boolean pivot overrides
 * (can_create_tasks, can_edit_tasks, etc.) are already merged server-side.
 *
 * Usage:
 *   const { canCreateTask, canValidateN1 } = useActivitePermissions(activiteRef)
 *
 * @param {Ref|null} activite - Vue ref containing the activity object
 */
export function useActivitePermissions(activite = null) {
    const authStore = useAuthStore()

    const currentUser = computed(() => authStore.user)

    const isSuperAdmin = computed(() => currentUser.value?.is_super_admin === true)

    /** True if user is the activity responsable */
    const isResponsable = computed(() => {
        if (!activite?.value || !currentUser.value) return false
        return activite.value.responsable_id === currentUser.value.id
    })

    /** Role name of the current user in this activity (from API response) */
    const memberRole = computed(() => {
        if (!activite?.value?.membres || !currentUser.value) return null
        const member = activite.value.membres.find(m => m.id === currentUser.value.id)
        return member?.role ?? member?.pivot?.role ?? null
    })

    const isCadre         = computed(() => memberRole.value === 'cadre')
    const isCollaborateur = computed(() => memberRole.value === 'collaborateur')
    const isStagiaire     = computed(() => memberRole.value === 'stagiaire')
    const isObservateur   = computed(() => memberRole.value === 'observateur')

    const isMember = computed(() => {
        if (isResponsable.value) return true
        return memberRole.value !== null
    })

    // Pre-computed permissions object from backend (ContextualPermissionGate)
    const perms = computed(() => activite?.value?.user_permissions ?? {})

    const canView         = computed(() => isResponsable.value || isMember.value)
    const canEdit         = computed(() => perms.value.can_edit_activity ?? false)
    const canDelete       = computed(() => perms.value.can_delete_activity ?? false)
    const canCreateTask   = computed(() => perms.value.can_create_tasks ?? false)
    const canEditTask     = computed(() => perms.value.can_edit_tasks ?? false)
    const canDeleteTask   = computed(() => perms.value.can_delete_tasks ?? false)
    const canValidateN1   = computed(() => perms.value.can_validate_results ?? false)
    const canAssignUsers  = computed(() => perms.value.can_assign_users ?? false)
    const canManageMembers = computed(() => perms.value.can_manage_members ?? false)

    /** Can create sous-tâches (cadre, collaborateur, or explicit can_create_tasks) */
    const canCreateSousTache = computed(() => {
        if (isResponsable.value) return true
        if (isObservateur.value) return false
        return isCadre.value || isCollaborateur.value || (memberPivot.value?.can_create_tasks ?? false)
    })

    /** Can assign an intervenant to a sous-tâche */
    const canAssignSousTacheIntervenant = computed(() => {
        if (isResponsable.value) return true
        return isCadre.value || (memberPivot.value?.can_assign_users ?? false)
    })

    return {
        currentUser,
        memberRole,

        isSuperAdmin,
        isResponsable,
        isCadre,
        isCollaborateur,
        isStagiaire,
        isObservateur,
        isMember,

        canView,
        canEdit,
        canDelete,
        canCreateTask,
        canEditTask,
        canDeleteTask,
        canValidateN1,
        canAssignUsers,
        canManageMembers,
        canCreateSousTache,
        canAssignSousTacheIntervenant,
    }
}
