// resources/js/composables/useInvitationPermissions.js
import { computed } from 'vue'
import { useWorkspacePermissions } from './useWorkspacePermissions'

/**
 * Composable pour gérer les permissions d'invitation des membres
 * Valide et formate les permissions en fonction du rôle et du contexte
 */
export function useInvitationPermissions(workspace) {
  const { canManageSettings, canManageMembers, isOwner } = useWorkspacePermissions(workspace)

  // Permissions disponibles selon le rôle
  const getAvailablePermissionsForRole = (role) => {
    const basePermissions = {
      can_create_projects: {
        key: 'can_create_projects',
        label: 'Créer des projets',
        description: 'Autoriser la création de nouveaux projets dans le workspace',
        allowedRoles: ['owner', 'manager', 'cadre']
      },
      can_view_all_projects: {
        key: 'can_view_all_projects',
        label: 'Voir tous les projets',
        description: 'Autoriser la visualisation de tous les projets, même ceux auxquels le membre n\'est pas assigné',
        allowedRoles: ['owner', 'manager', 'cadre']
      },
      can_invite_members: {
        key: 'can_invite_members',
        label: 'Inviter des membres',
        description: 'Autoriser l\'invitation de nouveaux membres au workspace',
        allowedRoles: ['owner', 'manager']
      },
      can_delete_members: {
        key: 'can_delete_members',
        label: 'Supprimer des membres',
        description: 'Autoriser la suppression de membres du workspace',
        allowedRoles: ['owner', 'manager']
      },
      can_manage_settings: {
        key: 'can_manage_settings',
        label: 'Gérer les paramètres',
        description: 'Autoriser la modification des paramètres du workspace',
        allowedRoles: ['owner', 'manager']
      },
      can_transfer_ownership: {
        key: 'can_transfer_ownership',
        label: 'Transférer la propriété',
        description: 'Autoriser le transfert de la propriété du workspace à un autre membre',
        allowedRoles: ['owner']
      }
    }

    // Retourne uniquement les permissions autorisées pour le rôle
    return Object.values(basePermissions)
      .filter(permission => permission.allowedRoles.includes(role))
      .map(permission => ({
        ...permission,
        disabled: !canGrantPermission(permission.key, role),
        recommended: isPermissionRecommended(permission.key, role)
      }))
  }

  // Vérifie si l'utilisateur courant peut accorder une permission
  const canGrantPermission = (permissionKey, targetRole) => {
    // L'owner peut tout accorder
    if (isOwner.value) return true

    // Les admins ne peuvent pas accorder certaines permissions
    if (!canManageSettings.value || !canManageMembers.value) {
      const restrictedPermissions = [
        'can_transfer_ownership',
        'can_manage_settings',
        'can_delete_members'
      ]
      if (restrictedPermissions.includes(permissionKey)) {
        return false
      }
    }

    // Empêcher d'accorder plus de permissions que ce qu'on a soi-même
    const userPermissions = useWorkspacePermissions(workspace)

    // Logique de vérification basée sur les permissions de l'utilisateur
    switch (permissionKey) {
      case 'can_invite_members':
        return canManageMembers.value
      case 'can_manage_settings':
        return canManageSettings.value
      case 'can_delete_members':
        return canManageMembers.value
      case 'can_transfer_ownership':
        return isOwner.value
      default:
        return true
    }
  }

  // Détermine si une permission est recommandée pour un rôle
  const isPermissionRecommended = (permissionKey, role) => {
    const recommendations = {
      manager: ['can_create_projects', 'can_view_all_projects', 'can_invite_members'],
      cadre: ['can_create_projects'],
      collaborateur: [],
      stagiaire: [],
      observateur: []
    }
    return recommendations[role]?.includes(permissionKey) || false
  }

  // Retourne les permissions par défaut pour un rôle
  const getDefaultPermissionsForRole = (role) => {
    const defaults = {
      manager: {
        can_create_projects: true,
        can_view_all_projects: true,
        can_invite_members: true,
        can_delete_members: true,
        can_manage_settings: true,
        can_transfer_ownership: false
      },
      cadre: {
        can_create_projects: true,
        can_view_all_projects: false,
        can_invite_members: false,
        can_delete_members: false,
        can_manage_settings: false,
        can_transfer_ownership: false
      },
      collaborateur: {
        can_create_projects: false,
        can_view_all_projects: false,
        can_invite_members: false,
        can_delete_members: false,
        can_manage_settings: false,
        can_transfer_ownership: false
      },
      stagiaire: {
        can_create_projects: false,
        can_view_all_projects: false,
        can_invite_members: false,
        can_delete_members: false,
        can_manage_settings: false,
        can_transfer_ownership: false
      },
      observateur: {
        can_create_projects: false,
        can_view_all_projects: false,
        can_invite_members: false,
        can_delete_members: false,
        can_manage_settings: false,
        can_transfer_ownership: false
      }
    }
    return defaults[role] || defaults.observateur
  }

  // Valide une configuration de permissions
  const validatePermissions = (permissions, role) => {
    const errors = []

    // Vérifier les permissions incompatibles avec le rôle
    const availablePermissions = getAvailablePermissionsForRole(role)
    const availableKeys = availablePermissions.map(p => p.key)

    Object.keys(permissions).forEach(key => {
      if (permissions[key] && !availableKeys.includes(key)) {
        errors.push(`La permission "${key}" n'est pas disponible pour le rôle ${role}`)
      }
    })

    // Vérifier les conflits spécifiques
    if (role === 'observateur' && permissions.can_create_projects) {
      errors.push('Un observateur ne peut pas avoir la permission de créer des projets')
    }

    if (!isOwner.value && permissions.can_transfer_ownership) {
      errors.push('Seul le propriétaire peut accorder la permission de transfert de propriété')
    }

    return {
      isValid: errors.length === 0,
      errors
    }
  }


  /**
 * Parse member permissions from pivot data
 * @param {Object} pivot - Member pivot data
 * @returns {Object} Parsed permissions object
 */
const parseMemberPermissions = (pivot) => {
  if (!pivot || !pivot.permissions) return {}

  let permissions = pivot.permissions

  // Handle various permission formats
  if (typeof permissions === 'string') {
    try {
      // Try to parse as JSON
      permissions = JSON.parse(permissions)
    } catch {
      // If parsing fails, check for 'all' format
      if (permissions === 'all' || permissions === '["all"]') {
        return {
          can_create_projects: true,
          can_view_all_projects: true,
          can_invite_members: true,
          can_delete_members: true,
          can_manage_settings: true,
          can_transfer_ownership: true
        }
      }
      permissions = {}
    }
  }
  // Handle array format
  if (Array.isArray(permissions)) {
    if (permissions.includes('all')) {
      return {
        can_create_projects: true,
        can_view_all_projects: true,
        can_invite_members: true,
        can_delete_members: true,
        can_manage_settings: true,
        can_transfer_ownership: true
      }
    }

    // Convert array to object
    return permissions.reduce((acc, perm) => {
      acc[perm] = true
      return acc
    }, {})
  }

  // Return object format
  return {
    can_create_projects: permissions.can_create_projects ?? false,
    can_view_all_projects: permissions.can_view_all_projects ?? false,
    can_invite_members: permissions.can_invite_members ?? false,
    can_delete_members: permissions.can_delete_members ?? false,
    can_manage_settings: permissions.can_manage_settings ?? false,
    can_transfer_ownership: permissions.can_transfer_ownership ?? false
  }
}

/**
 * Format permissions for API submission
 * @param {Object} permissions - Permissions object
 * @returns {Object|string} Formatted permissions
 */
const formatPermissionsForApi = (permissions) => {
  // If all permissions are true, return 'all'
  const allTrue = Object.values(permissions).every(value => value === true)
  if (allTrue) {
    return 'all'
  }

  // Otherwise return the object
  return permissions
}

  return {
    getAvailablePermissionsForRole,
    getDefaultPermissionsForRole,
    canGrantPermission,
    isPermissionRecommended,
    validatePermissions,
    parseMemberPermissions,
    formatPermissionsForApi
  }
}