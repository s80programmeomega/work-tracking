<!-- resources\js\components\settings\NotificationSettings.vue -->
<template>
  <div class="notification-settings">
    <div class="mb-6">
      <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">
        Paramètres de notifications
      </h4>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Gérez comment et quand vous recevez des notifications
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="p-8 text-center">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-500 dark:text-gray-400">Chargement des paramètres...</p>
    </div>

    <!-- Settings Content -->
    <div v-else class="space-y-6">
      <!-- Email Notifications -->
      <div class="p-5 border border-gray-200 rounded-3 dark:border-gray-800">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h5 class="font-medium text-gray-800 dark:text-white/90">Notifications par email</h5>
            <p class="text-sm text-gray-500 dark:text-gray-400">Recevoir des emails pour les activités importantes</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              type="checkbox"
              v-model="settings.emailNotifications"
              class="sr-only peer"
              @change="updateSettings"
            >
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-hidden peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
          </label>
        </div>

        <!-- Email Settings -->
        <div v-if="settings.emailNotifications" class="pl-4 mt-4 space-y-4 border-l-2 border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div>
              <h6 class="text-sm font-medium text-gray-700 dark:text-gray-300">Nouvelles tâches</h6>
              <p class="text-xs text-gray-500 dark:text-gray-400">Quand on vous assigne une nouvelle tâche</p>
            </div>
            <input
              type="checkbox"
              v-model="settings.emailTaskAssignments"
              @change="updateSettings"
              class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
            >
          </div>

          <div class="flex items-center justify-between">
            <div>
              <h6 class="text-sm font-medium text-gray-700 dark:text-gray-300">Délais de tâches</h6>
              <p class="text-xs text-gray-500 dark:text-gray-400">Rappels pour les tâches en retard</p>
            </div>
            <input
              type="checkbox"
              v-model="settings.emailTaskDeadlines"
              @change="updateSettings"
              class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
            >
          </div>

          <div class="flex items-center justify-between">
            <div>
              <h6 class="text-sm font-medium text-gray-700 dark:text-gray-300">Mises à jour de projet</h6>
              <p class="text-xs text-gray-500 dark:text-gray-400">Changements importants dans vos projets</p>
            </div>
            <input
              type="checkbox"
              v-model="settings.emailProjectUpdates"
              @change="updateSettings"
              class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
            >
          </div>
        </div>
      </div>

      <!-- Push Notifications -->
      <div class="p-5 border border-gray-200 rounded-3 dark:border-gray-800">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h5 class="font-medium text-gray-800 dark:text-white/90">Notifications push</h5>
            <p class="text-sm text-gray-500 dark:text-gray-400">Recevoir des notifications sur votre appareil</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              type="checkbox"
              v-model="settings.pushNotifications"
              class="sr-only peer"
              @change="updateSettings"
            >
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-hidden peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
          </label>
        </div>
      </div>

      <!-- In-App Notifications -->
      <div class="p-5 border border-gray-200 rounded-3 dark:border-gray-800">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h5 class="font-medium text-gray-800 dark:text-white/90">Notifications dans l'app</h5>
            <p class="text-sm text-gray-500 dark:text-gray-400">Voir les notifications dans l'application</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              type="checkbox"
              v-model="settings.inAppNotifications"
              class="sr-only peer"
              @change="updateSettings"
            >
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-hidden peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
          </label>
        </div>

        <!-- Frequency Settings -->
        <div v-if="settings.inAppNotifications" class="pl-4 mt-4 space-y-4 border-l-2 border-gray-200 dark:border-gray-700">
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
              Fréquence des rappels
            </label>
            <select
              v-model="settings.notificationFrequency"
              @change="updateSettings"
              class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-3 focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
            >
              <option value="realtime">En temps réel</option>
              <option value="hourly">Toutes les heures</option>
              <option value="daily">Quotidiennement</option>
              <option value="weekly">Hebdomadairement</option>
            </select>
          </div>

          <div>
            <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
              Heures de réception
            </label>
            <div class="flex items-center space-x-4">
              <input
                type="time"
                v-model="settings.notificationStartTime"
                @change="updateSettings"
                class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-3 focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
              >
              <span class="text-gray-500 dark:text-gray-400">à</span>
              <input
                type="time"
                v-model="settings.notificationEndTime"
                @change="updateSettings"
                class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-3 focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
              >
            </div>
          </div>
        </div>
      </div>

      <!-- Notification Sounds -->
      <div class="p-5 border border-gray-200 rounded-3 dark:border-gray-800">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h5 class="font-medium text-gray-800 dark:text-white/90">Sons de notification</h5>
            <p class="text-sm text-gray-500 dark:text-gray-400">Jouer un son pour les nouvelles notifications</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              type="checkbox"
              v-model="settings.notificationSounds"
              class="sr-only peer"
              @change="updateSettings"
            >
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-hidden peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
          </label>
        </div>
      </div>

      <!-- Save Button -->
      <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-800">
        <button
          @click="saveSettings"
          :disabled="saving"
          class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-3 hover:bg-blue-700 focus:outline-hidden focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="saving">
            <svg class="inline w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Enregistrement...
          </span>
          <span v-else>
            Enregistrer les modifications
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'

const props = defineProps({
  user: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['refresh'])

const loading = ref(true)
const saving = ref(false)
const hasChanges = ref(false)

const settings = reactive({
  emailNotifications: true,
  emailTaskAssignments: true,
  emailTaskDeadlines: true,
  emailProjectUpdates: true,
  pushNotifications: true,
  inAppNotifications: true,
  notificationFrequency: 'realtime',
  notificationStartTime: '09:00',
  notificationEndTime: '18:00',
  notificationSounds: true
})

// Backup for reset
const originalSettings = ref({})

const loadSettings = async () => {
  loading.value = true
  
  // Simulate API call
  await new Promise(resolve => setTimeout(resolve, 500))
  
  // Load from localStorage or API
  const savedSettings = localStorage.getItem('notificationSettings')
  if (savedSettings) {
    Object.assign(settings, JSON.parse(savedSettings))
  }
  
  // Save original for reset
  originalSettings.value = { ...settings }
  
  loading.value = false
}

const updateSettings = () => {
  hasChanges.value = true
}

const saveSettings = async () => {
  saving.value = true
  
  try {
    // Save to localStorage (replace with API call)
    localStorage.setItem('notificationSettings', JSON.stringify(settings))
    
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    hasChanges.value = false
    originalSettings.value = { ...settings }
    
    // Show success message
    alert('Paramètres enregistrés avec succès')
    
    emit('refresh')
  } catch (error) {
    console.error('Error saving settings:', error)
    alert('Erreur lors de l\'enregistrement')
  } finally {
    saving.value = false
  }
}

const resetSettings = () => {
  Object.assign(settings, originalSettings.value)
  hasChanges.value = false
}

onMounted(() => {
  loadSettings()
})

watch(settings, () => {
  hasChanges.value = true
}, { deep: true })
</script>