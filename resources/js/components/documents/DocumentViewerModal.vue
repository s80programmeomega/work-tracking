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
        <div class="fixed inset-0 bg-gray-900 bg-opacity-90 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
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
            <DialogPanel class="relative w-full max-w-6xl transform overflow-hidden rounded-3 bg-white transition-all dark:bg-gray-900">
              <!-- Header -->
              <div class="border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                  <div class="min-w-0 flex-1">
                    <DialogTitle class="truncate text-lg font-semibold text-gray-900 dark:text-white">
                      {{ document.nom }}
                    </DialogTitle>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                      {{ document.formatted_size }} • Uploadé le {{ formatDate(document.created_at) }}
                    </p>
                  </div>
                  <div class="flex items-center gap-2">
                    <button
                      @click="$emit('download')"
                      class="rounded-3 p-2 text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                      title="Télécharger"
                    >
                      <ArrowDownTrayIcon class="h-6 w-6" />
                    </button>
                    <button
                      @click="$emit('close')"
                      class="rounded-3 p-2 text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                    >
                      <XMarkIcon class="h-6 w-6" />
                    </button>
                  </div>
                </div>
              </div>

              <!-- Preview Area -->
              <div class="bg-gray-50 dark:bg-gray-950">
                <!-- Image Preview -->
                <div v-if="document.is_image" class="flex items-center justify-center p-8">
                  <img
                    :src="document.url"
                    :alt="document.nom"
                    class="max-h-[70vh] w-auto rounded-3 "
                  />
                </div>

                <!-- PDF Preview -->
                <div v-else-if="document.is_pdf" class="h-[70vh]">
                  <iframe
                    :src="document.url"
                    class="h-full w-full"
                    frameborder="0"
                  ></iframe>
                </div>

                <!-- Video Preview -->
                <div v-else-if="document.is_video" class="flex items-center justify-center p-8">
                  <video
                    :src="document.url"
                    controls
                    class="max-h-[70vh] w-auto rounded-3 "
                  ></video>
                </div>

                <!-- Audio Preview -->
                <div v-else-if="document.is_audio" class="flex items-center justify-center p-16">
                  <div class="w-full max-w-2xl space-y-6 text-center">
                    <MusicalNoteIcon class="mx-auto h-24 w-24 text-gray-400" />
                    <h3 class="text-xl font-medium text-gray-900 dark:text-white">
                      {{ document.nom }}
                    </h3>
                    <audio :src="document.url" controls class="w-full"></audio>
                  </div>
                </div>

                <!-- No Preview Available -->
                <div v-else class="flex items-center justify-center p-16">
                  <div class="text-center">
                    <component :is="getFileIcon(document)" class="mx-auto h-24 w-24 text-gray-400" />
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                      Aperçu non disponible
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                      Ce type de fichier ne peut pas être prévisualisé
                    </p>
                    <button
                      @click="$emit('download')"
                      class="mt-6 inline-flex items-center gap-2 rounded-3 bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                    >
                      <ArrowDownTrayIcon class="h-5 w-5" />
                      Télécharger le fichier
                    </button>
                  </div>
                </div>
              </div>

              <!-- Details -->
              <div class="border-t border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                  <!-- Left Column -->
                  <div class="space-y-4">
                    <div>
                      <h4 class="text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                        Informations
                      </h4>
                      <dl class="mt-2 space-y-2">
                        <div class="flex justify-between text-sm">
                          <dt class="text-gray-500 dark:text-gray-400">Type</dt>
                          <dd class="font-medium text-gray-900 dark:text-white">{{ document.extension.toUpperCase() }}</dd>
                        </div>
                        <div class="flex justify-between text-sm">
                          <dt class="text-gray-500 dark:text-gray-400">Taille</dt>
                          <dd class="font-medium text-gray-900 dark:text-white">{{ document.formatted_size }}</dd>
                        </div>
                        <div class="flex justify-between text-sm">
                          <dt class="text-gray-500 dark:text-gray-400">Version</dt>
                          <dd class="font-medium text-gray-900 dark:text-white">v{{ document.version }}</dd>
                        </div>
                        <div class="flex justify-between text-sm">
                          <dt class="text-gray-500 dark:text-gray-400">Téléchargements</dt>
                          <dd class="font-medium text-gray-900 dark:text-white">{{ document.download_count }}</dd>
                        </div>
                      </dl>
                    </div>

                    <div v-if="document.description">
                      <h4 class="text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                        Description
                      </h4>
                      <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                        {{ document.description }}
                      </p>
                    </div>
                  </div>

                  <!-- Right Column -->
                  <div class="space-y-4">
                    <div>
                      <h4 class="text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                        Uploadé par
                      </h4>
                      <div class="mt-2 flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-sm font-medium text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                          {{ getInitials(document.user.nom) }}
                        </div>
                        <div>
                          <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ document.user.nom }}
                          </p>
                          <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ formatDate(document.created_at) }}
                          </p>
                        </div>
                      </div>
                    </div>

                    <div>
                      <h4 class="text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                        Visibilité
                      </h4>
                      <div class="mt-2">
                        <span
                          :class="[
                            'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium',
                            document.visibility === 'public'
                              ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                              : document.visibility === 'team'
                              ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400'
                              : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
                          ]"
                        >
                          <component
                            :is="getVisibilityIcon(document.visibility)"
                            class="h-4 w-4"
                          />
                          {{ getVisibilityLabel(document.visibility) }}
                        </span>
                      </div>
                    </div>
                  </div>
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
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import {
  XMarkIcon,
  ArrowDownTrayIcon,
  DocumentIcon,
  PhotoIcon,
  FilmIcon,
  MusicalNoteIcon,
  ArchiveBoxIcon,
  DocumentTextIcon,
  GlobeAltIcon,
  UserGroupIcon,
  LockClosedIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  document: {
    type: Object,
    required: true
  }
})

defineEmits(['close', 'download'])

const getFileIcon = (document) => {
  const mimeType = document.mime_type
  if (mimeType.startsWith('image/')) return PhotoIcon
  if (mimeType === 'application/pdf') return DocumentTextIcon
  if (mimeType.startsWith('video/')) return FilmIcon
  if (mimeType.startsWith('audio/')) return MusicalNoteIcon
  if (mimeType.includes('zip') || mimeType.includes('compressed')) return ArchiveBoxIcon
  return DocumentIcon
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
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getVisibilityIcon = (visibility) => {
  switch (visibility) {
    case 'public':
      return GlobeAltIcon
    case 'team':
      return UserGroupIcon
    default:
      return LockClosedIcon
  }
}

const getVisibilityLabel = (visibility) => {
  switch (visibility) {
    case 'public':
      return 'Public'
    case 'team':
      return 'Équipe'
    default:
      return 'Privé'
  }
}
</script>