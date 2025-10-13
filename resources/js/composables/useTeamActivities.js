import { ref } from 'vue'
import api from '../api/axios'

export function useTeamActivities() {
  const activities = ref([])
  const loading = ref(false)
  const error = ref(null)

  const fetchActivities = async (teamUuid, limit = 50) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(`/teams/${teamUuid}/activities`, { params: { limit } })
      activities.value = response.data.activities || []
      return response.data.activities || []
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la récupération des activités'
      throw err
    } finally {
      loading.value = false
    }
  }

  const getActivityIcon = (action) => {
    const icons = {
      'team_created': '✨',
      'member_added': '👤',
      'member_removed': '👋',
      'member_role_updated': '🔄',
      'team_updated': '✏️',
      'team_archived': '📦',
      'team_restored': '🔓',
      'message_sent': '💬',
      'announcement_posted': '📢',
      'resource_uploaded': '📎',
      'resource_deleted': '🗑️',
    }
    return icons[action] || '📋'
  }

  const getActivityLabel = (action) => {
    const labels = {
      'team_created': 'a créé l\'équipe',
      'member_added': 'a ajouté un membre',
      'member_removed': 'a retiré un membre',
      'member_role_updated': 'a modifié le rôle d\'un membre',
      'team_updated': 'a mis à jour l\'équipe',
      'team_archived': 'a archivé l\'équipe',
      'team_restored': 'a restauré l\'équipe',
      'message_sent': 'a envoyé un message',
      'announcement_posted': 'a publié une annonce',
      'resource_uploaded': 'a partagé une ressource',
      'resource_deleted': 'a supprimé une ressource',
    }
    return labels[action] || 'a effectué une action'
  }

  return {
    activities,
    loading,
    error,
    fetchActivities,
    getActivityIcon,
    getActivityLabel,
  }
}
