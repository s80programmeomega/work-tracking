// resources/js/composables/useActivitePermissions.js
import { computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'

/**
 * Activity-level permission composable.
 *
 * Contextual roles on activite_user pivot:
 *   cadre > collaborateur > stagiaire > observateur
 *
 * Granular boolean permissions also stored on the pivot:
 *   can_create_tasks, can_edit_tasks, can_delete_tasks,
 *   can_validate_results, can_assign_users
 *
 * N1 validation belongs to cadre (or anyone with can_validate_results).
 *
 * Usage:
 *   const { canCreateTask, canValidateN1 } = useActivitePermissions(activiteRef)
 *
 * @param {Ref|null} activite - A Vue ref containing the activity object (with members array)
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

    /** Pivot row for the current user in this activity */
    const memberPivot = computed(() => {
        if (!activite?.value?.members || !currentUser.value) return null
        const member = activite.value.members.find(m => m.id === currentUser.value.id)
        return member?.pivot ?? null
    })

    const memberRole      = computed(() => memberPivot.value?.role ?? null)
    const isCadre         = computed(() => memberRole.value === 'cadre')
    const isCollaborateur = computed(() => memberRole.value === 'collaborateur')
    const isStagiaire     = computed(() => memberRole.value === 'stagiaire')
    const isObservateur   = computed(() => memberRole.value === 'observateur')

    const isMember = computed(() => {
        if (isSuperAdmin.value || isResponsable.value) return true
        return memberRole.value !== null
    })

    // ==================== PERMISSIONS ====================

    const canView = computed(() => isSuperAdmin.value || isResponsable.value || isMember.value)

    const canEdit = computed(() => {
        if (isSuperAdmin.value || isResponsable.value) return true
        return memberPivot.value?.can_edit_activity ?? false
    })

    const canDelete = computed(() => isSuperAdmin.value || isResponsable.value)

    /** Can create tasks inside this activity */
    const canCreateTask = computed(() => {
        if (isSuperAdmin.value || isResponsable.value) return true
        return memberPivot.value?.can_create_tasks ?? false
    })

    /** Can edit tasks inside this activity */
    const canEditTask = computed(() => {
        if (isSuperAdmin.value || isResponsable.value) return true
        return memberPivot.value?.can_edit_tasks ?? false
    })

    /** Can delete tasks inside this activity */
    const canDeleteTask = computed(() => {
        if (isSuperAdmin.value || isResponsable.value) return true
        return memberPivot.value?.can_delete_tasks ?? false
    })

    /**
     * N1 validation — cadre role or explicit can_validate_results permission.
     * The cadre validates task results before they go to the manager (N2).
     */
    const canValidateN1 = computed(() => {
        if (isSuperAdmin.value || isResponsable.value) return true
        return isCadre.value || (memberPivot.value?.can_validate_results ?? false)
    })

    /** Can assign users to tasks in this activity */
    const canAssignUsers = computed(() => {
        if (isSuperAdmin.value || isResponsable.value) return true
        return memberPivot.value?.can_assign_users ?? false
    })

    return {
        currentUser,
        memberRole,
        memberPivot,

        // Role flags
        isSuperAdmin,
        isResponsable,
        isCadre,
        isCollaborateur,
        isStagiaire,
        isObservateur,
        isMember,

        // Permissions
        canView,
        canEdit,
        canDelete,
        canCreateTask,
        canEditTask,
        canDeleteTask,
        canValidateN1,
        canAssignUsers,
    }
}
