<!-- resources/js/components/taches/TachesAssigneesStats.vue -->
<template>
  <div class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Statistiques — Tâches assignées</h3>
      <button
        @click="$emit('close')"
        class="rounded-3 p-1 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <SkeletonLoader v-if="loading" type="stats" :cols="5" />

    <div v-else class="space-y-6">
      <div ref="staggerRef" class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="stagger-item">
          <StatCard title="Total assignées" :value="stats.total" icon="clipboard-list" color="gray" />
        </div>
        <div class="stagger-item">
          <StatCard title="À faire" :value="stats.a_faire" icon="clock" color="slate" />
        </div>
        <div class="stagger-item">
          <StatCard title="En cours" :value="stats.en_cours" icon="play" color="blue" />
        </div>
        <div class="stagger-item">
          <StatCard title="Terminées" :value="stats.termine" icon="check-circle" color="green" />
        </div>
        <div class="stagger-item">
          <StatCard title="En retard" :value="stats.overdue" icon="exclamation" color="red" :alert="stats.overdue > 0" />
        </div>
      </div>

      <div class="rounded-3 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-4">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Taux de complétion</span>
          <span class="text-sm font-bold text-brand-600 dark:text-brand-400 counter-pop">{{ completionRate }}%</span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
          <div
            class="h-2.5 rounded-full bg-brand-500 transition-all duration-700 ease-out"
            :style="{ width: `${completionRate}%` }"
          ></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import StatCard from '@/components/common/StatCard.vue'
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
