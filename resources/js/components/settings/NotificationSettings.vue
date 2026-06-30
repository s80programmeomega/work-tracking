<!-- resources\js\components\settings\NotificationSettings.vue -->
<template>
  <div class="notification-settings">
    <div class="mb-6">
      <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">
        {{ $t('notif_settings.title') }}
      </h4>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ $t('notif_settings.subtitle') }}
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="p-8 text-center">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-500 dark:text-gray-400">{{ $t('notif_settings.loading') }}</p>
    </div>

    <!-- Settings Content -->
    <div v-else class="space-y-6">
      <!-- Email Notifications -->
      <div class="p-5 border border-gray-200 rounded-3 dark:border-gray-800">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h5 class="font-medium text-gray-800 dark:text-white/90">{{ $t('notif_settings.email_title') }}</h5>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('notif_settings.email_desc') }}</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              dusk="email-notifications-toggle"
              type="checkbox"
              v-model="settings.emailNotifications"
              class="sr-only peer"
              @change="hasChanges = true"
            >
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-hidden peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
          </label>
        </div>

        <!-- Email Settings -->
        <div v-if="settings.emailNotifications" class="pl-4 mt-4 space-y-4 border-l-2 border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div>
              <h6 class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('notif_settings.email_tasks_title') }}</h6>
              <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('notif_settings.email_tasks_desc') }}</p>
            </div>
            <input
              type="checkbox"
              v-model="settings.emailTaskAssignments"
              @change="hasChanges = true"
              class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
            >
          </div>

          <div class="flex items-center justify-between">
            <div>
              <h6 class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('notif_settings.email_deadlines_title') }}</h6>
              <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('notif_settings.email_deadlines_desc') }}</p>
            </div>
            <input
              type="checkbox"
              v-model="settings.emailTaskDeadlines"
              @change="hasChanges = true"
              class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
            >
          </div>

          <div class="flex items-center justify-between">
            <div>
              <h6 class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('notif_settings.email_projects_title') }}</h6>
              <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('notif_settings.email_projects_desc') }}</p>
            </div>
            <input
              type="checkbox"
              v-model="settings.emailProjectUpdates"
              @change="hasChanges = true"
              class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
            >
          </div>
        </div>
      </div>

      <!-- Push Notifications -->
      <div class="p-5 border border-gray-200 rounded-3 dark:border-gray-800">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h5 class="font-medium text-gray-800 dark:text-white/90">{{ $t('notif_settings.push_title') }}</h5>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('notif_settings.push_desc') }}</p>
          </div>
          <label class="relative inline-flex items-center" :class="pushToggleDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'">
            <input
              dusk="push-notifications-toggle"
              type="checkbox"
              v-model="settings.pushNotifications"
              class="sr-only peer"
              :disabled="pushToggleDisabled"
              @change="onPushToggleChange"
            >
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-hidden peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
          </label>
        </div>

        <!-- Browser not supported -->
        <p v-if="!webPush.isSupported.value" class="text-xs text-amber-600 dark:text-amber-400">
          {{ $t('notif_settings.push_not_supported') }}
        </p>

        <!-- Permission denied -->
        <p v-else-if="webPush.permission.value === 'denied'" class="text-xs text-red-600 dark:text-red-400">
          {{ $t('notif_settings.push_permission_denied') }}
        </p>
      </div>

      <!-- In-App Notifications -->
      <div class="p-5 border border-gray-200 rounded-3 dark:border-gray-800">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h5 class="font-medium text-gray-800 dark:text-white/90">{{ $t('notif_settings.inapp_title') }}</h5>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('notif_settings.inapp_desc') }}</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              type="checkbox"
              v-model="settings.inAppNotifications"
              class="sr-only peer"
              @change="hasChanges = true"
            >
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-hidden peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
          </label>
        </div>
      </div>

      <!-- Notification Sounds -->
      <div class="p-5 border border-gray-200 rounded-3 dark:border-gray-800">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h5 class="font-medium text-gray-800 dark:text-white/90">{{ $t('notif_settings.sounds_title') }}</h5>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('notif_settings.sounds_desc') }}</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              dusk="notification-sounds-toggle"
              type="checkbox"
              v-model="settings.notificationSounds"
              class="sr-only peer"
              @change="hasChanges = true"
            >
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-hidden peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
          </label>
        </div>
      </div>

      <!-- Save Button -->
      <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-800">
        <button
          dusk="save-notifications-btn"
          @click="saveSettings"
          :disabled="saving"
          class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-3 hover:bg-blue-700 focus:outline-hidden focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="saving">
            <svg class="inline w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ $t('notif_settings.btn_saving') }}
          </span>
          <span v-else>
            {{ $t('notif_settings.btn_save') }}
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vue-toastification'
import { useWebPush } from '@/composables/useWebPush'
import api from '@/api/axios'

defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
})

const emit = defineEmits(['refresh'])

const { t } = useI18n()
const toast = useToast()
const webPush = useWebPush()

const loading = ref(true)
const saving = ref(false)
const hasChanges = ref(false)

const settings = reactive({
    emailNotifications: true,
    emailTaskAssignments: true,
    emailTaskDeadlines: true,
    emailProjectUpdates: true,
    pushNotifications: false,
    inAppNotifications: true,
    notificationSounds: true,
})

const pushToggleDisabled = computed(
    () => ! webPush.isSupported.value || webPush.permission.value === 'denied',
)

const loadSettings = async () => {
    loading.value = true
    try {
        const [prefRes] = await Promise.all([
            api.get('/notification-preferences'),
            webPush.checkSubscription(),
        ])

        const prefs = prefRes.data?.data ?? prefRes.data

        settings.emailNotifications = prefs.email_enabled ?? true
        settings.inAppNotifications = prefs.in_app_enabled ?? true
        // L'état réel du push = abonnement navigateur ET préférence backend
        settings.pushNotifications = (prefs.push_enabled ?? false) && webPush.isSubscribed.value
    } catch {
        toast.error(t('notif_settings.load_error'))
    } finally {
        loading.value = false
    }
}

const onPushToggleChange = async () => {
    if (settings.pushNotifications) {
        // Activer : demander permission + créer abonnement navigateur
        await webPush.subscribe()
        if (! webPush.isSubscribed.value) {
            // Permission refusée ou erreur — annuler le toggle
            settings.pushNotifications = false
        }
    } else {
        // Désactiver : révoquer l'abonnement navigateur
        await webPush.unsubscribe()
    }
    hasChanges.value = true
}

const saveSettings = async () => {
    saving.value = true
    try {
        await api.put('/notification-preferences', {
            email_enabled: settings.emailNotifications,
            push_enabled: settings.pushNotifications,
            in_app_enabled: settings.inAppNotifications,
        })
        hasChanges.value = false
        toast.success(t('notif_settings.save_success'))
        emit('refresh')
    } catch {
        toast.error(t('notif_settings.save_error'))
    } finally {
        saving.value = false
    }
}

onMounted(() => {
    loadSettings()
})
</script>
