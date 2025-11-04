// resources/js/stores/projetStore.js

import { defineStore } from 'pinia'
import api from '@/api/axios'

export const useProjetStore = defineStore('projet', {
  state: () => ({
    projets: [],
    currentProjet: null,
    stats: {},
    filters: {
      search: '',
      status: 'all',
      visibility: 'all',
      responsable_id: null,
      tags: [],
      is_template: null,
      is_favorite: null,
      is_overdue: null,
    },
    pagination: {
      current_page: 1,
      per_page: 15,
      total: 0,
      last_page: 1,
    },
    loading: false,
    error: null,
  }),

  getters: {
    activeProjets: (state) => state.projets.filter(p => p.status === 'active'),
    archivedProjets: (state) => state.projets.filter(p => p.status === 'archived'),
    completedProjets: (state) => state.projets.filter(p => p.status === 'completed'),
    favoriteProjets: (state) => state.projets.filter(p => p.is_favorite),
    overdueProjets: (state) => state.projets.filter(p => p.is_overdue),
    
    getProjetById: (state) => (id) => {
      return state.projets.find(p => p.id === id)
    },
  },

  actions: {
    // Fetch operations
    async fetchProjets(filters = {}) {
      try {
        this.loading = true
        this.error = null
        
        const params = {
          ...this.filters,
          ...filters,
          page: this.pagination.current_page,
          per_page: this.pagination.per_page,
        }

        const response = await api.get('/api/projets', { params })
        
        this.projets = response.data.data
        
        if (response.data.meta) {
          this.pagination = {
            current_page: response.data.meta.current_page,
            per_page: response.data.meta.per_page,
            total: response.data.meta.total,
            last_page: response.data.meta.last_page,
          }
        }

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors du chargement des projets'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchMyProjets(filters = {}) {
      try {
        this.loading = true
        this.error = null
        
        const params = {
          ...this.filters,
          ...filters,
          page: this.pagination.current_page,
          per_page: this.pagination.per_page,
        }

        const response = await api.get('/api/projets/mes-projets', { params })
        
        this.projets = response.data.data
        
        if (response.data.meta) {
          this.pagination = {
            current_page: response.data.meta.current_page,
            per_page: response.data.meta.per_page,
            total: response.data.meta.total,
            last_page: response.data.meta.last_page,
          }
        }

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors du chargement de vos projets'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchDashboardStats() {
      try {
        const response = await api.get('/api/projets/dashboard-stats')
        this.stats = response.data.data
        return response.data.data
      } catch (error) {
        console.error('Error fetching dashboard stats:', error)
        throw error
      }
    },

    async fetchProjet(id) {
      try {
        this.loading = true
        this.error = null
        
        const response = await api.get(`/api/projets/${id}`)
        this.currentProjet = response.data.data
        
        // Update in list if exists
        const index = this.projets.findIndex(p => p.id === id)
        if (index !== -1) {
          this.projets[index] = response.data.data
        }

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors du chargement du projet'
        throw error
      } finally {
        this.loading = false
      }
    },

    // CRUD operations
    async createProjet(data) {
      try {
        this.loading = true
        this.error = null
        
        const response = await api.post('/api/projets', data)
        
        this.projets.unshift(response.data.data)
        this.currentProjet = response.data.data
        
        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors de la création du projet'
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateProjet(id, data) {
      try {
        this.loading = true
        this.error = null
        
        const response = await api.put(`/api/projets/${id}`, data)
        
        // Update in list
        const index = this.projets.findIndex(p => p.id === id)
        if (index !== -1) {
          this.projets[index] = response.data.data
        }
        
        // Update current if it's the same
        if (this.currentProjet?.id === id) {
          this.currentProjet = response.data.data
        }
        
        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors de la mise à jour du projet'
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteProjet(id) {
      try {
        this.loading = true
        this.error = null
        
        await api.delete(`/api/projets/${id}`)
        
        // Remove from list
        this.projets = this.projets.filter(p => p.id !== id)
        
        // Clear current if it's the same
        if (this.currentProjet?.id === id) {
          this.currentProjet = null
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors de la suppression du projet'
        throw error
      } finally {
        this.loading = false
      }
    },

    // Actions
    async archiveProjet(id) {
      try {
        const response = await api.post(`/api/projets/${id}/archive`)
        
        // Update in list
        const index = this.projets.findIndex(p => p.id === id)
        if (index !== -1) {
          this.projets[index] = response.data.data
        }
        
        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors de l\'archivage du projet'
        throw error
      }
    },

    async unarchiveProjet(id) {
      try {
        const response = await api.post(`/api/projets/${id}/unarchive`)
        
        // Update in list
        const index = this.projets.findIndex(p => p.id === id)
        if (index !== -1) {
          this.projets[index] = response.data.data
        }
        
        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors du désarchivage du projet'
        throw error
      }
    },

    async completeProjet(id) {
      try {
        const response = await api.post(`/api/projets/${id}/complete`)
        
        // Update in list
        const index = this.projets.findIndex(p => p.id === id)
        if (index !== -1) {
          this.projets[index] = response.data.data
        }
        
        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors de la finalisation du projet'
        throw error
      }
    },

    async cloneProjet(id, overrides = {}) {
      try {
        this.loading = true
        
        const response = await api.post(`/api/projets/${id}/clone`, overrides)
        
        this.projets.unshift(response.data.data)
        
        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors de la duplication du projet'
        throw error
      } finally {
        this.loading = false
      }
    },

    async toggleFavorite(id) {
      try {
        const response = await api.post(`/api/projets/${id}/toggle-favorite`)
        
        // Update in list
        const index = this.projets.findIndex(p => p.id === id)
        if (index !== -1) {
          this.projets[index] = response.data.data
        }
        
        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors de la mise à jour des favoris'
        throw error
      }
    },

    // Member management
    async addMember(projetId, memberData) {
      try {
        await api.post(`/api/projets/${projetId}/members`, memberData)
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors de l\'ajout du membre'
        throw error
      }
    },

    async updateMember(projetId, userId, permissions) {
      try {
        await api.put(`/api/projets/${projetId}/members/${userId}`, permissions)
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors de la mise à jour du membre'
        throw error
      }
    },

    async removeMember(projetId, userId) {
      try {
        await api.delete(`/api/projets/${projetId}/members/${userId}`)
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors du retrait du membre'
        throw error
      }
    },

    // Filter operations
    updateFilters(newFilters) {
      this.filters = {
        ...this.filters,
        ...newFilters,
      }
    },

    resetFilters() {
      this.filters = {
        search: '',
        status: 'all',
        visibility: 'all',
        responsable_id: null,
        tags: [],
        is_template: null,
        is_favorite: null,
        is_overdue: null,
      }
    },

    // Utility
    clearCurrentProjet() {
      this.currentProjet = null
    },

    clearError() {
      this.error = null
    },

    setPage(page) {
      this.pagination.current_page = page
    },
  },
})