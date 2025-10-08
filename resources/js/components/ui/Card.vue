<template>
  <div
    :class="[
      'rounded-lg border bg-white dark:bg-gray-800 dark:border-gray-700',
      className
    ]"
  >
    <slot />
  </div>
</template>

<script setup>
defineProps({
  className: {
    type: String,
    default: ''
  }
})
</script>
