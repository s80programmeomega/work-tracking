import { ref, computed } from 'vue'
import { useProjetStore } from '@/stores/projetStore'
import { useRouter } from 'vue-router'

export function useProjets() {
  const projetStore = useProjetStore()
  const router = useRouter()

  const loading = computed(() => projetStore.loading)
  const error = computed(() => projetStore.error)
  const projets = computed(() => projetStore.projets)
  const currentProjet = computed(() => projetStore.currentProjet)
  const stats = computed(() => projetStore.stats)
  const filters = computed(() => projetStore.filters)
  const pagination = computed(() => projetStore.pagination)

  // Getters
  const activeProjets = computed(() => projetStore.activeProjets)
  const archivedProjets = computed(() => projetStore.archivedProjets)
  const completedProjets = computed(() => projetStore.completedProjets)
  const favoriteProjets = computed(() => projetStore.favoriteProjets)
  const overdueProjets = computed(() => projetStore.overdueProjets)

  // Fetch operations
  const fetchProjets = async (filters = {}) => {
    try {
      await projetStore.fetchProjets(filters)
    } catch (error) {
      console.error('Error fetching projets:', error)
      throw error
    }
  }

  const fetchMyProjets = async (filters = {}) => {
    try {
      await projetStore.fetchMyProjets(filters)
    } catch (error) {
      console.error('Error fetching my projets:', error)
      throw error
    }
  }

  const fetchDashboardStats = async () => {
    try {
      await projetStore.fetchDashboardStats()
    } catch (error) {
      console.error('Error fetching dashboard stats:', error)
    }
  }

  const fetchProjet = async (id) => {
    try {
      return await projetStore.fetchProjet(id)
    } catch (error) {
      console.error('Error fetching projet:', error)
      throw error
    }
  }

  // CRUD operations
  const createProjet = async (data) => {
    try {
      const projet = await projetStore.createProjet(data)
      return projet
    } catch (error) {
      console.error('Error creating projet:', error)
      throw error
    }
  }

  const updateProjet = async (id, data) => {
    try {
      const projet = await projetStore.updateProjet(id, data)
      return projet
    } catch (error) {
      console.error('Error updating projet:', error)
      throw error
    }
  }

  const deleteProjet = async (id) => {
    try {
      await projetStore.deleteProjet(id)
    } catch (error) {
      console.error('Error deleting projet:', error)
      throw error
    }
  }

  // Actions
  const archiveProjet = async (id) => {
    try {
      return await projetStore.archiveProjet(id)
    } catch (error) {
      console.error('Error archiving projet:', error)
      throw error
    }
  }

  const unarchiveProjet = async (id) => {
    try {
      return await projetStore.unarchiveProjet(id)
    } catch (error) {
      console.error('Error unarchiving projet:', error)
      throw error
    }
  }

  const completeProjet = async (id) => {
    try {
      return await projetStore.completeProjet(id)
    } catch (error) {
      console.error('Error completing projet:', error)
      throw error
    }
  }

  const cloneProjet = async (id, overrides = {}) => {
    try {
      return await projetStore.cloneProjet(id, overrides)
    } catch (error) {
      console.error('Error cloning projet:', error)
      throw error
    }
  }

  const toggleFavorite = async (id) => {
    try {
      return await projetStore.toggleFavorite(id)
    } catch (error) {
      console.error('Error toggling favorite:', error)
      throw error
    }
  }

  // Member management
  const addMember = async (projetId, memberData) => {
    try {
      await projetStore.addMember(projetId, memberData)
    } catch (error) {
      console.error('Error adding member:', error)
      throw error
    }
  }

  const updateMember = async (projetId, userId, permissions) => {
    try {
      await projetStore.updateMember(projetId, userId, permissions)
    } catch (error) {
      console.error('Error updating member:', error)
      throw error
    }
  }

  const removeMember = async (projetId, userId) => {
    try {
      await projetStore.removeMember(projetId, userId)
    } catch (error) {
      console.error('Error removing member:', error)
      throw error
    }
  }

  // Filter operations
  const updateFilters = (newFilters) => {
    projetStore.updateFilters(newFilters)
  }

  const resetFilters = () => {
    projetStore.resetFilters()
  }

  // Navigation
  const goToProjet = (id) => {
    router.push({ name: 'projets.show', params: { id } })
  }

  const goToProjetEdit = (id) => {
    router.push({ name: 'projets.edit', params: { id } })
  }

  const goToProjets = () => {
    router.push({ name: 'projets.index' })
  }

  // Utility
  const clearCurrentProjet = () => {
    projetStore.clearCurrentProjet()
  }

  const clearError = () => {
    projetStore.clearError()
  }

  // Status helpers
  const getStatusColor = (status) => {
    const colors = {
      active: 'blue',
      archived: 'gray',
      completed: 'green',
    }
    return colors[status] || 'gray'
  }

  const getStatusLabel = (status) => {
    const labels = {
      active: 'Actif',
      archived: 'Archivé',
      completed: 'Terminé',
    }
    return labels[status] || status
  }

  const getVisibilityLabel = (visibility) => {
    const labels = {
      public: 'Public',
      private: 'Privé',
      team: 'Équipe',
    }
    return labels[visibility] || visibility
  }

  const getRoleLabel = (role) => {
    const labels = {
      owner: 'Propriétaire',
      admin: 'Administrateur',
      member: 'Membre',
      viewer: 'Observateur',
    }
    return labels[role] || role
  }

  return {
    // State
    loading,
    error,
    projets,
    currentProjet,
    stats,
    filters,
    pagination,

    // Getters
    activeProjets,
    archivedProjets,
    completedProjets,
    favoriteProjets,
    overdueProjets,

    // Fetch operations
    fetchProjets,
    fetchMyProjets,
    fetchDashboardStats,
    fetchProjet,

    // CRUD operations
    createProjet,
    updateProjet,
    deleteProjet,

    // Actions
    archiveProjet,
    unarchiveProjet,
    completeProjet,
    cloneProjet,
    toggleFavorite,

    // Member management
    addMember,
    updateMember,
    removeMember,

    // Filter operations
    updateFilters,
    resetFilters,

    // Navigation
    goToProjet,
    goToProjetEdit,
    goToProjets,

    // Utility
    clearCurrentProjet,
    clearError,
    getStatusColor,
    getStatusLabel,
    getVisibilityLabel,
    getRoleLabel,
  }
}
