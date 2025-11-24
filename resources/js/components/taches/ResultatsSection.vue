<!-- resources/js/components/taches/ResultatsSection.vue -->
<template>
  <div class="space-y-6">
    
    <!-- Header avec statistiques -->
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
      
      <button
        v-if="canAddResultat"
        @click="showForm = true"
        class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Ajouter un résultat
      </button>
    </div>

    <!-- Statistiques des résultats -->
    <div v-if="resultats.length > 0" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
      <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
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

      <div class="bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
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

      <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-purple-700 dark:text-purple-300 uppercase">En validation</p>
            <p class="text-2xl font-bold text-purple-900 dark:text-purple-100 mt-1">{{ stats.pending }}</p>
          </div>
          <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
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

    <!-- Loading -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-16">
      <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-500 border-t-transparent mb-4"></div>
      <p class="text-sm text-gray-600 dark:text-gray-400">Chargement des résultats...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="resultats.length === 0" class="text-center py-16 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-700">
      <div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
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
        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all"
      >
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Ajouter le premier résultat
      </button>
    </div>

    <!-- Liste des résultats avec timeline -->
    <div v-else class="relative">
      <!-- Timeline line -->
      <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-gradient-to-b from-blue-500 via-purple-500 to-green-500"></div>
      
      <div class="space-y-6">
        <ResultatCard
          v-for="(resultat, index) in sortedResultats"
          :key="resultat.id"
          :resultat="resultat"
          :tache="tache"
          :index="index"
          :total="resultats.length"
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
import { ref, onMounted, computed } from 'vue'
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

const emit = defineEmits(['resultat-added'])

const resultats = ref([])
const loading = ref(false)
const showForm = ref(false)
const showRejectModal = ref(false)
const currentResultat = ref(null)
const rejectLevel = ref('n1')

const currentUser = computed(() => {
  const userStr = localStorage.getItem('user')
  return userStr ? JSON.parse(userStr) : null
})

const canAddResultat = computed(() => {
  return props.tache.permissions?.can_edit
})

// Trier les résultats par date (plus récent en premier)
const sortedResultats = computed(() => {
  return [...resultats.value].sort((a, b) => {
    return new Date(b.created_at) - new Date(a.created_at)
  })
})

// Calculer les statistiques
const stats = computed(() => {
  return {
    total: resultats.value.length,
    drafts: resultats.value.filter(r => r.status === 'draft').length,
    pending: resultats.value.filter(r => r.status === 'submitted' || r.status === 'validated_n1').length,
    validated: resultats.value.filter(r => r.status === 'validated_n2').length,
  }
})

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
}

const handleSaved = async () => {
  closeForm()
  await loadResultats()
  emit('resultat-added')
}

const closeForm = () => {
  showForm.value = false
  currentResultat.value = null
}

onMounted(() => {
  loadResultats()
})
</script>