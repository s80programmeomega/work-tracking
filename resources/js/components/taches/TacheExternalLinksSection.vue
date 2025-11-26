<!-- resources\js\components\taches\TacheExternalLinksSection.vue -->
<template>
  <div class="space-y-4">
    <!-- Header avec bouton d'ajout -->
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
        </svg>
        Liens externes ({{ links.length }})
      </h3>
      
      <button
        v-if="canEdit && !showAddForm"
        @click="showAddForm = true"
        class="px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 flex items-center gap-2 transition-all shadow-md hover:shadow-lg"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Ajouter un lien</span>
      </button>
    </div>

    <!-- Messages d'erreur -->
    <div v-if="errorMessage" class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
      <div class="flex items-start gap-3">
        <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="flex-1">
          <p class="text-sm font-medium text-red-800 dark:text-red-300">{{ errorMessage }}</p>
        </div>
        <button @click="errorMessage = ''" class="text-red-600 hover:text-red-800">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Formulaire d'ajout -->
    <div v-if="showAddForm" class="p-4 border-2 border-brand-500 rounded-lg bg-brand-50 dark:bg-brand-900/10 space-y-4">
      <div class="flex items-center justify-between">
        <h4 class="font-semibold text-gray-900 dark:text-white">Nouveau lien</h4>
        <button @click="cancelAdd" class="text-gray-400 hover:text-gray-600">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      
      <div class="space-y-3">
        <!-- URL input -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            URL <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input
              v-model="newLink.url"
              @input="validateUrl"
              @blur="autoFillTitle"
              type="url"
              placeholder="https://example.com"
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-brand-500 dark:bg-gray-800 dark:text-white pr-10"
              :class="urlError ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'"
              required
            />
            <!-- Indicateur validation URL -->
            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
              <svg v-if="isValidUrl && newLink.url" class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <svg v-else-if="urlError" class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </div>
          </div>
          <p v-if="urlError" class="mt-1 text-xs text-red-600">{{ urlError }}</p>
          <p v-else class="mt-1 text-xs text-gray-500">L'URL doit commencer par http:// ou https://</p>
        </div>

        <!-- Title input -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Titre <span class="text-gray-400 text-xs">(optionnel)</span>
          </label>
          <input
            v-model="newLink.title"
            type="text"
            placeholder="Ex: Documentation API (généré automatiquement si vide)"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-brand-500 dark:bg-gray-800 dark:text-white"
          />
          <p class="mt-1 text-xs text-gray-500">
            Laissez vide pour utiliser le nom de domaine
          </p>
        </div>

        <!-- Preview -->
        <div v-if="newLink.url && isValidUrl" class="p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
          <p class="text-xs text-gray-500 mb-2">Aperçu:</p>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
              <img 
                v-if="faviconUrl"
                :src="faviconUrl" 
                @error="faviconError = true"
                class="w-5 h-5"
                alt="favicon"
              />
              <svg v-else class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                {{ newLink.title || getDomainFromUrl(newLink.url) }}
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                {{ newLink.url }}
              </p>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-2 pt-2">
          <button
            @click="cancelAdd"
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-all"
          >
            Annuler
          </button>
          <button
            @click="addLink"
            :disabled="!canSubmit || saving"
            class="px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2"
          >
            <svg v-if="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ saving ? 'Enregistrement...' : 'Ajouter' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Liste des liens -->
    <div v-if="links.length > 0" class="space-y-2">
      <a
        v-for="link in links"
        :key="link.id"
        :href="link.url"
        target="_blank"
        rel="noopener noreferrer"
        class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 hover:border-brand-500 dark:hover:border-brand-400 transition-all group"
      >
        <!-- Favicon / Icône -->
        <div class="flex-shrink-0 w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
          <img 
            :src="getFaviconUrl(link.url)" 
            @error="handleFaviconError"
            class="w-5 h-5"
            :alt="link.title"
          />
        </div>

        <!-- Info lien -->
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-gray-900 dark:text-white truncate group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors">
            {{ link.title || getDomainFromUrl(link.url) }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
            {{ link.url }}
          </p>
          <p v-if="link.created_by" class="text-xs text-gray-400 mt-1">
            Ajouté par {{ link.created_by.nom }} • {{ formatDate(link.created_at) }}
          </p>
        </div>

        <!-- Actions -->
        <div class="flex-shrink-0 flex items-center gap-1">
          <!-- Icône lien externe -->
          <svg class="w-4 h-4 text-gray-400 group-hover:text-brand-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
          </svg>

          <!-- Supprimer -->
          <button
            v-if="canEdit"
            @click.prevent="deleteLink(link)"
            :disabled="deleting"
            class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors opacity-0 group-hover:opacity-100 disabled:opacity-50"
            title="Supprimer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
      </a>
    </div>

    <!-- Empty state -->
    <div v-else-if="!showAddForm" class="text-center py-12 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-700">
      <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
      </svg>
      <p class="text-gray-600 dark:text-gray-400 mb-4">Aucun lien externe</p>
      <button
        v-if="canEdit"
        @click="showAddForm = true"
        class="inline-flex items-center px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 transition-all shadow-md hover:shadow-lg"
      >
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Ajouter le premier lien
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import api from '@/api/axios'

const props = defineProps({
  tacheId: {
    type: Number,
    required: true
  },
  links: {
    type: Array,
    default: () => []
  },
  canEdit: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['updated'])

const showAddForm = ref(false)
const saving = ref(false)
const deleting = ref(false)
const errorMessage = ref('')
const urlError = ref('')
const isValidUrl = ref(false)
const faviconError = ref(false)

const newLink = reactive({
  title: '',
  url: ''
})

const faviconUrl = computed(() => {
  if (!newLink.url || !isValidUrl.value || faviconError.value) return null
  return getFaviconUrl(newLink.url)
})

const canSubmit = computed(() => {
  return newLink.url && isValidUrl.value && !saving.value
})

const validateUrl = () => {
  urlError.value = ''
  isValidUrl.value = false

  if (!newLink.url) return

  try {
    const url = new URL(newLink.url)
    if (url.protocol !== 'http:' && url.protocol !== 'https:') {
      urlError.value = 'L\'URL doit commencer par http:// ou https://'
      return
    }
    isValidUrl.value = true
  } catch {
    urlError.value = 'URL invalide'
  }
}

const autoFillTitle = () => {
  if (!newLink.title && newLink.url && isValidUrl.value) {
    newLink.title = getDomainFromUrl(newLink.url)
  }
}

const addLink = async () => {
  if (!canSubmit.value) return

  saving.value = true
  errorMessage.value = ''

  try {
    await api.post(`/taches/${props.tacheId}/external-links`, {
      title: newLink.title || getDomainFromUrl(newLink.url),
      url: newLink.url
    })

    emit('updated')
    cancelAdd()
  } catch (error) {
    console.error('Error adding link:', error)
    errorMessage.value = error.response?.data?.message || 'Erreur lors de l\'ajout du lien'
  } finally {
    saving.value = false
  }
}

const deleteLink = async (link) => {
  if (!confirm(`Supprimer le lien "${link.title}" ?`)) return

  deleting.value = true
  errorMessage.value = ''

  try {
    await api.delete(`/taches/${props.tacheId}/external-links/${link.id}`)
    emit('updated')
  } catch (error) {
    console.error('Error deleting link:', error)
    errorMessage.value = 'Erreur lors de la suppression'
  } finally {
    deleting.value = false
  }
}

const cancelAdd = () => {
  showAddForm.value = false
  newLink.title = ''
  newLink.url = ''
  urlError.value = ''
  isValidUrl.value = false
  faviconError.value = false
}

const getDomainFromUrl = (url) => {
  try {
    const urlObj = new URL(url)
    return urlObj.hostname.replace('www.', '')
  } catch {
    return url
  }
}

const getFaviconUrl = (url) => {
  try {
    const urlObj = new URL(url)
    return `https://www.google.com/s2/favicons?domain=${urlObj.hostname}&sz=32`
  } catch {
    return null
  }
}

const handleFaviconError = (event) => {
  // Fallback to default icon
  event.target.style.display = 'none'
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}
</script>