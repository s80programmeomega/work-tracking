<!-- resources/js/pages/TachesAssignees.vue -->
<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header Premium -->
      <div class="rounded-2xl border border-gray-200 bg-gradient-to-br from-white to-gray-50 dark:from-gray-900 dark:to-gray-800 dark:border-gray-800 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mes Tâches Assignées</h1>
              <p class="text-gray-500 dark:text-gray-400">Gérez votre avancement personnel de manière indépendante</p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <!-- Bouton refresh -->
            <button 
              @click="loadAssignedTasks" 
              :disabled="loading" 
              class="p-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" 
              title="Actualiser"
            >
              <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </button>

            <!-- Toggle vue -->
            <div class="flex gap-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg">
              <button 
                @click="currentView = 'kanban'" 
                :class="['px-3 py-2 rounded-md transition-all flex items-center gap-2 text-sm', 
                  currentView === 'kanban' ? 'bg-white dark:bg-gray-700 shadow-sm text-brand-600 dark:text-brand-400' : 'text-gray-600 dark:text-gray-400']"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                </svg>
                Kanban
              </button>
              <button 
                @click="currentView = 'grouped'" 
                :class="['px-3 py-2 rounded-md transition-all flex items-center gap-2 text-sm', 
                  currentView === 'grouped' ? 'bg-white dark:bg-gray-700 shadow-sm text-brand-600 dark:text-brand-400' : 'text-gray-600 dark:text-gray-400']"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Par Activité
              </button>
            </div>
          </div>
        </div>

        <!-- Statistiques avec design premium -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
          <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-700 shadow-sm group hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total assignées</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ stats.total }}</p>
              </div>
              <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
              </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-gray-400 to-gray-500"></div>
          </div>

          <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-700 shadow-sm group hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">À faire</p>
                <p class="text-2xl font-bold text-slate-600 dark:text-slate-400 mt-1">{{ stats.a_faire }}</p>
              </div>
              <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-slate-400 to-slate-500"></div>
          </div>

          <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-700 shadow-sm group hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">En cours</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ stats.en_cours }}</p>
              </div>
              <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-400 to-blue-500"></div>
          </div>

          <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-700 shadow-sm group hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Terminées</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ stats.termine }}</p>
              </div>
              <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-green-400 to-green-500"></div>
          </div>

          <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-700 shadow-sm group hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">En retard</p>
                <p class="text-2xl font-bold mt-1" :class="stats.overdue > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-400'">{{ stats.overdue }}</p>
              </div>
              <div class="w-12 h-12 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" :class="stats.overdue > 0 ? 'bg-red-100 dark:bg-red-900/30' : 'bg-gray-100 dark:bg-gray-700'">
                <svg class="w-6 h-6" :class="stats.overdue > 0 ? 'text-red-500 animate-pulse' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
              </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1" :class="stats.overdue > 0 ? 'bg-gradient-to-r from-red-400 to-red-500' : 'bg-gray-300'"></div>
          </div>
        </div>
      </div>

      <!-- Message d'erreur -->
      <div v-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <div class="flex items-center gap-3">
          <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
          <p class="text-red-800 dark:text-red-300">{{ error }}</p>
        </div>
      </div>

      <!-- Vue Kanban Personnel -->
      <div v-if="currentView === 'kanban' && !loading" class="grid grid-cols-1 md:grid-cols-3 gap-6">
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

      <!-- Vue groupée par activité -->
      <div v-else-if="currentView === 'grouped' && !loading" class="space-y-6">
        <div v-if="tasksByActivite.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
          Aucune tâche assignée pour le moment
        </div>
        
        <div v-for="group in tasksByActivite" :key="group.activite.id" class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] overflow-hidden">
          <!-- Header du groupe -->
          <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-900 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-brand-100 dark:bg-brand-900/30 flex items-center justify-center">
                  <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                  </svg>
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900 dark:text-white">{{ group.activite.nom }}</h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400">{{ group.activite.projet_nom }}</p>
                </div>
              </div>
              <span class="px-3 py-1 text-sm font-medium bg-brand-100 dark:bg-brand-900/30 text-brand-700 dark:text-brand-300 rounded-full">
                {{ group.taches.length }} tâche{{ group.taches.length > 1 ? 's' : '' }}
              </span>
            </div>
          </div>

          <!-- Mini Kanban par activité -->
          <div class="p-4 grid grid-cols-3 gap-3">
            <div v-for="col in kanbanColumns" :key="col.statut" class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3">
              <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                <span>{{ col.icon }}</span>
                <span>{{ col.title }}</span>
                <span class="ml-auto text-xs bg-gray-200 dark:bg-gray-700 px-2 py-0.5 rounded">
                  {{ group.taches.filter(t => getMyStatus(t) === col.statut).length }}
                </span>
              </h4>
              <div class="space-y-2">
                <TacheCardPersonal
                  v-for="tache in group.taches.filter(t => getMyStatus(t) === col.statut)"
                  :key="tache.id"
                  :tache="tache"
                  compact
                  @view="handleViewTask"
                  @move="handleMoveMyCard"
                  @submit-result="handleSubmitResult"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading state -->
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="text-center">
          <svg class="animate-spin h-10 w-10 text-brand-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <p class="text-gray-600 dark:text-gray-400">Chargement de vos tâches...</p>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <TacheDetailModal
      v-if="showViewModal"
      :tache="currentTache"
      @close="showViewModal = false"
      @move-my-card="handleMoveMyCard"
      @submit-result="handleSubmitResult"
      @task-updated="handleTaskUpdated"
    />

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
import AdminLayout from '@/components/layout/AdminLayout.vue'
import KanbanColumnPersonal from '@/components/taches/KanbanColumnPersonal.vue'
import TacheCardPersonal from '@/components/taches/TacheCardPersonal.vue'
import TacheDetailModal from '@/components/taches/TacheDetailModal.vue'
import SubmitResultModal from '@/components/taches/SubmitResultModal.vue'
import api from '@/api/axios'

// State
const taches = ref([])
const loading = ref(false)
const error = ref(null)
const currentView = ref('kanban')
const showViewModal = ref(false)
const showSubmitResultModal = ref(false)
const currentTache = ref(null)

// Colonnes Kanban
const kanbanColumns = [
  { statut: 'a_faire', title: 'À faire', color: '#6B7280', icon: '📋' },
  { statut: 'en_cours', title: 'En cours', color: '#3B82F6', icon: '🔄' },
  { statut: 'termine', title: 'Terminé', color: '#10B981', icon: '✅' }
]

// Computed
const stats = computed(() => {
  const all = taches.value
  return {
    total: all.length,
    a_faire: all.filter(t => getMyStatus(t) === 'a_faire').length,
    en_cours: all.filter(t => getMyStatus(t) === 'en_cours').length,
    termine: all.filter(t => getMyStatus(t) === 'termine').length,
    overdue: all.filter(t => t.is_overdue && getMyStatus(t) !== 'termine').length
  }
})

const tasksByActivite = computed(() => {
  const grouped = {}
  taches.value.forEach(tache => {
    const activiteId = tache.activite_id
    if (!grouped[activiteId]) {
      grouped[activiteId] = {
        activite: tache.activite,
        taches: []
      }
    }
    grouped[activiteId].taches.push(tache)
  })
  return Object.values(grouped).sort((a, b) => a.activite.nom.localeCompare(b.activite.nom))
})

// Methods
function getMyStatus(tache) {
  // ✅ CORRECTION: Utiliser my_status.statut en priorité
  if (tache.my_status && tache.my_status.statut) {
    return tache.my_status.statut
  }
  
  // Fallback sur le statut global
  return tache.statut
}

function getMyTasksByStatus(statut) {
  return taches.value.filter(t => getMyStatus(t) === statut)
}

async function loadAssignedTasks() {
  loading.value = true
  error.value = null

  try {
    const { data } = await api.get('/taches/assignees')
    taches.value = data.data || []
    
    console.log('✅ Tâches assignées chargées:', {
      total: taches.value.length,
      sample: taches.value.slice(0, 2).map(t => ({
        id: t.id,
        titre: t.titre,
        statut_global: t.statut,
        my_status: t.my_status
      }))
    })
  } catch (err) {
    console.error('❌ Erreur chargement:', err)
    error.value = err.response?.data?.message || 'Impossible de charger les tâches assignées'
  } finally {
    loading.value = false
  }
}

async function handleMoveMyCard({ tache, newStatut, progression, notes }) {
  try {
    console.log('🔄 Déplacement carte:', {
      tache_id: tache.id,
      old_status: getMyStatus(tache),
      new_status: newStatut,
      progression
    })

    const { data } = await api.post(`/taches/${tache.id}/move-my-card`, {
      statut: newStatut,
      progression,
      notes_personnelles: notes
    })

    console.log('✅ Carte déplacée:', data.changes)
    
    // Recharger les tâches pour avoir les données à jour
    await loadAssignedTasks()
    
  } catch (err) {
    console.error('❌ Erreur déplacement:', err)
    error.value = err.response?.data?.message || 'Erreur lors du déplacement'
    
    // Recharger quand même pour restaurer l'état correct
    await loadAssignedTasks()
  }
}

function handleSubmitResult(tache) {
  currentTache.value = tache
  showSubmitResultModal.value = true
}

async function handleResultSubmitted() {
  showSubmitResultModal.value = false
  await loadAssignedTasks()
  error.value = null
  // Afficher une notification de succès
  alert('✅ Résultat soumis avec succès !')
}

async function handleViewTask(tache) {
  try {
    const { data } = await api.get(`/taches/${tache.id}`)
    currentTache.value = data.data
    showViewModal.value = true
  } catch (err) {
    console.error('Erreur:', err)
    error.value = 'Erreur lors du chargement de la tâche'
  }
}

function handleTaskUpdated() {
  loadAssignedTasks()
}

// Lifecycle
onMounted(() => {
  loadAssignedTasks()
})
</script>