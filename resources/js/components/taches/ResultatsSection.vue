<!-- resources/js/components/taches/ResultatsSection.vue -->
<template>
  <div class="space-y-6">
    
    <!-- Header avec statistiques -->
    <div class="flex flex-col gap-4">
      <!-- Titre et actions -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Résultats de la tâche
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            {{ resultats.length }} résultat{{ resultats.length > 1 ? 's' : '' }} enregistré{{ resultats.length > 1 ? 's' : '' }}
          </p>
        </div>
        
        <div class="flex items-center gap-2">
          <!-- Filtre -->
          <div class="relative" v-if="resultats.length > 0">
            <button
              @click="showFilters = !showFilters"
              class="px-4 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all flex items-center gap-2"
              :class="{ 'bg-brand-50 dark:bg-brand-900/20 border-brand-500': activeFilters.length > 0 }"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
              </svg>
              <span>Filtrer</span>
              <span v-if="activeFilters.length > 0" class="px-2 py-0.5 text-xs rounded-full bg-brand-500 text-white">
                {{ activeFilters.length }}
              </span>
            </button>
            
            <!-- Dropdown filtres -->
            <transition name="fade-pop">
            <div v-if="showFilters" class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 z-10">
              <div class="p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-2">
                  <h4 class="font-semibold text-gray-900 dark:text-white">Filtres</h4>
                  <button @click="resetFilters" class="text-xs text-brand-600 hover:text-brand-700">
                    Réinitialiser
                  </button>
                </div>
                
                <!-- Filtre par statut -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Statut
                  </label>
                  <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input type="checkbox" v-model="filters.draft" class="rounded text-brand-500">
                      <span class="text-sm text-gray-700 dark:text-gray-300">Brouillons</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input type="checkbox" v-model="filters.submitted" class="rounded text-brand-500">
                      <span class="text-sm text-gray-700 dark:text-gray-300">Soumis</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input type="checkbox" v-model="filters.validated_n1" class="rounded text-brand-500">
                      <span class="text-sm text-gray-700 dark:text-gray-300">Validés N1</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input type="checkbox" v-model="filters.validated_n2" class="rounded text-brand-500">
                      <span class="text-sm text-gray-700 dark:text-gray-300">Validés N2</span>
                    </label>
                  </div>
                </div>

                <!-- Tri -->
                <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Trier par
                  </label>
                  <select v-model="sortBy" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="date_desc">Plus récent</option>
                    <option value="date_asc">Plus ancien</option>
                    <option value="progress_desc">Progression (desc)</option>
                    <option value="progress_asc">Progression (asc)</option>
                  </select>
                </div>
              </div>
            </div>
            </transition>
          </div>

          <!-- Bouton ajouter -->
          <button
            v-if="canAddResultat"
            @click="showForm = true"
            class="px-4 py-2 text-white rounded-3 font-medium transition-all flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span class="hidden sm:inline">Ajouter un résultat</span>
            <span class="sm:hidden">Ajouter</span>
          </button>
        </div>
      </div>

      <!-- Statistiques -->
      <div v-if="resultats.length > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="rounded-3 p-3 border border-blue-200 dark:border-blue-800">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium text-blue-700 dark:text-blue-300 uppercase">Total</p>
              <p class="text-2xl font-bold text-blue-900 dark:text-blue-100 mt-1">{{ stats.total }}</p>
            </div>
            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="rounded-3 p-3 border border-amber-200 dark:border-amber-800">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium text-amber-700 dark:text-amber-300 uppercase">Brouillons</p>
              <p class="text-2xl font-bold text-amber-900 dark:text-amber-100 mt-1">{{ stats.drafts }}</p>
            </div>
            <div class="w-10 h-10 bg-amber-500 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="rounded-3 p-3 border border-purple-200 dark:border-purple-800">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium text-purple-700 dark:text-purple-300 uppercase">Validation</p>
              <p class="text-2xl font-bold text-purple-900 dark:text-purple-100 mt-1">{{ stats.pending }}</p>
            </div>
            <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="rounded-3 p-3 border border-green-200 dark:border-green-800">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium text-green-700 dark:text-green-300 uppercase">Validés</p>
              <p class="text-2xl font-bold text-green-900 dark:text-green-100 mt-1">{{ stats.validated }}</p>
            </div>
            <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-16">
      <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-500 border-t-transparent mb-4"></div>
      <p class="text-sm text-gray-600 dark:text-gray-400">Chargement des résultats...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="resultats.length === 0" class="text-center py-16 rounded-3 border-2 border-dashed border-gray-300 dark:border-gray-700">
      <div class="w-20 h-20 mx-auto mb-4 rounded-full flex items-center justify-center">
        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>
      <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
        Aucun résultat enregistré
      </h4>
      <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
        Commencez à documenter les résultats de cette tâche pour suivre sa progression et faciliter la validation.
      </p>
      <button
        v-if="canAddResultat"
        @click="showForm = true"
        class="inline-flex items-center px-6 py-3 text-white rounded-3 font-medium transition-all"
      >
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Ajouter le premier résultat
      </button>
    </div>

    <!-- Résultats filtrés vides -->
    <div v-else-if="filteredResultats.length === 0" class="text-center py-12 bg-gray-50 dark:bg-gray-900 rounded-3">
      <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
      <p class="text-gray-600 dark:text-gray-400 mb-4">Aucun résultat ne correspond aux filtres</p>
      <button @click="resetFilters" class="text-brand-600 hover:text-brand-700 font-medium">
        Réinitialiser les filtres
      </button>
    </div>

    <!-- Liste des résultats avec timeline -->
    <div v-else class="relative">
      <!-- Timeline line -->
      <div class="absolute left-8 top-0 bottom-0 w-0.5 hidden md:block"></div>
      
      <div class="space-y-6">
        <ResultatCard
          v-for="(resultat, index) in filteredResultats"
          :key="resultat.id"
          :resultat="resultat"
          :tache="tache"
          :index="index"
          :total="filteredResultats.length"
          @edit="handleEdit"
          @delete="handleDelete"
          @submit="handleSubmit"
          @validate-n1="handleValidateN1"
          @validate-n2="handleValidateN2"
          @reject="handleReject"
          @updated="loadResultats"
        />
      </div>
    </div>

    <!-- Form Modal -->
    <ResultatForm
      v-if="showForm"
      :tache="tache"
      :resultat="currentResultat"
      @close="closeForm"
      @saved="handleSaved"
    />

    <!-- Reject Modal -->
    <RejectModal
      v-if="showRejectModal"
      :resultat="currentResultat"
      :level="rejectLevel"
      @close="showRejectModal = false"
      @rejected="handleRejected"
    />

  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import ResultatCard from './ResultatCard.vue'
import ResultatForm from './ResultatForm.vue'
import RejectModal from './RejectModal.vue'
import api from '@/api/axios'

const props = defineProps({
  tache: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['resultat-added', 'refresh'])

const resultats = ref([])
const loading = ref(false)
const showForm = ref(false)
const showRejectModal = ref(false)
const showFilters = ref(false)
const currentResultat = ref(null)
const rejectLevel = ref('n1')
const sortBy = ref('date_desc')

const filters = ref({
  draft: false,
  submitted: false,
  validated_n1: false,
  validated_n2: false
})

const currentUser = computed(() => {
  const userStr = localStorage.getItem('user')
  return userStr ? JSON.parse(userStr) : null
})

const canAddResultat = computed(() => {
  return props.tache.permissions?.can_edit
})

const activeFilters = computed(() => {
  return Object.entries(filters.value).filter(([_, value]) => value).map(([key]) => key)
})

// Filtrer les résultats
const filteredResultats = computed(() => {
  let filtered = [...resultats.value]

  // Appliquer les filtres de statut
  if (activeFilters.value.length > 0) {
    filtered = filtered.filter(r => {
      if (filters.value.draft && !r.soumis_le) return true
      if (filters.value.submitted && r.soumis_le && !r.validation_n1?.valide) return true
      if (filters.value.validated_n1 && r.validation_n1?.valide && !r.validation_n2?.valide) return true
      if (filters.value.validated_n2 && r.validation_n2?.valide) return true
      return false
    })
  }

  // Appliquer le tri
  filtered.sort((a, b) => {
    switch (sortBy.value) {
      case 'date_desc':
        return new Date(b.created_at) - new Date(a.created_at)
      case 'date_asc':
        return new Date(a.created_at) - new Date(b.created_at)
      case 'progress_desc':
        return b.taux_realisation - a.taux_realisation
      case 'progress_asc':
        return a.taux_realisation - b.taux_realisation
      default:
        return 0
    }
  })

  return filtered
})

// Calculer les statistiques
const stats = computed(() => {
  return {
    total: resultats.value.length,
    drafts: resultats.value.filter(r => !r.soumis_le).length,
    pending: resultats.value.filter(r => r.soumis_le && (!r.validation_n1?.valide || !r.validation_n2?.valide)).length,
    validated: resultats.value.filter(r => r.validation_n2?.valide).length,
  }
})

const resetFilters = () => {
  filters.value = {
    draft: false,
    submitted: false,
    validated_n1: false,
    validated_n2: false
  }
  sortBy.value = 'date_desc'
}

const loadResultats = async () => {
  loading.value = true
  try {
    const { data } = await api.get(`/taches/${props.tache.id}/resultats`)
    resultats.value = data.data || []
  } catch (error) {
    console.error('Error loading resultats:', error)
  } finally {
    loading.value = false
  }
}

const handleEdit = (resultat) => {
  currentResultat.value = resultat
  showForm.value = true
}

const handleDelete = async (resultat) => {
  if (!confirm('Voulez-vous vraiment supprimer ce résultat ?')) return

  try {
    await api.delete(`/taches/${props.tache.id}/resultats/${resultat.id}`)
    await loadResultats()
    emit('resultat-added')
    emit('refresh')
  } catch (error) {
    console.error('Error deleting resultat:', error)
    alert('Erreur lors de la suppression')
  }
}

const handleSubmit = async (resultat) => {
  if (!confirm('Soumettre ce résultat pour validation ?')) return

  try {
    await api.post(`/taches/${props.tache.id}/resultats/${resultat.id}/submit`)
    await loadResultats()
    emit('resultat-added')
    emit('refresh')
  } catch (error) {
    console.error('Error submitting resultat:', error)
    alert('Erreur lors de la soumission')
  }
}

const handleValidateN1 = async (resultat) => {
  const commentaire = prompt('Commentaire de validation (optionnel):')
  if (commentaire === null) return

  try {
    await api.post(`/taches/${props.tache.id}/resultats/${resultat.id}/validate-n1`, {
      commentaire
    })
    await loadResultats()
    emit('resultat-added')
    emit('refresh')
  } catch (error) {
    console.error('Error validating N1:', error)
    alert('Erreur lors de la validation')
  }
}

const handleValidateN2 = async (resultat) => {
  const commentaire = prompt('Commentaire de validation (optionnel):')
  if (commentaire === null) return

  try {
    await api.post(`/taches/${props.tache.id}/resultats/${resultat.id}/validate-n2`, {
      commentaire
    })
    await loadResultats()
    emit('resultat-added')
    emit('refresh')
  } catch (error) {
    console.error('Error validating N2:', error)
    alert('Erreur lors de la validation')
  }
}

const handleReject = (resultat, level) => {
  currentResultat.value = resultat
  rejectLevel.value = level
  showRejectModal.value = true
}

const handleRejected = async () => {
  showRejectModal.value = false
  await loadResultats()
  emit('resultat-added')
  emit('refresh')
}

const handleSaved = async () => {
  closeForm()
  await loadResultats()
  emit('resultat-added')
  emit('refresh')
}

const closeForm = () => {
  showForm.value = false
  currentResultat.value = null
}

// Fermer dropdown filtres en cliquant dehors
watch(showFilters, (newVal) => {
  if (newVal) {
    const closeDropdown = (e) => {
      if (!e.target.closest('.relative')) {
        showFilters.value = false
        document.removeEventListener('click', closeDropdown)
      }
    }
    setTimeout(() => {
      document.addEventListener('click', closeDropdown)
    }, 100)
  }
})

onMounted(() => {
  loadResultats()
})
</script>