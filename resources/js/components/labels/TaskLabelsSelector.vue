<!-- resources\js\components\labels\TaskLabelsSelector.vue -->
<template>
  <div class="space-y-4">
    <!-- Current Labels Display -->
    <div>
      <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
        Labels de la tâche
      </label>
      <div class="flex flex-wrap gap-2 min-h-[2.5rem] p-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900/50">
        <span
          v-for="label in selectedLabelsObjects"
          :key="label.id"
          class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium shadow-sm transition-all hover:shadow-md"
          :style="{
            backgroundColor: label.couleur,
            color: label.text_color
          }"
        >
          {{ label.nom }}
          <button
            type="button"
            @click="removeLabel(label.id)"
            class="hover:scale-110 transition-transform"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </span>
        <span v-if="selectedLabelIds.length === 0" class="text-sm text-gray-400 dark:text-gray-500 italic">
          Aucun label sélectionné
        </span>
      </div>
    </div>

    <!-- Label Selector -->
    <div>
      <div class="flex items-center justify-between mb-2">
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
          Ajouter des labels
        </label>
        <button
          v-if="showCreateButton"
          type="button"
          @click="$emit('create-label')"
          class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 flex items-center gap-1"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Nouveau label
        </button>
      </div>

      <!-- Search Bar -->
      <div class="relative mb-3">
        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Rechercher un label..."
          class="w-full pl-10 pr-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all"
        />
      </div>

      <!-- Available Labels Grid -->
      <div class="grid grid-cols-2 gap-2 max-h-64 overflow-y-auto p-1">
        <button
          v-for="label in filteredAvailableLabels"
          :key="label.id"
          type="button"
          @click="toggleLabel(label.id)"
          class="flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium transition-all hover:scale-105 shadow-sm hover:shadow-md"
          :style="{
            backgroundColor: label.couleur,
            color: label.text_color,
            opacity: selectedLabelIds.includes(label.id) ? 0.5 : 1
          }"
          :disabled="selectedLabelIds.includes(label.id)"
        >
          <span class="flex items-center gap-2 truncate">
            <svg
              v-if="selectedLabelIds.includes(label.id)"
              class="w-4 h-4 flex-shrink-0"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            <span class="truncate">{{ label.nom }}</span>
          </span>
          <span v-if="label.usage_count > 0" class="flex-shrink-0 text-xs opacity-75">
            {{ label.usage_count }}
          </span>
        </button>

        <!-- Empty State -->
        <div v-if="filteredAvailableLabels.length === 0" class="col-span-2 text-center py-8">
          <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
          </svg>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ searchQuery ? 'Aucun label trouvé' : 'Aucun label disponible' }}
          </p>
        </div>
      </div>

      <!-- Label Scope Filter (if multiple scopes available) -->
      <div v-if="showScopeFilter" class="mt-3 flex gap-2">
        <button
          v-for="scope in scopes"
          :key="scope.value"
          type="button"
          @click="currentScope = scope.value"
          class="flex-1 px-3 py-2 text-xs font-medium rounded-lg transition-colors"
          :class="currentScope === scope.value
            ? 'bg-indigo-500 text-white'
            : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'"
        >
          {{ scope.label }}
        </button>
      </div>
    </div>

    <!-- Quick Actions -->
    <div v-if="selectedLabelIds.length > 0" class="flex justify-between items-center pt-2">
      <span class="text-xs text-gray-500 dark:text-gray-400">
        {{ selectedLabelIds.length }} label(s) sélectionné(s)
      </span>
      <button
        type="button"
        @click="clearAllLabels"
        class="text-xs font-medium text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300"
      >
        Tout effacer
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useLabels } from '../../composables/useLabels'

const props = defineProps({
  tacheId: {
    type: Number,
    default: null
  },
  projetId: {
    type: Number,
    default: null
  },
  modelValue: {
    type: Array,
    default: () => []
  },
  showCreateButton: {
    type: Boolean,
    default: true
  },
  showScopeFilter: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'change', 'create-label'])

const { labels, fetchLabels, fetchLabelsForProject } = useLabels()

const searchQuery = ref('')
const currentScope = ref('all') // 'all', 'global', 'project'
const selectedLabelIds = ref([...props.modelValue])

const scopes = [
  { value: 'all', label: 'Tous' },
  { value: 'global', label: 'Globaux' },
  { value: 'project', label: 'Projet' }
]

// Filter labels based on search and scope
const filteredAvailableLabels = computed(() => {
  let filtered = labels.value || []

  // Filter by scope
  if (currentScope.value === 'global') {
    filtered = filtered.filter(label => label.is_global)
  } else if (currentScope.value === 'project') {
    filtered = filtered.filter(label => !label.is_global && label.projet_id === props.projetId)
  }

  // Filter by search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(label =>
      label.nom.toLowerCase().includes(query) ||
      (label.description && label.description.toLowerCase().includes(query))
    )
  }

  // Sort by usage count and name
  return filtered.sort((a, b) => {
    if (b.usage_count !== a.usage_count) {
      return b.usage_count - a.usage_count
    }
    return a.nom.localeCompare(b.nom)
  })
})

// Get selected labels objects
const selectedLabelsObjects = computed(() => {
  return selectedLabelIds.value
    .map(id => labels.value?.find(label => label.id === id))
    .filter(Boolean)
})

// Toggle label selection
const toggleLabel = (labelId) => {
  if (selectedLabelIds.value.includes(labelId)) {
    selectedLabelIds.value = selectedLabelIds.value.filter(id => id !== labelId)
  } else {
    selectedLabelIds.value.push(labelId)
  }
}

// Remove a label
const removeLabel = (labelId) => {
  selectedLabelIds.value = selectedLabelIds.value.filter(id => id !== labelId)
}

// Clear all labels
const clearAllLabels = () => {
  selectedLabelIds.value = []
}

// Watch for changes and emit
watch(selectedLabelIds, (newValue) => {
  emit('update:modelValue', newValue)
  emit('change', newValue)
}, { deep: true })

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  selectedLabelIds.value = [...newValue]
}, { deep: true })

// Load labels on mount
onMounted(async () => {
  if (props.projetId) {
    await fetchLabelsForProject(props.projetId)
  } else {
    await fetchLabels()
  }
})
</script>
