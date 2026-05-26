<!-- resources/js/pages/dashboard/KanbanTaskCard.vue -->
<template>
  <div
    draggable="true"
    @dragstart="handleDragStart"
    @dragend="handleDragEnd"
    @click="$emit('click', task)"
    class="bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 p-4 shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer group hover:border-brand-300 dark:hover:border-brand-500"
    :class="{
      'border-l-4 border-l-red-500': task.priority === 'Élevée' || task.priorite === 'ELEVEE',
      'border-l-4 border-l-yellow-500': task.priority === 'Moyenne' || task.priorite === 'MOYENNE',
      'border-l-4 border-l-blue-500': task.priority === 'Faible' || task.priorite === 'FAIBLE',
      'ring-2 ring-brand-500': isDragging
    }"
  >
    <!-- Task Header -->
    <div class="flex items-start justify-between mb-2">
      <h4 class="font-medium text-gray-900 dark:text-white group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors line-clamp-2">
        {{ task.name || task.titre }}
      </h4>
      <div class="flex items-center gap-1 flex-shrink-0 ml-2">
        <button
          v-if="task.is_favorite"
          class="text-yellow-500 hover:text-yellow-600 transition-colors"
          @click.stop="toggleFavorite"
        >
          <StarIcon class="w-4 h-4 fill-current" />
        </button>
        <TaskMenu :task="task" @update="$emit('update', $event)" />
      </div>
    </div>

    <!-- Project & Team -->
    <div class="flex items-center gap-2 mb-3 text-xs text-gray-500 dark:text-gray-400">
      <span class="bg-gray-100 dark:bg-gray-600 px-2 py-1 rounded">
        {{ task.code || task.project_code }}
      </span>
      <span v-if="task.team || task.team_count" class="flex items-center gap-1">
        <UsersIcon class="w-3 h-3" />
        {{ task.team || task.team_count }}
      </span>
    </div>

    <!-- Progress Bar -->
    <div v-if="task.progress !== undefined || task.taux_realisation !== undefined" class="mb-3">
      <div class="flex items-center justify-between text-xs mb-1">
        <span class="text-gray-600 dark:text-gray-400">Progression</span>
        <span class="font-medium text-gray-900 dark:text-white">
          {{ (task.progress || task.taux_realisation || 0) }}%
        </span>
      </div>
      <div class="w-full h-2 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
        <div
          class="h-full rounded-full transition-all duration-500"
          :class="getProgressColor(task.progress || task.taux_realisation)"
          :style="{ width: `${task.progress || task.taux_realisation || 0}%` }"
        />
      </div>
    </div>

    <!-- Subtask badge -->
    <div
      v-if="(task.sous_taches_count || 0) > 0"
      dusk="st-badge-wrapper"
      class="mb-3 flex items-center gap-2"
    >
      <span dusk="st-badge" class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-gray-100 dark:bg-gray-700 text-xs font-medium text-gray-700 dark:text-gray-300">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
        </svg>
        {{ task.sous_taches_count }} ST
      </span>
    </div>

    <!-- Task Meta -->
    <div class="flex items-center justify-between text-xs">
      <div class="flex items-center gap-2 flex-wrap">
        <!-- Priority Badge -->
        <span 
          class="px-2 py-1 rounded-full font-medium capitalize"
          :class="getPriorityClass(task.priority || task.priorite)"
        >
          {{ getPriorityLabel(task.priority || task.priorite) }}
        </span>
        
        <!-- Due Date -->
        <span 
          v-if="task.due_date || task.echeance"
          class="flex items-center gap-1"
          :class="getDueDateClass(task.due_date || task.echeance)"
        >
          <CalendarIcon class="w-3 h-3" />
          {{ formatDueDate(task.due_date || task.echeance) }}
        </span>
      </div>

      <!-- Assignee Avatars -->
      <div v-if="task.preview_members" class="flex -space-x-2">
        <div
          v-for="member in task.preview_members.slice(0, 3)"
          :key="member.id"
          class="w-6 h-6 rounded-full border-2 border-white dark:border-gray-700 bg-gray-300 dark:bg-gray-600 flex items-center justify-center text-xs font-medium text-gray-700 dark:text-gray-300"
          :title="member.name || member.initials"
        >
          {{ member.initials }}
        </div>
        <div 
          v-if="task.additional_members"
          class="w-6 h-6 rounded-full border-2 border-white dark:border-gray-700 bg-gray-200 dark:bg-gray-500 flex items-center justify-center text-xs font-medium text-gray-600 dark:text-gray-400"
        >
          +{{ task.additional_members }}
        </div>
      </div>
    </div>

    <!-- Status Badge -->
    <div v-if="task.status" class="mt-2">
      <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium" 
            :class="getStatusClass(task.status)">
        {{ getStatusLabel(task.status) }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import TaskMenu from './TaskMenu.vue'
import {
  UsersIcon,
  CalendarIcon,
  StarIcon,
  CheckCircleIcon
} from '@/icons'

const props = defineProps({
  task: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['click', 'update'])

const isDragging = ref(false)

const handleDragStart = (e) => {
  isDragging.value = true
  e.dataTransfer.setData('text/plain', props.task.id)
  e.dataTransfer.effectAllowed = 'move'
}

const handleDragEnd = () => {
  isDragging.value = false
}

const getProgressColor = (progress) => {
  progress = progress || 0
  if (progress >= 90) return 'bg-green-500'
  if (progress >= 50) return 'bg-blue-500'
  if (progress > 0) return 'bg-yellow-500'
  return 'bg-gray-300 dark:bg-gray-500'
}

const getPriorityClass = (priority) => {
  const classes = {
    'Élevée': 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
    'ELEVEE': 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
    'Moyenne': 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
    'MOYENNE': 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
    'Faible': 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
    'FAIBLE': 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400'
  }
  return classes[priority] || 'bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300'
}

const getPriorityLabel = (priority) => {
  const labels = {
    'ELEVEE': 'Élevée',
    'MOYENNE': 'Moyenne',
    'FAIBLE': 'Faible'
  }
  return labels[priority] || priority
}

const getStatusClass = (status) => {
  const classes = {
    'active': 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
    'pending': 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
    'completed': 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
    'archived': 'bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300'
  }
  return classes[status] || 'bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300'
}

const getStatusLabel = (status) => {
  const labels = {
    'active': 'En cours',
    'pending': 'En attente',
    'completed': 'Terminé',
    'archived': 'Archivé'
  }
  return labels[status] || status
}

const getDueDateClass = (dueDate) => {
  if (!dueDate) return 'text-gray-500 dark:text-gray-400'
  
  const today = new Date()
  const due = new Date(dueDate)
  const diffDays = Math.ceil((due - today) / (1000 * 60 * 60 * 24))
  
  if (diffDays < 0) return 'text-red-600 dark:text-red-400'
  if (diffDays <= 2) return 'text-orange-600 dark:text-orange-400'
  return 'text-gray-500 dark:text-gray-400'
}

const formatDueDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short'
  })
}

const toggleFavorite = async () => {
  try {
    const updates = { is_favorite: !props.task.is_favorite }
    emit('update', updates)
  } catch (error) {
    console.error('Error toggling favorite:', error)
  }
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>