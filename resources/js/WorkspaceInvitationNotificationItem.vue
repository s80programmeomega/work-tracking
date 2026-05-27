<template>
  <div
    class="relative flex gap-3 rounded-3 border border-brand-200 dark:border-brand-800 p-4 :class="{ 'opacity-60': !notification.data.is_pending }"
  >
    <!-- Workspace Logo/Icon -->
    <div class="flex-shrink-0">
      <div
        v-if="notification.data.workspace_logo"
        class="w-12 h-12 rounded-3 overflow-hidden border-2 border-brand-200 dark:border-brand-700"
      >
        <img :src="notification.data.workspace_logo" :alt="notification.data.workspace_name" class="w-full h-full object-cover" />
      </div>
      <div
        v-else
        class="w-12 h-12 rounded-3 bg-brand-600 dark:bg-brand-700 flex items-center justify-center border-2 border-brand-200 dark:border-brand-700"
      >
        <span class="text-white font-bold text-lg">{{ getInitials(notification.data.workspace_name) }}</span>
      </div>
    </div>

    <!-- Content -->
    <div class="flex-1 min-w-0">
      <!-- Header -->
      <div class="flex items-start justify-between gap-2 mb-2">
        <div class="flex-1">
          <div class="flex items-center gap-2 mb-1">
            <svg class="w-4 h-4 text-brand-600 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
              {{ notification.title }}
            </h4>
          </div>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
            {{ notification.message }}
          </p>
        </div>

        <!-- Status Badge -->
        <span
          v-if="!notification.data.is_pending"
          class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400"
        >
          <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
          Traité
        </span>
      </div>

      <!-- Details -->
      <div class="space-y-2 mb-3">
        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
          <span>Workspace: <strong class="text-gray-700 dark:text-gray-300">{{ notification.data.workspace_name }}</strong></span>
        </div>

        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          <span>Par: <strong class="text-gray-700 dark:text-gray-300">{{ notification.data.inviter_name }}</strong></span>
        </div>

        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
          <span>Rôle: <strong class="text-gray-700 dark:text-gray-300">{{ getRoleLabel(notification.data.role) }}</strong></span>
        </div>

        <div v-if="notification.data.invitation_message" class="mt-2 p-2 bg-white dark:bg-gray-800 rounded text-xs text-gray-600 dark:text-gray-400 italic border-l-2 border-brand-400">
          "{{ notification.data.invitation_message }}"
        </div>

        <div class="flex items-center gap-2 text-xs text-orange-600 dark:text-orange-400">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>Expire {{ formatExpirationDate(notification.data.expires_at) }}</span>
        </div>
      </div>

      <!-- Actions -->
      <div v-if="notification.data.is_pending" class="flex items-center gap-2">
        <button
          @click="acceptInvitation"
          :disabled="accepting"
          class="flex-1 px-4 py-2 bg-brand-600 text-white rounded-3 hover:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium transition-colors flex items-center justify-center gap-2"
        >
          <span v-if="accepting" class="animate-spin rounded-full h-3.5 w-3.5 border-b-2 border-white"></span>
          <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          {{ accepting ? 'Acceptation...' : 'Accepter' }}
        </button>

        <button
          @click="declineInvitation"
          :disabled="declining"
          class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-3 hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium transition-colors"
        >
          {{ declining ? 'Refus...' : 'Refuser' }}
        </button>

        <button
          @click="viewDetails"
          class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          title="Voir les détails"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </button>
      </div>

      <!-- Time -->
      <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
        {{ notification.time_ago }}
      </div>
    </div>

    <!-- Success/Error Messages -->
    <div v-if="successMessage" class="absolute inset-0 flex items-center justify-center bg-green-50/95 dark:bg-green-900/95 rounded-3">
      <div class="text-center p-4">
        <svg class="w-12 h-12 text-green-600 dark:text-green-400 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-sm font-medium text-green-900 dark:text-green-100">{{ successMessage }}</p>
      </div>
    </div>

    <div v-if="errorMessage" class="absolute inset-0 flex items-center justify-center bg-red-50/95 dark:bg-red-900/95 rounded-3">
      <div class="text-center p-4">
        <svg class="w-12 h-12 text-red-600 dark:text-red-400 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-sm font-medium text-red-900 dark:text-red-100 mb-2">{{ errorMessage }}</p>
        <button @click="errorMessage = null" class="text-xs underline text-red-700 dark:text-red-300">Fermer</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const props = defineProps({
  notification: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['updated', 'mark-read'])

const router = useRouter()
const accepting = ref(false)
const declining = ref(false)
const successMessage = ref(null)
const errorMessage = ref(null)

const getInitials = (name) => {
  return name
    ?.split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2) || 'W'
}

const getRoleLabel = (role) => {
  const labels = {
    owner: 'Propriétaire',
    admin: 'Administrateur',
    manager: 'Gestionnaire',
    member: 'Membre',
    viewer: 'Observateur'
  }
  return labels[role] || role
}

const formatExpirationDate = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = date - now
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

  if (diffDays < 0) return 'Expirée'
  if (diffDays === 0) return "aujourd'hui"
  if (diffDays === 1) return 'demain'
  if (diffDays < 7) return `dans ${diffDays} jours`
  
  return `le ${date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long' })}`
}

const acceptInvitation = async () => {
  if (accepting.value) return

  try {
    accepting.value = true
    errorMessage.value = null

    const response = await axios.post(
      `/api/workspace-invitations/${props.notification.data.invitation_token}/accept`
    )

    successMessage.value = 'Invitation acceptée avec succès !'
    
    // Marquer la notification comme lue
    emit('mark-read', props.notification.id)

    // Rediriger après 1.5 secondes
    setTimeout(() => {
      router.push(response.data.data.redirect_to)
    }, 1500)

  } catch (error) {
    console.error('Error accepting invitation:', error)
    errorMessage.value = error.response?.data?.message || 'Erreur lors de l\'acceptation'
  } finally {
    accepting.value = false
  }
}

const declineInvitation = async () => {
  if (!confirm('Êtes-vous sûr de vouloir refuser cette invitation ?')) {
    return
  }

  try {
    declining.value = true
    errorMessage.value = null

    // Appeler l'API pour refuser l'invitation
    await axios.delete(`/api/workspace-invitations/${props.notification.data.invitation_id}`)

    successMessage.value = 'Invitation refusée'
    
    // Marquer la notification comme lue
    emit('mark-read', props.notification.id)

    // Émettre l'événement de mise à jour
    setTimeout(() => {
      emit('updated')
    }, 1500)

  } catch (error) {
    console.error('Error declining invitation:', error)
    errorMessage.value = error.response?.data?.message || 'Erreur lors du refus'
  } finally {
    declining.value = false
  }
}

const viewDetails = () => {
  router.push(`/accept-invitation/${props.notification.data.invitation_token}`)
}
</script>