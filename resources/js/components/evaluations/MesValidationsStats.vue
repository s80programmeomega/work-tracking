<!-- resources/js/components/evaluations/MesValidationsStats.vue -->
<template>
  <div class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Statistiques de mes validations</h3>
      <button
        @click="$emit('close')"
        class="rounded-3 p-1 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <SkeletonLoader v-if="loading" type="stats" :cols="3" />

    <div v-else>
      <div ref="staggerRef" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="stagger-item">
          <StatCard title="En attente N1" :value="counts.en_validation_n1" icon="clock" color="amber" />
        </div>
        <div class="stagger-item">
          <StatCard title="En attente N2" :value="counts.en_validation_n2" icon="clock" color="blue" />
        </div>
        <div class="stagger-item">
          <StatCard title="Total en attente" :value="counts.total" icon="clipboard-list" color="gray" />
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
  counts: { type: Object, required: true },
  loading: { type: Boolean, default: false },
})

defineEmits(['close'])

const { staggerRef, applyStagger } = useStagger(60)

onMounted(() => applyStagger())
</script>
