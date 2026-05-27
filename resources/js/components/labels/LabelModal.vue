<!-- resources\js\components\labels\LabelModal.vue -->
<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 " @click.self="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-3 max-w-2xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
      <!-- Header -->
      <div class="px-8 py-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-3 flex items-center justify-center ">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
          </div>
          <div class="flex-1">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ label ? 'Modifier le label' : 'Nouveau label' }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ label ? 'Mettre à jour le label' : 'Créer un nouveau label pour organiser vos tâches' }}
            </p>
          </div>
          <button
            @click="$emit('close')"
            class="p-2 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Error Alert -->
      <div v-if="errorMessage" class="mx-8 mt-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-3">
        <div class="flex items-start gap-3">
          <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div class="flex-1">
            <p class="font-medium text-red-800 dark:text-red-200">{{ errorMessage }}</p>
          </div>
        </div>
      </div>

      <!-- Form Body -->
      <div class="flex-1 overflow-y-auto px-8 py-6">
        <form @submit.prevent="handleSubmit" class="space-y-6">
          <!-- Section 1: Informations du label -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Informations du label
            </h3>

            <div>
              <label for="nom" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Nom <span class="text-red-500">*</span>
              </label>
              <input
                v-model="formData.nom"
                type="text"
                id="nom"
                required
                dusk="label-form-nom"
                placeholder="Ex: Urgent, En attente, Bug"
                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all"
              />
            </div>

            <div>
              <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Description
              </label>
              <textarea
                v-model="formData.description"
                id="description"
                rows="2"
                placeholder="Description optionnelle du label..."
                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all resize-none"
              ></textarea>
            </div>
          </div>

          <!-- Section 2: Apparence -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
              </svg>
              Apparence
            </h3>

            <div>
              <label for="couleur" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Couleur <span class="text-red-500">*</span>
              </label>
              <div class="flex gap-4 items-center">
                <input
                  v-model="formData.couleur"
                  type="color"
                  id="couleur"
                  required
                  class="h-12 w-20 rounded-3 border-2 border-gray-300 dark:border-gray-600 cursor-pointer"
                />
                <div class="flex-1">
                  <input
                    v-model="formData.couleur"
                    type="text"
                    placeholder="#3B82F6"
                    pattern="^#[0-9A-Fa-f]{6}$"
                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all"
                  />
                </div>
                <!-- Preview -->
                <div
                  class="px-6 py-3 rounded-3 font-semibold text-sm "
                  :style="{
                    backgroundColor: formData.couleur,
                    color: textColor
                  }"
                >
                  Aperçu
                </div>
              </div>
            </div>

            <!-- Color Presets -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Couleurs prédéfinies
              </label>
              <div class="grid grid-cols-8 gap-2">
                <button
                  v-for="color in colorPresets"
                  :key="color"
                  type="button"
                  @click="formData.couleur = color"
                  class="w-10 h-10 rounded-3 border-2 transition-transform"
                  :class="formData.couleur === color ? 'border-indigo-500 ring-2 ring-indigo-500/20' : 'border-gray-200 dark:border-gray-600'"
                  :style="{ backgroundColor: color }"
                  :title="color"
                ></button>
              </div>
            </div>
          </div>

          <!-- Section 3: Scope (Global vs Project) -->
          <div v-if="showScopeSelection" class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
              </svg>
              Portée du label
            </h3>

            <div class="grid grid-cols-2 gap-3">
              <label
                class="relative flex items-start gap-3 p-4 border-2 rounded-3 cursor-pointer transition-all "
                :class="!formData.projet_id
                  ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20'
                  : 'border-gray-300 dark:border-gray-600 hover:border-indigo-300 dark:hover:border-indigo-700'"
              >
                <input
                  type="radio"
                  :value="null"
                  v-model="formData.projet_id"
                  class="sr-only"
                />
                <span class="text-2xl">🌍</span>
                <div class="flex-1">
                  <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Label global
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    Disponible dans tous les projets
                  </div>
                </div>
                <svg
                  v-if="!formData.projet_id"
                  class="absolute right-3 top-3 w-5 h-5 text-indigo-500"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
              </label>

              <label
                class="relative flex items-start gap-3 p-4 border-2 rounded-3 cursor-pointer transition-all "
                :class="formData.projet_id
                  ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20'
                  : 'border-gray-300 dark:border-gray-600 hover:border-indigo-300 dark:hover:border-indigo-700'"
              >
                <input
                  type="radio"
                  :value="projetId"
                  v-model="formData.projet_id"
                  class="sr-only"
                />
                <span class="text-2xl">📁</span>
                <div class="flex-1">
                  <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Label spécifique
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    Uniquement pour ce projet
                  </div>
                </div>
                <svg
                  v-if="formData.projet_id"
                  class="absolute right-3 top-3 w-5 h-5 text-indigo-500"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
              </label>
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
            class="px-6 py-2.5 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-3 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all"
          >
            Annuler
          </button>
          <button
            type="submit"
            @click="handleSubmit"
            :disabled="loading"
            dusk="label-form-submit"
            class="px-6 py-2.5 text-white font-semibold rounded-3 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
          >
            <span v-if="loading" class="flex items-center gap-2">
              <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Enregistrement...
            </span>
            <span v-else>{{ label ? 'Mettre à jour' : 'Créer le label' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useLabels } from '../../composables/useLabels'

const props = defineProps({
  label: {
    type: Object,
    default: null
  },
  projetId: {
    type: Number,
    default: null
  },
  showScopeSelection: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['close', 'saved'])

const { createLabel, updateLabel } = useLabels()

const loading = ref(false)
const errorMessage = ref('')

const colorPresets = [
  '#EF4444', '#F59E0B', '#10B981', '#3B82F6',
  '#6366F1', '#8B5CF6', '#EC4899', '#F43F5E',
  '#14B8A6', '#06B6D4', '#84CC16', '#A855F7',
  '#D946EF', '#F97316', '#22C55E', '#0EA5E9'
]

const formData = reactive({
  nom: '',
  description: '',
  couleur: '#3B82F6',
  projet_id: props.projetId,
  ordre: 0
})

// Calculate text color based on background color
const textColor = computed(() => {
  const hex = formData.couleur.replace('#', '')
  const r = parseInt(hex.substr(0, 2), 16)
  const g = parseInt(hex.substr(2, 2), 16)
  const b = parseInt(hex.substr(4, 2), 16)
  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255
  return luminance > 0.5 ? '#000000' : '#FFFFFF'
})

const handleSubmit = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    if (props.label) {
      await updateLabel(props.label.id, formData)
    } else {
      await createLabel(formData)
    }
    emit('saved')
    emit('close')
  } catch (error) {
    errorMessage.value = error.message || 'Une erreur est survenue'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (props.label) {
    formData.nom = props.label.nom || ''
    formData.description = props.label.description || ''
    formData.couleur = props.label.couleur || '#3B82F6'
    formData.projet_id = props.label.projet_id
    formData.ordre = props.label.ordre || 0
  }
})
</script>
