<!-- resources\js\pages\Notifications.vue -->
<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="'Notifications'" />
    <div class="mx-auto max-w-screen-2xl">
      <!-- Global Loading State -->
      <div v-if="initialLoading" class="flex flex-col items-center justify-center py-20">
        <div class="relative">
          <i class="fas fa-spinner fa-spin text-6xl text-blue-600 dark:text-blue-400"></i>
        </div>
        <p class="mt-6 text-lg font-medium text-gray-700 dark:text-gray-300">Chargement des notifications...</p>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">Veuillez patienter</p>
      </div>

      <!-- Page Content -->
      <div v-else>
        <!-- Page Header -->
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">
            Notifications
          </h2>

          <div class="flex items-center gap-2">
            <button @click="filterType = 'all'"
              :class="filterType === 'all' ? 'bg-brand-500 text-white border-brand-500' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800'"
              class="px-4 py-2 rounded-3 text-sm font-medium border transition-colors">
              Toutes ({{ statistics.total || 0 }})
            </button>
            <button @click="filterType = 'unread'"
              :class="filterType === 'unread' ? 'bg-brand-500 text-white border-brand-500' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800'"
              class="px-4 py-2 rounded-3 text-sm font-medium border transition-colors">
              Non lues ({{ unreadCount }})
            </button>
            <button v-if="unreadCount > 0" @click="handleMarkAllAsRead"
              class="px-4 py-2 rounded-3 text-sm font-medium bg-success-500 hover:bg-success-600 text-white transition-colors flex items-center gap-2">
              <i class="fas fa-check-double"></i>
              Tout marquer comme lu
            </button>
          </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
          <div class="rounded-3 border border-brand-200 dark:border-brand-800/50 bg-white dark:bg-gray-900 p-5">
            <div class="flex items-center gap-3">
              <div class="flex h-11 w-11 items-center justify-center rounded-3 bg-brand-500">
                <i class="fas fa-bell text-white text-lg"></i>
              </div>
              <div>
                <p class="text-xs font-medium text-brand-600 dark:text-brand-400">Total</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics.total || 0 }}</p>
              </div>
            </div>
          </div>

          <div class="rounded-3 border border-warning-200 dark:border-warning-800/50 bg-white dark:bg-gray-900 p-5">
            <div class="flex items-center gap-3">
              <div class="flex h-11 w-11 items-center justify-center rounded-3 bg-warning-500">
                <i class="fas fa-envelope text-white text-lg"></i>
              </div>
              <div>
                <p class="text-xs font-medium text-warning-600 dark:text-warning-400">Non lues</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics.unread || 0 }}</p>
              </div>
            </div>
          </div>

          <div class="rounded-3 border border-success-200 dark:border-success-800/50 bg-white dark:bg-gray-900 p-5">
            <div class="flex items-center gap-3">
              <div class="flex h-11 w-11 items-center justify-center rounded-3 bg-success-500">
                <i class="fas fa-check-circle text-white text-lg"></i>
              </div>
              <div>
                <p class="text-xs font-medium text-success-600 dark:text-success-400">Lues aujourd'hui</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics.read_today || 0 }}</p>
              </div>
            </div>
          </div>

          <div
            class="rounded-3 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            @click="$router.push('/notification-preferences')">
            <div class="flex items-center gap-3">
              <div class="flex h-11 w-11 items-center justify-center rounded-3 bg-gray-500">
                <i class="fas fa-sliders-h text-white text-lg"></i>
              </div>
              <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Personnaliser</p>
                <p class="text-base font-semibold text-gray-900 dark:text-white">Préférences →</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Notifications List -->
        <div class="rounded-3 border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
          <div v-if="loading" class="flex items-center justify-center py-16">
            <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
          </div>

          <div v-else-if="displayedNotifications.length === 0" class="flex flex-col items-center justify-center py-16 gap-3">
            <i class="fas fa-bell-slash text-5xl text-gray-300 dark:text-gray-700"></i>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
              {{ filterType === 'unread' ? 'Aucune notification non lue' : 'Aucune notification' }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ filterType === 'unread' ? 'Toutes vos notifications ont été lues' : 'Vous n\'avez reçu aucune notification' }}
            </p>
          </div>

          <div v-else ref="listRef">
            <NotificationItem v-for="notification in displayedNotifications" :key="notification.id"
              :notification="notification" class="stagger-item" @click="handleNotificationClick" @mark-read="handleMarkAsRead"
              @delete="handleDelete" @open-resultat-modal="handleOpenResultatModal" />
          </div>

          <!-- Pagination -->
          <div v-if="pagination.total > pagination.per_page" class="border-t border-gray-200 dark:border-gray-800 p-4 flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Page {{ pagination.current_page }} / {{ pagination.last_page }}
              <span class="ml-1">({{ pagination.total }})</span>
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

        <!-- Notification Detail Modal -->
        <NotificationDetailModal :is-open="showDetailModal" :notification="selectedNotification"
          @close="closeDetailModal" @mark-read="handleMarkAsRead" @delete="handleDelete" />

        <!-- Resultat Detail Modal -->
        <ResultatDetailModal v-if="showResultatModal && selectedResultat" :resultat="selectedResultat"
          @close="closeResultatModal" />
      </div>
      <!-- End Page Content -->
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
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import api from '@/api/axios';

const router = useRouter();
const { staggerRef: listRef, applyStagger } = useStagger(35);
const filterType = ref('all');
const showDetailModal = ref(false);
const selectedNotification = ref(null);
const showResultatModal = ref(false);
const selectedResultat = ref(null);
const initialLoading = ref(true);

const {
  notifications,
  unreadCount,
  loading,
  fetchUnread,
  fetchAll,
  markAsRead,
  markAllAsRead,
  deleteNotification,
  fetchStatistics,
} = useNotifications();

const statistics = ref({
  total: 0,
  unread: 0,
  read_today: 0,
  by_type: {},
});

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
});

const displayedNotifications = computed(() => {
  if (filterType.value === 'unread') {
    return notifications.value.filter(n => !n.read_at);
  }
  return notifications.value;
});

const loadPage = async (page) => {
  try {
    const response = await fetchAll(page);
    notifications.value = response.data;
    pagination.value = response.meta;
    applyStagger();
  } catch (error) {
    console.error('Error loading page:', error);
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
    console.error('Error marking as read:', error);
  }
};

const handleMarkAllAsRead = async () => {
  try {
    await markAllAsRead();
    await loadStatistics();
    await loadPage(pagination.value.current_page);
  } catch (error) {
    console.error('Error marking all as read:', error);
  }
};

const handleDelete = async (notificationId) => {
  try {
    await deleteNotification(notificationId);
    await loadStatistics();

    // Reload current page if we deleted the last item
    if (displayedNotifications.value.length === 0 && pagination.value.current_page > 1) {
      await loadPage(pagination.value.current_page - 1);
    } else {
      await loadPage(pagination.value.current_page);
    }
  } catch (error) {
    console.error('Error deleting notification:', error);
  }
};

const loadStatistics = async () => {
  try {
    statistics.value = await fetchStatistics();
  } catch (error) {
    console.error('Error loading statistics:', error);
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
    await Promise.all([
      loadPage(1),
      loadStatistics(),
    ]);
  } catch (error) {
    console.error('Error loading initial data:', error);
  } finally {
    initialLoading.value = false;
  }
});
</script>
