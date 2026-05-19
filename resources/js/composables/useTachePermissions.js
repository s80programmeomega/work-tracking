// resources/js/composables/useTachePermissions.js
import { computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'

/**
 * Task-level permission composable.
 *
 * Usage:
 *   const { canApprouverN0, canRenvoyerN0 } = useTachePermissions(tacheRef, resultatRef)
 *
 * @param {Ref|null} tache  - Vue ref containing the task object (with assignees array incl. pivot)
 * @param {Ref|null} resultat - Vue ref containing the TacheResultat object (optional, for N0 checks)
 */
export function useTachePermissions(tache = null, resultat = null) {
    const authStore = useAuthStore()

    const currentUser = computed(() => authStore.user)
    const isSuperAdmin = computed(() => currentUser.value?.is_super_admin === true)

    /** Pivot row for current user in the task assignees */
    const assigneePivot = computed(() => {
        if (!tache?.value?.assignees || !currentUser.value) return null
        const assignee = tache.value.assignees.find(a => a.id === currentUser.value.id)
        return assignee?.pivot ?? null
    })

    const isTaskResponsable = computed(() => {
        if (isSuperAdmin.value) return true
        return assigneePivot.value?.is_responsable === true || assigneePivot.value?.is_responsable === 1
    })

    // ==================== N0 PERMISSIONS ====================

    /**
     * Can approve a result at N0.
     * Reserved for the task is_responsable.
     */
    const canApprouverN0 = computed(() => {
        if (isSuperAdmin.value) return true
        if (!resultat?.value) return false
        // Only when statut is en_verification_n0
        if (resultat.value.statut !== 'en_verification_n0') return false
        return isTaskResponsable.value
    })

    /**
     * Can return a result to the author at N0 with a comment.
     * Same gate as approuverN0.
     */
    const canRenvoyerN0 = computed(() => canApprouverN0.value)

    // ==================== BYPASS PERMISSION ====================

    /**
     * Can activate the anti-sabotage bypass.
     * Reserved for the result's own author, only when statut = a_refaire,
     * and the bypass has not already been used on this submission.
     */
    const canActiverBypass = computed(() => {
        if (isSuperAdmin.value) return true
        if (!resultat?.value) return false
        // Only when N0 has returned the result
        if (resultat.value.statut !== 'a_refaire') return false
        // Only the result's author
        if (resultat.value.user?.id !== currentUser.value?.id) return false
        // Not already bypassed
        if (resultat.value.bypass_active) return false
        return true
    })

    return {
        currentUser,
        isSuperAdmin,
        isTaskResponsable,
        assigneePivot,

        // N0 permissions
        canApprouverN0,
        canRenvoyerN0,

        // Bypass permission
        canActiverBypass,
    }
}
