<!-- resources\js\components\layout\header\NotificationItem.vue -->
<template>
  <div
    class="notification-item group relative flex gap-4 border-b border-gray-100 p-4 transition-all duration-200 hover:bg-gradient-to-r hover:from-gray-50 hover:to-transparent dark:border-gray-800 dark:hover:from-gray-800/50 dark:hover:to-transparent cursor-pointer"
    :class="{
      'bg-gradient-to-r from-blue-50/50 to-transparent dark:from-blue-900/10 dark:to-transparent border-l-4 border-l-blue-500': !notification.read_at,
      'opacity-75': notification.read_at
    }" @click="handleClick">
    <!-- Unread indicator dot -->
    <div v-if="!notification.read_at"
      class="absolute left-0 top-1/2 -translate-y-1/2 w-1.5 h-12 bg-gradient-to-b from-blue-500 to-blue-600 rounded-r-full shadow-lg shadow-blue-500/50">
    </div>

    <!-- Icon with gradient background -->
    <div class="flex-shrink-0 relative">
      <div
        class="w-12 h-12 rounded-xl flex items-center justify-center shadow-lg transition-transform duration-200 group-hover:scale-110"
        :class="getIconBackgroundClass(iconColor)">
        <i :class="['fas', icon, 'text-white text-lg']"></i>
      </div>
      <!-- Badge pour les notifications importantes -->
      <div v-if="isUrgentNotification"
        class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full flex items-center justify-center shadow-lg">
        <i class="fas fa-exclamation text-white text-xs"></i>
      </div>
    </div>

    <!-- Content -->
    <div class="flex-1 min-w-0">
      <!-- Title with badge -->
      <div class="flex items-start gap-2 mb-1.5">
        <h4 class="text-sm font-bold text-gray-900 dark:text-white flex-1 line-clamp-1">
          {{ notification.data?.title || notification.title || 'Notification' }}
        </h4>
        <span v-if="getNotificationBadge()"
          class="flex-shrink-0 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full"
          :class="getNotificationBadge().class">
          {{ getNotificationBadge().text }}
        </span>
      </div>

      <!-- Message -->
      <p class="text-sm text-gray-600 dark:text-gray-400 mb-2 line-clamp-2 leading-relaxed">
        {{ notification.data?.message || notification.message || 'Nouvelle notification' }}
      </p>

      <!-- Meta info -->
      <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-500">
        <span class="flex items-center gap-1.5">
          <i class="far fa-clock"></i>
          {{ notification.time_ago }}
        </span>
        <span
          v-if="notification.data?.auteur_nom || notification.data?.inviter_name || notification.data?.validateur_nom"
          class="flex items-center gap-1.5">
          <i class="far fa-user"></i>
          {{ notification.data.auteur_nom || notification.data.inviter_name || notification.data.validateur_nom }}
        </span>
        <span v-if="notification.data?.taux_realisation"
          class="flex items-center gap-1.5 text-green-600 dark:text-green-400 font-semibold">
          <i class="fas fa-chart-line"></i>
          {{ notification.data.taux_realisation }}%
        </span>
      </div>
    </div>

    <!-- Actions (visible au survol) -->
    <div
      class="flex-shrink-0 flex items-start gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
      <button v-if="!notification.read_at" type="button"
        class="p-2 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/20 text-green-600 dark:text-green-400 transition-colors duration-200"
        @click.stop="markAsRead" title="Marquer comme lu">
        <i class="fas fa-check text-sm"></i>
      </button>
      <button type="button"
        class="p-2 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 transition-colors duration-200"
        @click.stop="confirmDelete" title="Supprimer">
        <i class="fas fa-trash-alt text-sm"></i>
      </button>
    </div>

    <!-- Hover effect overlay -->
    <div
      class="absolute inset-0 bg-gradient-to-r from-transparent via-blue-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useNotifications } from '@/composables/useNotifications';

const props = defineProps({
  notification: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['click', 'mark-read', 'delete', 'open-resultat-modal']);

const router = useRouter();
const { getNotificationIcon, getNotificationColor } = useNotifications();

const icon = computed(() => getNotificationIcon(props.notification.data?.type || props.notification.type));
const iconColor = computed(() => getNotificationColor(props.notification.data?.type || props.notification.type));

const isUrgentNotification = computed(() => {
  const type = props.notification.data?.type || props.notification.type;
  return type === 'resultat_attente_n2' ||
    type === 'resultat_rejete' ||
    type === 'task_due_soon' ||
    type === 'deadline_approaching';
});

const getIconBackgroundClass = (color) => {
  const gradients = {
    blue: 'bg-gradient-to-br from-blue-500 to-blue-600',
    orange: 'bg-gradient-to-br from-orange-500 to-orange-600',
    green: 'bg-gradient-to-br from-green-500 to-green-600',
    purple: 'bg-gradient-to-br from-purple-500 to-purple-600',
    cyan: 'bg-gradient-to-br from-cyan-500 to-cyan-600',
    indigo: 'bg-gradient-to-br from-indigo-500 to-indigo-600',
    red: 'bg-gradient-to-br from-red-500 to-red-600',
    yellow: 'bg-gradient-to-br from-yellow-500 to-yellow-600',
    emerald: 'bg-gradient-to-br from-emerald-500 to-emerald-600',
    brand: 'bg-gradient-to-br from-brand-500 to-brand-600',
  };
  return gradients[color] || 'bg-gradient-to-br from-gray-500 to-gray-600';
};

const getNotificationBadge = () => {
  const type = props.notification.data?.type || props.notification.type;

  const badges = {
    'resultat_soumis': { text: 'À valider', class: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' },
    'resultat_attente_n2': { text: 'Urgent', class: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' },
    'resultat_valide_n1': { text: 'Validé N1', class: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' },
    'resultat_valide_n2': { text: 'Validé N2', class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' },
    'resultat_rejete': { text: 'Rejeté', class: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' },
    'validation_n1_confirmee': { text: 'Confirmé', class: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' },
    'validation_n2_confirmee': { text: 'Confirmé', class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' },
    'rejet_confirme': { text: 'Confirmé', class: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' },
    'resultat_validation_complete': { text: 'Complet', class: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400' },
    'resultat_rejete_n2_info': { text: 'Info', class: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' },
    'workspace_invitation': { text: 'Invitation', class: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' },
    'projet_invitation': { text: 'Invitation', class: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' },
  };
  return badges[type];
};

const confirmDelete = () => {
  if (confirm('Supprimer cette notification ?')) {
    emit('delete', props.notification.id);
  }
};

const markAsRead = () => {
  emit('mark-read', props.notification.id);
};


const handleClick = () => {
  // Marquer comme lu automatiquement lors du clic
  if (!props.notification.read_at) {
    emit('mark-read', props.notification.id);
  }

  const type = props.notification.type || props.notification.data?.type;

  // 📋 NOTIFICATIONS DE RÉSULTATS → Ouvrir le modal
  if (isResultatNotification(type)) {
    const resultatId = props.notification.data?.resultat_id;
    if (resultatId) {
      emit('open-resultat-modal', resultatId);
      return;
    }
  }

  // 💼 INVITATIONS WORKSPACE
  if (type === 'workspace_invitation' && props.notification.data?.action_url) {
    const actionUrl = props.notification.data.action_url;
    const path = actionUrl.startsWith('http') ? new URL(actionUrl).pathname : actionUrl;
    router.push(path);
    return;
  }

  // 📁 INVITATIONS PROJET
  if (type === 'projet_invitation' && props.notification.data?.token) {
    router.push(`/invitations/projet/${props.notification.data.token}`);
    return;
  }

  // 🔗 AUTRES avec URL
  const url = props.notification.data?.url || props.notification.url;
  if (url) {
    // Vérifier si c'est une URL de résultat (format /resultats/{id})
    if (url.match(/\/resultats\/\d+/)) {
      const resultatId = url.match(/\/resultats\/(\d+)/)[1];
      emit('open-resultat-modal', parseInt(resultatId));
    } else {
      router.push(url);
    }
  } else {
    // Ouvrir le modal de détail de notification
    emit('click', props.notification);
  }
};
// Helper pour identifier les notifications de résultats
const isResultatNotification = (type) => {
  return [
    'resultat_soumis',
    'resultat_attente_n2',
    'resultat_valide_n1',
    'resultat_valide_n2',
    'resultat_rejete',
    'validation_n1_confirmee',
    'validation_n2_confirmee',
    'rejet_confirme',
    'resultat_validation_complete',
    'resultat_rejete_n2_info'
  ].includes(type);
};
</script>

<style scoped>
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>