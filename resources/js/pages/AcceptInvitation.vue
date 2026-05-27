
<!-- resources\js\pages\AcceptInvitation.vue -->
<template>
  <FullScreenLayout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center p-4">
      <!-- Loading State -->
      <div v-if="loading" class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-600 mx-auto"></div>
        <p class="mt-4 text-gray-600 dark:text-gray-400">Chargement de l'invitation...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="max-w-md w-full bg-white dark:bg-gray-800 rounded-3 p-6">
        <div class="text-center">
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30">
            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </div>
          <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Invitation invalide</h3>
          <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ error }}</p>
          <router-link
            to="/workspaces"
            class="mt-6 inline-block px-4 py-2 bg-brand-600 text-white rounded-3 hover:bg-brand-700"
          >
            Retour a l'espace de travail
          </router-link>
        </div>
      </div>

      <!-- Invitation Details -->
      <div v-else-if="invitation" class="max-w-2xl w-full bg-white dark:bg-gray-800 rounded-3 overflow-hidden">
        <!-- Header avec logo du workspace -->
        <div class="px-6 py-8 text-center">
          <div v-if="invitation.workspace.logo_url" class="mb-4">
            <img :src="invitation.workspace.logo_url" alt="Logo" class="h-20 w-20 rounded-3 mx-auto" />
          </div>
          <div v-else class="mb-4">
            <div class="h-20 w-20 rounded-3 bg-white/20 flex items-center justify-center mx-auto">
              <span class="text-3xl text-white font-bold">{{ getInitials(invitation.workspace.nom) }}</span>
            </div>
          </div>
          <h1 class="text-2xl font-bold text-white">Invitation à rejoindre</h1>
          <p class="text-xl text-white/90 mt-2">{{ invitation.workspace.nom }}</p>
        </div>

        <!-- Content -->
        <div class="px-6 py-8">
          <!-- Invitation Info -->
          <div class="space-y-4 mb-6">
            <div class="flex items-start gap-3">
              <div class="p-2 bg-brand-100 dark:bg-brand-900/30 rounded-3">
                <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Invité par</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ invitation.inviter.nom }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ invitation.inviter.email }}</p>
              </div>
            </div>

            <div class="flex items-start gap-3">
              <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-3">
                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
              </div>
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Rôle assigné</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ getRoleLabel(invitation.role) }}</p>
              </div>
            </div>

            <div v-if="invitation.message" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-3">
              <p class="text-sm text-gray-700 dark:text-gray-300">{{ invitation.message }}</p>
            </div>

            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Expire le {{ formatDate(invitation.expires_at) }}
            </div>
          </div>

          <!-- Pour utilisateur existant -->
          <div v-if="userExists">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-3 p-4 mb-6">
              <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                  <p class="text-sm font-medium text-blue-900 dark:text-blue-200">Vous avez déjà un compte</p>
                  <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                    Connectez-vous avec votre compte <strong>{{ invitation.email }}</strong> pour accepter cette invitation.
                  </p>
                </div>
              </div>
            </div>

            <div class="flex gap-3">
              <button
                @click="acceptInvitation"
                :disabled="accepting"
                class="flex-1 px-4 py-3 bg-brand-600 text-white rounded-3 hover:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                <span v-if="accepting" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></span>
                {{ accepting ? 'Acceptation...' : 'Accepter l\'invitation' }}
              </button>
              <button
                @click="declineInvitation"
                class="px-4 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700"
              >
                Refuser
              </button>
            </div>
          </div>

          <!-- Pour nouvel utilisateur -->
          <div v-else>
            <div class="mb-6">
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Créez votre compte pour rejoindre ce workspace
              </p>
            </div>

            <form @submit.prevent="registerAndAccept" class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Prénom <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.prenom"
                    type="text"
                    required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Nom <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.nom"
                    type="text"
                    required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500"
                  />
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Email
                </label>
                <input
                  :value="invitation.email"
                  type="email"
                  disabled
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-gray-400"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Mot de passe <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.password"
                  type="password"
                  required
                  minlength="8"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Confirmer le mot de passe <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.password_confirmation"
                  type="password"
                  required
                  minlength="8"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500"
                />
              </div>

              <div v-if="formError" class="rounded-3 bg-red-50 dark:bg-red-900/20 p-4">
                <p class="text-sm text-red-700 dark:text-red-400">{{ formError }}</p>
              </div>

              <button
                type="submit"
                :disabled="accepting"
                class="w-full px-4 py-3 bg-brand-600 text-white rounded-3 hover:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                <span v-if="accepting" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></span>
                {{ accepting ? 'Création du compte...' : 'Créer mon compte et rejoindre' }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </FullScreenLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import FullScreenLayout from '@/components/layout/FullScreenLayout.vue'
import api from '@/api/axios'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const loading = ref(true)
const accepting = ref(false)
const error = ref(null)
const formError = ref(null)
const invitation = ref(null)
const userExists = ref(false)

const form = ref({
  prenom: '',
  nom: '',
  password: '',
  password_confirmation: ''
})

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

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const checkInvitation = async () => {
  try {
    loading.value = true
    const token = route.params.token

    const response = await api.get(`/workspace-invitations/${token}`)
    invitation.value = response.data.data.invitation
    userExists.value = response.data.data.user_exists

  } catch (err) {
    console.error('Error checking invitation:', err)
    error.value = err.response?.data?.message || 'Invitation invalide ou expirée'
  } finally {
    loading.value = false
  }
}

const acceptInvitation = async () => {
  if (!authStore.isAuthenticated) {
    // Rediriger vers la connexion avec le token en query
    router.push({
      path: '/signin',
      query: { invitation: route.params.token }
    })
    return
  }

  try {
    accepting.value = true
    const token = route.params.token

    const response = await api.post(`/workspace-invitations/${token}/accept`)

    // Redirection vers le workspace
    router.push(response.data.data.redirect_to)

  } catch (err) {
    console.error('Error accepting invitation:', err)
    formError.value = err.response?.data?.message || 'Erreur lors de l\'acceptation'
  } finally {
    accepting.value = false
  }
}

const registerAndAccept = async () => {
  formError.value = null

  if (form.value.password !== form.value.password_confirmation) {
    formError.value = 'Les mots de passe ne correspondent pas'
    return
  }

  try {
    accepting.value = true
    const token = route.params.token

    const response = await api.post(`/workspace-invitations/${token}/accept`, form.value)

    // Sauvegarder le token et l'utilisateur
    authStore.setUser(response.data.data.user)
    authStore.token = response.data.data.token
    authStore.isAuthenticated = true
    localStorage.setItem('auth_token', response.data.data.token)
    localStorage.setItem('user', JSON.stringify(response.data.data.user))

    // Redirection vers le workspace
    router.push(response.data.data.redirect_to)

  } catch (err) {
    console.error('Error registering:', err)
    formError.value = err.response?.data?.message || 'Erreur lors de la création du compte'
  } finally {
    accepting.value = false
  }
}

const declineInvitation = () => {
  router.push('/signin')
}

onMounted(() => {
  checkInvitation()
})
</script>