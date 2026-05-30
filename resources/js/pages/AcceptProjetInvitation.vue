<!-- resources/js/pages/AcceptProjetInvitation.vue -->
<template>
  <FullScreenLayout>
    <div class="min-h-screen flex items-center justify-center p-4">
      <!-- Loading State -->
      <div v-if="loading" class="text-center">
        <div class="relative inline-block">
          <div class="animate-spin rounded-full h-16 w-16 border-4 border-brand-200 border-t-brand-600"></div>
          <svg class="absolute inset-0 m-auto w-8 h-8 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <p class="mt-6 text-lg font-medium text-gray-700 dark:text-gray-300">Chargement de l'invitation...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="max-w-md w-full">
        <div class="bg-white dark:bg-gray-800 rounded-3 p-8 border border-red-100 dark:border-red-900">
          <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 dark:bg-red-900/30 mb-4">
              <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Invitation invalide</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">{{ error }}</p>
            <router-link
              to="/workspaces"
              class="inline-flex items-center gap-2 px-6 py-3 bg-brand-600 text-white rounded-3 hover:bg-brand-700 transition-colors font-medium"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Retour à l'espace de travail
            </router-link>
          </div>
        </div>
      </div>

      <!-- Invitation Details -->
      <div v-else-if="invitation" class="max-w-3xl w-full">
        <div class="bg-white dark:bg-gray-800 rounded-3 overflow-hidden border border-gray-100 dark:border-gray-700">
          <!-- Header avec couleur du projet -->
          <div class="relative h-32 overflow-hidden">
            <!-- Pattern décoratif -->
            <div class="absolute inset-0 opacity-10">
              <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                <pattern id="pattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                  <circle cx="20" cy="20" r="2" fill="white"/>
                </pattern>
                <rect x="0" y="0" width="100%" height="100%" fill="url(#pattern)"/>
              </svg>
            </div>
            
            <!-- Icône projet -->
            <div class="absolute -bottom-12 left-8">
              <div 
                class="w-24 h-24 rounded-3 flex items-center justify-center border-4 border-white dark:border-gray-800"
                :style="{ backgroundColor: invitation.projet.couleur || '#3B82F6' }"
              >
                <svg class="w-12 h-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
            </div>
          </div>

          <!-- Content -->
          <div class="pt-16 px-8 pb-8">
            <!-- Titre et badges -->
            <div class="mb-6">
              <div class="flex items-start justify-between mb-2">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                  Invitation au projet
                </h1>
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-brand-100 text-brand-800 dark:bg-brand-900/30 dark:text-brand-400">
                  {{ invitation.projet.code }}
                </span>
              </div>
              <p class="text-xl text-gray-600 dark:text-gray-400">{{ invitation.projet.nom }}</p>
            </div>

            <!-- Info cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
              <!-- Workspace -->
              <div class="p-4 rounded-3 border border-purple-200 dark:border-purple-800">
                <div class="flex items-center gap-3">
                  <div class="p-2 bg-purple-100 dark:bg-purple-900/40 rounded-3">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-xs text-purple-600 dark:text-purple-400 font-medium">Workspace</p>
                    <!-- <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ invitation.workspace.nom }}</p> -->
                  </div>
                </div>
              </div>

              <!-- Invité par -->
              <div class="p-4 rounded-3 border border-blue-200 dark:border-blue-800">
                <div class="flex items-center gap-3">
                  <div class="p-2 bg-blue-100 dark:bg-blue-900/40 rounded-3">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">Invité par</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ invitation.inviter.nom }}</p>
                  </div>
                </div>
              </div>

              <!-- Rôle -->
              <div class="p-4 rounded-3 border border-green-200 dark:border-green-800">
                <div class="flex items-center gap-3">
                  <div class="p-2 bg-green-100 dark:bg-green-900/40 rounded-3">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-xs text-green-600 dark:text-green-400 font-medium">Rôle</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ getRoleLabel(invitation.role) }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Description du projet -->
            <div v-if="invitation.projet.description" class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-3 border border-gray-200 dark:border-gray-600">
              <p class="text-sm text-gray-700 dark:text-gray-300">{{ invitation.projet.description }}</p>
            </div>

            <!-- Message personnel -->
            <div v-if="invitation.message" class="mb-6 p-4 rounded-3 border border-amber-200 dark:border-amber-800">
              <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                </svg>
                <div>
                  <p class="text-xs font-semibold text-amber-600 dark:text-amber-400 mb-1">Message personnel</p>
                  <p class="text-sm text-gray-700 dark:text-gray-300 italic">"{{ invitation.message }}"</p>
                </div>
              </div>
            </div>

            <!-- Expiration -->
            <div class="flex items-center justify-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-8 pb-8 border-b border-gray-200 dark:border-gray-700">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>Cette invitation expire le {{ formatDate(invitation.expires_at) }}</span>
            </div>

            <!-- Actions section -->
            <div v-if="userExists">
              <!-- Utilisateur existant -->
              <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-3 p-6 mb-6">
                <div class="flex items-start gap-4">
                  <div class="p-3 bg-blue-100 dark:bg-blue-900/40 rounded-3">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <div class="flex-1">
                    <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-200 mb-2">Vous avez déjà un compte</h3>
                    <p class="text-sm text-blue-700 dark:text-blue-300">
                      Connectez-vous avec <strong>{{ invitation.email }}</strong> pour accepter cette invitation.
                    </p>
                  </div>
                </div>
              </div>

              <div class="flex gap-4">
                <button
                  @click="acceptInvitation"
                  :disabled="accepting"
                  class="flex-1 px-6 py-4 text-white rounded-3 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3 font-semibold transition-all"
                >
                  <span v-if="accepting" class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent"></span>
                  <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  {{ accepting ? 'Acceptation...' : 'Accepter l\'invitation' }}
                </button>
                <button
                  @click="declineInvitation"
                  class="px-6 py-4 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all font-semibold"
                >
                  Refuser
                </button>
              </div>
            </div>

            <!-- Nouvel utilisateur -->
            <div v-else>
              <div class="mb-6 text-center">
                <p class="text-gray-600 dark:text-gray-400">
                  Créez votre compte pour rejoindre ce projet
                </p>
              </div>

              <form @submit.prevent="registerAndAccept" class="space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                      Prénom <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model="form.prenom"
                      type="text"
                      required
                      class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                      placeholder="Jean"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                      Nom <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model="form.nom"
                      type="text"
                      required
                      class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                      placeholder="Dupont"
                    />
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Email
                  </label>
                  <input
                    :value="invitation.email"
                    type="email"
                    disabled
                    class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-3 bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed"
                  />
                </div>

                <div>
                  <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Mot de passe <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.password"
                    type="password"
                    required
                    minlength="8"
                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                    placeholder="••••••••"
                  />
                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Minimum 8 caractères</p>
                </div>

                <div>
                  <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Confirmer le mot de passe <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.password_confirmation"
                    type="password"
                    required
                    minlength="8"
                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                    placeholder="••••••••"
                  />
                </div>

                <div v-if="formError" class="rounded-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
                  <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm text-red-700 dark:text-red-400">{{ formError }}</p>
                  </div>
                </div>

                <button
                  type="submit"
                  :disabled="accepting"
                  class="w-full px-6 py-4 text-white rounded-3 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3 font-semibold transition-all"
                >
                  <span v-if="accepting" class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent"></span>
                  <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                  </svg>
                  {{ accepting ? 'Création du compte...' : 'Créer mon compte et rejoindre' }}
                </button>
              </form>
            </div>
          </div>
        </div>

        <!-- Footer info -->
        <div class="mt-6 text-center">
          <p class="text-sm text-gray-600 dark:text-gray-400">
            En acceptant cette invitation, vous acceptez les 
            <a href="#" class="text-brand-600 dark:text-brand-400 hover:underline">conditions d'utilisation</a>
          </p>
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

const getRoleLabel = (role) => {
  const labels = {
    admin: 'Administrateur',
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

    const response = await api.get(`/invitations/projet/${token}/check`)
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
    router.push({
      path: '/signin',
      query: { invitation: route.params.token, type: 'projet' }
    })
    return
  }

  try {
    accepting.value = true
    const token = route.params.token

    const response = await api.post(`/invitations/projet/${token}/accept`)

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

    const response = await api.post(`/invitations/projet/${token}/accept`, form.value)

    authStore.setUser(response.data.data.user)
    authStore.token = response.data.data.token
    authStore.isAuthenticated = true
    localStorage.setItem('auth_token', response.data.data.token)
    localStorage.setItem('user', JSON.stringify(response.data.data.user))

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

<style scoped>
@keyframes float {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

.animate-float {
  animation: float 3s ease-in-out infinite;
}
</style>