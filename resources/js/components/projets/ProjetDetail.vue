<template>
  <div v-if="loading" class="flex items-center justify-center py-12">
    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-500"></div>
    <span class="ml-2">Chargement...</span>
  </div>

  <div v-else-if="projet" class="space-y-6">
    <!-- Header -->
    <div class="flex items-start justify-between">
      <div class="space-y-1">
        <div class="flex items-center space-x-3">
          <button
            @click="$emit('back')"
            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ projet.nom }}</h1>
          <span
            :class="[
              'px-2 py-1 text-xs font-medium rounded-full',
              getStatusClass(projet.status)
            ]"
          >
            {{ getStatusLabel(projet.status) }}
          </span>
        </div>
        <p class="text-gray-500 dark:text-gray-400">{{ projet.description }}</p>
      </div>
    </div>

    <!-- Project Info -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
      <h3 class="text-lg font-semibold mb-4">Informations</h3>
      <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Code</dt>
          <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ projet.code }}</dd>
        </div>
        <div>
          <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Responsable</dt>
          <dd class="mt-1 text-sm text-gray-900 dark:text-white">
            {{ projet.responsable?.nom || '-' }}
          </dd>
        </div>
        <div>
          <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Date début</dt>
          <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ projet.date_debut || '-' }}</dd>
        </div>
        <div>
          <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Date fin</dt>
          <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ projet.date_fin || '-' }}</dd>
        </div>
        <div>
          <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Progression</dt>
          <dd class="mt-1">
            <div class="flex items-center gap-2">
              <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div
                  class="h-full bg-brand-500"
                  :style="{ width: `${projet.progression}%` }"
                ></div>
              </div>
              <span class="text-sm text-gray-900 dark:text-white">{{ projet.progression }}%</span>
            </div>
          </dd>
        </div>
        <div>
          <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Membres</dt>
          <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ projet.member_count || 0 }}</dd>
        </div>
      </dl>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import projetsApi from '@/api/projets'

const props = defineProps({
  projetId: {
    type: [String, Number],
    required: true
  }
})

defineEmits(['back', 'create-activity', 'view-activity'])

const projet = ref(null)
const loading = ref(false)

const fetchProjet = async () => {
  loading.value = true
  try {
    const { data } = await projetsApi.getById(props.projetId)
    projet.value = data.data
  } catch (error) {
    console.error('Erreur lors du chargement du projet:', error)
  } finally {
    loading.value = false
  }
}

const getStatusLabel = (status) => {
  const labels = {
    active: 'Actif',
    archived: 'Archivé',
    completed: 'Terminé'
  }
  return labels[status] || status
}

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    archived: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    completed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

onMounted(() => {
  fetchProjet()
})
</script>
