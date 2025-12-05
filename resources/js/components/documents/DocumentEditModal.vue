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
            <DialogPanel class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all dark:bg-gray-900 sm:my-8 sm:w-full sm:max-w-lg">
              <!-- Header -->
              <div class="border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                  <DialogTitle class="text-lg font-semibold text-gray-900 dark:text-white">
                    Modifier le document
                  </DialogTitle>
                  <button
                    @click="$emit('close')"
                    class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-500 dark:hover:bg-gray-800"
                  >
                    <XMarkIcon class="h-6 w-6" />
                  </button>
                </div>
              </div>

              <!-- Body -->
              <form @submit.prevent="handleSubmit" class="bg-white px-6 py-5 dark:bg-gray-900">
                <div class="space-y-5">
                  <!-- Document Preview -->
                  <div class="flex items-center gap-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-800/50">
                    <div class="flex-shrink-0">
                      <component
                        :is="getFileIcon(document)"
                        class="h-12 w-12 text-gray-400"
                      />
                    </div>
                    <div class="min-w-0 flex-1">
                      <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                        {{ document.nom }}
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ document.formatted_size }} • v{{ document.version }}
                      </p>
                    </div>
                  </div>

                  <!-- Name -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Nom du document
                    </label>
                    <input
                      v-model="form.nom"
                      type="text"
                      required
                      class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                      placeholder="Mon document.pdf"
                    />
                  </div>

                  <!-- Description -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Description
                    </label>
                    <textarea
                      v-model="form.description"
                      rows="3"
                      class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                      placeholder="Ajoutez une description..."
                    ></textarea>
                  </div>

                  <!-- Visibility -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Visibilité
                    </label>
                    <select
                      v-model="form.visibility"
                      class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                      <option value="private">Privé (propriétaire uniquement)</option>
                      <option value="team">Équipe (membres de l'entité)</option>
                      <option value="public">Public (tous les utilisateurs)</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                      Définissez qui peut voir ce document
                    </p>
                  </div>
                </div>

                <!-- Error Message -->
                <div v-if="error" class="mt-5 rounded-lg bg-red-50 p-4 dark:bg-red-900/20">
                  <div class="flex items-start gap-3">
                    <ExclamationTriangleIcon class="h-5 w-5 flex-shrink-0 text-red-600 dark:text-red-400" />
                    <p class="text-sm text-red-800 dark:text-red-300">{{ error }}</p>
                  </div>
                </div>
              </form>

              <!-- Footer -->
              <div class="border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-800 dark:bg-gray-800/50">
                <div class="flex justify-end gap-3">
                  <button
                    @click="$emit('close')"
                    :disabled="loading"
                    type="button"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                  >
                    Annuler
                  </button>
                  <button
                    @click="handleSubmit"
                    :disabled="loading"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <div v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                    {{ loading ? 'Enregistrement...' : 'Enregistrer' }}
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
import { ref, reactive } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import {
  XMarkIcon,
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
  document: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'updated'])

const { updateDocument, loading, error } = useDocuments()

const form = reactive({
  nom: props.document.nom,
  description: props.document.description || '',
  visibility: props.document.visibility
})

const handleSubmit = async () => {
  try {
    await updateDocument(props.document.id, form)
    emit('updated')
  } catch (err) {
    console.error('Update error:', err)
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
</script>