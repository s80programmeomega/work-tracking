// resources/js/composables/useTaches.js
import { useTacheStore } from '@/stores/tacheStore'
import { storeToRefs } from 'pinia'
import api from '@/api/axios'

export function useTaches() {
  const tacheStore = useTacheStore()

  // Reactive state from store
  const {
    taches,
    kanban,
    currentTache,
    loading,
    error,
    stats,
    tachesByStatut,
    overdueTaches,
    myTaches
  } = storeToRefs(tacheStore)

  // Actions de base
  const {
    fetchTaches,
    fetchKanbanForActivite,
    fetchMyTaches,
    fetchTache,
    createTache,
    updateTache,
    deleteTache,
    moveTache,
    duplicateTache,
    archiveTache,
    unarchiveTache,
    assignUser,
    unassignUser,
    updateProgress,
    clearError
  } = tacheStore

  
  /**
   * ✅ Marquer une tâche comme terminée
   */
  const completeTache = async (tacheId) => {
    try {
      const response = await api.post(`/taches/${tacheId}/complete`)
      return response.data
    } catch (err) {
      throw err
    }
  }

  /**
   * ✅ Valider une tâche (N1)
   */
  const validateTacheN1 = async (tacheId, commentaire = null) => {
    try {
      const response = await api.post(`/taches/${tacheId}/validate-n1`, {
        commentaire
      })
      return response.data
    } catch (err) {
      throw err
    }
  }

  /**
   * ✅ Valider une tâche (N2)
   */
  const validateTacheN2 = async (tacheId, commentaire = null) => {
    try {
      const response = await api.post(`/taches/${tacheId}/validate-n2`, {
        commentaire
      })
      return response.data
    } catch (err) {
      throw err
    }
  }

  /**
   * ✅ Obtenir les tâches en attente de validation
   */
  const fetchPendingValidations = async () => {
    try {
    const response = await api.get('/taches/en-attente?with=activite.projet,assignees,labels')
      return response.data
    } catch (err) {
      throw err
    }
  }

  /**
   * ✅ Obtenir mon rapport hebdomadaire
   */
  const fetchMyWeeklyReport = async (weekNumber = null, year = null) => {
    try {
      const params = {}
      if (weekNumber) params.week_number = weekNumber
      if (year) params.year = year

      const response = await api.get('/evaluations/mon-rapport-hebdomadaire', { params })
      return response.data
    } catch (err) {
      throw err
    }
  }

  /**
   * ✅ Obtenir le rapport hebdomadaire d'un utilisateur
   */
  const fetchUserWeeklyReport = async (userId, weekNumber = null, year = null) => {
    try {
      const params = {}
      if (weekNumber) params.week_number = weekNumber
      if (year) params.year = year

      const response = await api.get(`/evaluations/rapport-hebdomadaire/${userId}`, { params })
      return response.data
    } catch (err) {
      throw err
    }
  }

  /**
   * ✅ Obtenir la performance d'équipe
   */
  const fetchTeamPerformance = async (activiteId, weekNumber = null, year = null) => {
    try {
      const params = {}
      if (weekNumber) params.week_number = weekNumber
      if (year) params.year = year

      const response = await api.get(`/evaluations/performance-equipe/${activiteId}`, { params })
      return response.data
    } catch (err) {
      throw err
    }
  }

  /**
   * ✅ Exporter le rapport hebdomadaire en PDF
   */
  const exportWeeklyReportPdf = async (userId = null, weekNumber = null, year = null) => {
    try {
      const params = {}
      if (userId) params.user_id = userId
      if (weekNumber) params.week_number = weekNumber
      if (year) params.year = year

      const response = await api.post('/evaluations/export-pdf', params, {
        responseType: 'blob'
      })

      // Créer un lien de téléchargement
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `rapport-hebdomadaire-S${weekNumber || 'actuelle'}.pdf`)
      document.body.appendChild(link)
      link.click()
      link.remove()

      return true
    } catch (err) {
      throw err
    }
  }

  /**
   * ✅ Dashboard d'évaluation
   */
  const fetchEvaluationDashboard = async () => {
    try {
      const response = await api.get('/evaluations/dashboard')
      return response.data
    } catch (err) {
      throw err
    }
  }

  /**
   * ✅ Créer une sous-tâche
   */
  const createSubTask = async (parentTacheId, data) => {
    try {
      const response = await api.post(`/taches/${parentTacheId}/sous-taches`, data)
      return response.data
    } catch (err) {
      throw err
    }
  }

  /**
   * ✅ Obtenir les sous-tâches
   */
  const fetchSubTasks = async (parentTacheId) => {
    try {
      const response = await api.get(`/taches/${parentTacheId}/sous-taches`)
      return response.data
    } catch (err) {
      throw err
    }
  }

  /**
   * Helper pour obtenir le badge de statut de validation
   */
  const getValidationBadge = (tache) => {
    if (tache.validation?.n2_validated_at) {
      return { text: 'Validé N2', color: 'purple', icon: '✓✓' }
    }
    if (tache.validation?.n1_validated_at) {
      return { text: 'Validé N1', color: 'green', icon: '✓' }
    }
    if (tache.statut === 'termine') {
      return { text: 'En attente', color: 'yellow', icon: '⏳' }
    }
    return { text: 'Non validé', color: 'gray', icon: '—' }
  }

  /**
   * Helper pour vérifier si une tâche peut être validée par l'utilisateur
   */
  const canValidate = (tache, level = 'n1') => {
    if (!tache.permissions) return false
    return level === 'n1' 
      ? tache.permissions.can_validate_n1 
      : tache.permissions.can_validate_n2
  }

  return {
    // State
    taches,
    kanban,
    currentTache,
    loading,
    error,
    stats,
   
    // Getters
    tachesByStatut,
    overdueTaches,
    myTaches,

    // Actions de base
    fetchTaches,
    fetchKanbanForActivite,
    fetchMyTaches,
    fetchTache,
    createTache,
    updateTache,
    deleteTache,
    moveTache,
    duplicateTache,
    archiveTache,
    unarchiveTache,
    assignUser,
    unassignUser,
    updateProgress,

    // ✅ Nouvelles actions
    completeTache,
    validateTacheN1,
    validateTacheN2,
    fetchPendingValidations,
    fetchMyWeeklyReport,
    fetchUserWeeklyReport,
    fetchTeamPerformance,
    exportWeeklyReportPdf,
    fetchEvaluationDashboard,
    createSubTask,
    fetchSubTasks,

    // Helpers
    getValidationBadge,
    canValidate,
    clearError
  }
}