<template>
  <TransitionRoot as="template" :show="true">
    <Dialog as="div" class="relative z-50" @close="$emit('close')">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-80" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-3 bg-white text-left transition-all dark:bg-gray-900 sm:my-8 sm:w-full sm:max-w-lg">
              <!-- Header -->
              <div class="border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                  <DialogTitle class="text-lg font-semibold text-gray-900 dark:text-white">
                    Créer une nouvelle version
                  </DialogTitle>
                  <button
                    @click="$emit('close')"
                    class="rounded-3 p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-500 dark:text-gray-400 dark:hover:bg-gray-800"
                  >
                    <XMarkIcon class="h-6 w-6" />
                  </button>
                </div>
              </div>

              <!-- Body -->
              <div class="bg-white px-6 py-5 dark:bg-gray-900">
                <!-- Current Document Info -->
                <div class="rounded-3 border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                  <div class="flex items-center gap-4">
                    <component
                      :is="getFileIcon(document)"
                      class="h-12 w-12 flex-shrink-0 text-gray-400"
                    />
                    <div class="min-w-0 flex-1">
                      <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                        {{ document.nom }}
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        Version actuelle : v{{ document.version }}
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ document.formatted_size }}
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Upload New Version -->
                <div class="mt-5">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Nouveau fichier (v{{ document.version + 1 }})
                  </label>
                  
                  <div
                    @click="$refs.fileInput.click()"
                    @drop.prevent="handleDrop"
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    :class="[
                      'mt-2 cursor-pointer rounded-3 border-2 border-dashed transition-all',
                      isDragging
                        ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20'
                        : 'border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50'
                    ]"
                  >
                    <input
                      ref="fileInput"
                      type="file"
                      @change="handleFileSelect"
                      class="hidden"
                      :accept="document.mime_type"
                    />

                    <div v-if="!selectedFile" class="p-8 text-center">
                      <DocumentPlusIcon class="mx-auto h-12 w-12 text-gray-400" />
                      <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Cliquez ou glissez-déposez le nouveau fichier
                      </p>
                      <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Même type que l'original (.{{ document.extension }})
                      </p>
                    </div>

                    <div v-else class="flex items-center gap-3 p-4">
                      <component
                        :is="getFileIcon(document)"
                        class="h-10 w-10 flex-shrink-0 text-gray-400"
                      />
                      <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                          {{ selectedFile.name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                          {{ formatFileSize(selectedFile.size) }}
                        </p>
                      </div>
                      <button
                        @click.stop="selectedFile = null"
                        class="text-gray-400 hover:text-red-600"
                      >
                        <XMarkIcon class="h-5 w-5" />
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Version History -->
                <div class="mt-6">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                    Historique des versions
                  </h4>
                  <div class="mt-3 max-h-48 space-y-2 overflow-y-auto">
                    <div
                      v-for="version in versions"
                      :key="version.id"
                      class="flex items-center justify-between rounded-3 border border-gray-200 bg-white p-3 text-sm dark:border-gray-700 dark:bg-gray-800"
                    >
                      <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-medium text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                          v{{ version.version }}
                        </div>
                        <div>
                          <p class="font-medium text-gray-900 dark:text-white">
                            {{ version.user.nom }}
                          </p>
                          <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ formatDate(version.created_at) }}
                          </p>
                        </div>
                      </div>
                      <span
                        v-if="version.is_latest_version"
                        class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400"
                      >
                        Actuelle
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Upload Progress -->
                <div v-if="uploading" class="mt-5">
                  <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-700 dark:text-gray-300">Création de la version...</span>
                    <span class="font-medium text-blue-600 dark:text-blue-400">{{ uploadProgress }}%</span>
                  </div>
                  <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                    <div
                      class="h-full bg-blue-600 transition-all duration-300"
                      :style="{ width: uploadProgress + '%' }"
                    ></div>
                  </div>
                </div>

                <!-- Error Message -->
                <div v-if="error" class="mt-4 rounded-3 bg-red-50 p-4 dark:bg-red-900/20">
                  <div class="flex items-start gap-3">
                    <ExclamationTriangleIcon class="h-5 w-5 flex-shrink-0 text-red-600 dark:text-red-400" />
                    <p class="text-sm text-red-800 dark:text-red-300">{{ error }}</p>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-800 dark:bg-gray-800/50">
                <div class="flex justify-end gap-3">
                  <button
                    @click="$emit('close')"
                    :disabled="uploading"
                    class="rounded-3 border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                  >
                    Annuler
                  </button>
                  <button
                    @click="handleCreateVersion"
                    :disabled="!selectedFile || uploading"
                    class="inline-flex items-center gap-2 rounded-3 bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <div v-if="uploading" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                    <DocumentDuplicateIcon v-else class="h-5 w-5" />
                    {{ uploading ? 'Création...' : 'Créer la version' }}
                  </button>
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import {
  XMarkIcon,
  DocumentPlusIcon,
  DocumentDuplicateIcon,
  ExclamationTriangleIcon,
  DocumentIcon,
  PhotoIcon,
  FilmIcon,
  MusicalNoteIcon,
  ArchiveBoxIcon,
  DocumentTextIcon
} from '@heroicons/vue/24/outline'
import { useDocuments } from '@/composables/useDocuments'
import api from '@/api/axios'

const props = defineProps({
  document: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'created'])

const { createVersion, uploading, uploadProgress, error } = useDocuments()

const fileInput = ref(null)
const selectedFile = ref(null)
const isDragging = ref(false)
const versions = ref([])

const handleDrop = (e) => {
  isDragging.value = false
  const file = e.dataTransfer.files[0]
  if (file) {
    selectedFile.value = file
  }
}

const handleFileSelect = (e) => {
  const file = e.target.files[0]
  if (file) {
    selectedFile.value = file
  }
}

const handleCreateVersion = async () => {
  if (!selectedFile.value) return

  try {
    await createVersion(props.document.id, selectedFile.value)
    emit('created')
  } catch (err) {
    console.error('Create version error:', err)
  }
}

const loadVersions = async () => {
  try {
    const response = await api.get(`/documents/${props.document.id}/versions`)
    versions.value = response.data.data
  } catch (err) {
    console.error('Load versions error:', err)
  }
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

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

onMounted(() => {
  loadVersions()
})
</script>
