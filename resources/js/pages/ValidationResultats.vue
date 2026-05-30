<!-- resources/js/pages/ValidationResultats.vue -->
<template>
  <AdminLayout>
    <div class="min-h-screen bg-gray-50/50 dark:bg-gray-900/50">
      <!-- Header Premium -->
      <div class="bg-white dark:bg-gray-900 border-b border-gray-200/80 dark:border-gray-800/80 ">
        <div class="max-w-7xl mx-auto px-6 py-8">
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-5">
              <div class="relative">
                <div
                  class="w-16 h-16 rounded-3 flex items-center justify-center shadow-green-500/25">
                  <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div class="absolute -top-1 -right-1 w-6 h-6 rounded-full flex items-center justify-center ">
                  <span class="text-xs font-bold text-white">{{ countsN1.pending + countsN2.pending }}</span>
                </div>
              </div>
              <div>
                <h1
                  class="text-3xl font-bold bg-clip-text text-transparent"> Validation des Résultats
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2 text-lg">
                  Valider ou rejeter les résultats soumis par les membres
                </p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <button @click="loadData" :disabled="loading"
                class="p-3 rounded-3 border border-gray-300/80 dark:border-gray-700/80 bg-white/80 dark:bg-gray-800/80 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200 disabled:opacity-50 "
                title="Actualiser">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" :class="{ 'animate-spin': loading }" fill="none"
                  stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Enhanced Tabs -->
          <div class="flex gap-1 mt-8 bg-gray-100/80 dark:bg-gray-800/80 rounded-3 p-1.5 ">
            <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
              class="flex items-center gap-3 px-6 py-3.5 text-sm font-semibold transition-all duration-200 rounded-3 relative group"
              :class="activeTab === tab.id
                ? 'text-white ' + tab.activeGradient
                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-white/50 dark:hover:bg-gray-700/50'">
              <div class="flex items-center gap-2.5">
                <component :is="tab.icon" class="w-4 h-4" />
                <span>{{ tab.label }}</span>
                <span v-if="tab.count > 0"
                  class="px-2 py-1 text-xs font-bold bg-white/20 rounded-full min-w-[24px] text-center">
                  {{ tab.count }}
                </span>
              </div>
            </button>
          </div>
        </div>
      </div>

      <!-- Stats Cards Grid -->
      <div class="max-w-7xl mx-auto px-6 py-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
          <div v-for="stat in stats" :key="stat.id"
            class="bg-white/80 dark:bg-gray-800/80 rounded-3 p-6 border border-gray-200/50 dark:border-gray-700/50 transition-all duration-200 hover:border-gray-300/80 dark:hover:border-gray-600/80">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ stat.label }}</p>
                <p class="text-3xl font-bold mt-2" :class="stat.color">{{ stat.value }}</p>
              </div>
              <div class="w-12 h-12 rounded-3 flex items-center justify-center" :class="stat.bgColor">
                <component :is="stat.icon" class="w-6 h-6" :class="stat.iconColor" />
              </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-200/50 dark:border-gray-700/50">
              <p class="text-xs text-gray-500 dark:text-gray-400" v-html="stat.description"></p>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center h-64">
          <div class="text-center">
            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-green-500 mx-auto mb-4"></div>
            <p class="text-gray-600 dark:text-gray-400 text-lg">Chargement des résultats...</p>
          </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error"
          class="rounded-3 border border-red-200 bg-red-50/80 dark:bg-red-900/20 dark:border-red-800/50 p-8 ">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-3 bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
              <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <p class="text-red-700 dark:text-red-300 font-medium">{{ error }}</p>
              <button @click="loadData" class="text-red-600 dark:text-red-400 text-sm mt-1 hover:underline">
                Réessayer
              </button>
            </div>
          </div>
        </div>

        <!-- Results Grid - 2 columns on desktop -->
        <div v-else class="space-y-6">
          <!-- Empty State -->
          <div v-if="currentResults.length === 0"
            class="rounded-3 border-2 border-dashed border-gray-300/80 dark:border-gray-700/80 bg-white/50 dark:bg-gray-800/50 p-16 text-center ">
            <div
              class="w-24 h-24 mx-auto mb-6 rounded-3 flex items-center justify-center">
              <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
              Aucun résultat en attente
            </h3>
            <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto">
              Tous les résultats ont été traités pour le moment. Les nouvelles soumissions apparaîtront ici
              automatiquement.
            </p>
          </div>

          <!-- Results Grid -->
          <div v-else class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <ResultatCard v-for="resultat in currentResults" :key="resultat.id" :resultat="resultat" :level="activeTab"
              :expanded="expandedCards.includes(resultat.id)" @toggle="toggleCard(resultat.id)"
              @validate="handleValidate" @reject="handleReject" @view-details="viewResultatDetails" />
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <ValidationModal v-if="showValidationModal" :resultat="selectedResultat" :action="validationAction"
      :level="validationLevel" @close="showValidationModal = false" @confirmed="handleValidationConfirmed" />

    <ResultatDetailModal v-if="showDetailModal && selectedResultatForDetail" :resultat="selectedResultatForDetail"
      @close="showDetailModal = false" />

  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch, h } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import ResultatCard from '@/components/taches/resultats/ResultatCard.vue'
import ValidationModal from '@/components/taches/resultats/ValidationModal.vue'
import ResultatDetailModal from '@/components/taches/resultats/ResultatDetailModal.vue'
import api from '@/api/axios'
import { useToast } from 'vue-toastification'
import { useRealtimeRefresh } from '@/composables/useRealtimeRefresh'

const toast = useToast()

// State
const activeTab = ref('n1')
const loading = ref(false)
const error = ref(null)
const resultatsN1 = ref([])
const resultatsN2 = ref([])
const countsN1 = ref({ pending: 0 })
const countsN2 = ref({ pending: 0 })
const expandedCards = ref([])
const showValidationModal = ref(false)
const showDetailModal = ref(false)
const selectedResultat = ref(null)
const selectedResultatForDetail = ref(null)
const validationAction = ref('validate')
const validationLevel = ref('n1')
const historyItems = ref([])

// Enhanced Tabs Configuration
const tabs = computed(() => [
  {
    id: 'n1',
    label: 'Validation N1',
    icon: 'ClockIcon',
    count: countsN1.value.pending,
    activeGradient: ' bg-warning-500',
    iconComponent: ClockIcon
  },
  {
    id: 'n2',
    label: 'Validation N2',
    icon: 'CheckCircleIcon',
    count: countsN2.value.pending,
    activeGradient: ' bg-brand-500',
    iconComponent: CheckCircleIcon
  },
  {
    id: 'history',
    label: 'Historique',
    icon: 'ArchiveIcon',
    count: 0,
    activeGradient: ' bg-purple-500',
    iconComponent: ArchiveIcon
  }
])

// Enhanced Stats
const stats = computed(() => [
  {
    id: 'pendingN1',
    label: 'En attente N1',
    value: countsN1.value.pending,
    color: 'text-orange-600 dark:text-orange-400',
    bgColor: 'bg-orange-100 dark:bg-orange-900/30',
    icon: ClockIcon,
    iconColor: 'text-orange-600 dark:text-orange-400',
    description: 'En attente de votre validation'
  },
  {
    id: 'pendingN2',
    label: 'En attente N2',
    value: countsN2.value.pending,
    color: 'text-blue-600 dark:text-blue-400',
    bgColor: 'bg-blue-100 dark:bg-blue-900/30',
    icon: CheckCircleIcon,
    iconColor: 'text-blue-600 dark:text-blue-400',
    description: 'En attente validation finale'
  },
  {
    id: 'today',
    label: 'Validés aujourd\'hui',
    value: todayValidated.value,
    color: 'text-green-600 dark:text-green-400',
    bgColor: 'bg-green-100 dark:bg-green-900/30',
    icon: ChartBarIcon,
    iconColor: 'text-green-600 dark:text-green-400',
    description: `Résultats validés aujourd'hui`
  },
  {
    id: 'total',
    label: 'Total traités',
    value: totalProcessed.value,
    color: 'text-gray-900 dark:text-white',
    bgColor: 'bg-gray-100 dark:bg-gray-900/30',
    icon: DocumentChartBarIcon,
    iconColor: 'text-gray-600 dark:text-gray-400',
    description: 'Total des validations effectuées'
  }
])

// Computed
const currentResults = computed(() => {
  if (activeTab.value === 'n1') return resultatsN1.value
  if (activeTab.value === 'n2') return resultatsN2.value
  return []
})

const todayValidated = computed(() => {
  const today = new Date().toDateString()
  return historyItems.value.filter(item => {
    const itemDate = new Date(item.validated_at || item.rejected_at).toDateString()
    return itemDate === today && item.status === 'validated'
  }).length
})

const totalProcessed = computed(() => {
  return countsN1.value.validated + countsN2.value.validated + countsN1.value.rejected + countsN2.value.rejected
})

// Methods (rest of the methods remain the same as original)
async function loadData() {
  loading.value = true
  error.value = null

  try {
    const { data } = await api.get('/evaluations/resultats/en-attente')

    resultatsN1.value = data.data.pending_n1 || []
    resultatsN2.value = data.data.pending_n2 || []
    countsN1.value = {
      pending: data.data.counts.n1 || 0,
      validated: 0,
      rejected: 0
    }
    countsN2.value = {
      pending: data.data.counts.n2 || 0,
      validated: 0,
      rejected: 0
    }

    await loadHistory()

  } catch (err) {
    console.error('❌ Erreur chargement:', err)
    error.value = err.response?.data?.message || 'Erreur lors du chargement des résultats'
    toast.error(error.value)
  } finally {
    loading.value = false
  }
}

async function loadHistory() {
  try {
    const { data } = await api.get('/evaluations/history')
    historyItems.value = data.data || []
  } catch (err) {
    console.error('Erreur chargement historique:', err)
  }
}

function toggleCard(resultatId) {
  const index = expandedCards.value.indexOf(resultatId)
  if (index > -1) {
    expandedCards.value.splice(index, 1)
  } else {
    expandedCards.value.push(resultatId)
  }
}

function handleValidate(resultat, level) {
  selectedResultat.value = resultat
  validationAction.value = 'validate'
  validationLevel.value = level
  showValidationModal.value = true
}

function handleReject(resultat, level) {
  selectedResultat.value = resultat
  validationAction.value = 'reject'
  validationLevel.value = level
  showValidationModal.value = true
}

async function handleValidationConfirmed(data) {
  const { resultat, action, level, commentaire } = data

  try {
    const endpoint = action === 'validate'
      ? `/evaluations/resultats-individuels/${resultat.id}/validate-${level}`
      : `/evaluations/resultats/${resultat.id}/reject`

    const payload = action === 'reject'
      ? { commentaire, level }
      : { commentaire }

    await api.post(endpoint, payload)

    const message = action === 'validate'
      ? `✅ Résultat validé avec succès (${level.toUpperCase()})`
      : `❌ Résultat rejeté. Le statut de l'utilisateur a été réinitialisé.`

    toast.success(message)
    await loadData()
    showValidationModal.value = false
    selectedResultat.value = null

  } catch (err) {
    console.error('❌ Erreur validation:', err)
    const errorMsg = err.response?.data?.message || 'Erreur lors de la validation'
    toast.error(errorMsg)
  }
}

function viewResultatDetails(resultat) {
  // console.log('resultat detail',resultat);

  selectedResultatForDetail.value = resultat
  showDetailModal.value = true
}

// Composants d'icônes — fonctions de rendu (h()) car le build runtime de
// Vue (utilisé par Vite) ne compile pas les chaînes template: à la volée.
const strokeIcon = (d) => ({
  render: () => h(
    'svg',
    { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' },
    [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': 2, d })]
  ),
})

const ClockIcon = strokeIcon('M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z')
const CheckCircleIcon = strokeIcon('M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z')
const ArchiveIcon = strokeIcon('M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4')
const ChartBarIcon = strokeIcon('M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z')
const DocumentChartBarIcon = strokeIcon('M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z')

// Watchers & Lifecycle
watch(activeTab, (newTab) => {
  if (newTab === 'history') {
    loadHistory()
  }
})

useRealtimeRefresh({
  onResultatChanged: () => loadData(),
  onPendingChanged: () => loadData(),
})

onMounted(() => {
  loadData()
})
</script>

<style scoped>
/* Custom scrollbar and animations */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

.dark ::-webkit-scrollbar-track {
  background: #374151;
}

.dark ::-webkit-scrollbar-thumb {
  background: #6b7280;
}

.dark ::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

/* Smooth transitions for all interactive elements */
* {
  transition-property: color, background-color, border-color, transform, box-shadow;
  transition-duration: 200ms;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>