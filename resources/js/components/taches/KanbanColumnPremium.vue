<!-- resources/js/components/taches/KanbanColumnPremium.vue -->
<template>
  <div class="kanban-column-premium">
    <div class="flex flex-col h-full bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-800 rounded-2xl p-6 border-2 border-gray-200 dark:border-gray-700 shadow-sm">
      <!-- Column header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white text-lg font-bold shadow-lg" :style="{ backgroundColor: statusColor }">
            {{ statusIcon }}
          </div>
          <div>
            <h3 class="font-bold text-gray-900 dark:text-white text-lg">{{ title }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ taches.length }} tâche{{ taches.length !== 1 ? 's' : '' }}</p>
          </div>
        </div>

        <button
          v-if="canAdd"
          @click="$emit('add-task')"
          class="w-10 h-10 rounded-xl bg-white dark:bg-gray-800 border-2 border-dashed border-gray-300 dark:border-gray-600 text-gray-400 hover:text-brand-500 hover:border-brand-300 dark:hover:border-brand-600 flex items-center justify-center transition-all hover:scale-110 shadow-sm"
          title="Ajouter une tâche"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
        </button>
      </div>

      <!-- Task list -->
      <div class="flex-1 overflow-y-auto space-y-4 min-h-[500px] max-h-[70vh] custom-scrollbar">
        <div v-if="taches.length === 0" class="flex items-center justify-center h-full text-gray-400 dark:text-gray-600">
          <div class="text-center py-12">
            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-800 rounded-2xl flex items-center justify-center">
              <svg class="w-8 h-8 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <p class="text-sm">Aucune tâche</p>
            <p class="text-xs mt-1">Glissez une tâche ici</p>
          </div>
        </div>

        <!-- Draggable task list -->
        <draggable
          v-else
          :list="localTaches"
          :group="{ name: 'taches', pull: true, put: true }"
          item-key="id"
          class="space-y-4 min-h-full"
          :animation="200"
          ghost-class="ghost-card"
          drag-class="dragging-card"
          @start="onDragStart"
          @end="onDragEnd"
          @change="onDragChange"
        >
          <template #item="{ element }">
            <div class="transform transition-all duration-300 hover:scale-[1.02]">
              <TacheCard
                :tache="element"
                @view="$emit('view-task', element)"
                @edit="$emit('edit-task', element)"
                @duplicate="$emit('duplicate-task', element)"
                @archive="$emit('archive-task', element)"
                @delete="$emit('delete-task', element)"
                @validate="$emit('validate-task', element)"
              />
            </div>
          </template>
        </draggable>
      </div>

      <!-- Column footer -->
      <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-center text-sm text-gray-500 dark:text-gray-400">
          <span>Glissez pour réorganiser</span>
          <span class="font-medium" :style="{ color: statusColor }">{{ taches.length }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
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
  statusIcon: {
    type: String,
    default: '📋'
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
  'validate-task',
  'task-moved'
])

// Local mutable copy for draggable
const localTaches = ref([...props.taches])

// Sync with props
watch(() => props.taches, (newTaches) => {
  localTaches.value = [...newTaches]
}, { deep: true })

const onDragStart = (event) => {
  console.log(`[${props.statut}] Drag start:`, event.item.textContent)
  event.item.classList.add('dragging')
}

const onDragEnd = (event) => {
  console.log(`[${props.statut}] Drag end`)
  event.item.classList.remove('dragging')
}

const onDragChange = (event) => {
  if (event.added) {
    const { element, newIndex } = event.added
    console.log(`[${props.statut}] Task added:`, element.titre, 'at index:', newIndex)
    
    emit('task-moved', {
      tache: element,
      newStatut: props.statut,
      newOrdre: newIndex,
      oldStatut: getSourceColumn(event),
      oldOrdre: event.added.oldIndex
    })
  } else if (event.moved) {
    const { element, newIndex } = event.moved
    console.log(`[${props.statut}] Task moved within column:`, element.titre, 'to index:', newIndex)
    
    emit('task-moved', {
      tache: element,
      newStatut: props.statut,
      newOrdre: newIndex,
      oldStatut: props.statut,
      oldOrdre: event.moved.oldIndex
    })
  }
}

const getSourceColumn = (event) => {
  // This would need to be implemented based on your drag context
  // For now, we'll return the current statut for moves within the same column
  return props.statut
}
</script>

<style scoped>
.kanban-column-premium {
  height: 100%;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb {
  background: #475569;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #64748b;
}

.ghost-card {
  opacity: 0.6;
  background: #f1f5f9;
  border: 2px dashed #cbd5e1;
  border-radius: 0.75rem;
}

.dragging-card {
  transform: rotate(5deg);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
</style>