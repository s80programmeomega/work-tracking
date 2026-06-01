<!-- resources/js/components/evaluations/FichesEvaluationStats.vue -->
<template>
  <div class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $t('fiches_eval.stats_title') }}</h3>
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
      <!-- Stat cards -->
      <div ref="staggerRef" class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="stagger-item">
          <StatCard :title="$t('fiches_eval.stat_total')" :value="stats.total" icon="clipboard-list" color="gray" />
        </div>
        <div class="stagger-item">
          <StatCard :title="$t('fiches_eval.stat_a_faire')" :value="stats.a_faire" icon="clock" color="slate" />
        </div>
        <div class="stagger-item">
          <StatCard :title="$t('fiches_eval.stat_en_cours')" :value="stats.en_cours" icon="play" color="blue"
            :progress="stats.total > 0 ? Math.round((stats.en_cours / stats.total) * 100) : 0" />
        </div>
        <div class="stagger-item">
          <StatCard :title="$t('fiches_eval.stat_termine')" :value="stats.termine" icon="check-circle" color="green"
            :progress="stats.total > 0 ? Math.round((stats.termine / stats.total) * 100) : 0" />
        </div>
        <div class="stagger-item">
          <StatCard :title="$t('fiches_eval.stat_overdue')" :value="stats.en_retard" icon="exclamation" color="red" :alert="stats.en_retard > 0" />
        </div>
      </div>

      <!-- Validation details -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 rounded-3 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-4">
        <div class="text-center">
          <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $t('fiches_eval.stat_with_result') }}</p>
          <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 counter-pop mt-1">{{ stats.avec_resultat }}</p>
        </div>
        <div class="text-center">
          <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $t('fiches_eval.stat_validated_n1') }}</p>
          <p class="text-2xl font-bold text-green-600 dark:text-green-400 counter-pop mt-1">{{ stats.valide_n1 }}</p>
        </div>
        <div class="text-center">
          <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $t('fiches_eval.stat_validated_n2') }}</p>
          <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 counter-pop mt-1">{{ stats.valide_n2 }}</p>
        </div>
      </div>

      <!-- Completion bar -->
      <div class="rounded-3 border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('fiches_eval.stat_completion_rate') }}</span>
          <span class="text-sm font-bold text-brand-600 dark:text-brand-400 counter-pop">{{ stats.completionRate }}%</span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
          <div
            class="h-2.5 rounded-full bg-brand-500 transition-all duration-700 ease-out"
            :style="{ width: `${stats.completionRate}%` }"
          ></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import StatCard from '@/components/common/StatCard.vue'
import SkeletonLoader from '@/components/common/SkeletonLoader.vue'
import { useStagger } from '@/composables/useAnimations'

const { t } = useI18n()

defineProps({
  stats: { type: Object, required: true },
  loading: { type: Boolean, default: false },
})

defineEmits(['close'])

const { staggerRef, applyStagger } = useStagger(60)

onMounted(() => applyStagger())
</script>
