<!-- resources\js\components\documents\DocumentList.vue -->
<template>
  <div>
    <!-- Loading State -->
    <div v-if="loading" class="rounded-3 border border-gray-200 bg-white p-12 text-center dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent"></div>
      <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Chargement des documents...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="documents.length === 0" class="rounded-3 border border-gray-200 bg-white p-12 text-center dark:border-gray-800 dark:bg-white/[0.03]">
      <DocumentIcon class="mx-auto h-16 w-16 text-gray-400" />
      <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
        Aucun document
      </h3>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        Commencez par télécharger votre premier document
      </p>
    </div>

    <!-- Grid View -->
    <div v-else-if="viewMode === 'grid'" ref="staggerRef" class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <document-card
        v-for="document in documents"
        :key="document.id"
        class="stagger-item"
        :document="document"
        @view="$emit('view', document)"
        @download="$emit('download', document)"
        @edit="$emit('edit', document)"
        @delete="$emit('delete', document)"
        @share="$emit('share', document)"
        @version="$emit('version', document)"
      />
    </div>

    <!-- List View -->
    <div v-else class="rounded-3 border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
          <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                Document
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                Taille
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                Uploadé par
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                Date
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                Téléchargements
              </th>
              <th scope="col" class="relative px-6 py-3">
                <span class="sr-only">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody ref="staggerRef" class="bg-white divide-y divide-gray-200 dark:bg-transparent dark:divide-gray-800">
            <tr
              v-for="document in documents"
              :key="document.id"
              class="stagger-item hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors"
            >
              <!-- Document Name -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-3">
                  <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-3 bg-gray-100 dark:bg-gray-800">
                    <component :is="getFileIcon(document)" class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                  </div>
                  <div class="min-w-0 flex-1">
                    <button
                      @click="$emit('view', document)"
                      class="text-sm font-medium text-gray-900 hover:text-blue-600 dark:text-white dark:hover:text-blue-400 truncate block max-w-xs"
                    >
                      {{ document.nom }}
                    </button>
                    <p v-if="document.description" class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">
                      {{ document.description }}
                    </p>
                  </div>
                </div>
              </td>

              <!-- Size -->
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                {{ document.formatted_size }}
              </td>

              <!-- Uploaded By -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-xs font-medium text-blue-600 dark:text-blue-400">
                    {{ getInitials(document.user.nom) }}
                  </div>
                  <span class="text-sm text-gray-900 dark:text-white">{{ document.user.nom }}</span>
                </div>
              </td>

              <!-- Date -->
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                {{ formatDate(document.created_at) }}
              </td>

              <!-- Downloads -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                  <ArrowDownTrayIcon class="h-4 w-4" />
                  {{ document.download_count }}
                </div>
              </td>

              <!-- Actions -->
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <document-actions-menu
                  :document="document"
                  @view="$emit('view', document)"
                  @download="$emit('download', document)"
                  @edit="$emit('edit', document)"
                  @delete="$emit('delete', document)"
                  @share="$emit('share', document)"
                  @version="$emit('version', document)"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, watch, nextTick } from 'vue'
import { useStagger } from '@/composables/useAnimations'
import {
  DocumentIcon,
  ArrowDownTrayIcon,
  DocumentTextIcon,
  PhotoIcon,
  FilmIcon,
  MusicalNoteIcon,
  ArchiveBoxIcon
} from '@heroicons/vue/24/outline'
import DocumentCard from './DocumentCard.vue'
import DocumentActionsMenu from './DocumentActionsMenu.vue'

const { staggerRef, applyStagger } = useStagger(40)

const props = defineProps({
  documents: {
    type: Array,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  },
  viewMode: {
    type: String,
    default: 'grid',
    validator: (value) => ['grid', 'list'].includes(value)
  }
})

defineEmits(['view', 'download', 'edit', 'delete', 'share', 'version'])

watch(() => props.documents, async () => {
  await nextTick()
  applyStagger()
}, { immediate: true })

// Methods
const getFileIcon = (document) => {
  const mimeType = document.mime_type
  if (mimeType.startsWith('image/')) return PhotoIcon
  if (mimeType === 'application/pdf') return DocumentTextIcon
  if (mimeType.startsWith('video/')) return FilmIcon
  if (mimeType.startsWith('audio/')) return MusicalNoteIcon
  if (mimeType.includes('zip') || mimeType.includes('compressed')) return ArchiveBoxIcon
  return DocumentIcon
}

const getInitials = (name) => {
  if (!name) return '?'
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .substring(0, 2)
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  const now = new Date()
  const diffInMs = now - date
  const diffInDays = Math.floor(diffInMs / (1000 * 60 * 60 * 24))

  if (diffInDays === 0) {
    return 'Aujourd\'hui'
  } else if (diffInDays === 1) {
    return 'Hier'
  } else if (diffInDays < 7) {
    return `Il y a ${diffInDays} jours`
  } else {
    return date.toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    })
  }
}
</script>