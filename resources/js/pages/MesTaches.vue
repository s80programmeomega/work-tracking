<!-- resources/js/pages/MesTaches.vue -->
<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header avec statistiques personnelles -->
      <div class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
              <div class="w-12 h-12 rounded-3 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
              </div>
              Mes Tâches
            </h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">
              Gérez vos tâches individuelles et collaboratives
            </p>
          </div>

          <div class="flex items-center gap-3">
            <!-- Filtre par semaine -->
            <div class="flex items-center gap-2 bg-gray-100 dark:bg-gray-800 rounded-3 p-1">
              <button @click="changeWeek(-1)" class="p-2 rounded-md hover:bg-white dark:hover:bg-gray-700 transition-colors" title="Semaine précédente">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
              </button>
              <span class="px-3 py-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                S{{ currentWeek }} - {{ currentYear }}
              </span>
              <button @click="changeWeek(1)" class="p-2 rounded-md hover:bg-white dark:hover:bg-gray-700 transition-colors" title="Semaine suivante">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </button>
              <button @click="resetToCurrentWeek" class="px-3 py-1 text-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400" title="Revenir à la semaine actuelle">
                Aujourd'hui
              </button>
            </div>

            <!-- Toggle vue -->
            <div class="flex gap-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-3">
              <button @click="currentView = 'kanban'" :class="['px-3 py-2 rounded-md transition-all flex items-center gap-2 text-sm', currentView === 'kanban' ? 'bg-white dark:bg-gray-700 text-brand-600 dark:text-brand-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white']">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                </svg>
                Kanban
              </button>
              <button @click="currentView = 'list'" :class="['px-3 py-2 rounded-md transition-all flex items-center gap-2 text-sm', currentView === 'list' ? 'bg-white dark:bg-gray-700 text-brand-600 dark:text-brand-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white']">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                Liste
              </button>
            </div>
          </div>
        </div>

        <!-- Statistiques personnelles -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
          <StatCard title="Total" :value="stats.total" icon="clipboard-list" color="gray" />
          <StatCard title="À faire" :value="stats.a_faire" icon="clock" color="slate" />
          <StatCard title="En cours" :value="stats.en_cours" icon="play" color="blue" />
          <StatCard title="Terminées" :value="stats.termine" icon="check-circle" color="green" />
          <StatCard title="En retard" :value="stats.overdue" icon="exclamation" color="red" :alert="stats.overdue > 0" />
          <StatCard title="Taux" :value="`${completionRate}%`" icon="chart-pie" color="brand" :progress="completionRate" />
        </div>
      </div>

      <!-- Filtres avancés -->
      <div class="rounded-3 border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-wrap items-center gap-4">
          <div class="flex-1 min-w-[200px]">
            <select v-model="filters.activite_id" @change="applyFilters" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500">
              <option value="">Toutes les activités</option>
              <option v-for="activite in activites" :key="activite.id" :value="activite.id">
                {{ activite.nom }} ({{ activite.projet?.nom }})
              </option>
            </select>
          </div>
          <select v-model="filters.priorite" @change="applyFilters" class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500">
            <option value="">Toutes priorités</option>
            <option value="critique">🔴 Critique</option>
            <option value="elevee">🟠 Élevée</option>
            <option value="moyenne">🟡 Moyenne</option>
            <option value="faible">🟢 Faible</option>
          </select>
          <select v-model="filters.statut" @change="applyFilters" class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500">
            <option value="">Tous statuts</option>
            <option value="a_faire">À faire</option>
            <option value="en_cours">En cours</option>
            <option value="termine">Terminé</option>
          </select>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="filters.overdue" @change="applyFilters" class="w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500" />
            <span class="text-sm text-gray-700 dark:text-gray-300">En retard uniquement</span>
          </label>
          <button @click="resetFilters" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
            Réinitialiser
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-500 mx-auto mb-4"></div>
          <p class="text-gray-600 dark:text-gray-400">Chargement de vos tâches...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="rounded-3 border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-6">
        <div class="flex items-center gap-3 text-red-700 dark:text-red-300">
          <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
          <span class="font-medium">{{ error }}</span>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredTaches.length === 0" class="rounded-3 border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-12 text-center">
        <div class="w-20 h-20 mx-auto mb-6 bg-gray-100 dark:bg-gray-800 rounded-3 flex items-center justify-center">
          <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
          </svg>
        </div>
        <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">Aucune tâche trouvée</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-6">
          {{ hasActiveFilters ? 'Essayez de modifier vos filtres' : 'Vous n\'avez aucune tâche pour cette période' }}
        </p>
        <button v-if="hasActiveFilters" @click="resetFilters" class="px-6 py-2 bg-brand-500 text-white rounded-3 hover:bg-brand-600 transition-colors">
          Réinitialiser les filtres
        </button>
      </div>

      <!-- Content Views -->
      <div v-else class="rounded-3 border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-6">
        <!-- Vue Kanban avec Statuts Individuels -->
        <div v-if="currentView === 'kanban'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <KanbanColumnPersonal
            v-for="column in kanbanColumns"
            :key="column.statut"
            :title="column.title"
            :statut="column.statut"
            :taches="getMyTasksByStatus(column.statut)"
            :status-color="column.color"
            :status-icon="column.icon"
            @move-card="handleMoveMyCard"
            @view-task="handleViewTask"
            @submit-result="handleSubmitResult"
          />
        </div>

        <!-- Vue Liste -->
        <div v-else-if="currentView === 'list'" class="space-y-3">
          <div v-for="tache in filteredTaches" :key="tache.id">
            <TacheCardPersonal
              :tache="tache"
              @view="handleViewTask"
              @move="handleMoveMyCard"
              @submit-result="handleSubmitResult"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Task Detail Modal -->
    <TacheDetailModal
      v-if="showViewModal"
      :tache="currentTache"
      @close="showViewModal = false"
      @edit="handleEditTask"
      @move-my-card="handleMoveMyCard"
      @submit-result="handleSubmitResult"
    />

    <!-- Submit Result Modal -->
    <SubmitResultModal
      v-if="showSubmitResultModal"
      :tache="currentTache"
      @close="showSubmitResultModal = false"
      @submitted="handleResultSubmitted"
    />
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import StatCard from '@/components/common/StatCard.vue'
import KanbanColumnPersonal from '@/components/taches/KanbanColumnPersonal.vue'
import TacheCardPersonal from '@/components/taches/TacheCardPersonal.vue'
import TacheDetailModal from '@/components/taches/TacheDetailModal.vue'
import SubmitResultModal from '@/components/taches/SubmitResultModal.vue'
import api from '@/api/axios'
import { useRealtimeRefresh } from '@/composables/useRealtimeRefresh'

// State
const taches = ref([])
const activites = ref([])
const loading = ref(false)
const error = ref(null)
const currentView = ref('kanban')
const router = useRouter()
const showViewModal = ref(false)
const showSubmitResultModal = ref(false)
const currentTache = ref(null)

// Semaine courante
const now = new Date()
const currentWeek = ref(getWeekNumber(now))
const currentYear = ref(now.getFullYear())

// Filtres
const filters = ref({
  activite_id: '',
  priorite: '',
  statut: '',
  overdue: false
})

// Colonnes Kanban basées sur MON statut individuel
const kanbanColumns = [
  { statut: 'a_faire', title: 'À faire', color: '#6B7280', icon: '📋' },
  { statut: 'en_cours', title: 'En cours', color: '#3B82F6', icon: '🔄' },
  { statut: 'termine', title: 'Terminé', color: '#10B981', icon: '✅' }
]

// Computed
const filteredTaches = computed(() => {
  let result = [...taches.value]

  if (filters.value.activite_id) {
    result = result.filter(t => t.activite_id == filters.value.activite_id)
  }

  if (filters.value.priorite) {
    result = result.filter(t => t.priorite === filters.value.priorite)
  }

  if (filters.value.statut) {
    result = result.filter(t => getMyStatus(t) === filters.value.statut)
  }

  if (filters.value.overdue) {
    result = result.filter(t => t.is_overdue)
  }

  return result
})

const stats = computed(() => {
  const all = filteredTaches.value
  return {
    total: all.length,
    a_faire: all.filter(t => getMyStatus(t) === 'a_faire').length,
    en_cours: all.filter(t => getMyStatus(t) === 'en_cours').length,
    termine: all.filter(t => getMyStatus(t) === 'termine').length,
    overdue: all.filter(t => t.is_overdue && getMyStatus(t) !== 'termine').length
  }
})

const completionRate = computed(() => {
  if (stats.value.total === 0) return 0
  return Math.round((stats.value.termine / stats.value.total) * 100)
})

const hasActiveFilters = computed(() => {
  return filters.value.activite_id || filters.value.priorite || filters.value.statut || filters.value.overdue
})

// Methods
function getWeekNumber(date) {
  const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()))
  const dayNum = d.getUTCDay() || 7
  d.setUTCDate(d.getUTCDate() + 4 - dayNum)
  const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1))
  return Math.ceil((((d - yearStart) / 86400000) + 1) / 7)
}

// ✅ Obtenir MON statut individuel pour une tâche
function getMyStatus(tache) {
  return tache.my_status?.statut || tache.statut
}

// ✅ Filtrer mes tâches par mon statut individuel
function getMyTasksByStatus(statut) {
  return filteredTaches.value.filter(t => getMyStatus(t) === statut)
}

async function loadTaches() {
  loading.value = true
  error.value = null

  try {
    const params = {
      week_number: currentWeek.value,
      year: currentYear.value
    }

    const { data } = await api.get('/taches/mes-taches', { params })
    taches.value = data.data || []
    console.log('✅ Mes tâches chargées:', taches.value.length)
  } catch (err) {
    console.error('❌ Erreur chargement tâches:', err)
    error.value = err.response?.data?.message || 'Impossible de charger vos tâches'
  } finally {
    loading.value = false
  }
}

async function loadActivites() {
  try {
    const { data } = await api.get('/activites/mes-activites')
    activites.value = data.data || data || []
  } catch (err) {
    console.error('Erreur chargement activités:', err)
  }
}

function changeWeek(delta) {
  currentWeek.value += delta
  if (currentWeek.value > 52) {
    currentWeek.value = 1
    currentYear.value++
  } else if (currentWeek.value < 1) {
    currentWeek.value = 52
    currentYear.value--
  }
  loadTaches()
}

function resetToCurrentWeek() {
  const now = new Date()
  currentWeek.value = getWeekNumber(now)
  currentYear.value = now.getFullYear()
  loadTaches()
}

function applyFilters() {
  // Les filtres sont appliqués automatiquement via computed
}

function resetFilters() {
  filters.value = {
    activite_id: '',
    priorite: '',
    statut: '',
    overdue: false
  }
}

// ✅ NOUVEAU : Déplacer ma carte (statut individuel)
function handleEditTask(tache) {
  showViewModal.value = false
  router.push(`/taches/${tache.id}`)
}

async function handleMoveMyCard({ tache, newStatut, progression, notes }) {
  try {
    const payload = {
      statut: newStatut,
      progression: progression || (newStatut === 'termine' ? 100 : newStatut === 'en_cours' ? 50 : 0),
      notes_personnelles: notes
    }

    await api.post(`/taches/${tache.id}/move-my-card`, payload)
    await loadTaches()

    console.log('✅ Statut individuel mis à jour:', newStatut)
  } catch (err) {
    console.error('❌ Erreur déplacement carte:', err)
    alert(err.response?.data?.message || 'Erreur lors du déplacement')
    await loadTaches()
  }
}

// ✅ NOUVEAU : Soumettre mon résultat
function handleSubmitResult(tache) {
  currentTache.value = tache
  showSubmitResultModal.value = true
}

async function handleResultSubmitted() {
  showSubmitResultModal.value = false
  await loadTaches()
  alert('✅ Résultat soumis avec succès !')
}

// Handlers tâches
async function handleViewTask(tache) {
  try {
    const { data } = await api.get(`/taches/${tache.id}`)
    currentTache.value = data.data
    showViewModal.value = true
  } catch (err) {
    console.error('Erreur chargement détails:', err)
    alert('Erreur lors du chargement des détails')
  }
}

useRealtimeRefresh({ onTacheChanged: () => loadTaches() })

// Lifecycle
onMounted(async () => {
  await Promise.all([loadTaches(), loadActivites()])
})
</script>