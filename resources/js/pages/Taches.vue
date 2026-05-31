<!-- resources/js/pages/Taches.vue - VERSION FINALE CORRIGÉE -->
<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="'Gestion des Tâches'" />

    <div class="rounded-3 border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      
      <!-- Header avec filtres et statistiques -->
      <div class="mb-6 space-y-4">
        <!-- Ligne 1: Sélection activité et actions -->
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-3 flex-1">
            <!-- Activity Selector -->
            <div class="relative w-full sm:flex-1 sm:max-w-md">
              <select
                v-model="selectedActiviteId"
                @change="handleActiviteChange"
                class="w-full px-4 py-2.5 pl-10 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all"
              >
                <option value="">📋 Toutes les activités</option>
                <option v-for="activite in activites" :key="activite.id" :value="activite.id">
                  {{ activite.nom }} ({{ activite.projet?.nom }})
                </option>
              </select>
              <svg class="absolute left-3 top-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>

            <!-- Vue Toggle -->
            <div class="flex gap-2 bg-gray-100 dark:bg-gray-800 p-1 rounded-3 shrink-0">
              <button
                dusk="view-table-btn"
                @click="currentView = 'table'"
                :class="[
                  'px-3 py-2 rounded-md transition-all flex items-center gap-2 text-sm',
                  currentView === 'table'
                    ? 'bg-white dark:bg-gray-700 text-brand-600 dark:text-brand-400'
                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                ]"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M10 3v18M6 3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6a3 3 0 013-3z" />
                </svg>
                Tableau
              </button>
              <button
                dusk="view-kanban-btn"
                @click="currentView = 'kanban'"
                :class="[
                  'px-3 py-2 rounded-md transition-all flex items-center gap-2 text-sm',
                  currentView === 'kanban'
                    ? 'bg-white dark:bg-gray-700 text-brand-600 dark:text-brand-400'
                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                ]"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                </svg>
                Kanban
              </button>
              <button
                dusk="view-list-btn"
                @click="currentView = 'list'"
                :class="[
                  'px-3 py-2 rounded-md transition-all flex items-center gap-2 text-sm',
                  currentView === 'list'
                    ? 'bg-white dark:bg-gray-700 text-brand-600 dark:text-brand-400'
                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                ]"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                Liste
              </button>
            </div>

            <!-- Filtre assigné -->
            <select
              v-model="filterAssignee"
              dusk="filter-assignee"
              class="w-full sm:w-auto px-3 py-2 text-sm border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent"
            >
              <option value="me">Mes tâches</option>
              <option value="all">Toutes les tâches</option>
            </select>
          </div>

          <!-- Actions -->
          <div class="flex flex-wrap gap-3 w-full sm:w-auto">
            <!-- Validations en attente -->
            <button
              v-if="pendingValidationsCount > 0"
              @click="showPendingValidations = true"
              class="relative px-4 py-2 border border-amber-300 dark:border-amber-700 rounded-3 hover:bg-amber-50 dark:hover:bg-amber-900/20 flex items-center gap-2 text-amber-700 dark:text-amber-400 transition-all"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Validations
              <span class="absolute -top-2 -right-2 px-2 py-0.5 bg-amber-500 text-white text-xs font-bold rounded-full">
                {{ pendingValidationsCount }}
              </span>
            </button>

            <!-- Archivées -->
            <button
              @click="showArchived = !showArchived"
              :class="[
                'px-4 py-2 border rounded-3 flex items-center gap-2 transition-all',
                showArchived 
                  ? 'bg-gray-100 dark:bg-gray-700 border-gray-400 dark:border-gray-600' 
                  : 'border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
              ]"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
              </svg>
              {{ showArchived ? 'Masquer archivées' : 'Voir archivées' }}
            </button>

            <!-- Nouvelle tâche -->
            <button
              v-if="canCreateTask"
              @click="openCreateForm"
              class="px-4 py-2 text-white rounded-3 flex items-center gap-2 transition-all"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Nouvelle tâche
            </button>
          </div>
        </div>

        <!-- Ligne 2: Statistiques (si activité sélectionnée) -->
        <div v-if="selectedActiviteId && !showArchived && stats.total > 0" class="flex items-center gap-6 p-4 rounded-3 border border-gray-200 dark:border-gray-700">
          <!-- À faire -->
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-3 bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
              <div class="w-3 h-3 rounded-full bg-gray-500"></div>
            </div>
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">À faire</p>
              <p class="text-xl font-bold text-gray-900 dark:text-white">{{ stats.a_faire }}</p>
            </div>
          </div>

          <!-- En cours -->
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-3 bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
              <div class="w-3 h-3 rounded-full bg-blue-500"></div>
            </div>
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">En cours</p>
              <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ stats.en_cours }}</p>
            </div>
          </div>

          <!-- Terminé -->
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-3 bg-green-100 dark:bg-green-900/20 flex items-center justify-center">
              <div class="w-3 h-3 rounded-full bg-green-500"></div>
            </div>
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Terminé</p>
              <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ stats.termine }}</p>
            </div>
          </div>

          <!-- Taux de complétion -->
          <div class="ml-auto">
            <div class="flex items-center gap-3">
              <div class="text-right">
                <p class="text-sm text-gray-600 dark:text-gray-400">Taux de complétion</p>
                <p class="text-xl font-bold text-brand-600 dark:text-brand-400">{{ completionRate }}%</p>
              </div>
              <div class="w-16 h-16 relative">
                <svg class="transform -rotate-90" viewBox="0 0 36 36">
                  <path
                    class="text-gray-200 dark:text-gray-700"
                    stroke="currentColor"
                    stroke-width="3"
                    fill="none"
                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                  />
                  <path
                    class="text-brand-500"
                    stroke="currentColor"
                    stroke-width="3"
                    fill="none"
                    :stroke-dasharray="`${completionRate}, 100`"
                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                  />
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Debug Info (si activé) -->
      <div v-if="$route.query.debug" class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 rounded-3">
        <p class="text-sm text-blue-800 dark:text-blue-300">
          <strong>Debug Info:</strong><br>
          Activité sélectionnée: {{ selectedActiviteId }}<br>
          Kanban initialisé: {{ !!kanban }}<br>
          À faire: {{ kanban.a_faire?.length || 0 }} tâches<br>
          En cours: {{ kanban.en_cours?.length || 0 }} tâches<br>
          Terminé: {{ kanban.termine?.length || 0 }} tâches<br>
          Loading: {{ loading }}<br>
          Error: {{ error }}<br>
          Can Create: {{ canCreateTask }}
        </p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-500 mx-auto mb-4"></div>
          <p class="text-gray-600 dark:text-gray-400">Chargement des tâches...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-3 relative">
        <div class="flex items-center gap-2">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
          <span>{{ error }}</span>
        </div>
      </div>

      <!-- Empty State - No Activity Selected -->
      <div v-else-if="!selectedActiviteId && !showArchived" class="text-center py-16">
        <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 dark:bg-gray-800 rounded-3 flex items-center justify-center">
          <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
        </div>
        <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">
          Sélectionnez une activité
        </h3>
        <p class="text-gray-500 dark:text-gray-400 mb-6">
          Choisissez une activité pour voir et gérer ses tâches
        </p>
      </div>

      <!-- Content -->
      <div v-else>
        <!-- Archived Tasks View -->
        <div v-if="showArchived" class="space-y-4">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
              </svg>
              Tâches Archivées
              <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ archivedTasks.length }})</span>
            </h3>
          </div>

          <div v-if="archivedTasks.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
            </svg>
            <p>Aucune tâche archivée</p>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="tache in archivedTasks" :key="tache.id" class="opacity-75 hover:opacity-100 transition-opacity">
              <TacheCard
                :tache="tache"
                @view="handleViewTask"
                @edit="handleEditTask"
                @duplicate="handleDuplicateTask"
                @archive="handleUnarchiveTask"
                @delete="handleDeleteTask"
                @validate="handleValidateTask"
              />
            </div>
          </div>
        </div>

        <!-- Active Tasks - Table View (default) -->
        <div dusk="view-table-panel" v-else-if="currentView === 'table'">
          <TacheTable
            dusk="tache-table"
            :taches="filteredTasks"
            @view="handleViewTask"
            @edit="handleEditTask"
            @updated="() => fetchKanbanForActivite(selectedActiviteId)"
          />
        </div>

        <!-- Active Tasks - Kanban View -->
        <div dusk="view-kanban-panel" v-else-if="currentView === 'kanban'">
          <KanbanBoard
            :kanban="kanban"
            :loading="loading"
            :activite-id="selectedActiviteId"
            @add-task="handleAddTask"
            @view-task="handleViewTask"
            @edit-task="handleEditTask"
            @duplicate-task="handleDuplicateTask"
            @archive-task="handleArchiveTask"
            @delete-task="handleDeleteTask"
            @validate-task="handleValidateTask"
            @task-moved="handleTaskMoved"
          />
        </div>

        <!-- Active Tasks - List View -->
        <div dusk="view-list-panel" v-else-if="currentView === 'list'" class="space-y-2">
          <div v-if="filteredTasks.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <p>Aucune tâche pour cette activité</p>
          </div>
          
          <div v-else v-for="tache in filteredTasks" :key="tache.id">
            <TacheCard
              :tache="tache"
              @view="handleViewTask"
              @edit="handleEditTask"
              @duplicate="handleDuplicateTask"
              @archive="handleArchiveTask"
              @delete="handleDeleteTask"
              @validate="handleValidateTask"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Create: wizard -->
    <TacheCreateWizard
      v-if="showForm && !currentTache"
      :activite-id="selectedActiviteId"
      :initial-statut="currentStatut"
      @close="closeForm"
      @saved="handleTaskSaved"
    />

    <!-- Edit: tabbed form -->
    <TacheForm
      v-if="showForm && currentTache"
      :tache="currentTache"
      :activite-id="selectedActiviteId"
      :initial-statut="currentStatut"
      @close="closeForm"
      @saved="handleTaskSaved"
    />

    <!-- Task Detail Modal -->
    <TacheDetailModal
      v-if="showViewModal"
      :tache="currentTache"
      @close="closeDetailModal"
      @edit="handleEditFromDetail"
      @validate-n1="handleValidateN1"
      @validate-n2="handleValidateN2"
    />

    <!-- Pending Validations Modal -->
    <PendingValidationsModal
      v-if="showPendingValidations"
      @close="showPendingValidations = false"
      @validated="loadPendingValidations"
    />
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useTaches } from '@/composables/useTaches'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import KanbanBoard from '@/components/taches/KanbanBoardSimple.vue'
import TacheForm from '@/components/taches/TacheForm.vue'
import TacheCreateWizard from '@/components/taches/TacheCreateWizard.vue'
import TacheCard from '@/components/taches/TacheCard.vue'
import TacheTable from '@/components/taches/TacheTable.vue'
import TacheDetailModal from '@/components/taches/TacheDetailModal.vue'
import PendingValidationsModal from '@/components/taches/PendingValidationsModal.vue'
import api from '@/api/axios'

const route = useRoute()

const {
  kanban: storeKanban,
  loading,
  error,
  stats: storeStats,
  fetchKanbanForActivite,
  moveTache,
  duplicateTache,
  archiveTache,
  deleteTache,
  clearError
} = useTaches()

// ✅ CORRIGÉ : Utiliser directement storeKanban avec garantie de structure
const kanban = computed(() => {
  const data = storeKanban.value || {}
  return {
    a_faire: data.a_faire || [],
    en_cours: data.en_cours || [],
    termine: data.termine || []
  }
})

// ✅ CORRIGÉ : Stats basées sur le kanban réel
const stats = computed(() => {
  const k = kanban.value
  return {
    total: k.a_faire.length + k.en_cours.length + k.termine.length,
    a_faire: k.a_faire.length,
    en_cours: k.en_cours.length,
    termine: k.termine.length
  }
})

// State
const activites = ref([])
const selectedActiviteId = ref(null)
const showForm = ref(false)
const showViewModal = ref(false)
const showArchived = ref(false)
const showPendingValidations = ref(false)
const currentTache = ref(null)
const currentStatut = ref('a_faire')
const currentView = ref('table')
const archivedTasks = ref([])
const pendingValidationsCount = ref(0)
const filterAssignee = ref('me')

// Computed
const currentUser = computed(() => {
  const userStr = localStorage.getItem('user')
  return userStr ? JSON.parse(userStr) : null
})
 
// ✅ CORRIGÉ : Simplification des permissions
const canCreateTask = computed(() => {
  if (!selectedActiviteId.value || !currentUser.value) return false
  
  const activite = activites.value.find(a => a.id === selectedActiviteId.value)
  if (!activite) return false
  
  // Super admin peut toujours créer
  if (currentUser.value.is_super_admin) return true
  
  // Responsable de l'activité peut créer
  if (activite.responsable_id === currentUser.value.id) return true
  
  // Responsable du projet peut créer
  if (activite.projet?.responsable_id === currentUser.value.id) return true
  
  // Vérifier les permissions du membre (si disponible)
  if (activite.user_permissions?.can_create_tasks) return true
  
  // Par défaut, autoriser (sera validé par le backend)
  return true
})

const completionRate = computed(() => {
  if (stats.value.total === 0) return 0
  return Math.round((stats.value.termine / stats.value.total) * 100)
})

const allActiveTasks = computed(() => {
  return [
    ...kanban.value.a_faire,
    ...kanban.value.en_cours,
    ...kanban.value.termine
  ]
})

const filteredTasks = computed(() => {
  const tasks = allActiveTasks.value
  if (filterAssignee.value === 'me' && currentUser.value) {
    return tasks.filter(t =>
      t.assignees?.some(a => a.id === currentUser.value.id) ||
      t.responsable_id === currentUser.value.id
    )
  }
  return tasks
})

// Methods  
const loadActivites = async () => {
  try {
    console.log('🔍 Chargement des activités...')
    const { data } = await api.get('/activites/mes-activites')
    activites.value = data.data || data || []
    
    console.log('✅ Activités chargées:', activites.value.length)

    // Auto-select first activity if none already set (e.g. from query param)
    if (activites.value.length > 0 && !selectedActiviteId.value) {
      selectedActiviteId.value = activites.value[0].id
    }
    if (selectedActiviteId.value) {
      await handleActiviteChange()
    }
  } catch (err) {
    console.error('❌ Erreur chargement activités:', err)
    error.value = 'Impossible de charger les activités'
  }
}

const handleActiviteChange = async () => {
  if (!selectedActiviteId.value) return

  console.log('📍 Changement d\'activité:', selectedActiviteId.value)
  clearError()
  
  try {
    await fetchKanbanForActivite(selectedActiviteId.value)
    console.log('✅ Kanban chargé:', kanban.value)
  } catch (err) {
    console.error('❌ Erreur chargement kanban:', err)
  }
}

const loadArchivedTasks = async () => {
  if (!selectedActiviteId.value) return

  try {
    const { data } = await api.get(`/taches`, {
      params: {
        activite_id: selectedActiviteId.value,
        archive_status: 'archived'
      }
    })
    archivedTasks.value = data.data || []
  } catch (err) {
    console.error('Erreur chargement tâches archivées:', err)
  }
}

const loadPendingValidations = async () => {
  try {
    const { data } = await api.get('/taches/en-attente')
    pendingValidationsCount.value = (data.counts?.n1 || 0) + (data.counts?.n2 || 0)
  } catch (err) {
    console.error('Erreur chargement validations:', err)
  }
}

const openCreateForm = () => {
  if (!selectedActiviteId.value) {
    alert('Veuillez sélectionner une activité')
    return
  }
  showViewModal.value = false
  currentTache.value = null
  currentStatut.value = 'a_faire'
  showForm.value = true
}

const handleAddTask = (statut) => {
  showViewModal.value = false
  currentTache.value = null
  currentStatut.value = statut
  showForm.value = true
}

const handleViewTask = async (tache) => {
  showForm.value = false
  currentTache.value = null
  try {
    const { data } = await api.get(`/taches/${tache.id}`)
    currentTache.value = data.data
    showViewModal.value = true
  } catch (err) {
    console.error('Error loading task details:', err)
    alert('Erreur lors du chargement des détails de la tâche')
  }
}

const handleEditTask = (tache) => {
  showViewModal.value = false
  currentTache.value = tache
  showForm.value = true
}

const handleEditFromDetail = (tache) => {
  showViewModal.value = false
  currentTache.value = tache
  showForm.value = true
}

const handleDuplicateTask = async (tache) => {
  if (!confirm('Voulez-vous dupliquer cette tâche ?')) return

  try {
    await duplicateTache(tache.id)
    await fetchKanbanForActivite(selectedActiviteId.value)
  } catch (err) {
    console.error('Error duplicating task:', err)
    alert('Erreur lors de la duplication de la tâche')
  }
}

const handleArchiveTask = async (tache) => {
  if (!confirm('Voulez-vous archiver cette tâche ?')) return

  try {
    await archiveTache(tache.id)
    await fetchKanbanForActivite(selectedActiviteId.value)
    await loadArchivedTasks()
  } catch (err) {
    console.error('Error archiving task:', err)
    alert('Erreur lors de l\'archivage de la tâche')
  }
}

const handleUnarchiveTask = async (tache) => {
  if (!confirm('Voulez-vous désarchiver cette tâche ?')) return

  try {
    await api.post(`/taches/${tache.id}/unarchive`)
    await fetchKanbanForActivite(selectedActiviteId.value)
    await loadArchivedTasks()
  } catch (err) {
    console.error('Error unarchiving task:', err)
    alert('Erreur lors du désarchivage de la tâche')
  }
}

const handleDeleteTask = async (tache) => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) return

  try {
    await deleteTache(tache.id)
    await fetchKanbanForActivite(selectedActiviteId.value)
    if (showArchived.value) await loadArchivedTasks()
  } catch (err) {
    console.error('Error deleting task:', err)
    alert('Erreur lors de la suppression de la tâche')
  }
}

const handleValidateTask = async (tache) => {
  const needsN1 = tache.validation?.n1_required && !tache.validation?.n1_validated_at
  const needsN2 = tache.validation?.n2_required && tache.validation?.n1_validated_at && !tache.validation?.n2_validated_at

  if (needsN1) {
    await handleValidateN1(tache)
  } else if (needsN2) {
    await handleValidateN2(tache)
  }
}

// Post-refactor: la validation est faite au niveau resultat (pas tâche). On
// récupère le tache complet pour lire `all_results`, puis on valide tous les
// resultats en attente du niveau demandé en parallèle.
async function validateAllPendingForLevel(tache, level, commentaire) {
  const fresh = tache.all_results
    ? tache
    : (await api.get(`/taches/${tache.id}`)).data.data
  const matcher = level === 'n1'
    ? (r) => !r.valide_par_n1
    : (r) => r.valide_par_n1 && !r.valide_par_n2
  const pending = (fresh.all_results ?? []).filter(matcher)
  if (!pending.length) {
    alert('Aucun résultat en attente de validation à ce niveau.')
    return false
  }
  await Promise.all(pending.map((r) =>
    api.post(`/evaluations/resultats-individuels/${r.id}/validate-${level}`, { commentaire })
  ))
  return true
}

const handleValidateN1 = async (tache) => {
  const commentaire = prompt('Commentaire de validation (optionnel):')
  if (commentaire === null) return

  try {
    const ok = await validateAllPendingForLevel(tache, 'n1', commentaire)
    if (!ok) return
    await fetchKanbanForActivite(selectedActiviteId.value)
    await loadPendingValidations()
    if (showViewModal.value) {
      await handleViewTask(tache)
    }
  } catch (err) {
    console.error('Error validating N1:', err)
    alert(err.response?.data?.message || 'Erreur lors de la validation')
  }
}

const handleValidateN2 = async (tache) => {
  const commentaire = prompt('Commentaire de validation finale (optionnel):')
  if (commentaire === null) return

  try {
    const ok = await validateAllPendingForLevel(tache, 'n2', commentaire)
    if (!ok) return
    await fetchKanbanForActivite(selectedActiviteId.value)
    await loadPendingValidations()
    if (showViewModal.value) {
      await handleViewTask(tache)
    }
  } catch (err) {
    console.error('Error validating N2:', err)
    alert(err.response?.data?.message || 'Erreur lors de la validation')
  }
}

const handleTaskMoved = async ({ tache, newStatut, newOrdre }) => {
  try {
    await moveTache(tache.id, newStatut, newOrdre)
    await fetchKanbanForActivite(selectedActiviteId.value)
  } catch (err) {
    console.error('Error moving task:', err)
    alert('Erreur lors du déplacement de la tâche')
    await fetchKanbanForActivite(selectedActiviteId.value)
  }
}

const handleTaskSaved = async () => {
  closeForm()
  await fetchKanbanForActivite(selectedActiviteId.value)
  await loadPendingValidations()
}

const closeForm = () => {
  showForm.value = false
  currentTache.value = null
}

const closeDetailModal = () => {
  showViewModal.value = false
  currentTache.value = null
}

// Watchers
watch(showArchived, async (newValue) => {
  if (newValue && selectedActiviteId.value) {
    await loadArchivedTasks()
  }
})

// Lifecycle
onMounted(async () => {
  if (route.query.activite) {
    selectedActiviteId.value = Number(route.query.activite)
  }
  await loadActivites()
  await loadPendingValidations()
})

</script>