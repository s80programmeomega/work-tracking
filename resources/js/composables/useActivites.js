// resources\js\composables\useActivites.js
import { computed } from 'vue'
import { useActiviteStore } from '@/stores/activiteStore'

export function useActivites() {
  const activiteStore = useActiviteStore()

  const loading = computed(() => activiteStore.loading)
  const error = computed(() => activiteStore.error)
  const activites = computed(() => activiteStore.activites)
  const currentActivite = computed(() => activiteStore.currentActivite)
  const filters = computed(() => activiteStore.filters)
  const pagination = computed(() => activiteStore.pagination)

  // Getters
  const activeActivites = computed(() => activiteStore.activeActivites)
  const archivedActivites = computed(() => activiteStore.archivedActivites)
  const overdueActivites = computed(() => activiteStore.overdueActivites)


  const fetchActiviteTaches = async (activiteId) => {
    try {
      const response = await activiteStore.fetchActiviteTaches(activiteId)
      return response.data || [] // Retourner les données des tâches
    } catch (err) {
      console.error('Error fetching activity tasks:', err)
      return []
    }
  }

  const fetchMesActivites = async (filters = {}) => {
    try {
      console.log('🔍 fetchMesActivites appelé avec:', filters)
      await activiteStore.fetchMesActivites(filters)
    } catch (error) {
      console.error('Error fetching user activites:', error)
      throw error
    }
  }

  const fetchActivite = async (id) => {
    try {
      return await activiteStore.fetchActivite(id)
    } catch (error) {
      console.error('Error fetching activite:', error)
      throw error
    }
  }
 
  const fetchActivites = async (filters = {}) => {
    try {
      console.log('🔍 fetchActivites appelé avec:', filters)
      await activiteStore.fetchActivites(filters)
    } catch (error) {
      console.error('Error fetching activites:', error)
      throw error
    }
  }

  const fetchActivitesForProjet = async (projetId, filters = {}) => {
    try {
      await activiteStore.fetchActivitiesForProjet(projetId, filters)
    } catch (error) {
      console.error('Error fetching activites:', error)
      throw error
    }
  }

  // CRUD operations
  const createActivite = async (data) => {
    try {
      return await activiteStore.createActivite(data)
    } catch (error) {
      console.error('Error creating activite:', error)
      throw error
    }
  }

  const updateActivite = async (id, data) => {
    try {
      return await activiteStore.updateActivite(id, data)
    } catch (error) {
      console.error('Error updating activite:', error)
      throw error
    }
  }

  const deleteActivite = async (id) => {
    try {
      await activiteStore.deleteActivite(id)
    } catch (error) {
      console.error('Error deleting activite:', error)
      throw error
    }
  }

  // Actions
  const archiveActivite = async (id) => {
    try {
      return await activiteStore.archiveActivite(id)
    } catch (error) {
      console.error('Error archiving activite:', error)
      throw error
    }
  }

  const unarchiveActivite = async (id) => {
    try {
      return await activiteStore.unarchiveActivite(id)
    } catch (error) {
      console.error('Error unarchiving activite:', error)
      throw error
    }
  }

  const duplicateActivite = async (id, overrides = {}) => {
    try {
      return await activiteStore.duplicateActivite(id, overrides)
    } catch (error) {
      console.error('Error duplicating activite:', error)
      throw error
    }
  }

  const reorderActivites = async (orderedIds) => {
    try {
      await activiteStore.reorderActivites(orderedIds)
    } catch (error) {
      console.error('Error reordering activites:', error)
      throw error
    }
  }

  // Filter operations
  const updateFilters = (newFilters) => {
    activiteStore.updateFilters(newFilters)
  }

  const resetFilters = () => {
    activiteStore.resetFilters()
  }

  // Utility
  const clearCurrentActivite = () => {
    activiteStore.clearCurrentActivite()
  }

  const clearError = () => {
    activiteStore.clearError()
  }

  // Status helpers
  const getStatusColor = (status) => {
    const colors = {
      active: 'blue',
      archived: 'gray',
      planifiee: 'blue',
      en_cours: 'green',
      terminee: 'purple',
      annulee: 'red',
      suspendue: 'yellow'
    }
    return colors[status] || 'gray'
  }

  const getStatusLabel = (status) => {
    const labels = {
      active: 'Actif',
      archived: 'Archivé',
      planifiee: 'Planifiée',
      en_cours: 'En cours',
      terminee: 'Terminée',
      annulee: 'Annulée',
      suspendue: 'Suspendue'
    }
    return labels[status] || status
  }

  // ✅ NOUVEAU : Helper pour les classes Tailwind CSS
  const getStatusClass = (status) => {
    const classes = {
      active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
      archived: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
      planifiee: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
      en_cours: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
      terminee: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
      annulee: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
      suspendue: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400'
    }
    return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
  }

  return {
    // State
    loading,
    error,
    activites,
    currentActivite,
    filters,
    pagination,

    // Getters
    activeActivites,
    archivedActivites,
    overdueActivites,

    // Fetch operations
    fetchActivites,
    fetchMesActivites,
    fetchActivitesForProjet,
    fetchActivite,
    fetchActiviteTaches,

    // CRUD operations
    createActivite,
    updateActivite,
    deleteActivite,

    // Actions
    archiveActivite,
    unarchiveActivite,
    duplicateActivite,
    reorderActivites,

    // Filter operations
    updateFilters,
    resetFilters,

    // Utility
    clearCurrentActivite,
    clearError,
    getStatusColor,
    getStatusLabel,
    getStatusClass, // ✅ NOUVEAU
  }
}