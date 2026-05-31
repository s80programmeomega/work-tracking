<!-- resources/js/components/workspaces/WorkspaceStats.vue -->
<template>
  <div class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Statistiques du workspace</h3>
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
      <div ref="staggerRef" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Projets -->
        <div class="stagger-item rounded-3 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Projets</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1 counter-pop">{{ statistics.total_projets || 0 }}</p>
            </div>
            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-3 flex items-center justify-center">
              <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
              </svg>
            </div>
          </div>
          <div class="mt-2">
            <span class="text-xs text-green-600 dark:text-green-400 font-medium">{{ statistics.projets_actifs || 0 }} actifs</span>
          </div>
        </div>

        <!-- Tâches -->
        <div class="stagger-item rounded-3 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Tâches</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1 counter-pop">{{ statistics.total_taches || 0 }}</p>
            </div>
            <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-3 flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
              </svg>
            </div>
          </div>
          <div class="mt-2">
            <div class="flex items-center justify-between mb-1">
              <span class="text-xs text-green-600 dark:text-green-400 font-medium">{{ statistics.taux_completion || 0 }}% complétées</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
              <div class="h-1.5 rounded-full bg-green-500 transition-all duration-700" :style="{ width: `${statistics.taux_completion || 0}%` }"></div>
            </div>
          </div>
        </div>

        <!-- Activités -->
        <div class="stagger-item rounded-3 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Activités</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1 counter-pop">{{ statistics.total_activites || 0 }}</p>
            </div>
            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-3 flex items-center justify-center">
              <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
              </svg>
            </div>
          </div>
        </div>

        <!-- Membres -->
        <div class="stagger-item rounded-3 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Membres</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1 counter-pop">{{ memberCount || 0 }}</p>
            </div>
            <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900 rounded-3 flex items-center justify-center">
              <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
              </svg>
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
  memberCount: { type: Number, default: 0 },
  loading: { type: Boolean, default: false },
})

defineEmits(['close'])

const { staggerRef, applyStagger } = useStagger(70)

onMounted(() => applyStagger())
</script>
