<!-- resources/js/components/notifications/NotificationCenter.vue -->
<template>
  <Menu as="div" class="relative inline-block text-left">
    <MenuButton class="relative p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
      </svg>
      
      <!-- Badge de compteur -->
      <span 
        v-if="unreadCount > 0"
        class="absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </MenuButton>

    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <MenuItems class="absolute right-0 mt-2 w-96 origin-top-right rounded-lg bg-white dark:bg-gray-800 shadow-xl ring-1 ring-black ring-opacity-5 focus:outline-none z-50 max-h-[80vh] overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Notifications
          </h3>
          <button
            v-if="unreadCount > 0"
            @click="markAllAsRead"
            class="text-sm text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300"
          >
            Tout marquer comme lu
          </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-500"></div>
        </div>

        <!-- Notifications List -->
        <div v-else-if="notifications.length > 0" class="overflow-y-auto flex-1">
          <MenuItem
            v-for="notification in notifications"
            :key="notification.id"
            v-slot="{ active }"
            @click="handleNotificationClick(notification)"
          >
            <div
              :class="[
                'px-4 py-3 border-b border-gray-100 dark:border-gray-700 cursor-pointer transition-colors',
                active ? 'bg-gray-50 dark:bg-gray-700' : '',
                !notification.read_at ? 'bg-blue-50 dark:bg-blue-900/10' : ''
              ]"
            >
              <div class="flex items-start gap-3">
                <!-- Icône selon le type -->
                <div 
                  :class="[
                    'flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center',
                    getNotificationStyle(notification.data.type).bg
                  ]"
                >
                  <svg 
                    class="w-5 h-5" 
                    :class="getNotificationStyle(notification.data.type).icon"
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                  >
                    <path 
                      stroke-linecap="round" 
                      stroke-linejoin="round" 
                      stroke-width="2" 
                      :d="getNotificationIcon(notification.data.type)" 
                    />
                  </svg>
                </div>

                <!-- Contenu -->
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ notification.data.message }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ formatDate(notification.created_at) }}
                  </p>
                </div>

                <!-- Badge non lu -->
                <div v-if="!notification.read_at" class="flex-shrink-0">
                  <span class="inline-block w-2 h-2 bg-blue-600 rounded-full"></span>
                </div>
              </div>
            </div>
          </MenuItem>
        </div>

        <!-- Empty State -->
        <div v-else class="flex flex-col items-center justify-center py-12 px-4">
          <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
          <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">Aucune notification</p>
          <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">Vous êtes à jour !</p>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
          <router-link
            to="/notifications"
            class="block text-center text-sm text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 font-medium"
          >
            Voir toutes les notifications
          </router-link>
        </div>
      </MenuItems>
    </transition>
  </Menu>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import api from '@/api/axios'

const router = useRouter()

const notifications = ref([])
const loading = ref(false)

const unreadCount = computed(() => {
  return notifications.value.filter(n => !n.read_at).length
})

// Charger les notifications
const fetchNotifications = async () => {
  try {
    loading.value = true
    const { data } = await api.get('/notifications', {
      params: { per_page: 10 }
    })
    notifications.value = data.data || []
  } catch (error) {
    console.error('Erreur chargement notifications:', error)
  } finally {
    loading.value = false
  }
}

// Marquer toutes comme lues
const markAllAsRead = async () => {
  try {
    await api.post('/notifications/mark-all-read')
    notifications.value = notifications.value.map(n => ({ ...n, read_at: new Date() }))
  } catch (error) {
    console.error('Erreur marquage notifications:', error)
  }
}

// Gérer le clic sur une notification
const handleNotificationClick = async (notification) => {
  // Marquer comme lue
  if (!notification.read_at) {
    try {
      await api.post(`/notifications/${notification.id}/mark-read`)
      notification.read_at = new Date()
    } catch (error) {
      console.error('Erreur marquage notification:', error)
    }
  }

  // Rediriger selon le type
  if (notification.data.url) {
    router.push(notification.data.url)
  }
}

// Formater la date
const formatDate = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now - date
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMins / 60)
  const diffDays = Math.floor(diffHours / 24)

  if (diffMins < 1) return 'À l\'instant'
  if (diffMins < 60) return `Il y a ${diffMins} min`
  if (diffHours < 24) return `Il y a ${diffHours}h`
  if (diffDays < 7) return `Il y a ${diffDays}j`
  
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short'
  })
}

// Obtenir l'icône selon le type
const getNotificationIcon = (type) => {
  const icons = {
    'activite_member_added': 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
    'activite_member_removed': 'M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6',
    'activite_member_permissions_updated': 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
    'default': 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
  }
  return icons[type] || icons.default
}

// Obtenir le style selon le type
const getNotificationStyle = (type) => {
  const styles = {
    'activite_member_added': {
      bg: 'bg-green-100 dark:bg-green-900/30',
      icon: 'text-green-600 dark:text-green-400'
    },
    'activite_member_removed': {
      bg: 'bg-red-100 dark:bg-red-900/30',
      icon: 'text-red-600 dark:text-red-400'
    },
    'activite_member_permissions_updated': {
      bg: 'bg-blue-100 dark:bg-blue-900/30',
      icon: 'text-blue-600 dark:text-blue-400'
    },
    'default': {
      bg: 'bg-gray-100 dark:bg-gray-700',
      icon: 'text-gray-600 dark:text-gray-400'
    }
  }
  return styles[type] || styles.default
}

onMounted(() => {
  fetchNotifications()
  
  // Rafraîchir toutes les 30 secondes
  setInterval(fetchNotifications, 30000)
})
</script>