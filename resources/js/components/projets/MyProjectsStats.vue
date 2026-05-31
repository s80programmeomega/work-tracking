<!-- resources/js/components/projets/MyProjectsStats.vue -->
<template>
  <div class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Statistiques — Mes projets</h3>
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
        <!-- Projets -->
        <div class="stagger-item rounded-3 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white counter-pop">{{ stats.total_projets || 0 }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">Projets total</p>
            </div>
            <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-3">
              <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
              </svg>
            </div>
          </div>
          <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
            <span class="text-xs font-medium text-green-600 dark:text-green-400">{{ stats.projets_actifs || 0 }} actifs</span>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ stats.projets_termines || 0 }} terminés</span>
          </div>
        </div>

        <!-- Activités -->
        <div class="stagger-item rounded-3 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white counter-pop">{{ stats.total_activites || 0 }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">Activités</p>
            </div>
            <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-3">
              <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
              </svg>
            </div>
          </div>
          <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ stats.activites_actives || 0 }} actives</span>
          </div>
        </div>

        <!-- Tâches -->
        <div class="stagger-item rounded-3 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white counter-pop">{{ stats.total_taches || 0 }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">Tâches</p>
            </div>
            <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-3">
              <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between mb-1">
              <span class="text-xs text-gray-500 dark:text-gray-400">{{ stats.taches_terminees || 0 }}/{{ stats.total_taches || 0 }}</span>
              <span class="text-xs font-medium text-green-600 dark:text-green-400">{{ stats.taux_completion || 0 }}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
              <div class="h-1.5 rounded-full bg-green-500 transition-all duration-700" :style="{ width: `${stats.taux_completion || 0}%` }"></div>
            </div>
          </div>
        </div>

        <!-- En retard -->
        <div class="stagger-item rounded-3 border dark:border-gray-700 bg-white dark:bg-gray-800 p-4"
          :class="(stats.projets_en_retard || 0) > 0 ? 'border-red-200' : 'border-gray-200'">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-2xl font-bold counter-pop"
                :class="(stats.projets_en_retard || 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'">
                {{ stats.projets_en_retard || 0 }}
              </p>
              <p class="text-sm text-gray-500 dark:text-gray-400">En retard</p>
            </div>
            <div class="p-2 rounded-3" :class="(stats.projets_en_retard || 0) > 0 ? 'bg-red-100 dark:bg-red-900/30' : 'bg-gray-100 dark:bg-gray-700'">
              <svg class="w-6 h-6" :class="(stats.projets_en_retard || 0) > 0 ? 'text-red-500 animate-pulse' : 'text-gray-400'"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
          </div>
          <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
            <span class="text-xs font-medium"
              :class="(stats.projets_en_retard || 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
              {{ (stats.projets_en_retard || 0) > 0 ? 'Nécessite attention' : 'Aucun retard' }}
            </span>
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
  stats: { type: Object, required: true },
  loading: { type: Boolean, default: false },
})

defineEmits(['close'])

const { staggerRef, applyStagger } = useStagger(70)

onMounted(() => applyStagger())
</script>
