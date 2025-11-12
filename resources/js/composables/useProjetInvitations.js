// resources/js/composables/useProjetInvitations.js
import { ref } from 'vue'
import api from '@/api/axios'

export function useProjetInvitations() {
  const loading = ref(false)
  const invitations = ref([])
  const error = ref(null)

  /**
   * ✅ Inviter des membres au projet
   */
  const inviteMembers = async (projetId, data) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.post(`/projets/${projetId}/invitations`, data)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de l\'invitation'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * ✅ Récupérer les invitations d'un projet
   */
  const fetchInvitations = async (projetId) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.get(`/projets/${projetId}/invitations`)
      invitations.value = response.data.data
      return invitations.value
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement des invitations'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * ✅ Vérifier une invitation
   */
  const checkInvitation = async (token) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.get(`/invitations/projet/${token}/check`)
      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Invitation invalide'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * ✅ Accepter une invitation
   */
  const acceptInvitation = async (token, userData = null) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.post(`/invitations/projet/${token}/accept`, userData)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de l\'acceptation'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * ✅ Renvoyer une invitation
   */
  const resendInvitation = async (projetId, invitationId) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.post(`/projets/${projetId}/invitations/${invitationId}/resend`)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du renvoi'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * ✅ Annuler une invitation
   */
  const cancelInvitation = async (projetId, invitationId) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.delete(`/projets/${projetId}/invitations/${invitationId}`)
      
      // Retirer de la liste locale
      invitations.value = invitations.value.filter(inv => inv.id !== invitationId)
      
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de l\'annulation'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * ✅ Retirer un membre avec transfert
   */
  const removeMemberWithTransfer = async (projetId, userId, newResponsableId = null) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.delete(`/projets/${projetId}/members/${userId}/remove`, {
        data: { new_responsable_id: newResponsableId }
      })
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du retrait'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * ✅ Obtenir les projets où l'user est responsable
   */
  const getUserProjects = async (workspaceId, userId) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.get(`/workspaces/${workspaceId}/members/${userId}/projects`)
      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * ✅ Obtenir les candidats pour le transfert
   */
  const getTransferCandidates = async (workspaceId, excludeUserId) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.get(`/workspaces/${workspaceId}/transfer-candidates`, {
        params: { exclude_user_id: excludeUserId }
      })
      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    invitations,
    error,
    inviteMembers,
    fetchInvitations,
    checkInvitation,
    acceptInvitation,
    resendInvitation,
    cancelInvitation,
    removeMemberWithTransfer,
    getUserProjects,
    getTransferCandidates
  }
}