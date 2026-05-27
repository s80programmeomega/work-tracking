<template>
  <div
    :class="[
      'rounded-3 border border-gray-200 bg-white shadow-none dark:bg-gray-800 dark:border-gray-700',
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
