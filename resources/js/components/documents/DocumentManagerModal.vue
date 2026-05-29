<template>
  <TransitionRoot as="template" :show="true">
    <Dialog as="div" class="relative z-50" @close="$emit('close')">
      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:scale-95"
          >
            <DialogPanel class="relative w-full max-w-7xl transform overflow-hidden rounded-3 bg-white transition-all dark:bg-gray-900">
              <!-- Header -->
              <div class="border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <component :is="getEntityIcon()" class="h-6 w-6 text-gray-400" />
                    <div>
                      <DialogTitle class="text-lg font-semibold text-gray-900 dark:text-white">
                        Documents - {{ entityLabel }}
                      </DialogTitle>
                      <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ getEntityTypeLabel() }}
                      </p>
                    </div>
                  </div>
                  <button
                    @click="$emit('close')"
                    class="rounded-3 p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 dark:text-gray-400 dark:hover:bg-gray-800"
                  >
                    <XMarkIcon class="h-6 w-6" />
                  </button>
                </div>
              </div>

              <!-- Body -->
              <div class="p-6">
                <document-manager
                  :documentable-type="entityType"
                  :documentable-id="entityId"
                  :entity-label="entityLabel"
                  :can-upload="canUpload"
                  :show-hierarchy="true"
                />
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { computed } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import {
  XMarkIcon,
  FolderIcon,
  BriefcaseIcon,
  RectangleStackIcon,
  CheckCircleIcon,
  DocumentTextIcon
} from '@heroicons/vue/24/outline'
import DocumentManager from './DocumentManager.vue'
import { useAuthStore } from '@/stores/authStore'

const props = defineProps({
  entityType: {
    type: String,
    required: true
  },
  entityId: {
    type: [String, Number],
    required: true
  },
  entityLabel: {
    type: String,
    required: true
  }
})

defineEmits(['close'])

const authStore = useAuthStore()

const canUpload = computed(() => {
  // TODO: Implémenter la vérification des permissions
  return authStore.user?.is_super_admin || true
})

const getEntityIcon = () => {
  const icons = {
    'App\\Models\\Workspace': FolderIcon,
    'App\\Models\\Projet': BriefcaseIcon,
    'App\\Models\\Activite': RectangleStackIcon,
    'App\\Models\\Tache': CheckCircleIcon,
    'App\\Models\\TacheResultat': DocumentTextIcon
  }
  return icons[props.entityType] || FolderIcon
}

const getEntityTypeLabel = () => {
  const labels = {
    'App\\Models\\Workspace': 'Workspace',
    'App\\Models\\Projet': 'Projet',
    'App\\Models\\Activite': 'Activité',
    'App\\Models\\Tache': 'Tâche',
    'App\\Models\\TacheResultat': 'Résultat de Tâche'
  }
  return labels[props.entityType] || 'Entité'
}
</script>