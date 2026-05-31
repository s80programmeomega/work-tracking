<!-- resources/js/pages/TachesResponsable.vue -->
<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header Premium avec thème violet/purple pour "Responsable" -->
      <div class="rounded-3 border border-purple-200 dark:border-purple-800 p-6 ">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
          <div class="flex min-w-0 items-center gap-4">
            <div class="w-14 h-14 rounded-3 flex items-center justify-center ring-4 ring-purple-100 dark:ring-purple-900/30">
              <span class="text-2xl">👑</span>
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                Mes Tâches en Responsabilité
              </h1>
              <p class="text-gray-500 dark:text-gray-400">Tâches dont vous êtes le responsable avec pleins pouvoirs</p>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-3">
            <!-- Bouton Statistiques -->
            <button
              @click="showStats = !showStats"
              class="inline-flex items-center gap-2 rounded-3 border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              Statistiques
            </button>

            <!-- Bouton refresh -->
            <button
              @click="loadResponsableTasks" 
              :disabled="loading" 
              class="p-2 rounded-3 border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" 
              title="Actualiser"
            >
              <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </button>

            <!-- Toggle vue -->
            <div class="flex gap-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-3">
              <button
                dusk="view-kanban-btn"
                @click="currentView = 'kanban'"
                :class="['px-3 py-2 rounded-md transition-all flex items-center gap-2 text-sm',
                  currentView === 'kanban' ? 'bg-white dark:bg-gray-700 text-purple-600 dark:text-purple-400' : 'text-gray-600 dark:text-gray-400']"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                </svg>
                Kanban
              </button>
              <button
                dusk="view-grouped-btn"
                @click="currentView = 'grouped'"
                :class="['px-3 py-2 rounded-md transition-all flex items-center gap-2 text-sm',
                  currentView === 'grouped' ? 'bg-white dark:bg-gray-700 text-purple-600 dark:text-purple-400' : 'text-gray-600 dark:text-gray-400']"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Par Activité
              </button>
            </div>
          </div>
        </div>

        <!-- Statistiques panel -->
        <transition
          enter-active-class="transition-all duration-300 ease-out"
          enter-from-class="opacity-0 -translate-y-4"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-200 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-4"
        >
          <TachesResponsableStats
            v-if="showStats"
            :stats="stats"
            :loading="loading"
            @close="showStats = false"
          />
        </transition>

        <!-- Info box -->
        <div class="mt-4 p-3 bg-purple-50 dark:bg-purple-900/20 rounded-3 border border-purple-200 dark:border-purple-800">
          <div class="flex items-start gap-2">
            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-sm text-purple-700 dark:text-purple-300">
              <p class="font-semibold mb-1">En tant que responsable, vous avez :</p>
              <ul class="space-y-1 list-disc list-inside ml-2">
                <li>Tous les droits de gestion (édition, validation, suppression)</li>
                <li>La responsabilité du pilotage et de la coordination</li>
                <li>La capacité de valider les résultats des intervenants</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Message d'erreur -->
      <div v-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-3 p-4">
        <p class="text-red-800 dark:text-red-300">{{ error }}</p>
      </div>

      <!-- Vue Kanban -->
      <div dusk="view-kanban-panel" v-if="currentView === 'kanban' && !loading" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <KanbanColumnResponsable
          v-for="column in kanbanColumns"
          :key="column.statut"
          :title="column.title"
          :statut="column.statut"
          :taches="getTasksByStatus(column.statut)"
          :status-color="column.color"
          :status-icon="column.icon"
          @move-card="handleMoveCard"
          @view-task="handleViewTask"
          @edit-task="handleEditTask"
        />
      </div>

      <!-- Vue groupée -->
      <div dusk="view-grouped-panel" v-else-if="currentView === 'grouped' && !loading" ref="listRef" class="space-y-6">
        <p v-if="tasksByActivite.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">Aucune tâche</p>
        
        <div v-for="group in tasksByActivite" :key="group.activite.id" class="stagger-item rounded-3 border bg-white dark:bg-gray-800 overflow-hidden">
          <div class="px-6 py-4 bg-purple-50 dark:bg-purple-900/20 border-b">
            <div class="flex items-center justify-between">
              <h3 class="font-semibold">{{ group.activite.nom }}</h3>
              <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 rounded-full text-sm">
                {{ group.taches.length }} tâche{{ group.taches.length > 1 ? 's' : '' }}
              </span>
            </div>
          </div>
          <div class="p-4 grid grid-cols-3 gap-3">
            <div v-for="col in kanbanColumns" :key="col.statut" class="bg-gray-50 dark:bg-gray-900 rounded-3 p-3">
              <h4 class="text-sm font-medium mb-2">{{ col.icon }} {{ col.title }}</h4>
              <div class="space-y-2">
                <div 
                  v-for="tache in group.taches.filter(t => t.statut === col.statut)" 
                  :key="tache.id"
                  @click="handleViewTask(tache)"
                  class="p-2 bg-white dark:bg-gray-800 rounded border hover:border-purple-400 cursor-pointer"
                >
                  <p class="text-sm line-clamp-2">{{ tache.titre }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="text-center">
          <svg class="animate-spin h-10 w-10 text-purple-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <p class="text-gray-600 dark:text-gray-400">Chargement...</p>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <TacheDetailModal
      v-if="showViewModal"
      :tache="currentTache"
      @close="showViewModal = false"
      @edit="handleEditTask"
      @task-updated="loadResponsableTasks"
    />

    <TacheForm
      v-if="showEditModal"
      :tache="currentTache"
      @close="showEditModal = false"
      @saved="handleTaskSaved"
    />
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import TachesResponsableStats from '@/components/taches/TachesResponsableStats.vue'
import KanbanColumnResponsable from '@/components/taches/KanbanColumnResponsable.vue'
import TacheDetailModal from '@/components/taches/TacheDetailModal.vue'
import TacheForm from '@/components/taches/TacheForm.vue'
import { useStagger } from '@/composables/useAnimations'
import api from '@/api/axios'
import { useToast } from 'vue-toastification'

const toast = useToast()
const { staggerRef: listRef, applyStagger } = useStagger(55)

const taches = ref([])
const loading = ref(false)
const error = ref(null)
const currentView = ref('kanban')
const showViewModal = ref(false)
const showStats = ref(false)
const showEditModal = ref(false)
const currentTache = ref(null)

const kanbanColumns = [
  { statut: 'a_faire', title: 'À faire', color: '#6B7280', icon: '📋' },
  { statut: 'en_cours', title: 'En cours', color: '#3B82F6', icon: '🔄' },
  { statut: 'termine', title: 'Terminé', color: '#10B981', icon: '✅' }
]

const stats = computed(() => ({
  total: taches.value.length,
  a_faire: taches.value.filter(t => t.statut === 'a_faire').length,
  en_cours: taches.value.filter(t => t.statut === 'en_cours').length,
  termine: taches.value.filter(t => t.statut === 'termine').length,
  overdue: taches.value.filter(t => t.is_overdue && t.statut !== 'termine').length,
  total_assignees: [...new Set(taches.value.flatMap(t => t.assignees?.map(a => a.id) || []))].length
}))

const tasksByActivite = computed(() => {
  const grouped = {}
  taches.value.forEach(tache => {
    const id = tache.activite_id
    if (!grouped[id]) {
      grouped[id] = {
        activite: tache.activite,
        taches: [],
        total_assignees: new Set()
      }
    }
    grouped[id].taches.push(tache)
    tache.assignees?.forEach(a => grouped[id].total_assignees.add(a.id))
  })
  
  return Object.values(grouped).map(g => ({
    ...g,
    total_assignees: g.total_assignees.size
  })).sort((a, b) => a.activite.nom.localeCompare(b.activite.nom))
})

function getTasksByStatus(statut) {
  return taches.value.filter(t => t.statut === statut)
}

async function loadResponsableTasks() {
  loading.value = true
  error.value = null
  try {
    const { data } = await api.get('/taches/my-tasks-as-responsable')
    taches.value = data.data || []
    applyStagger()
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur de chargement'
    toast.error(error.value)
  } finally {
    loading.value = false
  }
}

async function handleMoveCard({ tache, newStatut }) {
  try {
    await api.put(`/taches/${tache.id}`, { statut: newStatut })
    await loadResponsableTasks()
    toast.success("Tâche mise à jour !")
  } catch (err) {
    toast.error('Erreur lors du déplacement')
    await loadResponsableTasks()
  }
}

async function handleViewTask(tache) {
  try {
    const { data } = await api.get(`/taches/${tache.id}`)
    currentTache.value = data.data
    showViewModal.value = true
  } catch (err) {
    toast.error('Erreur de chargement')
  }
}

function handleEditTask(tache) {
  currentTache.value = tache
  showEditModal.value = true
}

async function handleTaskSaved() {
  showEditModal.value = false
  await loadResponsableTasks()
  toast.success("Tâche enregistrée !")
}

onMounted(() => {
  loadResponsableTasks()
})
</script>