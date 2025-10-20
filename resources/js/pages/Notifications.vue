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
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
          Notifications
        </h2>

        <div class="flex items-center gap-3">
          <!-- Filter buttons -->
          <button
            @click="filterType = 'all'"
            :class="filterType === 'all' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
            class="px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 dark:border-gray-700 hover:bg-blue-50 dark:hover:bg-gray-700"
          >
            Toutes ({{ statistics.total || 0 }})
          </button>
          <button
            @click="filterType = 'unread'"
            :class="filterType === 'unread' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
            class="px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 dark:border-gray-700 hover:bg-blue-50 dark:hover:bg-gray-700"
          >
            Non lues ({{ unreadCount }})
          </button>

          <!-- Actions -->
          <button
            v-if="unreadCount > 0"
            @click="handleMarkAllAsRead"
            class="px-4 py-2 rounded-lg text-sm font-medium bg-green-600 text-white hover:bg-green-700"
          >
            <i class="fas fa-check-double mr-2"></i>
            Tout marquer comme lu
          </button>
        </div>
      </div>

      <!-- Statistics Cards with Gradient Backgrounds -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-4 md:gap-6 mb-6">
      <!-- Total Card -->
      <div class="rounded-xl border border-blue-200 dark:border-blue-500/30 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950 dark:to-blue-900 p-6 shadow-lg hover:shadow-xl transition-all duration-300">
        <div class="flex items-center gap-3">
          <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-blue-500 dark:bg-blue-600 shadow-lg">
            <i class="fas fa-bell text-2xl text-white"></i>
          </div>
          <div>
            <p class="text-sm font-medium text-blue-700 dark:text-blue-300">Total</p>
            <p class="text-3xl font-bold text-blue-900 dark:text-blue-100">{{ statistics.total || 0 }}</p>
          </div>
        </div>
      </div>

      <!-- Unread Card -->
      <div class="rounded-xl border border-orange-200 dark:border-orange-500/30 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-950 dark:to-orange-900 p-6 shadow-lg hover:shadow-xl transition-all duration-300">
        <div class="flex items-center gap-3">
          <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-orange-500 dark:bg-orange-600 shadow-lg">
            <i class="fas fa-envelope text-2xl text-white"></i>
          </div>
          <div>
            <p class="text-sm font-medium text-orange-700 dark:text-orange-300">Non lues</p>
            <p class="text-3xl font-bold text-orange-900 dark:text-orange-100">{{ statistics.unread || 0 }}</p>
          </div>
        </div>
      </div>

      <!-- Read Today Card -->
      <div class="rounded-xl border border-green-200 dark:border-green-500/30 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950 dark:to-green-900 p-6 shadow-lg hover:shadow-xl transition-all duration-300">
        <div class="flex items-center gap-3">
          <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-green-500 dark:bg-green-600 shadow-lg">
            <i class="fas fa-check-circle text-2xl text-white"></i>
          </div>
          <div>
            <p class="text-sm font-medium text-green-700 dark:text-green-300">Lues aujourd'hui</p>
            <p class="text-3xl font-bold text-green-900 dark:text-green-100">{{ statistics.read_today || 0 }}</p>
          </div>
        </div>
      </div>

      <!-- Preferences Card -->
      <div class="rounded-xl border border-purple-200 dark:border-purple-500/30 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950 dark:to-purple-900 p-6 shadow-lg hover:shadow-xl transition-all duration-300 cursor-pointer group"
           @click="$router.push('/notification-preferences')">
        <div class="flex items-center gap-3">
          <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-purple-500 dark:bg-purple-600 shadow-lg group-hover:scale-110 transition-transform">
            <i class="fas fa-cog text-2xl text-white"></i>
          </div>
          <div>
            <p class="text-sm font-medium text-purple-700 dark:text-purple-300">Personnaliser</p>
            <p class="text-lg font-bold text-purple-900 dark:text-purple-100 group-hover:underline">Préférences →</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Notifications List -->
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-20">
        <i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i>
      </div>

      <!-- Empty State -->
      <div v-else-if="displayedNotifications.length === 0" class="flex flex-col items-center justify-center py-20">
        <i class="fas fa-bell-slash text-6xl text-gray-300 dark:text-gray-700 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
          {{ filterType === 'unread' ? 'Aucune notification non lue' : 'Aucune notification' }}
        </h3>
        <p class="text-gray-600 dark:text-gray-400">
          {{ filterType === 'unread' ? 'Toutes vos notifications ont été lues' : 'Vous n\'avez reçu aucune notification' }}
        </p>
      </div>

      <!-- Notifications -->
      <div v-else class="divide-y divide-gray-200 dark:divide-gray-800">
        <NotificationItem
          v-for="notification in displayedNotifications"
          :key="notification.id"
          :notification="notification"
          @click="handleNotificationClick"
          @mark-read="handleMarkAsRead"
          @delete="handleDelete"
          class="group"
        />
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > pagination.per_page" class="border-t border-gray-200 dark:border-gray-800 p-4">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-600 dark:text-gray-400">
            Page {{ pagination.current_page }} sur {{ pagination.last_page }}
            <span class="ml-2">({{ pagination.total }} au total)</span>
          </div>

          <div class="flex gap-2">
            <button
              @click="loadPage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              :class="pagination.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-800'"
              class="px-4 py-2 rounded-lg text-sm font-medium bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-700"
            >
              <i class="fas fa-chevron-left"></i>
              Précédent
            </button>
            <button
              @click="loadPage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              :class="pagination.current_page === pagination.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-800'"
              class="px-4 py-2 rounded-lg text-sm font-medium bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-700"
            >
              Suivant
              <i class="fas fa-chevron-right ml-2"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Notification Detail Modal -->
    <NotificationDetailModal
      :is-open="showDetailModal"
      :notification="selectedNotification"
      @close="closeDetailModal"
      @mark-read="handleMarkAsRead"
      @delete="handleDelete"
    />
    </div>
    <!-- End Page Content -->
  </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useNotifications } from '@/composables/useNotifications';
import NotificationItem from '@/components/layout/header/NotificationItem.vue';
import NotificationDetailModal from '@/components/layout/header/NotificationDetailModal.vue';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';

const router = useRouter();
const filterType = ref('all');
const showDetailModal = ref(false);
const selectedNotification = ref(null);
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
