<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="rounded-3 border border-gray-200 dark:border-gray-800 p-6 ">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-3 flex items-center justify-center ">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                En Attente de Mes Collègues
              </h1>
              <p class="text-gray-500 dark:text-gray-400">
                Tâches où vous avez terminé votre partie
              </p>
            </div>
          </div>

          <button
            @click="loadTaches"
            :disabled="loading"
            class="p-2 rounded-3 border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            title="Actualiser"
          >
            <svg 
              class="w-5 h-5 text-gray-600 dark:text-gray-400" 
              :class="{ 'animate-spin': loading }"
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </button>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4">
          <div class="bg-white dark:bg-gray-800 rounded-3 p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total en attente</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
          </div>
          <div class="bg-white dark:bg-gray-800 rounded-3 p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">Collègues en cours</p>
            <p class="text-2xl font-bold text-blue-600">{{ stats.colleagues_working }}</p>
          </div>
          <div class="bg-white dark:bg-gray-800 rounded-3 p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">Non démarrées</p>
            <p class="text-2xl font-bold text-gray-600 dark:text-gray-300">{{ stats.not_started }}</p>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-500 mx-auto mb-4"></div>
          <p class="text-gray-600 dark:text-gray-400">Chargement...</p>
        </div>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="rounded-3 border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-6">
        <div class="flex items-center gap-3 text-red-700 dark:text-red-300">
          <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
          <span>{{ error }}</span>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="taches.length === 0" class="rounded-3 border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-12 text-center">
        <div class="w-20 h-20 mx-auto mb-6 bg-green-100 dark:bg-green-900/30 rounded-3 flex items-center justify-center">
          <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">
          Aucune tâche en attente
        </h3>
        <p class="text-gray-500 dark:text-gray-400">
          Toutes vos tâches collaboratives sont à jour ou terminées par tous les assignés
        </p>
      </div>

      <!-- Liste des tâches groupées par activité -->
      <div v-else class="space-y-6">
        <div
          v-for="group in tachesByActivite"
          :key="group.activite.id"
          class="rounded-3 border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] overflow-hidden"
        >
          <!-- Header du groupe -->
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ group.activite.nom }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ group.activite.projet }}</p>
              </div>
              <span class="px-3 py-1 text-sm font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-full">
                {{ group.taches.length }} tâche{{ group.taches.length > 1 ? 's' : '' }}
              </span>
            </div>
          </div>

          <!-- Tâches -->
          <div class="p-4 space-y-4">
            <div
              v-for="tache in group.taches"
              :key="tache.id"
              class="border border-gray-200 dark:border-gray-700 rounded-3 p-4 transition-all"
            >
              <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ tache.code }}</span>
                    <span 
                      class="px-2 py-0.5 text-xs rounded-full"
                      :style="{ 
                        backgroundColor: tache.priorite_color + '20', 
                        color: tache.priorite_color 
                      }"
                    >
                      {{ tache.priorite_icon }} {{ tache.priorite_label }}
                    </span>
                  </div>
                  <h4 class="font-semibold text-gray-900 dark:text-white mb-2">
                    {{ tache.titre }}
                  </h4>
                  <p v-if="tache.description" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                    {{ tache.description }}
                  </p>
                </div>
              </div>

              <!-- Ma complétion -->
              <div class="mb-3 p-2 bg-green-50 dark:bg-green-900/20 rounded-3">
                <div class="flex items-center gap-2 text-sm">
                  <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span class="text-green-700 dark:text-green-300 font-medium">
                    Vous avez terminé votre partie
                  </span>
                  <span v-if="tache.my_status?.completed_at" class="text-green-600 text-xs">
                    le {{ formatDateTime(tache.my_status.completed_at) }}
                  </span>
                </div>
              </div>

              <!-- Statut des collègues -->
              <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  En attente de :
                </p>
                <div class="space-y-2">
                  <div
                    v-for="assignee in getIncompleteAssignees(tache)"
                    :key="assignee.id"
                    class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded-3"
                  >
                    <div class="flex items-center gap-3">
                      <img
                        :src="assignee.avatar || '/default-avatar.png'"
                        :alt="assignee.nom"
                        class="w-8 h-8 rounded-full"
                      />
                      <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                          {{ assignee.nom }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                          {{ assignee.email }}
                        </p>
                      </div>
                    </div>
                    <div class="flex items-center gap-2">
                      <span 
                        class="px-2 py-1 text-xs rounded-full"
                        :class="getStatusClass(assignee.statut)"
                      >
                        {{ assignee.statut_label }}
                      </span>
                      <span class="text-sm text-gray-600 dark:text-gray-300">
                        {{ assignee.progression }}%
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="mt-4 flex items-center gap-2">
                <button
                  @click="viewTask(tache)"
                  class="px-4 py-2 bg-brand-500 text-white rounded-3 hover:bg-brand-600 transition-colors text-sm"
                >
                  Voir détails
                </button>
                <button
                  v-if="tache.echeance"
                  class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors text-sm"
                  :class="{ 'text-red-600 border-red-300': isOverdue(tache.echeance) }"
                >
                  Échéance : {{ formatDate(tache.echeance) }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Detail Modal -->
    <TacheDetailModal
      v-if="showDetailModal"
      :tache="selectedTache"
      @close="showDetailModal = false"
      @edit="handleEditTask"
    />
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import TacheDetailModal from '@/components/taches/TacheDetailModal.vue'
import api from '@/api/axios'

// State
const taches = ref([])
const loading = ref(false)
const error = ref(null)
const router = useRouter()
const showDetailModal = ref(false)
const selectedTache = ref(null)

// Computed
const stats = computed(() => {
  let colleaguesWorking = 0
  let notStarted = 0

  taches.value.forEach(tache => {
    const incomplete = getIncompleteAssignees(tache)
    colleaguesWorking += incomplete.filter(a => a.statut === 'en_cours').length
    notStarted += incomplete.filter(a => a.statut === 'a_faire').length
  })

  return {
    total: taches.value.length,
    colleagues_working: colleaguesWorking,
    not_started: notStarted
  }
})

const tachesByActivite = computed(() => {
  const grouped = {}

  taches.value.forEach(tache => {
    const activiteId = tache.activite.id
    if (!grouped[activiteId]) {
      grouped[activiteId] = {
        activite: {
          id: tache.activite.id,
          nom: tache.activite.nom,
          projet: tache.activite.projet_nom
        },
        taches: []
      }
    }
    grouped[activiteId].taches.push(tache)
  })

  return Object.values(grouped)
})

// Methods
async function loadTaches() {
  loading.value = true
  error.value = null

  try {
    const { data } = await api.get('/taches/waiting-for-colleagues')
    taches.value = data.data || []
  } catch (err) {
    console.error('Erreur chargement:', err)
    error.value = err.response?.data?.message || 'Erreur lors du chargement'
  } finally {
    loading.value = false
  }
}

function getIncompleteAssignees(tache) {
  if (!tache.assignees_status) return []
  return tache.assignees_status.filter(a => a.statut !== 'termine')
}

function getStatusClass(statut) {
  const classes = {
    'a_faire': 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    'en_cours': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    'termine': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
  }
  return classes[statut] || classes['a_faire']
}

function viewTask(tache) {
  selectedTache.value = tache
  showDetailModal.value = true
}

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  })
}

function formatDateTime(dateTime) {
  if (!dateTime) return ''
  return new Date(dateTime).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function isOverdue(date) {
  return new Date(date) < new Date()
}

function handleEditTask(tache) {
  showDetailModal.value = false
  router.push(`/taches/${tache.id}`)
}

// Lifecycle
onMounted(() => {
  loadTaches()
})
</script>