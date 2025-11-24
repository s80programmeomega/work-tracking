<template>
  <div class="space-y-4">
    <!-- Header avec bouton d'ajout -->
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        Liens externes ({{ links.length }})
      </h3>
      
      <button
        v-if="canEdit && !showAddForm"
        @click="showAddForm = true"
        class="px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 flex items-center gap-2 transition-all"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Ajouter un lien</span>
      </button>
    </div>

    <!-- Formulaire d'ajout -->
    <div v-if="showAddForm" class="p-4 border-2 border-brand-500 rounded-lg bg-brand-50 dark:bg-brand-900/10">
      <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Nouveau lien</h4>
      
      <div class="space-y-3">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Titre
          </label>
          <input
            v-model="newLink.title"
            type="text"
            placeholder="Ex: Documentation API"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-brand-500 dark:bg-gray-800 dark:text-white"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            URL <span class="text-red-500">*</span>
          </label>
          <input
            v-model="newLink.url"
            type="url"
            placeholder="https://example.com"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-brand-500 dark:bg-gray-800 dark:text-white"
            required
          />
        </div>

        <div class="flex justify-end gap-2">
          <button
            @click="cancelAdd"
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-all"
          >
            Annuler
          </button>
          <button
            @click="addLink"
            :disabled="!newLink.url || saving"
            class="px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
          >
            {{ saving ? 'Enregistrement...' : 'Ajouter' }}
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
        class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors group"
      >
        <!-- Icône -->
        <div class="flex-shrink-0 w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
          <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
          </svg>
        </div>

        <!-- Info lien -->
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
            {{ link.title || getDomainFromUrl(link.url) }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
            {{ link.url }}
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
            class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors opacity-0 group-hover:opacity-100"
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
    <div v-else-if="!showAddForm" class="text-center py-12 bg-gray-50 dark:bg-gray-900 rounded-xl">
      <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
      </svg>
      <p class="text-gray-600 dark:text-gray-400 mb-4">Aucun lien externe</p>
      <button
        v-if="canEdit"
        @click="showAddForm = true"
        class="inline-flex items-center px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 transition-all"
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
import { ref, reactive } from 'vue'
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
const newLink = reactive({
  title: '',
  url: ''
})

const addLink = async () => {
  if (!newLink.url) return

  saving.value = true

  try {
    await api.post(`/taches/${props.tacheId}/external-links`, {
      title: newLink.title || getDomainFromUrl(newLink.url),
      url: newLink.url
    })

    emit('updated')
    cancelAdd()
  } catch (error) {
    console.error('Error adding link:', error)
    alert('Erreur lors de l\'ajout du lien')
  } finally {
    saving.value = false
  }
}

const deleteLink = async (link) => {
  if (!confirm(`Supprimer le lien "${link.title}" ?`)) return

  try {
    await api.delete(`/taches/${props.tacheId}/external-links/${link.id}`)
    emit('updated')
  } catch (error) {
    console.error('Error deleting link:', error)
    alert('Erreur lors de la suppression')
  }
}

const cancelAdd = () => {
  showAddForm.value = false
  newLink.title = ''
  newLink.url = ''
}

const getDomainFromUrl = (url) => {
  try {
    const urlObj = new URL(url)
    return urlObj.hostname.replace('www.', '')
  } catch {
    return url
  }
}
</script>