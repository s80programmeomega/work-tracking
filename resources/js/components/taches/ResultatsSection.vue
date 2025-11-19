<template>
  <div class="space-y-6">
    
    <!-- Header avec bouton d'ajout -->
    <div class="flex justify-between items-center">
      <h3 class="text-xl font-bold text-gray-900 dark:text-white">
        Résultats de la tâche
      </h3>
      <button
        v-if="canAddResultat"
        @click="showForm = true"
        class="px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Ajouter un résultat
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-500"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="resultats.length === 0" class="text-center py-12 bg-gray-50 dark:bg-gray-900 rounded-xl">
      <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="text-gray-600 dark:text-gray-400 mb-4">Aucun résultat enregistré pour cette tâche</p>
      <button
        v-if="canAddResultat"
        @click="showForm = true"
        class="px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600"
      >
        Ajouter le premier résultat
      </button>
    </div>

    <!-- Liste des résultats -->
    <div v-else class="space-y-4">
      <ResultatCard
        v-for="resultat in resultats"
        :key="resultat.id"
        :resultat="resultat"
        :tache="tache"
        @edit="handleEdit"
        @delete="handleDelete"
        @submit="handleSubmit"
        @validate-n1="handleValidateN1"
        @validate-n2="handleValidateN2"
        @reject="handleReject"
        @updated="loadResultats"
      />
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
// import ResultatCard from './ResultatCard.vue'
// import ResultatForm from './ResultatForm.vue'
// import RejectModal from './RejectModal.vue'
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