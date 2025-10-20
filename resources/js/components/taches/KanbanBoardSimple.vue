<template>
  <div class="kanban-board h-full">
    <div class="grid grid-cols-3 gap-4 h-full">
      <!-- À faire column -->
      <div class="flex flex-col h-full bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-gray-500"></div>
            <h3 class="font-semibold text-gray-900 dark:text-white">À faire</h3>
            <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
              {{ localAFaire.length }}
            </span>
          </div>
          <button
            @click="$emit('add-task', 'a_faire')"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            title="Ajouter une tâche"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </button>
        </div>

        <div class="flex-1 overflow-y-auto min-h-[400px]">
          <draggable
            v-model="localAFaire"
            group="taches"
            item-key="id"
            class="space-y-3 min-h-full"
            :animation="200"
            ghost-class="opacity-50"
            @change="(e) => handleChange(e, 'a_faire')"
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
                  @validate="$emit('validate-task', element)"
                />
              </div>
            </template>
          </draggable>
        </div>
      </div>

      <!-- En cours column -->
      <div class="flex flex-col h-full bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-blue-500"></div>
            <h3 class="font-semibold text-gray-900 dark:text-white">En cours</h3>
            <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
              {{ localEnCours.length }}
            </span>
          </div>
          <button
            @click="$emit('add-task', 'en_cours')"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            title="Ajouter une tâche"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </button>
        </div>

        <div class="flex-1 overflow-y-auto min-h-[400px]">
          <draggable
            v-model="localEnCours"
            group="taches"
            item-key="id"
            class="space-y-3 min-h-full"
            :animation="200"
            ghost-class="opacity-50"
            @change="(e) => handleChange(e, 'en_cours')"
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
                  @validate="$emit('validate-task', element)"
                />
              </div>
            </template>
          </draggable>
        </div>
      </div>

      <!-- Terminé column -->
      <div class="flex flex-col h-full bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-green-500"></div>
            <h3 class="font-semibold text-gray-900 dark:text-white">Terminé</h3>
            <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
              {{ localTermine.length }}
            </span>
          </div>
          <button
            @click="$emit('add-task', 'termine')"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            title="Ajouter une tâche"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </button>
        </div>

        <div class="flex-1 overflow-y-auto min-h-[400px]">
          <draggable
            v-model="localTermine"
            group="taches"
            item-key="id"
            class="space-y-3 min-h-full"
            :animation="200"
            ghost-class="opacity-50"
            @change="(e) => handleChange(e, 'termine')"
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
                  @validate="$emit('validate-task', element)"
                />
              </div>
            </template>
          </draggable>
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
  kanban: {
    type: Object,
    required: true,
    default: () => ({
      a_faire: [],
      en_cours: [],
      termine: []
    })
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

// Create local mutable refs that vuedraggable can modify
const localAFaire = ref([])
const localEnCours = ref([])
const localTermine = ref([])

// Watch incoming kanban prop and sync to local refs
watch(() => props.kanban, (newKanban) => {
  localAFaire.value = [...(newKanban.a_faire || [])]
  localEnCours.value = [...(newKanban.en_cours || [])]
  localTermine.value = [...(newKanban.termine || [])]
}, { immediate: true, deep: true })

// Watch local refs for changes and emit task-moved events
watch([localAFaire, localEnCours, localTermine], ([newAFaire, newEnCours, newTermine], [oldAFaire, oldEnCours, oldTermine]) => {
  // Detect which task moved where
  if (oldAFaire && oldEnCours && oldTermine) {
    detectTaskMove(oldAFaire, newAFaire, oldEnCours, newEnCours, oldTermine, newTermine)
  }
}, { deep: true })

const handleChange = (event, statut) => {
  // Event handler for drag & drop changes
}

const detectTaskMove = (oldAFaire, newAFaire, oldEnCours, newEnCours, oldTermine, newTermine) => {
  let movedTask = null
  let newStatut = null
  let newOrdre = 0

  // Check if a task was removed from a_faire
  const removedFromAFaire = oldAFaire.find(t => !newAFaire.find(nt => nt.id === t.id))
  if (removedFromAFaire) {
    const addedToEnCours = newEnCours.find(t => t.id === removedFromAFaire.id)
    const addedToTermine = newTermine.find(t => t.id === removedFromAFaire.id)

    if (addedToEnCours) {
      movedTask = removedFromAFaire
      newStatut = 'en_cours'
      newOrdre = newEnCours.findIndex(t => t.id === removedFromAFaire.id)
    } else if (addedToTermine) {
      movedTask = removedFromAFaire
      newStatut = 'termine'
      newOrdre = newTermine.findIndex(t => t.id === removedFromAFaire.id)
    }
  }

  // Check if a task was removed from en_cours
  const removedFromEnCours = oldEnCours.find(t => !newEnCours.find(nt => nt.id === t.id))
  if (removedFromEnCours) {
    const addedToAFaire = newAFaire.find(t => t.id === removedFromEnCours.id)
    const addedToTermine = newTermine.find(t => t.id === removedFromEnCours.id)

    if (addedToAFaire) {
      movedTask = removedFromEnCours
      newStatut = 'a_faire'
      newOrdre = newAFaire.findIndex(t => t.id === removedFromEnCours.id)
    } else if (addedToTermine) {
      movedTask = removedFromEnCours
      newStatut = 'termine'
      newOrdre = newTermine.findIndex(t => t.id === removedFromEnCours.id)
    }
  }

  // Check if a task was removed from termine
  const removedFromTermine = oldTermine.find(t => !newTermine.find(nt => nt.id === t.id))
  if (removedFromTermine) {
    const addedToAFaire = newAFaire.find(t => t.id === removedFromTermine.id)
    const addedToEnCours = newEnCours.find(t => t.id === removedFromTermine.id)

    if (addedToAFaire) {
      movedTask = removedFromTermine
      newStatut = 'a_faire'
      newOrdre = newAFaire.findIndex(t => t.id === removedFromTermine.id)
    } else if (addedToEnCours) {
      movedTask = removedFromTermine
      newStatut = 'en_cours'
      newOrdre = newEnCours.findIndex(t => t.id === removedFromTermine.id)
    }
  }

  if (movedTask && newStatut) {
    emit('task-moved', {
      tache: movedTask,
      newStatut: newStatut,
      newOrdre: newOrdre
    })
  }
}
</script>

<style scoped>
.kanban-board {
  min-height: 600px;
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
