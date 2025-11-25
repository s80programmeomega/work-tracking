<!-- resources/js/components/taches/KanbanColumnPersonal.vue -->
<template>
  <div class="kanban-column-personal">
    <div class="flex flex-col h-full bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-800 rounded-2xl p-6 border-2 border-gray-200 dark:border-gray-700 shadow-sm">
      <!-- Column header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white text-lg font-bold shadow-lg" :style="{ backgroundColor: statusColor }">
            {{ statusIcon }}
          </div>
          <div>
            <h3 class="font-bold text-gray-900 dark:text-white text-lg">{{ title }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ filteredTaches.length }} tâche{{ filteredTaches.length !== 1 ? 's' : '' }}</p>
          </div>
        </div>

        <!-- Actions rapides pour la vue personnelle -->
        <div class="flex items-center gap-2">
          <button
            v-if="statut === 'a_faire' && filteredTaches.length > 0"
            @click="startAllTasks"
            class="w-10 h-10 rounded-xl bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-green-600 hover:text-green-700 hover:border-green-300 dark:hover:border-green-600 flex items-center justify-center transition-all hover:scale-110 shadow-sm"
            title="Démarrer toutes les tâches"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Task list -->
      <div class="flex-1 overflow-y-auto space-y-4 min-h-[500px] max-h-[70vh] custom-scrollbar">
        <div v-if="filteredTaches.length === 0" class="flex items-center justify-center h-full text-gray-400 dark:text-gray-600">
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
          :group="{ name: 'personal-tasks', pull: true, put: true }"
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
              <TacheCardPersonal
                :tache="element"
                @view="$emit('view-task', element)"
                @move="handleMoveTask"
                @submit-result="$emit('submit-result', element)"
              />
            </div>
          </template>
        </draggable>
      </div>

      <!-- Column footer -->
      <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-center text-sm text-gray-500 dark:text-gray-400">
          <span>Glissez pour réorganiser</span>
          <span class="font-medium" :style="{ color: statusColor }">{{ filteredTaches.length }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import draggable from 'vuedraggable'
import TacheCardPersonal from './TacheCardPersonal.vue'

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
  }
})

const emit = defineEmits([
  'view-task',
  'submit-result',
  'my-card-moved'
])

// Filtrer les tâches selon le statut personnel
const filteredTaches = computed(() => {
  return props.taches.filter(tache => {
    return tache.my_status?.statut === props.statut
  })
})

// Local mutable copy for draggable
const localTaches = ref([...filteredTaches.value])

// Sync with filtered taches
watch(filteredTaches, (newTaches) => {
  console.log(`🔄 Synchronisation colonne ${props.statut}:`, newTaches.length, 'tâches')
  localTaches.value = [...newTaches]
}, { deep: true, immediate: true })

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
    
    // Émettre l'événement pour déplacement de carte personnelle
    emit('my-card-moved', {
      tache: element,
      newStatut: props.statut,
      progression: getProgressionForStatus(props.statut),
      oldStatut: element.my_status?.statut || element.statut
    })
  }
}

// Démarrer toutes les tâches de la colonne
function startAllTasks() {
  localTaches.value.forEach(tache => {
    if (tache.my_status?.statut === 'a_faire') {
      emit('my-card-moved', {
        tache,
        newStatut: 'en_cours',
        progression: 50
      })
    }
  })
}

function handleMoveTask({ tache, newStatut, progression, notes }) {
  emit('my-card-moved', { 
    tache, 
    newStatut, 
    progression, 
    notes_personnelles: notes 
  })
}

function getProgressionForStatus(status) {
  const progressMap = {
    'a_faire': 0,
    'en_cours': 50,
    'termine': 100
  }
  return progressMap[status] || 0
}
</script>

<style scoped>
.kanban-column-personal {
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