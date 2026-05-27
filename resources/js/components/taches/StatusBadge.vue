<!-- resources/js/components/taches/StatusBadge.vue -->
<template>
  <span
    class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] font-medium rounded-1 border"
    :class="statusClasses">
    <span class="w-1.5 h-1.5 rounded-full" :class="dotClasses"></span>
    {{ statusLabel }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: {
    type: String,
    required: true,
    validator: (value) => ['a_faire', 'en_cours', 'termine'].includes(value)
  }
})

const statusConfig = {
  a_faire: {
    label: 'À faire',
    classes: 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
    dotClasses: 'bg-gray-400'
  },
  en_cours: {
    label: 'En cours',
    classes: 'bg-brand-50 text-brand-500 border-brand-200 dark:bg-brand-500/15 dark:text-brand-400 dark:border-brand-500/30',
    dotClasses: 'bg-brand-500 animate-pulse'
  },
  termine: {
    label: 'Terminé',
    classes: 'bg-success-50 text-success-500 border-success-300 dark:bg-success-500/15 dark:text-success-300 dark:border-success-500/30',
    dotClasses: 'bg-success-500'
  }
}

const statusClasses = computed(() => {
  return statusConfig[props.status]?.classes || statusConfig.a_faire.classes
})

const dotClasses = computed(() => {
  return statusConfig[props.status]?.dotClasses || statusConfig.a_faire.dotClasses
})

const statusLabel = computed(() => {
  return statusConfig[props.status]?.label || 'Inconnu'
})
</script>