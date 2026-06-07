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

    <transition name="fade-pop">
    <div
      v-if="dropdownOpen"
      class="absolute -right-[240px] mt-[17px] flex h-[480px] w-[350px] flex-col rounded-3 border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900 sm:w-90.25 lg:right-0"
    >
      <div class="flex items-center justify-between pb-3 mb-3 border-b border-gray-200 dark:border-gray-800">
        <h5 class="text-base font-semibold text-gray-900 dark:text-white">
          {{ $t('notifications.heading') }}
          <span v-if="unreadCount > 0" class="ml-1.5 text-sm font-medium text-brand-600 dark:text-brand-400">
            ({{ unreadCount }})
          </span>
        </h5>

        <div class="flex items-center gap-2">
          <button
            v-if="unreadCount > 0"
            @click="handleMarkAllAsRead"
            class="p-1.5 rounded-3 text-success-600 hover:bg-success-50 dark:text-success-400 dark:hover:bg-success-500/10 transition-colors"
            :title="$t('notifications.mark_read_title')"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m1.5 12.75 6 6 9-13.5m3 0-6 6-3-3"/></svg>
          </button>
          <button
            @click="closeDropdown"
            class="p-1.5 rounded-3 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition-colors"
          >
            <svg class="fill-current w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" clip-rule="evenodd"
                d="M6.21967 7.28131C5.92678 6.98841 5.92678 6.51354 6.21967 6.22065C6.51256 5.92775 6.98744 5.92775 7.28033 6.22065L11.999 10.9393L16.7176 6.22078C17.0105 5.92789 17.4854 5.92788 17.7782 6.22078C18.0711 6.51367 18.0711 6.98855 17.7782 7.28144L13.0597 12L17.7782 16.7186C18.0711 17.0115 18.0711 17.4863 17.7782 17.7792C17.4854 18.0721 17.0105 18.0721 16.7176 17.7792L11.999 13.0607L7.28033 17.7794C6.98744 18.0722 6.51256 18.0722 6.21967 17.7794C5.92678 17.4865 5.92678 17.0116 6.21967 16.7187L10.9384 12L6.21967 7.28131Z"
                fill="" />
            </svg>
          </button>
        </div>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-8">
        <svg class="animate-spin h-6 w-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
      </div>

      <div v-else-if="displayedNotifications.length === 0" class="flex flex-col items-center justify-center py-8 gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-gray-300 dark:text-gray-700"><path stroke-linecap="round" stroke-linejoin="round" d="M9.143 17.082a24.248 24.248 0 0 0 3.844.148m-3.844-.148a23.856 23.856 0 0 1-5.455-1.31 8.964 8.964 0 0 0 2.3-5.542m3.155 6.852a3 3 0 0 0 5.667 1.97m1.965-2.277L21 21m-4.225-4.225a23.81 23.81 0 0 0 .356-1.899M6.228 6.228 3 3m3.228 3.228a23.793 23.793 0 0 0-.021.614 8.985 8.985 0 0 0 2.14 5.755m9.592-9.592a6 6 0 0 0-7.752 5.929m9.447 2.021a6 6 0 0 0 .3-1.95"/></svg>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('notifications.none') }}</p>
      </div>

      <div v-else class="flex flex-col overflow-y-auto">
        <NotificationItem
          v-for="notification in displayedNotifications"
          :key="notification.id"
          :notification="notification"
          @click="handleNotificationClick"
          @mark-read="handleMarkAsRead"
          @delete="handleDelete"
        />
      </div>

      <router-link
        to="/notifications"
        class="mt-3 flex justify-center rounded-3 border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white transition-colors"
        @click="closeDropdown"
      >
        {{ $t('notifications.view_all') }}
      </router-link>
    </div>
    </transition>

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
import { useI18n } from 'vue-i18n'
import { useNotifications } from '@/composables/useNotifications'
import { useAuthStore } from '@/stores/authStore'
import NotificationItem from './NotificationItem.vue'
import NotificationDetailModal from './NotificationDetailModal.vue'
import ResultatDetailModal from '@/components/taches/resultats/ResultatDetailModal.vue'
import { useRealtimeRefresh } from '@/composables/useRealtimeRefresh'
import api from '@/api/axios'

const router = useRouter()
const authStore = useAuthStore()
const { t } = useI18n()
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