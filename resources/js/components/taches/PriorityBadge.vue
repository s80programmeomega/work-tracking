<!-- resources/js/components/taches/PriorityBadge.vue -->
<template>
  <span
    class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] font-medium rounded-1 border"
    :class="priorityClasses">
    <svg v-if="priority === 'faible'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
    </svg>
    <svg v-else-if="priority === 'moyenne'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
    </svg>
    <svg v-else-if="priority === 'elevee'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
    </svg>
    <svg v-else class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
      <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
    </svg>
    {{ priorityLabel }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  priority: {
    type: String,
    required: true,
    validator: (value) => ['faible', 'moyenne', 'elevee', 'critique'].includes(value)
  }
})

const priorityConfig = {
  faible: {
    label: 'Faible',
    classes: 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700'
  },
  moyenne: {
    label: 'Moyenne',
    classes: 'bg-warning-50 text-warning-500 border-warning-300 dark:bg-warning-500/15 dark:text-warning-300 dark:border-warning-500/30'
  },
  elevee: {
    label: 'Élevée',
    classes: 'bg-brand-50 text-brand-500 border-brand-200 dark:bg-brand-500/15 dark:text-brand-400 dark:border-brand-500/30'
  },
  critique: {
    label: 'Critique',
    classes: 'bg-error-50 text-error-500 border-error-300 dark:bg-error-500/15 dark:text-error-300 dark:border-error-500/30'
  }
}

const priorityClasses = computed(() => {
  return priorityConfig[props.priority]?.classes || priorityConfig.faible.classes
})

const priorityLabel = computed(() => {
  return priorityConfig[props.priority]?.label || 'Inconnue'
})
</script>