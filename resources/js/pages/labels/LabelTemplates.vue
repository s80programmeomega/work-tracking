<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
              <div class="w-12 h-12 rounded-3 flex items-center justify-center ">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
              </div>
              Templates de Labels
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
              Créez et gérez des ensembles de labels réutilisables pour vos projets
            </p>
          </div>

          <div class="flex gap-3">
            <button
              @click="$router.back()"
              class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-3 hover:bg-gray-300 dark:hover:bg-gray-600 transition-all flex items-center gap-2 font-medium"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Retour aux Labels
            </button>
            <button
              @click="openCreateTemplateModal"
              class="px-4 py-2.5 text-white rounded-3 transition-all flex items-center gap-2 font-medium"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Nouveau Template
            </button>
          </div>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-3 p-6 border-2 border-purple-100 dark:border-purple-900">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Templates</p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ templates.length }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-3 flex items-center justify-center">
              <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-3 p-6 border-2 border-green-100 dark:border-green-900">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Templates Prédéfinis</p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ predefinedTemplates.length }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-3 flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-3 p-6 border-2 border-amber-100 dark:border-amber-900">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Templates Personnalisés</p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ customTemplatesCount }}</p>
            </div>
            <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900 rounded-3 flex items-center justify-center">
              <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Predefined Templates Section -->
      <div class="bg-white dark:bg-gray-800 rounded-3 p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
          <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
          </svg>
          Templates Prédéfinis
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="template in predefinedTemplates"
            :key="template.nom"
            class="group relative p-5 border-2 border-gray-200 dark:border-gray-700 rounded-3 hover:border-green-300 dark:hover:border-green-700 transition-all"
          >
            <!-- Type Badge -->
            <span class="inline-block px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-semibold rounded-3 mb-3">
              {{ template.type_workflow.toUpperCase() }}
            </span>

            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ template.nom }}</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ template.description }}</p>

            <!-- Labels Preview -->
            <div class="flex flex-wrap gap-2 mb-4">
              <span
                v-for="(item, index) in template.items.slice(0, 3)"
                :key="index"
                class="inline-flex items-center px-3 py-1 rounded-3 text-xs font-medium "
                :style="{
                  backgroundColor: item.couleur,
                  color: getTextColor(item.couleur)
                }"
              >
                {{ item.nom }}
              </span>
              <span v-if="template.items.length > 3" class="inline-flex items-center px-3 py-1 rounded-3 text-xs font-medium bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                +{{ template.items.length - 3 }}
              </span>
            </div>

            <!-- Stats -->
            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-4">
              <span class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                {{ template.items.length }} labels
              </span>
            </div>

            <!-- Actions -->
            <button
              @click="applyPredefinedTemplate(template)"
              class="w-full px-4 py-2 text-white rounded-3 transition-all font-medium"
            >
              Utiliser ce template
            </button>
          </div>
        </div>
      </div>

      <!-- Custom Templates Section -->
      <div class="bg-white dark:bg-gray-800 rounded-3 p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
          <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
          </svg>
          Mes Templates ({{ customTemplatesCount }})
        </h2>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center py-12">
          <svg class="animate-spin h-12 w-12 text-purple-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </div>

        <!-- Templates List -->
        <div v-else-if="templates.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="template in templates"
            :key="template.id"
            class="group relative p-5 border-2 border-gray-200 dark:border-gray-700 rounded-3 hover:border-purple-300 dark:hover:border-purple-700 transition-all cursor-pointer"
            @click="openEditTemplateModal(template)"
          >
            <!-- Type Badge & Default Badge -->
            <div class="flex items-center justify-between mb-3">
              <span class="inline-block px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 text-xs font-semibold rounded-3">
                {{ template.type_workflow.toUpperCase() }}
              </span>
              <span v-if="template.is_default" class="inline-block px-3 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-semibold rounded-3">
                ⭐ Par défaut
              </span>
            </div>

            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ template.nom }}</h3>
            <p v-if="template.description" class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">{{ template.description }}</p>

            <!-- Labels Preview -->
            <div class="flex flex-wrap gap-2 mb-4">
              <span
                v-for="(item, index) in template.items.slice(0, 3)"
                :key="item.id"
                class="inline-flex items-center px-3 py-1 rounded-3 text-xs font-medium "
                :style="{
                  backgroundColor: item.couleur,
                  color: getTextColor(item.couleur)
                }"
              >
                {{ item.nom }}
              </span>
              <span v-if="template.items.length > 3" class="inline-flex items-center px-3 py-1 rounded-3 text-xs font-medium bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                +{{ template.items.length - 3 }}
              </span>
            </div>

            <!-- Stats -->
            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-4">
              <span class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                {{ template.items.length }} labels
              </span>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
              <button
                @click.stop="applyTemplate(template)"
                class="flex-1 px-3 py-2 bg-purple-500 text-white rounded-3 hover:bg-purple-600 transition-colors text-sm font-medium"
              >
                Appliquer
              </button>
              <button
                @click.stop="duplicateTemplate(template)"
                class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-3 transition-colors"
                title="Dupliquer"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
              </button>
              <button
                @click.stop="confirmDeleteTemplate(template)"
                class="p-2 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-500 rounded-3 transition-colors"
                title="Supprimer"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-16">
          <svg class="w-20 h-20 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
          <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Aucun template personnalisé</h3>
          <p class="text-gray-500 dark:text-gray-400 mb-6">
            Créez votre premier template personnalisé ou utilisez un template prédéfini
          </p>
          <button
            @click="openCreateTemplateModal"
            class="px-6 py-3 text-white rounded-3 transition-all font-medium"
          >
            Créer un template
          </button>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <LabelTemplateModal
      v-if="showTemplateModal"
      :template="selectedTemplate"
      @saved="onTemplateSaved"
      @close="closeTemplateModal"
    />

    <!-- Apply Template Modal -->
    <div
      v-if="showApplyModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 "
      @click.self="showApplyModal = false"
    >
      <div class="bg-white dark:bg-gray-800 rounded-3 max-w-md w-full mx-4">
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white">
            Appliquer le template "{{ templateToApply?.nom }}"
          </h3>
        </div>

        <div class="px-6 py-5">
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            {{ templateToApply?.items?.length }} label(s) seront créés comme labels globaux.
          </p>

          <div class="max-h-48 overflow-y-auto space-y-2 bg-gray-50 dark:bg-gray-900 p-4 rounded-3">
            <div
              v-for="item in templateToApply?.items"
              :key="item.nom"
              class="flex items-center justify-between"
            >
              <span class="text-sm text-gray-700 dark:text-gray-300">• {{ item.nom }}</span>
              <span
                class="inline-block w-4 h-4 rounded"
                :style="{ backgroundColor: item.couleur }"
              ></span>
            </div>
          </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
          <button
            @click="showApplyModal = false"
            class="px-4 py-2 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all font-medium"
          >
            Annuler
          </button>
          <button
            @click="confirmApplyTemplate"
            :disabled="applyingTemplate"
            class="px-4 py-2 text-white rounded-3 transition-all font-medium disabled:opacity-50"
          >
            {{ applyingTemplate ? 'Application...' : 'Appliquer' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useLabelTemplates } from '../../composables/useLabelTemplates'
import LabelTemplateModal from '../../components/labels/LabelTemplateModal.vue'

const {
  templates,
  predefinedTemplates,
  loading,
  fetchTemplates,
  fetchPredefinedTemplates,
  applyTemplateToProject: applyTemplateAction,
  duplicateTemplate: duplicateTemplateAction,
  deleteTemplate
} = useLabelTemplates()

const showTemplateModal = ref(false)
const selectedTemplate = ref(null)
const showApplyModal = ref(false)
const templateToApply = ref(null)
const applyingTemplate = ref(false)

// Computed
const customTemplatesCount = computed(() => templates.value.length)

// Methods
const getTextColor = (hex) => {
  const cleanHex = hex.replace('#', '')
  const r = parseInt(cleanHex.substr(0, 2), 16)
  const g = parseInt(cleanHex.substr(2, 2), 16)
  const b = parseInt(cleanHex.substr(4, 2), 16)
  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255
  return luminance > 0.5 ? '#000000' : '#FFFFFF'
}

const openCreateTemplateModal = () => {
  selectedTemplate.value = null
  showTemplateModal.value = true
}

const openEditTemplateModal = (template) => {
  selectedTemplate.value = template
  showTemplateModal.value = true
}

const closeTemplateModal = () => {
  showTemplateModal.value = false
  selectedTemplate.value = null
}

const onTemplateSaved = async () => {
  await fetchTemplates()
  closeTemplateModal()
}

const applyTemplate = (template) => {
  templateToApply.value = template
  showApplyModal.value = true
}

const applyPredefinedTemplate = (template) => {
  templateToApply.value = template
  showApplyModal.value = true
}

const confirmApplyTemplate = async () => {
  if (!templateToApply.value) return

  applyingTemplate.value = true
  try {
    // Pour les templates prédéfinis, on doit d'abord les sauvegarder
    if (!templateToApply.value.id) {
      // C'est un template prédéfini, on l'applique directement comme labels globaux
      const { createLabel } = await import('../../composables/useLabels')
      const { createLabel: createLabelFn } = createLabel()

      for (const item of templateToApply.value.items) {
        await createLabelFn({
          nom: item.nom,
          couleur: item.couleur,
          description: item.description || '',
          is_global: true,
          ordre: item.ordre
        })
      }
    } else {
      // C'est un template personnalisé
      await applyTemplateAction(templateToApply.value.id, null)
    }

    showApplyModal.value = false
    templateToApply.value = null
  } catch (error) {
    console.error('Erreur lors de l\'application du template:', error)
    alert('Erreur lors de l\'application du template')
  } finally {
    applyingTemplate.value = false
  }
}

const duplicateTemplate = async (template) => {
  try {
    await duplicateTemplateAction(template.id)
    await fetchTemplates()
  } catch (error) {
    console.error('Erreur lors de la duplication:', error)
  }
}

const confirmDeleteTemplate = async (template) => {
  if (confirm(`Êtes-vous sûr de vouloir supprimer le template "${template.nom}" ?`)) {
    try {
      await deleteTemplate(template.id)
      await fetchTemplates()
    } catch (error) {
      console.error('Erreur lors de la suppression:', error)
    }
  }
}

// Load data on mount
onMounted(async () => {
  await Promise.all([
    fetchTemplates(),
    fetchPredefinedTemplates()
  ])
})
</script>
