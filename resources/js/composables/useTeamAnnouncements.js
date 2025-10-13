import { ref } from 'vue'
import api from '../api/axios'

export function useTeamAnnouncements() {
  const announcements = ref([])
  const loading = ref(false)
  const error = ref(null)

  const fetchAnnouncements = async (teamUuid) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(`/teams/${teamUuid}/announcements`)
      announcements.value = response.data.announcements || []
      return response.data.announcements || []
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la récupération des annonces'
      throw err
    } finally {
      loading.value = false
    }
  }

  const createAnnouncement = async (teamUuid, data) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.post(`/teams/${teamUuid}/announcements`, data)
      announcements.value.unshift(response.data.announcement)
      return response.data.announcement
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la création de l\'annonce'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateAnnouncement = async (teamUuid, announcementId, data) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.put(`/teams/${teamUuid}/announcements/${announcementId}`, data)
      const index = announcements.value.findIndex(a => a.id === announcementId)
      if (index !== -1) {
        announcements.value[index] = response.data.announcement
      }
      return response.data.announcement
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la mise à jour de l\'annonce'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteAnnouncement = async (teamUuid, announcementId) => {
    loading.value = true
    error.value = null
    try {
      await api.delete(`/teams/${teamUuid}/announcements/${announcementId}`)
      announcements.value = announcements.value.filter(a => a.id !== announcementId)
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la suppression de l\'annonce'
      throw err
    } finally {
      loading.value = false
    }
  }

  const publishAnnouncement = async (teamUuid, announcementId) => {
    try {
      const response = await api.post(`/teams/${teamUuid}/announcements/${announcementId}/publish`)
      const index = announcements.value.findIndex(a => a.id === announcementId)
      if (index !== -1) {
        announcements.value[index] = response.data.announcement
      }
      return response.data.announcement
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la publication de l\'annonce'
      throw err
    }
  }

  const unpublishAnnouncement = async (teamUuid, announcementId) => {
    try {
      const response = await api.post(`/teams/${teamUuid}/announcements/${announcementId}/unpublish`)
      const index = announcements.value.findIndex(a => a.id === announcementId)
      if (index !== -1) {
        announcements.value[index] = response.data.announcement
      }
      return response.data.announcement
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du retrait de l\'annonce'
      throw err
    }
  }

  return {
    announcements,
    loading,
    error,
    fetchAnnouncements,
    createAnnouncement,
    updateAnnouncement,
    deleteAnnouncement,
    publishAnnouncement,
    unpublishAnnouncement,
  }
}
