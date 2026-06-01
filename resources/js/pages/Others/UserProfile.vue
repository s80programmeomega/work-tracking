<!-- resources\js\pages\Others\UserProfile.vue -->
<template>
  <admin-layout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />

    <div
      class="rounded-3 border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6"
    >
      <!-- Loading State -->
      <div v-if="loading" class="p-8 text-center">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p class="mt-2 text-gray-500 dark:text-gray-400">{{ $t('user_profile.loading') }}</p>
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
          <div v-if="activeTab === 'security'" class="space-y-6">
            <session-settings />
            <two-factor-settings />

            <!-- Danger Zone: account deletion -->
            <div class="p-5 border border-red-200 dark:border-red-800/50 rounded-3 bg-red-50/50 dark:bg-red-900/10">
              <h5 class="font-semibold text-red-700 dark:text-red-400 mb-1">{{ $t('user_profile.danger_zone_title') }}</h5>
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                {{ $t('user_profile.danger_zone_desc') }}
              </p>
              <button
                @click="showDeleteAccountModal = true"
                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-3 hover:bg-red-700 transition-colors"
              >
                {{ $t('user_profile.delete_btn') }}
              </button>
            </div>
          </div>

          <!-- Delete Account confirm dialog -->
          <div
            v-if="showDeleteAccountModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="showDeleteAccountModal = false"
          >
            <div class="bg-white dark:bg-gray-900 rounded-3 p-6 w-full max-w-md ">
              <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ $t('user_profile.delete_modal_title') }}</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                {{ $t('user_profile.delete_modal_desc') }}
              </p>
              <input
                v-model="deleteAccountPassword"
                type="password"
                :placeholder="$t('user_profile.password_placeholder')"
                class="w-full px-4 py-2 text-sm rounded-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white mb-3 focus:ring-2 focus:ring-red-500 focus:border-transparent"
                @keyup.enter="confirmDeleteAccount"
              />
              <p v-if="deleteAccountError" class="text-xs text-red-600 mb-3">{{ deleteAccountError }}</p>
              <div class="flex justify-end gap-3">
                <button
                  @click="showDeleteAccountModal = false"
                  class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                >
                  {{ $t('user_profile.cancel') }}
                </button>
                <button
                  @click="confirmDeleteAccount"
                  :disabled="deletingAccount || !deleteAccountPassword"
                  class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-3 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  <i v-if="deletingAccount" class="fas fa-spinner fa-spin mr-1"></i>
                  {{ $t('user_profile.confirm_delete') }}
                </button>
              </div>
            </div>
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
          {{ $t('user_profile.retry') }}
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
import TwoFactorSettings from '@/components/auth/TwoFactorSettings.vue'
import NotificationSettings from '@/components/settings/NotificationSettings.vue'
import PreferencesSettings from '@/components/settings/PreferencesSettings.vue'
import ActivityLog from '@/components/profile/ActivityLog.vue'
import { useUsers } from '@/composables/useUsers'
import api from '@/api/axios'
import { useAuthStore } from '@/stores/authStore'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

// Icons
import {
  UserIcon,
  ShieldCheckIcon,
  BellIcon,
  CogIcon,
  ClockIcon
} from '@heroicons/vue/24/outline'

const { t } = useI18n()
const authStore = useAuthStore()
const router = useRouter()

const currentPageTitle = computed(() => t('user_profile.page_title'))
const profileData = ref(null)
const loading = ref(false)
const error = ref(null)
const activeTab = ref('profile')

const showDeleteAccountModal = ref(false)
const deleteAccountPassword = ref('')
const deletingAccount = ref(false)
const deleteAccountError = ref(null)

const tabs = computed(() => [
  { id: 'profile', name: t('user_profile.tab_profile'), icon: UserIcon },
  { id: 'security', name: t('user_profile.tab_security'), icon: ShieldCheckIcon },
  { id: 'notifications', name: t('user_profile.tab_notifications'), icon: BellIcon },
  { id: 'preferences', name: t('user_profile.tab_preferences'), icon: CogIcon },
  { id: 'activity', name: t('user_profile.tab_activity'), icon: ClockIcon }
])

const { fetchProfile } = useUsers()

const loadProfile = async () => {
  loading.value = true
  error.value = null

  try {
    profileData.value = await fetchProfile()
  } catch (err) {
    error.value = err.response?.data?.message || t('user_profile.error_load')
    console.error('Error loading profile:', err)
  } finally {
    loading.value = false
  }
}

const confirmDeleteAccount = async () => {
  if (!deleteAccountPassword.value) { return }
  deletingAccount.value = true
  deleteAccountError.value = null
  try {
    await api.delete('/users/profile', { data: { password: deleteAccountPassword.value } })
    await authStore.logout?.()
    router.push('/signin')
  } catch (err) {
    deleteAccountError.value = err.response?.data?.message ?? t('user_profile.error_password')
  } finally {
    deletingAccount.value = false
  }
}

onMounted(() => {
  loadProfile()
})
</script>