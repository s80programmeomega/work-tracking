<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" @click.self="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
      <!-- Header -->
      <div class="px-8 py-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-purple-50 to-white dark:from-gray-900 dark:to-gray-800">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-lg">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
          </div>
          <div class="flex-1">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ activite ? 'Modifier l\'activité' : 'Nouvelle activité' }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ activite ? 'Mettre à jour les informations de l\'activité' : 'Créer une nouvelle activité pour votre projet' }}
            </p>
          </div>
          <button
            @click="$emit('close')"
            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Error Alert -->
      <div v-if="errorMessage" class="mx-8 mt-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
        <div class="flex items-start gap-3">
          <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div class="flex-1">
            <p class="font-medium text-red-800 dark:text-red-200">{{ errorMessage }}</p>
            <ul v-if="validationErrors.length > 0" class="mt-2 space-y-1">
              <li v-for="(error, index) in validationErrors" :key="index" class="text-sm text-red-700 dark:text-red-300 flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                {{ error }}
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Form Body -->
      <div class="flex-1 overflow-y-auto px-8 py-6">
        <form @submit.prevent="handleSubmit" class="space-y-6">
          <!-- Section 1: Informations générales -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Informations générales
            </h3>

            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Nom de l'activité <span class="text-red-500">*</span>
              </label>
              <input
                v-model="formData.nom"
                type="text"
                required
                placeholder="Ex: Phase de développement"
                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all"
              />
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Description
              </label>
              <textarea
                v-model="formData.description"
                rows="3"
                placeholder="Décrivez les objectifs et le périmètre de cette activité..."
                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all resize-none"
              ></textarea>
            </div>
          </div>

          <!-- Section 2: Projet & Responsable -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              Projet & Responsable
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Projet <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="formData.projet_id"
                  required
                  class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all"
                >
                  <option value="">Sélectionner un projet</option>
                  <option v-for="projet in projets" :key="projet.id" :value="projet.id">
                    {{ projet.nom }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Responsable <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="formData.responsable_id"
                  required
                  class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all"
                >
                  <option value="">Sélectionner un responsable</option>
                  <option v-for="user in users" :key="user.id" :value="user.id">
                    {{ user.nom }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <!-- Section 3: Planification -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              Planification
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Date de début
                </label>
                <input
                  v-model="formData.date_debut"
                  type="date"
                  class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all"
                />
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Date de fin
                </label>
                <input
                  v-model="formData.date_fin"
                  type="date"
                  class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all"
                />
              </div>
            </div>
          </div>

          <!-- Section 4: Statut & Progression -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              Statut & Progression
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Statut
                </label>
                <div class="grid grid-cols-2 gap-3">
                  <label
                    v-for="statut in statusOptions"
                    :key="statut.value"
                    class="relative flex items-center gap-3 p-3 border-2 rounded-xl cursor-pointer transition-all hover:shadow-md"
                    :class="formData.status === statut.value
                      ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                      : 'border-gray-300 dark:border-gray-600 hover:border-purple-300 dark:hover:border-purple-700'"
                  >
                    <input
                      type="radio"
                      v-model="formData.status"
                      :value="statut.value"
                      class="sr-only"
                    />
                    <span
                      :class="statut.color"
                      class="w-3 h-3 rounded-full flex-shrink-0"
                    ></span>
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                      {{ statut.label }}
                    </span>
                    <svg
                      v-if="formData.status === statut.value"
                      class="absolute right-3 w-5 h-5 text-purple-500"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                    >
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                  </label>
                </div>
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Progression ({{ formData.progression }}%)
                </label>
                <input
                  v-model.number="formData.progression"
                  type="range"
                  min="0"
                  max="100"
                  step="5"
                  class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full appearance-none cursor-pointer accent-purple-500"
                />
                <div class="mt-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                  <div
                    class="bg-gradient-to-r from-purple-500 to-purple-600 h-2 rounded-full transition-all duration-300"
                    :style="{ width: `${formData.progression}%` }"
                  ></div>
                </div>
                <div class="flex justify-between mt-1 text-xs text-gray-500 dark:text-gray-400">
                  <span>0%</span>
                  <span>50%</span>
                  <span>100%</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Section 5: Apparence -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
              </svg>
              Apparence
            </h3>

            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Couleur de l'activité
              </label>
              <div class="flex gap-3 items-center">
                <div class="relative">
                  <input
                    v-model="formData.couleur"
                    type="color"
                    class="h-12 w-16 border-2 border-gray-300 dark:border-gray-600 rounded-xl cursor-pointer"
                  />
                </div>
                <input
                  v-model="formData.couleur"
                  type="text"
                  placeholder="#3B82F6"
                  pattern="^#[0-9A-Fa-f]{6}$"
                  class="flex-1 px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all font-mono"
                />
                <div
                  class="h-12 w-16 rounded-xl border-2 border-gray-300 dark:border-gray-600"
                  :style="{ backgroundColor: formData.couleur }"
                ></div>
              </div>
              <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Cette couleur sera utilisée pour identifier visuellement l'activité
              </p>
            </div>
          </div>
        </form>
      </div>

      <!-- Footer -->
      <div class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
        <div class="flex justify-end gap-3">
          <button
            type="button"
            @click="$emit('close')"
            class="px-6 py-2.5 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-all"
          >
            Annuler
          </button>
          <button
            type="submit"
            @click="handleSubmit"
            :disabled="loading"
            class="px-6 py-2.5 bg-gradient-to-r from-purple-500 to-purple-600 text-white font-semibold rounded-xl hover:from-purple-600 hover:to-purple-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl transition-all"
          >
            <span v-if="loading" class="flex items-center gap-2">
              <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Enregistrement...
            </span>
            <span v-else>Enregistrer</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { useActivites } from '@/composables/useActivites'
import api from '@/api/axios'

const props = defineProps({
  activite: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

const authStore = useAuthStore()
const { createActivite, updateActivite } = useActivites()

const loading = ref(false)
const users = ref([])
const projets = ref([])
const errorMessage = ref('')
const validationErrors = ref([])

const statusOptions = [
  { value: 'active', label: 'Actif', color: 'bg-green-500' },
  { value: 'archived', label: 'Archivé', color: 'bg-gray-500' }
]

const formData = ref({
  nom: '',
  description: '',
  projet_id: '',
  responsable_id: authStore.user?.id,
  date_debut: '',
  date_fin: '',
  progression: 0,
  status: 'active',
  couleur: '#3B82F6'
})

const loadUsers = async () => {
  try {
    const { data } = await api.get('/users')
    users.value = data.data || []
  } catch (error) {
    console.error('Error loading users:', error)
  }
}

const loadProjets = async () => {
  try {
    const { data } = await api.get('/projets')
    projets.value = data.data || []
  } catch (error) {
    console.error('Error loading projets:', error)
  }
}

const handleSubmit = async () => {
  loading.value = true
  errorMessage.value = ''
  validationErrors.value = []

  try {
    if (props.activite) {
      await updateActivite(props.activite.id, formData.value)
    } else {
      await createActivite(formData.value)
    }
    emit('saved')
  } catch (error) {
    console.error(error)

    // Handle validation errors (422)
    if (error.response && error.response.status === 422) {
      const errors = error.response.data.errors
      if (errors) {
        // Collect all validation error messages
        validationErrors.value = Object.values(errors).flat()
        errorMessage.value = 'Veuillez corriger les erreurs suivantes :'
      } else {
        errorMessage.value = error.response.data.message || 'Erreur de validation'
      }
    }
    // Handle other errors
    else if (error.response && error.response.data && error.response.data.message) {
      errorMessage.value = error.response.data.message
    }
    // Network or unknown errors
    else if (error.message === 'Network Error') {
      errorMessage.value = 'Erreur de connexion. Veuillez vérifier votre connexion internet.'
    } else {
      errorMessage.value = 'Une erreur s\'est produite. Veuillez réessayer.'
    }
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadUsers(), loadProjets()])

  if (props.activite) {
    formData.value = {
      nom: props.activite.nom || '',
      description: props.activite.description || '',
      projet_id: props.activite.projet_id || '',
      responsable_id: props.activite.responsable_id || authStore.user?.id,
      date_debut: props.activite.date_debut || '',
      date_fin: props.activite.date_fin || '',
      progression: props.activite.progression || 0,
      status: props.activite.status || 'active',
      couleur: props.activite.couleur || '#3B82F6'
    }
  }
})
</script>
