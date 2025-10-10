<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
        {{ tache ? 'Modifier la tâche' : 'Nouvelle tâche' }}
      </h2>

      <!-- Error Alert -->
      <div v-if="errorMessage" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
        <p class="font-medium">{{ errorMessage }}</p>
        <ul v-if="validationErrors.length > 0" class="mt-2 list-disc list-inside text-sm">
          <li v-for="(error, index) in validationErrors" :key="index">{{ error }}</li>
        </ul>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Activity Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Activité *
          </label>
          <select
            v-model="formData.activite_id"
            required
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
          >
            <option value="">Sélectionner une activité</option>
            <option v-for="activite in activites" :key="activite.id" :value="activite.id">
              {{ activite.nom }}
            </option>
          </select>
        </div>

        <!-- Title -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Titre de la tâche *
          </label>
          <input
            v-model="formData.titre"
            type="text"
            required
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
          />
        </div>

        <!-- Description -->
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

        <!-- Objectif and Indicateurs -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Objectif
            </label>
            <textarea
              v-model="formData.objectif"
              rows="2"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Indicateurs de résultats
            </label>
            <textarea
              v-model="formData.indicateurs_resultats"
              rows="2"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            ></textarea>
          </div>
        </div>

        <!-- Status and Priority -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Statut
            </label>
            <select
              v-model="formData.statut"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            >
              <option value="a_faire">À faire</option>
              <option value="en_cours">En cours</option>
              <option value="termine">Terminé</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Priorité
            </label>
            <select
              v-model="formData.priorite"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            >
              <option value="faible">Faible</option>
              <option value="moyenne">Moyenne</option>
              <option value="elevee">Élevée</option>
              <option value="critique">Critique</option>
            </select>
          </div>
        </div>

        <!-- Assignees -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Assigner à
          </label>
          <select
            v-model="formData.assignee_ids"
            multiple
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            size="4"
          >
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.nom }}
            </option>
          </select>
          <p class="mt-1 text-xs text-gray-500">Maintenez Ctrl/Cmd pour sélectionner plusieurs utilisateurs</p>
        </div>

        <!-- Labels -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Labels
          </label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="label in labels"
              :key="label.id"
              type="button"
              @click="toggleLabel(label.id)"
              class="px-3 py-1.5 text-sm font-medium rounded-lg transition-all"
              :class="formData.label_ids.includes(label.id) ? 'ring-2 ring-offset-2' : 'opacity-60 hover:opacity-100'"
              :style="{
                backgroundColor: label.couleur + (formData.label_ids.includes(label.id) ? '' : '20'),
                color: formData.label_ids.includes(label.id) ? '#ffffff' : label.couleur,
                borderColor: label.couleur,
                ringColor: label.couleur
              }"
              style="border-width: 1px;"
              :title="label.description"
            >
              {{ label.nom }}
            </button>
          </div>
          <p v-if="labels.length === 0" class="mt-1 text-xs text-gray-500">Aucun label disponible</p>
        </div>

        <!-- Dates: Start, Due, and End -->
        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Date de début
            </label>
            <input
              v-model="formData.date_debut"
              type="date"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Date d'échéance
            </label>
            <input
              v-model="formData.echeance"
              type="date"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Date de fin réelle
            </label>
            <input
              v-model="formData.date_fin_reelle"
              type="date"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            />
          </div>
        </div>

        <!-- Progress and Time Tracking -->
        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Progression (%)
            </label>
            <input
              v-model.number="formData.taux_realisation"
              type="number"
              min="0"
              max="100"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Heures estimées
            </label>
            <input
              v-model.number="formData.estimated_hours"
              type="number"
              min="0"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Heures réelles
            </label>
            <input
              v-model.number="formData.actual_hours"
              type="number"
              min="0"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            />
          </div>
        </div>

        <!-- Cover Image -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Image de couverture
          </label>
          <input
            type="file"
            accept="image/*"
            @change="handleCoverImageUpload"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
          />
          <p class="mt-1 text-xs text-gray-500">PNG, JPG, GIF jusqu'à 2 Mo</p>
          <div v-if="coverImagePreview" class="mt-2">
            <img :src="coverImagePreview" alt="Preview" class="h-32 rounded-lg object-cover" />
          </div>
        </div>

        <!-- Color -->
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

        <!-- Comment -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Commentaire
          </label>
          <textarea
            v-model="formData.commentaire"
            rows="2"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
          ></textarea>
        </div>

        <!-- Buttons -->
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
import { useTaches } from '@/composables/useTaches'
import { useLabels } from '@/composables/useLabels'
import api from '@/api/axios'

const props = defineProps({
  tache: {
    type: Object,
    default: null
  },
  activiteId: {
    type: Number,
    default: null
  },
  initialStatut: {
    type: String,
    default: 'a_faire'
  }
})

const emit = defineEmits(['close', 'saved'])

const authStore = useAuthStore()
const { createTache, updateTache } = useTaches()
const { labels, fetchLabels } = useLabels()

const loading = ref(false)
const users = ref([])
const activites = ref([])
const errorMessage = ref('')
const validationErrors = ref([])

const formData = ref({
  activite_id: props.activiteId || '',
  titre: '',
  description: '',
  objectif: '',
  indicateurs_resultats: '',
  statut: props.initialStatut || 'a_faire',
  priorite: 'moyenne',
  echeance: '',
  date_debut: '',
  date_fin_reelle: '',
  taux_realisation: 0,
  estimated_hours: null,
  actual_hours: null,
  cover_image: null,
  couleur: '#3B82F6',
  commentaire: '',
  assignee_ids: [],
  label_ids: []
})

const coverImagePreview = ref(null)

const toggleLabel = (labelId) => {
  const index = formData.value.label_ids.indexOf(labelId)
  if (index > -1) {
    formData.value.label_ids.splice(index, 1)
  } else {
    formData.value.label_ids.push(labelId)
  }
}

const handleCoverImageUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    formData.value.cover_image = file
    // Create preview
    const reader = new FileReader()
    reader.onload = (e) => {
      coverImagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const loadUsers = async () => {
  try {
    const { data } = await api.get('/users')
    users.value = data.data || []
  } catch (error) {
    console.error('Error loading users:', error)
  }
}

const loadActivites = async () => {
  try {
    const { data } = await api.get('/activites')
    activites.value = data.data || []
  } catch (error) {
    console.error('Error loading activites:', error)
  }
}

const handleSubmit = async () => {
  loading.value = true
  errorMessage.value = ''
  validationErrors.value = []

  try {
    // Prepare data as FormData if there's a cover image
    let dataToSend = formData.value

    if (formData.value.cover_image instanceof File) {
      const formDataObj = new FormData()
      Object.keys(formData.value).forEach(key => {
        if (key === 'assignee_ids' || key === 'label_ids') {
          formData.value[key].forEach(id => {
            formDataObj.append(`${key}[]`, id)
          })
        } else if (formData.value[key] !== null && formData.value[key] !== '') {
          formDataObj.append(key, formData.value[key])
        }
      })
      dataToSend = formDataObj
    }

    if (props.tache) {
      await updateTache(props.tache.id, dataToSend)
    } else {
      await createTache(dataToSend)
    }
    emit('saved')
  } catch (error) {
    console.error(error)

    // Handle validation errors (422)
    if (error.response && error.response.status === 422) {
      const errors = error.response.data.errors
      if (errors) {
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
  try {
    await Promise.all([loadUsers(), loadActivites(), fetchLabels()])
  } catch (error) {
    console.error('Error loading form data:', error)
  }

  if (props.tache) {
    formData.value = {
      activite_id: props.tache.activite_id || '',
      titre: props.tache.titre || '',
      description: props.tache.description || '',
      objectif: props.tache.objectif || '',
      indicateurs_resultats: props.tache.indicateurs_resultats || '',
      statut: props.tache.statut || 'a_faire',
      priorite: props.tache.priorite || 'moyenne',
      echeance: props.tache.echeance || '',
      date_debut: props.tache.date_debut || '',
      date_fin_reelle: props.tache.date_fin_reelle || '',
      taux_realisation: props.tache.taux_realisation || 0,
      estimated_hours: props.tache.estimated_hours || null,
      actual_hours: props.tache.actual_hours || null,
      cover_image: null,
      couleur: props.tache.couleur || '#3B82F6',
      commentaire: props.tache.commentaire || '',
      assignee_ids: props.tache.assignees?.map(a => a.id) || [],
      label_ids: props.tache.labels?.map(l => l.id) || []
    }

    // Show existing cover image if available
    if (props.tache.cover_image) {
      coverImagePreview.value = props.tache.cover_image
    }
  }
})
</script>
