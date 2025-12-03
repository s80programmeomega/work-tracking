<!-- resources/js/components/taches/StatusBadge.vue -->
<template>
  <span 
    class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full"
    :class="statusClasses">
    <span class="w-2 h-2 rounded-full" :class="dotClasses"></span>
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
    classes: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    dotClasses: 'bg-gray-500'
  },
  en_cours: {
    label: 'En cours',
    classes: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    dotClasses: 'bg-blue-500 animate-pulse'
  },
  termine: {
    label: 'Terminé',
    classes: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    dotClasses: 'bg-green-500'
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