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

  // Fetch operations
  const fetchActivites = async (filters = {}) => {
    try {
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

  const fetchActivite = async (id) => {
    try {
      return await activiteStore.fetchActivite(id)
    } catch (error) {
      console.error('Error fetching activite:', error)
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
    }
    return colors[status] || 'gray'
  }

  const getStatusLabel = (status) => {
    const labels = {
      active: 'Actif',
      archived: 'Archivé',
    }
    return labels[status] || status
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
    fetchActivitesForProjet,
    fetchActivite,

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
  }
}
