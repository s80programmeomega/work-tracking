<!-- resources/js/components/taches/KanbanBoardSimple.vue -->
<template>
  <div class="kanban-board">
    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-500 mx-auto mb-4"></div>
        <p class="text-gray-600 dark:text-gray-400">Chargement du tableau Kanban...</p>
      </div>
    </div>

    <!-- Kanban Columns -->
    <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Column: À faire -->
      <div class="kanban-column">
        <div class="column-header bg-gray-50 dark:bg-gray-800">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-gray-500"></div>
            <h3 class="font-semibold text-gray-900 dark:text-white">À faire</h3>
            <span class="px-2 py-1 text-xs font-medium bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full">
              {{ kanban.a_faire?.length || 0 }}
            </span>
          </div>
          <button
            v-if="canCreateTask"
            @click="$emit('add-task', 'a_faire')"
            class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors"
            title="Ajouter une tâche"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </button>
        </div>
        
        <draggable
          :list="kanban.a_faire"
          group="taches"
          item-key="id"
          class="column-content"
          @end="onDragEnd"
          :move="checkMove"
        >
          <template #item="{ element }">
            <TacheCard
              :tache="element"
              @view="$emit('view-task', $event)"
              @edit="$emit('edit-task', $event)"
              @duplicate="$emit('duplicate-task', $event)"
              @archive="$emit('archive-task', $event)"
              @delete="$emit('delete-task', $event)"
              @validate-n1="$emit('validate-task', { tache: $event, level: 'n1' })"
              @validate-n2="$emit('validate-task', { tache: $event, level: 'n2' })"
              @complete="handleCompleteTask(element)"
            />
          </template>
        </draggable>

        <!-- Empty State -->
        <div v-if="!kanban.a_faire?.length" class="empty-column">
          <div class="text-center py-8 text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <p class="text-sm">Aucune tâche</p>
          </div>
        </div>
      </div>

      <!-- Column: En cours -->
      <div class="kanban-column">
        <div class="column-header bg-blue-50 dark:bg-blue-900/20">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-blue-500"></div>
            <h3 class="font-semibold text-gray-900 dark:text-white">En cours</h3>
            <span class="px-2 py-1 text-xs font-medium bg-blue-200 dark:bg-blue-800 text-blue-700 dark:text-blue-300 rounded-full">
              {{ kanban.en_cours?.length || 0 }}
            </span>
          </div>
          <button
            v-if="canCreateTask"
            @click="$emit('add-task', 'en_cours')"
            class="p-1 text-blue-400 hover:text-blue-600 dark:hover:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-800 rounded transition-colors"
            title="Ajouter une tâche"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </button>
        </div>
        
        <draggable
          :list="kanban.en_cours"
          group="taches"
          item-key="id"
          class="column-content"
          @end="onDragEnd"
          :move="checkMove"
        >
          <template #item="{ element }">
            <TacheCard
              :tache="element"
              @view="$emit('view-task', $event)"
              @edit="$emit('edit-task', $event)"
              @duplicate="$emit('duplicate-task', $event)"
              @archive="$emit('archive-task', $event)"
              @delete="$emit('delete-task', $event)"
              @validate-n1="$emit('validate-task', { tache: $event, level: 'n1' })"
              @validate-n2="$emit('validate-task', { tache: $event, level: 'n2' })"
              @complete="handleCompleteTask(element)"
            />
          </template>
        </draggable>

        <!-- Empty State -->
        <div v-if="!kanban.en_cours?.length" class="empty-column">
          <div class="text-center py-8 text-blue-400">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <p class="text-sm">Aucune tâche en cours</p>
          </div>
        </div>
      </div>

      <!-- Column: Terminé -->
      <div class="kanban-column">
        <div class="column-header bg-green-50 dark:bg-green-900/20">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-green-500"></div>
            <h3 class="font-semibold text-gray-900 dark:text-white">Terminé</h3>
            <span class="px-2 py-1 text-xs font-medium bg-green-200 dark:bg-green-800 text-green-700 dark:text-green-300 rounded-full">
              {{ kanban.termine?.length || 0 }}
            </span>
          </div>
          <button
            v-if="canCreateTask"
            @click="$emit('add-task', 'termine')"
            class="p-1 text-green-400 hover:text-green-600 dark:hover:text-green-300 hover:bg-green-200 dark:hover:bg-green-800 rounded transition-colors"
            title="Ajouter une tâche"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </button>
        </div>
        
        <draggable
          :list="kanban.termine"
          group="taches"
          item-key="id"
          class="column-content"
          @end="onDragEnd"
          :move="checkMove"
        >
          <template #item="{ element }">
            <TacheCard
              :tache="element"
              @view="$emit('view-task', $event)"
              @edit="$emit('edit-task', $event)"
              @duplicate="$emit('duplicate-task', $event)"
              @archive="$emit('archive-task', $event)"
              @delete="$emit('delete-task', $event)"
              @validate-n1="$emit('validate-task', { tache: $event, level: 'n1' })"
              @validate-n2="$emit('validate-task', { tache: $event, level: 'n2' })"
            />
          </template>
        </draggable>

        <!-- Empty State -->
        <div v-if="!kanban.termine?.length" class="empty-column">
          <div class="text-center py-8 text-green-400">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm">Aucune tâche terminée</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Drag Overlay -->
    <div
      v-if="isDragging"
      class="fixed inset-0 bg-black bg-opacity-10 z-40 pointer-events-none"
    ></div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import draggable from 'vuedraggable'
import { useAuthStore } from '@/stores/authStore'
import { useTaches } from '@/composables/useTaches'

const props = defineProps({
  kanban: {
    type: Object,
    required: true,
    default: () => ({
      a_faire: [],
      en_cours: [],
      termine: []
    })
  },
  loading: {
    type: Boolean,
    default: false
  },
  activiteId: {
    type: Number,
    default: null
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

const authStore = useAuthStore()
const { moveTache, completeTache } = useTaches()

const isDragging = ref(false)

// Computed
const currentUser = computed(() => authStore.user)
const canCreateTask = computed(() => {
  // Vérifier les permissions de création basées sur l'activité
  // Cette logique devrait être basée sur les permissions réelles
  return true // À adapter selon votre système de permissions
})

// Methods
const onDragEnd = async (event) => {
  isDragging.value = false
  
  if (!event.item || !event.to) return

  const tache = event.item._underlying_vm_
  const newStatut = event.to.getAttribute('data-statut')
  const newOrdre = event.newIndex

  if (!tache || !newStatut) return

  try {
    // Émettre l'événement de déplacement
    emit('task-moved', {
      tache,
      newStatut,
      newOrdre,
      oldStatut: tache.statut,
      oldOrdre: event.oldIndex
    })
  } catch (error) {
    console.error('Error moving task:', error)
    // Revert the drag operation visually
    event.from.insertBefore(event.item, event.oldIndex >= 0 ? event.from.children[event.oldIndex] : null)
  }
}

const checkMove = (event) => {
  const tache = event.draggedContext.element
  const toStatut = event.to.getAttribute('data-statut')
  
  // Vérifier les permissions de modification
  if (!tache.permissions?.can_edit) {
    return false
  }

  // Empêcher le déplacement vers "Terminé" si la tâche ne peut pas être complétée
  if (toStatut === 'termine' && !tache.permissions?.can_complete) {
    return false
  }

  return true
}

const handleDragStart = () => {
  isDragging.value = true
}

const handleCompleteTask = async (tache) => {
  if (!tache.permissions?.can_complete) {
    alert('Vous n\'avez pas la permission de marquer cette tâche comme terminée')
    return
  }

  try {
    await completeTache(tache.id)
    // La tâche sera automatiquement mise à jour via le store
  } catch (error) {
    console.error('Error completing task:', error)
    alert(error.response?.data?.message || 'Erreur lors de la complétion de la tâche')
  }
}

// Add data-statut to draggable elements for move validation
const getDraggableProps = (statut) => ({
  'data-statut': statut
})
</script>

<style scoped>
@reference "tailwindcss";

.kanban-board {
  @apply min-h-screen;
}

.kanban-column {
  @apply flex flex-col h-full;
}

.column-header {
  @apply flex items-center justify-between p-4 rounded-t-lg border-b border-gray-200 dark:border-gray-700;
}

.column-content {
  @apply flex-1 p-4 space-y-4 min-h-[200px] max-h-[70vh] overflow-y-auto;
  border-left: 1px solid #e5e7eb;
  border-right: 1px solid #e5e7eb;
  border-bottom: 1px solid #e5e7eb;
  border-bottom-left-radius: 0.5rem;
  border-bottom-right-radius: 0.5rem;
}

.empty-column {
  @apply flex-1 p-4 border border-dashed border-gray-300 dark:border-gray-600 rounded-b-lg;
}

/* Custom scrollbar for column content */
.column-content::-webkit-scrollbar {
  width: 6px;
}

.column-content::-webkit-scrollbar-track {
  @apply bg-gray-100 dark:bg-gray-800 rounded;
}

.column-content::-webkit-scrollbar-thumb {
  @apply bg-gray-300 dark:bg-gray-600 rounded;
}

.column-content::-webkit-scrollbar-thumb:hover {
  @apply bg-gray-400 dark:bg-gray-500;
}

/* Drag and drop styles */
.sortable-chosen {
  @apply opacity-50;
}

.sortable-ghost {
  @apply opacity-30 bg-gray-100 dark:bg-gray-700 rounded-lg;
}

.sortable-drag {
  @apply transform rotate-3;
}
</style>