<!-- resources\js\components\settings\PreferencesSettings.vue -->
<template>
  <div class="preferences-settings">
    <div class="mb-6">
      <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">
        Préférences générales
      </h4>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Personnalisez votre expérience utilisateur
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="p-8 text-center">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-500">Chargement des préférences...</p>
    </div>

    <!-- Preferences Content -->
    <div v-else class="space-y-6">
      <!-- Theme Settings -->
      <div class="p-5 border border-gray-200 rounded-3 dark:border-gray-800">
        <h5 class="mb-4 font-medium text-gray-800 dark:text-white/90">Thème</h5>
        
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <label class="relative cursor-pointer">
            <input
              type="radio"
              v-model="preferences.theme"
              value="light"
              @change="updatePreferences"
              class="sr-only peer"
            >
            <div class="flex flex-col items-center p-4 border-2 rounded-3 peer-checked:border-blue-500 dark:peer-checked:border-blue-400 hover:bg-gray-50 dark:hover:bg-gray-800">
              <div class="w-full h-24 mb-3 overflow-hidden bg-white border border-gray-200 rounded-md dark:bg-gray-700 dark:border-gray-600">
                <!-- Light theme preview -->
                <div class="h-3 bg-gray-200"></div>
                <div class="p-3">
                  <div class="h-2 mb-1 bg-gray-300 rounded"></div>
                  <div class="w-2/3 h-2 bg-gray-300 rounded"></div>
                </div>
              </div>
              <span class="font-medium text-gray-700 dark:text-gray-300">Clair</span>
            </div>
          </label>

          <label class="relative cursor-pointer">
            <input
              type="radio"
              v-model="preferences.theme"
              value="dark"
              @change="updatePreferences"
              class="sr-only peer"
            >
            <div class="flex flex-col items-center p-4 border-2 rounded-3 peer-checked:border-blue-500 dark:peer-checked:border-blue-400 hover:bg-gray-50 dark:hover:bg-gray-800">
              <div class="w-full h-24 mb-3 overflow-hidden bg-gray-900 border border-gray-700 rounded-md">
                <!-- Dark theme preview -->
                <div class="h-3 bg-gray-800"></div>
                <div class="p-3">
                  <div class="h-2 mb-1 bg-gray-700 rounded"></div>
                  <div class="w-2/3 h-2 bg-gray-700 rounded"></div>
                </div>
              </div>
              <span class="font-medium text-gray-700 dark:text-gray-300">Sombre</span>
            </div>
          </label>

          <label class="relative cursor-pointer">
            <input
              type="radio"
              v-model="preferences.theme"
              value="system"
              @change="updatePreferences"
              class="sr-only peer"
            >
            <div class="flex flex-col items-center p-4 border-2 rounded-3 peer-checked:border-blue-500 dark:peer-checked:border-blue-400 hover:bg-gray-50 dark:hover:bg-gray-800">
              <div class="relative w-full h-24 mb-3 overflow-hidden border rounded-md dark:border-gray-600">
                <!-- System theme preview -->
                <div class="absolute top-0 left-0 w-1/2 h-full bg-white border-r border-gray-200">
                  <div class="h-3 bg-gray-200"></div>
                  <div class="p-3">
                    <div class="h-2 mb-1 bg-gray-300 rounded"></div>
                  </div>
                </div>
                <div class="absolute top-0 right-0 w-1/2 h-full bg-gray-900">
                  <div class="h-3 bg-gray-800"></div>
                  <div class="p-3">
                    <div class="h-2 bg-gray-700 rounded"></div>
                  </div>
                </div>
              </div>
              <span class="font-medium text-gray-700 dark:text-gray-300">Système</span>
            </div>
          </label>
        </div>
      </div>

      <!-- Language Settings -->
      <div class="p-5 border border-gray-200 rounded-3 dark:border-gray-800">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h5 class="font-medium text-gray-800 dark:text-white/90">Langue</h5>
            <p class="text-sm text-gray-500 dark:text-gray-400">Choisissez la langue d'affichage</p>
          </div>
        </div>
        
        <select
          v-model="preferences.language"
          @change="updatePreferences"
          class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-3 focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
        >
          <option value="fr">Français</option>
          <option value="en">English</option>
          <option value="es">Español</option>
          <option value="de">Deutsch</option>
          <option value="it">Italiano</option>
        </select>
      </div>

      <!-- Timezone Settings -->
      <div class="p-5 border border-gray-200 rounded-3 dark:border-gray-800">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h5 class="font-medium text-gray-800 dark:text-white/90">Fuseau horaire</h5>
            <p class="text-sm text-gray-500 dark:text-gray-400">Définissez votre fuseau horaire local</p>
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

      <!-- Date Format -->
      <div class="p-5 border border-gray-200 rounded-3 dark:border-gray-800">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h5 class="font-medium text-gray-800 dark:text-white/90">Format de date</h5>
            <p class="text-sm text-gray-500 dark:text-gray-400">Choisissez comment les dates sont affichées</p>
          </div>
        </div>
        
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <label class="flex items-center p-3 border rounded-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
            <input
              type="radio"
              v-model="preferences.dateFormat"
              value="DD/MM/YYYY"
              @change="updatePreferences"
              class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
            >
            <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">DD/MM/YYYY (31/12/2023)</span>
          </label>
          
          <label class="flex items-center p-3 border rounded-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
            <input
              type="radio"
              v-model="preferences.dateFormat"
              value="MM/DD/YYYY"
              @change="updatePreferences"
              class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
            >
            <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">MM/DD/YYYY (12/31/2023)</span>
          </label>
          
          <label class="flex items-center p-3 border rounded-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
            <input
              type="radio"
              v-model="preferences.dateFormat"
              value="YYYY-MM-DD"
              @change="updatePreferences"
              class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
            >
            <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">YYYY-MM-DD (2023-12-31)</span>
          </label>
          
          <label class="flex items-center p-3 border rounded-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
            <input
              type="radio"
              v-model="preferences.dateFormat"
              value="relative"
              @change="updatePreferences"
              class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
            >
            <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">Relatif (il y a 2 jours)</span>
          </label>
        </div>
      </div>

      <!-- Display Density -->
      <div class="p-5 border border-gray-200 rounded-3 dark:border-gray-800">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h5 class="font-medium text-gray-800 dark:text-white/90">Densité d'affichage</h5>
            <p class="text-sm text-gray-500 dark:text-gray-400">Contrôlez l'espacement des éléments</p>
          </div>
        </div>
        
        <div class="flex items-center space-x-6">
          <label class="flex items-center cursor-pointer">
            <input
              type="radio"
              v-model="preferences.displayDensity"
              value="compact"
              @change="updatePreferences"
              class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
            >
            <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Compact</span>
          </label>
          
          <label class="flex items-center cursor-pointer">
            <input
              type="radio"
              v-model="preferences.displayDensity"
              value="comfortable"
              @change="updatePreferences"
              class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
            >
            <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Confortable</span>
          </label>
          
          <label class="flex items-center cursor-pointer">
            <input
              type="radio"
              v-model="preferences.displayDensity"
              value="spacious"
              @change="updatePreferences"
              class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
            >
            <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Spacieux</span>
          </label>
        </div>
      </div>

      <!-- Auto-save -->
      <div class="p-5 border border-gray-200 rounded-3 dark:border-gray-800">
        <div class="flex items-center justify-between">
          <div>
            <h5 class="font-medium text-gray-800 dark:text-white/90">Sauvegarde automatique</h5>
            <p class="text-sm text-gray-500 dark:text-gray-400">Sauvegarder automatiquement les modifications</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              type="checkbox"
              v-model="preferences.autoSave"
              @change="updatePreferences"
              class="sr-only peer"
            >
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-hidden peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
          </label>
        </div>
        
        <div v-if="preferences.autoSave" class="mt-4 pl-4 border-l-2 border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm text-gray-600 dark:text-gray-400">Intervalle de sauvegarde</span>
            <span class="text-sm font-medium text-gray-800 dark:text-gray-300">
              {{ preferences.saveInterval }} secondes
            </span>
          </div>
          <input
            type="range"
            v-model="preferences.saveInterval"
            min="5"
            max="60"
            step="5"
            @change="updatePreferences"
            class="w-full h-2 bg-gray-200 rounded-3 appearance-none cursor-pointer dark:bg-gray-700"
          >
          <div class="flex justify-between mt-1 text-xs text-gray-500">
            <span>5s</span>
            <span>30s</span>
            <span>60s</span>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex flex-col gap-4 pt-4 border-t border-gray-200 sm:flex-row sm:justify-end dark:border-gray-800">
        <button
          @click="resetPreferences"
          class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-3 hover:bg-gray-50 focus:outline-hidden focus:ring-4 focus:ring-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
        >
          Réinitialiser
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
            Enregistrement...
          </span>
          <span v-else>
            Enregistrer les préférences
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '@/api/axios'

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

const preferences = reactive({
  theme: 'system',
  language: 'fr',
  timezone: 'Europe/Paris',
  dateFormat: 'DD/MM/YYYY',
  displayDensity: 'comfortable',
  autoSave: true,
  saveInterval: 30
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

    if (originalPreferences.value.theme !== preferences.theme) {
      applyTheme(preferences.theme)
    }

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

const applyTheme = (theme) => {
  if (theme === 'dark') {
    document.documentElement.classList.add('dark')
  } else if (theme === 'light') {
    document.documentElement.classList.remove('dark')
  } else {
    // System theme
    if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
      document.documentElement.classList.add('dark')
    } else {
      document.documentElement.classList.remove('dark')
    }
  }
}

onMounted(() => {
  loadPreferences()
})
</script>