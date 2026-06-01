<!-- resources\js\pages\Notifications.vue -->
<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="'Notifications'" />
    <div class="mx-auto max-w-screen-2xl">
      <!-- Chargement initial -->
      <div v-if="initialLoading" class="flex flex-col items-center justify-center py-20">
        <svg class="animate-spin h-14 w-14 text-brand-500 dark:text-brand-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
        <p class="mt-6 text-lg font-medium text-gray-700 dark:text-gray-300">{{ $t('notifications.loading') }}</p>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">{{ $t('notifications.please_wait') }}</p>
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
              {{ $t('notifications.statistics') }}
            </button>
            <button v-if="unreadCount > 0" @click="handleMarkAllAsRead"
              class="px-3 py-2 rounded-3 text-sm font-medium bg-success-500 hover:bg-success-600 text-white transition-colors flex items-center gap-1.5">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m1.5 12.75 6 6 9-13.5m3 0-6 6-3-3"/></svg>
              {{ $t('notifications.mark_all_read') }}
            </button>
            <button v-if="hasReadNotifications" @click="handleDeleteAllRead"
              class="px-3 py-2 rounded-3 text-sm font-medium bg-error-50 hover:bg-error-100 text-error-700 dark:bg-error-500/10 dark:hover:bg-error-500/20 dark:text-error-400 border border-error-200 dark:border-error-700 transition-colors flex items-center gap-1.5">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
              {{ $t('notifications.delete_read') }}
            </button>
            <button @click="$router.push('/notification-preferences')"
              class="px-3 py-2 rounded-3 text-sm font-medium bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors flex items-center gap-1.5">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>
              {{ $t('notifications.preferences') }}
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
              {{ $t('notifications.all') }}
            </button>
            <button @click="filterRead = 'unread'"
              :class="filterRead === 'unread' ? 'bg-brand-500 text-white' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800'"
              class="px-3 py-2 text-sm font-medium border-l border-gray-200 dark:border-gray-700 transition-colors">
              {{ $t('notifications.unread') }}
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
            <span class="w-3 h-3 flex items-center justify-center" v-html="getCategoryIcon(cat.key)"></span>
            {{ cat.label }}
          </button>
        </div>

        <!-- Liste des notifications -->
        <div class="rounded-3 border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
          <div v-if="loading" class="flex items-center justify-center py-16">
            <svg class="animate-spin h-8 w-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          </div>

          <div v-else-if="displayedNotifications.length === 0" class="flex flex-col items-center justify-center py-16 gap-3">
            <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-gray-400 dark:text-gray-600"><path stroke-linecap="round" stroke-linejoin="round" d="M9.143 17.082a24.248 24.248 0 0 0 3.844.148m-3.844-.148a23.856 23.856 0 0 1-5.455-1.31 8.964 8.964 0 0 0 2.3-5.542m3.155 6.852a3 3 0 0 0 5.667 1.97m1.965-2.277L21 21m-4.225-4.225a23.81 23.81 0 0 0 .356-1.899M6.228 6.228 3 3m3.228 3.228a23.793 23.793 0 0 0-.021.614 8.985 8.985 0 0 0 2.14 5.755m9.592-9.592a6 6 0 0 0-7.752 5.929m9.447 2.021a6 6 0 0 0 .3-1.95"/></svg>
            </div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
              {{ filterRead === 'unread' ? $t('notifications.empty.no_unread') : $t('notifications.none') }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 text-center max-w-xs">
              {{ filterRead === 'unread'
                ? $t('notifications.empty.all_read')
                : activeCategories.size > 0
                  ? $t('notifications.empty.no_category')
                  : $t('notifications.empty.none_received') }}
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
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                {{ $t('common.previous') }}
              </button>
              <button @click="loadPage(pagination.current_page + 1)"
                :disabled="pagination.current_page === pagination.last_page"
                class="px-3 py-1.5 rounded-3 text-sm font-medium border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 transition-colors disabled:opacity-50 disabled:cursor-not-allowed hover:enabled:bg-gray-50 dark:hover:enabled:bg-gray-800 flex items-center gap-1.5">
                {{ $t('common.next') }}
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
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
import { useI18n } from 'vue-i18n';
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
const { t } = useI18n();
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
const categoryFilters = computed(() => [
  {
    key: 'resultats',
    label: t('notifications.categories.results'),
    types: ['resultat_soumis', 'resultat_attente_n2', 'resultat_valide_n1', 'resultat_valide_n2', 'resultat_rejete', 'resultat_validation_complete', 'resultat_rejete_n2_info', 'validation_n1_confirmee', 'validation_n2_confirmee', 'rejet_confirme'],
    activeClass: 'bg-brand-500 text-white',
  },
  {
    key: 'taches',
    label: t('notifications.categories.tasks'),
    types: ['task_assigned', 'task_updated', 'task_completed', 'task_due_soon', 'deadline_approaching'],
    activeClass: 'bg-purple-500 text-white',
  },
  {
    key: 'fichiers',
    label: t('notifications.categories.files'),
    types: ['task_file_added', 'task_file_removed', 'task_link_added', 'task_link_removed'],
    activeClass: 'bg-success-500 text-white',
  },
  {
    key: 'invitations',
    label: t('notifications.categories.invitations'),
    types: ['workspace_invitation', 'projet_invitation'],
    activeClass: 'bg-warning-500 text-white',
  },
  {
    key: 'documents',
    label: t('notifications.categories.documents'),
    types: ['document_uploaded', 'document_deleted', 'document_shared'],
    activeClass: 'bg-warning-400 text-white',
  },
]);

const getCategoryIcon = (key) => {
  const icons = {
    resultats: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>',
    taches: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>',
    fichiers: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>',
    invitations: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>',
    documents: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9z"/></svg>',
  };
  return icons[key] ?? '';
};

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
