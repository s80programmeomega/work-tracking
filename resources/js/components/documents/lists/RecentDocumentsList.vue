<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        Documents Récents
      </h3>
      <select
        v-model="timeRange"
        class="rounded-3 border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
      >
        <option value="today">Aujourd'hui</option>
        <option value="week">Cette semaine</option>
        <option value="month">Ce mois</option>
        <option value="all">Tous</option>
      </select>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 8" :key="i" class="h-20 animate-pulse rounded-3 bg-gray-200 dark:bg-gray-800"></div>
    </div>

    <!-- Documents Grouped by Date -->
    <div v-else-if="groupedDocuments.length > 0" class="space-y-6">
      <div
        v-for="group in groupedDocuments"
        :key="group.date"
        class="space-y-3"
      >
        <!-- Date Header -->
        <div class="sticky top-0 z-10 flex items-center gap-2 bg-gray-50 py-2 dark:bg-gray-900/50">
          <CalendarIcon class="h-4 w-4 text-gray-400" />
          <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ group.label }}
          </h4>
          <span class="text-xs text-gray-500 dark:text-gray-400">
            ({{ group.documents.length }})
          </span>
        </div>

        <!-- Documents in this group -->
        <div class="space-y-2">
          <div
            v-for="document in group.documents"
            :key="document.id"
            @click="handleView(document)"
            class="group flex cursor-pointer items-center gap-4 rounded-3 border border-gray-200 bg-white p-3 transition-all hover:border-blue-500 dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-blue-400"
          >
            <!-- File Icon -->
            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-3 bg-gray-100 dark:bg-gray-800">
              <component :is="getFileIcon(document)" class="h-6 w-6 text-gray-600 dark:text-gray-400" />
            </div>

            <!-- Document Info -->
            <div class="min-w-0 flex-1">
              <div class="flex items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                  <h5 class="truncate text-sm font-medium text-gray-900 dark:text-white">
                    {{ document.nom }}
                  </h5>
                  <div class="mt-1 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ document.formatted_size }}</span>
                    <span>•</span>
                    <span>{{ getEntityLabel(document.documentable_type) }}</span>
                    <span>•</span>
                    <span>{{ formatTime(document.created_at) }}</span>
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                  <button
                    @click.stop="handleDownload(document)"
                    class="rounded-3 p-1.5 text-gray-400 hover:bg-gray-100 hover:text-blue-600 dark:hover:bg-gray-800"
                    title="Télécharger"
                  >
                    <ArrowDownTrayIcon class="h-4 w-4" />
                  </button>
                  <button
                    @click.stop="handleShare(document)"
                    class="rounded-3 p-1.5 text-gray-400 hover:bg-gray-100 hover:text-blue-600 dark:hover:bg-gray-800"
                    title="Partager"
                  >
                    <ShareIcon class="h-4 w-4" />
                  </button>
                </div>
              </div>

              <!-- User -->
              <div class="mt-2 flex items-center gap-2">
                <div class="h-6 w-6 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-xs font-medium text-blue-600 dark:text-blue-400">
                  {{ getInitials(document.user?.nom) }}
                </div>
                <span class="text-xs text-gray-600 dark:text-gray-400">
                  {{ document.user?.nom }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="rounded-3 border-2 border-dashed border-gray-300 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-800/50">
      <ClockIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
        Aucun document récent
      </h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Les documents que vous créez ou modifiez apparaîtront ici
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
import {
  ClockIcon,
  CalendarIcon,
  ArrowDownTrayIcon,
  ShareIcon,
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

const documents = ref([])
const loading = ref(false)
const timeRange = ref('week')
const selectedDocument = ref(null)
const sharingDocument = ref(null)

const groupedDocuments = computed(() => {
  const filtered = filterByTimeRange(documents.value)
  const groups = {}

  filtered.forEach(doc => {
    const date = new Date(doc.created_at)
    const dateKey = date.toISOString().split('T')[0]

    if (!groups[dateKey]) {
      groups[dateKey] = {
        date: dateKey,
        label: getDateLabel(date),
        documents: []
      }
    }

    groups[dateKey].documents.push(doc)
  })

  return Object.values(groups).sort((a, b) => b.date.localeCompare(a.date))
})

const filterByTimeRange = (docs) => {
  const now = new Date()
  const ranges = {
    today: () => {
      const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
      return docs.filter(d => new Date(d.created_at) >= today)
    },
    week: () => {
      const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000)
      return docs.filter(d => new Date(d.created_at) >= weekAgo)
    },
    month: () => {
      const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000)
      return docs.filter(d => new Date(d.created_at) >= monthAgo)
    },
    all: () => docs
  }

  return (ranges[timeRange.value] || ranges.week)()
}

const getDateLabel = (date) => {
  const now = new Date()
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const yesterday = new Date(today.getTime() - 24 * 60 * 60 * 1000)

  if (date >= today) {
    return "Aujourd'hui"
  } else if (date >= yesterday) {
    return 'Hier'
  } else {
    return date.toLocaleDateString('fr-FR', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
    })
  }
}

const formatTime = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

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
    const response = await api.get('/documents/recent')
    documents.value = response.data.data
  } catch (error) {
    console.error('Error loading recent documents:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadDocuments()
})
</script>