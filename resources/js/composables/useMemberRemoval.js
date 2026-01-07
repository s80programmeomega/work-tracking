// resources/js/composables/useMemberRemoval.js

import { ref } from 'vue'
import api from '@/api/axios'

export function useMemberRemoval() {
  const loading = ref(false)
  const error = ref(null)
  const removalPreview = ref(null)
  const userProjects = ref([])
  const transferCandidates = ref([])

  /**
   * Obtenir l'aperçu de l'impact du retrait
   */
  const getRemovalPreview = async (workspaceId, userId) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.get(
        `/workspaces/${workspaceId}/members/${userId}/removal-preview`
      )
      removalPreview.value = response.data.data
      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Obtenir les projets où l'utilisateur est responsable
   */
  const getUserProjects = async (workspaceId, userId) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.get(
        `/workspaces/${workspaceId}/members/${userId}/projects`
      )
      userProjects.value = response.data.data
      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Obtenir les candidats pour le transfert
   */
  const getTransferCandidates = async (workspaceId, excludeUserId) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.get(
        `/workspaces/${workspaceId}/transfer-candidates`,
        { params: { exclude_user_id: excludeUserId } }
      )
      transferCandidates.value = response.data.data
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Retirer un membre avec transfert
   */
  const removeMemberWithTransfer = async (workspaceId, userId, newResponsableId = null) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.delete(
        `/workspaces/${workspaceId}/members/${userId}/remove`,
        {
          data: { new_responsable_id: newResponsableId }
        }
      )
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du retrait'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Retirer un membre simple (vérifie automatiquement les responsabilités)
   */
  const removeMember = async (workspaceId, userId) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.delete(
        `/workspaces/${workspaceId}/members/${userId}`
      )
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du retrait'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Vérifier si un membre peut être retiré sans transfert
   */
  const canRemoveWithoutTransfer = (preview) => {
    if (!preview || !preview.impact) return false
    return !preview.impact.requires_transfer
  }

  /**
   * Obtenir un résumé formaté de l'impact
   */
  const getImpactSummary = (preview) => {
    if (!preview || !preview.impact) return null
    
    const impact = preview.impact
    const items = []
    
    if (impact.projets_as_responsable > 0) {
      items.push(`${impact.projets_as_responsable} projet(s) comme responsable`)
    }
    if (impact.activites_as_responsable > 0) {
      items.push(`${impact.activites_as_responsable} activité(s) comme responsable`)
    }
    if (impact.taches_non_terminees > 0) {
      items.push(`${impact.taches_non_terminees} tâche(s) non terminée(s)`)
    }
    
    return {
      items,
      requiresTransfer: impact.requires_transfer,
      totalProjects: impact.projets_as_member,
    }
  }

  return {
    // State
    loading,
    error,
    removalPreview,
    userProjects,
    transferCandidates,
    
    // Methods
    getRemovalPreview,
    getUserProjects,
    getTransferCandidates,
    removeMemberWithTransfer,
    removeMember,
    
    // Helpers
    canRemoveWithoutTransfer,
    getImpactSummary,
  }
}