import { ref } from 'vue'
import api from '../api/axios'

export function useTeamCalendar() {
  const events = ref([])
  const loading = ref(false)
  const error = ref(null)

  /**
   * Récupère les événements du calendrier d'équipe
   * @param {string} teamUuid - UUID de l'équipe
   * @param {object} filters - Filtres: start_date, end_date, type, user_id
   */
  const fetchEvents = async (teamUuid, filters = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(`/teams/${teamUuid}/calendar/events`, { params: filters })
      events.value = response.data.events || []
      return response.data.events || []
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la récupération des événements'
      console.error('Error fetching events:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Crée un nouvel événement dans le calendrier d'équipe
   * @param {string} teamUuid - UUID de l'équipe
   * @param {object} data - Données: title, description, start_date, end_date, type, attendees, location
   */
  const createEvent = async (teamUuid, data) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.post(`/teams/${teamUuid}/calendar/events`, data)
      events.value.unshift(response.data.event)
      return response.data.event
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la création de l\'événement'
      console.error('Error creating event:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Met à jour un événement existant
   * @param {string} teamUuid - UUID de l'équipe
   * @param {number} eventId - ID de l'événement
   * @param {object} data - Données à mettre à jour
   */
  const updateEvent = async (teamUuid, eventId, data) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.put(`/teams/${teamUuid}/calendar/events/${eventId}`, data)
      const index = events.value.findIndex(e => e.id === eventId)
      if (index !== -1) {
        events.value[index] = response.data.event
      }
      return response.data.event
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la mise à jour de l\'événement'
      console.error('Error updating event:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Supprime un événement
   * @param {string} teamUuid - UUID de l'équipe
   * @param {number} eventId - ID de l'événement
   */
  const deleteEvent = async (teamUuid, eventId) => {
    loading.value = true
    error.value = null
    try {
      await api.delete(`/teams/${teamUuid}/calendar/events/${eventId}`)
      events.value = events.value.filter(e => e.id !== eventId)
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la suppression de l\'événement'
      console.error('Error deleting event:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Met à jour le statut de participation d'un utilisateur
   * @param {string} teamUuid - UUID de l'équipe
   * @param {number} eventId - ID de l'événement
   * @param {string} status - Statut: 'accepted', 'declined', 'tentative'
   */
  const updateAttendeeStatus = async (teamUuid, eventId, status) => {
    try {
      const response = await api.post(`/teams/${teamUuid}/calendar/events/${eventId}/respond`, { status })
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la mise à jour du statut'
      console.error('Error updating attendee status:', err)
      throw err
    }
  }

  /**
   * Obtient l'icône et la couleur pour un type d'événement
   * @param {string} type - Type d'événement
   */
  const getEventTypeStyle = (type) => {
    const styles = {
      meeting: {
        icon: '👥',
        color: 'blue',
        label: 'Réunion',
        bgClass: 'bg-blue-100 dark:bg-blue-900/30',
        textClass: 'text-blue-700 dark:text-blue-400',
        borderClass: 'border-blue-500'
      },
      deadline: {
        icon: '⏰',
        color: 'red',
        label: 'Échéance',
        bgClass: 'bg-red-100 dark:bg-red-900/30',
        textClass: 'text-red-700 dark:text-red-400',
        borderClass: 'border-red-500'
      },
      milestone: {
        icon: '🎯',
        color: 'purple',
        label: 'Jalon',
        bgClass: 'bg-purple-100 dark:bg-purple-900/30',
        textClass: 'text-purple-700 dark:text-purple-400',
        borderClass: 'border-purple-500'
      },
      task: {
        icon: '✓',
        color: 'green',
        label: 'Tâche',
        bgClass: 'bg-green-100 dark:bg-green-900/30',
        textClass: 'text-green-700 dark:text-green-400',
        borderClass: 'border-green-500'
      },
      reminder: {
        icon: '🔔',
        color: 'amber',
        label: 'Rappel',
        bgClass: 'bg-amber-100 dark:bg-amber-900/30',
        textClass: 'text-amber-700 dark:text-amber-400',
        borderClass: 'border-amber-500'
      },
      event: {
        icon: '📅',
        color: 'indigo',
        label: 'Événement',
        bgClass: 'bg-indigo-100 dark:bg-indigo-900/30',
        textClass: 'text-indigo-700 dark:text-indigo-400',
        borderClass: 'border-indigo-500'
      }
    }
    return styles[type] || styles.event
  }

  /**
   * Obtient le badge de statut de participation
   * @param {string} status - Statut de participation
   */
  const getAttendeeStatusBadge = (status) => {
    const badges = {
      accepted: { color: 'green', label: 'Accepté', icon: '✓' },
      declined: { color: 'red', label: 'Refusé', icon: '✗' },
      tentative: { color: 'yellow', label: 'Peut-être', icon: '?' },
      pending: { color: 'gray', label: 'En attente', icon: '○' }
    }
    return badges[status] || badges.pending
  }

  /**
   * Formate une date pour l'affichage
   * @param {string} date - Date au format ISO
   * @param {boolean} withTime - Inclure l'heure
   */
  const formatEventDate = (date, withTime = true) => {
    if (!date) return ''
    const d = new Date(date)
    const options = {
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    }
    if (withTime) {
      options.hour = '2-digit'
      options.minute = '2-digit'
    }
    return d.toLocaleDateString('fr-FR', options)
  }

  /**
   * Calcule la durée d'un événement
   * @param {string} startDate - Date de début
   * @param {string} endDate - Date de fin
   */
  const getEventDuration = (startDate, endDate) => {
    if (!startDate || !endDate) return ''
    const start = new Date(startDate)
    const end = new Date(endDate)
    const diffMs = end - start
    const diffHours = Math.floor(diffMs / 1000 / 60 / 60)
    const diffMinutes = Math.floor((diffMs / 1000 / 60) % 60)

    if (diffHours > 0) {
      return diffMinutes > 0 ? `${diffHours}h${diffMinutes}m` : `${diffHours}h`
    }
    return `${diffMinutes}m`
  }

  /**
   * Vérifie si un événement est aujourd'hui
   * @param {string} date - Date de l'événement
   */
  const isToday = (date) => {
    const today = new Date()
    const eventDate = new Date(date)
    return (
      eventDate.getDate() === today.getDate() &&
      eventDate.getMonth() === today.getMonth() &&
      eventDate.getFullYear() === today.getFullYear()
    )
  }

  /**
   * Vérifie si un événement est passé
   * @param {string} date - Date de l'événement
   */
  const isPast = (date) => {
    return new Date(date) < new Date()
  }

  /**
   * Obtient les événements d'une date spécifique
   * @param {string} date - Date au format YYYY-MM-DD
   */
  const getEventsByDate = (date) => {
    return events.value.filter(event => {
      const eventDate = new Date(event.start_date).toISOString().split('T')[0]
      return eventDate === date
    })
  }

  return {
    events,
    loading,
    error,
    fetchEvents,
    createEvent,
    updateEvent,
    deleteEvent,
    updateAttendeeStatus,
    getEventTypeStyle,
    getAttendeeStatusBadge,
    formatEventDate,
    getEventDuration,
    isToday,
    isPast,
    getEventsByDate
  }
}
