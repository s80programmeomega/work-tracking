<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div>
      <!-- Header -->
      <div class="mb-8">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div class="min-w-0">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
              <div class="w-12 h-12 rounded-3 flex items-center justify-center ">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
              </div>
              Gestion des Labels
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
              Organisez vos tâches avec des labels personnalisés
            </p>
          </div>

          <div class="flex flex-wrap gap-3">
            <button
              @click="showTemplateModal = true"
              class="px-4 py-2.5 bg-purple-500 text-white rounded-3 hover:bg-purple-600 transition-all flex items-center gap-2 font-medium"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
              Templates
            </button>
            <button
              @click="openCreateLabelModal"
              dusk="open-create-label-btn"
              class="px-4 py-2.5 text-white rounded-3 transition-all flex items-center gap-2 font-medium"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Nouveau Label
            </button>
          </div>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-3 p-6 border-2 border-indigo-100 dark:border-indigo-900">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Labels</p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ labels.length }}</p>
            </div>
            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-3 flex items-center justify-center">
              <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-3 p-6 border-2 border-green-100 dark:border-green-900">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Labels Globaux</p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ globalLabelsCount }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-3 flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-3 p-6 border-2 border-purple-100 dark:border-purple-900">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Labels Projet</p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ projectLabelsCount }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-3 flex items-center justify-center">
              <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-3 p-6 border-2 border-amber-100 dark:border-amber-900">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Utilisations</p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ totalUsageCount }}</p>
            </div>
            <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900 rounded-3 flex items-center justify-center">
              <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-3 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Search -->
          <div class="relative">
            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher un label..."
              class="w-full pl-10 pr-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all"
            />
          </div>

          <!-- Scope Filter -->
          <select
            v-model="scopeFilter"
            class="px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all"
          >
            <option value="all">Tous les labels</option>
            <option value="global">Labels globaux</option>
            <option value="project">Labels de projet</option>
          </select>

          <!-- Sort -->
          <select
            v-model="sortBy"
            class="px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all"
          >
            <option value="name">Trier par nom</option>
            <option value="usage">Trier par utilisation</option>
            <option value="recent">Plus récents</option>
          </select>
        </div>
      </div>

      <!-- Labels Grid -->
      <div class="bg-white dark:bg-gray-800 rounded-3 p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
          <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
          </svg>
          Labels ({{ filteredLabels.length }})
        </h2>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center py-12">
          <svg class="animate-spin h-12 w-12 text-indigo-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </div>

        <!-- Labels List -->
        <div v-else-if="filteredLabels.length > 0" ref="gridRef" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="label in filteredLabels"
            :key="label.id"
            :dusk="`label-card-${label.id}`"
            class="stagger-item group relative p-5 border-2 border-gray-200 dark:border-gray-700 rounded-3 hover:border-indigo-300 dark:hover:border-indigo-700 transition-all cursor-pointer"
            @click="openEditLabelModal(label)"
          >
            <!-- Label Badge -->
            <div class="flex items-start justify-between mb-4">
              <span
                class="inline-flex items-center px-4 py-2 rounded-3 text-sm font-semibold "
                :style="{
                  backgroundColor: label.couleur,
                  color: label.text_color
                }"
              >
                {{ label.nom }}
              </span>

              <!-- Scope Badge -->
              <span
                class="text-xs px-2 py-1 rounded-full font-medium"
                :class="label.is_global
                  ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
                  : 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400'"
              >
                {{ label.is_global ? '🌍 Global' : '📁 Projet' }}
              </span>
            </div>

            <!-- Description -->
            <p v-if="label.description" class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
              {{ label.description }}
            </p>

            <!-- Stats -->
            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
              <div class="flex items-center gap-4">
                <span class="flex items-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                  {{ label.usage_count }} tâches
                </span>
              </div>

              <!-- Actions -->
              <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <button
                  @click.stop="duplicateLabel(label)"
                  class="p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-3 transition-colors"
                  title="Dupliquer"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  </svg>
                </button>
                <button
                  @click.stop="confirmDeleteLabel(label)"
                  class="p-1.5 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-500 rounded-3 transition-colors"
                  title="Supprimer"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-16">
          <svg class="w-20 h-20 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
          </svg>
          <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Aucun label trouvé</h3>
          <p class="text-gray-500 dark:text-gray-400 mb-6">
            {{ searchQuery ? 'Aucun label ne correspond à votre recherche' : 'Commencez par créer votre premier label' }}
          </p>
          <button
            @click="openCreateLabelModal"
            class="px-6 py-3 text-white rounded-3 transition-all font-medium"
          >
            Créer un label
          </button>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <LabelModal
      v-if="showLabelModal"
      :label="selectedLabel"
      :projet-id="currentProjetId"
      @saved="onLabelSaved"
      @close="closeLabelModal"
    />

    <LabelTemplateModal
      v-if="showTemplateModal"
      @saved="onTemplateSaved"
      @close="showTemplateModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useLabels } from '../../composables/useLabels'
import { useStagger } from '@/composables/useAnimations'
import LabelModal from '../../components/labels/LabelModal.vue'
import LabelTemplateModal from '../../components/labels/LabelTemplateModal.vue'

const { labels, loading, fetchLabels, duplicateLabel: duplicateLabelAction, deleteLabel } = useLabels()
const { staggerRef: gridRef, applyStagger } = useStagger(50)

const searchQuery = ref('')
const scopeFilter = ref('all')
const sortBy = ref('name')
const showLabelModal = ref(false)
const showTemplateModal = ref(false)
const selectedLabel = ref(null)
const currentProjetId = ref(null)

// Computed stats
const globalLabelsCount = computed(() =>
  labels.value.filter(l => l.is_global).length
)

const projectLabelsCount = computed(() =>
  labels.value.filter(l => !l.is_global).length
)

const totalUsageCount = computed(() =>
  labels.value.reduce((sum, label) => sum + (label.usage_count || 0), 0)
)

// Filtered and sorted labels
const filteredLabels = computed(() => {
  let filtered = labels.value

  // Filter by scope
  if (scopeFilter.value === 'global') {
    filtered = filtered.filter(l => l.is_global)
  } else if (scopeFilter.value === 'project') {
    filtered = filtered.filter(l => !l.is_global)
  }

  // Filter by search
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(l =>
      l.nom.toLowerCase().includes(query) ||
      (l.description && l.description.toLowerCase().includes(query))
    )
  }

  // Sort
  if (sortBy.value === 'usage') {
    filtered = [...filtered].sort((a, b) => (b.usage_count || 0) - (a.usage_count || 0))
  } else if (sortBy.value === 'name') {
    filtered = [...filtered].sort((a, b) => a.nom.localeCompare(b.nom))
  } else if (sortBy.value === 'recent') {
    filtered = [...filtered].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
  }

  return filtered
})

// Methods
const openCreateLabelModal = () => {
  selectedLabel.value = null
  showLabelModal.value = true
}

const openEditLabelModal = (label) => {
  selectedLabel.value = label
  showLabelModal.value = true
}

const closeLabelModal = () => {
  showLabelModal.value = false
  selectedLabel.value = null
}

const onLabelSaved = async () => {
  await fetchLabels()
  closeLabelModal()
}

const onTemplateSaved = () => {
  showTemplateModal.value = false
}

const duplicateLabel = async (label) => {
  try {
    await duplicateLabelAction(label.id)
    await fetchLabels()
  } catch (error) {
    console.error('Erreur lors de la duplication:', error)
  }
}

const confirmDeleteLabel = async (label) => {
  if (confirm(`Êtes-vous sûr de vouloir supprimer le label "${label.nom}" ?`)) {
    try {
      await deleteLabel(label.id)
      await fetchLabels()
    } catch (error) {
      console.error('Erreur lors de la suppression:', error)
    }
  }
}

// Load labels on mount
onMounted(async () => {
  await fetchLabels()
  applyStagger()
})
</script>
