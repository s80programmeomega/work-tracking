<template>
  <div
    class="notification-item flex gap-3 rounded-lg border-b border-gray-100 p-3 px-4.5 py-3 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-white/5 cursor-pointer relative"
    :class="{ 'bg-blue-50 dark:bg-blue-900/10': !notification.read_at }"
    @click="$emit('click', notification)"
  >
    <!-- Icon -->
    <div
      class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
      :class="`bg-${iconColor}-100 dark:bg-${iconColor}-900/20`"
    >
      <i :class="['fas', icon, `text-${iconColor}-600 dark:text-${iconColor}-400`]"></i>
    </div>

    <!-- Content -->
    <div class="flex-1 min-w-0">
      <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90 mb-1">
        {{ notification.title }}
      </h4>
      <p class="text-sm text-gray-600 dark:text-gray-400 mb-2 line-clamp-2">
        {{ notification.message }}
      </p>
      <span class="text-xs text-gray-500 dark:text-gray-500">
        {{ notification.time_ago }}
      </span>
    </div>

    <!-- Actions -->
    <div class="flex-shrink-0 flex items-start gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
      <button
        v-if="!notification.read_at"
        type="button"
        class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400"
        @click.stop="$emit('mark-read', notification.id)"
        title="Marquer comme lu"
      >
        <i class="fas fa-check text-xs"></i>
      </button>
      <button
        type="button"
        class="p-1.5 rounded hover:bg-red-100 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400"
        @click.stop="confirmDelete"
        title="Supprimer"
      >
        <i class="fas fa-trash-alt text-xs"></i>
      </button>
    </div>

    <!-- Unread indicator -->
    <div
      v-if="!notification.read_at"
      class="absolute left-2 top-1/2 -translate-y-1/2 w-2 h-2 bg-blue-500 rounded-full"
    ></div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useNotifications } from '@/composables/useNotifications';

const props = defineProps({
  notification: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['click', 'mark-read', 'delete']);

const { getNotificationIcon, getNotificationColor } = useNotifications();

const icon = computed(() => getNotificationIcon(props.notification.type));
const iconColor = computed(() => getNotificationColor(props.notification.type));

const confirmDelete = () => {
  if (confirm('Supprimer cette notification ?')) {
    emit('delete', props.notification.id);
  }
};
</script>

<style scoped>
.notification-item:hover .opacity-0 {
  opacity: 1;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
