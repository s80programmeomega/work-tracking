<!-- resources/js/pages/ValidationTaches.vue -->
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
                Examinez et validez les résultats soumis par les collaborateurs
              </p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <!-- Filtres -->
            <select v-model="filters.niveau" @change="loadPendingValidations" class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
              <option value="all">Tous les niveaux</option>
              <option value="n1">En attente N1</option>
              <option value="n2">En attente N2</option>
            </select>

            <select v-model="filters.activite_id" @change="loadPendingValidations" class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
              <option value="">Toutes les activités</option>
              <option v-for="act in activites" :key="act.id" :value="act.id">
                {{ act.nom }}
              </option>
            </select>

            <button @click="loadPendingValidations" :disabled="loading" class="p-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors disabled:opacity-50">
              <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-4 gap-4">
          <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">En attente N1</p>
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-1">{{ stats.pending_n1 }}</p>
          </div>
          <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">En attente N2</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ stats.pending_n2 }}</p>
          </div>
          <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">Validés cette semaine</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ stats.validated_week }}</p>
          </div>
          <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">Rejetés</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ stats.rejected }}</p>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-500 mx-auto mb-4"></div>
          <p class="text-gray-600 dark:text-gray-400">Chargement des validations...</p>
        </div>
      </div>

      <!-- Liste des résultats en attente -->
      <div v-else class="space-y-4">
        <div v-for="resultat in pendingValidations" :key="resultat.id" class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] overflow-hidden">
          <div class="p-6">
            <div class="flex items-start justify-between gap-4">
              <!-- Informations de base -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 mb-3">
                  <span class="px-3 py-1 text-xs font-medium rounded-full" :class="getNiveauBadgeClass(resultat)">
                    {{ getNiveauLabel(resultat) }}
                  </span>
                  <span class="text-sm text-gray-500 dark:text-gray-400">
                    Soumis le {{ formatDate(resultat.soumis_le) }}
                  </span>
                </div>

                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                  {{ resultat.tache.titre }}
                </h3>

                <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400 mb-3">
                  <span class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    {{ resultat.user.nom }}
                  </span>
                  <span class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    {{ resultat.tache.activite.nom }}
                  </span>
                  <span class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    {{ resultat.taux_realisation }}% réalisé
                  </span>
                </div>

                <!-- Résultats -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                  <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Résultats attendus</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900 p-3 rounded-lg">
                      {{ resultat.resultats_attendus }}
                    </p>
                  </div>
                  <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Résultats obtenus</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900 p-3 rounded-lg">
                      {{ resultat.resultats_obtenus }}
                    </p>
                  </div>
                </div>

                <!-- Documents -->
                <div v-if="resultat.documents.length > 0" class="mb-4">
                  <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Documents justificatifs</h4>
                  <div class="flex flex-wrap gap-2">
                    <a
                      v-for="doc in resultat.documents"
                      :key="doc.id"
                      :href="doc.url"
                      target="_blank"
                      class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                      {{ doc.nom }}
                    </a>
                  </div>
                </div>

                <!-- Commentaires supplémentaires -->
                <div v-if="resultat.difficultes_rencontrees || resultat.observations" class="space-y-2">
                  <div v-if="resultat.difficultes_rencontrees">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Difficultés rencontrées</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ resultat.difficultes_rencontrees }}</p>
                  </div>
                  <div v-if="resultat.observations">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Observations</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ resultat.observations }}</p>
                  </div>
                </div>
              </div>

              <!-- Actions de validation -->
              <div class="flex flex-col gap-3 min-w-[200px]">
                <button
                  @click="validateResultat(resultat, 'validate')"
                  :disabled="validating"
                  class="w-full px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 disabled:opacity-50 transition-colors flex items-center justify-center gap-2"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  Valider
                </button>

                <button
                  @click="openRejectModal(resultat)"
                  :disabled="validating"
                  class="w-full px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 disabled:opacity-50 transition-colors flex items-center justify-center gap-2"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                  Refuser
                </button>

                <button
                  @click="viewTacheDetails(resultat.tache_id)"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                >
                  Voir la tâche
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty state -->
        <div v-if="pendingValidations.length === 0" class="text-center py-12">
          <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Aucune validation en attente</h3>
          <p class="text-gray-500 dark:text-gray-400">Tous les résultats ont été traités.</p>
        </div>
      </div>
    </div>

    <!-- Modal de rejet -->
    <RejectModal
      v-if="showRejectModal"
      :resultat="currentResultat"
      :niveau="currentNiveau"
      @close="showRejectModal = false"
      @rejected="handleRejected"
    />
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import RejectModal from '@/components/taches/RejectModal.vue'
import api from '@/api/axios'

// State
const loading = ref(false)
const validating = ref(false)
const activites = ref([])
const pendingValidations = ref([])
const showRejectModal = ref(false)
const currentResultat = ref(null)
const currentNiveau = ref('n1')

const filters = ref({
  niveau: 'all',
  activite_id: ''
})

const stats = ref({
  pending_n1: 0,
  pending_n2: 0,
  validated_week: 0,
  rejected: 0
})

// Methods
async function loadActivites() {
  try {
    const { data } = await api.get('/activites/mes-activites')
    activites.value = data.data || data || []
  } catch (err) {
    console.error('Erreur chargement activités:', err)
  }
}

async function loadPendingValidations() {
  loading.value = true
  try {
    const params = {}
    if (filters.value.niveau !== 'all') params.niveau = filters.value.niveau
    if (filters.value.activite_id) params.activite_id = filters.value.activite_id

    const { data } = await api.get('/taches/resultats/en-attente', { params })
    
    pendingValidations.value = data.data || []
    stats.value = data.stats || stats.value
  } catch (err) {
    console.error('Erreur chargement validations:', err)
  } finally {
    loading.value = false
  }
}

async function validateResultat(resultat, action) {
  validating.value = true
  try {
    const niveau = resultat.valide_par_n1 ? 'n2' : 'n1'
    const url = `/taches/resultats-individuels/${resultat.id}/validate-${niveau}`

    await api.post(url, {
      commentaire: 'Résultat validé avec succès'
    })

    await loadPendingValidations()
  } catch (err) {
    console.error('Erreur validation:', err)
    alert(err.response?.data?.message || 'Erreur lors de la validation')
  } finally {
    validating.value = false
  }
}

function openRejectModal(resultat) {
  currentResultat.value = resultat
  currentNiveau.value = resultat.valide_par_n1 ? 'n2' : 'n1'
  showRejectModal.value = true
}

async function handleRejected() {
  showRejectModal.value = false
  await loadPendingValidations()
}

function viewTacheDetails(tacheId) {
  // Implémenter la navigation vers les détails de la tâche
  window.open(`/taches/${tacheId}`, '_blank')
}

// Helpers
function getNiveauBadgeClass(resultat) {
  if (resultat.valide_par_n1) {
    return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
  }
  return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300'
}

function getNiveauLabel(resultat) {
  if (resultat.valide_par_n1) {
    return 'En attente N2 (Responsable Projet)'
  }
  return 'En attente N1 (Responsable Activité)'
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Lifecycle
onMounted(async () => {
  await loadActivites()
  await loadPendingValidations()
})
</script>