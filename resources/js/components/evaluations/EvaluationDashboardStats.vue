<!-- resources/js/components/evaluations/EvaluationDashboardStats.vue -->
<template>
  <div class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        Statistiques — Semaine {{ weekNumber }}
      </h3>
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
      <!-- Mes tâches -->
      <div ref="staggerRef" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stagger-item">
          <StatCard title="Total tâches" :value="myTasks.total" icon="clipboard-list" color="blue" />
        </div>
        <div class="stagger-item">
          <StatCard
            title="Complétées"
            :value="myTasks.completed"
            :progress="myTasks.completionRate"
            icon="check-circle"
            color="green"
          />
        </div>
        <div class="stagger-item">
          <StatCard title="En cours" :value="myTasks.in_progress" icon="play" color="amber" />
        </div>
        <div class="stagger-item">
          <StatCard title="En retard" :value="myTasks.overdue" icon="exclamation" color="red" :alert="myTasks.overdue > 0" />
        </div>
      </div>

      <!-- Validations en attente -->
      <div v-if="pendingValidations.total > 0" class="grid grid-cols-1 sm:grid-cols-3 gap-4 rounded-3 border border-orange-200 dark:border-orange-800/50 bg-orange-50 dark:bg-orange-900/20 p-4">
        <div class="sm:col-span-1 flex items-center gap-3">
          <svg class="w-6 h-6 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div>
            <p class="text-sm font-semibold text-orange-700 dark:text-orange-300">Validations en attente</p>
            <p class="text-2xl font-bold text-orange-700 dark:text-orange-300 counter-pop">{{ pendingValidations.total }}</p>
          </div>
        </div>
        <div class="text-center">
          <p class="text-xs font-medium text-orange-600 dark:text-orange-400 uppercase tracking-wide">N1 — Responsable activité</p>
          <p class="text-2xl font-bold text-orange-700 dark:text-orange-300 counter-pop mt-1">{{ pendingValidations.n1 }}</p>
        </div>
        <div class="text-center">
          <p class="text-xs font-medium text-purple-600 dark:text-purple-400 uppercase tracking-wide">N2 — Responsable projet</p>
          <p class="text-2xl font-bold text-purple-700 dark:text-purple-300 counter-pop mt-1">{{ pendingValidations.n2 }}</p>
        </div>
      </div>

      <!-- Completion bar -->
      <div class="rounded-3 border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Taux de complétion</span>
          <span class="text-sm font-bold text-brand-600 dark:text-brand-400 counter-pop">{{ myTasks.completionRate }}%</span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
          <div
            class="h-2.5 rounded-full bg-brand-500 transition-all duration-700 ease-out"
            :style="{ width: `${myTasks.completionRate}%` }"
          ></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import StatCard from '@/components/common/StatCard.vue'
import SkeletonLoader from '@/components/common/SkeletonLoader.vue'
import { useStagger } from '@/composables/useAnimations'

defineProps({
  myTasks: { type: Object, required: true },
  pendingValidations: { type: Object, default: () => ({ total: 0, n1: 0, n2: 0 }) },
  weekNumber: { type: Number, default: 0 },
  loading: { type: Boolean, default: false },
})

defineEmits(['close'])

const { staggerRef, applyStagger } = useStagger(60)

onMounted(() => applyStagger())
</script>
