// Composable de messagerie d'équipe — gère CRUD, temps réel (Echo), indicateur
// de frappe (whisper), messages optimistes, réactions et lectures.
import { ref } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import api from '../api/axios'
import { useEcho } from './useEcho'

export function useTeamMessages() {
  const messages = ref([])
  const loading = ref(false)
  const error = ref(null)
  const typingUsers = ref([])   // utilisateurs en train d'écrire (auto-effacés après 3 s)

  // Minuteries de nettoyage pour l'indicateur de frappe
  const typingTimers = {}

  // ── Référence au canal Echo actif ────────────────────────────────────────────
  let teamChannel = null

  // ── Abonnement au canal privé de l'équipe ────────────────────────────────────

  // UUIDs des messages envoyés par cet utilisateur et déjà gérés de façon optimiste
  const ownSentUuids = new Set()

  const subscribeToTeam = (teamId) => {
    const echo = useEcho().echo
    if (!echo || !teamId) return

    teamChannel = echo.private(`team.${teamId}`)

    // Nouveau message envoyé par un autre membre
    teamChannel.listen('.message.sent', (event) => {
      const msg = event.message
      // Ignorer si déjà présent (optimiste ou broadcast en double)
      if (messages.value.some((m) => m.uuid === msg.uuid)) return
      // Ignorer le broadcast du propre message de l'utilisateur (déjà géré de façon optimiste)
      if (ownSentUuids.has(msg.uuid)) {
        ownSentUuids.delete(msg.uuid)
        return
      }
      messages.value.push(msg)
      // Marquer automatiquement comme lu si l'onglet est actif
      if (document.visibilityState === 'visible') {
        markRead(currentTeamUuid.value)
      }
    })

    // Message modifié
    teamChannel.listen('.message.updated', (event) => {
      const idx = messages.value.findIndex((m) => m.uuid === event.uuid)
      if (idx !== -1) {
        Object.assign(messages.value[idx], {
          content: event.content,
          is_edited: event.is_edited,
          edited_at: event.edited_at,
        })
      }
    })

    // Message supprimé
    teamChannel.listen('.message.deleted', (event) => {
      messages.value = messages.value.filter((m) => m.uuid !== event.uuid)
    })

    // Réaction ajoutée ou retirée
    teamChannel.listen('.reaction.changed', (event) => {
      const msg = messages.value.find((m) => m.uuid === event.message_uuid)
      if (!msg) return
      const reactions = msg.reactions ?? []
      const idx = reactions.findIndex((r) => r.emoji === event.emoji)
      if (event.count === 0) {
        if (idx !== -1) reactions.splice(idx, 1)
      } else if (idx !== -1) {
        reactions[idx].count = event.count
      } else {
        reactions.push({ emoji: event.emoji, count: event.count, did_react: false })
      }
      msg.reactions = [...reactions]
    })

    // Indicateur de frappe (Reverb whisper — canal client-à-client)
    teamChannel.listenForWhisper('typing', (event) => {
      const uid = event.user_id
      if (!typingUsers.value.some((u) => u.user_id === uid)) {
        typingUsers.value.push(event)
      }
      // Effacer après 3 secondes d'inactivité
      clearTimeout(typingTimers[uid])
      typingTimers[uid] = setTimeout(() => {
        typingUsers.value = typingUsers.value.filter((u) => u.user_id !== uid)
      }, 3000)
    })
  }

  const unsubscribeFromTeam = (teamId) => {
    if (teamId) useEcho().echo?.leave(`team.${teamId}`)
    teamChannel = null
    typingUsers.value = []
  }

  // UUID de l'équipe courante (stocké pour markRead automatique)
  const currentTeamUuid = ref(null)

  // ── Récupération des messages ─────────────────────────────────────────────────

  const fetchMessages = async (teamUuid, filters = {}) => {
    currentTeamUuid.value = teamUuid
    loading.value = true
    error.value = null
    try {
      const response = await api.get(`/teams/${teamUuid}/messages`, { params: filters })
      const raw = response.data
      messages.value = raw.data ?? (Array.isArray(raw.messages) ? raw.messages : [])
      return messages.value
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la récupération des messages'
      throw err
    } finally {
      loading.value = false
    }
  }

  // ── Envoi optimiste ───────────────────────────────────────────────────────────

  const sendMessage = async (teamUuid, data) => {
    const authStore = useAuthStore()

    // Ajouter immédiatement un message temporaire pour un ressenti instantané
    const tempUuid = `temp-${Date.now()}`
    const tempMessage = {
      uuid: tempUuid,
      content: data.content ?? data.get?.('content') ?? '',
      user: { id: authStore.currentUser?.id, nom: authStore.currentUser?.nom, email: authStore.currentUser?.email },
      created_at: new Date().toISOString(),
      is_pinned: false,
      is_edited: false,
      reactions: [],
      attachments: [],
      mentions: data.mentions ?? [],
      _pending: true,
    }
    messages.value.push(tempMessage)

    try {
      const response = await api.post(`/teams/${teamUuid}/messages`, data)
      const saved = response.data.data ?? response.data.message

      // Enregistrer l'UUID réel pour que le broadcast soit ignoré
      if (saved?.uuid) ownSentUuids.add(saved.uuid)

      // Remplacer l'entrée temporaire par le message persisté
      const idx = messages.value.findIndex((m) => m.uuid === tempUuid)
      if (idx !== -1) {
        messages.value.splice(idx, 1, { ...saved, _pending: false })
      } else if (!messages.value.some((m) => m.uuid === saved.uuid)) {
        messages.value.push(saved)
      }

      return saved
    } catch (err) {
      // Retirer l'entrée temporaire en cas d'erreur
      messages.value = messages.value.filter((m) => m.uuid !== tempUuid)
      error.value = err.response?.data?.message || "Erreur lors de l'envoi du message"
      throw err
    }
  }

  // ── Modification ──────────────────────────────────────────────────────────────

  const updateMessage = async (uuid, content) => {
    try {
      const response = await api.patch(`/teams/messages/${uuid}`, { content })
      const updated = response.data.data ?? response.data.message
      const idx = messages.value.findIndex((m) => m.uuid === uuid)
      if (idx !== -1) Object.assign(messages.value[idx], updated)
      return updated
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la modification'
      throw err
    }
  }

  // ── Suppression ───────────────────────────────────────────────────────────────

  const deleteMessage = async (uuid) => {
    try {
      await api.delete(`/teams/messages/${uuid}`)
      messages.value = messages.value.filter((m) => m.uuid !== uuid)
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la suppression'
      throw err
    }
  }

  // ── Épinglage ─────────────────────────────────────────────────────────────────

  const togglePin = async (uuid) => {
    try {
      const response = await api.post(`/teams/messages/${uuid}/pin`)
      const msg = messages.value.find((m) => m.uuid === uuid)
      if (msg) msg.is_pinned = response.data.is_pinned
    } catch (err) {
      error.value = err.response?.data?.message || "Erreur lors de l'épinglage"
      throw err
    }
  }

  const fetchPinned = async (teamUuid) => {
    try {
      const response = await api.get(`/teams/${teamUuid}/messages/pinned`)
      return response.data.data ?? []
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la récupération des épinglés'
      throw err
    }
  }

  // ── Réactions ─────────────────────────────────────────────────────────────────

  const addReaction = async (messageUuid, emoji) => {
    const authStore = useAuthStore()
    const msg = messages.value.find((m) => m.uuid === messageUuid)

    // Mise à jour optimiste locale
    if (msg) {
      const reactions = msg.reactions ?? []
      const existing = reactions.find((r) => r.emoji === emoji)
      if (existing) {
        existing.count++
        existing.did_react = true
      } else {
        reactions.push({ emoji, count: 1, did_react: true })
      }
      msg.reactions = [...reactions]
    }

    try {
      await api.post(`/teams/messages/${messageUuid}/reactions`, { emoji })
    } catch (err) {
      // Annuler l'optimiste en cas d'erreur
      if (msg) {
        const idx = (msg.reactions ?? []).findIndex((r) => r.emoji === emoji)
        if (idx !== -1) {
          msg.reactions[idx].count--
          msg.reactions[idx].did_react = false
          if (msg.reactions[idx].count <= 0) msg.reactions.splice(idx, 1)
        }
      }
      error.value = err.response?.data?.message || "Erreur lors de l'ajout de la réaction"
      throw err
    }
  }

  const removeReaction = async (messageUuid, emoji) => {
    const msg = messages.value.find((m) => m.uuid === messageUuid)

    // Mise à jour optimiste locale
    if (msg) {
      const idx = (msg.reactions ?? []).findIndex((r) => r.emoji === emoji)
      if (idx !== -1) {
        msg.reactions[idx].count--
        msg.reactions[idx].did_react = false
        if (msg.reactions[idx].count <= 0) msg.reactions.splice(idx, 1)
      }
    }

    try {
      await api.delete(`/teams/messages/${messageUuid}/reactions`, { data: { emoji } })
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la suppression de la réaction'
      throw err
    }
  }

  // ── Indicateur de frappe ──────────────────────────────────────────────────────

  let typingThrottle = null
  const sendTyping = (teamId) => {
    if (!teamChannel || typingThrottle) return
    typingThrottle = setTimeout(() => (typingThrottle = null), 2000)
    const authStore = useAuthStore()
    teamChannel.whisper('typing', {
      user_id: authStore.currentUser?.id,
      nom: authStore.currentUser?.nom,
    })
  }

  // ── Marquer comme lu ──────────────────────────────────────────────────────────

  const markRead = async (teamUuid) => {
    if (!teamUuid) return
    try {
      await api.post(`/teams/${teamUuid}/read`)
    } catch {
      /* silencieux — non bloquant */
    }
  }

  return {
    messages,
    loading,
    error,
    typingUsers,
    currentTeamUuid,
    fetchMessages,
    sendMessage,
    updateMessage,
    deleteMessage,
    togglePin,
    fetchPinned,
    addReaction,
    removeReaction,
    sendTyping,
    markRead,
    subscribeToTeam,
    unsubscribeFromTeam,
  }
}
