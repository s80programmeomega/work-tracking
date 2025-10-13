import { ref } from 'vue'
import api from '../api/axios'

export function useTeamMessages() {
  const messages = ref([])
  const loading = ref(false)
  const error = ref(null)

  const fetchMessages = async (teamUuid, filters = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(`/teams/${teamUuid}/messages`, { params: filters })
      messages.value = response.data.messages
      return response.data.messages
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la récupération des messages'
      throw err
    } finally {
      loading.value = false
    }
  }

  const sendMessage = async (teamUuid, data) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.post(`/teams/${teamUuid}/messages`, data)
      messages.value.push(response.data.message)
      return response.data.message
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de l\'envoi du message'
      throw err
    } finally {
      loading.value = false
    }
  }

  const addReaction = async (messageUuid, emoji) => {
    try {
      const response = await api.post(`/teams/messages/${messageUuid}/reactions`, { emoji })
      return response.data.reaction
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de l\'ajout de la réaction'
      throw err
    }
  }

  return {
    messages,
    loading,
    error,
    fetchMessages,
    sendMessage,
    addReaction,
  }
}
