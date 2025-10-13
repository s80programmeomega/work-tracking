import { ref, onMounted, onUnmounted } from 'vue'
import api from '../api/axios'

export function useTeamPresence() {
  const presences = ref([])
  const loading = ref(false)
  const error = ref(null)
  let intervalId = null

  /**
   * Récupère les présences des membres d'une équipe
   * @param {string} teamUuid - UUID de l'équipe
   */
  const fetchPresences = async (teamUuid) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(`/teams/${teamUuid}/presence`)
      presences.value = response.data.presences || []
      return response.data.presences || []
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la récupération des présences'
      console.error('Error fetching presences:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Met à jour le statut de présence de l'utilisateur courant
   * @param {string} teamUuid - UUID de l'équipe
   * @param {string} status - Statut: 'online', 'away', 'busy', 'offline'
   */
  const updateStatus = async (teamUuid, status) => {
    try {
      const response = await api.post(`/teams/${teamUuid}/presence/update`, { status })
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la mise à jour du statut'
      console.error('Error updating status:', err)
      throw err
    }
  }

  /**
   * Démarre le suivi automatique de présence (heartbeat toutes les 30 secondes)
   * @param {string} teamUuid - UUID de l'équipe
   * @param {number} interval - Intervalle en ms (défaut: 30000)
   */
  const startPresenceTracking = (teamUuid, interval = 30000) => {
    // Envoyer immédiatement le premier heartbeat
    updateStatus(teamUuid, 'online').catch(console.error)

    // Puis continuer périodiquement
    intervalId = setInterval(async () => {
      try {
        await updateStatus(teamUuid, 'online')
        await fetchPresences(teamUuid)
      } catch (err) {
        console.error('Presence tracking error:', err)
      }
    }, interval)
  }

  /**
   * Arrête le suivi automatique de présence
   * @param {string} teamUuid - UUID de l'équipe
   */
  const stopPresenceTracking = (teamUuid) => {
    if (intervalId) {
      clearInterval(intervalId)
      intervalId = null
    }
    // Marquer comme offline lors de la déconnexion
    updateStatus(teamUuid, 'offline').catch(console.error)
  }

  /**
   * Obtient le badge de statut avec couleur et label
   * @param {string} status - Statut de présence
   */
  const getStatusBadge = (status) => {
    const badges = {
      online: { color: 'green', label: 'En ligne', icon: '🟢' },
      away: { color: 'yellow', label: 'Absent', icon: '🟡' },
      busy: { color: 'red', label: 'Occupé', icon: '🔴' },
      offline: { color: 'gray', label: 'Hors ligne', icon: '⚪' }
    }
    return badges[status] || badges.offline
  }

  /**
   * Vérifie si un utilisateur est actuellement en ligne (basé sur last_seen)
   * @param {string} lastSeen - Date de dernière activité
   * @param {number} thresholdMinutes - Seuil en minutes (défaut: 5)
   */
  const isOnline = (lastSeen, thresholdMinutes = 5) => {
    if (!lastSeen) return false
    const lastSeenDate = new Date(lastSeen)
    const now = new Date()
    const diffMinutes = (now - lastSeenDate) / 1000 / 60
    return diffMinutes < thresholdMinutes
  }

  /**
   * Formate la date de dernière activité
   * @param {string} lastSeen - Date de dernière activité
   */
  const formatLastSeen = (lastSeen) => {
    if (!lastSeen) return 'Jamais vu'

    const date = new Date(lastSeen)
    const now = new Date()
    const diffMs = now - date
    const diffMinutes = Math.floor(diffMs / 1000 / 60)
    const diffHours = Math.floor(diffMinutes / 60)
    const diffDays = Math.floor(diffHours / 24)

    if (diffMinutes < 1) return 'À l\'instant'
    if (diffMinutes < 60) return `Il y a ${diffMinutes} min`
    if (diffHours < 24) return `Il y a ${diffHours}h`
    if (diffDays < 7) return `Il y a ${diffDays}j`

    return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
  }

  // Nettoyer l'intervalle lors du démontage du composant
  onUnmounted(() => {
    if (intervalId) {
      clearInterval(intervalId)
    }
  })

  return {
    presences,
    loading,
    error,
    fetchPresences,
    updateStatus,
    startPresenceTracking,
    stopPresenceTracking,
    getStatusBadge,
    isOnline,
    formatLastSeen
  }
}
