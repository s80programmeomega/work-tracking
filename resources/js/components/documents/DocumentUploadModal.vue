<template>
  <TransitionRoot as="template" :show="true">
    <Dialog as="div" class="relative z-50" @close="$emit('close')">
      <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100"
        leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-80" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild as="template" enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            <DialogPanel
              class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all dark:bg-gray-900 sm:my-8 sm:w-full sm:max-w-2xl">
              <!-- Header -->
              <div class="border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                  <DialogTitle class="text-lg font-semibold text-gray-900 dark:text-white">
                    Télécharger des documents
                  </DialogTitle>
                  <button @click="$emit('close')"
                    class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-500 dark:hover:bg-gray-800">
                    <XMarkIcon class="h-6 w-6" />
                  </button>
                </div>
              </div>

              <!-- Body -->
              <div class="bg-white px-6 py-5 dark:bg-gray-900">
                <!-- Drag & Drop Zone -->
                <div @drop.prevent="handleDrop" @dragover.prevent="isDragging = true"
                  @dragleave.prevent="isDragging = false" :class="[
                    'relative rounded-xl border-2 border-dashed transition-all',
                    isDragging
                      ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20'
                      : 'border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50'
                  ]">
                  <input ref="fileInput" type="file" multiple @change="handleFileSelect" class="hidden" />

                  <div v-if="selectedFiles.length === 0" class="p-12 text-center">
                    <CloudArrowUpIcon class="mx-auto h-16 w-16 text-gray-400" />
                    <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">
                      Glissez-déposez vos fichiers ici
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                      ou cliquez pour sélectionner
                    </p>
                    <button @click="$refs.fileInput.click()"
                      class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                      <DocumentPlusIcon class="h-5 w-5" />
                      Parcourir les fichiers
                    </button>
                    <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                      Taille maximale : {{ maxSizeMB }}MB par fichier
                    </p>
                  </div>

                  <!-- Selected Files List -->
                  <div v-else class="p-4 space-y-3">
                    <div class="flex items-center justify-between">
                      <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ selectedFiles.length }} fichier(s) sélectionné(s)
                      </h4>
                      <button @click="clearFiles" class="text-sm text-red-600 hover:text-red-700 dark:text-red-400">
                        Tout supprimer
                      </button>
                    </div>

                    <div class="max-h-64 space-y-2 overflow-y-auto">
                      <div v-for="(file, index) in selectedFiles" :key="index"
                        class="flex items-center gap-3 rounded-lg bg-white p-3 dark:bg-gray-800">
                        <component :is="getFileIcon(file.type)" class="h-8 w-8 flex-shrink-0 text-gray-400" />
                        <div class="min-w-0 flex-1">
                          <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                            {{ file.name }}
                          </p>
                          <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ formatFileSize(file.size) }}
                          </p>
                        </div>
                        <button @click="removeFile(index)" class="flex-shrink-0 text-gray-400 hover:text-red-600">
                          <XMarkIcon class="h-5 w-5" />
                        </button>
                      </div>
                    </div>

                    <button @click="$refs.fileInput.click()"
                      class="w-full rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 py-2 text-sm text-gray-600 hover:border-blue-500 hover:bg-blue-50 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:border-blue-400 dark:hover:bg-blue-900/20">
                      + Ajouter d'autres fichiers
                    </button>
                  </div>
                </div>

                <!-- Options -->
                <div v-if="selectedFiles.length > 0" class="mt-5 space-y-4">
                  <!-- Description -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Description (optionnelle)
                    </label>
                    <textarea v-model="description" rows="2"
                      class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                      placeholder="Ajoutez une description pour ces documents..."></textarea>
                  </div>

                  <!-- Visibility -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Visibilité
                    </label>
                    <select v-model="visibility"
                      class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                      <option value="private">Privé (propriétaire uniquement)</option>
                      <option value="team">Équipe (membres de l'entité)</option>
                      <option value="public">Public (tous les utilisateurs)</option>
                    </select>
                  </div>

                  <!-- Allow Duplicates -->
                  <div class="flex items-center">
                    <input v-model="allowDuplicates" type="checkbox" id="allowDuplicates"
                      class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-700" />
                    <label for="allowDuplicates" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                      Autoriser les doublons
                    </label>
                  </div>
                </div>

                <!-- Upload Progress -->
                <div v-if="uploading" class="mt-5">
                  <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-700 dark:text-gray-300">Téléchargement en cours...</span>
                    <span class="font-medium text-blue-600 dark:text-blue-400">{{ uploadProgress }}%</span>
                  </div>
                  <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                    <div class="h-full bg-blue-600 transition-all duration-300"
                      :style="{ width: uploadProgress + '%' }"></div>
                  </div>
                </div>

                <!-- Error Message -->
                <div v-if="error" class="mt-4 rounded-lg bg-red-50 p-4 dark:bg-red-900/20">
                  <div class="flex items-start gap-3">
                    <ExclamationTriangleIcon class="h-5 w-5 flex-shrink-0 text-red-600 dark:text-red-400" />
                    <p class="text-sm text-red-800 dark:text-red-300">{{ error }}</p>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-800 dark:bg-gray-800/50">
                <div class="flex justify-end gap-3">
                  <button @click="$emit('close')" :disabled="uploading"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                    Annuler
                  </button>
                  <button @click="handleUpload" :disabled="selectedFiles.length === 0 || uploading"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <CloudArrowUpIcon v-if="!uploading" class="h-5 w-5" />
                    <div v-else class="h-5 w-5 animate-spin rounded-full border-2 border-white border-t-transparent">
                    </div>
                    {{ uploading ? 'Téléchargement...' : 'Télécharger' }}
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
import { ref } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import {
  CloudArrowUpIcon,
  XMarkIcon,
  DocumentPlusIcon,
  ExclamationTriangleIcon,
  DocumentIcon,
  PhotoIcon,
  FilmIcon,
  MusicalNoteIcon,
  ArchiveBoxIcon,
  DocumentTextIcon
} from '@heroicons/vue/24/outline'
import { useDocuments } from '@/composables/useDocuments'

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

const emit = defineEmits(['close', 'uploaded'])

const { uploadDocuments, uploading, uploadProgress, error } = useDocuments()

const fileInput = ref(null)
const selectedFiles = ref([])
const isDragging = ref(false)
const description = ref('')
const visibility = ref('private')
const allowDuplicates = ref(false)
const maxSizeMB = ref(10)

const handleDrop = (e) => {
  isDragging.value = false
  const files = Array.from(e.dataTransfer.files)
  addFiles(files)
}

const handleFileSelect = (e) => {
  const files = Array.from(e.target.files)
  addFiles(files)
}

const addFiles = (files) => {
  const maxSize = maxSizeMB.value * 1024 * 1024
  const validFiles = files.filter(file => {
    if (file.size > maxSize) {
      error.value = `Le fichier "${file.name}" dépasse la taille maximale de ${maxSizeMB.value}MB`
      return false
    }
    return true
  })

  selectedFiles.value.push(...validFiles)
}

const removeFile = (index) => {
  selectedFiles.value.splice(index, 1)
}

const clearFiles = () => {
  selectedFiles.value = []
}

const handleUpload = async () => {
  if (selectedFiles.value.length === 0) return

  try {
    // ✅ Convertir explicitement en boolean
    const allowDuplicatesValue = Boolean(allowDuplicates.value)

    await uploadDocuments(
      selectedFiles.value,
      props.documentableType,
      props.documentableId,
      {
        description: description.value,
        visibility: visibility.value,
        allow_duplicates: allowDuplicatesValue
      }
    )

    emit('uploaded')
  } catch (err) {
    console.error('Upload error:', err)
  }
}

const getFileIcon = (mimeType) => {
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
</script>