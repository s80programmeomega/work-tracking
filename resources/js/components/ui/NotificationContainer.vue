<!-- resources\js\components\ui\NotificationContainer.vue -->
<template>
  <TransitionGroup
    name="notification"
    tag="div"
    class="fixed top-4 right-4 z-9999 space-y-3 w-96 max-w-full pointer-events-auto"
  >
    <div
      v-for="notification in notifications"
      :key="notification.id"
      :class="[
        'relative flex items-start p-4 rounded-3 border transform transition-all duration-300',
        getNotificationClass(notification.type)
      ]"
    >
      <!-- Icon -->
      <div class="shrink-0">
        <component
          :is="getNotificationIcon(notification.type)"
          class="w-5 h-5"
        />
      </div>
      
      <!-- Content -->
      <div class="ml-3 flex-1">
        <p class="text-sm font-medium">
          {{ notification.message }}
        </p>
      </div>
      
      <!-- Close button -->
      <button
        @click="removeNotification(notification.id)"
        class="ml-4 shrink-0 text-gray-400 hover:text-gray-500 dark:text-gray-400"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
      
      <!-- Progress bar -->
      <div
        v-if="notification.duration"
        class="absolute bottom-0 left-0 right-0 h-1 bg-current opacity-25 rounded-b-lg"
        :style="{ animation: `progress ${notification.duration}ms linear forwards` }"
      ></div>
    </div>
  </TransitionGroup>
</template>

<script setup>
import {
  CheckCircleIcon,
  ExclamationTriangleIcon,
  XCircleIcon,
  InformationCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  notifications: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['remove'])

const getNotificationClass = (type) => {
  const classes = {
    success: 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 border-green-200 dark:border-green-700',
    error: 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 border-red-200 dark:border-red-700',
    warning: 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-300 border-yellow-200 dark:border-yellow-700',
    info: 'bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 border-blue-200 dark:border-blue-700'
  }
  return classes[type] || classes.info
}

const getNotificationIcon = (type) => {
  const icons = {
    success: CheckCircleIcon,
    error: XCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon
  }
  return icons[type] || InformationCircleIcon
}

const removeNotification = (id) => {
  emit('remove', id)
}
</script>

<style>
@keyframes progress {
  from {
    width: 100%;
  }
  to {
    width: 0%;
  }
}

.notification-enter-active,
.notification-leave-active {
  transition: all 0.3s ease;
}

.notification-enter-from,
.notification-leave-to {
  opacity: 0;
  transform: translateX(30px);
}
</style>