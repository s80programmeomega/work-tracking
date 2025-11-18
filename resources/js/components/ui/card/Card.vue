<!-- resources/js/components/ui/card/Card.vue -->
<template>
  <div 
    class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm"
    :class="[paddingClass, additionalClasses]"
  >
    <slot />
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  padding: {
    type: String,
    default: 'medium',
    validator: (value) => ['none', 'small', 'medium', 'large'].includes(value)
  },
  class: {
    type: String,
    default: ''
  }
})

const paddingClass = computed(() => {
  const classes = {
    'none': 'p-0',
    'small': 'p-4',
    'medium': 'p-6', 
    'large': 'p-8'
  }
  return classes[props.padding]
})

const additionalClasses = computed(() => props.class)
</script>