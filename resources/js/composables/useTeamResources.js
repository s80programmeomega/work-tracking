import { ref } from 'vue'
import api from '../api/axios'

export function useTeamResources() {
  const resources = ref([])
  const loading = ref(false)
  const error = ref(null)

  const fetchResources = async (teamUuid, filters = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(`/teams/${teamUuid}/resources`, { params: filters })
      resources.value = response.data.resources || []
      return response.data.resources || []
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la récupération des ressources'
      throw err
    } finally {
      loading.value = false
    }
  }

  const createResource = async (teamUuid, data) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.post(`/teams/${teamUuid}/resources`, data)
      resources.value.unshift(response.data.resource)
      return response.data.resource
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la création de la ressource'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateResource = async (teamUuid, resourceId, data) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.put(`/teams/${teamUuid}/resources/${resourceId}`, data)
      const index = resources.value.findIndex(r => r.id === resourceId)
      if (index !== -1) {
        resources.value[index] = response.data.resource
      }
      return response.data.resource
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la mise à jour de la ressource'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteResource = async (teamUuid, resourceId) => {
    loading.value = true
    error.value = null
    try {
      await api.delete(`/teams/${teamUuid}/resources/${resourceId}`)
      resources.value = resources.value.filter(r => r.id !== resourceId)
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la suppression de la ressource'
      throw err
    } finally {
      loading.value = false
    }
  }

  const downloadResource = async (teamUuid, resourceId) => {
    try {
      const response = await api.get(`/teams/${teamUuid}/resources/${resourceId}/download`, {
        responseType: 'blob'
      })
      return response
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du téléchargement'
      throw err
    }
  }

  return {
    resources,
    loading,
    error,
    fetchResources,
    createResource,
    updateResource,
    deleteResource,
    downloadResource,
  }
}
