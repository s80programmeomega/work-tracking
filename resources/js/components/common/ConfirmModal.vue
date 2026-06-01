<!-- resources/js/components/common/ConfirmModal.vue -->
<template>
  <Teleport to="body">
    <div class="fixed inset-0 bg-black/30 flex items-center justify-center z-50 p-4">
      <div ref="dialogRef" :style="dragStyle"
        class="bg-white dark:bg-gray-800 rounded-3 max-w-md w-full"
        @click.stop
      >
        <!-- Header -->
        <div ref="handleRef" class="p-6 border-b border-gray-200 dark:border-gray-700 cursor-move select-none">
          <div class="flex items-center gap-3">
            <div
              :class="[
                'p-3 rounded-3',
                type === 'danger' ? 'bg-red-100 dark:bg-red-900/30' : 'bg-yellow-100 dark:bg-yellow-900/30'
              ]"
            >
              <AlertCircleIcon
                :class="[
                  'w-6 h-6',
                  type === 'danger' ? 'text-red-600 dark:text-red-400' : 'text-yellow-600 dark:text-yellow-400'
                ]"
              />
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ title }}
            </h3>
          </div>
        </div>

        <!-- Body -->
        <div class="p-6">
          <p class="text-gray-600 dark:text-gray-400">
            {{ message }}
          </p>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
          <button
            @click="$emit('cancel')"
            class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
          >
            {{ cancelText }}
          </button>
          <button
            @click="$emit('confirm')"
            :class="[
              'px-4 py-2 rounded-3 text-white transition-colors',
              confirmClass || 'bg-brand-600 hover:bg-brand-700'
            ]"
          >
            {{ confirmText }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { onMounted } from 'vue'
import { AlertCircleIcon } from '@/icons'
import { useDraggable } from '@/composables/useDraggable'

const { dialogRef, handleRef, dragStyle, attachHandle } = useDraggable()
onMounted(attachHandle)

defineProps({
  title: {
    type: String,
    required: true
  },
  message: {
    type: String,
    required: true
  },
  confirmText: {
    type: String,
    default: 'Confirmer'
  },
  cancelText: {
    type: String,
    default: 'Annuler'
  },
  confirmClass: {
    type: String,
    default: ''
  },
  type: {
    type: String,
    default: 'danger',
    validator: (value) => ['danger', 'warning'].includes(value)
  }
})

defineEmits(['confirm', 'cancel'])
</script>