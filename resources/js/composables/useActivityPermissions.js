// resources/js/composables/useActivityPermissions.js - VERSION OPTIMISÉE
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

/**
 * Composable pour gérer les permissions des activités
 * Centralise toute la logique de vérification des permissions
 * Suit une hiérarchie claire: SuperAdmin > Responsable Projet > Responsable Activité > Membre > Viewer
 */
export function useActivityPermissions(activite) {
  const authStore = useAuthStore()

  // ==================== UTILISATEUR COURANT ====================
  
  const currentUser = computed(() => authStore.user)

  // ==================== RÔLES DE BASE ====================

  /**
   * Vérifie si l'utilisateur est super admin
   * Les super admins ont TOUS les droits sur TOUTES les activités
   */
  const isSuperAdmin = computed(() => {
    return currentUser.value?.is_super_admin === true
  })

  /**
   * Vérifie si l'utilisateur est responsable de l'activité
   * A tous les droits sur l'activité
   */
  const isActivityResponsable = computed(() => {
    if (!activite.value || !currentUser.value) return false
    return activite.value.responsable_id === currentUser.value.id
  })

  /**
   * Vérifie si l'utilisateur est responsable du projet parent
   * A des droits étendus sur toutes les activités du projet
   */
  const isProjectResponsable = computed(() => {
    if (!activite.value?.projet || !currentUser.value) return false
    return activite.value.projet.responsable_id === currentUser.value.id
  })

  /**
   * Vérifie si l'utilisateur est membre de l'activité
   */
  const isMember = computed(() => {
    if (!activite.value?.membres || !currentUser.value) return false
    return activite.value.membres.some(m => m.id === currentUser.value.id)
  })

  /**
   * Obtient les données du membre avec ses permissions
   */
  const memberData = computed(() => {
    if (!activite.value?.membres || !currentUser.value) return null
    return activite.value.membres.find(m => m.id === currentUser.value.id)
  })

  // ==================== PERMISSIONS API ====================

  /**
   * Obtient les permissions depuis l'API
   * Fournit un objet avec toutes les permissions possibles
   */
  const userPermissions = computed(() => {
    const defaultPermissions = {
      can_edit: false,
      can_delete: false,
      can_manage_members: false,
      can_create_tasks: false,
      can_edit_tasks: false,
      can_delete_tasks: false,
      can_validate_results: false,
      can_assign_users: false
    }

    if (!activite.value?.user_permissions) {
      return defaultPermissions
    }

    return { ...defaultPermissions, ...activite.value.user_permissions }
  })

  // ==================== RÔLE UTILISATEUR ====================

  /**
   * Détermine le rôle de l'utilisateur dans l'activité
   * Hiérarchie: super_admin > project_responsable > responsable > contributor > viewer
   */
  const userRole = computed(() => {
    if (isSuperAdmin.value) return 'super_admin'
    if (isProjectResponsable.value) return 'project_responsable'
    if (isActivityResponsable.value) return 'responsable'
    
    if (memberData.value) {
      // Le rôle peut être défini dans pivot.role ou directement
      return memberData.value.pivot?.role || memberData.value.role || 'viewer'
    }
    
    return null
  })

  /**
   * Vérifie si l'utilisateur est un viewer (lecture seule)
   */
  const isViewer = computed(() => userRole.value === 'viewer')

  /**
   * Vérifie si l'utilisateur est un contributeur
   */
  const isContributor = computed(() => userRole.value === 'contributor')

  // ==================== PERMISSIONS SPÉCIFIQUES ====================

  /**
   * PERMISSION: Peut voir l'activité (accès basique)
   * Hiérarchie complète + vérification projet parent
   */
  const canView = computed(() => {
    if (isSuperAdmin.value) return true
    if (isProjectResponsable.value) return true
    if (isActivityResponsable.value) return true
    if (isMember.value) return true

    // Vérifier l'accès via le projet parent
    if (activite.value?.projet) {
      return activite.value.projet.hasAccess?.(currentUser.value) || false
    }

    return false
  })

  /**
   * PERMISSION: Peut éditer l'activité
   * SuperAdmin + Responsables seulement
   */
  const canEdit = computed(() => {
    if (isSuperAdmin.value) return true
    if (isActivityResponsable.value) return true
    if (isProjectResponsable.value) return true
    return userPermissions.value.can_edit || false
  })

  /**
   * PERMISSION: Peut supprimer l'activité
   * SuperAdmin + Responsable projet seulement (sécurité renforcée)
   */
  const canDelete = computed(() => {
    if (isSuperAdmin.value) return true
    if (isProjectResponsable.value) return true
    // Note: Le responsable d'activité ne peut PAS supprimer sans permission explicite
    return userPermissions.value.can_delete || false
  })

  /**
   * PERMISSION: Peut gérer les membres
   * SuperAdmin + Responsables
   */
  const canManageMembers = computed(() => {
    if (isSuperAdmin.value) return true
    if (isActivityResponsable.value) return true
    if (isProjectResponsable.value) return true
    return userPermissions.value.can_manage_members || false
  })

  /**
   * PERMISSION: Peut créer des tâches
   * Refusé pour les viewers
   */
  const canCreateTasks = computed(() => {
    if (isSuperAdmin.value) return true
    if (isActivityResponsable.value) return true
    if (isProjectResponsable.value) return true

    // ❌ CRITIQUE: Les viewers ne peuvent JAMAIS créer
    if (isViewer.value) return false

    return userPermissions.value.can_create_tasks || false
  })

  /**
   * PERMISSION: Peut modifier des tâches
   * Refusé pour les viewers
   */
  const canEditTasks = computed(() => {
    if (isSuperAdmin.value) return true
    if (isActivityResponsable.value) return true
    if (isProjectResponsable.value) return true

    // ❌ CRITIQUE: Les viewers ne peuvent JAMAIS modifier
    if (isViewer.value) return false

    return userPermissions.value.can_edit_tasks || false
  })

  /**
   * PERMISSION: Peut supprimer des tâches
   * Refusé pour les viewers et contributors
   */
  const canDeleteTasks = computed(() => {
    if (isSuperAdmin.value) return true
    if (isActivityResponsable.value) return true
    if (isProjectResponsable.value) return true

    // ❌ CRITIQUE: Viewers et contributors ne peuvent PAS supprimer
    if (isViewer.value || isContributor.value) return false

    return userPermissions.value.can_delete_tasks || false
  })

  /**
   * PERMISSION: Peut valider les résultats (N1)
   * Pour responsables et utilisateurs avec permission explicite
   */
  const canValidateResults = computed(() => {
    if (isSuperAdmin.value) return true
    if (isActivityResponsable.value) return true
    if (isProjectResponsable.value) return true

    // ❌ CRITIQUE: Les viewers ne peuvent JAMAIS valider
    if (isViewer.value) return false

    return userPermissions.value.can_validate_results || false
  })

  /**
   * PERMISSION: Peut assigner des utilisateurs aux tâches
   * Équivalent à la gestion des membres
   */
  const canAssignUsers = computed(() => {
    return canManageMembers.value || userPermissions.value.can_assign_users || false
  })

  /**
   * PERMISSION: Peut modifier ses propres tâches
   * Les contributeurs peuvent modifier leurs propres tâches
   */
  const canEditOwnTasks = computed(() => {
    if (canEditTasks.value) return true
    return isContributor.value
  })

  // ==================== HELPERS ====================

  /**
   * Vérifie si l'utilisateur a UNE permission spécifique
   * @param {string} permissionName - Nom de la permission à vérifier
   * @returns {boolean}
   */
  const hasPermission = (permissionName) => {
    const permissionMap = {
      'view': canView.value,
      'edit': canEdit.value,
      'delete': canDelete.value,
      'manage_members': canManageMembers.value,
      'create_tasks': canCreateTasks.value,
      'edit_tasks': canEditTasks.value,
      'delete_tasks': canDeleteTasks.value,
      'validate_results': canValidateResults.value,
      'assign_users': canAssignUsers.value,
      'edit_own_tasks': canEditOwnTasks.value
    }

    return permissionMap[permissionName] || false
  }

  /**
   * Vérifie si l'utilisateur a TOUTES les permissions listées
   * @param {string[]} permissions - Liste des permissions à vérifier
   * @returns {boolean}
   */
  const hasAllPermissions = (permissions) => {
    return permissions.every(p => hasPermission(p))
  }

  /**
   * Vérifie si l'utilisateur a AU MOINS UNE des permissions listées
   * @param {string[]} permissions - Liste des permissions à vérifier
   * @returns {boolean}
   */
  const hasAnyPermission = (permissions) => {
    return permissions.some(p => hasPermission(p))
  }

  /**
   * Obtient un message d'erreur approprié pour permission refusée
   * @param {string} permissionName - Nom de la permission refusée
   * @returns {string} Message d'erreur localisé
   */
  const getPermissionDeniedMessage = (permissionName) => {
    const messages = {
      'view': 'Vous n\'avez pas accès à cette activité',
      'edit': 'Vous n\'avez pas la permission de modifier cette activité',
      'delete': 'Vous n\'avez pas la permission de supprimer cette activité',
      'manage_members': 'Vous n\'avez pas la permission de gérer les membres de cette activité',
      'create_tasks': 'Vous n\'avez pas la permission de créer des tâches dans cette activité',
      'edit_tasks': 'Vous n\'avez pas la permission de modifier les tâches de cette activité',
      'delete_tasks': 'Vous n\'avez pas la permission de supprimer des tâches',
      'validate_results': 'Vous n\'avez pas la permission de valider les résultats',
      'assign_users': 'Vous n\'avez pas la permission d\'assigner des utilisateurs',
      'edit_own_tasks': 'Vous n\'avez pas la permission de modifier vos tâches'
    }

    return messages[permissionName] || 'Permission refusée'
  }

  /**
   * Obtient une explication détaillée sur pourquoi la permission est refusée
   * Utile pour le debugging et les messages utilisateur détaillés
   */
  const getPermissionDenialReason = (permissionName) => {
    if (!currentUser.value) {
      return 'Utilisateur non authentifié'
    }

    if (!activite.value) {
      return 'Activité non définie'
    }

    if (isViewer.value) {
      return 'Votre rôle de lecteur ne permet pas cette action'
    }

    if (!isMember.value) {
      return 'Vous n\'êtes pas membre de cette activité'
    }

    return 'Permission insuffisante pour cette action'
  }

  /**
   * Vérifie si un utilisateur peut effectuer une action sur une tâche spécifique
   * @param {Object} task - La tâche à vérifier
   * @param {string} action - L'action à effectuer ('edit', 'delete', 'validate')
   * @returns {boolean}
   */
  const canPerformTaskAction = (task, action) => {
    if (!task) return false

    // Vérifications hiérarchiques
    if (isSuperAdmin.value) return true
    if (isProjectResponsable.value) return true
    if (isActivityResponsable.value) return true

    // Actions spécifiques
    switch (action) {
      case 'edit':
        // Peut modifier si permission générale OU si c'est sa propre tâche et qu'il est contributor
        return canEditTasks.value || 
               (task.assignees?.some(a => a.id === currentUser.value.id) && canEditOwnTasks.value)
      
      case 'delete':
        return canDeleteTasks.value
      
      case 'validate':
        return canValidateResults.value
      
      default:
        return false
    }
  }

  // ==================== DEBUG ====================

  /**
   * Fonction de debug pour afficher l'état des permissions
   * Utile pour le développement
   */
  const debugPermissions = () => {
    return {
      user: {
        id: currentUser.value?.id,
        name: currentUser.value?.nom || currentUser.value?.name,
        role: userRole.value
      },
      activity: {
        id: activite.value?.id,
        name: activite.value?.nom,
        responsable_id: activite.value?.responsable_id
      },
      roles: {
        isSuperAdmin: isSuperAdmin.value,
        isProjectResponsable: isProjectResponsable.value,
        isActivityResponsable: isActivityResponsable.value,
        isMember: isMember.value,
        isViewer: isViewer.value,
        isContributor: isContributor.value
      },
      permissions: {
        canView: canView.value,
        canEdit: canEdit.value,
        canDelete: canDelete.value,
        canManageMembers: canManageMembers.value,
        canCreateTasks: canCreateTasks.value,
        canEditTasks: canEditTasks.value,
        canDeleteTasks: canDeleteTasks.value,
        canValidateResults: canValidateResults.value,
        canAssignUsers: canAssignUsers.value
      },
      apiPermissions: userPermissions.value
    }
  }

  // ==================== RETOUR ====================

  return {
    // Utilisateur et états de base
    currentUser,
    userRole,
    
    // Rôles
    isSuperAdmin,
    isActivityResponsable,
    isProjectResponsable,
    isMember,
    memberData,
    isViewer,
    isContributor,
    
    // Permissions API
    userPermissions,
    
    // Permissions principales
    canView,
    canEdit,
    canDelete,
    canManageMembers,
    canCreateTasks,
    canEditTasks,
    canDeleteTasks,
    canValidateResults,
    canAssignUsers,
    canEditOwnTasks,
    
    // Helpers
    hasPermission,
    hasAllPermissions,
    hasAnyPermission,
    getPermissionDeniedMessage,
    getPermissionDenialReason,
    canPerformTaskAction,
    
    // Debug
    debugPermissions
  }
}