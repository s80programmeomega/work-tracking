<template>
  <AdminLayout>
    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center min-h-[60vh]">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-600"></div>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="container mx-auto px-4 py-8">
      <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-3 p-6">
        <h3 class="text-lg font-semibold text-red-900 dark:text-red-200">{{ $t('projets_page.load_error') }}</h3>
        <p class="mt-1 text-red-700 dark:text-red-300">{{ error }}</p>
        <button
          @click="goBack"
          class="mt-4 px-4 py-2 bg-red-600 text-white rounded-3 hover:bg-red-700 transition-colors"
        >
          {{ $t('projets_page.back') }}
        </button>
      </div>
    </div>

    <!-- Form -->
    <ProjetForm
      v-else-if="projet"
      :projet="projet"
      @close="goBack"
      @saved="onSaved"
    />

    <!-- Not found fallback -->
    <div v-else class="container mx-auto px-4 py-8 text-center text-gray-600 dark:text-gray-400">
      {{ $t('projets_page.not_found') }}
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProjets } from '@/composables/useProjets'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import ProjetForm from '@/components/projets/ProjetForm.vue'

const route = useRoute()
const router = useRouter()
const { fetchProjet } = useProjets()

const projet = ref(null)
const loading = ref(true)
const error = ref(null)

const loadProjet = async () => {
  loading.value = true
  error.value = null
  try {
    const id = parseInt(route.params.id)
    if (!id || isNaN(id)) {
      error.value = 'ID de projet invalide'
      return
    }
    const response = await fetchProjet(id)
    projet.value = response?.data ?? null
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors du chargement du projet'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  if (window.history.length > 2) {
    router.back()
  } else {
    router.push({ name: 'projets.my' })
  }
}

const onSaved = () => {
  router.push({ name: 'projets.show', params: { id: route.params.id } })
}

watch(() => route.params.id, (newId) => {
  if (newId) {
    loadProjet()
  }
})

onMounted(loadProjet)
</script>
