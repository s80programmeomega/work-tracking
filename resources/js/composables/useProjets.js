// resources/js/composables/useProjets.js
import { ref, computed } from 'vue'
import { useToast } from '@/composables/useToast'
import api from '@/api/axios'

export function useProjets() {
  const { showSuccess, showError } = useToast()

  const loading = ref(false)
  const projets = ref([])
  const stats = ref({
    total_projets: 0,
    projets_actifs: 0,
    projets_termines: 0,
    projets_archives: 0,
    projets_en_retard: 0,
    projets_favoris: 0,
    total_activites: 0,
    total_taches: 0,
    taches_terminees: 0,
    taux_completion: 0,
    recent_activities: []
  })
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
  })
  const errors = ref({})

  // Toast notifications helper
  const showToast = (message, type = 'success') => {
    // Utilisation d'une notification simple si useToast n'est pas disponible
    const event = new CustomEvent('toast', {
      detail: { message, type }
    })
    window.dispatchEvent(event)

    // Fallback console
    if (type === 'success') {
      console.log('✅', message)
    } else if (type === 'error') {
      console.error('❌', message)
    }
  }

  // Fetch dashboard statistics
  const fetchDashboardStats = async () => {
    loading.value = true
    errors.value = {}

    try {
      const { data } = await api.get('/projets/dashboard-stats') 

      if (data?.data) {
        stats.value = { ...stats.value, ...data.data }
        return data.data
      } else if (data) {
        // Fallback si la structure est différente
        stats.value = { ...stats.value, ...data }
        return data
      }
    } catch (error) {
      console.error('Error fetching dashboard stats:', error)
      errors.value.stats = error.response?.data?.message || 'Erreur lors du chargement des statistiques'

      // Ne pas afficher d'erreur si c'est juste un workspace vide
      if (error.response?.status !== 404) {
        showToast('Impossible de charger les statistiques', 'error')
      }
    } finally {
      loading.value = false
    }
  }

  // Fetch projects list
  const fetchProjets = async (filters = {}) => {
    loading.value = true
    errors.value = {}

    try {
      const params = {
        page: filters.page || 1,
        per_page: filters.per_page || 15,
        ...filters
      }

      const { data } = await api.get('/projets', { params })

      if (data?.data) {
        // Structure Laravel Resource Collection
        projets.value = Array.isArray(data.data) ? data.data : []

        if (data.meta) {
          pagination.value = {
            current_page: data.meta.current_page || 1,
            last_page: data.meta.last_page || 1,
            per_page: data.meta.per_page || 15,
            total: data.meta.total || 0
          }
        }
      } else if (Array.isArray(data)) {
        // Fallback si c'est directement un array
        projets.value = data
      } else {
        projets.value = []
      }

      return projets.value
    } catch (error) {
      console.error('Error fetching projets:', error)
      projets.value = []
      errors.value.fetch = error.response?.data?.message || 'Erreur lors du chargement des projets'
      // Ne pas afficher d'erreur si c'est juste un workspace vide
      if (error.response?.status !== 404) {
        showToast('Impossible de charger les projets', 'error')
      }
    } finally {
      loading.value = false
    }
  }

  // Fetch single project
  const fetchProjet = async (id) => {
    loading.value = true
    errors.value = {}

    try {
      const { data } = await api.get(`/projets/${id}`) 
      return data?.data || data
    } catch (error) {
      console.error('Error fetching projet:', error)
      errors.value.fetch = error.response?.data?.message || 'Erreur lors du chargement du projet'
      showToast('Impossible de charger le projet', 'error')
      throw error
    } finally {
      loading.value = false
    }
  }

  // Create project
  const createProjet = async (projetData) => {
    loading.value = true
    errors.value = {}

    try {
      const { data } = await api.post('/projets', projetData)
      showToast('Projet créé avec succès', 'success')

      // Recharger les données après création
      await Promise.all([
        fetchProjets(),
        fetchDashboardStats()
      ])

      return data?.data || data
    } catch (error) {
      console.error('Error creating projet:', error)
      errors.value = error.response?.data?.errors || {}

      const errorMessage = error.response?.data?.message || 'Erreur lors de la création du projet'
      showToast(errorMessage, 'error')

      throw error
    } finally {
      loading.value = false
    }
  }

  // Update project
  const updateProjet = async (id, projetData) => {
    loading.value = true
    errors.value = {}

    try {
      const { data } = await api.put(`/projets/${id}`, projetData)
      showToast('Projet mis à jour avec succès', 'success')
      // Recharger les données après mise à jour
      await Promise.all([
        fetchProjets(),
        fetchDashboardStats()
      ])

      return data?.data || data
    } catch (error) {
      console.error('Error updating projet:', error)
      errors.value = error.response?.data?.errors || {}

      const errorMessage = error.response?.data?.message || 'Erreur lors de la mise à jour du projet'
      showToast(errorMessage, 'error')

      throw error
    } finally {
      loading.value = false
    }
  }

  // Delete project
  const deleteProjet = async (id) => {
    loading.value = true
    errors.value = {}

    try {
      await api.delete(`/projets/${id}`)
      showToast('Projet supprimé avec succès', 'success')

      // Refresh data
      await Promise.all([
        fetchProjets(),
        fetchDashboardStats()
      ])
    } catch (error) {
      console.error('Error deleting projet:', error)
      showToast(error.response?.data?.message || 'Erreur lors de la suppression du projet', 'error')
      throw error
    } finally {
      loading.value = false
    }
  }

  // Archive project
  const archiveProjet = async (id) => {
    loading.value = true

    try {
      const { data } = await api.post(`/projets/${id}/archive`) // Retiré /api
      showToast('Projet archivé avec succès', 'success')
      await fetchProjets()

      return data.data
    } catch (error) {
      console.error('Error archiving projet:', error)
      showToast(error.response?.data?.message || 'Erreur lors de l\'archivage du projet', 'error')
      throw error
    } finally {
      loading.value = false
    }

  }

   // Unarchive project
  const unarchiveProjet = async (id) => {
    loading.value = true

    try {
      const { data } = await api.post(`/projets/${id}/unarchive`)
      showToast('Projet désarchivé avec succès', 'success')
      
      await fetchProjets()
      return data?.data || data
    } catch (error) {
      console.error('Error unarchiving projet:', error)
      showToast(error.response?.data?.message || 'Erreur lors du désarchivage du projet', 'error')
      throw error
    } finally {
      loading.value = false
    }
  }

  // Complete project
  const completeProjet = async (id) => {
    loading.value = true

    try {
      const { data } = await api.post(`/projets/${id}/complete`)
      showToast('Projet marqué comme terminé', 'success')
      
      await fetchProjets()
      return data?.data || data
    } catch (error) {
      console.error('Error completing projet:', error)
      showToast(error.response?.data?.message || 'Erreur lors de la finalisation du projet', 'error')
      throw error
    } finally {
      loading.value = false
    }
  }

   // Clone project
  const cloneProjet = async (id, overrides = {}) => {
    loading.value = true

    try {
      const { data } = await api.post(`/projets/${id}/clone`, overrides)
      showToast('Projet cloné avec succès', 'success')
      
      await fetchProjets()
      return data?.data || data
    } catch (error) {
      console.error('Error cloning projet:', error)
      showToast(error.response?.data?.message || 'Erreur lors du clonage du projet', 'error')
      throw error
    } finally {
      loading.value = false
    }
  }

// Toggle favorite
  const toggleFavorite = async (id) => {
    try {
      const { data } = await api.post(`/projets/${id}/toggle-favorite`)
      showToast(data?.message || 'Favoris mis à jour', 'success')
      
      await fetchProjets()
      return data?.data || data
    } catch (error) {
      console.error('Error toggling favorite:', error)
      showToast(error.response?.data?.message || 'Erreur lors de la mise à jour des favoris', 'error')
      throw error
    }
  }

// Add member
  const addMember = async (projetId, memberData) => {
    loading.value = true

    try {
      await api.post(`/projets/${projetId}/members`, memberData)
      showToast('Membre ajouté avec succès', 'success')
    } catch (error) {
      console.error('Error adding member:', error)
      showToast(error.response?.data?.message || 'Erreur lors de l\'ajout du membre', 'error')
      throw error
    } finally {
      loading.value = false
    }
  }

 // Update member
  const updateMember = async (projetId, userId, permissions) => {
    loading.value = true

    try {
      await api.put(`/projets/${projetId}/members/${userId}`, permissions)
      showToast('Permissions mises à jour avec succès', 'success')
    } catch (error) {
      console.error('Error updating member:', error)
      showToast(error.response?.data?.message || 'Erreur lors de la mise à jour des permissions', 'error')
      throw error
    } finally {
      loading.value = false
    }
  }

 // Remove member
  const removeMember = async (projetId, userId) => {
    loading.value = true

    try {
      await api.delete(`/projets/${projetId}/members/${userId}`)
      showToast('Membre retiré avec succès', 'success')
    } catch (error) {
      console.error('Error removing member:', error)
      showToast(error.response?.data?.message || 'Erreur lors du retrait du membre', 'error')
      throw error
    } finally {
      loading.value = false
    }
  }

  // Computed
  const hasProjects = computed(() => projets.value.length > 0)
  const hasStats = computed(() => stats.value.total_projets > 0)

  return {
    // State
    loading,
    projets,
    stats,
    pagination,
    errors,

    // Computed
    hasProjects,
    hasStats,

    // Methods
    fetchDashboardStats,
    fetchProjets,
    fetchProjet,
    createProjet,
    updateProjet,
    deleteProjet,
    archiveProjet,
    unarchiveProjet,
    completeProjet,
    cloneProjet,
    toggleFavorite,
    addMember,
    updateMember,
    removeMember
  }
}