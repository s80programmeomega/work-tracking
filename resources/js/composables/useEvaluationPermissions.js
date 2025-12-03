// resources/js/composables/useEvaluationPermissions.js

import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

/**
 * 🎯 Composable pour gérer les permissions d'évaluation
 * Règles strictes :
 * - N1 : Uniquement responsable de l'activité
 * - N2 : Uniquement responsable du projet
 */
export function useEvaluationPermissions() {
  const authStore = useAuthStore()
  const currentUser = computed(() => authStore.user)

  /**
   * ✅ Peut valider N1 ?
   */
  const canValidateN1 = (resultat) => {
    if (!resultat || !currentUser.value) return false

    // Ne peut pas valider son propre résultat
    if (resultat.user?.id === currentUser.value.id) return false

    // Doit être soumis
    if (!resultat.soumis_le) return false

    // Pas déjà validé
    if (resultat.validation_n1?.valide) return false

    // STRICT : Uniquement responsable de l'activité
    return resultat.tache?.activite?.responsable_id === currentUser.value.id
  }

  /**
   * ✅ Peut valider N2 ?
   */
  const canValidateN2 = (resultat) => {
    if (!resultat || !currentUser.value) return false

    // Ne peut pas valider son propre résultat
    if (resultat.user?.id === currentUser.value.id) return false

    // N1 doit être validé
    if (!resultat.validation_n1?.valide) return false

    // Pas déjà validé
    if (resultat.validation_n2?.valide) return false

    // STRICT : Uniquement responsable du projet
    return resultat.tache?.activite?.projet?.responsable_id === currentUser.value.id
  }

  /**
   * 👁️ Peut consulter ce résultat ?
   */
  const canViewResultat = (resultat) => {
    if (!resultat || !currentUser.value) return false

    // C'est son résultat
    if (resultat.user?.id === currentUser.value.id) return true

    // Responsable de l'activité
    if (resultat.tache?.activite?.responsable_id === currentUser.value.id) return true

    // Responsable du projet
    if (resultat.tache?.activite?.projet?.responsable_id === currentUser.value.id) return true

    return false
  }

  /**
   * ❌ Peut rejeter N1 ?
   */
  const canRejectN1 = (resultat) => {
    return canValidateN1(resultat)
  }

  /**
   * ❌ Peut rejeter N2 ?
   */
  const canRejectN2 = (resultat) => {
    return canValidateN2(resultat)
  }

  /**
   * 🏷️ Obtenir le rôle de l'utilisateur par rapport au résultat
   */
  const getUserRole = (resultat) => {
    if (!resultat || !currentUser.value) return 'none'

    if (resultat.user?.id === currentUser.value.id) {
      return 'auteur'
    }

    if (resultat.tache?.activite?.responsable_id === currentUser.value.id) {
      return 'responsable_activite'
    }

    if (resultat.tache?.activite?.projet?.responsable_id === currentUser.value.id) {
      return 'responsable_projet'
    }

    return 'none'
  }

  /**
   * 🎨 Obtenir le badge de rôle
   */
  const getRoleBadge = (resultat) => {
    const role = getUserRole(resultat)

    const badges = {
      auteur: {
        label: 'Mon résultat',
        class: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
      },
      responsable_activite: {
        label: 'Responsable Activité (N1)',
        class: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300'
      },
      responsable_projet: {
        label: 'Responsable Projet (N2)',
        class: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300'
      },
      none: null
    }

    return badges[role]
  }

  /**
   * 📊 Obtenir les actions disponibles
   */
  const getAvailableActions = (resultat) => {
    return {
      canValidateN1: canValidateN1(resultat),
      canValidateN2: canValidateN2(resultat),
      canRejectN1: canRejectN1(resultat),
      canRejectN2: canRejectN2(resultat),
      canView: canViewResultat(resultat),
      role: getUserRole(resultat),
      roleBadge: getRoleBadge(resultat)
    }
  }

  /**
   * 🎯 Déterminer le niveau de validation en cours
   */
  const getCurrentValidationLevel = (resultat) => {
    if (!resultat.soumis_le) return null
    if (!resultat.validation_n1?.valide) return 'n1'
    if (resultat.validation_n1?.valide && !resultat.validation_n2?.valide) return 'n2'
    return 'completed'
  }

  /**
   * 📈 Obtenir le statut de progression
   */
  const getProgressStatus = (resultat) => {
    if (!resultat.soumis_le) {
      return {
        label: 'Non soumis',
        percentage: 0,
        color: 'gray'
      }
    }

    if (!resultat.validation_n1?.valide) {
      return {
        label: 'En attente validation N1',
        percentage: 33,
        color: 'orange'
      }
    }

    if (!resultat.validation_n2?.valide) {
      return {
        label: 'En attente validation N2',
        percentage: 66,
        color: 'blue'
      }
    }

    return {
      label: 'Entièrement validé',
      percentage: 100,
      color: 'green'
    }
  }

  return {
    // Permissions
    canValidateN1,
    canValidateN2,
    canViewResultat,
    canRejectN1,
    canRejectN2,

    // Utilitaires
    getUserRole,
    getRoleBadge,
    getAvailableActions,
    getCurrentValidationLevel,
    getProgressStatus,

    // User
    currentUser
  }
}