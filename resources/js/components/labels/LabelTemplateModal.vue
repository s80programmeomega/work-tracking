<!-- resources\js\components\labels\LabelTemplateModal.vue -->
<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="$emit('close')">
    <div ref="dialogRef" :style="dragStyle" class="bg-white dark:bg-gray-800 rounded-3 max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
      <!-- Header -->
      <div ref="handleRef" class="px-8 py-6 border-b border-gray-200 dark:border-gray-700 cursor-move select-none">
          <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-3 flex items-center justify-center ">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
          </div>
          <div class="flex-1">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ template ? 'Modifier le template' : 'Nouveau template de labels' }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ template ? 'Mettre à jour le template' : 'Créer un set de labels réutilisable' }}
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
          <!-- Section 1: Informations du template -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Informations du template
            </h3>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label for="nom" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Nom <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="formData.nom"
                  type="text"
                  id="nom"
                  required
                  placeholder="Ex: Agile/Scrum"
                  class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all"
                />
              </div>

              <div>
                <label for="type_workflow" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Type de workflow <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="formData.type_workflow"
                  id="type_workflow"
                  required
                  class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all"
                >
                  <option value="agile">Agile</option>
                  <option value="kanban">Kanban</option>
                  <option value="waterfall">Waterfall</option>
                  <option value="custom">Personnalisé</option>
                </select>
              </div>
            </div>

            <div>
              <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Description
              </label>
              <textarea
                v-model="formData.description"
                id="description"
                rows="2"
                placeholder="Description du template..."
                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all resize-none"
              ></textarea>
            </div>

            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="formData.is_default"
                  type="checkbox"
                  class="w-4 h-4 text-purple-600 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded focus:ring-purple-500"
                />
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Définir comme template par défaut
                </span>
              </label>
            </div>
          </div>

          <!-- Section 2: Labels du template -->
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                Labels ({{ formData.items.length }})
              </h3>
              <button
                type="button"
                @click="addItem"
                class="px-4 py-2 bg-purple-500 text-white rounded-3 hover:bg-purple-600 transition-colors flex items-center gap-2 text-sm font-medium"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Ajouter un label
              </button>
            </div>

            <!-- Items List -->
            <div class="space-y-3 max-h-96 overflow-y-auto">
              <div
                v-for="(item, index) in formData.items"
                :key="index"
                class="p-4 border-2 border-gray-200 dark:border-gray-700 rounded-3 bg-gray-50 dark:bg-gray-900/50 hover:border-purple-300 dark:hover:border-purple-700 transition-all"
              >
                <div class="grid grid-cols-12 gap-3 items-start">
                  <!-- Order -->
                  <div class="col-span-1 flex items-center justify-center mt-3">
                    <span class="text-sm font-bold text-gray-400">#{{ index + 1 }}</span>
                  </div>

                  <!-- Nom -->
                  <div class="col-span-4">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nom</label>
                    <input
                      v-model="item.nom"
                      type="text"
                      required
                      placeholder="Ex: À faire"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20"
                    />
                  </div>

                  <!-- Couleur -->
                  <div class="col-span-3">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Couleur</label>
                    <div class="flex gap-2">
                      <input
                        v-model="item.couleur"
                        type="color"
                        class="h-9 w-12 rounded border border-gray-300 dark:border-gray-600 cursor-pointer"
                      />
                      <input
                        v-model="item.couleur"
                        type="text"
                        pattern="^#[0-9A-Fa-f]{6}$"
                        class="flex-1 px-2 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-800 text-xs text-gray-900 dark:text-white focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20"
                      />
                    </div>
                  </div>

                  <!-- Preview -->
                  <div class="col-span-3 flex items-end h-full">
                    <div
                      class="w-full px-3 py-2 rounded-3 text-xs font-semibold text-center"
                      :style="{
                        backgroundColor: item.couleur,
                        color: getTextColor(item.couleur)
                      }"
                    >
                      {{ item.nom || 'Aperçu' }}
                    </div>
                  </div>

                  <!-- Delete -->
                  <div class="col-span-1 flex items-center justify-center mt-8">
                    <button
                      type="button"
                      @click="removeItem(index)"
                      class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-3 transition-colors"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </div>

                <!-- Description (optional) -->
                <div class="mt-2">
                  <input
                    v-model="item.description"
                    type="text"
                    placeholder="Description (optionnelle)"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-800 text-xs text-gray-900 dark:text-white focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20"
                  />
                </div>
              </div>

              <!-- Empty State -->
              <div v-if="formData.items.length === 0" class="text-center py-12">
                <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                <p class="text-gray-500 dark:text-gray-400 font-medium mb-2">Aucun label dans ce template</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mb-4">Ajoutez des labels pour créer votre template</p>
                <button
                  type="button"
                  @click="addItem"
                  class="px-4 py-2 bg-purple-500 text-white rounded-3 hover:bg-purple-600 transition-colors text-sm font-medium"
                >
                  Ajouter le premier label
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- Footer -->
      <div class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
        <div class="flex justify-between items-center">
          <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ formData.items.length }} label(s) dans ce template
          </p>
          <div class="flex gap-3">
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
              :disabled="loading || formData.items.length === 0"
              class="px-6 py-2.5 text-white font-semibold rounded-3 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
            >
              <span v-if="loading" class="flex items-center gap-2">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Enregistrement...
              </span>
              <span v-else>{{ template ? 'Mettre à jour' : 'Créer le template' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useDraggable } from '@/composables/useDraggable'

const { dialogRef, handleRef, dragStyle, attachHandle } = useDraggable()
onMounted(attachHandle)
import { useLabelTemplates } from '../../composables/useLabelTemplates'

const props = defineProps({
  template: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

const { createTemplate, updateTemplate } = useLabelTemplates()

const loading = ref(false)
const errorMessage = ref('')

const formData = reactive({
  nom: '',
  description: '',
  type_workflow: 'agile',
  is_default: false,
  items: []
})

const addItem = () => {
  formData.items.push({
    nom: '',
    couleur: '#3B82F6',
    description: '',
    ordre: formData.items.length
  })
}

const removeItem = (index) => {
  formData.items.splice(index, 1)
  // Reorder
  formData.items.forEach((item, idx) => {
    item.ordre = idx
  })
}

const getTextColor = (hexColor) => {
  const hex = hexColor.replace('#', '')
  const r = parseInt(hex.substr(0, 2), 16)
  const g = parseInt(hex.substr(2, 2), 16)
  const b = parseInt(hex.substr(4, 2), 16)
  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255
  return luminance > 0.5 ? '#000000' : '#FFFFFF'
}

const handleSubmit = async () => {
  if (formData.items.length === 0) {
    errorMessage.value = 'Veuillez ajouter au moins un label au template'
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    if (props.template) {
      await updateTemplate(props.template.id, formData)
    } else {
      await createTemplate(formData)
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
  if (props.template) {
    formData.nom = props.template.nom || ''
    formData.description = props.template.description || ''
    formData.type_workflow = props.template.type_workflow || 'agile'
    formData.is_default = props.template.is_default || false

    if (props.template.items && props.template.items.length > 0) {
      formData.items = props.template.items.map((item, index) => ({
        nom: item.nom || '',
        couleur: item.couleur || '#3B82F6',
        description: item.description || '',
        ordre: item.ordre !== undefined ? item.ordre : index
      }))
    }
  } else {
    // Add 3 default items for new templates
    addItem()
    addItem()
    addItem()
  }
})
</script>
