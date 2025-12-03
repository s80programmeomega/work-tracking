<!-- resources/js/components/dashboard/ActivityItem.vue -->
<template>
  <div class="flex items-start gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200 group">
    <div
      class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-brand-500 to-accent-500 flex items-center justify-center">
      <component :is="activity.icon" class="w-4 h-4 text-white" />
    </div>
    <div class="flex-1 min-w-0">
      <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-2">
        {{ activity.description }}
      </p>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
        {{ activity.timestamp }}
      </p>
    </div>
    <div v-if="activity.badge" class="flex-shrink-0">
      <span class="text-xs px-2 py-1 rounded-full" :class="activity.badge.class">
        {{ activity.badge.text }}
      </span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  activity: {
    type: Object,
    required: true
  }
})
</script>