<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 " @click.self="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-3 max-w-2xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
      <!-- Header -->
      <div class="px-8 py-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-3 flex items-center justify-center ">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
            </svg>
          </div>
          <div class="flex-1">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ announcement ? 'Modifier l\'annonce' : 'Nouvelle annonce' }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ announcement ? 'Mettre à jour l\'annonce de l\'équipe' : 'Créer une annonce importante pour votre équipe' }}
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
          <!-- Section 1: Informations de l'annonce -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Informations de l'annonce
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
                placeholder="Ex: Réunion d'équipe importante"
                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all"
              />
            </div>

            <div>
              <label for="content" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Contenu <span class="text-red-500">*</span>
              </label>
              <textarea
                v-model="formData.content"
                id="content"
                rows="4"
                required
                placeholder="Décrivez votre annonce en détail..."
                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all resize-none"
              ></textarea>
            </div>
          </div>

          <!-- Section 2: Priorité & Publication -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              Priorité & Publication
            </h3>

            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Niveau de priorité
              </label>
              <div class="grid grid-cols-2 gap-3">
                <label
                  v-for="priority in priorityOptions"
                  :key="priority.value"
                  class="relative flex items-center gap-3 p-4 border-2 rounded-3 cursor-pointer transition-all "
                  :class="formData.priority === priority.value
                    ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20'
                    : 'border-gray-300 dark:border-gray-600 hover:border-amber-300 dark:hover:border-amber-700'"
                >
                  <input
                    type="radio"
                    v-model="formData.priority"
                    :value="priority.value"
                    class="sr-only"
                  />
                  <span class="text-2xl">{{ priority.icon }}</span>
                  <div class="flex-1">
                    <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                      {{ priority.label }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                      {{ priority.description }}
                    </div>
                  </div>
                  <svg
                    v-if="formData.priority === priority.value"
                    class="absolute right-3 top-3 w-5 h-5 text-amber-500"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </label>
              </div>
            </div>

            <div>
              <label for="published_at" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Date de publication
              </label>
              <input
                v-model="formData.published_at"
                type="datetime-local"
                id="published_at"
                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all"
              />
              <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Laissez vide pour publier immédiatement
              </p>
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
            <span v-else>{{ announcement ? 'Mettre à jour' : 'Publier l\'annonce' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'

const props = defineProps({
  announcement: {
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

const priorityOptions = [
  {
    value: 'normal',
    label: 'Normal',
    icon: '📝',
    description: 'Annonce standard'
  },
  {
    value: 'high',
    label: 'Urgent',
    icon: '🚨',
    description: 'Nécessite attention immédiate'
  }
]

const formData = reactive({
  title: '',
  content: '',
  priority: 'normal',
  published_at: ''
})

const handleSubmit = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const url = props.announcement
      ? `/api/teams/${props.teamId}/announcements/${props.announcement.id}`
      : `/api/teams/${props.teamId}/announcements`

    const method = props.announcement ? 'PUT' : 'POST'

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
  if (props.announcement) {
    formData.title = props.announcement.title || ''
    formData.content = props.announcement.content || ''
    formData.priority = props.announcement.priority || 'normal'
    formData.published_at = props.announcement.published_at || ''
  }
})
</script>
