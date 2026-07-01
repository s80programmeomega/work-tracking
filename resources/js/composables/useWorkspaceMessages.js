// Composable de messagerie workspace — gère CRUD, temps réel (Echo), indicateur
// de frappe (whisper), messages optimistes, réactions et lectures.
import { ref } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import api from '../api/axios'
import { useEcho } from './useEcho'

export function useWorkspaceMessages() {
  const messages = ref([])
  const loading = ref(false)
  const error = ref(null)
  const typingUsers = ref([])

  const typingTimers = {}
  let wsChannel = null
  const currentChannelType = ref(null)
  const currentWorkspaceId = ref(null)

  // ── Abonnement au canal privé du workspace ────────────────────────────────────

  const subscribeToChannel = (workspaceId, channelType) => {
    const echo = useEcho().echo
    if (!echo || !workspaceId || !channelType) return

    currentWorkspaceId.value = workspaceId
    currentChannelType.value = channelType

    wsChannel = echo.private(`workspace.${workspaceId}.${channelType}`)

    wsChannel.listen('.workspace.message.sent', (event) => {
      const msg = event.message

      // Déjà présent par UUID réel → ignorer
      if (messages.value.some((m) => m.uuid === msg.uuid)) {
        if (document.visibilityState === 'visible') markRead(workspaceId, channelType)
        return
      }

      // Message optimiste en attente du même expéditeur → remplacer plutôt que dupliquer
      // On cherche le message en attente le plus récent du même expéditeur avec le même contenu
      const arr = messages.value
      let pendingIdx = -1
      for (let i = arr.length - 1; i >= 0; i--) {
        if (arr[i]._pending && arr[i].user?.id === msg.user?.id && arr[i].content === msg.content) {
          pendingIdx = i
          break
        }
      }
      if (pendingIdx !== -1) {
        messages.value.splice(pendingIdx, 1, { ...msg, _pending: false })
      } else {
        messages.value.push(msg)
      }

      if (document.visibilityState === 'visible') markRead(workspaceId, channelType)
    })

    wsChannel.listen('.workspace.message.updated', (event) => {
      const idx = messages.value.findIndex((m) => m.uuid === event.uuid)
      if (idx !== -1) {
        Object.assign(messages.value[idx], {
          content: event.content,
          is_edited: event.is_edited,
          edited_at: event.edited_at,
        })
      }
    })

    wsChannel.listen('.workspace.message.deleted', (event) => {
      messages.value = messages.value.filter((m) => m.uuid !== event.uuid)
    })

    wsChannel.listen('.workspace.reaction.changed', (event) => {
      // Ignorer si une mise à jour optimiste est déjà en cours pour ce couple
      if (reactionInFlight.has(`${event.message_uuid}:${event.emoji}`)) return

      const msg = messages.value.find((m) => m.uuid === event.message_uuid)
      if (!msg) return

      const authStore = useAuthStore()
      const currentUserId = authStore.currentUser?.id

      // Le serveur envoie reactions = [{emoji, count}] — reconstruire depuis la source de vérité
      const serverReactions = event.reactions ?? []
      msg.reactions = serverReactions.map((r) => {
        const existing = (msg.reactions ?? []).find((x) => x.emoji === r.emoji)
        return {
          emoji: r.emoji,
          count: r.count,
          // did_react : si c'est l'emoji modifié, on le déduit de l'action + user_id
          // sinon on conserve l'état local existant
          did_react: r.emoji === event.emoji
            ? (event.action === 'added' && event.user_id === currentUserId)
            : (existing?.did_react ?? false),
        }
      })
    })

    wsChannel.listenForWhisper('typing', (event) => {
      const uid = event.user_id
      if (!typingUsers.value.some((u) => u.user_id === uid)) {
        typingUsers.value.push(event)
      }
      clearTimeout(typingTimers[uid])
      typingTimers[uid] = setTimeout(() => {
        typingUsers.value = typingUsers.value.filter((u) => u.user_id !== uid)
      }, 3000)
    })
  }

  const unsubscribeFromChannel = (workspaceId, channelType) => {
    if (workspaceId && channelType) {
      useEcho().echo?.leave(`workspace.${workspaceId}.${channelType}`)
    }
    wsChannel = null
    typingUsers.value = []
    currentChannelType.value = null
    currentWorkspaceId.value = null
  }

  // ── Récupération des messages ─────────────────────────────────────────────────

  const fetchMessages = async (workspaceId, channelType, params = {}) => {
    currentWorkspaceId.value = workspaceId
    currentChannelType.value = channelType
    loading.value = true
    error.value = null
    try {
      const response = await api.get(
        `/workspaces/${workspaceId}/chat/channels/${channelType}/messages`,
        { params },
      )
      const raw = response.data
      // Le backend pagine avec ->latest() (plus récent en premier) — inverser pour
      // afficher dans l'ordre chronologique (plus ancien en haut, plus récent en bas).
      const items = raw.data ?? (Array.isArray(raw) ? raw : [])
      messages.value = [...items].reverse()
      return messages.value
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la récupération des messages'
      throw err
    } finally {
      loading.value = false
    }
  }

  // ── Envoi optimiste ───────────────────────────────────────────────────────────

  const sendMessage = async (workspaceId, channelType, data) => {
    const authStore = useAuthStore()
    const tempUuid = `temp-${Date.now()}`
    const tempMessage = {
      uuid: tempUuid,
      content: data.content ?? '',
      user: {
        id: authStore.currentUser?.id,
        nom: authStore.currentUser?.nom,
        email: authStore.currentUser?.email,
        avatar: authStore.currentUser?.avatar ?? null,
      },
      created_at: new Date().toISOString(),
      channel_type: channelType,
      is_pinned: false,
      is_edited: false,
      reactions: [],
      // Afficher les pièces jointes dès l'envoi optimiste
      attachments: data.attachments ?? [],
      mentions: data.mentions ?? [],
      reply_to: null,
      _pending: true,
      _tempUuid: tempUuid,
    }
    messages.value.push(tempMessage)

    try {
      const response = await api.post(
        `/workspaces/${workspaceId}/chat/channels/${channelType}/messages`,
        data,
      )
      const saved = response.data.message ?? response.data

      // Le broadcast peut être arrivé avant la réponse HTTP et avoir déjà remplacé
      // le message optimiste (repéré par _tempUuid) par l'UUID réel.
      if (messages.value.some((m) => m.uuid === saved.uuid)) {
        messages.value = messages.value.filter((m) => m._tempUuid !== tempUuid)
        return saved
      }

      const idx = messages.value.findIndex((m) => m._tempUuid === tempUuid)
      if (idx !== -1) {
        messages.value.splice(idx, 1, { ...saved, _pending: false })
      } else {
        messages.value.push({ ...saved, _pending: false })
      }

      return saved
    } catch (err) {
      messages.value = messages.value.filter((m) => m._tempUuid !== tempUuid)
      error.value = err.response?.data?.message || "Erreur lors de l'envoi du message"
      throw err
    }
  }

  // ── Modification ──────────────────────────────────────────────────────────────

  const updateMessage = async (workspaceId, uuid, content) => {
    try {
      const response = await api.patch(`/workspaces/${workspaceId}/chat/messages/${uuid}`, {
        content,
      })
      const updated = response.data.message ?? response.data
      const idx = messages.value.findIndex((m) => m.uuid === uuid)
      if (idx !== -1) Object.assign(messages.value[idx], updated)
      return updated
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la modification'
      throw err
    }
  }

  // ── Suppression ───────────────────────────────────────────────────────────────

  const deleteMessage = async (workspaceId, uuid) => {
    try {
      await api.delete(`/workspaces/${workspaceId}/chat/messages/${uuid}`)
      messages.value = messages.value.filter((m) => m.uuid !== uuid)
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la suppression'
      throw err
    }
  }

  // ── Épinglage ─────────────────────────────────────────────────────────────────

  const pinMessage = async (workspaceId, uuid) => {
    try {
      await api.post(`/workspaces/${workspaceId}/chat/messages/${uuid}/pin`)
      const msg = messages.value.find((m) => m.uuid === uuid)
      if (msg) msg.is_pinned = true
    } catch (err) {
      error.value = err.response?.data?.message || "Erreur lors de l'épinglage"
      throw err
    }
  }

  const unpinMessage = async (workspaceId, uuid) => {
    try {
      await api.delete(`/workspaces/${workspaceId}/chat/messages/${uuid}/pin`)
      const msg = messages.value.find((m) => m.uuid === uuid)
      if (msg) msg.is_pinned = false
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du désépinglage'
      throw err
    }
  }

  // ── Réactions ─────────────────────────────────────────────────────────────────

  const reactionInFlight = new Set()

  const addReaction = async (workspaceId, messageUuid, emoji) => {
    const key = `${messageUuid}:${emoji}`
    if (reactionInFlight.has(key)) return
    reactionInFlight.add(key)

    const msg = messages.value.find((m) => m.uuid === messageUuid)
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
      await api.post(`/workspaces/${workspaceId}/chat/messages/${messageUuid}/reactions`, { emoji })
    } catch (err) {
      if (msg) {
        const idx = (msg.reactions ?? []).findIndex((r) => r.emoji === emoji)
        if (idx !== -1) {
          msg.reactions[idx].count--
          msg.reactions[idx].did_react = false
          if (msg.reactions[idx].count <= 0) msg.reactions.splice(idx, 1)
        }
      }
      error.value = err.response?.data?.message || "Erreur lors de l'ajout de la réaction"
    } finally {
      reactionInFlight.delete(key)
    }
  }

  const removeReaction = async (workspaceId, messageUuid, emoji) => {
    const key = `${messageUuid}:${emoji}`
    if (reactionInFlight.has(key)) return
    reactionInFlight.add(key)

    const msg = messages.value.find((m) => m.uuid === messageUuid)
    if (msg) {
      const idx = (msg.reactions ?? []).findIndex((r) => r.emoji === emoji)
      if (idx !== -1) {
        msg.reactions[idx].count--
        msg.reactions[idx].did_react = false
        if (msg.reactions[idx].count <= 0) msg.reactions.splice(idx, 1)
      }
    }

    try {
      await api.delete(`/workspaces/${workspaceId}/chat/messages/${messageUuid}/reactions`, {
        data: { emoji },
      })
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la suppression de la réaction'
    } finally {
      reactionInFlight.delete(key)
    }
  }

  // ── Indicateur de frappe ──────────────────────────────────────────────────────

  let typingThrottle = null
  const sendTyping = () => {
    if (!wsChannel || typingThrottle) return
    typingThrottle = setTimeout(() => (typingThrottle = null), 2000)
    const authStore = useAuthStore()
    wsChannel.whisper('typing', {
      user_id: authStore.currentUser?.id,
      nom: authStore.currentUser?.nom,
    })
  }

  // ── Marquer comme lu ──────────────────────────────────────────────────────────

  const markRead = async (workspaceId, channelType) => {
    if (!workspaceId || !channelType) return
    try {
      await api.post(`/workspaces/${workspaceId}/chat/channels/${channelType}/read`)
    } catch {
      /* silencieux — non bloquant */
    }
  }

  // ── Compteurs non lus ─────────────────────────────────────────────────────────

  const fetchUnreadCounts = async (workspaceId) => {
    try {
      const response = await api.get(`/workspaces/${workspaceId}/chat/unread`)
      return response.data
    } catch {
      return { responsibles: 0, global: 0, total: 0 }
    }
  }

  return {
    messages,
    loading,
    error,
    typingUsers,
    currentChannelType,
    currentWorkspaceId,
    subscribeToChannel,
    unsubscribeFromChannel,
    fetchMessages,
    sendMessage,
    updateMessage,
    deleteMessage,
    pinMessage,
    unpinMessage,
    addReaction,
    removeReaction,
    sendTyping,
    markRead,
    fetchUnreadCounts,
  }
}
