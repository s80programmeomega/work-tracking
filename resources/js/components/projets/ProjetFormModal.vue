<!-- resources/js/components/projets/ProjetFormModal.vue -->
<template>
  <Teleport to="body">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden"
        @click.stop >
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
            {{ isEdit ? 'Modifier le projet' : 'Créer un nouveau projet' }}
          </h2>
          <button
            @click="$emit('close')"
            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <XIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Body -->
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
          <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Workspace Selection -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Workspace <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.workspace_id"
                required
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent"
              >
                <option value="">Sélectionner un workspace</option>
                <option
                  v-for="workspace in workspaces"
                  :key="workspace.id"
                  :value="workspace.id"
                >
                  {{ workspace.nom }}
                </option>
              </select>
            </div>

            <!-- Nom du projet -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Nom du projet <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.nom"
                type="text"
                required
                placeholder="Ex: Système de gestion RH"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
              />
            </div>

            <!-- Code (auto-généré ou manuel) -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Code du projet
              </label>
              <input
                v-model="form.code"
                type="text"
                placeholder="Auto-généré si vide"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
              />
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Laissez vide pour générer automatiquement
              </p>
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Description
              </label>
              <textarea
                v-model="form.description"
                rows="4"
                placeholder="Décrivez votre projet..."
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent resize-none"
              ></textarea>
            </div>

             <!-- Dates avec DatePicker personnalisé -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Date de début <span class="text-red-500">*</span>
            </label>
            <DatePicker
              v-model="form.date_debut"
              required
              :enable-time-picker="false"
              :is-required="true"
              auto-apply
              :format="'yyyy-MM-dd'"
              :locale="'fr'"
              :dark="isDark"
              class="w-full"
            >
              <template #input-icon>
                <CalendarIcon class="w-5 h-5 text-gray-400" />
              </template>
            </DatePicker>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Date de fin <span class="text-red-500">*</span>
            </label>
            <DatePicker
              v-model="form.date_fin"
              required
              :enable-time-picker="false"
              :is-required="true"
              auto-apply
              :format="'yyyy-MM-dd'"
              :locale="'fr'"
              :dark="isDark"
              :min-date="form.date_debut"
              class="w-full"
            >
              <template #input-icon>
                <CalendarIcon class="w-5 h-5 text-gray-400" />
              </template>
            </DatePicker>
          </div>
        </div>

            <!-- Responsable -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Responsable du projet <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.responsable_id"
                required
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent"
              >
                <option value="">Sélectionner un responsable</option>
                <option
                  v-for="user in users"
                  :key="user.id"
                  :value="user.id"
                >
                  {{ user.nom }} - {{ user.email }}
                </option>
              </select>
            </div>

            <!-- Status et Visibilité -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Statut
                </label>
                <select
                  v-model="form.status"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                >
                  <option value="active">Actif</option>
                  <option value="pending">En attente</option>
                  <option value="completed">Terminé</option>
                  <option value="archived">Archivé</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Visibilité
                </label>
                <select
                  v-model="form.visibility"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                >
                  <option value="public">Public - Visible par tous</option>
                  <option value="team">Équipe - Visible par les membres</option>
                  <option value="private">Privé - Visible uniquement par le responsable</option>
                </select>
              </div>
            </div>

            <!-- Budget -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Budget (optionnel)
              </label>
              <div class="relative">
                <input
                  v-model.number="form.budget"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="0.00"
                  class="w-full px-4 py-2 pl-12 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                />
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                  XAF
                </span>
              </div>
            </div>

            <!-- Couleur -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Couleur du projet
              </label>
              <div class="flex items-center gap-4">
                <input
                  v-model="form.couleur"
                  type="color"
                  class="w-16 h-10 rounded-lg border border-gray-300 dark:border-gray-600 cursor-pointer"
                />
                <input
                  v-model="form.couleur"
                  type="text"
                  placeholder="#3B82F6"
                  class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                />
              </div>
              <div class="flex gap-2 mt-3">
                <button
                  v-for="color in presetColors"
                  :key="color"
                  type="button"
                  @click="form.couleur = color"
                  :style="{ backgroundColor: color }"
                  :class="[
                    'w-8 h-8 rounded-lg border-2 transition-transform hover:scale-110',
                    form.couleur === color ? 'border-gray-900 dark:border-white scale-110' : 'border-transparent'
                  ]"
                ></button>
              </div>
            </div>

            <!-- Objectifs -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Objectifs du projet
              </label>
              <textarea
                v-model="form.objectifs"
                rows="3"
                placeholder="Décrivez les objectifs principaux du projet..."
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent resize-none"
              ></textarea>
            </div>

            <!-- Options -->
            <div class="space-y-3">
              <div class="flex items-center gap-3">
                <input
                  v-model="form.is_template"
                  type="checkbox"
                  id="is_template"
                  class="w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                />
                <label for="is_template" class="text-sm text-gray-700 dark:text-gray-300">
                  Utiliser comme modèle pour de futurs projets
                </label>
              </div>
              <div class="flex items-center gap-3">
                <input
                  v-model="form.is_favorite"
                  type="checkbox"
                  id="is_favorite"
                  class="w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                />
                <label for="is_favorite" class="text-sm text-gray-700 dark:text-gray-300">
                  Ajouter aux favoris
                </label>
              </div>
            </div>

            <!-- Error Message -->
            <div
              v-if="error"
              class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800"
            >
              <p class="text-sm text-red-800 dark:text-red-400">
                {{ error }}
              </p>
            </div>
          </form>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
          >
            Annuler
          </button>
          <button
            @click="handleSubmit"
            :disabled="loading"
            class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
          >
            <span v-if="loading" class="animate-spin">⏳</span>
            {{ isEdit ? 'Mettre à jour' : 'Créer le projet' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useProjets } from '@/composables/useProjets'
import { useWorkspace } from '@/composables/useWorkspace'
import { XIcon } from '@/icons'

import DatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import { CalendarIcon } from '@/icons'

const props = defineProps({
  projet: {
    type: Object,
    default: null
  }
})

const isDark = computed(() => document.documentElement.classList.contains('dark'))

const emit = defineEmits(['close', 'saved'])

const { createProjet, updateProjet } = useProjets()
const { fetchWorkspaces, fetchMembers } = useWorkspace()

const loading = ref(false)
const error = ref(null)
const workspaces = ref([])
const users = ref([])

const presetColors = [
  '#3B82F6', // Blue
  '#10B981', // Green
  '#8B5CF6', // Purple
  '#F59E0B', // Amber
  '#EF4444', // Red
  '#EC4899', // Pink
  '#14B8A6', // Teal
  '#F97316'  // Orange
]

const isEdit = computed(() => !!props.projet)

const form = ref({
  workspace_id: '',
  nom: '',
  code: '',
  description: '',
  date_debut: '',
  date_fin: '',
  responsable_id: '',
  status: 'active',
  visibility: 'team',
  couleur: '#3B82F6',
  budget: null,
  objectifs: '',
  is_template: false,
  is_favorite: false
})

// Initialize form with projet data if editing
watch(() => props.projet, (newProjet) => {
  if (newProjet) {
    form.value = {
      workspace_id: newProjet.workspace_id || '',
      nom: newProjet.nom || '',
      code: newProjet.code || '',
      description: newProjet.description || '',
      date_debut: newProjet.date_debut || '',
      date_fin: newProjet.date_fin || '',
      responsable_id: newProjet.responsable_id || '',
      status: newProjet.status || 'active',
      visibility: newProjet.visibility || 'team',
      couleur: newProjet.couleur || '#3B82F6',
      budget: newProjet.budget || null,
      objectifs: newProjet.objectifs || '',
      is_template: newProjet.is_template || false,
      is_favorite: newProjet.is_favorite || false
    }
  }
}, { immediate: true })

const handleSubmit = async () => {
  try {
    loading.value = true
    error.value = null

    // Validation
    if (!form.value.workspace_id) {
      error.value = 'Veuillez sélectionner un workspace'
      return
    }
    if (!form.value.nom) {
      error.value = 'Le nom du projet est requis'
      return
    }
    if (!form.value.date_debut) {
      error.value = 'La date de début est requise'
      return
    }
    if (!form.value.date_fin) {
      error.value = 'La date de fin est requise'
      return
    }
    if (!form.value.responsable_id) {
      error.value = 'Le responsable du projet est requis'
      return
    }

    // Check dates
    if (new Date(form.value.date_fin) < new Date(form.value.date_debut)) {
      error.value = 'La date de fin doit être après la date de début'
      return
    }

    // Create or update
    if (isEdit.value) {
      await updateProjet(props.projet.id, form.value)
    } else {
      await createProjet(form.value)
    }

    emit('saved')
  } catch (err) {
    error.value = err.response?.data?.message || 'Une erreur est survenue'
    console.error('Error saving projet:', err)
  } finally {
    loading.value = false
  }
}

// Load data
onMounted(async () => {
  try {
    // Load workspaces
    const workspacesResponse = await fetchWorkspaces()
    workspaces.value = workspacesResponse.data || []

    // Load users (members of workspace)
    if (form.value.workspace_id) {
      const usersResponse = await fetchMembers(form.value.workspace_id)
      users.value = usersResponse || []
    }

    // Set default workspace if creating new
    if (!isEdit.value && workspaces.value.length > 0) {
      form.value.workspace_id = workspaces.value[0].id
    }
  } catch (err) {
    console.error('Error loading data:', err)
  }
})

// Watch workspace change to load members
watch(() => form.value.workspace_id, async (newWorkspaceId) => {
  if (newWorkspaceId) {
    try {
      const usersResponse = await fetchMembers(newWorkspaceId)
      users.value = usersResponse || []
    } catch (err) {
      console.error('Error loading workspace members:', err)
    }
  }
})
</script>

<style scoped>
/* Solution CSS pour forcer l'affichage du calendrier */
.date-input {
  position: relative;
  z-index: 1;
}

/* S'assurer que le calendrier s'affiche au-dessus de la modal */
.date-input::-webkit-calendar-picker-indicator {
  background: transparent;
  bottom: 0;
  color: transparent;
  cursor: pointer;
  height: auto;
  left: 0;
  position: absolute;
  right: 0;
  top: 0;
  width: auto;
  z-index: 2;
}

/* Pour Firefox */
.date-input {
  position: relative;
}

.date-input:focus {
  z-index: 100000;
}
</style>