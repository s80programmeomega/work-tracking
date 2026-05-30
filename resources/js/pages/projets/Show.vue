<!-- resources/js/pages/projets/Show.vue -->
<template>
  <AdminLayout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center h-screen">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="container mx-auto px-4 py-8">
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-3 p-6">
          <div class="flex items-center gap-3">
            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
              <h3 class="text-lg font-semibold text-red-900 dark:text-red-200">Erreur de chargement</h3>
              <p class="text-red-700 dark:text-red-300 mt-1">{{ error }}</p>
            </div>
          </div>
          <button 
            @click="goBack"
            class="mt-4 px-4 py-2 bg-red-600 text-white rounded-3 hover:bg-red-700 transition-colors"
          >
            Retour
          </button>
        </div>
      </div>

      <!-- Project Detail -->
      <template v-else-if="projet">
        <ProjetDetail
          :projet-id="projetId"
          @back="goBack"
          @create-activity="createActivity"
          @view-activity="viewActivity"
        />
      </template>

      <!-- Not Found State -->
      <div v-else class="container mx-auto px-4 py-8">
        <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-3 p-6 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Projet introuvable</h3>
          <p class="mt-2 text-gray-600 dark:text-gray-400">Le projet que vous recherchez n'existe pas ou a été supprimé.</p>
          <button 
            @click="goBack"
            class="mt-4 px-4 py-2 bg-brand-600 text-white rounded-3 hover:bg-brand-700 transition-colors"
          >
            Retour aux projets
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProjets } from '@/composables/useProjets'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import ProjetDetail from '@/components/projets/ProjetDetail.vue'

const route = useRoute()
const router = useRouter()
const { fetchProjet } = useProjets()

const projetId = ref(null)
const projet = ref(null)
const loading = ref(true)
const error = ref(null)

// Load project data
const loadProjet = async () => {
  try {
    loading.value = true
    error.value = null
    
    const id = parseInt(route.params.id)
    if (!id || isNaN(id)) {
      error.value = 'ID de projet invalide'
      return
    }
    
    projetId.value = id
    const response = await fetchProjet(id)
    
    if (response && response.data) {
      projet.value = response.data
    } else {
      error.value = 'Projet non trouvé'
    }
  } catch (err) {
    console.error('Error loading projet:', err)
    error.value = err.response?.data?.message || 'Erreur lors du chargement du projet'
  } finally {
    loading.value = false
  }
}

// Navigation handlers
const goBack = () => {
  // Try to go back to previous page, or fallback to projects list
  if (window.history.length > 2) {
    router.back()
  } else {
    router.push({ name: 'projets.index' })
  }
}

const createActivity = () => {
  // L'activité se crée depuis ActiviteDetail (route activites.show).
  router.push({ name: 'Activites' })
}

const viewActivity = (activityId) => {
  router.push({ name: 'activites.show', params: { id: activityId } })
}

// Watch route changes
watch(() => route.params.id, (newId) => {
  if (newId) {
    loadProjet()
  }
})

// Initial load
onMounted(() => {
  loadProjet()
})
</script>

<style scoped>
/* Add any custom styles here if needed */
</style>