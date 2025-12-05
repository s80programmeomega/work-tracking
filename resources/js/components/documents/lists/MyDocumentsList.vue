<template>
  <div class="space-y-4">
    <!-- Header with Stats -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
      <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Total</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
              {{ stats.total }}
            </p>
          </div>
          <DocumentIcon class="h-8 w-8 text-blue-500" />
        </div>
      </div>

      <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Partagés</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
              {{ stats.shared }}
            </p>
          </div>
          <ShareIcon class="h-8 w-8 text-purple-500" />
        </div>
      </div>

      <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Téléchargements</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
              {{ stats.downloads }}
            </p>
          </div>
          <ArrowDownTrayIcon class="h-8 w-8 text-green-500" />
        </div>
      </div>

      <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Espace</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
              {{ formatBytes(stats.totalSize) }}
            </p>
          </div>
          <CircleStackIcon class="h-8 w-8 text-orange-500" />
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <!-- Search -->
      <div class="relative flex-1">
        <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Rechercher dans mes documents..."
          class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
        />
      </div>

      <!-- Filters -->
      <div class="flex gap-3">
        <!-- Type Filter -->
        <select
          v-model="typeFilter"
          class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
        >
          <option value="">Tous les types</option>
          <option value="image">Images</option>
          <option value="pdf">PDF</option>
          <option value="document">Documents</option>
          <option value="video">Vidéos</option>
          <option value="audio">Audio</option>
          <option value="archive">Archives</option>
        </select>

        <!-- Entity Filter -->
        <select
          v-model="entityFilter"
          class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
        >
          <option value="">Tous les niveaux</option>
          <option value="App\Models\Workspace">Workspaces</option>
          <option value="App\Models\Projet">Projets</option>
          <option value="App\Models\Activite">Activités</option>
          <option value="App\Models\Tache">Tâches</option>
          <option value="App\Models\TacheResultat">Résultats</option>
        </select>

        <!-- View Mode -->
        <div class="flex rounded-lg border border-gray-300 dark:border-gray-700">
          <button
            @click="viewMode = 'grid'"
            :class="[
              'px-3 py-2 text-sm font-medium transition-colors rounded-l-lg',
              viewMode === 'grid'
                ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400'
                : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300'
            ]"
          >
            <Squares2X2Icon class="h-5 w-5" />
          </button>
          <button
            @click="viewMode = 'list'"
            :class="[
              'px-3 py-2 text-sm font-medium transition-colors rounded-r-lg border-l border-gray-300 dark:border-gray-700',
              viewMode === 'list'
                ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400'
                : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300'
            ]"
          >
            <ListBulletIcon class="h-5 w-5" />
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
      <div v-for="i in 9" :key="i" class="h-48 animate-pulse rounded-lg bg-gray-200 dark:bg-gray-800"></div>
    </div>

    <!-- Documents -->
    <document-list
      v-else
      :documents="filteredDocuments"
      :loading="loading"
      :view-mode="viewMode"
      @view="handleView"
      @download="handleDownload"
      @edit="handleEdit"
      @delete="handleDelete"
      @share="handleShare"
      @version="handleVersion"
    />

    <!-- Modals -->
    <document-viewer-modal
      v-if="selectedDocument"
      :document="selectedDocument"
      @close="selectedDocument = null"
      @download="handleDownload"
    />

    <document-edit-modal
      v-if="editingDocument"
      :document="editingDocument"
      @close="editingDocument = null"
      @updated="handleUpdated"
    />

    <document-share-modal
      v-if="sharingDocument"
      :document="sharingDocument"
      @close="sharingDocument = null"
    />

    <document-version-modal
      v-if="versioningDocument"
      :document="versioningDocument"
      @close="versioningDocument = null"
      @created="handleVersionCreated"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  DocumentIcon,
  ShareIcon,
  ArrowDownTrayIcon,
  CircleStackIcon,
  MagnifyingGlassIcon,
  Squares2X2Icon,
  ListBulletIcon
} from '@heroicons/vue/24/outline'
import { useDocuments } from '@/composables/useDocuments'
import DocumentList from '@/components/documents/DocumentList.vue'
import DocumentViewerModal from '@/components/documents/DocumentViewerModal.vue'
import DocumentEditModal from '@/components/documents/DocumentEditModal.vue'
import DocumentShareModal from '@/components/documents/DocumentShareModal.vue'
import DocumentVersionModal from '@/components/documents/DocumentVersionModal.vue'
import api from '@/api/axios'

const { downloadDocument, deleteDocument } = useDocuments()

const documents = ref([])
const loading = ref(false)
const searchQuery = ref('')
const typeFilter = ref('')
const entityFilter = ref('')
const viewMode = ref('grid')
const stats = ref({
  total: 0,
  shared: 0,
  downloads: 0,
  totalSize: 0
})

const selectedDocument = ref(null)
const editingDocument = ref(null)
const sharingDocument = ref(null)
const versioningDocument = ref(null)

const filteredDocuments = computed(() => {
  let filtered = documents.value

  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(doc =>
      doc.nom.toLowerCase().includes(query) ||
      doc.description?.toLowerCase().includes(query)
    )
  }

  // Type filter
  if (typeFilter.value) {
    filtered = filtered.filter(doc => {
      const mimeType = doc.mime_type
      switch (typeFilter.value) {
        case 'image':
          return mimeType.startsWith('image/')
        case 'pdf':
          return mimeType === 'application/pdf'
        case 'document':
          return mimeType.includes('word') || mimeType.includes('document')
        case 'video':
          return mimeType.startsWith('video/')
        case 'audio':
          return mimeType.startsWith('audio/')
        case 'archive':
          return mimeType.includes('zip') || mimeType.includes('compressed')
        default:
          return true
      }
    })
  }

  // Entity filter
  if (entityFilter.value) {
    filtered = filtered.filter(doc => doc.documentable_type === entityFilter.value)
  }

  return filtered
})

const formatBytes = (bytes) => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
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

const handleEdit = (document) => {
  editingDocument.value = document
}

const handleDelete = async (document) => {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer "${document.nom}" ?`)) {
    return
  }

  try {
    await deleteDocument(document.id)
    loadDocuments()
  } catch (error) {
    console.error('Error deleting document:', error)
  }
}

const handleShare = (document) => {
  sharingDocument.value = document
}

const handleVersion = (document) => {
  versioningDocument.value = document
}

const handleUpdated = () => {
  editingDocument.value = null
  loadDocuments()
}

const handleVersionCreated = () => {
  versioningDocument.value = null
  loadDocuments()
}

const loadDocuments = async () => {
  loading.value = true
  try {
    const response = await api.get('/documents/my-documents')
    documents.value = response.data.data

    // Calculate stats
    stats.value = {
      total: documents.value.length,
      shared: documents.value.filter(d => d.shared_with_count > 0).length,
      downloads: documents.value.reduce((sum, d) => sum + (d.download_count || 0), 0),
      totalSize: documents.value.reduce((sum, d) => sum + (d.taille || 0), 0)
    }
  } catch (error) {
    console.error('Error loading my documents:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadDocuments()
})
</script>