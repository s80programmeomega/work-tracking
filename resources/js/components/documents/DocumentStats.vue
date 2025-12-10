<template>
  <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        Statistiques des documents
      </h3>
      <button
        @click="$emit('close')"
        class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800"
      >
        <XMarkIcon class="h-5 w-5" />
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="py-12 text-center">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-2 border-blue-600 border-t-transparent"></div>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Chargement des statistiques...</p>
    </div>

    <!-- Stats Content -->
    <div v-else-if="stats" class="space-y-6">
      <!-- Overview Cards -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Total Documents -->
        <div class="rounded-xl border border-gray-200 bg-gradient-to-br from-blue-50 to-blue-100 p-4 dark:border-gray-700 dark:from-blue-900/20 dark:to-blue-900/10">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-blue-600 dark:text-blue-400">
                Total Documents
              </p>
              <p class="mt-2 text-3xl font-bold text-blue-900 dark:text-blue-300">
                {{ stats.total_documents }}
              </p>
            </div>
            <DocumentIcon class="h-12 w-12 text-blue-600/30 dark:text-blue-400/30" />
          </div>
        </div>

        <!-- Total Size -->
        <div class="rounded-xl border border-gray-200 bg-gradient-to-br from-green-50 to-green-100 p-4 dark:border-gray-700 dark:from-green-900/20 dark:to-green-900/10">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-green-600 dark:text-green-400">
                Taille Totale
              </p>
              <p class="mt-2 text-3xl font-bold text-green-900 dark:text-green-300">
                {{ formatBytes(stats.total_size) }}
              </p>
            </div>
            <ServerIcon class="h-12 w-12 text-green-600/30 dark:text-green-400/30" />
          </div>
        </div>

        <!-- Total Downloads -->
        <div class="rounded-xl border border-gray-200 bg-gradient-to-br from-purple-50 to-purple-100 p-4 dark:border-gray-700 dark:from-purple-900/20 dark:to-purple-900/10">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-purple-600 dark:text-purple-400">
                Téléchargements
              </p>
              <p class="mt-2 text-3xl font-bold text-purple-900 dark:text-purple-300">
                {{ stats.total_downloads }}
              </p>
            </div>
            <ArrowDownTrayIcon class="h-12 w-12 text-purple-600/30 dark:text-purple-400/30" />
          </div>
        </div>

        <!-- Users Count -->
        <div class="rounded-xl border border-gray-200 bg-gradient-to-br from-orange-50 to-orange-100 p-4 dark:border-gray-700 dark:from-orange-900/20 dark:to-orange-900/10">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-orange-600 dark:text-orange-400">
                Contributeurs
              </p>
              <p class="mt-2 text-3xl font-bold text-orange-900 dark:text-orange-300">
                {{ Object.keys(stats.by_mime_type || {}).length }}
              </p>
            </div>
            <UsersIcon class="h-12 w-12 text-orange-600/30 dark:text-orange-400/30" />
          </div>
        </div>
      </div>

      <!-- Documents by Type -->
      <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-800/50">
        <h4 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">
          Documents par type
        </h4>
        <div class="space-y-3">
          <div
            v-for="(count, type) in stats.by_type"
            :key="type"
            class="flex items-center justify-between"
          >
            <div class="flex items-center gap-3">
              <component
                :is="getTypeIcon(type)"
                class="h-5 w-5 text-gray-400"
              />
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ getTypeName(type) }}
              </span>
            </div>
            <div class="flex items-center gap-3">
              <div class="h-2 w-32 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                <div
                  class="h-full bg-blue-600"
                  :style="{ width: (count / stats.total_documents * 100) + '%' }"
                ></div>
              </div>
              <span class="w-8 text-right text-sm font-semibold text-gray-900 dark:text-white">
                {{ count }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Documents by MIME Type -->
      <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-800/50">
        <h4 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">
          Formats de fichiers
        </h4>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
          <div
            v-for="(count, mimeType) in stats.by_mime_type"
            :key="mimeType"
            class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800"
          >
            <p class="truncate text-xs text-gray-500 dark:text-gray-400" :title="mimeType">
              {{ mimeType.split('/')[1] || mimeType }}
            </p>
            <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
              {{ count }}
            </p>
          </div>
        </div>
      </div>

      <!-- Recent Uploads -->
      <div v-if="stats.recent_uploads && stats.recent_uploads.length > 0" class="rounded-xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-800/50">
        <h4 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">
          Téléchargements récents
        </h4>
        <div class="space-y-2">
          <div
            v-for="doc in stats.recent_uploads.slice(0, 5)"
            :key="doc.id"
            class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800"
          >
            <component
              :is="getFileIcon(doc.mime_type)"
              class="h-8 w-8 flex-shrink-0 text-gray-400"
            />
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                {{ doc.nom }}
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ formatDate(doc.created_at) }} • {{ doc.formatted_size }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="py-12 text-center">
      <ExclamationTriangleIcon class="mx-auto h-12 w-12 text-red-500" />
      <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import {
  XMarkIcon,
  DocumentIcon,
  ArrowDownTrayIcon,
  ServerIcon,
  UsersIcon,
  ExclamationTriangleIcon,
  PhotoIcon,
  FilmIcon,
  MusicalNoteIcon,
  ArchiveBoxIcon,
  DocumentTextIcon,
  FolderIcon
} from '@heroicons/vue/24/outline'
import api from '@/api/axios'

const props = defineProps({
  documentableType: {
    type: String,
    required: true
  },
  documentableId: {
    type: [String, Number],
    required: true
  }
})

defineEmits(['close'])

const stats = ref(null)
const loading = ref(false)
const error = ref(null)

const loadStats = async () => {
  loading.value = true
  error.value = null

  try {
    // Si c'est un workspace, utiliser l'endpoint workspace
    if (props.documentableType.includes('Workspace')) {
      const response = await api.get(`/documents/workspace/${props.documentableId}/stats`)
      stats.value = response.data.data
    } else {
      // Sinon, calculer les stats côté client à partir des documents
      const docsResponse = await api.get('/documents', {
        params: {
          documentable_type: props.documentableType,
          documentable_id: props.documentableId
        }
      })
      
      const documents = docsResponse.data.data
      
      stats.value = {
        total_documents: documents.length,
        total_size: documents.reduce((sum, doc) => sum + doc.taille, 0),
        total_downloads: documents.reduce((sum, doc) => sum + doc.download_count, 0),
        by_type: groupByType(documents),
        by_mime_type: groupByMimeType(documents),
        recent_uploads: documents.slice(0, 10)
      }
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors du chargement des statistiques'
    console.error('Load stats error:', err)
  } finally {
    loading.value = false
  }
}

const groupByType = (documents) => {
  const grouped = {}
  documents.forEach(doc => {
    const type = doc.documentable_type.split('\\').pop()
    grouped[type] = (grouped[type] || 0) + 1
  })
  return grouped
}

const groupByMimeType = (documents) => {
  const grouped = {}
  documents.forEach(doc => {
    grouped[doc.mime_type] = (grouped[doc.mime_type] || 0) + 1
  })
  return grouped
}

const formatBytes = (bytes) => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
}

const getTypeIcon = (type) => {
  const iconMap = {
    'Workspace': FolderIcon,
    'Projet': FolderIcon,
    'Activite': FolderIcon,
    'Tache': DocumentIcon,
    'TacheResultat': DocumentIcon
  }
  return iconMap[type] || DocumentIcon
}

const getTypeName = (type) => {
  const nameMap = {
    'Workspace': 'Workspace',
    'Projet': 'Projets',
    'Activite': 'Activités',
    'Tache': 'Tâches',
    'TacheResultat': 'Résultats'
  }
  return nameMap[type] || type
}

const getFileIcon = (mimeType) => {
  if (mimeType.startsWith('image/')) return PhotoIcon
  if (mimeType === 'application/pdf') return DocumentTextIcon
  if (mimeType.startsWith('video/')) return FilmIcon
  if (mimeType.startsWith('audio/')) return MusicalNoteIcon
  if (mimeType.includes('zip') || mimeType.includes('compressed')) return ArchiveBoxIcon
  return DocumentIcon
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  const now = new Date()
  const diffInMs = now - date
  const diffInDays = Math.floor(diffInMs / (1000 * 60 * 60 * 24))

  if (diffInDays === 0) return 'Aujourd\'hui'
  if (diffInDays === 1) return 'Hier'
  if (diffInDays < 7) return `Il y a ${diffInDays} jours`
  
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

onMounted(() => {
  loadStats()
})
</script>
