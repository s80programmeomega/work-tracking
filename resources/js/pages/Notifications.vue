<!-- resources\js\pages\Notifications.vue -->
<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="'Notifications'" />
    <div class="mx-auto max-w-screen-2xl">
      <!-- Chargement initial -->
      <div v-if="initialLoading" class="flex flex-col items-center justify-center py-20">
        <i class="fas fa-spinner fa-spin text-6xl text-brand-500 dark:text-brand-400"></i>
        <p class="mt-6 text-lg font-medium text-gray-700 dark:text-gray-300">Chargement des notifications…</p>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">Veuillez patienter</p>
      </div>

      <!-- Contenu principal -->
      <div v-else>
        <!-- En-tête de page -->
        <div class="mb-6 flex flex-wrap gap-3 items-center justify-between">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">
            Notifications
            <span v-if="unreadCount > 0" class="ml-2 text-sm font-semibold text-brand-600 dark:text-brand-400">
              ({{ unreadCount }} non {{ unreadCount > 1 ? 'lues' : 'lue' }})
            </span>
          </h2>

          <div class="flex flex-wrap items-center gap-2">
            <!-- Bouton Statistiques -->
            <button
              @click="showStats = !showStats"
              class="inline-flex items-center gap-2 rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              Statistiques
            </button>
            <button v-if="unreadCount > 0" @click="handleMarkAllAsRead"
              class="px-3 py-2 rounded-3 text-sm font-medium bg-success-500 hover:bg-success-600 text-white transition-colors flex items-center gap-1.5">
              <i class="fas fa-check-double"></i>
              Tout marquer lu
            </button>
            <button v-if="hasReadNotifications" @click="handleDeleteAllRead"
              class="px-3 py-2 rounded-3 text-sm font-medium bg-error-50 hover:bg-error-100 text-error-700 dark:bg-error-500/10 dark:hover:bg-error-500/20 dark:text-error-400 border border-error-200 dark:border-error-700 transition-colors flex items-center gap-1.5">
              <i class="fas fa-trash"></i>
              Supprimer les lues
            </button>
            <button @click="$router.push('/notification-preferences')"
              class="px-3 py-2 rounded-3 text-sm font-medium bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors flex items-center gap-1.5">
              <i class="fas fa-sliders-h"></i>
              Préférences
            </button>
          </div>
        </div>

        <!-- Panneau statistiques -->
        <transition
          enter-active-class="transition-all duration-300 ease-out"
          enter-from-class="opacity-0 -translate-y-4"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-200 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-4"
        >
          <NotificationsStats
            v-if="showStats"
            :statistics="statistics"
            :loading="initialLoading"
            @close="showStats = false"
            class="mb-6"
          />
        </transition>

        <!-- Filtres -->
        <div class="flex flex-wrap gap-2 mb-4">
          <!-- Filtre lu/non-lu -->
          <div class="flex rounded-3 border border-gray-200 dark:border-gray-700 overflow-hidden">
            <button @click="filterRead = 'all'"
              :class="filterRead === 'all' ? 'bg-brand-500 text-white' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800'"
              class="px-3 py-2 text-sm font-medium transition-colors">
              Toutes
            </button>
            <button @click="filterRead = 'unread'"
              :class="filterRead === 'unread' ? 'bg-brand-500 text-white' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800'"
              class="px-3 py-2 text-sm font-medium border-l border-gray-200 dark:border-gray-700 transition-colors">
              Non lues
              <span v-if="unreadCount > 0" class="ml-1 text-xs font-bold">{{ unreadCount }}</span>
            </button>
          </div>

          <!-- Séparateur -->
          <div class="w-px bg-gray-200 dark:bg-gray-700 self-stretch"></div>

          <!-- Filtres par catégorie -->
          <button
            v-for="cat in categoryFilters"
            :key="cat.key"
            @click="toggleCategory(cat.key)"
            :class="[
              'px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors flex items-center gap-1.5',
              activeCategories.has(cat.key)
                ? `${cat.activeClass} border-transparent`
                : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800'
            ]">
            <i :class="['fas', cat.icon, 'text-[10px]']"></i>
            {{ cat.label }}
          </button>
        </div>

        <!-- Liste des notifications -->
        <div class="rounded-3 border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
          <div v-if="loading" class="flex items-center justify-center py-16">
            <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
          </div>

          <div v-else-if="displayedNotifications.length === 0" class="flex flex-col items-center justify-center py-16 gap-3">
            <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
              <i class="fas fa-bell-slash text-3xl text-gray-400 dark:text-gray-600"></i>
            </div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
              {{ filterRead === 'unread' ? 'Aucune notification non lue' : 'Aucune notification' }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 text-center max-w-xs">
              {{ filterRead === 'unread'
                ? 'Toutes vos notifications ont été lues. Bravo !'
                : activeCategories.size > 0
                  ? 'Aucune notification dans cette catégorie.'
                  : 'Vous n\'avez reçu aucune notification pour l\'instant.' }}
            </p>
          </div>

          <div v-else ref="listRef">
            <NotificationItem
              v-for="notification in displayedNotifications"
              :key="notification.id"
              :notification="notification"
              class="stagger-item"
              @click="handleNotificationClick"
              @mark-read="handleMarkAsRead"
              @delete="handleDelete"
              @open-resultat-modal="handleOpenResultatModal"
            />
          </div>

          <!-- Pagination -->
          <div v-if="pagination.total > pagination.per_page" class="border-t border-gray-200 dark:border-gray-800 p-4 flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Page {{ pagination.current_page }} / {{ pagination.last_page }}
              <span class="ml-1 text-gray-400">({{ pagination.total }} au total)</span>
            </p>

            <div class="flex gap-2">
              <button @click="loadPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
                class="px-3 py-1.5 rounded-3 text-sm font-medium border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 transition-colors disabled:opacity-50 disabled:cursor-not-allowed hover:enabled:bg-gray-50 dark:hover:enabled:bg-gray-800 flex items-center gap-1.5">
                <i class="fas fa-chevron-left text-xs"></i>
                Précédent
              </button>
              <button @click="loadPage(pagination.current_page + 1)"
                :disabled="pagination.current_page === pagination.last_page"
                class="px-3 py-1.5 rounded-3 text-sm font-medium border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 transition-colors disabled:opacity-50 disabled:cursor-not-allowed hover:enabled:bg-gray-50 dark:hover:enabled:bg-gray-800 flex items-center gap-1.5">
                Suivant
                <i class="fas fa-chevron-right text-xs"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Modal de détail -->
        <NotificationDetailModal
          :is-open="showDetailModal"
          :notification="selectedNotification"
          @close="closeDetailModal"
          @mark-read="handleMarkAsRead"
          @delete="handleDelete"
        />

        <!-- Modal résultat -->
        <ResultatDetailModal
          v-if="showResultatModal && selectedResultat"
          :resultat="selectedResultat"
          @close="closeResultatModal"
        />
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useNotifications } from '@/composables/useNotifications';
import { useStagger } from '@/composables/useAnimations';
import NotificationItem from '@/components/layout/header/NotificationItem.vue';
import NotificationDetailModal from '@/components/layout/header/NotificationDetailModal.vue';
import ResultatDetailModal from '@/components/taches/resultats/ResultatDetailModal.vue';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import NotificationsStats from '@/components/layout/header/NotificationsStats.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import api from '@/api/axios';

const router = useRouter();
const { staggerRef: listRef, applyStagger } = useStagger(35);

const filterRead = ref('all');
const showStats = ref(false);
const activeCategories = ref(new Set());
const showDetailModal = ref(false);
const selectedNotification = ref(null);
const showResultatModal = ref(false);
const selectedResultat = ref(null);
const initialLoading = ref(true);

const {
  notifications,
  unreadCount,
  loading,
  fetchAll,
  markAsRead,
  markAllAsRead,
  deleteNotification,
  deleteAllRead,
  fetchStatistics,
} = useNotifications();

const statistics = ref({ total: 0, unread: 0, read_today: 0, by_type: {} });

const pagination = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 });

// Définition des catégories de filtre
const categoryFilters = [
  {
    key: 'resultats',
    label: 'Résultats',
    icon: 'fa-file-check',
    types: ['resultat_soumis', 'resultat_attente_n2', 'resultat_valide_n1', 'resultat_valide_n2', 'resultat_rejete', 'resultat_validation_complete', 'resultat_rejete_n2_info', 'validation_n1_confirmee', 'validation_n2_confirmee', 'rejet_confirme'],
    activeClass: 'bg-brand-500 text-white',
  },
  {
    key: 'taches',
    label: 'Tâches',
    icon: 'fa-tasks',
    types: ['task_assigned', 'task_updated', 'task_completed', 'task_due_soon', 'deadline_approaching'],
    activeClass: 'bg-purple-500 text-white',
  },
  {
    key: 'fichiers',
    label: 'Fichiers & liens',
    icon: 'fa-paperclip',
    types: ['task_file_added', 'task_file_removed', 'task_link_added', 'task_link_removed'],
    activeClass: 'bg-success-500 text-white',
  },
  {
    key: 'invitations',
    label: 'Invitations',
    icon: 'fa-envelope',
    types: ['workspace_invitation', 'projet_invitation'],
    activeClass: 'bg-warning-500 text-white',
  },
  {
    key: 'documents',
    label: 'Documents',
    icon: 'fa-file-alt',
    types: ['document_uploaded', 'document_deleted', 'document_shared'],
    activeClass: 'bg-warning-400 text-white',
  },
];

const toggleCategory = (key) => {
  const set = new Set(activeCategories.value);
  if (set.has(key)) {
    set.delete(key);
  } else {
    set.add(key);
  }
  activeCategories.value = set;
};

const activeCategoryTypes = computed(() => {
  if (activeCategories.value.size === 0) return null;
  const types = new Set();
  categoryFilters.forEach(cat => {
    if (activeCategories.value.has(cat.key)) {
      cat.types.forEach(t => types.add(t));
    }
  });
  return types;
});

const displayedNotifications = computed(() => {
  let list = notifications.value;
  if (filterRead.value === 'unread') {
    list = list.filter(n => !n.read_at);
  }
  if (activeCategoryTypes.value) {
    list = list.filter(n => activeCategoryTypes.value.has(n.type || n.data?.type));
  }
  return list;
});

const hasReadNotifications = computed(() => notifications.value.some(n => !!n.read_at));

const loadPage = async (page) => {
  try {
    const response = await fetchAll(page);
    notifications.value = response.data;
    pagination.value = response.meta;
    applyStagger();
  } catch (error) {
    console.error('Erreur lors du chargement de la page :', error);
  }
};

const handleNotificationClick = (notification) => {
  selectedNotification.value = notification;
  showDetailModal.value = true;
};

const closeDetailModal = () => {
  showDetailModal.value = false;
  selectedNotification.value = null;
};

const handleMarkAsRead = async (notificationId) => {
  try {
    await markAsRead(notificationId);
    await loadStatistics();
  } catch (error) {
    console.error('Erreur lors du marquage comme lu :', error);
  }
};

const handleMarkAllAsRead = async () => {
  try {
    await markAllAsRead();
    await loadStatistics();
    await loadPage(pagination.value.current_page);
  } catch (error) {
    console.error('Erreur lors du marquage global :', error);
  }
};

const handleDelete = async (notificationId) => {
  try {
    await deleteNotification(notificationId);
    await loadStatistics();
    if (displayedNotifications.value.length === 0 && pagination.value.current_page > 1) {
      await loadPage(pagination.value.current_page - 1);
    } else {
      await loadPage(pagination.value.current_page);
    }
  } catch (error) {
    console.error('Erreur lors de la suppression :', error);
  }
};

const handleDeleteAllRead = async () => {
  if (!confirm('Supprimer toutes les notifications lues ?')) return;
  try {
    await deleteAllRead();
    await loadStatistics();
    await loadPage(1);
  } catch (error) {
    console.error('Erreur lors de la suppression des lues :', error);
  }
};

const loadStatistics = async () => {
  try {
    statistics.value = await fetchStatistics();
  } catch (error) {
    console.error('Erreur lors du chargement des statistiques :', error);
  }
};

const handleOpenResultatModal = async (resultatId) => {
  try {
    const { data } = await api.get(`/tache-resultats/${resultatId}`);
    selectedResultat.value = data.data ?? data;
    showResultatModal.value = true;
  } catch (error) {
    console.error('Erreur lors du chargement du résultat :', error);
  }
};

const closeResultatModal = () => {
  showResultatModal.value = false;
  selectedResultat.value = null;
};

onMounted(async () => {
  initialLoading.value = true;
  try {
    await Promise.all([loadPage(1), loadStatistics()]);
  } catch (error) {
    console.error('Erreur lors du chargement initial :', error);
  } finally {
    initialLoading.value = false;
  }
});
</script>
