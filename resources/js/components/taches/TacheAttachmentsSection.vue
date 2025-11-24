<!-- resources/js/components/taches/TacheAttachmentsSection.vue -->
<template>
  <div class="space-y-4">
    <!-- Header avec bouton d'upload -->
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        Fichiers attachés ({{ attachments.length }})
      </h3>
      
      <label 
        v-if="canEdit"
        class="px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 cursor-pointer flex items-center gap-2 transition-all"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Ajouter un fichier</span>
        <input 
          type="file" 
          @change="handleFileUpload" 
          class="hidden" 
          multiple
          accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.zip,.txt"
        />
      </label>
    </div>

    <!-- Loading -->
    <div v-if="uploading" class="flex items-center gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
      <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-500"></div>
      <span class="text-sm text-blue-700 dark:text-blue-400">Upload en cours...</span>
    </div>

    <!-- Liste des fichiers -->
    <div v-if="attachments.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div
        v-for="attachment in attachments"
        :key="attachment.id"
        class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors group"
      >
        <!-- Icône du fichier -->
        <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center"
             :class="getFileIconClass(attachment.mime_type)">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
          </svg>
        </div>

        <!-- Info fichier -->
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
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
          </div>
        </div>

        <!-- Actions -->
        <div class="flex-shrink-0 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
          <!-- Télécharger -->
          <button
            @click="downloadFile(attachment)"
            class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
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
            class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
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
    <div v-else class="text-center py-12 bg-gray-50 dark:bg-gray-900 rounded-xl">
      <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="text-gray-600 dark:text-gray-400 mb-4">Aucun fichier attaché</p>
      <label 
        v-if="canEdit"
        class="inline-flex items-center px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 cursor-pointer transition-all"
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
          accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.zip,.txt"
        />
      </label>
    </div>
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

const handleFileUpload = async (event) => {
  const files = Array.from(event.target.files)
  if (files.length === 0) return

  uploading.value = true

  try {
    const formData = new FormData()
    files.forEach(file => {
      formData.append('files[]', file)
    })

    await api.post(`/taches/${props.tacheId}/attachments`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    emit('updated')
    event.target.value = '' // Reset input
  } catch (error) {
    console.error('Error uploading files:', error)
    alert('Erreur lors de l\'upload des fichiers')
  } finally {
    uploading.value = false
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
    alert('Erreur lors du téléchargement')
  }
}

const deleteFile = async (attachment) => {
  if (!confirm(`Supprimer le fichier "${attachment.original_name}" ?`)) return

  try {
    await api.delete(`/taches/${props.tacheId}/attachments/${attachment.id}`)
    emit('updated')
  } catch (error) {
    console.error('Error deleting file:', error)
    alert('Erreur lors de la suppression')
  }
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