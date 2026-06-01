<!-- resources/js/components/taches/TachesStats.vue -->
<template>
  <div class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Statistiques des tâches</h3>
      <button
        @click="$emit('close')"
        class="rounded-3 p-1 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <SkeletonLoader v-if="loading" type="stats" :cols="4" />

    <div v-else class="space-y-6">
      <div ref="staggerRef" class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="stagger-item rounded-3 border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-3">
          <div class="w-10 h-10 rounded-3 bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
            <div class="w-3 h-3 rounded-full bg-gray-500"></div>
          </div>
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Total</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white counter-pop">{{ stats.total }}</p>
          </div>
        </div>

        <div class="stagger-item rounded-3 border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-3">
          <div class="w-10 h-10 rounded-3 bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
            <div class="w-3 h-3 rounded-full bg-gray-500"></div>
          </div>
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">À faire</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white counter-pop">{{ stats.a_faire }}</p>
          </div>
        </div>

        <div class="stagger-item rounded-3 border border-blue-200 dark:border-blue-800/50 p-4 flex items-center gap-3">
          <div class="w-10 h-10 rounded-3 bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
            <div class="w-3 h-3 rounded-full bg-blue-500"></div>
          </div>
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">En cours</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 counter-pop">{{ stats.en_cours }}</p>
          </div>
        </div>

        <div class="stagger-item rounded-3 border border-green-200 dark:border-green-800/50 p-4 flex items-center gap-3">
          <div class="w-10 h-10 rounded-3 bg-green-100 dark:bg-green-900/20 flex items-center justify-center">
            <div class="w-3 h-3 rounded-full bg-green-500"></div>
          </div>
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Terminé</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400 counter-pop">{{ stats.termine }}</p>
          </div>
        </div>
      </div>

      <!-- Completion circle + bar -->
      <div class="rounded-3 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-4 flex items-center gap-6">
        <!-- Circular progress -->
        <div class="w-16 h-16 relative flex-shrink-0">
          <svg class="transform -rotate-90 w-16 h-16" viewBox="0 0 36 36">
            <path class="text-gray-200 dark:text-gray-700" stroke="currentColor" stroke-width="3" fill="none"
              d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
            <path class="text-brand-500" stroke="currentColor" stroke-width="3" fill="none"
              :stroke-dasharray="`${completionRate}, 100`"
              d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
          </svg>
          <div class="absolute inset-0 flex items-center justify-center">
            <span class="text-xs font-bold text-brand-600 dark:text-brand-400">{{ completionRate }}%</span>
          </div>
        </div>
        <div class="flex-1">
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Taux de complétion</p>
          <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
            <div class="h-2 rounded-full bg-brand-500 transition-all duration-700 ease-out" :style="{ width: `${completionRate}%` }"></div>
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ stats.termine }} terminées sur {{ stats.total }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import SkeletonLoader from '@/components/common/SkeletonLoader.vue'
import { useStagger } from '@/composables/useAnimations'

const props = defineProps({
  stats: { type: Object, required: true },
  loading: { type: Boolean, default: false },
})

defineEmits(['close'])

const { staggerRef, applyStagger } = useStagger(60)

const completionRate = computed(() => {
  if (!props.stats.total) { return 0 }
  return Math.round((props.stats.termine / props.stats.total) * 100)
})

onMounted(() => applyStagger())
</script>
