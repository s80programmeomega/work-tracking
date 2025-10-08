<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
        {{ activite ? 'Modifier l\'activité' : 'Nouvelle activité' }}
      </h2>

      <!-- Error Alert -->
      <div v-if="errorMessage" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
        <p class="font-medium">{{ errorMessage }}</p>
        <ul v-if="validationErrors.length > 0" class="mt-2 list-disc list-inside text-sm">
          <li v-for="(error, index) in validationErrors" :key="index">{{ error }}</li>
        </ul>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Nom de l'activité *
          </label>
          <input
            v-model="formData.nom"
            type="text"
            required
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Description
          </label>
          <textarea
            v-model="formData.description"
            rows="3"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
          ></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Projet *
            </label>
            <select
              v-model="formData.projet_id"
              required
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            >
              <option value="">Sélectionner un projet</option>
              <option v-for="projet in projets" :key="projet.id" :value="projet.id">
                {{ projet.nom }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Responsable *
            </label>
            <select
              v-model="formData.responsable_id"
              required
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            >
              <option value="">Sélectionner un responsable</option>
              <option v-for="user in users" :key="user.id" :value="user.id">
                {{ user.nom }}
              </option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Date début
            </label>
            <input
              v-model="formData.date_debut"
              type="date"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Date fin
            </label>
            <input
              v-model="formData.date_fin"
              type="date"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Progression (%)
            </label>
            <input
              v-model.number="formData.progression"
              type="number"
              min="0"
              max="100"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Statut
            </label>
            <select
              v-model="formData.status"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            >
              <option value="active">Actif</option>
              <option value="archived">Archivé</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Couleur
          </label>
          <div class="flex gap-2 items-center">
            <input
              v-model="formData.couleur"
              type="color"
              class="h-10 w-20 border border-gray-300 dark:border-gray-700 rounded-lg"
            />
            <input
              v-model="formData.couleur"
              type="text"
              placeholder="#3B82F6"
              pattern="^#[0-9A-Fa-f]{6}$"
              class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            />
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-4">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            Annuler
          </button>
          <button
            type="submit"
            :disabled="loading"
            class="px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 disabled:opacity-50"
          >
            {{ loading ? 'Enregistrement...' : 'Enregistrer' }}
          </button>
        </div>
      </form>
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
