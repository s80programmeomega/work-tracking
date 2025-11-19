<!-- resources/js/components/taches/KanbanBoardSimple.vue - VERSION FINALE CORRIGÉE -->
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
              {{ localKanban.a_faire.length }}
            </span>
          </div>
          <button
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
          v-model="localKanban.a_faire"
          group="taches"
          item-key="id"
          class="column-content"
          :data-statut="'a_faire'"
          @start="handleDragStart"
          @end="handleDragEnd"
          :move="checkMove"
          :animation="200"
          ghost-class="ghost-card"
          drag-class="dragging-card"
        >
          <template #item="{ element }">
            <div class="mb-3" :data-tache-id="element.id">
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

        <div v-if="localKanban.a_faire.length === 0" class="empty-column">
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
              {{ localKanban.en_cours.length }}
            </span>
          </div>
          <button
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
          v-model="localKanban.en_cours"
          group="taches"
          item-key="id"
          class="column-content"
          :data-statut="'en_cours'"
          @start="handleDragStart"
          @end="handleDragEnd"
          :move="checkMove"
          :animation="200"
          ghost-class="ghost-card"
          drag-class="dragging-card"
        >
          <template #item="{ element }">
            <div class="mb-3" :data-tache-id="element.id">
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

        <div v-if="localKanban.en_cours.length === 0" class="empty-column">
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
              {{ localKanban.termine.length }}
            </span>
          </div>
          <button
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
          v-model="localKanban.termine"
          group="taches"
          item-key="id"
          class="column-content"
          :data-statut="'termine'"
          @start="handleDragStart"
          @end="handleDragEnd"
          :move="checkMove"
          :animation="200"
          ghost-class="ghost-card"
          drag-class="dragging-card"
        >
          <template #item="{ element }">
            <div class="mb-3" :data-tache-id="element.id">
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

        <div v-if="localKanban.termine.length === 0" class="empty-column">
          <div class="text-center py-8 text-green-400">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm">Aucune tâche terminée</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ✅ CORRECTION : Overlay subtil au lieu de complètement opaque -->
    <div
      v-if="isDragging"
      class="fixed inset-0 bg-blue-500/5 backdrop-blur-[2px] pointer-events-none z-10"
    ></div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import draggable from 'vuedraggable'
import TacheCard from './TacheCard.vue'

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

const isDragging = ref(false)

// ✅ Copie locale du kanban
const localKanban = ref({
  a_faire: [],
  en_cours: [],
  termine: []
})

// ✅ Synchroniser avec les props
watch(() => props.kanban, (newKanban) => {
  console.log('🔄 Synchronisation Kanban', {
    a_faire: newKanban.a_faire?.length || 0,
    en_cours: newKanban.en_cours?.length || 0,
    termine: newKanban.termine?.length || 0
  })
  
  localKanban.value = {
    a_faire: Array.isArray(newKanban.a_faire) ? [...newKanban.a_faire] : [],
    en_cours: Array.isArray(newKanban.en_cours) ? [...newKanban.en_cours] : [],
    termine: Array.isArray(newKanban.termine) ? [...newKanban.termine] : []
  }
}, { immediate: true, deep: true })

// ✅ CORRECTION : handleDragStart
const handleDragStart = (event) => {
  isDragging.value = true
  console.log('🖱️ Drag start', {
    element: event.item.getAttribute('data-tache-id')
  })
}

// ✅ CORRECTION CRITIQUE : handleDragEnd avec récupération correcte de l'ID
const handleDragEnd = (event) => {
  isDragging.value = false
  
  if (!event.item || !event.to) {
    console.warn('⚠️ Données de drag manquantes')
    return
  }

  // Récupérer le statut de destination
  const newStatut = event.to.getAttribute('data-statut')
  const newOrdre = event.newIndex
  const oldStatut = event.from.getAttribute('data-statut')

  console.log('📦 Drag end:', {
    to_statut: newStatut,
    from_statut: oldStatut,
    new_index: newOrdre,
    old_index: event.oldIndex
  })

  // ✅ MÉTHODE 1 : Récupérer l'ID depuis le wrapper parent
  let tacheId = event.item.getAttribute('data-tache-id')
  
  // ✅ MÉTHODE 2 : Si pas trouvé, chercher dans les enfants
  if (!tacheId) {
    const wrapper = event.item.querySelector('[data-tache-id]')
    if (wrapper) {
      tacheId = wrapper.getAttribute('data-tache-id')
    }
  }

  // ✅ MÉTHODE 3 : Si toujours pas trouvé, utiliser le nouvel index
  if (!tacheId) {
    const tache = localKanban.value[newStatut][newOrdre]
    if (tache) {
      tacheId = tache.id
    }
  }

  if (!tacheId) {
    console.error('❌ Impossible de trouver l\'ID de la tâche')
    return
  }

  // Trouver la tâche complète
  let tache = null
  for (const statut of ['a_faire', 'en_cours', 'termine']) {
    tache = localKanban.value[statut].find(t => t.id == tacheId)
    if (tache) break
  }

  if (!tache) {
    console.error('❌ Tâche non trouvée dans le kanban local')
    return
  }

  console.log('✅ Tâche trouvée:', {
    id: tache.id,
    titre: tache.titre,
    old_statut: oldStatut,
    new_statut: newStatut
  })

  // ✅ Émettre l'événement
  emit('task-moved', {
    tache,
    newStatut,
    newOrdre,
    oldStatut,
    oldOrdre: event.oldIndex
  })
}

// ✅ Vérification avant déplacement
const checkMove = (event) => {
  const tache = event.draggedContext.element
  
  if (!tache) {
    console.warn('⚠️ Pas de tâche dans le contexte de drag')
    return false
  }
  
  // Vérifier les permissions
  if (tache.permissions && !tache.permissions.can_edit) {
    console.warn('⚠️ Permission refusée pour cette tâche')
    return false
  }

  return true
}
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
  @apply flex-1 p-4 min-h-[200px] max-h-[70vh] overflow-y-auto;
  border-left: 1px solid #e5e7eb;
  border-right: 1px solid #e5e7eb;
  border-bottom: 1px solid #e5e7eb;
  border-bottom-left-radius: 0.5rem;
  border-bottom-right-radius: 0.5rem;
}

.empty-column {
  @apply flex-1 p-4 border border-dashed border-gray-300 dark:border-gray-600 rounded-b-lg;
}

/* Scrollbar personnalisée */
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

/* ✅ CORRECTION : Styles de drag & drop améliorés */
.ghost-card {
  @apply opacity-40 bg-blue-100 dark:bg-blue-900/30 border-2 border-blue-400 border-dashed rounded-lg;
}

.dragging-card {
  @apply transform rotate-3 scale-105 shadow-2xl opacity-80 cursor-grabbing;
}

/* Animation lors du drop */
.column-content > div {
  transition: transform 0.2s ease, opacity 0.2s ease;
}
</style>