// resources/js/composables/useWorkspacePermissions.js
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

/**
 * Composant pour gérer les permissions des membres d'un workspace
 * Suit la hiérarchie: SuperAdmin > Owner > Admin > Member > Viewer
 * Gère les permissions stockées en JSON dans la table workspace_members
 */
export function useWorkspacePermissions(workspace = null) {
  const authStore = useAuthStore()

  // ==================== UTILISATEUR COURANT ====================
  
  const currentUser = computed(() => authStore.user)

  // ==================== RÔLES DE BASE ====================

  /**
   * Vérifie si l'utilisateur est super admin
   * Les super admins ont TOUS les droits sur TOUS les workspaces
   */
  const isSuperAdmin = computed(() => {
    return currentUser.value?.is_super_admin === true
  })

   // Vérifie si l'utilisateur est propriétaire du workspace
  const isOwner = computed(() => {
    if (!workspace?.value || !currentUser.value) return false
    return workspace.value.owner_id === currentUser.value.id
  })
  

  /**
   * Vérifie si l'utilisateur est membre du workspace
   */
  const isMember = computed(() => {
    if (!workspace?.value?.members || !currentUser.value) return false
    return workspace.value.members.some(m => m.id === currentUser.value.id)
  })

  // Récupère les données du membre
  const memberData = computed(() => {
    if (!workspace?.value?.members || !currentUser.value) return null
    return workspace.value.members.find(m => m.id === currentUser.value.id)
  })

// Récupère les permissions du membre
  const memberPermissions = computed(() => {
    if (!memberData.value?.pivot) return {}
    
    const pivot = memberData.value.pivot
    let permissions = pivot.permissions || {}

    if (typeof permissions === 'string') {
      try {
        permissions = JSON.parse(permissions)
      } catch (e) {
        console.error('Erreur parsing permissions:', permissions)
        permissions = {}
      }
    }

    // Format "all" pour owner
    if (permissions === 'all' || 
        (Array.isArray(permissions) && permissions[0] === 'all') ||
        (typeof permissions === 'string' && permissions === '["all"]')) {
      return {
        can_delete_members: true,
        can_invite_members: true,
        can_create_projects: true,
        can_manage_settings: true,
        can_view_all_projects: true,
        can_transfer_ownership: true
      }
    }

    return {
      can_delete_members: permissions.can_delete_members ?? false,
      can_invite_members: permissions.can_invite_members ?? false,
      can_create_projects: permissions.can_create_projects ?? false,
      can_manage_settings: permissions.can_manage_settings ?? false,
      can_view_all_projects: permissions.can_view_all_projects ?? false,
      can_transfer_ownership: permissions.can_transfer_ownership ?? false
    }
  })

  /**
   * Obtient le rôle du membre depuis le pivot
   */
  const memberRole = computed(() => {
    if (!memberData.value?.pivot) return null
    return memberData.value.pivot.role
  })

  // ==================== RÔLE UTILISATEUR ====================

  /**
   * Détermine le rôle de l'utilisateur dans le workspace
   * Hiérarchie: super_admin > owner > admin > member > viewer
   */
  const userRole = computed(() => {
    if (isSuperAdmin.value) return 'super_admin'
    if (isOwner.value) return 'owner'
    if (memberRole.value) return memberRole.value
    return null
  })

  /**
   * Vérifie si l'utilisateur est un viewer (lecture seule)
   */
  const isViewer = computed(() => userRole.value === 'viewer')

  /**
   * Vérifie si l'utilisateur est un admin
   */
  const isAdmin = computed(() => userRole.value === 'admin')

  /**
   * Vérifie si l'utilisateur est membre régulier
   */
  const isRegularMember = computed(() => userRole.value === 'member')

  // ==================== PERMISSIONS SPÉCIFIQUES ====================

   // Permission d'éditer le workspace (seulement propriétaire)
  const canEditWorkspace = computed(() => {
    if (isSuperAdmin.value) return true
    if (isOwner.value) return true
    return false
  })

  /**
   * PERMISSION: Peut voir le workspace
   * SuperAdmin, Owner, Members avec accès
   */
  const canView = computed(() => {
    if (isSuperAdmin.value) return true
    if (isOwner.value) return true
    if (isMember.value) return true
    return false
  })

  /**
   * PERMISSION: Peut éditer le workspace
   * SuperAdmin, Owner, Admins avec permission
   */
  const canEdit = computed(() => {
    if (isSuperAdmin.value) return true
    if (isOwner.value) return true
    if (!memberPermissions.value) return false
    return memberPermissions.value.can_manage_settings || false
  })

  /**
   * PERMISSION: Peut supprimer le workspace
   * SuperAdmin, Owner seulement (sécurité maximale)
   */
  const canDelete = computed(() => {
    if (isSuperAdmin.value) return true
    if (isOwner.value) return true
    return false
  })

  /**
   * PERMISSION: Peut gérer les membres (inviter, modifier, supprimer)
   * SuperAdmin, Owner, Admins avec permission
   */
  const canManageMembers = computed(() => {
    if (isSuperAdmin.value) return true
    if (isOwner.value) return true
    if (!memberPermissions.value) return false
    return memberPermissions.value.can_invite_members || false
  })

  /**
   * PERMISSION: Peut supprimer des membres
   * SuperAdmin, Owner, Admins avec permission explicite
   */
  const canDeleteMembers = computed(() => {
    if (isSuperAdmin.value) return true
    if (isOwner.value) return true
    if (!memberPermissions.value) return false
    return memberPermissions.value.can_delete_members || false
  })

  /**
   * PERMISSION: Peut créer des projets
   * SuperAdmin, Owner, Admins, Members avec permission
   * Refusé pour les viewers
   */
  const canCreateProjects = computed(() => {
    if (isSuperAdmin.value) return true
    if (isOwner.value) return true
    if (isViewer.value) return false // ❌ Viewers ne peuvent jamais créer
    if (!memberPermissions.value) return false
    
    // Vérifier d'abord la permission explicite
    if (memberPermissions.value.can_create_projects) return true
    
    // Sinon vérifier les settings du workspace
    if (workspace?.value?.settings?.members_can_create_projects) {
      return ['owner', 'admin', 'member'].includes(userRole.value)
    }
    
    return false
  })

  /**
   * PERMISSION: Peut voir tous les projets
   * SuperAdmin, Owner, Admins, Members avec permission
   */
  const canViewAllProjects = computed(() => {
    if (isSuperAdmin.value) return true
    if (isOwner.value) return true
    if (!memberPermissions.value) return false
    return memberPermissions.value.can_view_all_projects || false
  })

  /**
   * PERMISSION: Peut transférer la propriété
   * SuperAdmin, Owner seulement
   */
  const canTransferOwnership = computed(() => {
    if (isSuperAdmin.value) return true
    if (isOwner.value) return true
    if (!memberPermissions.value) return false
    return memberPermissions.value.can_transfer_ownership || false
  })

  /**
   * PERMISSION: Peut gérer les paramètres du workspace
   * SuperAdmin, Owner, Admins avec permission
   */
  const canManageSettings = computed(() => {
    if (isSuperAdmin.value) return true
    if (isOwner.value) return true
    if (!memberPermissions.value) return false
    return memberPermissions.value.can_manage_settings || false
  })

  /**
   * PERMISSION: Peut archiver/restaurer le workspace
   * SuperAdmin, Owner seulement
   */
  const canArchiveWorkspace = computed(() => {
    if (isSuperAdmin.value) return true
    if (isOwner.value) return true
    return false
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
      'delete_members': canDeleteMembers.value,
      'create_projects': canCreateProjects.value,
      'view_all_projects': canViewAllProjects.value,
      'transfer_ownership': canTransferOwnership.value,
      'manage_settings': canManageSettings.value,
      'archive_workspace': canArchiveWorkspace.value
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
   * Vérifie si l'utilisateur peut effectuer une action sur un membre spécifique
   * @param {Object} targetMember - Le membre cible
   * @param {string} action - L'action ('edit', 'delete', 'view')
   * @returns {boolean}
   */
  const canPerformMemberAction = (targetMember, action) => {
    if (!targetMember) return false

    // Vérifications hiérarchiques
    if (isSuperAdmin.value) return true
    if (isOwner.value) return true

     // Owner peut tout faire sauf sur lui-même
    if (isOwner.value && targetMember.id !== currentUser.value.id) {
      return true
    }

     // Ne pas permettre de modifier le propriétaire
    // if (targetMember.pivot?.role === 'owner') {
    //   return false
    // }

    // Actions spécifiques
    switch (action) {
      case 'view':
        return canView.value
        
      case 'edit':
        return canManageMembers.value && 
               targetMember.pivot?.role !== 'owner' &&
               targetMember.id !== currentUser.value?.id
      
      case 'delete':
        return canDeleteMembers.value && 
               targetMember.pivot?.role !== 'owner' &&
               targetMember.id !== currentUser.value?.id
      
      default:
        return false
    }
  }

  /**
   * Obtient un message d'erreur approprié pour permission refusée
   * @param {string} permissionName - Nom de la permission refusée
   * @returns {string} Message d'erreur localisé
   */
  const getPermissionDeniedMessage = (permissionName) => {
    const messages = {
      'view': 'Vous n\'avez pas accès à ce workspace',
      'edit': 'Vous n\'avez pas la permission de modifier ce workspace',
      'delete': 'Vous n\'avez pas la permission de supprimer ce workspace',
      'manage_members': 'Vous n\'avez pas la permission de gérer les membres',
      'delete_members': 'Vous n\'avez pas la permission de supprimer des membres',
      'create_projects': 'Vous n\'avez pas la permission de créer des projets',
      'view_all_projects': 'Vous n\'avez pas la permission de voir tous les projets',
      'transfer_ownership': 'Vous n\'avez pas la permission de transférer la propriété',
      'manage_settings': 'Vous n\'avez pas la permission de gérer les paramètres',
      'archive_workspace': 'Vous n\'avez pas la permission d\'archiver ce workspace'
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

    if (!workspace?.value) {
      return 'Workspace non défini'
    }

    if (isViewer.value) {
      return 'Votre rôle de viewer ne permet pas cette action'
    }

    if (!isMember.value) {
      return 'Vous n\'êtes pas membre de ce workspace'
    }

    return 'Permission insuffisante pour cette action'
  }

  /**
   * Formate les permissions pour l'affichage
   * @param {Object} permissions - Les permissions à formater
   * @returns {Object} Permissions formatées avec labels
   */
  const formatPermissionsForDisplay = (permissions = {}) => {
    return {
      can_delete_members: {
        value: permissions.can_delete_members || false,
        label: 'Supprimer des membres',
        description: 'Permet de retirer des membres du workspace'
      },
      can_invite_members: {
        value: permissions.can_invite_members || false,
        label: 'Inviter des membres',
        description: 'Permet d\'inviter de nouveaux membres'
      },
      can_create_projects: {
        value: permissions.can_create_projects || false,
        label: 'Créer des projets',
        description: 'Permet de créer de nouveaux projets dans le workspace'
      },
      can_manage_settings: {
        value: permissions.can_manage_settings || false,
        label: 'Gérer les paramètres',
        description: 'Permet de modifier les paramètres du workspace'
      },
      can_view_all_projects: {
        value: permissions.can_view_all_projects || false,
        label: 'Voir tous les projets',
        description: 'Permet d\'accéder à tous les projets du workspace'
      },
      can_transfer_ownership: {
        value: permissions.can_transfer_ownership || false,
        label: 'Transférer la propriété',
        description: 'Permet de transférer la propriété du workspace'
      }
    }
  }

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
      workspace: {
        id: workspace?.value?.id,
        name: workspace?.value?.nom,
        owner_id: workspace?.value?.owner_id
      },
      roles: {
        isSuperAdmin: isSuperAdmin.value,
        isOwner: isOwner.value,
        isMember: isMember.value,
        isAdmin: isAdmin.value,
        isViewer: isViewer.value,
        isRegularMember: isRegularMember.value
      },
      permissions: {
        canView: canView.value,
        canEdit: canEdit.value,
        canDelete: canDelete.value,
        canManageMembers: canManageMembers.value,
        canDeleteMembers: canDeleteMembers.value,
        canCreateProjects: canCreateProjects.value,
        canViewAllProjects: canViewAllProjects.value,
        canTransferOwnership: canTransferOwnership.value,
        canManageSettings: canManageSettings.value,
        canArchiveWorkspace: canArchiveWorkspace.value
      },
      memberData: {
        role: memberRole.value,
        permissions: memberPermissions.value,
        formattedPermissions: formatPermissionsForDisplay(memberPermissions.value)
      }
    }
  }

  // === HELPER POUR LES NOTIFICATIONS ===
  
  const getToast = () => {
    // Nous utiliserons un store de notification ou un composant global
    return {
      success: (message) => {
        // Vous pouvez intégrer vue-toastification ou votre propre système
        console.log('Success:', message)
        // Exemple: useToast().success(message)
      },
      error: (message) => {
        console.error('Error:', message)
        // Exemple: useToast().error(message)
      },
      info: (message) => {
        console.info('Info:', message)
        // Exemple: useToast().info(message)
      }
    }
  }

  // ==================== RETOUR ====================

  return {
    // Utilisateur et états de base
    currentUser,
    userRole,
    
    // Rôles
    isSuperAdmin,
    isOwner,
    isMember,
    isAdmin,
    isViewer,
    isRegularMember,
    
    // Données du membre
    memberData,
    memberRole,
    memberPermissions,
    
    // Permissions principales
     canEditWorkspace,
    canManageSettings,
    canView,
    canEdit,
    canDelete,
    canManageMembers,
    canDeleteMembers,
    canCreateProjects,
    canViewAllProjects,
    canTransferOwnership,
    canManageSettings,
    canArchiveWorkspace,
    
    // Helpers
    hasPermission,
    hasAllPermissions,
    hasAnyPermission,
    getPermissionDeniedMessage,
    getPermissionDenialReason,
    canPerformMemberAction,
    formatPermissionsForDisplay,
    
    // Debug
    debugPermissions,

     // Notification helper
    getToast,
  }
}