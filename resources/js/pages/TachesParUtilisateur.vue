<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-6">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">
            {{ activite?.nom }}
          </h2>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ activite?.projet?.nom }} • Vue par utilisateur
          </p>
        </div>

        <button
          @click="loadData"
          :disabled="loading"
          class="p-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
        >
          <svg 
            class="w-5 h-5" 
            :class="{ 'animate-spin': loading }"
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </button>
      </div>

      <!-- Summary -->
      <div v-if="summary" class="mt-4 grid grid-cols-3 gap-4">
        <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
          <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ summary.total_users }}</p>
          <p class="text-sm text-gray-500">Utilisateurs</p>
        </div>
        <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
          <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ summary.total_tasks }}</p>
          <p class="text-sm text-gray-500">Tâches totales</p>
        </div>
        <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
          <p class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ Math.round(summary.avg_completion || 0) }}%
          </p>
          <p class="text-sm text-gray-500">Complétion moyenne</p>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-500"></div>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="rounded-xl border border-red-200 bg-red-50 dark:bg-red-900/20 p-4">
      <p class="text-red-700 dark:text-red-300">{{ error }}</p>
    </div>

    <!-- Liste par utilisateur -->
    <div v-else-if="byUser.length" class="space-y-4">
      <div
        v-for="userData in byUser"
        :key="userData.user.id"
        class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden"
      >
        <!-- Header utilisateur -->
        <div class="p-4 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-900 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <img
                :src="userData.user.avatar || '/default-avatar.png'"
                :alt="userData.user.nom"
                class="w-12 h-12 rounded-full border-2 border-white dark:border-gray-700 shadow"
              />
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">
                  {{ userData.user.nom }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ userData.user.email }}
                </p>
              </div>
            </div>

            <!-- Stats utilisateur -->
            <div class="flex items-center gap-4">
              <div class="text-center">
                <p class="text-lg font-bold text-gray-900 dark:text-white">
                  {{ userData.stats.total }}
                </p>
                <p class="text-xs text-gray-500">Tâches</p>
              </div>
              <div class="text-center">
                <p class="text-lg font-bold text-green-600">
                  {{ userData.stats.termine }}
                </p>
                <p class="text-xs text-gray-500">Terminées</p>
              </div>
              <div class="text-center">
                <p class="text-lg font-bold text-blue-600">
                  {{ userData.stats.en_cours }}
                </p>
                <p class="text-xs text-gray-500">En cours</p>
              </div>
              <div class="text-center">
                <p class="text-lg font-bold text-gray-600">
                  {{ userData.stats.a_faire }}
                </p>
                <p class="text-xs text-gray-500">À faire</p>
              </div>
            </div>
          </div>

          <!-- Barre de progression -->
          <div class="mt-3">
            <div class="flex items-center justify-between text-xs mb-1">
              <span class="text-gray-600 dark:text-gray-400">Progression personnelle</span>
              <span class="font-medium text-gray-900 dark:text-white">
                {{ Math.round(userData.stats.avg_progression) }}%
              </span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
              <div 
                class="h-full bg-gradient-to-r from-brand-500 to-brand-600 rounded-full transition-all"
                :style="{ width: userData.stats.avg_progression + '%' }"
              ></div>
            </div>
          </div>
        </div>

        <!-- Liste des tâches de l'utilisateur -->
        <div class="p-4">
          <!-- Filtres rapides -->
          <div class="flex gap-2 mb-4">
            <button
              v-for="filter in ['tous', 'a_faire', 'en_cours', 'termine']"
              :key="filter"
              @click="activeFilter[userData.user.id] = filter"
              class="px-3 py-1 text-sm rounded-lg transition-colors"
              :class="(activeFilter[userData.user.id] || 'tous') === filter
                ? 'bg-brand-500 text-white'
                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
              "
            >
              {{ getFilterLabel(filter) }}
              <span v-if="filter !== 'tous'" class="ml-1 font-medium">
                ({{ userData.stats[filter] || 0 }})
              </span>
            </button>
          </div>

          <!-- Tâches filtrées -->
          <div class="space-y-2">
            <div
              v-for="tache in getFilteredTasks(userData.tasks, activeFilter[userData.user.id] || 'tous')"
              :key="tache.id"
              class="p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:shadow-md transition-all cursor-pointer"
              @click="viewTask(tache)"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-mono text-gray-500">{{ tache.code }}</span>
                    <span 
                      class="px-2 py-0.5 text-xs rounded-full"
                      :class="getStatusClass(tache.personal_status.statut)"
                    >
                      {{ getStatusLabel(tache.personal_status.statut) }}
                    </span>
                    <span 
                      class="px-2 py-0.5 text-xs rounded-full"
                      :style="{ 
                        backgroundColor: tache.priorite_color + '20', 
                        color: tache.priorite_color 
                      }"
                    >
                      {{ tache.priorite_icon }}
                    </span>
                  </div>
                  <h4 class="font-medium text-gray-900 dark:text-white mb-1">
                    {{ tache.titre }}
                  </h4>
                  <div class="flex items-center gap-3 text-xs text-gray-500">
                    <span v-if="tache.personal_status.started_at">
                      Démarré {{ formatRelativeDate(tache.personal_status.started_at) }}
                    </span>
                    <span v-if="tache.personal_status.completed_at">
                      Terminé {{ formatRelativeDate(tache.personal_status.completed_at) }}
                    </span>
                    <span v-if="tache.personal_status.actual_hours">
                      {{ tache.personal_status.actual_hours }}h travaillées
                    </span>
                  </div>
                </div>

                <!-- Progression personnelle -->
                <div class="ml-4 text-right">
                  <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ tache.personal_status.progression }}%
                  </div>
                  <div class="text-xs text-gray-500">
                    progression
                  </div>
                </div>
              </div>

              <!-- Barre de progression -->
              <div class="mt-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                <div 
                  class="h-full rounded-full transition-all"
                  :class="tache.personal_status.statut === 'termine' 
                    ? 'bg-green-500' 
                    : tache.personal_status.statut === 'en_cours'
                    ? 'bg-blue-500'
                    : 'bg-gray-400'
                  "
                  :style="{ width: tache.personal_status.progression + '%' }"
                ></div>
              </div>
            </div>

            <!-- Empty state -->
            <div v-if="getFilteredTasks(userData.tasks, activeFilter[userData.user.id] || 'tous').length === 0" class="text-center py-8">
              <p class="text-gray-500 dark:text-gray-400">
                Aucune tâche {{ getFilterLabel(activeFilter[userData.user.id] || 'tous').toLowerCase() }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty state général -->
    <div v-else class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-12 text-center">
      <p class="text-gray-500 dark:text-gray-400">Aucune tâche assignée dans cette activité</p>
    </div>

    <!-- Detail Modal -->
    <TacheDetailModal
      v-if="showDetailModal"
      :tache="selectedTache"
      @close="showDetailModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import TacheDetailModal from '@/components/taches/TacheDetailModal.vue'
import api from '@/api/axios'

const props = defineProps({
  activiteId: {
    type: Number,
    required: true
  }
})

// State
const activite = ref(null)
const byUser = ref([])
const summary = ref(null)
const loading = ref(false)
const error = ref(null)
const activeFilter = ref({}) // { userId: 'filter' }
const showDetailModal = ref(false)
const selectedTache = ref(null)

// Computed
const avgCompletion = computed(() => {
  if (!byUser.value.length) return 0
  const total = byUser.value.reduce((sum, u) => sum + u.stats.avg_progression, 0)
  return total / byUser.value.length
})

// Methods
async function loadData() {
  loading.value = true
  error.value = null

  try {
    const { data } = await api.get(`/activites/${props.activiteId}/tasks-by-user`)
    
    activite.value = data.activite
    byUser.value = data.by_user || []
    summary.value = {
      ...data.summary,
      avg_completion: avgCompletion.value
    }
  } catch (err) {
    console.error('Erreur chargement:', err)
    error.value = err.response?.data?.message || 'Erreur lors du chargement'
  } finally {
    loading.value = false
  }
}

function getFilteredTasks(tasks, filter) {
  if (filter === 'tous') return tasks
  return tasks.filter(t => t.personal_status.statut === filter)
}

function getFilterLabel(filter) {
  const labels = {
    tous: 'Toutes',
    a_faire: 'À faire',
    en_cours: 'En cours',
    termine: 'Terminées'
  }
  return labels[filter] || filter
}

function getStatusClass(statut) {
  const classes = {
    'a_faire': 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    'en_cours': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    'termine': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
  }
  return classes[statut] || classes['a_faire']
}

function getStatusLabel(statut) {
  const labels = {
    'a_faire': 'À faire',
    'en_cours': 'En cours',
    'termine': 'Terminé'
  }
  return labels[statut] || statut
}

function formatRelativeDate(dateStr) {
  const date = new Date(dateStr)
  const now = new Date()
  const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24))

  if (diffDays === 0) return "aujourd'hui"
  if (diffDays === 1) return 'hier'
  if (diffDays < 7) return `il y a ${diffDays} jours`
  if (diffDays < 30) return `il y a ${Math.floor(diffDays / 7)} semaines`
  return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
}

function viewTask(tache) {
  selectedTache.value = tache
  showDetailModal.value = true
}

// Lifecycle
onMounted(() => {
  loadData()
})
</script>