<!-- resources/js/pages/dashboard/KanbanColumn.vue -->
<template>
  <div 
    class="flex-shrink-0 w-80 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
    @drop="handleDrop"
    @dragover="handleDragOver"
    @dragenter="handleDragEnter"
    @dragleave="handleDragLeave"
  >
    <!-- Column Header -->
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div class="w-3 h-3 rounded-full" :class="column.color"></div>
          <h3 class="font-semibold" :class="column.textColor">{{ column.title }}</h3>
          <span class="bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs font-medium px-2 py-1 rounded-full">
            {{ tasks.length }}
          </span>
        </div>
        <button
          @click="$emit('add-task', column.status)"
          class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
          title="Ajouter une tâche"
        >
          <PlusIcon class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Tasks List -->
    <div 
      class="p-2 space-y-2 max-h-[calc(100vh-300px)] overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-transparent"
      :class="{ 'bg-blue-50/50 dark:bg-blue-900/20': isDragOver }"
    >
      <KanbanTaskCard
        v-for="task in tasks"
        :key="task.id"
        :task="task"
        @click="$emit('task-click', task)"
        @update="(updates) => $emit('update-task', task.id, updates)"
      />
      
      <!-- Empty State -->
      <div 
        v-if="tasks.length === 0"
        class="text-center py-8 text-gray-400 dark:text-gray-500"
      >
        <div class="mb-2">
          <!-- Remplacement de DocumentTextIcon par FolderKanbanIcon -->
          <FolderKanbanIcon class="w-8 h-8 mx-auto opacity-50" />
        </div>
        <p class="text-sm">Aucun projet</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import KanbanTaskCard from './KanbanTaskCard.vue'
// Remplacer DocumentTextIcon par FolderKanbanIcon qui existe
import { PlusIcon, FolderKanbanIcon } from '@/icons'

const props = defineProps({
  column: {
    type: Object,
    required: true
  },
  tasks: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['task-click', 'task-drop', 'add-task', 'update-task'])

const isDragOver = ref(false)

const handleDragOver = (e) => {
  e.preventDefault()
  isDragOver.value = true
}

const handleDragEnter = (e) => {
  e.preventDefault()
  isDragOver.value = true
}

const handleDragLeave = (e) => {
  if (!e.currentTarget.contains(e.relatedTarget)) {
    isDragOver.value = false
  }
}

const handleDrop = (e) => {
  e.preventDefault()
  isDragOver.value = false
  
  const taskId = e.dataTransfer.getData('text/plain')
  if (taskId) {
    emit('task-drop', taskId, props.column.status)
  }
}
</script>