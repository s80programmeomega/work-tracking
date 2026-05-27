<!-- resources/js/components/admin/SubscriptionBadge.vue -->
<template>
  <span :class="classes" class="px-2 py-0.5 rounded-full text-xs font-medium capitalize">
    {{ mode }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  mode: { type: String, required: true },
});

const classes = computed(() => ({
  trial:  'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
  paid:   'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  free:   'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
}[props.mode] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'));
</script>
