<!-- resources\js\components\documents\DocumentUpload.vue -->
<template>
  <div class="space-y-4">
    <!-- Dropzone -->
    <div
      @drop.prevent="handleDrop"
      @dragover.prevent="dragOver = true"
      @dragleave.prevent="dragOver = false"
      :class="[
        'relative rounded-2xl border-2 border-dashed p-8 text-center transition-all',
        dragOver
          ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
          : 'border-gray-300 bg-gray-50 hover:border-gray-400 dark:border-gray-700 dark:bg-gray-900/50'
      ]"
    >
      <input
        ref="fileInput"
        type="file"
        multiple
        @change="handleFileSelect"
        class="hidden"
        :accept="acceptedFileTypes"
      />

      <div class="space-y-3">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
          <CloudArrowUpIcon class="h-8 w-8 text-blue-600 dark:text-blue-400" />
        </div>

        <div>
          <p class="text-base font-medium text-gray-900 dark:text-white">
            Glissez-déposez vos fichiers ici
          </p>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            ou
            <button
              type="button"
              @click="$refs.fileInput.click()"
              class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400"
            >
              parcourez vos fichiers
            </button>
          </p>
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400">
          {{ maxFileSizeText }} • {{ allowedFormatsText }}
        </p>
      </div>
    </div>

    <!-- Selected Files List -->
    <transition-group
      v-if="selectedFiles.length > 0"
      name="list"
      tag="div"
      class="space-y-3"
    >
      <div
        v-for="(file, index) in selectedFiles"
        :key="file.id"
        class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]"
      >
        <!-- File Icon -->
        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800">
          <component :is="getFileIcon(file.type)" class="h-6 w-6 text-gray-600 dark:text-gray-400" />
        </div>

        <!-- File Info -->
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
            {{ file.name }}
          </p>
          <div class="mt-1 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
            <span>{{ formatFileSize(file.size) }}</span>
            <span v-if="file.progress !== undefined" class="flex items-center gap-1">
              • {{ file.progress }}%
            </span>
            <span v-if="file.error" class="text-red-600 dark:text-red-400">
              • {{ file.error }}
            </span>
          </div>

          <!-- Progress Bar -->
          <div v-if="file.progress !== undefined" class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
            <div
              class="h-full rounded-full bg-blue-600 transition-all duration-300"
              :style="{ width: `${file.progress}%` }"
            ></div>
          </div>
        </div>

        <!-- Actions -->
        <button
          v-if="!file.uploading"
          @click="removeFile(index)"
          class="flex-shrink-0 rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"
        >
          <XMarkIcon class="h-5 w-5" />
        </button>
        <div v-else class="flex-shrink-0">
          <div class="h-5 w-5 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"></div>
        </div>
      </div>
    </transition-group>

    <!-- Upload Options -->
    <div v-if="selectedFiles.length > 0" class="space-y-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
      <!-- Description -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Description (optionnelle)
        </label>
        <textarea
          v-model="description"
          rows="2"
          placeholder="Ajoutez une description pour ces documents..."
          class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
        ></textarea>
      </div>

      <!-- Visibility -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Visibilité
        </label>
        <select
          v-model="visibility"
          class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
        >
          <option value="private">Privé (seulement moi)</option>
          <option value="team">Équipe (membres sélectionnés)</option>
          <option value="public">Public (tous les membres du workspace)</option>
        </select>
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-end gap-3">
        <button
          type="button"
          @click="clearAll"
          :disabled="uploading"
          class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
        >
          Annuler
        </button>
        <button
          type="button"
          @click="uploadFiles"
          :disabled="uploading || selectedFiles.length === 0"
          class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
        >
          <CloudArrowUpIcon v-if="!uploading" class="h-5 w-5" />
          <div v-else class="h-5 w-5 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
          {{ uploading ? 'Téléchargement...' : 'Télécharger' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useDocuments } from '@/composables/useDocuments'
import {
  CloudArrowUpIcon,
  XMarkIcon,
  DocumentTextIcon,
  PhotoIcon,
  FilmIcon,
  MusicalNoteIcon,
  ArchiveBoxIcon,
  DocumentIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  documentableType: {
    type: String,
    required: true
  },
  documentableId: {
    type: [String, Number],
    required: true
  },
  maxFileSize: {
    type: Number,
    default: 10 * 1024 * 1024 // 10MB
  },
  allowedTypes: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['uploaded', 'error'])

const { uploadDocuments, uploading } = useDocuments()

const fileInput = ref(null)
const selectedFiles = ref([])
const dragOver = ref(false)
const description = ref('')
const visibility = ref('private')

let fileIdCounter = 0

const acceptedFileTypes = computed(() => {
  if (props.allowedTypes.length === 0) return '*'
  return props.allowedTypes.join(',')
})

const maxFileSizeText = computed(() => {
  const mb = props.maxFileSize / (1024 * 1024)
  return `Max ${mb}MB par fichier`
})

const allowedFormatsText = computed(() => {
  if (props.allowedTypes.length === 0) return 'Tous les formats acceptés'
  return props.allowedTypes.map(t => t.replace('.', '').toUpperCase()).join(', ')
})

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files)
  addFiles(files)
  event.target.value = ''
}

const handleDrop = (event) => {
  dragOver.value = false
  const files = Array.from(event.dataTransfer.files)
  addFiles(files)
}

const addFiles = (files) => {
  files.forEach(file => {
    // Validate file size
    if (file.size > props.maxFileSize) {
      emit('error', `Le fichier "${file.name}" est trop volumineux`)
      return
    }

    // Validate file type
    if (props.allowedTypes.length > 0) {
      const extension = '.' + file.name.split('.').pop().toLowerCase()
      if (!props.allowedTypes.includes(extension)) {
        emit('error', `Le fichier "${file.name}" n'est pas d'un type autorisé`)
        return
      }
    }

    selectedFiles.value.push({
      id: fileIdCounter++,
      file,
      name: file.name,
      size: file.size,
      type: file.type,
      progress: undefined,
      uploading: false,
      error: null
    })
  })
}

const removeFile = (index) => {
  selectedFiles.value.splice(index, 1)
}

const clearAll = () => {
  selectedFiles.value = []
  description.value = ''
  visibility.value = 'private'
}

const uploadFiles = async () => {
  if (selectedFiles.value.length === 0) return

  try {
    const files = selectedFiles.value.map(f => f.file)

    // Mark all as uploading
    selectedFiles.value.forEach(f => {
      f.uploading = true
      f.progress = 0
    })

    const result = await uploadDocuments(
      files,
      props.documentableType,
      props.documentableId,
      {
        description: description.value,
        visibility: visibility.value
      }
    )

    emit('uploaded', result)
    clearAll()
  } catch (error) {
    selectedFiles.value.forEach(f => {
      f.uploading = false
      f.error = 'Échec du téléchargement'
    })
    emit('error', error.message)
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

<style scoped>
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}

.list-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.list-leave-to {
  opacity: 0;
  transform: translateX(-20px);
}
</style>