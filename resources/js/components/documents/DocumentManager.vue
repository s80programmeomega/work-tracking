<!-- resources\js\components\documents\DocumentManager.vue -->
<template>
  <div class="space-y-6">
    <!-- Header avec actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
          Documents
        </h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Gérez les documents de {{ entityLabel }}
        </p>
      </div>

      <div class="flex items-center gap-3">
        <!-- Bouton Upload -->
        <button
          v-if="canUpload"
          @click="showUploadModal = true"
          class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
        >
          <CloudArrowUpIcon class="h-5 w-5" />
          Télécharger
        </button>

        <!-- Bouton Stats -->
        <button
          @click="showStats = !showStats"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors"
        >
          <ChartBarIcon class="h-5 w-5" />
          Statistiques
        </button>
      </div>
    </div>

    <!-- Statistiques (si affichées) -->
    <transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 -translate-y-4"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-4"
    >
      <document-stats
        v-if="showStats"
        :documentable-type="documentableType"
        :documentable-id="documentableId"
        @close="showStats = false"
      />
    </transition>

    <!-- Filtres et recherche -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex flex-col sm:flex-row gap-4">
        <!-- Barre de recherche -->
        <div class="flex-1">
          <div class="relative">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher un document..."
              class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
            />
          </div>
        </div>

        <!-- Filtres -->
        <div class="flex gap-3">
          <!-- Filtre par type -->
          <select
            v-model="filterType"
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

          <!-- Vue Grid/List -->
          <div class="flex rounded-lg border border-gray-300 dark:border-gray-700">
            <button
              @click="viewMode = 'grid'"
              :class="[
                'px-3 py-2 text-sm font-medium transition-colors rounded-l-lg',
                viewMode === 'grid'
                  ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400'
                  : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
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
                  : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
              ]"
            >
              <ListBulletIcon class="h-5 w-5" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Liste des documents -->
    <document-list
      :documents="filteredDocuments"
      :loading="loading"
      :view-mode="viewMode"
      @view="handleViewDocument"
      @download="handleDownloadDocument"
      @edit="handleEditDocument"
      @delete="handleDeleteDocument"
      @share="handleShareDocument"
      @version="handleCreateVersion"
    />

    <!-- Modals -->
    <document-upload-modal
      v-if="showUploadModal"
      :documentable-type="documentableType"
      :documentable-id="documentableId"
      @close="showUploadModal = false"
      @uploaded="handleDocumentUploaded"
    />

    <document-viewer-modal
      v-if="selectedDocument"
      :document="selectedDocument"
      @close="selectedDocument = null"
      @download="handleDownloadDocument"
    />

    <document-edit-modal
      v-if="editingDocument"
      :document="editingDocument"
      @close="editingDocument = null"
      @updated="handleDocumentUpdated"
    />

    <document-share-modal
      v-if="sharingDocument"
      :document="sharingDocument"
      @close="sharingDocument = null"
      @shared="handleDocumentShared"
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
import { ref, computed, onMounted, watch } from 'vue'
import { useDocuments } from '@/composables/useDocuments'
import {
  CloudArrowUpIcon,
  MagnifyingGlassIcon,
  ChartBarIcon,
  Squares2X2Icon,
  ListBulletIcon
} from '@heroicons/vue/24/outline'
import DocumentList from './DocumentList.vue'
import DocumentStats from './DocumentStats.vue'
import DocumentUploadModal from './DocumentUploadModal.vue'
import DocumentViewerModal from './DocumentViewerModal.vue'
import DocumentEditModal from './DocumentEditModal.vue'
import DocumentShareModal from './DocumentShareModal.vue'
import DocumentVersionModal from './DocumentVersionModal.vue'

const props = defineProps({
  documentableType: {
    type: String,
    required: true
  },
  documentableId: {
    type: [String, Number],
    required: true
  },
  entityLabel: {
    type: String,
    default: 'cette entité'
  },
  canUpload: {
    type: Boolean,
    default: true
  }
})

const {
  documents,
  loading,
  fetchDocuments,
  downloadDocument,
  deleteDocument
} = useDocuments()

// State
const searchQuery = ref('')
const filterType = ref('')
const viewMode = ref('grid')
const showStats = ref(false)
const showUploadModal = ref(false)
const selectedDocument = ref(null)
const editingDocument = ref(null)
const sharingDocument = ref(null)
const versioningDocument = ref(null)

// Computed
const filteredDocuments = computed(() => {
  let filtered = documents.value

  // Filtre par recherche
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(doc =>
      doc.nom.toLowerCase().includes(query) ||
      doc.description?.toLowerCase().includes(query)
    )
  }

  // Filtre par type
  if (filterType.value) {
    filtered = filtered.filter(doc => {
      const mimeType = doc.mime_type
      switch (filterType.value) {
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

  return filtered
})

// Methods
const loadDocuments = async () => {
  try {
    console.log('Chargement des documents pour:', props.documentableType, props.documentableId);
    await fetchDocuments(props.documentableType, props.documentableId, false)
    console.log('Documents chargés avec succès:', documents.value);
  } catch (error) {
    console.error('Error loading documents:', error);
    console.error('Response data:', error.response?.data);
    console.error('Response status:', error.response?.status);
    
    // Afficher un message d'erreur à l'utilisateur
    if (error.response?.status === 401) {
      alert('Session expirée. Veuillez vous reconnecter.');
      window.location.href = '/login';
    } else {
      alert(`Erreur: ${error.response?.data?.message || error.message}`);
    }
  }
}

const handleViewDocument = (document) => {
  selectedDocument.value = document
}

const handleDownloadDocument = async (document) => {
  try {
    await downloadDocument(document.id, document.nom)
  } catch (error) {
    console.error('Error downloading document:', error)
  }
}

const handleEditDocument = (document) => {
  editingDocument.value = document
}

const handleDeleteDocument = async (document) => {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer "${document.nom}" ?`)) {
    return
  }

  try {
    await deleteDocument(document.id)
  } catch (error) {
    console.error('Error deleting document:', error)
  }
}

const handleShareDocument = (document) => {
  sharingDocument.value = document
}

const handleCreateVersion = (document) => {
  versioningDocument.value = document
}

const handleDocumentUploaded = () => {
  showUploadModal.value = false
  loadDocuments()
}

const handleDocumentUpdated = () => {
  editingDocument.value = null
  loadDocuments()
}

const handleDocumentShared = () => {
  sharingDocument.value = null
}

const handleVersionCreated = () => {
  versioningDocument.value = null
  loadDocuments()
}

// Lifecycle
onMounted(() => {
  loadDocuments()
})

// Watch for prop changes
watch(() => [props.documentableType, props.documentableId], () => {
  loadDocuments()
})
</script>