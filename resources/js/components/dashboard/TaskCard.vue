<!-- resources\js\components\dashboard\TaskCard.vue -->
<template>
  <div class="group bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all duration-300 cursor-pointer"
       :class="taskBorderClass">
    <div class="flex items-start justify-between mb-2">
      <h4 class="font-medium text-gray-900 dark:text-white group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors line-clamp-2 flex-1">
        {{ task.title }}
      </h4>
      <span class="text-xs font-medium px-2 py-1 rounded-full ml-2" :class="priorityClass">
        {{ task.priority }}
      </span>
    </div>

    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2 line-clamp-1">{{ task.project }}</p>

    <div class="flex items-center justify-between text-xs">
      <span class="text-gray-500 dark:text-gray-400 flex items-center gap-1">
        <ClockIcon class="w-3 h-3" />
        {{ task.due_date }}
      </span>
      <span class="font-medium" :class="statusClass">
        {{ task.status }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { ClockIcon } from '@/icons'
import { computed } from 'vue'

const props = defineProps({
  task: {
    type: Object,
    required: true
  }
})

const taskBorderClass = computed(() => {
  if (props.task.is_overdue) {
    return 'border-l-4 border-l-red-500 bg-red-50/50 dark:bg-red-900/10'
  }
  if (props.task.priority === 'Élevée') {
    return 'border-l-4 border-l-orange-500 bg-orange-50/50 dark:bg-orange-900/10'
  }
  return ''
})

const priorityClass = computed(() => {
  const classes = {
    'Élevée': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    'Moyenne': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
    'faible': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
  }
  return classes[props.task.priority] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
})

const statusClass = computed(() => {
  const classes = {
    'Terminé': 'text-green-600 dark:text-green-400',
    'En cours': 'text-blue-600 dark:text-blue-400',
    'À faire': 'text-gray-600 dark:text-gray-400'
  }
  return classes[props.task.status] || 'text-gray-600 dark:text-gray-400'
})
</script>