<template>
  <div class="flex flex-col h-full bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
    <!-- Column header -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-2">
        <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: statusColor }"></div>
        <h3 class="font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
        <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
          {{ taches.length }}
        </span>
      </div>

      <button
        v-if="canAdd"
        @click="$emit('add-task')"
        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        title="Ajouter une tâche"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
      </button>
    </div>

    <!-- Task list -->
    <div class="flex-1 overflow-y-auto min-h-[400px]">
      <div v-if="taches.length === 0" class="flex items-center justify-center h-full text-gray-400 dark:text-gray-600 text-sm">
        <div class="text-center">
          <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <p>Aucune tâche</p>
        </div>
      </div>

      <!-- Draggable list -->
      <draggable
        v-else
        :list="taches"
        :group="{ name: 'taches', pull: true, put: true }"
        item-key="id"
        class="space-y-3 min-h-full"
        :animation="200"
        ghost-class="opacity-50"
        @change="onChange"
        @start="onStart"
        @end="onEnd"
      >
        <template #item="{ element }">
          <div class="mb-3">
            <TacheCard
              :tache="element"
              @view="$emit('view-task', element)"
              @edit="$emit('edit-task', element)"
              @duplicate="$emit('duplicate-task', element)"
              @archive="$emit('archive-task', element)"
              @delete="$emit('delete-task', element)"
            />
          </div>
        </template>
      </draggable>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import draggable from 'vuedraggable'
import TacheCard from './TacheCard.vue'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  statut: {
    type: String,
    required: true
  },
  taches: {
    type: Array,
    default: () => []
  },
  statusColor: {
    type: String,
    default: '#6B7280'
  },
  canAdd: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits([
  'add-task',
  'view-task',
  'edit-task',
  'duplicate-task',
  'archive-task',
  'delete-task',
  'update:taches'
])

// v-model computed for two-way binding
const localTaches = computed({
  get: () => props.taches,
  set: (value) => {
    console.log(`[${props.statut}] localTaches SET - emitting update:taches`)
    emit('update:taches', value)
  }
})

const onStart = (event) => {
  console.log(`[${props.statut}] START drag - from index:`, event.oldIndex)
}

const onEnd = (event) => {
  console.log(`[${props.statut}] END drag - from:`, event.from, 'to:', event.to)
  console.log(`[${props.statut}] Indices - old:`, event.oldIndex, 'new:', event.newIndex)
}

const onChange = (event) => {
  console.log(`[${props.statut}] CHANGE event:`, event)

  if (event.added) {
    const { element, newIndex } = event.added
    console.log(`[${props.statut}] Task ADDED:`, element.titre, 'at index:', newIndex)
  } else if (event.moved) {
    const { element, newIndex } = event.moved
    console.log(`[${props.statut}] Task MOVED within column:`, element.titre, 'to index:', newIndex)
  } else if (event.removed) {
    const { element, oldIndex } = event.removed
    console.log(`[${props.statut}] Task REMOVED:`, element.titre, 'from index:', oldIndex)
  }
}
</script>

<style scoped>
.ghost-card {
  opacity: 0.5;
  background: #3B82F6;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: #9CA3AF;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #6B7280;
}
</style>
