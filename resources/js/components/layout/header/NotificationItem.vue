<!-- resources\js\components\layout\header\NotificationItem.vue -->
<template>
  <div
    class="group relative flex gap-3 border-b border-gray-100 dark:border-gray-800 p-4 cursor-pointer transition-colors duration-150 hover:bg-gray-50 dark:hover:bg-gray-800/50"
    :class="{ 'border-l-2 border-l-brand-500': !notification.read_at }"
    @click="handleClick"
  >
    <!-- Icon -->
    <div class="shrink-0 relative">
      <div
        class="w-10 h-10 rounded-3 flex items-center justify-center"
        :class="getIconBackgroundClass(iconColor)"
      >
        <i :class="['fas', icon, 'text-white text-sm']"></i>
      </div>
      <div v-if="isUrgentNotification"
        class="absolute -top-1 -right-1 w-4 h-4 bg-error-500 rounded-full flex items-center justify-center">
        <i class="fas fa-exclamation text-white" style="font-size: 8px;"></i>
      </div>
    </div>

    <!-- Content -->
    <div class="flex-1 min-w-0">
      <div class="flex items-start gap-2 mb-1">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-white flex-1 line-clamp-1">
          {{ notification.data?.title || notification.title || 'Notification' }}
        </h4>
        <span v-if="getNotificationBadge()"
          class="shrink-0 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full"
          :class="getNotificationBadge().class">
          {{ getNotificationBadge().text }}
        </span>
      </div>

      <p class="text-xs text-gray-600 dark:text-gray-400 mb-1.5 line-clamp-2 leading-relaxed">
        {{ notification.data?.message || notification.message || 'Nouvelle notification' }}
      </p>

      <div class="flex items-center gap-3 text-xs text-gray-400 dark:text-gray-500">
        <span class="flex items-center gap-1">
          <i class="far fa-clock"></i>
          {{ notification.time_ago }}
        </span>
        <span
          v-if="notification.data?.auteur_nom || notification.data?.inviter_name || notification.data?.validateur_nom"
          class="flex items-center gap-1">
          <i class="far fa-user"></i>
          {{ notification.data.auteur_nom || notification.data.inviter_name || notification.data.validateur_nom }}
        </span>
        <span v-if="notification.data?.taux_realisation"
          class="flex items-center gap-1 text-success-600 dark:text-success-400 font-semibold">
          <i class="fas fa-chart-line"></i>
          {{ notification.data.taux_realisation }}%
        </span>
      </div>
    </div>

    <!-- Actions (visible on hover) -->
    <div class="shrink-0 flex items-start gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-150">
      <button v-if="!notification.read_at" type="button"
        class="p-1.5 rounded-3 hover:bg-success-50 dark:hover:bg-success-500/10 text-success-600 dark:text-success-400 transition-colors"
        @click.stop="markAsRead" title="Marquer comme lu">
        <i class="fas fa-check text-xs"></i>
      </button>
      <button type="button"
        class="p-1.5 rounded-3 hover:bg-error-50 dark:hover:bg-error-500/10 text-error-600 dark:text-error-400 transition-colors"
        @click.stop="confirmDelete" title="Supprimer">
        <i class="fas fa-trash-alt text-xs"></i>
      </button>
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
  const colors = {
    blue: 'bg-brand-500',
    orange: 'bg-warning-500',
    green: 'bg-success-500',
    purple: 'bg-purple-500',
    cyan: 'bg-brand-400',
    indigo: 'bg-purple-600',
    red: 'bg-error-500',
    yellow: 'bg-warning-400',
    emerald: 'bg-success-400',
    brand: 'bg-brand-500',
  };
  return colors[color] || 'bg-gray-400';
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

