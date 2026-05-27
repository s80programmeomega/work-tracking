<template>
  <div class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-4">
    <h4 class="font-semibold text-gray-900 dark:text-white mb-3">
      Labels ({{ labels?.length || 0 }})
    </h4>
    
    <div v-if="labels && labels.length > 0" class="flex flex-wrap gap-2">
      <span
        v-for="label in labels"
        :key="label.id"
        class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium transition-all "
        :style="{
          backgroundColor: label.couleur + '20',
          color: label.couleur,
          border: `1px solid ${label.couleur}40`
        }"
      >
        <span class="w-2 h-2 rounded-full mr-2" :style="{ backgroundColor: label.couleur }"></span>
        {{ label.nom }}
      </span>
    </div>

    <div v-else class="text-center py-4">
      <svg class="w-10 h-10 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
      </svg>
      <p class="text-sm text-gray-500 dark:text-gray-400">Aucun label</p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  labels: {
    type: Array,
    default: () => []
  }
})
</script>