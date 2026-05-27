<!-- resources\js\components\dashboard\TaskCard.vue -->
<template>
  <div class="group bg-white dark:bg-gray-800 rounded-3 p-3 border border-gray-200 dark:border-gray-700 hover:border-brand-300 dark:hover:border-brand-600 transition-colors duration-200 cursor-pointer"
       :class="taskBorderClass">
    <div class="flex items-start justify-between mb-2">
      <h4 class="font-medium text-gray-900 dark:text-white group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors line-clamp-2 flex-1">
        {{ task.title }}
      </h4>
      <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-1 border ml-2" :class="priorityClass">
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
    return 'border-l-4 border-l-error-500 bg-error-50/30 dark:bg-error-500/10'
  }
  if (props.task.priority === 'Élevée') {
    return 'border-l-4 border-l-brand-500 bg-brand-50/30 dark:bg-brand-500/10'
  }
  return ''
})

const priorityClass = computed(() => {
  const classes = {
    'Élevée': 'bg-brand-50 text-brand-500 border-brand-200 dark:bg-brand-500/15 dark:text-brand-400 dark:border-brand-500/30',
    'Moyenne': 'bg-warning-50 text-warning-500 border-warning-300 dark:bg-warning-500/15 dark:text-warning-300 dark:border-warning-500/30',
    'Faible': 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
    'Critique': 'bg-error-50 text-error-500 border-error-300 dark:bg-error-500/15 dark:text-error-300 dark:border-error-500/30'
  }
  return classes[props.task.priority] || 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700'
})

const statusClass = computed(() => {
  const classes = {
    'Terminé': 'text-success-500 dark:text-success-300',
    'En cours': 'text-brand-500 dark:text-brand-400',
    'À faire': 'text-gray-500 dark:text-gray-400'
  }
  return classes[props.task.status] || 'text-gray-500 dark:text-gray-400'
})
</script>