<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Documents Partagés avec Moi
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Documents que d'autres utilisateurs ont partagés avec vous
        </p>
      </div>

      <!-- Filter by Permission -->
      <select
        v-model="permissionFilter"
        class="rounded-3 border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
      >
        <option value="">Toutes les permissions</option>
        <option value="view">Lecture seule</option>
        <option value="download">Téléchargement</option>
        <option value="edit">Modification</option>
      </select>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 6" :key="i" class="h-24 animate-pulse rounded-3 bg-gray-200 dark:bg-gray-800"></div>
    </div>

    <!-- Documents List -->
    <div v-else-if="filteredDocuments.length > 0" ref="staggerRef" class="space-y-3">
      <div
        v-for="document in filteredDocuments"
        :key="document.id"
        @click="handleView(document)"
        class="stagger-item group cursor-pointer overflow-hidden rounded-3 border border-gray-200 bg-white transition-all hover:border-purple-500 dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-purple-400"
      >
        <div class="flex items-center gap-4 p-4">
          <!-- File Icon -->
          <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-3 bg-gray-100 dark:bg-gray-800">
            <component :is="getFileIcon(document)" class="h-7 w-7 text-gray-600 dark:text-gray-400" />
          </div>

          <!-- Document Info -->
          <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0 flex-1">
                <h5 class="truncate text-base font-medium text-gray-900 dark:text-white">
                  {{ document.nom }}
                </h5>
                <div class="mt-1 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                  <span>{{ document.formatted_size }}</span>
                  <span>•</span>
                  <span>{{ getEntityLabel(document.documentable_type) }}</span>
                  <span>•</span>
                  <span>{{ formatDate(document.created_at) }}</span>
                </div>
              </div>

              <!-- Permission Badges -->
              <div class="flex flex-wrap gap-1">
                <span
                  v-if="document.permission?.can_view"
                  class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-400"
                >
                  <EyeIcon class="mr-1 h-3 w-3" />
                  Lecture
                </span>
                <span
                  v-if="document.permission?.can_download"
                  class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400"
                >
                  <ArrowDownTrayIcon class="mr-1 h-3 w-3" />
                  Télécharger
                </span>
                <span
                  v-if="document.permission?.can_edit"
                  class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-800 dark:bg-purple-900/30 dark:text-purple-400"
                >
                  <PencilIcon class="mr-1 h-3 w-3" />
                  Modifier
                </span>
                <span
                  v-if="document.permission?.can_share"
                  class="inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-900/30 dark:text-orange-400"
                >
                  <ShareIcon class="mr-1 h-3 w-3" />
                  Partager
                </span>
              </div>
            </div>

            <!-- Shared By -->
            <div class="mt-3 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="h-6 w-6 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-xs font-medium text-purple-600 dark:text-purple-400">
                  {{ getInitials(document.user?.nom) }}
                </div>
                <span class="text-xs text-gray-600 dark:text-gray-400">
                  Partagé par <span class="font-medium">{{ document.user?.nom }}</span>
                </span>
              </div>

              <!-- Expiration Warning -->
              <div v-if="document.permission?.expires_at" class="flex items-center gap-1 text-xs text-orange-600 dark:text-orange-400">
                <ClockIcon class="h-3 w-3" />
                Expire {{ formatExpirationDate(document.permission.expires_at) }}
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-col gap-2 opacity-0 transition-opacity group-hover:opacity-100">
            <button
              v-if="document.permission?.can_download"
              @click.stop="handleDownload(document)"
              class="rounded-3 p-2 text-gray-400 hover:bg-gray-100 hover:text-blue-600 dark:hover:bg-gray-800"
              title="Télécharger"
            >
              <ArrowDownTrayIcon class="h-5 w-5" />
            </button>
            <button
              v-if="document.permission?.can_share"
              @click.stop="handleShare(document)"
              class="rounded-3 p-2 text-gray-400 hover:bg-gray-100 hover:text-purple-600 dark:hover:bg-gray-800"
              title="Partager"
            >
              <ShareIcon class="h-5 w-5" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="rounded-3 border-2 border-dashed border-gray-300 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-800/50">
      <UserGroupIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
        Aucun document partagé
      </h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Les documents que d'autres partagent avec vous apparaîtront ici
      </p>
    </div>

    <!-- Modals -->
    <document-viewer-modal
      v-if="selectedDocument"
      :document="selectedDocument"
      @close="selectedDocument = null"
      @download="handleDownload"
    />

    <document-share-modal
      v-if="sharingDocument"
      :document="sharingDocument"
      @close="sharingDocument = null"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useStagger } from '@/composables/useAnimations'
import {
  UserGroupIcon,
  ArrowDownTrayIcon,
  ShareIcon,
  EyeIcon,
  PencilIcon,
  ClockIcon,
  DocumentIcon,
  PhotoIcon,
  FilmIcon,
  MusicalNoteIcon,
  ArchiveBoxIcon,
  DocumentTextIcon
} from '@heroicons/vue/24/outline'
import { useDocuments } from '@/composables/useDocuments'
import DocumentViewerModal from '@/components/documents/DocumentViewerModal.vue'
import DocumentShareModal from '@/components/documents/DocumentShareModal.vue'
import api from '@/api/axios'

const { downloadDocument } = useDocuments()
const { staggerRef, applyStagger } = useStagger(40)

const documents = ref([])
const loading = ref(false)
const permissionFilter = ref('')
const selectedDocument = ref(null)
const sharingDocument = ref(null)

const filteredDocuments = computed(() => {
  if (!permissionFilter.value) return documents.value

  return documents.value.filter(doc => {
    const perm = doc.permission
    if (!perm) return false

    switch (permissionFilter.value) {
      case 'view':
        return perm.can_view
      case 'download':
        return perm.can_download
      case 'edit':
        return perm.can_edit
      default:
        return true
    }
  })
})

const getFileIcon = (document) => {
  const mimeType = document.mime_type
  if (mimeType.startsWith('image/')) return PhotoIcon
  if (mimeType === 'application/pdf') return DocumentTextIcon
  if (mimeType.startsWith('video/')) return FilmIcon
  if (mimeType.startsWith('audio/')) return MusicalNoteIcon
  if (mimeType.includes('zip') || mimeType.includes('compressed')) return ArchiveBoxIcon
  return DocumentIcon
}

const getEntityLabel = (type) => {
  const labels = {
    'App\\Models\\Workspace': 'Workspace',
    'App\\Models\\Projet': 'Projet',
    'App\\Models\\Activite': 'Activité',
    'App\\Models\\Tache': 'Tâche',
    'App\\Models\\TacheResultat': 'Résultat'
  }
  return labels[type] || 'Document'
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
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const formatExpirationDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  const now = new Date()
  const diffDays = Math.ceil((date - now) / (1000 * 60 * 60 * 24))

  if (diffDays < 0) return 'expiré'
  if (diffDays === 0) return 'aujourd\'hui'
  if (diffDays === 1) return 'demain'
  if (diffDays < 7) return `dans ${diffDays} jours`
  return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}

const handleView = (document) => {
  selectedDocument.value = document
}

const handleDownload = async (document) => {
  try {
    await downloadDocument(document.id, document.nom)
  } catch (error) {
    console.error('Error downloading document:', error)
  }
}

const handleShare = (document) => {
  sharingDocument.value = document
}

const loadDocuments = async () => {
  loading.value = true
  try {
    const response = await api.get('/documents/shared-with-me')
    documents.value = response.data.data
  } catch (error) {
    console.error('Error loading shared documents:', error)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadDocuments()
  applyStagger()
})
</script>