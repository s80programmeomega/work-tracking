<!-- resources\js\pages\Others\UserProfile.vue -->
<template>
  <admin-layout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />

    <div
      class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6"
    >
      <!-- Loading State -->
      <div v-if="loading" class="p-8 text-center">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p class="mt-2 text-gray-500">Chargement du profil...</p>
      </div>

      <!-- Profile Content -->
      <template v-else-if="profileData">
        <!-- Tabs Navigation -->
        <div class="mb-6 border-b border-gray-200 dark:border-gray-800">
          <nav class="flex space-x-1 overflow-x-auto" aria-label="Tabs">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                'px-4 py-3 text-sm font-medium transition-all whitespace-nowrap',
                activeTab === tab.id
                  ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400'
                  : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
              ]"
            >
              <div class="flex items-center gap-2">
                <component :is="tab.icon" class="w-5 h-5" />
                {{ tab.name }}
              </div>
            </button>
          </nav>
        </div>

        <!-- Tab Content -->
        <div class="tab-content">
          <!-- Profil Tab -->
          <div v-if="activeTab === 'profile'" class="space-y-6">
            <profile-card :user="profileData" @refresh="loadProfile" />
            <personal-info-card :user="profileData" @refresh="loadProfile" />
            <address-card :user="profileData" @refresh="loadProfile" />
          </div>

          <!-- Sécurité Tab -->
          <div v-if="activeTab === 'security'">
            <session-settings />
          </div>

          <!-- Notifications Tab -->
          <div v-if="activeTab === 'notifications'">
            <notification-settings :user="profileData" @refresh="loadProfile" />
          </div>

          <!-- Préférences Tab -->
          <div v-if="activeTab === 'preferences'">
            <preferences-settings :user="profileData" @refresh="loadProfile" />
          </div>

          <!-- Activité Tab -->
          <div v-if="activeTab === 'activity'">
            <activity-log :user="profileData" />
          </div>
        </div>
      </template>

      <!-- Error State -->
      <div v-else-if="error" class="p-8 text-center text-red-600">
        {{ error }}
        <button @click="loadProfile" class="mt-3 text-blue-600 hover:text-blue-800">
          Réessayer
        </button>
      </div>
    </div>
  </admin-layout>
</template>

<script setup>
import AdminLayout from '../../components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import { ref, onMounted, computed } from 'vue'
import ProfileCard from '../../components/profile/ProfileCard.vue'
import PersonalInfoCard from '../../components/profile/PersonalInfoCard.vue'
import AddressCard from '../../components/profile/AddressCard.vue'
import SessionSettings from '@/components/settings/SessionSettings.vue'
import NotificationSettings from '@/components/settings/NotificationSettings.vue'
import PreferencesSettings from '@/components/settings/PreferencesSettings.vue'
import ActivityLog from '@/components/profile/ActivityLog.vue'
import { useUsers } from '@/composables/useUsers'

// Icons
import {
  UserIcon,
  ShieldCheckIcon,
  BellIcon,
  CogIcon,
  ClockIcon
} from '@heroicons/vue/24/outline'

const currentPageTitle = ref('Mon Profil')
const profileData = ref(null)
const loading = ref(false)
const error = ref(null)
const activeTab = ref('profile')

const tabs = [
  { id: 'profile', name: 'Profil', icon: UserIcon },
  { id: 'security', name: 'Sécurité', icon: ShieldCheckIcon },
  { id: 'notifications', name: 'Notifications', icon: BellIcon },
  { id: 'preferences', name: 'Préférences', icon: CogIcon },
  { id: 'activity', name: 'Activité', icon: ClockIcon }
]

const { fetchProfile } = useUsers()

const loadProfile = async () => {
  loading.value = true
  error.value = null

  try {
    profileData.value = await fetchProfile()
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors du chargement du profil'
    console.error('Error loading profile:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadProfile()
})
</script>