<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Historique des versions
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ versions.length }} version{{ versions.length > 1 ? 's' : '' }} disponible{{ versions.length > 1 ? 's' : '' }}
        </p>
      </div>

      <button
        @click="showUploadModal = true"
        class="inline-flex items-center gap-2 rounded-3 bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
      >
        <DocumentPlusIcon class="h-5 w-5" />
        Nouvelle version
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-600 border-t-transparent"></div>
    </div>

    <!-- Versions Timeline -->
    <div v-else-if="versions.length > 0" class="relative">
      <!-- Timeline Line -->
      <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-800"></div>

      <!-- Version Items -->
      <div class="space-y-6">
        <div
          v-for="(version, index) in versions"
          :key="version.id"
          class="relative pl-20"
        >
          <!-- Timeline Dot -->
          <div
            :class="[
              'absolute left-6 flex h-5 w-5 items-center justify-center rounded-full border-2',
              version.is_latest_version
                ? 'border-blue-600 bg-blue-600'
                : 'border-gray-300 bg-white dark:border-gray-700 dark:bg-gray-900'
            ]"
          >
            <div v-if="version.is_latest_version" class="h-2 w-2 rounded-full bg-white"></div>
          </div>

          <!-- Version Card -->
          <div class="rounded-3 border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-start justify-between">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3">
                  <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                    Version {{ version.version }}
                  </h4>
                  <span
                    v-if="version.is_latest_version"
                    class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400"
                  >
                    <CheckCircleIcon class="h-3.5 w-3.5" />
                    Version actuelle
                  </span>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-4 text-sm lg:grid-cols-4">
                  <div>
                    <p class="text-gray-500 dark:text-gray-400">Taille</p>
                    <p class="mt-1 font-medium text-gray-900 dark:text-white">
                      {{ version.formatted_size }}
                    </p>
                  </div>
                  <div>
                    <p class="text-gray-500 dark:text-gray-400">Uploadé par</p>
                    <div class="mt-1 flex items-center gap-2">
                      <div class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-medium text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                        {{ getInitials(version.user.nom) }}
                      </div>
                      <span class="font-medium text-gray-900 dark:text-white truncate">
                        {{ version.user.nom }}
                      </span>
                    </div>
                  </div>
                  <div>
                    <p class="text-gray-500 dark:text-gray-400">Date</p>
                    <p class="mt-1 font-medium text-gray-900 dark:text-white">
                      {{ formatDate(version.created_at) }}
                    </p>
                  </div>
                  <div>
                    <p class="text-gray-500 dark:text-gray-400">Téléchargements</p>
                    <p class="mt-1 font-medium text-gray-900 dark:text-white">
                      {{ version.download_count }}
                    </p>
                  </div>
                </div>

                <p v-if="version.description" class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                  {{ version.description }}
                </p>
              </div>

              <!-- Actions -->
              <div class="flex items-center gap-2 ml-4">
                <button
                  @click="viewVersion(version)"
                  class="rounded-3 p-2 text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                  title="Voir"
                >
                  <EyeIcon class="h-5 w-5" />
                </button>
                <button
                  @click="downloadVersion(version)"
                  class="rounded-3 p-2 text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                  title="Télécharger"
                >
                  <ArrowDownTrayIcon class="h-5 w-5" />
                </button>
                <button
                  v-if="!version.is_latest_version"
                  @click="restoreVersion(version)"
                  class="rounded-3 p-2 text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20"
                  title="Restaurer"
                >
                  <ArrowPathIcon class="h-5 w-5" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="rounded-3 border-2 border-dashed border-gray-300 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-900/50">
      <DocumentDuplicateIcon class="mx-auto h-12 w-12 text-gray-400" />
      <p class="mt-3 text-sm font-medium text-gray-900 dark:text-white">
        Aucune version
      </p>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Créez votre première version du document
      </p>
    </div>

    <!-- Upload New Version Modal -->
    <TransitionRoot as="template" :show="showUploadModal">
      <Dialog as="div" class="relative z-50" @close="showUploadModal = false">
        <TransitionChild
          as="template"
          enter="ease-out duration-300"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="ease-in duration-200"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-black/50 " />
        </TransitionChild>

        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4">
            <TransitionChild
              as="template"
              enter="ease-out duration-300"
              enter-from="opacity-0 scale-95"
              enter-to="opacity-100 scale-100"
              leave="ease-in duration-200"
              leave-from="opacity-100 scale-100"
              leave-to="opacity-0 scale-95"
            >
              <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-3 bg-white p-6 transition-all dark:bg-gray-900">
                <DialogTitle class="text-lg font-semibold text-gray-900 dark:text-white">
                  Créer une nouvelle version
                </DialogTitle>

                <form @submit.prevent="uploadNewVersion" class="mt-6 space-y-4">
                  <!-- File Upload -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Fichier
                    </label>
                    <div class="mt-1">
                      <input
                        ref="fileInput"
                        type="file"
                        @change="handleFileSelect"
                        required
                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-3 cursor-pointer bg-white focus:outline-none dark:text-gray-400 dark:bg-gray-800 dark:border-gray-700"
                      />
                    </div>
                    <p v-if="selectedFile" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                      {{ selectedFile.name }} ({{ formatFileSize(selectedFile.size) }})
                    </p>
                  </div>

                  <!-- Description -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Description des changements (optionnelle)
                    </label>
                    <textarea
                      v-model="versionDescription"
                      rows="3"
                      placeholder="Décrivez les modifications apportées..."
                      class="mt-1 w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                    ></textarea>
                  </div>

                  <!-- Actions -->
                  <div class="flex justify-end gap-3 pt-4">
                    <button
                      type="button"
                      @click="showUploadModal = false"
                      :disabled="uploading"
                      class="rounded-3 border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                      Annuler
                    </button>
                    <button
                      type="submit"
                      :disabled="uploading || !selectedFile"
                      class="inline-flex items-center gap-2 rounded-3 bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                      <div v-if="uploading" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                      {{ uploading ? 'Upload...' : 'Créer la version' }}
                    </button>
                  </div>
                </form>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionRoot,
  TransitionChild
} from '@headlessui/vue'
import {
  DocumentPlusIcon,
  DocumentDuplicateIcon,
  EyeIcon,
  ArrowDownTrayIcon,
  ArrowPathIcon,
  CheckCircleIcon
} from '@heroicons/vue/24/outline'
import { useDocuments } from '@/composables/useDocuments'
import api from '@/api/axios'

const props = defineProps({
  document: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['view', 'download', 'restored'])

const { createVersion } = useDocuments()

const versions = ref([])
const loading = ref(false)
const uploading = ref(false)
const showUploadModal = ref(false)
const selectedFile = ref(null)
const versionDescription = ref('')
const fileInput = ref(null)

const loadVersions = async () => {
  loading.value = true
  try {
    const response = await api.get(`/documents/${props.document.id}/versions`)
    // Include current document as latest version
    versions.value = [props.document, ...response.data.data]
  } catch (error) {
    console.error('Error loading versions:', error)
  } finally {
    loading.value = false
  }
}

const handleFileSelect = (event) => {
  selectedFile.value = event.target.files[0]
}

const uploadNewVersion = async () => {
  if (!selectedFile.value) return

  uploading.value = true
  try {
    await createVersion(props.document.id, selectedFile.value)
    showUploadModal.value = false
    selectedFile.value = null
    versionDescription.value = ''
    await loadVersions()
    emit('restored')
  } catch (error) {
    console.error('Error creating version:', error)
  } finally {
    uploading.value = false
  }
}

const viewVersion = (version) => {
  emit('view', version)
}

const downloadVersion = (version) => {
  emit('download', version)
}

const restoreVersion = async (version) => {
  if (!confirm(`Restaurer la version ${version.version} comme version actuelle ?`)) {
    return
  }

  try {
    // Create new version from old version
    const response = await api.get(`/documents/${version.id}/download`, {
      responseType: 'blob'
    })

    const file = new File([response.data], version.nom, { type: version.mime_type })
    await createVersion(props.document.id, file)
    await loadVersions()
    emit('restored')
  } catch (error) {
    console.error('Error restoring version:', error)
  }
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
  const now = new Date()
  const diffInMs = now - date
  const diffInHours = Math.floor(diffInMs / (1000 * 60 * 60))

  if (diffInHours < 1) {
    return 'Il y a quelques minutes'
  } else if (diffInHours < 24) {
    return `Il y a ${diffInHours}h`
  } else if (diffInHours < 48) {
    return 'Hier'
  } else {
    return date.toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    })
  }
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
}

onMounted(() => {
  loadVersions()
})
</script>