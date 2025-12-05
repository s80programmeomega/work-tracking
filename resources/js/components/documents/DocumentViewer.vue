<template>
  <TransitionRoot as="template" :show="show" @after-leave="$emit('close')">
    <Dialog as="div" class="relative z-50">
      <!-- Backdrop -->
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-50 overflow-y-auto">
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
            <DialogPanel class="relative w-full max-w-6xl transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all dark:bg-gray-900">
              <!-- Header -->
              <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                <div class="min-w-0 flex-1">
                  <DialogTitle class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                    {{ document.nom }}
                  </DialogTitle>
                  <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ document.formatted_size }} • Uploadé par {{ document.user.nom }}
                  </p>
                </div>

                <div class="ml-4 flex items-center gap-2">
                  <!-- Download Button -->
                  <button
                    @click="$emit('download', document)"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                  >
                    <ArrowDownTrayIcon class="h-5 w-5" />
                    Télécharger
                  </button>

                  <!-- Close Button -->
                  <button
                    @click="$emit('close')"
                    class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 dark:hover:bg-gray-800"
                  >
                    <XMarkIcon class="h-6 w-6" />
                  </button>
                </div>
              </div>

              <!-- Content -->
              <div class="p-6">
                <!-- Image Preview -->
                <div v-if="document.is_image" class="flex justify-center">
                  <img
                    :src="document.url"
                    :alt="document.nom"
                    class="max-h-[70vh] rounded-lg shadow-lg"
                  />
                </div>

                <!-- PDF Preview -->
                <div v-else-if="document.is_pdf" class="h-[70vh]">
                  <iframe
                    :src="document.url"
                    class="h-full w-full rounded-lg border border-gray-200 dark:border-gray-800"
                  />
                </div>

                <!-- Video Preview -->
                <div v-else-if="document.is_video" class="flex justify-center">
                  <video
                    controls
                    class="max-h-[70vh] rounded-lg shadow-lg"
                  >
                    <source :src="document.url" :type="document.mime_type" />
                    Votre navigateur ne supporte pas la lecture de vidéos.
                  </video>
                </div>

                <!-- Audio Preview -->
                <div v-else-if="document.is_audio" class="flex justify-center">
                  <audio controls class="w-full max-w-2xl">
                    <source :src="document.url" :type="document.mime_type" />
                    Votre navigateur ne supporte pas la lecture audio.
                  </audio>
                </div>

                <!-- Other file types -->
                <div v-else class="flex flex-col items-center justify-center py-12">
                  <DocumentIcon class="h-24 w-24 text-gray-400" />
                  <p class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                    Aperçu non disponible
                  </p>
                  <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Téléchargez le fichier pour le consulter
                  </p>
                  <button
                    @click="$emit('download', document)"
                    class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-medium text-white hover:bg-blue-700"
                  >
                    <ArrowDownTrayIcon class="h-5 w-5" />
                    Télécharger le fichier
                  </button>
                </div>
              </div>

              <!-- Footer with metadata -->
              <div class="border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-800 dark:bg-gray-900/50">
                <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                  <div>
                    <dt class="font-medium text-gray-500 dark:text-gray-400">Type</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white">{{ document.extension.toUpperCase() }}</dd>
                  </div>
                  <div>
                    <dt class="font-medium text-gray-500 dark:text-gray-400">Version</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white">v{{ document.version }}</dd>
                  </div>
                  <div>
                    <dt class="font-medium text-gray-500 dark:text-gray-400">Téléchargements</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white">{{ document.download_count }}</dd>
                  </div>
                  <div>
                    <dt class="font-medium text-gray-500 dark:text-gray-400">Créé le</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white">{{ formatDate(document.created_at) }}</dd>
                  </div>
                </dl>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, watch } from 'vue'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot
} from '@headlessui/vue'
import {
  XMarkIcon,
  ArrowDownTrayIcon,
  DocumentIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  document: {
    type: Object,
    required: true
  }
})

defineEmits(['close', 'download'])

const show = ref(true)

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

watch(() => props.document, () => {
  show.value = true
})
</script>