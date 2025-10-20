<template>
  <teleport to="body">
    <transition name="modal-fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="close"
      >
        <div
          class="relative w-full max-w-2xl bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-h-[90vh] overflow-hidden"
          @click.stop
        >
          <!-- Header -->
          <div class="flex items-start justify-between p-6 border-b border-gray-200 dark:border-gray-800">
            <div class="flex items-start gap-4 flex-1">
              <!-- Icon -->
              <div
                class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center"
                :class="`bg-${iconColor}-100 dark:bg-${iconColor}-900/20`"
              >
                <i :class="['fas', icon, `text-${iconColor}-600 dark:text-${iconColor}-400`, 'text-xl']"></i>
              </div>

              <!-- Title and time -->
              <div class="flex-1 min-w-0">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-1">
                  {{ notification?.title }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ notification?.time_ago }}
                </p>
              </div>
            </div>

            <!-- Close button -->
            <button
              @click="close"
              class="ml-4 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500 dark:text-gray-400"
            >
              <i class="fas fa-times text-lg"></i>
            </button>
          </div>

          <!-- Content -->
          <div class="p-6 overflow-y-auto max-h-[calc(90vh-200px)]">
            <!-- Message -->
            <div class="mb-6">
              <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Message</h4>
              <p class="text-base text-gray-900 dark:text-white leading-relaxed">
                {{ notification?.message }}
              </p>
            </div>

            <!-- Additional data -->
            <div v-if="hasAdditionalData" class="space-y-4">
              <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Détails</h4>

              <!-- Task info -->
              <div v-if="notification?.data?.task_title" class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <i class="fas fa-tasks text-blue-600 dark:text-blue-400 mt-0.5"></i>
                <div>
                  <p class="text-xs text-gray-600 dark:text-gray-400">Tâche</p>
                  <p class="text-sm font-medium text-gray-900 dark:text-white">{{ notification.data.task_title }}</p>
                </div>
              </div>

              <!-- Project info -->
              <div v-if="notification?.data?.project_title" class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <i class="fas fa-project-diagram text-purple-600 dark:text-purple-400 mt-0.5"></i>
                <div>
                  <p class="text-xs text-gray-600 dark:text-gray-400">Projet</p>
                  <p class="text-sm font-medium text-gray-900 dark:text-white">{{ notification.data.project_title }}</p>
                </div>
              </div>

              <!-- Document info -->
              <div v-if="notification?.data?.document_name" class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <i class="fas fa-file text-green-600 dark:text-green-400 mt-0.5"></i>
                <div>
                  <p class="text-xs text-gray-600 dark:text-gray-400">Document</p>
                  <p class="text-sm font-medium text-gray-900 dark:text-white">{{ notification.data.document_name }}</p>
                </div>
              </div>

              <!-- Hours until due -->
              <div v-if="notification?.data?.hours_until_due" class="flex items-start gap-3 p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                <i class="fas fa-clock text-orange-600 dark:text-orange-400 mt-0.5"></i>
                <div>
                  <p class="text-xs text-orange-600 dark:text-orange-400">Échéance</p>
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    Dans {{ notification.data.hours_until_due }} heure(s)
                  </p>
                </div>
              </div>
            </div>

            <!-- Status badge -->
            <div class="mt-6 flex items-center justify-between">
              <span
                v-if="!notification?.read_at"
                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400"
              >
                <i class="fas fa-circle text-[6px] mr-2"></i>
                Non lu
              </span>
              <span
                v-else
                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400"
              >
                <i class="fas fa-check text-[10px] mr-2"></i>
                Lu
              </span>

              <span class="text-xs text-gray-500 dark:text-gray-400">
                Type: {{ notification?.type }}
              </span>
            </div>
          </div>

          <!-- Footer with actions -->
          <div class="flex items-center justify-between gap-3 p-6 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
            <div class="flex items-center gap-2">
              <button
                v-if="!notification?.read_at"
                @click="handleMarkAsRead"
                class="px-4 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700"
              >
                <i class="fas fa-check mr-2"></i>
                Marquer comme lu
              </button>

              <button
                @click="handleDelete"
                class="px-4 py-2 rounded-lg text-sm font-medium bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30"
              >
                <i class="fas fa-trash-alt mr-2"></i>
                Supprimer
              </button>
            </div>

            <button
              v-if="notification?.data?.action_url"
              @click="handleGoToAction"
              class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-900 text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100"
            >
              Voir plus
              <i class="fas fa-arrow-right ml-2"></i>
            </button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useNotifications } from '@/composables/useNotifications';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true,
  },
  notification: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close', 'mark-read', 'delete']);

const router = useRouter();
const { getNotificationIcon, getNotificationColor } = useNotifications();

const icon = computed(() => getNotificationIcon(props.notification?.type));
const iconColor = computed(() => getNotificationColor(props.notification?.type));

const hasAdditionalData = computed(() => {
  if (!props.notification?.data) return false;
  return (
    props.notification.data.task_title ||
    props.notification.data.project_title ||
    props.notification.data.document_name ||
    props.notification.data.hours_until_due
  );
});

const close = () => {
  emit('close');
};

const handleMarkAsRead = () => {
  emit('mark-read', props.notification.id);
};

const handleDelete = () => {
  if (confirm('Supprimer cette notification ?')) {
    emit('delete', props.notification.id);
    close();
  }
};

const handleGoToAction = () => {
  if (props.notification?.data?.action_url) {
    // Mark as read before navigating
    if (!props.notification.read_at) {
      emit('mark-read', props.notification.id);
    }
    router.push(props.notification.data.action_url);
    close();
  }
};

// Close on escape key
const handleKeydown = (e) => {
  if (e.key === 'Escape') {
    close();
  }
};

// Add/remove event listener
if (typeof window !== 'undefined') {
  window.addEventListener('keydown', handleKeydown);
}
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-fade-enter-active > div,
.modal-fade-leave-active > div {
  transition: transform 0.3s ease;
}

.modal-fade-enter-from > div,
.modal-fade-leave-to > div {
  transform: scale(0.95);
}
</style>
