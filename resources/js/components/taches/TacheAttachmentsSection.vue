<!-- resources/js/components/taches/TacheAttachmentsSection.vue -->
<template>
  <div class="space-y-4">
    <!-- Header avec bouton d'upload -->
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
        </svg>
        Fichiers attachés ({{ attachments.length }})
      </h3>
      
      <label 
        v-if="canEdit && !uploading"
        class="px-4 py-2 bg-brand-500 text-white rounded-3 hover:bg-brand-600 cursor-pointer flex items-center gap-2 transition-all "
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Ajouter</span>
        <input 
          type="file" 
          @change="handleFileUpload" 
          class="hidden" 
          multiple
          :accept="acceptedFileTypes"
        />
      </label>
    </div>

    <!-- Informations sur les types acceptés -->
    <div v-if="canEdit" class="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900 p-3 rounded-3">
      <strong>Types acceptés:</strong> PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, Images (JPG, PNG, GIF), ZIP
      <br>
      <strong>Taille max:</strong> 10 Mo par fichier
    </div>

    <!-- Progress upload -->
    <div v-if="uploading" class="space-y-2">
      <div class="flex items-center gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-3 border border-blue-200 dark:border-blue-800">
        <div class="animate-spin rounded-full h-6 w-6 border-3 border-blue-500 border-t-transparent"></div>
        <div class="flex-1">
          <p class="text-sm font-medium text-blue-700 dark:text-blue-400">Upload en cours...</p>
          <p class="text-xs text-blue-600 dark:text-blue-500">{{ uploadProgress }}%</p>
        </div>
      </div>
      <div class="w-full bg-blue-200 dark:bg-blue-900 rounded-full h-2">
        <div 
          class="h-2 rounded-full bg-blue-500 transition-all duration-300" 
          :style="{ width: `${uploadProgress}%` }"
        ></div>
      </div>
    </div>

    <!-- Messages d'erreur -->
    <div v-if="errorMessage" class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-3">
      <div class="flex items-start gap-3">
        <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="flex-1">
          <p class="text-sm font-medium text-red-800 dark:text-red-300">{{ errorMessage }}</p>
        </div>
        <button @click="errorMessage = ''" class="text-red-600 hover:text-red-800">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Liste des fichiers -->
    <div v-if="attachments.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div
        v-for="attachment in attachments"
        :key="attachment.id"
        class="relative flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-700 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all group"
        :class="{ 'ring-2 ring-blue-500': selectedFile === attachment.id }"
      >
        <!-- Preview / Icône -->
        <div 
          class="flex-shrink-0 w-12 h-12 rounded-3 flex items-center justify-center overflow-hidden cursor-pointer"
          :class="getFileIconClass(attachment.mime_type)"
          @click="previewFile(attachment)"
        >
          <!-- Image preview -->
          <img 
            v-if="isImage(attachment.mime_type)" 
            :src="attachment.file_url" 
            :alt="attachment.original_name"
            class="w-full h-full object-cover"
          />
          <!-- Icon fallback -->
          <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
          </svg>
        </div>

        <!-- Info fichier -->
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-gray-900 dark:text-white truncate" :title="attachment.original_name">
            {{ attachment.original_name }}
          </p>
          <div class="flex items-center gap-2 mt-1">
            <span class="text-xs text-gray-500 dark:text-gray-400">
              {{ formatFileSize(attachment.file_size) }}
            </span>
            <span class="text-xs text-gray-400">•</span>
            <span class="text-xs text-gray-500 dark:text-gray-400">
              {{ formatDate(attachment.created_at) }}
            </span>
            <span v-if="attachment.uploaded_by" class="text-xs text-gray-400">•</span>
            <span v-if="attachment.uploaded_by" class="text-xs text-gray-500 dark:text-gray-400">
              {{ attachment.uploaded_by.nom }}
            </span>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex-shrink-0 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
          <!-- Preview (pour images) -->
          <button
            v-if="isImage(attachment.mime_type)"
            @click="previewFile(attachment)"
            class="p-2 text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-3 transition-colors"
            title="Prévisualiser"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </button>

          <!-- Télécharger -->
          <button
            @click="downloadFile(attachment)"
            class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-3 transition-colors"
            title="Télécharger"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
          </button>

          <!-- Supprimer -->
          <button
            v-if="canEdit"
            @click="deleteFile(attachment)"
            :disabled="deleting"
            class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-3 transition-colors disabled:opacity-50"
            title="Supprimer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else-if="!uploading" class="text-center py-12 rounded-3 border-2 border-dashed border-gray-300 dark:border-gray-700">
      <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="text-gray-600 dark:text-gray-400 mb-4">Aucun fichier attaché</p>
      <label 
        v-if="canEdit"
        class="inline-flex items-center px-4 py-2 bg-brand-500 text-white rounded-3 hover:bg-brand-600 cursor-pointer transition-all "
      >
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Ajouter le premier fichier
        <input 
          type="file" 
          @change="handleFileUpload" 
          class="hidden" 
          multiple
          :accept="acceptedFileTypes"
        />
      </label>
    </div>

    <!-- Modal preview d'image -->
    <Teleport to="body">
      <div 
        v-if="previewUrl" 
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 p-4"
        @click="closePreview"
      >
        <button 
          @click="closePreview"
          class="absolute top-4 right-4 p-2 text-white hover:bg-white/20 rounded-3 transition-colors"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
        <img 
          :src="previewUrl" 
          :alt="previewFileName"
          class="max-w-full max-h-full object-contain"
          @click.stop
        />
        <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-black/75 text-white px-4 py-2 rounded-3">
          {{ previewFileName }}
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '@/api/axios'

const props = defineProps({
  tacheId: {
    type: Number,
    required: true
  },
  attachments: {
    type: Array,
    default: () => []
  },
  canEdit: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['updated'])

const uploading = ref(false)
const uploadProgress = ref(0)
const deleting = ref(false)
const errorMessage = ref('')
const selectedFile = ref(null)
const previewUrl = ref('')
const previewFileName = ref('')

const acceptedFileTypes = '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.zip,.txt'

const isImage = (mimeType) => {
  return mimeType && mimeType.startsWith('image/')
}

const handleFileUpload = async (event) => {
  const files = Array.from(event.target.files)
  if (files.length === 0) return

  // Validation taille
  const maxSize = 10 * 1024 * 1024 // 10MB
  const invalidFiles = files.filter(f => f.size > maxSize)
  if (invalidFiles.length > 0) {
    errorMessage.value = `Certains fichiers dépassent 10 Mo: ${invalidFiles.map(f => f.name).join(', ')}`
    event.target.value = ''
    return
  }

  uploading.value = true
  uploadProgress.value = 0
  errorMessage.value = ''

  try {
    const formData = new FormData()
    files.forEach(file => {
      formData.append('files[]', file)
    })

    await api.post(`/taches/${props.tacheId}/attachments`, formData, {
      headers: {
        
      },
      onUploadProgress: (progressEvent) => {
        uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total)
      }
    })

    emit('updated')
    event.target.value = '' // Reset input
  } catch (error) {
    console.error('Error uploading files:', error)
    errorMessage.value = error.response?.data?.message || 'Erreur lors de l\'upload des fichiers'
  } finally {
    uploading.value = false
    uploadProgress.value = 0
  }
}

const downloadFile = async (attachment) => {
  try {
    const response = await api.get(
      `/taches/${props.tacheId}/attachments/${attachment.id}/download`,
      { responseType: 'blob' }
    )

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', attachment.original_name)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Error downloading file:', error)
    errorMessage.value = 'Erreur lors du téléchargement'
  }
}

const deleteFile = async (attachment) => {
  if (!confirm(`Supprimer le fichier "${attachment.original_name}" ?`)) return

  deleting.value = true
  errorMessage.value = ''

  try {
    await api.delete(`/taches/${props.tacheId}/attachments/${attachment.id}`)
    emit('updated')
  } catch (error) {
    console.error('Error deleting file:', error)
    errorMessage.value = 'Erreur lors de la suppression'
  } finally {
    deleting.value = false
  }
}

const previewFile = (attachment) => {
  if (!isImage(attachment.mime_type)) return
  
  previewUrl.value = attachment.file_url
  previewFileName.value = attachment.original_name
  selectedFile.value = attachment.id
}

const closePreview = () => {
  previewUrl.value = ''
  previewFileName.value = ''
  selectedFile.value = null
}

const getFileIconClass = (mimeType) => {
  if (!mimeType) return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
  
  if (mimeType.startsWith('image/')) return 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400'
  if (mimeType.includes('pdf')) return 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
  if (mimeType.includes('word') || mimeType.includes('document')) return 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
  if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
  if (mimeType.includes('powerpoint') || mimeType.includes('presentation')) return 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400'
  if (mimeType.includes('zip') || mimeType.includes('compressed')) return 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400'
  if (mimeType.includes('text')) return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
  
  return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
}

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}
</script>