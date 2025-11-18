// resources/js/composables/useActivityPermissions.js
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

/**
 * Composable pour gérer les permissions des activités
 * Centralise toute la logique de vérification des permissions
 */
export function useActivityPermissions(activite) {
  const authStore = useAuthStore()

  // Utilisateur courant
  const currentUser = computed(() => authStore.user)

  // ✅ Vérifier si l'utilisateur est super admin
  const isSuperAdmin = computed(() => {
    return currentUser.value?.is_super_admin === true
  })

  // ✅ Vérifier si l'utilisateur est responsable de l'activité
  const isActivityResponsable = computed(() => {
    if (!activite.value || !currentUser.value) return false
    return activite.value.responsable_id === currentUser.value.id
  })

  // ✅ Vérifier si l'utilisateur est responsable du projet parent
  const isProjectResponsable = computed(() => {
    if (!activite.value?.projet || !currentUser.value) return false
    return activite.value.projet.responsable_id === currentUser.value.id
  })

  // ✅ Obtenir les permissions de l'utilisateur depuis l'API
  const userPermissions = computed(() => {
    if (!activite.value?.user_permissions) {
      return {
        can_edit: false,
        can_delete: false,
        can_manage_members: false,
        can_create_tasks: false,
        can_edit_tasks: false,
        can_delete_tasks: false,
        can_validate_results: false,
        can_assign_users: false
      }
    }
    return activite.value.user_permissions
  })

  // ✅ Vérifier si l'utilisateur est membre de l'activité
  const isMember = computed(() => {
    if (!activite.value?.membres || !currentUser.value) return false
    return activite.value.membres.some(m => m.id === currentUser.value.id)
  })

  // ✅ Obtenir le membre avec ses permissions
  const memberData = computed(() => {
    if (!activite.value?.membres || !currentUser.value) return null
    return activite.value.membres.find(m => m.id === currentUser.value.id)
  })

  // ✅ Obtenir le rôle de l'utilisateur
  const userRole = computed(() => {
    if (isSuperAdmin.value) return 'super_admin'
    if (isActivityResponsable.value) return 'responsable'
    if (isProjectResponsable.value) return 'project_responsable'
    if (memberData.value) return memberData.value.role || 'viewer'
    return null
  })

  // ==================== PERMISSIONS SPÉCIFIQUES ====================

  // ✅ Peut éditer l'activité
  const canEdit = computed(() => {
    if (isSuperAdmin.value) return true
    if (isActivityResponsable.value) return true
    if (isProjectResponsable.value) return true
    return userPermissions.value.can_edit || false
  })

  // ✅ Peut supprimer l'activité
  const canDelete = computed(() => {
    if (isSuperAdmin.value) return true
    if (isProjectResponsable.value) return true
    return userPermissions.value.can_delete || false
  })

  // ✅ Peut gérer les membres
  const canManageMembers = computed(() => {
    if (isSuperAdmin.value) return true
    if (isActivityResponsable.value) return true
    if (isProjectResponsable.value) return true
    return userPermissions.value.can_manage_members || false
  })

  // ✅ Peut créer des tâches
  const canCreateTasks = computed(() => {
    if (isSuperAdmin.value) return true
    if (isActivityResponsable.value) return true
    if (isProjectResponsable.value) return true

    // ❌ Les viewers ne peuvent JAMAIS créer
    if (userRole.value === 'viewer') return false

    return userPermissions.value.can_create_tasks || false
  })

  // ✅ Peut modifier des tâches
  const canEditTasks = computed(() => {
    if (isSuperAdmin.value) return true
    if (isActivityResponsable.value) return true
    if (isProjectResponsable.value) return true

    // ❌ Les viewers ne peuvent JAMAIS modifier
    if (userRole.value === 'viewer') return false

    return userPermissions.value.can_edit_tasks || false
  })

  // ✅ Peut supprimer des tâches
  const canDeleteTasks = computed(() => {
    if (isSuperAdmin.value) return true
    if (isActivityResponsable.value) return true
    if (isProjectResponsable.value) return true

    // ❌ Les viewers ne peuvent JAMAIS supprimer
    if (userRole.value === 'viewer') return false

    return userPermissions.value.can_delete_tasks || false
  })

  // ✅ Peut valider les résultats (N1)
  const canValidateResults = computed(() => {
    if (isSuperAdmin.value) return true
    if (isActivityResponsable.value) return true
    if (isProjectResponsable.value) return true

    // ❌ Les viewers ne peuvent JAMAIS valider
    if (userRole.value === 'viewer') return false

    return userPermissions.value.can_validate_results || false
  })

  // ✅ Peut assigner des utilisateurs
  const canAssignUsers = computed(() => {
    return canManageMembers.value
  })

  // ✅ Peut voir l'activité (accès basique)
  const canView = computed(() => {
    if (isSuperAdmin.value) return true
    if (isActivityResponsable.value) return true
    if (isProjectResponsable.value) return true
    if (isMember.value) return true

    // Vérifier si l'utilisateur est membre du projet parent
    if (activite.value?.projet) {
      return activite.value.projet.hasAccess?.(currentUser.value) || false
    }

    return false
  })

  // ==================== HELPERS ====================

  /**
   * Vérifier si l'utilisateur a UNE permission spécifique
   */
  const hasPermission = (permissionName) => {
    const permissionMap = {
      'edit': canEdit.value,
      'delete': canDelete.value,
      'manage_members': canManageMembers.value,
      'create_tasks': canCreateTasks.value,
      'edit_tasks': canEditTasks.value,
      'delete_tasks': canDeleteTasks.value,
      'validate_results': canValidateResults.value,
      'assign_users': canAssignUsers.value,
      'view': canView.value
    }

    return permissionMap[permissionName] || false
  }

  /**
   * Vérifier si l'utilisateur a TOUTES les permissions listées
   */
  const hasAllPermissions = (permissions) => {
    return permissions.every(p => hasPermission(p))
  }

  /**
   * Vérifier si l'utilisateur a AU MOINS UNE des permissions listées
   */
  const hasAnyPermission = (permissions) => {
    return permissions.some(p => hasPermission(p))
  }

  /**
   * Obtenir un message d'erreur pour permission refusée
   */
  const getPermissionDeniedMessage = (permissionName) => {
    const messages = {
      'edit': 'Vous n\'avez pas la permission de modifier cette activité',
      'delete': 'Vous n\'avez pas la permission de supprimer cette activité',
      'manage_members': 'Vous n\'avez pas la permission de gérer les membres',
      'create_tasks': 'Vous n\'avez pas la permission de créer des tâches',
      'edit_tasks': 'Vous n\'avez pas la permission de modifier des tâches',
      'delete_tasks': 'Vous n\'avez pas la permission de supprimer des tâches',
      'validate_results': 'Vous n\'avez pas la permission de valider les résultats',
      'assign_users': 'Vous n\'avez pas la permission d\'assigner des utilisateurs',
      'view': 'Vous n\'avez pas accès à cette activité'
    }

    return messages[permissionName] || 'Permission refusée'
  }

  return {
    // États
    currentUser,
    isSuperAdmin,
    isActivityResponsable,
    isProjectResponsable,
    isMember,
    memberData,
    userRole,
    userPermissions,

    // Permissions principales
    canEdit,
    canDelete,
    canManageMembers,
    canCreateTasks,
    canEditTasks,
    canDeleteTasks,
    canValidateResults,
    canAssignUsers,
    canView,

    // Helpers
    hasPermission,
    hasAllPermissions,
    hasAnyPermission,
    getPermissionDeniedMessage
  }
}