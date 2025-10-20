<template>
  <div class="kanban-board h-full">
    <div class="grid grid-cols-3 gap-4 h-full">
      <!-- À faire column -->
      <KanbanColumn
        title="À faire"
        statut="a_faire"
        v-model:taches="localAFaire"
        status-color="#6B7280"
        @add-task="$emit('add-task', 'a_faire')"
        @view-task="$emit('view-task', $event)"
        @edit-task="$emit('edit-task', $event)"
        @duplicate-task="$emit('duplicate-task', $event)"
        @archive-task="$emit('archive-task', $event)"
        @delete-task="$emit('delete-task', $event)"
      />

      <!-- En cours column -->
      <KanbanColumn
        title="En cours"
        statut="en_cours"
        v-model:taches="localEnCours"
        status-color="#3B82F6"
        @add-task="$emit('add-task', 'en_cours')"
        @view-task="$emit('view-task', $event)"
        @edit-task="$emit('edit-task', $event)"
        @duplicate-task="$emit('duplicate-task', $event)"
        @archive-task="$emit('archive-task', $event)"
        @delete-task="$emit('delete-task', $event)"
      />

      <!-- Terminé column -->
      <KanbanColumn
        title="Terminé"
        statut="termine"
        v-model:taches="localTermine"
        status-color="#10B981"
        @add-task="$emit('add-task', 'termine')"
        @view-task="$emit('view-task', $event)"
        @edit-task="$emit('edit-task', $event)"
        @duplicate-task="$emit('duplicate-task', $event)"
        @archive-task="$emit('archive-task', $event)"
        @delete-task="$emit('delete-task', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import KanbanColumn from './KanbanColumn.vue'

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
  'task-moved'
])

// Create local mutable refs that vuedraggable can modify
const localAFaire = ref([])
const localEnCours = ref([])
const localTermine = ref([])

// Watch incoming kanban prop and sync to local refs
watch(() => props.kanban, (newKanban) => {
  console.log('[KanbanBoard] Kanban prop changed, syncing to local refs')
  localAFaire.value = [...(newKanban.a_faire || [])]
  localEnCours.value = [...(newKanban.en_cours || [])]
  localTermine.value = [...(newKanban.termine || [])]
}, { immediate: true, deep: true })

// Watch local refs for changes and emit task-moved events
watch([localAFaire, localEnCours, localTermine], ([newAFaire, newEnCours, newTermine], [oldAFaire, oldEnCours, oldTermine]) => {
  console.log('[KanbanBoard] Local refs changed')
  console.log('A faire:', oldAFaire?.length, '->', newAFaire.length)
  console.log('En cours:', oldEnCours?.length, '->', newEnCours.length)
  console.log('Terminé:', oldTermine?.length, '->', newTermine.length)

  // Detect which task moved where
  if (oldAFaire && oldEnCours && oldTermine) {
    detectTaskMove(oldAFaire, newAFaire, oldEnCours, newEnCours, oldTermine, newTermine)
  }
}, { deep: true })

const detectTaskMove = (oldAFaire, newAFaire, oldEnCours, newEnCours, oldTermine, newTermine) => {
  // Find task that was removed from one column
  let movedTask = null
  let newStatut = null
  let newOrdre = 0

  // Check if a task was removed from a_faire
  const removedFromAFaire = oldAFaire.find(t => !newAFaire.find(nt => nt.id === t.id))
  if (removedFromAFaire) {
    // Check where it was added
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
    console.log('[KanbanBoard] Detected task move:', movedTask.titre, 'to', newStatut, 'at index', newOrdre)
    emit('task-moved', {
      tache: movedTask,
      newStatut: newStatut,
      newOrdre: newOrdre
    })
  }
}

const handleTaskMoved = (data) => {
  console.log('Task moved in KanbanBoard:', data)
  emit('task-moved', data)
}
</script>

<style scoped>
.kanban-board {
  min-height: 600px;
}
</style>
