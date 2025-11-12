<!-- resources\js\components\layout\header\NotificationDetailModal.vue -->
<template>
  <teleport to="body">
    <transition name="modal-fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        @click.self="close"
      >
        <div
          class="relative w-full max-w-3xl bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-h-[90vh] overflow-hidden border border-gray-200 dark:border-gray-700"
          @click.stop
        >
          <!-- Header avec design moderne -->
          <div class="relative bg-gradient-to-r from-brand-600 via-brand-700 to-purple-700 px-8 py-6 overflow-hidden">
            <!-- Background pattern -->
            <div class="absolute inset-0 opacity-10">
              <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 24px 24px;"></div>
            </div>

            <div class="relative z-10 flex items-start justify-between">
              <div class="flex items-start gap-4 flex-1">
                <!-- Icon -->
                <div
                  class="flex-shrink-0 w-14 h-14 rounded-2xl flex items-center justify-center shadow-lg backdrop-blur-sm border-2 border-white/30"
                  :class="`bg-white/20`"
                >
                  <i :class="['fas', icon, 'text-white', 'text-2xl']"></i>
                </div>

                <!-- Title and time -->
                <div class="flex-1 min-w-0">
                  <h3 class="text-2xl font-bold text-white mb-1 drop-shadow-lg">
                    {{ notification?.title }}
                  </h3>
                  <p class="text-sm text-white/80 flex items-center gap-2">
                    <i class="fas fa-clock text-xs"></i>
                    {{ notification?.time_ago }}
                  </p>
                </div>
              </div>

              <!-- Close button -->
              <button
                @click="close"
                class="ml-4 p-2.5 rounded-xl hover:bg-white/20 backdrop-blur-sm text-white transition-all duration-200 border border-white/30"
              >
                <i class="fas fa-times text-lg"></i>
              </button>
            </div>
          </div>
 
          <!-- Content -->
          <div class="p-8 overflow-y-auto max-h-[calc(90vh-250px)] custom-scrollbar">
            
            <!-- ========== INVITATIONS PROJET ========== -->
            <div v-if="notification?.type === 'projet_invitation'" class="space-y-6">
              <!-- Carte du projet -->
              <div class="p-6 bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-950 dark:to-indigo-950 rounded-2xl border-2 border-purple-200 dark:border-purple-800 shadow-sm">
                <div class="flex items-center gap-4 mb-4">
                  <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-purple-600 to-indigo-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-project-diagram text-white text-2xl"></i>
                  </div>
                  <div>
                    <p class="text-xs font-semibold text-purple-700 dark:text-purple-400 uppercase tracking-wide">Projet</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ notification.data.projet_nom }}</p>
                  </div>
                </div>

                <div class="space-y-3 text-sm">
                  <div class="flex items-start gap-3">
                    <i class="fas fa-user text-purple-600 dark:text-purple-400 mt-0.5"></i>
                    <div>
                      <p class="text-gray-600 dark:text-gray-400">Invité par</p>
                      <p class="font-semibold text-gray-900 dark:text-white">{{ notification.data.inviter_nom }}</p>
                    </div>
                  </div>

                  <div class="flex items-start gap-3">
                    <i class="fas fa-user-tag text-purple-600 dark:text-purple-400 mt-0.5"></i>
                    <div>
                      <p class="text-gray-600 dark:text-gray-400">Rôle proposé</p>
                      <p class="font-semibold text-gray-900 dark:text-white">{{ getRoleLabel(notification.data.role) }}</p>
                    </div>
                  </div>

                  <div v-if="notification.data.message" class="mt-4 p-4 bg-white dark:bg-gray-800 rounded-xl border-l-4 border-purple-500">
                    <p class="text-xs font-semibold text-purple-700 dark:text-purple-400 mb-2">Message personnel</p>
                    <p class="text-gray-700 dark:text-gray-300 italic">"{{ notification.data.message }}"</p>
                  </div>
                </div>
              </div>

              <!-- Expiration -->
              <div v-if="notification.data.expires_at" class="flex items-center justify-center gap-3 text-gray-600 dark:text-gray-400 bg-orange-50 dark:bg-orange-950/30 rounded-xl p-4 border border-orange-200 dark:border-orange-800">
                <i class="fas fa-clock text-orange-500"></i>
                <span class="font-medium">Expire {{ formatExpirationDate(notification.data.expires_at) }}</span>
              </div>

              <!-- Statut -->
              <div class="flex items-center justify-center">
                <span v-if="notification.data.is_pending" class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400">
                  <i class="fas fa-hourglass-half mr-2"></i>
                  En attente de réponse
                </span>
              </div>
            </div>

            <!-- ========== INVITATIONS WORKSPACE ========== -->
            <div v-else-if="notification?.type === 'workspace_invitation'" class="space-y-6">
              <div class="p-6 bg-gradient-to-br from-brand-50 to-blue-50 dark:from-brand-950 dark:to-blue-950 rounded-2xl border-2 border-brand-200 dark:border-brand-800 shadow-sm">
                <div class="flex items-center gap-4 mb-4">
                  <div
                    v-if="notification.data.workspace_logo"
                    class="w-16 h-16 rounded-xl overflow-hidden border-2 border-brand-300 dark:border-brand-700 shadow-lg"
                  >
                    <img :src="notification.data.workspace_logo" :alt="notification.data.workspace_name" class="w-full h-full object-cover" />
                  </div>
                  <div
                    v-else
                    class="w-16 h-16 rounded-xl bg-gradient-to-br from-brand-600 to-blue-600 flex items-center justify-center border-2 border-brand-300 dark:border-brand-700 shadow-lg"
                  >
                    <span class="text-white font-bold text-xl">{{ getWorkspaceInitials(notification.data.workspace_name) }}</span>
                  </div>
                  <div>
                    <p class="text-xs font-semibold text-brand-700 dark:text-brand-400 uppercase tracking-wide">Workspace</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ notification.data.workspace_name }}</p>
                  </div>
                </div>

                <div class="space-y-3 text-sm">
                  <p class="text-gray-700 dark:text-gray-300">
                    <strong class="text-gray-900 dark:text-white">{{ notification.data.inviter_name }}</strong> vous invite à rejoindre ce workspace
                  </p>
                  <p class="text-gray-600 dark:text-gray-400">
                    Rôle proposé: <strong class="text-gray-900 dark:text-white">{{ getRoleLabel(notification.data.role) }}</strong>
                  </p>
                  <p v-if="notification.data.invitation_message" class="mt-4 p-4 bg-white dark:bg-gray-800 rounded-xl italic text-gray-600 dark:text-gray-400 border-l-4 border-brand-400">
                    "{{ notification.data.invitation_message }}"
                  </p>
                </div>
              </div>

              <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                <span v-if="notification.data.expires_at">Expire {{ formatExpirationDate(notification.data.expires_at) }}</span>
                <span v-if="notification.data.is_pending" class="text-orange-600 dark:text-orange-400 font-semibold">
                  <i class="fas fa-hourglass-half mr-1"></i>
                  En attente de réponse
                </span>
              </div>
            </div>

            <!-- ========== AUTRES NOTIFICATIONS ========== -->
            <div v-else>
              <!-- Message -->
              <div class="mb-6 p-6 bg-gray-50 dark:bg-gray-800 rounded-2xl">
                <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 uppercase tracking-wide">Message</h4>
                <p class="text-base text-gray-900 dark:text-white leading-relaxed">
                  {{ notification?.message }}
                </p>
              </div>

              <!-- Additional data -->
              <div v-if="hasAdditionalData" class="space-y-4">
                <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 uppercase tracking-wide">Détails</h4>

                <!-- Task info -->
                <div v-if="notification?.data?.task_title" class="flex items-start gap-4 p-4 bg-blue-50 dark:bg-blue-950/30 rounded-xl border border-blue-200 dark:border-blue-800">
                  <i class="fas fa-tasks text-blue-600 dark:text-blue-400 text-xl mt-0.5"></i>
                  <div>
                    <p class="text-xs text-blue-600 dark:text-blue-400 font-semibold mb-1">Tâche</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ notification.data.task_title }}</p>
                  </div>
                </div>

                <!-- Project info -->
                <div v-if="notification?.data?.project_title" class="flex items-start gap-4 p-4 bg-purple-50 dark:bg-purple-950/30 rounded-xl border border-purple-200 dark:border-purple-800">
                  <i class="fas fa-project-diagram text-purple-600 dark:text-purple-400 text-xl mt-0.5"></i>
                  <div>
                    <p class="text-xs text-purple-600 dark:text-purple-400 font-semibold mb-1">Projet</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ notification.data.project_title }}</p>
                  </div>
                </div>

                <!-- Document info -->
                <div v-if="notification?.data?.document_name" class="flex items-start gap-4 p-4 bg-green-50 dark:bg-green-950/30 rounded-xl border border-green-200 dark:border-green-800">
                  <i class="fas fa-file text-green-600 dark:text-green-400 text-xl mt-0.5"></i>
                  <div>
                    <p class="text-xs text-green-600 dark:text-green-400 font-semibold mb-1">Document</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ notification.data.document_name }}</p>
                  </div>
                </div>

                <!-- Hours until due -->
                <div v-if="notification?.data?.hours_until_due" class="flex items-start gap-4 p-4 bg-orange-50 dark:bg-orange-950/30 rounded-xl border border-orange-200 dark:border-orange-800">
                  <i class="fas fa-clock text-orange-600 dark:text-orange-400 text-xl mt-0.5"></i>
                  <div>
                    <p class="text-xs text-orange-600 dark:text-orange-400 font-semibold mb-1">Échéance</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">
                      Dans {{ notification.data.hours_until_due }} heure(s)
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Status badge -->
            <div class="mt-8 flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
              <span
                v-if="!notification?.read_at"
                class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400"
              >
                <i class="fas fa-circle text-[8px] mr-2 animate-pulse"></i>
                Non lu
              </span>
              <span
                v-else
                class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400"
              >
                <i class="fas fa-check text-sm mr-2"></i>
                Lu
              </span>

              <span class="text-xs text-gray-500 dark:text-gray-400 font-medium px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded-full">
                {{ notification?.type }}
              </span>
            </div>
          </div>

          <!-- Footer with actions -->
          <div class="flex items-center justify-between gap-4 p-6 border-t-2 border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
            <div class="flex items-center gap-3">
              <button
                v-if="!notification?.read_at"
                @click="handleMarkAsRead"
                class="px-5 py-2.5 rounded-xl text-sm font-bold bg-gradient-to-r from-green-600 to-green-700 text-white hover:from-green-700 hover:to-green-800 transition-all duration-200 shadow-lg hover:shadow-xl flex items-center gap-2"
              >
                <i class="fas fa-check"></i>
                Marquer comme lu
              </button>

              <button
                @click="handleDelete"
                class="px-5 py-2.5 rounded-xl text-sm font-bold bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30 transition-all duration-200 flex items-center gap-2"
              >
                <i class="fas fa-trash-alt"></i>
                Supprimer
              </button>
            </div>

            <button
              v-if="notification?.data?.action_url || notification?.data?.token"
              @click="handleGoToAction"
              class="px-6 py-2.5 rounded-xl text-sm font-bold bg-gradient-to-r from-brand-600 to-purple-600 text-white hover:from-brand-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl flex items-center gap-2"
            >
              {{ getActionButtonText() }}
              <i class="fas fa-arrow-right"></i>
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

const getWorkspaceInitials = (name) => {
  return name
    ?.split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2) || 'W';
};

const getRoleLabel = (role) => {
  const labels = {
    owner: 'Propriétaire',
    admin: 'Administrateur',
    manager: 'Gestionnaire',
    member: 'Membre',
    viewer: 'Observateur'
  };
  return labels[role] || role;
};

const formatExpirationDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getActionButtonText = () => {
  if (props.notification?.type === 'projet_invitation') {
    return 'Voir l\'invitation';
  }
  if (props.notification?.type === 'workspace_invitation') {
    return 'Voir l\'invitation';
  }
  return 'Voir plus';
};
 
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
  // Pour les invitations de projet
  if (props.notification?.type === 'projet_invitation' && props.notification.data?.token) {
    if (!props.notification.read_at) {
      emit('mark-read', props.notification.id);
    }
    router.push(`/invitations/projet/${props.notification.data.token}`);
    close();
    return;
  }

  // Pour les invitations workspace
  if (props.notification?.data?.action_url) {
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
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.modal-fade-enter-from > div,
.modal-fade-leave-to > div {
  transform: scale(0.9) translateY(20px);
  opacity: 0;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.05);
  border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.3);
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}
</style>
