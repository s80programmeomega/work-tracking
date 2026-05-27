<!-- resources\js\components\layout\header\NotificationMenu.vue -->
<template>
  <div class="relative" ref="dropdownRef">
    <button
      dusk="notification-bell"
      class="relative flex items-center justify-center text-gray-500 transition-colors bg-white border border-gray-200 rounded-full hover:text-dark-900 h-11 w-11 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
      @click="toggleDropdown"
    >
      <span
        v-if="unreadCount > 0"
        dusk="notification-badge"
        class="absolute -right-1 -top-1 z-10 flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold text-white bg-red-500 rounded-full"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
 
      <svg
        class="fill-current"
        width="20"
        height="20"
        viewBox="0 0 20 20"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <path
          fill-rule="evenodd"
          clip-rule="evenodd"
          d="M10.75 2.29248C10.75 1.87827 10.4143 1.54248 10 1.54248C9.58583 1.54248 9.25004 1.87827 9.25004 2.29248V2.83613C6.08266 3.20733 3.62504 5.9004 3.62504 9.16748V14.4591H3.33337C2.91916 14.4591 2.58337 14.7949 2.58337 15.2091C2.58337 15.6234 2.91916 15.9591 3.33337 15.9591H4.37504H15.625H16.6667C17.0809 15.9591 17.4167 15.6234 17.4167 15.2091C17.4167 14.7949 17.0809 14.4591 16.6667 14.4591H16.375V9.16748C16.375 5.9004 13.9174 3.20733 10.75 2.83613V2.29248ZM14.875 14.4591V9.16748C14.875 6.47509 12.6924 4.29248 10 4.29248C7.30765 4.29248 5.12504 6.47509 5.12504 9.16748V14.4591H14.875ZM8.00004 17.7085C8.00004 18.1228 8.33583 18.4585 8.75004 18.4585H11.25C11.6643 18.4585 12 18.1228 12 17.7085C12 17.2943 11.6643 16.9585 11.25 16.9585H8.75004C8.33583 16.9585 8.00004 17.2943 8.00004 17.7085Z"
          fill=""
        />
      </svg>
    </button>

    <div
      v-if="dropdownOpen"
      class="absolute -right-[240px] mt-[17px] flex h-[480px] w-[350px] flex-col rounded-3 border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-dark sm:w-[361px] lg:right-0"
    >
      <div
        class="flex items-center justify-between pb-3 mb-3 border-b border-gray-100 dark:border-gray-800"
      >
        <h5 class="text-lg font-semibold text-gray-800 dark:text-white/90">
          Notifications
          <span v-if="unreadCount > 0" class="ml-2 text-sm font-medium text-blue-600 dark:text-blue-400">
            ({{ unreadCount }})
          </span>
        </h5>
 
        <div class="flex items-center gap-2">
          <button
            v-if="unreadCount > 0"
            @click="handleMarkAllAsRead"
            class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
            title="Tout marquer comme lu"
          >
            <i class="fas fa-check-double"></i>
          </button>
          <button @click="closeDropdown" class="text-gray-500 dark:text-gray-400">
            <svg
              class="fill-current"
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                fill-rule="evenodd"
                clip-rule="evenodd"
                d="M6.21967 7.28131C5.92678 6.98841 5.92678 6.51354 6.21967 6.22065C6.51256 5.92775 6.98744 5.92775 7.28033 6.22065L11.999 10.9393L16.7176 6.22078C17.0105 5.92789 17.4854 5.92788 17.7782 6.22078C18.0711 6.51367 18.0711 6.98855 17.7782 7.28144L13.0597 12L17.7782 16.7186C18.0711 17.0115 18.0711 17.4863 17.7782 17.7792C17.4854 18.0721 17.0105 18.0721 16.7176 17.7792L11.999 13.0607L7.28033 17.7794C6.98744 18.0722 6.51256 18.0722 6.21967 17.7794C5.92678 17.4865 5.92678 17.0116 6.21967 16.7187L10.9384 12L6.21967 7.28131Z"
                fill=""
              />
            </svg>
          </button>
        </div>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-8">
        <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
      </div>

      <div v-else-if="displayedNotifications.length === 0" class="flex flex-col items-center justify-center py-8">
        <i class="fas fa-bell-slash text-4xl text-gray-400 mb-3"></i>
        <p class="text-gray-600 dark:text-gray-400">Aucune notification</p>
      </div>

      <div v-else class="flex flex-col h-auto overflow-y-auto custom-scrollbar">
        <NotificationItem
          v-for="notification in displayedNotifications"
          :key="notification.id"
          :notification="notification"
          @click="handleNotificationClick"
          @mark-read="handleMarkAsRead"
          @delete="handleDelete"
          @open-resultat-modal="handleOpenResultatModal"
        />
      </div>

      <router-link
        to="/notifications"
        class="mt-3 flex justify-center rounded-3 border border-gray-300 bg-white p-3 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
        @click="closeDropdown"
      >
        Voir toutes les notifications 
      </router-link>
    </div>

    <!-- Notification Detail Modal -->
    <NotificationDetailModal
      :is-open="showDetailModal"
      :notification="selectedNotification"
      @close="closeDetailModal"
      @mark-read="handleMarkAsRead"
      @delete="handleDelete"
    />

    <!-- Resultat Detail Modal -->
    <ResultatDetailModal
      v-if="showResultatModal && selectedResultat"
      :resultat="selectedResultat"
      @close="closeResultatModal"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useNotifications } from '@/composables/useNotifications'
import { useAuthStore } from '@/stores/authStore'
import NotificationItem from './NotificationItem.vue'
import NotificationDetailModal from './NotificationDetailModal.vue'
import ResultatDetailModal from '@/components/taches/resultats/ResultatDetailModal.vue'
import { useRealtimeRefresh } from '@/composables/useRealtimeRefresh'
import api from '@/api/axios'

const router = useRouter()
const authStore = useAuthStore()
const dropdownOpen = ref(false)
const dropdownRef = ref(null)
const maxDisplayed = 5
const showDetailModal = ref(false)
const selectedNotification = ref(null)
const showResultatModal = ref(false)
const selectedResultat = ref(null)

const {
  notifications,
  unreadCount,
  loading,
  fetchUnread,
  markAsRead,
  markAllAsRead,
  deleteNotification,
} = useNotifications()

const notifying = computed(() => unreadCount.value > 0)
const displayedNotifications = computed(() => notifications.value.slice(0, maxDisplayed))

const toggleDropdown = async () => {
  dropdownOpen.value = !dropdownOpen.value

  if (dropdownOpen.value && notifications.value.length === 0) {
    safeFetchUnread()
  }
}

const closeDropdown = () => {
  dropdownOpen.value = false
}

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    closeDropdown()
  }
}

// const handleNotificationClick = (notification) => {
//   selectedNotification.value = notification
//   showDetailModal.value = true
//   closeDropdown()
// }

const handleNotificationClick = async (notification) => {
  selectedNotification.value = notification
  showDetailModal.value = true
  closeDropdown()

  // Marquer comme lu directement
  if (!notification.read_at) {
    try {
      await markAsRead(notification.id)
    } catch (error) {
      console.error('Erreur lors du marquage comme lu :', error)
    }
  }
}


const closeDetailModal = () => {
  showDetailModal.value = false
  selectedNotification.value = null
}

const handleOpenResultatModal = async (resultatId) => {
  closeDropdown()
  try {
    const { data } = await api.get(`/tache-resultats/${resultatId}`)
    selectedResultat.value = data.data ?? data
    showResultatModal.value = true
  } catch (error) {
    console.error('Erreur lors du chargement du résultat :', error)
  }
}

const closeResultatModal = () => {
  showResultatModal.value = false
  selectedResultat.value = null
}

const handleMarkAsRead = async (notificationId) => {
  try {
    await markAsRead(notificationId)
  } catch (error) {
    console.error('Error marking as read:', error)
  }
}

const handleMarkAllAsRead = async () => {
  try {
    await markAllAsRead()
  } catch (error) {
    console.error('Error marking all as read:', error)
  }
}

const handleDelete = async (notificationId) => {
  try {
    await deleteNotification(notificationId)
  } catch (error) {
    console.error('Error deleting notification:', error)
  }
}

// Évite de spammer l'API si l'utilisateur n'est pas connecté: sinon
// chaque appel renvoie 401 et déclenche un rejet de promesse non géré.
// Les erreurs réseau transitoires sont avalées car ce polling est best-effort.
const safeFetchUnread = () => {
  if (!authStore.isAuthenticated) return
  fetchUnread().catch(() => {})
}

// Refresh on any domain event; keep a 60 s fallback poll for silent updates.
useRealtimeRefresh({
  onResultatChanged: () => safeFetchUnread(),
  onPendingChanged: () => safeFetchUnread(),
})

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  safeFetchUnread()

  const interval = setInterval(safeFetchUnread, 60000)

  onUnmounted(() => {
    clearInterval(interval)
  })
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>