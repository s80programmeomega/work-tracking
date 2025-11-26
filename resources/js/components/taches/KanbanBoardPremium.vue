<!-- resources/js/components/taches/KanbanBoardPremium.vue -->
<template>
  <div class="kanban-board-premium">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 h-full">
      <!-- À faire column -->
      <KanbanColumnPremium
        title="À faire"
        statut="a_faire"
        :taches="kanban.a_faire"
        status-color="#6B7280"
        status-icon="📋"
        :can-add="canCreateTask"
        @add-task="$emit('add-task', 'a_faire')"
        @view-task="$emit('view-task', $event)"
        @edit-task="$emit('edit-task', $event)"
        @duplicate-task="$emit('duplicate-task', $event)"
        @archive-task="$emit('archive-task', $event)"
        @delete-task="$emit('delete-task', $event)"
        @validate-task="$emit('validate-task', $event)"
        @task-moved="handleTaskMoved"
      />

      <!-- En cours column -->
      <KanbanColumnPremium
        title="En cours"
        statut="en_cours"
        :taches="kanban.en_cours"
        status-color="#3B82F6"
        status-icon="⚡"
        :can-add="canCreateTask"
        @add-task="$emit('add-task', 'en_cours')"
        @view-task="$emit('view-task', $event)"
        @edit-task="$emit('edit-task', $event)"
        @duplicate-task="$emit('duplicate-task', $event)"
        @archive-task="$emit('archive-task', $event)"
        @delete-task="$emit('delete-task', $event)"
        @validate-task="$emit('validate-task', $event)"
        @task-moved="handleTaskMoved"
      />

      <!-- Terminé column -->
      <KanbanColumnPremium
        title="Terminé"
        statut="termine"
        :taches="kanban.termine"
        status-color="#10B981"
        status-icon="✅"
        :can-add="canCreateTask"
        @add-task="$emit('add-task', 'termine')"
        @view-task="$emit('view-task', $event)"
        @edit-task="$emit('edit-task', $event)"
        @duplicate-task="$emit('duplicate-task', $event)"
        @archive-task="$emit('archive-task', $event)"
        @delete-task="$emit('delete-task', $event)"
        @validate-task="$emit('validate-task', $event)"
        @task-moved="handleTaskMoved"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import KanbanColumnPremium from './KanbanColumnPremium.vue'

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

const currentUser = computed(() => {
  const userStr = localStorage.getItem('user')
  return userStr ? JSON.parse(userStr) : null
})

const canCreateTask = computed(() => {
  return !!props.activiteId && !!currentUser.value
})

const handleTaskMoved = (data) => {
  emit('task-moved', data)
}
</script>

<style scoped>
.kanban-board-premium {
  min-height: 70vh;
}
</style>