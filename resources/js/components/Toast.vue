<!-- resources/js/components/Toast.vue -->
<template>
  <teleport to="body">
    <div class="fixed top-4 right-4 z-50 space-y-2 max-w-sm">
      <transition-group
        enter-active-class="transition ease-out duration-300"
        enter-from-class="transform opacity-0 translate-x-full"
        enter-to-class="transform opacity-100 translate-x-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="transform opacity-100 translate-x-0"
        leave-to-class="transform opacity-0 translate-x-full"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="[
            'flex items-center gap-3 p-4 rounded-lg shadow-lg border',
            getToastClasses(toast.type)
          ]"
          role="alert"
        >
          <component
            :is="getToastIcon(toast.type)"
            class="w-5 h-5 flex-shrink-0"
          />
          <p class="text-sm font-medium flex-1">
            {{ toast.message }}
          </p>
          <button
            @click="removeToast(toast.id)"
            class="flex-shrink-0 rounded hover:opacity-70 transition-opacity"
          >
            <XIcon class="w-4 h-4" />
          </button>
        </div>
      </transition-group>
    </div>
  </teleport>
</template>

<script setup>
import { useToast } from '@/composables/useToast'
import {
  CheckCircleIcon,
  XCircleIcon,
  AlertTriangleIcon,
  InfoIcon,
  XIcon
} from '@/icons'

const { toasts, removeToast } = useToast()

const getToastClasses = (type) => {
  const classes = {
    success: 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200',
    error: 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200',
    warning: 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200',
    info: 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200'
  }
  return classes[type] || classes.info
}

const getToastIcon = (type) => {
  const icons = {
    success: CheckCircleIcon,
    error: XCircleIcon,
    warning: AlertTriangleIcon,
    info: InfoIcon
  }
  return icons[type] || InfoIcon
}
</script>