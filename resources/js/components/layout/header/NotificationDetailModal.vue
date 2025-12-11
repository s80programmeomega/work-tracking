<!-- resources\js\components\layout\header\NotificationDetailModal.vue -->
<template>
  <teleport to="body">
    <transition name="modal-fade">
      <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md"
        @click.self="close">
        <div
          class="relative w-full max-w-3xl bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-h-[90vh] overflow-hidden border border-gray-200 dark:border-gray-700"
          @click.stop>
          <!-- Header avec design moderne et gradient dynamique -->
          <div class="relative overflow-hidden" :class="getHeaderGradientClass()">
            <!-- Background pattern animé -->
            <div class="absolute inset-0 opacity-10">
              <div class="absolute inset-0 animate-pulse-slow"
                style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 24px 24px;">
              </div>
            </div>

            <!-- Decorative blobs -->
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>

            <div class="relative z-10 px-8 py-6 flex items-start justify-between">
              <div class="flex items-start gap-4 flex-1">
                <!-- Icon avec animation -->
                <div
                  class="flex-shrink-0 w-16 h-16 rounded-2xl flex items-center justify-center shadow-2xl backdrop-blur-sm border-2 border-white/30 bg-white/20 transform transition-transform duration-300 hover:scale-110">
                  <i :class="['fas', icon, 'text-white', 'text-3xl']"></i>
                </div>

                <!-- Title and time -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-start gap-3 mb-2">
                    <h3 class="text-2xl font-black text-white drop-shadow-lg flex-1">
                      {{ notification?.title }}
                    </h3>
                    <span v-if="getNotificationBadge()"
                      class="flex-shrink-0 px-3 py-1 text-xs font-bold uppercase tracking-wide rounded-full bg-white/20 backdrop-blur-sm text-white border border-white/30">
                      {{ getNotificationBadge() }}
                    </span>
                  </div>
                  <p class="text-sm text-white/90 flex items-center gap-2 font-medium">
                    <i class="fas fa-clock text-xs"></i>
                    {{ notification?.time_ago }}
                  </p>
                </div>
              </div>

              <!-- Close button -->
              <button @click="close"
                class="ml-4 p-2.5 rounded-xl hover:bg-white/20 backdrop-blur-sm text-white transition-all duration-200 border border-white/30 hover:rotate-90">
                <i class="fas fa-times text-xl"></i>
              </button>
            </div>
          </div>

          <!-- Content avec scroll custom -->
          <div class="p-8 overflow-y-auto max-h-[calc(90vh-280px)] custom-scrollbar">

            <!-- ========== RÉSULTATS SOUMIS ========== -->
            <div v-if="isResultatNotification" class="space-y-6">
              <!-- Carte principale du résultat -->
              <div
                class="p-6 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-950/50 dark:to-indigo-950/50 rounded-2xl border-2 border-blue-200 dark:border-blue-800 shadow-lg">
                <div class="flex items-center gap-4 mb-6">
                  <div
                    class="w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center shadow-xl">
                    <i class="fas fa-tasks text-white text-3xl"></i>
                  </div>
                  <div class="flex-1">
                    <p class="text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider mb-1">Tâche
                    </p>
                    <p class="text-2xl font-black text-gray-900 dark:text-white mb-1">{{ notification.data.tache_titre
                      }}</p>
                    <div v-if="notification.data.taux_realisation" class="flex items-center gap-2">
                      <div class="flex-1 h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div
                          class="h-full bg-gradient-to-r from-green-500 to-emerald-500 rounded-full transition-all duration-500"
                          :style="{ width: `${notification.data.taux_realisation}%` }"></div>
                      </div>
                      <span class="text-sm font-bold text-green-600 dark:text-green-400">
                        {{ notification.data.taux_realisation }}%
                      </span>
                    </div>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="flex items-start gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl">
                    <i class="fas fa-user text-blue-600 dark:text-blue-400 text-xl mt-1"></i>
                    <div>
                      <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold mb-1">Auteur</p>
                      <p class="font-bold text-gray-900 dark:text-white">{{ notification.data.auteur_nom ||
                        notification.data.assignee_nom }}</p>
                    </div>
                  </div>

                  <div v-if="notification.data.validateur_nom"
                    class="flex items-start gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl">
                    <i class="fas fa-user-check text-green-600 dark:text-green-400 text-xl mt-1"></i>
                    <div>
                      <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold mb-1">Validé par</p>
                      <p class="font-bold text-gray-900 dark:text-white">{{ notification.data.validateur_nom }}</p>
                    </div>
                  </div>
                </div>

                <div v-if="notification.data.commentaire"
                  class="mt-4 p-4 bg-white dark:bg-gray-800 rounded-xl border-l-4 border-blue-500">
                  <p class="text-xs font-bold text-blue-700 dark:text-blue-400 mb-2 uppercase tracking-wide">Commentaire
                  </p>
                  <p class="text-gray-700 dark:text-gray-300 italic leading-relaxed">"{{ notification.data.commentaire
                    }}"</p>
                </div>
              </div>

              <!-- Statut de validation -->
              <div v-if="notification.type === 'resultat_valide_n2'" class="flex items-center justify-center">
                <div
                  class="inline-flex items-center gap-3 px-6 py-3 rounded-2xl bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 border-2 border-green-300 dark:border-green-700">
                  <i class="fas fa-trophy text-yellow-500 text-2xl animate-bounce"></i>
                  <span class="text-sm font-black text-green-800 dark:text-green-300">Validation complète : N1 ✓ + N2
                    ✓</span>
                </div>
              </div>
            </div>

            <!-- ========== INVITATIONS PROJET ========== -->
            <div v-else-if="notification?.type === 'projet_invitation'" class="space-y-6">
              <div
                class="p-6 bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-950/50 dark:to-indigo-950/50 rounded-2xl border-2 border-purple-200 dark:border-purple-800 shadow-lg">
                <div class="flex items-center gap-4 mb-6">
                  <div
                    class="w-20 h-20 rounded-2xl bg-gradient-to-br from-purple-600 to-indigo-600 flex items-center justify-center shadow-xl">
                    <i class="fas fa-project-diagram text-white text-3xl"></i>
                  </div>
                  <div>
                    <p class="text-xs font-bold text-purple-700 dark:text-purple-400 uppercase tracking-wider mb-1">
                      Projet</p>
                    <p class="text-2xl font-black text-gray-900 dark:text-white">{{ notification.data.projet_nom }}</p>
                  </div>
                </div>

                <div class="space-y-3">
                  <div class="flex items-start gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl">
                    <i class="fas fa-user text-purple-600 dark:text-purple-400 text-xl"></i>
                    <div>
                      <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold mb-1">Invité par</p>
                      <p class="font-bold text-gray-900 dark:text-white">{{ notification.data.inviter_nom }}</p>
                    </div>
                  </div>

                  <div class="flex items-start gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl">
                    <i class="fas fa-user-tag text-purple-600 dark:text-purple-400 text-xl"></i>
                    <div>
                      <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold mb-1">Rôle proposé</p>
                      <p class="font-bold text-gray-900 dark:text-white">{{ getRoleLabel(notification.data.role) }}</p>
                    </div>
                  </div>

                  <div v-if="notification.data.message"
                    class="p-4 bg-white dark:bg-gray-800 rounded-xl border-l-4 border-purple-500">
                    <p class="text-xs font-bold text-purple-700 dark:text-purple-400 mb-2 uppercase tracking-wide">
                      Message personnel</p>
                    <p class="text-gray-700 dark:text-gray-300 italic leading-relaxed">"{{ notification.data.message }}"
                    </p>
                  </div>
                </div>
              </div>

              <div v-if="notification.data.expires_at"
                class="flex items-center justify-center gap-3 text-gray-700 dark:text-gray-300 bg-orange-50 dark:bg-orange-950/30 rounded-xl p-4 border-2 border-orange-200 dark:border-orange-800">
                <i class="fas fa-clock text-orange-500 text-xl"></i>
                <span class="font-bold">Expire {{ formatExpirationDate(notification.data.expires_at) }}</span>
              </div>

              <div v-if="notification.data.is_pending" class="flex items-center justify-center">
                <span
                  class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-black bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 border-2 border-yellow-300 dark:border-yellow-700">
                  <i class="fas fa-hourglass-half animate-pulse"></i>
                  En attente de réponse
                </span>
              </div>
            </div>

            <!-- ========== INVITATIONS WORKSPACE ========== -->
            <div v-else-if="notification?.type === 'workspace_invitation'" class="space-y-6">
              <div
                class="p-6 bg-gradient-to-br from-brand-50 to-blue-50 dark:from-brand-950/50 dark:to-blue-950/50 rounded-2xl border-2 border-brand-200 dark:border-brand-800 shadow-lg">
                <div class="flex items-center gap-4 mb-6">
                  <div v-if="notification.data.workspace_logo"
                    class="w-20 h-20 rounded-2xl overflow-hidden border-2 border-brand-300 dark:border-brand-700 shadow-xl">
                    <img :src="notification.data.workspace_logo" :alt="notification.data.workspace_name"
                      class="w-full h-full object-cover" />
                  </div>
                  <div v-else
                    class="w-20 h-20 rounded-2xl bg-gradient-to-br from-brand-600 to-blue-600 flex items-center justify-center border-2 border-brand-300 dark:border-brand-700 shadow-xl">
                    <span class="text-white font-black text-2xl">{{
                      getWorkspaceInitials(notification.data.workspace_name) }}</span>
                  </div>
                  <div>
                    <p class="text-xs font-bold text-brand-700 dark:text-brand-400 uppercase tracking-wider mb-1">
                      Workspace</p>
                    <p class="text-2xl font-black text-gray-900 dark:text-white">{{ notification.data.workspace_name }}
                    </p>
                  </div>
                </div>

                <div class="space-y-3">
                  <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                      <strong class="text-gray-900 dark:text-white font-black">{{ notification.data.inviter_name
                        }}</strong> vous invite à rejoindre ce workspace
                    </p>
                  </div>
                  <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                    <p class="text-gray-600 dark:text-gray-400">
                      Rôle proposé: <strong class="text-gray-900 dark:text-white font-black">{{
                        getRoleLabel(notification.data.role) }}</strong>
                    </p>
                  </div>
                  <div v-if="notification.data.invitation_message"
                    class="p-4 bg-white dark:bg-gray-800 rounded-xl border-l-4 border-brand-400">
                    <p class="text-xs font-bold text-brand-700 dark:text-brand-400 mb-2 uppercase tracking-wide">Message
                    </p>
                    <p class="text-gray-700 dark:text-gray-300 italic leading-relaxed">"{{
                      notification.data.invitation_message }}"</p>
                  </div>
                </div>
              </div>

              <div class="flex items-center justify-between text-sm">
                <span v-if="notification.data.expires_at" class="text-gray-600 dark:text-gray-400 font-semibold">
                  Expire {{ formatExpirationDate(notification.data.expires_at) }}
                </span>
                <span v-if="notification.data.is_pending" class="text-orange-600 dark:text-orange-400 font-black">
                  <i class="fas fa-hourglass-half mr-1 animate-pulse"></i>
                  En attente de réponse
                </span>
              </div>
            </div>

            <!-- ========== CHANGEMENT DE RESPONSABLE ========== -->
            <div v-else-if="notification?.type === 'responsable_changed'" class="space-y-6">

              <div class="p-6 bg-gradient-to-br from-indigo-50 to-blue-50 dark:from-indigo-950/50 dark:to-blue-950/50 
              rounded-2xl border-2 border-indigo-200 dark:border-indigo-800 shadow-lg">

                <div class="flex items-center gap-4 mb-6">
                  <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-indigo-600 to-blue-600 
                  flex items-center justify-center shadow-xl">
                    <i class="fas fa-user-shield text-white text-3xl"></i>
                  </div>

                  <div>
                    <p class="text-xs font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider mb-1">
                      Activité
                    </p>
                    <p class="text-2xl font-black text-gray-900 dark:text-white">
                      {{ notification.data.activite_nom }}
                    </p>
                  </div>
                </div>

                <div class="space-y-4">

                  <div class="flex items-start gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl">
                    <i class="fas fa-user-times text-red-500 dark:text-red-400 text-xl mt-1"></i>
                    <div>
                      <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold mb-1">Ancien responsable</p>
                      <p class="font-bold text-gray-900 dark:text-white">
                        {{ notification.data.old_responsable }}
                      </p>
                    </div>
                  </div>

                  <div class="flex items-start gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl">
                    <i class="fas fa-user-check text-green-600 dark:text-green-400 text-xl mt-1"></i>
                    <div>
                      <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold mb-1">Nouveau responsable</p>
                      <p class="font-bold text-gray-900 dark:text-white">
                        {{ notification.data.new_responsable }}
                      </p>
                    </div>
                  </div>

                  <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border-l-4 border-indigo-500">
                    <p class="text-xs font-bold text-indigo-700 dark:text-indigo-400 mb-2 uppercase tracking-wide">
                      Détails
                    </p>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                      {{ notification.data.message }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Bouton pour visiter l'activité -->
              <div class="flex justify-center">
                <button @click="$router.push(`/activites/${notification.data.activite_id}`)" class="px-6 py-3 font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-2xl shadow-lg transition-all duration-200">
                  Voir l’activité
                </button>
              </div>

            </div>

            <!-- ========== AUTRES NOTIFICATIONS ========== -->
            <div v-else>
              <div
                class="mb-6 p-6 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700">
                <h4 class="text-xs font-black text-gray-700 dark:text-gray-300 mb-3 uppercase tracking-wider">Message
                </h4>
                <p class="text-base text-gray-900 dark:text-white leading-relaxed font-medium">
                  {{ notification?.message }}
                </p>
              </div>

              <div v-if="hasAdditionalData" class="space-y-4">
                <h4 class="text-xs font-black text-gray-700 dark:text-gray-300 mb-3 uppercase tracking-wider">Détails
                </h4>

                <div v-if="notification?.data?.task_title"
                  class="flex items-start gap-4 p-5 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/30 dark:to-blue-900/30 rounded-xl border-2 border-blue-200 dark:border-blue-800">
                  <i class="fas fa-tasks text-blue-600 dark:text-blue-400 text-2xl mt-0.5"></i>
                  <div>
                    <p class="text-xs text-blue-600 dark:text-blue-400 font-bold mb-1 uppercase tracking-wide">Tâche</p>
                    <p class="text-sm font-black text-gray-900 dark:text-white">{{ notification.data.task_title }}</p>
                  </div>
                </div>

                <div v-if="notification?.data?.project_title"
                  class="flex items-start gap-4 p-5 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950/30 dark:to-purple-900/30 rounded-xl border-2 border-purple-200 dark:border-purple-800">
                  <i class="fas fa-project-diagram text-purple-600 dark:text-purple-400 text-2xl mt-0.5"></i>
                  <div>
                    <p class="text-xs text-purple-600 dark:text-purple-400 font-bold mb-1 uppercase tracking-wide">
                      Projet</p>
                    <p class="text-sm font-black text-gray-900 dark:text-white">{{ notification.data.project_title }}
                    </p>
                  </div>
                </div>

                <div v-if="notification?.data?.document_name"
                  class="flex items-start gap-4 p-5 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/30 dark:to-green-900/30 rounded-xl border-2 border-green-200 dark:border-green-800">
                  <i class="fas fa-file text-green-600 dark:text-green-400 text-2xl mt-0.5"></i>
                  <div>
                    <p class="text-xs text-green-600 dark:text-green-400 font-bold mb-1 uppercase tracking-wide">
                      Document</p>
                    <p class="text-sm font-black text-gray-900 dark:text-white">{{ notification.data.document_name }}
                    </p>
                  </div>
                </div>

                <div v-if="notification?.data?.hours_until_due"
                  class="flex items-start gap-4 p-5 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-950/30 dark:to-orange-900/30 rounded-xl border-2 border-orange-200 dark:border-orange-800">
                  <i class="fas fa-clock text-orange-600 dark:text-orange-400 text-2xl mt-0.5"></i>
                  <div>
                    <p class="text-xs text-orange-600 dark:text-orange-400 font-bold mb-1 uppercase tracking-wide">
                      Échéance</p>
                    <p class="text-sm font-black text-gray-900 dark:text-white">
                      Dans {{ notification.data.hours_until_due }} heure(s)
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Status badge amélioré -->
            <div
              class="mt-8 flex items-center justify-between p-5 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700">
              <span v-if="!notification?.read_at"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-black bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-lg">
                <i class="fas fa-circle text-[8px] animate-pulse"></i>
                Non lu
              </span>
              <span v-else
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-black bg-gradient-to-r from-green-500 to-emerald-500 text-white shadow-lg">
                <i class="fas fa-check-circle text-sm"></i>
                Lu
              </span>

              <span
                class="text-xs text-gray-500 dark:text-gray-400 font-bold px-4 py-2 bg-white dark:bg-gray-800 rounded-full border border-gray-300 dark:border-gray-600">
                {{ notification?.type }}
              </span>
            </div>
          </div>

          <!-- Footer avec actions améliorées -->
          <div
            class="flex items-center justify-between gap-4 p-6 border-t-2 border-gray-200 dark:border-gray-800 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800/50 dark:to-gray-900/50">
            <div class="flex items-center gap-3">
              <button v-if="!notification?.read_at" @click="handleMarkAsRead"
                class="px-6 py-3 rounded-xl text-sm font-black bg-gradient-to-r from-green-600 to-green-700 text-white hover:from-green-700 hover:to-green-800 transition-all duration-200 shadow-lg hover:shadow-xl hover:scale-105 flex items-center gap-2">
                <i class="fas fa-check"></i>
                Marquer comme lu
              </button>

              <button @click="handleDelete"
                class="px-6 py-3 rounded-xl text-sm font-black bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30 transition-all duration-200 hover:scale-105 flex items-center gap-2 border-2 border-red-300 dark:border-red-800">
                <i class="fas fa-trash-alt"></i>
                Supprimer
              </button>
            </div>

            <button v-if="notification?.data?.action_url || notification?.data?.token || notification?.data?.url"
              @click="handleGoToAction"
              class="px-8 py-3 rounded-xl text-sm font-black bg-gradient-to-r from-brand-600 to-purple-600 text-white hover:from-brand-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl hover:scale-105 flex items-center gap-2">
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

const isResultatNotification = computed(() => {
  return ['resultat_soumis', 'resultat_attente_n2', 'resultat_valide_n1', 'resultat_valide_n2'].includes(props.notification?.type);
});

const hasAdditionalData = computed(() => {
  if (!props.notification?.data) return false;
  return (
    props.notification.data.task_title ||
    props.notification.data.project_title ||
    props.notification.data.document_name ||
    props.notification.data.hours_until_due
  );
});

const getHeaderGradientClass = () => {
  const gradients = {
    'resultat_soumis': 'bg-gradient-to-r from-orange-600 via-orange-700 to-red-700',
    'resultat_attente_n2': 'bg-gradient-to-r from-red-600 via-red-700 to-pink-700',
    'resultat_valide_n1': 'bg-gradient-to-r from-green-600 via-green-700 to-emerald-700',
    'resultat_valide_n2': 'bg-gradient-to-r from-emerald-600 via-emerald-700 to-green-700',
    'projet_invitation': 'bg-gradient-to-r from-purple-600 via-purple-700 to-indigo-700',
    'workspace_invitation': 'bg-gradient-to-r from-brand-600 via-brand-700 to-blue-700',
  };
  return gradients[props.notification?.type] || 'bg-gradient-to-r from-brand-600 via-brand-700 to-purple-700';
};

const getNotificationBadge = () => {
  const badges = {
    'resultat_soumis': 'À valider',
    'resultat_attente_n2': 'Urgent',
    'resultat_valide_n1': 'Validé N1',
    'resultat_valide_n2': 'Complet',
    'workspace_invitation': 'Invitation',
    'projet_invitation': 'Invitation',
  };
  return badges[props.notification?.type];
};

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
  if (isResultatNotification.value) {
    return 'Voir le résultat';
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
  if (!props.notification.read_at) {
    emit('mark-read', props.notification.id);
  }

  if (props.notification?.type === 'projet_invitation' && props.notification.data?.token) {
    router.push(`/invitations/projet/${props.notification.data.token}`);
  } else if (props.notification?.data?.action_url) {
    const url = props.notification.data.action_url;
    const path = url.startsWith('http') ? new URL(url).pathname : url;
    router.push(path);
  } else if (props.notification?.data?.url) {
    router.push(props.notification.data.url);
  }

  close();
};

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

.modal-fade-enter-active>div,
.modal-fade-leave-active>div {
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease;
}

.modal-fade-enter-from>div,
.modal-fade-leave-to>div {
  transform: scale(0.9) translateY(30px);
  opacity: 0;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 10px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.05);
  border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: linear-gradient(to bottom, rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.4));
  border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(to bottom, rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.5));
}

@keyframes pulse-slow {

  0%,
  100% {
    opacity: 0.1;
  }

  50% {
    opacity: 0.15;
  }
}

.animate-pulse-slow {
  animation: pulse-slow 3s ease-in-out infinite;
}
</style>