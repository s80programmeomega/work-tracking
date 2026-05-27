<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 " @click.self="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-3 max-w-2xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
      <!-- Header -->
      <div class="px-8 py-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-3 flex items-center justify-center ">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
            </svg>
          </div>
          <div class="flex-1">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ resource ? 'Modifier la ressource' : 'Nouvelle ressource' }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ resource ? 'Mettre à jour la ressource partagée' : 'Partager une ressource avec votre équipe' }}
            </p>
          </div>
          <button
            @click="$emit('close')"
            class="p-2 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Error Alert -->
      <div v-if="errorMessage" class="mx-8 mt-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-3">
        <div class="flex items-start gap-3">
          <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div class="flex-1">
            <p class="font-medium text-red-800 dark:text-red-200">{{ errorMessage }}</p>
          </div>
        </div>
      </div>

      <!-- Form Body -->
      <div class="flex-1 overflow-y-auto px-8 py-6">
        <form @submit.prevent="handleSubmit" class="space-y-6">
          <!-- Section 1: Type de ressource -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
              </svg>
              Type de ressource
            </h3>

            <div class="grid grid-cols-2 gap-3">
              <label
                v-for="type in resourceTypes"
                :key="type.value"
                class="relative flex items-center gap-3 p-4 border-2 rounded-3 cursor-pointer transition-all "
                :class="formData.type === type.value
                  ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                  : 'border-gray-300 dark:border-gray-600 hover:border-blue-300 dark:hover:border-blue-700'"
              >
                <input
                  type="radio"
                  v-model="formData.type"
                  :value="type.value"
                  class="sr-only"
                />
                <span class="text-2xl">{{ type.icon }}</span>
                <div class="flex-1">
                  <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ type.label }}
                  </div>
                </div>
                <svg
                  v-if="formData.type === type.value"
                  class="absolute right-3 top-3 w-5 h-5 text-blue-500"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
              </label>
            </div>
          </div>

          <!-- Section 2: Informations -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Informations
            </h3>

            <div>
              <label for="title" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Titre <span class="text-red-500">*</span>
              </label>
              <input
                v-model="formData.title"
                type="text"
                id="title"
                required
                placeholder="Ex: Guide de démarrage rapide"
                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all"
              />
            </div>

            <div>
              <label for="url" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                URL <span class="text-red-500">*</span>
              </label>
              <input
                v-model="formData.url"
                type="url"
                id="url"
                required
                placeholder="https://example.com/document.pdf"
                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all"
              />
            </div>

            <div>
              <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Description
              </label>
              <textarea
                v-model="formData.description"
                id="description"
                rows="3"
                placeholder="Décrivez brièvement cette ressource..."
                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all resize-none"
              ></textarea>
            </div>
          </div>
        </form>
      </div>

      <!-- Footer -->
      <div class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
        <div class="flex justify-end gap-3">
          <button
            type="button"
            @click="$emit('close')"
            class="px-6 py-2.5 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-3 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all"
          >
            Annuler
          </button>
          <button
            type="submit"
            @click="handleSubmit"
            :disabled="loading"
            class="px-6 py-2.5 text-white font-semibold rounded-3 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
          >
            <span v-if="loading" class="flex items-center gap-2">
              <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Enregistrement...
            </span>
            <span v-else>{{ resource ? 'Mettre à jour' : 'Partager la ressource' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'

const props = defineProps({
  resource: {
    type: Object,
    default: null
  },
  teamId: {
    type: String,
    required: true
  }
})

const emit = defineEmits(['close', 'saved'])

const loading = ref(false)
const errorMessage = ref('')

const resourceTypes = [
  { value: 'file', label: 'Fichier', icon: '📄' },
  { value: 'link', label: 'Lien', icon: '🔗' },
  { value: 'document', label: 'Document', icon: '📝' },
  { value: 'template', label: 'Template', icon: '📋' }
]

const formData = reactive({
  title: '',
  type: 'link',
  url: '',
  description: ''
})

const handleSubmit = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const url = props.resource
      ? `/api/teams/${props.teamId}/resources/${props.resource.id}`
      : `/api/teams/${props.teamId}/resources`

    const method = props.resource ? 'PUT' : 'POST'

    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'include',
      body: JSON.stringify(formData)
    })

    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Une erreur est survenue')
    }

    emit('saved')
  } catch (error) {
    errorMessage.value = error.message
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (props.resource) {
    formData.title = props.resource.title || ''
    formData.type = props.resource.type || 'link'
    formData.url = props.resource.url || ''
    formData.description = props.resource.description || ''
  }
})
</script>
