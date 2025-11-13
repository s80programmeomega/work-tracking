import { defineStore } from 'pinia'
import api from '@/api/axios'

export const useActiviteStore = defineStore('activite', {
  state: () => ({
    activites: [],
    currentActivite: null,
    loading: false,
    error: null,
    filters: {
      search: '',
      status: '',
      projet_id: '',
      responsable_id: '',
      sort_by: 'created_at',
      sort_order: 'desc',
    },
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
    },
  }),

  getters: {
    activeActivites: (state) => state.activites.filter((a) => a.status === 'active'),
    archivedActivites: (state) => state.activites.filter((a) => a.status === 'archived'),
    overdueActivites: (state) => state.activites.filter((a) => a.is_overdue),
  },

  actions: {
    async fetchActivites(filters = {}) {
      this.loading = true
      this.error = null

      try {
        const params = { ...this.filters, ...filters }
        const { data } = await api.get('/activites', { params })

        this.activites = data.data || []
        this.pagination = {
          current_page: data.current_page,
          last_page: data.last_page,
          per_page: data.per_page,
          total: data.total,
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors du chargement des activités'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchActivitiesForProjet(projetId, filters = {}) {
      this.loading = true
      this.error = null

      try {
        const params = { ...filters }
        const { data } = await api.get(`/projets/${projetId}/activites`, { params })

        this.activites = data.data || []
        this.pagination = {
          current_page: data.current_page,
          last_page: data.last_page,
          per_page: data.per_page,
          total: data.total,
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors du chargement des activités'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchMesActivites(filters = {}) {
      this.loading = true
      this.error = null

      try {
        const params = { ...filters }
        const { data } = await api.get('/activites/mes-activites', { params })

        this.activites = data.data || []
        this.pagination = {
          current_page: data.current_page,
          last_page: data.last_page,
          per_page: data.per_page,
          total: data.total,
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors du chargement'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchActivitesEnRetard(filters = {}) {
      this.loading = true
      this.error = null

      try {
        const params = { ...filters }
        const { data } = await api.get('/activites/en-retard', { params })

        this.activites = data.data || []
        this.pagination = {
          current_page: data.current_page,
          last_page: data.last_page,
          per_page: data.per_page,
          total: data.total,
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors du chargement'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchActivite(id) {
      this.loading = true
      this.error = null

      try {
        const { data } = await api.get(`/activites/${id}`)
        this.currentActivite = data.data
        return data
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors du chargement de l\'activité'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createActivite(formData) {
      this.loading = true
      this.error = null

      try {
        const { data } = await api.post('/activites', formData)
        this.activites.unshift(data.data)
        return data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors de la création'
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateActivite(id, formData) {
      this.loading = true
      this.error = null

      try {
        const { data } = await api.put(`/activites/${id}`, formData)
        
        const index = this.activites.findIndex((a) => a.id === id)
        if (index !== -1) {
          this.activites[index] = data.data
        }
        
        if (this.currentActivite?.id === id) {
          this.currentActivite = data.data
        }

        return data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors de la mise à jour'
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteActivite(id) {
      this.loading = true
      this.error = null

      try {
        await api.delete(`/activites/${id}`)
        this.activites = this.activites.filter((a) => a.id !== id)
        
        if (this.currentActivite?.id === id) {
          this.currentActivite = null
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors de la suppression'
        throw error
      } finally {
        this.loading = false
      }
    },

    async archiveActivite(id) {
      try {
        const { data } = await api.post(`/activites/${id}/toggle-archive`)
        
        const index = this.activites.findIndex((a) => a.id === id)
        if (index !== -1) {
          this.activites[index] = data.data
        }

        return data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors de l\'archivage'
        throw error
      }
    },

    async unarchiveActivite(id) {
      return this.archiveActivite(id) // Same endpoint toggles
    },

    async duplicateActivite(id, overrides = {}) {
      this.loading = true
      this.error = null

      try {
        const { data } = await api.post(`/activites/${id}/duplicate`, overrides)
        this.activites.unshift(data.data)
        return data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors de la duplication'
        throw error
      } finally {
        this.loading = false
      }
    },

    async reorderActivites(orderedIds) {
      try {
        await api.post('/activites/reorder', { activite_ids: orderedIds })
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors de la réorganisation'
        throw error
      }
    },

    updateFilters(newFilters) {
      this.filters = { ...this.filters, ...newFilters }
    },

    resetFilters() {
      this.filters = {
        search: '',
        status: '',
        projet_id: '',
        responsable_id: '',
        sort_by: 'created_at',
        sort_order: 'desc',
      }
    },

    clearCurrentActivite() {
      this.currentActivite = null
    },

    clearError() {
      this.error = null
    },
  },
})