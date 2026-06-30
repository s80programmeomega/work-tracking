import { ref } from 'vue'
import api from '../api/axios'

export function useTeams() {
  const teams = ref([])
  const currentTeam = ref(null)
  const loading = ref(false)
  const error = ref(null)

  const fetchTeams = async (filters = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/teams', { params: filters })
      teams.value = response.data.teams
      return response.data.teams
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la récupération des équipes'
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchMyTeams = async (workspaceId = null) => {
    loading.value = true
    error.value = null
    try {
      const params = workspaceId ? { workspace_id: workspaceId } : {}
      const response = await api.get('/teams/my-teams', { params })
      teams.value = response.data.teams || []
      return response.data.teams || []
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la récupération de vos équipes'
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchTeam = async (uuid) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(`/teams/${uuid}`)
      currentTeam.value = response.data.team
      return response.data.team
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la récupération de l\'équipe'
      throw err
    } finally {
      loading.value = false
    }
  }

  const createTeam = async (data) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/teams', data)
      teams.value.unshift(response.data.team)
      return response.data.team
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la création de l\'équipe'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateTeam = async (uuid, data) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.put(`/teams/${uuid}`, data)
      const index = teams.value.findIndex(t => t.uuid === uuid)
      if (index !== -1) teams.value[index] = response.data.team
      if (currentTeam.value?.uuid === uuid) currentTeam.value = response.data.team
      return response.data.team
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la mise à jour de l\'équipe'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteTeam = async (uuid) => {
    loading.value = true
    error.value = null
    try {
      await api.delete(`/teams/${uuid}`)
      teams.value = teams.value.filter(t => t.uuid !== uuid)
      if (currentTeam.value?.uuid === uuid) currentTeam.value = null
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la suppression de l\'équipe'
      throw err
    } finally {
      loading.value = false
    }
  }

  const addMember = async (uuid, userData) => {
    try {
      const response = await api.post(`/teams/${uuid}/members`, userData)
      return response.data.member
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de l\'ajout du membre'
      throw err
    }
  }

  const removeMember = async (uuid, userId) => {
    try {
      await api.delete(`/teams/${uuid}/members/${userId}`)
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du retrait du membre'
      throw err
    }
  }

  const updateMemberRole = async (uuid, userId, role) => {
    try {
      await api.put(`/teams/${uuid}/members/${userId}/role`, { role })
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la mise à jour du rôle'
      throw err
    }
  }

  const fetchTeamStats = async (uuid) => {
    try {
      const response = await api.get(`/teams/${uuid}/stats`)
      return response.data.stats
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la récupération des statistiques'
      throw err
    }
  }

  const updatePresence = async (uuid, status) => {
    try {
      await api.post(`/teams/${uuid}/presence`, { status })
    } catch (err) {
      console.error('Error updating presence:', err)
    }
  }

  return {
    teams,
    currentTeam,
    loading,
    error,
    fetchTeams,
    fetchMyTeams,
    fetchTeam,
    createTeam,
    updateTeam,
    deleteTeam,
    addMember,
    removeMember,
    updateMemberRole,
    fetchTeamStats,
    updatePresence,
  }
}
