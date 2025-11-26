<!-- resources/js/pages/ValidationResultats.vue -->
<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="rounded-2xl border border-gray-200 bg-gradient-to-br from-white to-gray-50 dark:from-gray-900 dark:to-gray-800 dark:border-gray-800 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-lg">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Validation des Résultats</h1>
              <p class="text-gray-500 dark:text-gray-400">
                Valider ou rejeter les résultats soumis par les membres
              </p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <button @click="loadData" :disabled="loading" class="p-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors disabled:opacity-50" title="Actualiser">
              <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
          <button
            @click="activeTab = 'n1'"
            class="px-4 py-2 font-medium transition-colors relative"
            :class="activeTab === 'n1' ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
          >
            Validation N1
            <span v-if="countsN1.pending > 0" class="ml-2 px-2 py-0.5 text-xs font-bold bg-orange-500 text-white rounded-full">
              {{ countsN1.pending }}
            </span>
            <div v-if="activeTab === 'n1'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-green-600"></div>
          </button>

          <button
            @click="activeTab = 'n2'"
            class="px-4 py-2 font-medium transition-colors relative"
            :class="activeTab === 'n2' ? 'text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
          >
            Validation N2
            <span v-if="countsN2.pending > 0" class="ml-2 px-2 py-0.5 text-xs font-bold bg-orange-500 text-white rounded-full">
              {{ countsN2.pending }}
            </span>
            <div v-if="activeTab === 'n2'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600"></div>
          </button>

          <button
            @click="activeTab = 'history'"
            class="px-4 py-2 font-medium transition-colors relative"
            :class="activeTab === 'history' ? 'text-purple-600 dark:text-purple-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
          >
            Historique
            <div v-if="activeTab === 'history'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-purple-600"></div>
          </button>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-4 gap-4 mt-4">
          <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">En attente N1</p>
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-1">{{ countsN1.pending }}</p>
          </div>
          <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">En attente N2</p>
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-1">{{ countsN2.pending }}</p>
          </div>
          <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">Validés aujourd'hui</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ todayValidated }}</p>
          </div>
          <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total traités</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ totalProcessed }}</p>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-500 mx-auto mb-4"></div>
          <p class="text-gray-600 dark:text-gray-400">Chargement des résultats...</p>
        </div>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="rounded-2xl border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-6">
        <p class="text-red-700 dark:text-red-300">{{ error }}</p>
      </div>

      <!-- Tab Content: Validation N1 -->
      <div v-else-if="activeTab === 'n1'" class="space-y-4">
        <div v-if="resultatsN1.length === 0" class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-12 text-center">
          <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-gray-500 dark:text-gray-400 text-lg">Aucun résultat en attente de validation N1</p>
          <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">Tous les résultats ont été traités</p>
        </div>

        <ResultatCard
          v-for="resultat in resultatsN1"
          :key="resultat.id"
          :resultat="resultat"
          :level="'n1'"
          :expanded="expandedCards.includes(resultat.id)"
          @toggle="toggleCard(resultat.id)"
          @validate="handleValidate"
          @reject="handleReject"
          @view-details="viewResultatDetails"
        />
      </div>

      <!-- Tab Content: Validation N2 -->
      <div v-else-if="activeTab === 'n2'" class="space-y-4">
        <div v-if="resultatsN2.length === 0" class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-12 text-center">
          <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-gray-500 dark:text-gray-400 text-lg">Aucun résultat en attente de validation N2</p>
          <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">Tous les résultats ont été traités</p>
        </div>

        <ResultatCard
          v-for="resultat in resultatsN2"
          :key="resultat.id"
          :resultat="resultat"
          :level="'n2'"
          :expanded="expandedCards.includes(resultat.id)"
          @toggle="toggleCard(resultat.id)"
          @validate="handleValidate"
          @reject="handleReject"
          @view-details="viewResultatDetails"
        />
      </div>

      <!-- Tab Content: History -->
      <div v-else-if="activeTab === 'history'" class="space-y-4">
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-6">
          <!-- Filtres historique -->
          <div class="grid grid-cols-3 gap-4 mb-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statut</label>
              <select v-model="historyFilters.status" @change="loadHistory" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
                <option value="">Tous</option>
                <option value="validated">Validés</option>
                <option value="rejected">Rejetés</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Niveau</label>
              <select v-model="historyFilters.level" @change="loadHistory" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
                <option value="">Tous</option>
                <option value="n1">N1</option>
                <option value="n2">N2</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Période</label>
              <select v-model="historyFilters.period" @change="loadHistory" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
                <option value="today">Aujourd'hui</option>
                <option value="week">Cette semaine</option>
                <option value="month">Ce mois</option>
                <option value="all">Tout</option>
              </select>
            </div>
          </div>

          <!-- Liste historique -->
          <div class="space-y-3">
            <div v-if="historyItems.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
              Aucun historique pour cette période
            </div>

            <div v-for="item in historyItems" :key="item.id" class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-2">
                    <span v-if="item.status === 'validated'" class="flex items-center gap-1 text-sm font-medium text-green-600 dark:text-green-400">
                      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                      </svg>
                      Validé {{ item.level.toUpperCase() }}
                    </span>
                    <span v-else class="flex items-center gap-1 text-sm font-medium text-red-600 dark:text-red-400">
                      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                      </svg>
                      Rejeté {{ item.level.toUpperCase() }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatDateTime(item.validated_at || item.rejected_at) }}</span>
                  </div>

                  <p class="font-medium text-gray-900 dark:text-white mb-1">{{ item.tache?.titre }}</p>
                  <p class="text-sm text-gray-600 dark:text-gray-400">Par: {{ item.user?.nom }} • Validé par: {{ item.validator?.nom }}</p>
                  
                  <p v-if="item.commentaire" class="text-sm text-gray-600 dark:text-gray-400 mt-2 p-2 bg-gray-50 dark:bg-gray-800 rounded">
                    💬 {{ item.commentaire }}
                  </p>
                </div>

                <button @click="viewResultatDetails(item)" class="px-3 py-1.5 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors">
                  Voir détails
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Validation/Rejet -->
    <ValidationModal
      v-if="showValidationModal"
      :resultat="selectedResultat"
      :action="validationAction"
      :level="validationLevel"
      @close="showValidationModal = false"
      @confirmed="handleValidationConfirmed"
    />

    <!-- Modal Détails Résultat -->
    <ResultatDetailModal
      v-if="showDetailModal"
      :resultat="selectedResultatForDetail"
      @close="showDetailModal = false"
    />
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import ResultatCard from '@/components/taches/resultats/ResultatCard.vue'
import ValidationModal from '@/components/taches/resultats/ValidationModal.vue'
import ResultatDetailModal from '@/components/taches/resultats/ResultatDetailModal.vue'
import api from '@/api/axios'
import { useToast } from 'vue-toastification'

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
const historyFilters = ref({
  status: '',
  level: '',
  period: 'week'
})

// Computed
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

// Methods
async function loadData() {
  loading.value = true
  error.value = null

  try {
    // Charger résultats en attente
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

    // Charger historique
    await loadHistory()

    console.log('✅ Données chargées:', {
      n1: resultatsN1.value.length,
      n2: resultatsN2.value.length
    })
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
    const params = {
      status: historyFilters.value.status,
      level: historyFilters.value.level,
      period: historyFilters.value.period
    }

    const { data } = await api.get('/evaluations/history', { params })
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
      ? `/taches/resultats-individuels/${resultat.id}/validate-${level}`
      : `/taches/${resultat.tache.id}/resultats/${resultat.id}/reject`

    const payload = action === 'reject' 
      ? { commentaire, level }
      : { commentaire }

    await api.post(endpoint, payload)

    // Afficher message de succès
    const message = action === 'validate'
      ? `✅ Résultat validé avec succès (${level.toUpperCase()})`
      : `❌ Résultat rejeté. Le statut de l'utilisateur a été réinitialisé.`
    
    toast.success(message)

    // Recharger les données
    await loadData()

    // Fermer le modal
    showValidationModal.value = false
    selectedResultat.value = null

  } catch (err) {
    console.error('❌ Erreur validation:', err)
    const errorMsg = err.response?.data?.message || 'Erreur lors de la validation'
    toast.error(errorMsg)
  }
}

function viewResultatDetails(resultat) {
  selectedResultatForDetail.value = resultat
  showDetailModal.value = true
}

function formatDateTime(date) {
  if (!date) return ''
  return new Date(date).toLocaleString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Watchers
watch(activeTab, (newTab) => {
  if (newTab === 'history') {
    loadHistory()
  }
})

// Lifecycle
onMounted(() => {
  loadData()
})
</script>

<style scoped>
/* Custom scrollbar */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #555;
}

.dark ::-webkit-scrollbar-track {
  background: #1f2937;
}

.dark ::-webkit-scrollbar-thumb {
  background: #4b5563;
}

.dark ::-webkit-scrollbar-thumb:hover {
  background: #6b7280;
}
</style>