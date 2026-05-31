<!-- resources/js/components/evaluations/RapportHebdomadaireStats.vue -->
<template>
  <div class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Statistiques — Rapport hebdomadaire</h3>
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
      <div ref="staggerRef" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total -->
        <div class="stagger-item rounded-3 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Total tâches</p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2 counter-pop">{{ statistics.total }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-3 flex items-center justify-center">
              <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Terminées -->
        <div class="stagger-item rounded-3 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Terminées</p>
              <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2 counter-pop">{{ statistics.completed }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-3 flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
          </div>
          <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <span class="text-xs text-gray-500 dark:text-gray-400">Taux de complétion</span>
              <span class="text-sm font-bold text-green-600 dark:text-green-400">{{ statistics.completion_rate }}%</span>
            </div>
            <div class="mt-1 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
              <div class="h-1.5 rounded-full bg-green-500 transition-all duration-700" :style="{ width: `${statistics.completion_rate}%` }"></div>
            </div>
          </div>
        </div>

        <!-- Validées N2 -->
        <div class="stagger-item rounded-3 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Validées N2</p>
              <p class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-2 counter-pop">{{ statistics.validated_n2 }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-3 flex items-center justify-center">
              <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
          </div>
          <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <span class="text-xs text-gray-500 dark:text-gray-400">Taux de validation</span>
              <span class="text-sm font-bold text-purple-600 dark:text-purple-400">{{ statistics.validation_rate }}%</span>
            </div>
          </div>
        </div>

        <!-- Temps réel -->
        <div class="stagger-item rounded-3 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Temps réel</p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2 counter-pop">{{ statistics.actual_hours }}h</p>
            </div>
            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-3 flex items-center justify-center">
              <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <span class="text-xs text-gray-500 dark:text-gray-400">Estimé: {{ statistics.estimated_hours }}h</span>
              <span
                class="text-sm font-bold"
                :class="statistics.time_variance > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'"
              >
                {{ statistics.time_variance > 0 ? '+' : '' }}{{ statistics.time_variance }}%
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import SkeletonLoader from '@/components/common/SkeletonLoader.vue'
import { useStagger } from '@/composables/useAnimations'

defineProps({
  statistics: { type: Object, required: true },
  loading: { type: Boolean, default: false },
})

defineEmits(['close'])

const { staggerRef, applyStagger } = useStagger(70)

onMounted(() => applyStagger())
</script>
