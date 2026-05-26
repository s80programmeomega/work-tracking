<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="'Équipes de Collaboration'" />

    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
      <!-- Header with Actions -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <!-- Search -->
          <div class="relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher une équipe..."
              class="pl-10 pr-4 py-2.5 w-80 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all"
            />
            <svg class="absolute left-3 top-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>

          <!-- Visibility Filter -->
          <select
            v-model="visibilityFilter"
            class="px-4 py-2.5 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500"
          >
            <option value="">Toutes les visibilités</option>
            <option value="public">🌍 Public</option>
            <option value="private">🔒 Privé</option>
            <option value="secret">🕵️ Secret</option>
          </select>

          <!-- Stats -->
          <div v-if="teams.length > 0" class="flex items-center gap-3 ml-4">
            <div class="px-4 py-2 bg-brand-50 dark:bg-brand-900/20 rounded-lg">
              <span class="text-sm font-semibold text-brand-600 dark:text-brand-400">{{ filteredTeams.length }} équipe(s)</span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-3">
          <button
            @click="showCreateModal = true"
            dusk="open-create-team-btn"
            class="px-5 py-2.5 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-600 hover:to-brand-700 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all transform hover:scale-105 flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Créer une équipe
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-500"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-20">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-red-100 to-red-200 dark:from-red-900/30 dark:to-red-800/30 mb-6">
          <svg class="w-10 h-10 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
          Erreur de chargement
        </h3>
        <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
          {{ error }}
        </p>
        <button
          @click="fetchMyTeams()"
          class="px-6 py-3 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-600 hover:to-brand-700 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all inline-flex items-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Réessayer
        </button>
      </div>

      <!-- Empty State - No Teams -->
      <div v-else-if="filteredTeams.length === 0 && !searchQuery" class="text-center py-20">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-brand-100 to-brand-200 dark:from-brand-900/30 dark:to-brand-800/30 mb-6">
          <svg class="w-10 h-10 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
          Aucune équipe pour le moment
        </h3>
        <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
          Créez votre première équipe pour commencer à collaborer avec vos collègues en temps réel
        </p>
        <button
          @click="showCreateModal = true"
          class="px-6 py-3 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-600 hover:to-brand-700 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all inline-flex items-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Créer ma première équipe
        </button>
      </div>

      <!-- No Search Results -->
      <div v-else-if="filteredTeams.length === 0 && searchQuery" class="text-center py-16">
        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">
          Aucun résultat trouvé
        </h3>
        <p class="text-gray-500 dark:text-gray-400">
          Essayez de modifier vos critères de recherche
        </p>
      </div>

      <!-- Teams Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <div
          v-for="team in filteredTeams"
          :key="team.uuid"
          :dusk="`team-card-${team.uuid}`"
          @click="goToTeam(team.uuid)"
          class="group relative bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 hover:border-brand-500 dark:hover:border-brand-500 hover:shadow-xl transition-all duration-300 cursor-pointer transform hover:-translate-y-1"
        >
          <!-- Visibility Badge -->
          <div class="absolute top-4 right-4">
            <span
              class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full"
              :class="{
                'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': team.visibility === 'public',
                'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400': team.visibility === 'private',
                'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': team.visibility === 'secret'
              }"
            >
              <span class="w-1.5 h-1.5 rounded-full mr-1.5"
                :class="{
                  'bg-green-500': team.visibility === 'public',
                  'bg-amber-500': team.visibility === 'private',
                  'bg-red-500': team.visibility === 'secret'
                }"></span>
              {{ getVisibilityLabel(team.visibility) }}
            </span>
          </div>

          <!-- Team Avatar & Info -->
          <div class="flex items-start gap-4 mb-4">
            <div class="flex-shrink-0">
              <div v-if="team.avatar" class="w-16 h-16 rounded-xl overflow-hidden ring-4 ring-gray-100 dark:ring-gray-700 group-hover:ring-brand-500/30 transition-all">
                <img :src="team.avatar" class="w-full h-full object-cover" alt="Team avatar" />
              </div>
              <div v-else class="w-16 h-16 rounded-xl bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center text-white text-xl font-bold ring-4 ring-gray-100 dark:ring-gray-700 group-hover:ring-brand-500/30 transition-all">
                {{ getInitials(team.name) }}
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1 truncate group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors">
                {{ team.name }}
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                {{ team.description || 'Aucune description' }}
              </p>
            </div>
          </div>

          <!-- Team Stats -->
          <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4">
              <!-- Members Count -->
              <div class="flex items-center gap-2">
                <div class="flex -space-x-2">
                  <div v-for="i in Math.min(3, team.members_count || 0)" :key="i" class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 ring-2 ring-white dark:ring-gray-800 flex items-center justify-center text-white text-xs font-bold">
                    {{ String.fromCharCode(64 + i) }}
                  </div>
                  <div v-if="(team.members_count || 0) > 3" class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-600 ring-2 ring-white dark:ring-gray-800 flex items-center justify-center text-gray-700 dark:text-gray-300 text-xs font-bold">
                    +{{ (team.members_count || 0) - 3 }}
                  </div>
                </div>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                  {{ team.members_count || 0 }} membre{{ (team.members_count || 0) > 1 ? 's' : '' }}
                </span>
              </div>
            </div>

            <!-- Arrow Icon -->
            <div class="w-8 h-8 rounded-lg bg-brand-50 dark:bg-brand-900/30 flex items-center justify-center group-hover:bg-brand-500 transition-colors">
              <svg class="w-5 h-5 text-brand-600 dark:text-brand-400 group-hover:text-white transition-colors transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </div>
          </div>

          <!-- Active Status Indicator -->
          <div v-if="team.is_active" class="absolute bottom-4 left-4">
            <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-900/30 rounded">
              <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5 animate-pulse"></span>
              Actif
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Team Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 backdrop-blur-sm" @click.self="showCreateModal = false">
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl transform transition-all">
        <!-- Modal Header -->
        <div class="px-8 py-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-900 dark:to-gray-800">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
              </div>
              <div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Créer une équipe</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Commencez à collaborer avec vos collègues</p>
              </div>
            </div>
            <button type="button" @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Modal Body -->
        <form @submit.prevent="createTeam" class="px-8 py-6">
          <div class="space-y-5">
            <!-- Team Name -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Nom de l'équipe <span class="text-red-500">*</span>
              </label>
              <input
                v-model="newTeam.name"
                type="text"
                required
                dusk="team-form-name"
                placeholder="Ex: Équipe Marketing, Développement..."
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all"
              />
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Description
              </label>
              <textarea
                v-model="newTeam.description"
                rows="3"
                placeholder="Décrivez l'objectif de cette équipe..."
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all resize-none"
              ></textarea>
            </div>

            <!-- Visibility -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                Visibilité
              </label>
              <div class="grid grid-cols-3 gap-3">
                <label
                  v-for="option in visibilityOptions"
                  :key="option.value"
                  class="relative flex flex-col items-center p-4 border-2 rounded-xl cursor-pointer transition-all"
                  :class="newTeam.visibility === option.value
                    ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20'
                    : 'border-gray-300 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-600'"
                >
                  <input
                    type="radio"
                    v-model="newTeam.visibility"
                    :value="option.value"
                    class="sr-only"
                  />
                  <span class="text-2xl mb-2">{{ option.icon }}</span>
                  <span class="text-sm font-semibold text-gray-900 dark:text-white mb-1">{{ option.label }}</span>
                  <span class="text-xs text-gray-500 dark:text-gray-400 text-center">{{ option.description }}</span>
                  <svg v-if="newTeam.visibility === option.value" class="absolute top-2 right-2 w-5 h-5 text-brand-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </label>
              </div>
            </div>
          </div>
        </form>

        <!-- Modal Footer -->
        <div class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-end gap-3">
          <p v-if="createError" class="text-sm text-red-600 dark:text-red-400 self-center mr-auto">{{ createError }}</p>
          <button
            type="button"
            @click="showCreateModal = false"
            class="px-5 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-700 dark:text-gray-300 transition-all"
          >
            Annuler
          </button>
          <button
            type="button"
            @click="createTeam"
            :disabled="creating || !newTeam.name"
            dusk="team-form-submit"
            class="px-5 py-2.5 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-600 hover:to-brand-700 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          >
            <svg v-if="creating" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ creating ? 'Création...' : 'Créer l\'équipe' }}
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useTeams } from '@/composables/useTeams'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'

const router = useRouter()
const { teams, loading, error, fetchMyTeams, createTeam: createTeamApi } = useTeams()

const searchQuery = ref('')
const visibilityFilter = ref('')
const showCreateModal = ref(false)
const creating = ref(false)
const createError = ref('')
const newTeam = ref({
  name: '',
  description: '',
  visibility: 'private'
})

const visibilityOptions = [
  {
    value: 'public',
    label: 'Public',
    icon: '🌍',
    description: 'Visible par tous'
  },
  {
    value: 'private',
    label: 'Privé',
    icon: '🔒',
    description: 'Sur invitation'
  },
  {
    value: 'secret',
    label: 'Secret',
    icon: '🕵️',
    description: 'Totalement privé'
  }
]

const filteredTeams = computed(() => {
  let filtered = teams.value || []

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(team =>
      team.name.toLowerCase().includes(query) ||
      team.description?.toLowerCase().includes(query)
    )
  }

  if (visibilityFilter.value) {
    filtered = filtered.filter(team => team.visibility === visibilityFilter.value)
  }

  return filtered
})

const getInitials = (name) => {
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .substring(0, 2)
}

const getVisibilityLabel = (visibility) => {
  const labels = {
    public: 'Public',
    private: 'Privé',
    secret: 'Secret'
  }
  return labels[visibility] || visibility
}

const goToTeam = (uuid) => {
  router.push({ name: 'teams.show', params: { uuid } })
}

const createTeam = async () => {
  if (!newTeam.value.name) return

  creating.value = true
  createError.value = ''
  try {
    const team = await createTeamApi(newTeam.value)
    showCreateModal.value = false
    createError.value = ''
    newTeam.value = { name: '', description: '', visibility: 'private' }
    goToTeam(team.uuid)
  } catch (err) {
    console.error('Error creating team:', err)
    createError.value = err.response?.data?.message || 'Erreur lors de la création de l\'équipe'
  } finally {
    creating.value = false
  }
}

onMounted(async () => {
  console.log('Teams page mounted')
  console.log('Token:', localStorage.getItem('auth_token') ? 'Present' : 'Missing')
  console.log('Loading before fetch:', loading.value)

  try {
    console.log('Fetching teams...')
    const result = await fetchMyTeams()
    console.log('Teams fetched successfully:', result)
    console.log('Loading after fetch:', loading.value)
  } catch (error) {
    console.error('Erreur lors du chargement des équipes:', error)
    console.error('Error details:', error.response?.data || error.message)
    console.log('Loading after error:', loading.value)
    // Le loader sera arrêté automatiquement par le composable
  }
})
</script>
