<!-- resources\js\components\settings\PreferencesSettings.vue -->
<template>
  <div class="preferences-settings" dusk="preferences-settings-panel">
    <div class="mb-6">
      <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">
        {{ $t('pref_settings.title') }}
      </h4>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ $t('pref_settings.subtitle') }}
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="p-8 text-center">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-500 dark:text-gray-400">{{ $t('pref_settings.loading') }}</p>
    </div>

    <!-- Preferences Content -->
    <div v-else class="space-y-6">
      <!-- Timezone Settings -->
      <div dusk="pref-timezone-section" class="p-5 border border-gray-200 rounded-3 dark:border-gray-800">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h5 class="font-medium text-gray-800 dark:text-white/90">{{ $t('pref_settings.tz_title') }}</h5>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('pref_settings.tz_desc') }}</p>
          </div>
        </div>
        
        <select
          v-model="preferences.timezone"
          @change="updatePreferences"
          class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-3 focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
        >
          <option value="Europe/Paris">Europe/Paris (UTC+1)</option>
          <option value="UTC">UTC</option>
          <option value="America/New_York">America/New_York (UTC-5)</option>
          <option value="Asia/Tokyo">Asia/Tokyo (UTC+9)</option>
          <option value="Australia/Sydney">Australia/Sydney (UTC+10)</option>
        </select>
      </div>

      <!-- Actions -->
      <div class="flex flex-col gap-4 pt-4 border-t border-gray-200 sm:flex-row sm:justify-end dark:border-gray-800">
        <button
          @click="resetPreferences"
          class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-3 hover:bg-gray-50 focus:outline-hidden focus:ring-4 focus:ring-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
        >
          {{ $t('pref_settings.btn_reset') }}
        </button>

        <button
          @click="savePreferences"
          :disabled="saving"
          class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-3 hover:bg-blue-700 focus:outline-hidden focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="saving">
            <svg class="inline w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ $t('pref_settings.btn_saving') }}
          </span>
          <span v-else>
            {{ $t('pref_settings.btn_save') }}
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@/api/axios'

const props = defineProps({
  user: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['refresh'])

const { t } = useI18n()
const loading = ref(true)
const saving = ref(false)
const hasChanges = ref(false)

const preferences = reactive({
  language: 'fr',
  timezone: 'Europe/Paris',
})

const originalPreferences = ref({})

const loadPreferences = async () => {
  loading.value = true
  
  // Simulate API call
  await new Promise(resolve => setTimeout(resolve, 500))
  
  // Load from localStorage or user data
  const savedPrefs = localStorage.getItem('userPreferences')
  if (savedPrefs) {
    Object.assign(preferences, JSON.parse(savedPrefs))
  } else if (props.user.language) {
    preferences.language = props.user.language
  }
  if (props.user.timezone) {
    preferences.timezone = props.user.timezone
  }
  
  originalPreferences.value = { ...preferences }
  loading.value = false
}

const updatePreferences = () => {
  hasChanges.value = true
}

const savePreferences = async () => {
  saving.value = true

  try {
    localStorage.setItem('userPreferences', JSON.stringify(preferences))

    await api.put('/users/profile', {
      language: preferences.language,
      timezone: preferences.timezone,
    })

    hasChanges.value = false
    originalPreferences.value = { ...preferences }

    emit('refresh')
  } catch (error) {
    console.error('Error saving preferences:', error)
  } finally {
    saving.value = false
  }
}

const resetPreferences = () => {
  Object.assign(preferences, originalPreferences.value)
  hasChanges.value = false
}

onMounted(() => {
  loadPreferences()
})
</script>