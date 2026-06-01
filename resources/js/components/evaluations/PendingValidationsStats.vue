<!-- resources/js/components/evaluations/PendingValidationsStats.vue -->
<template>
  <div class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Statistiques des validations</h3>
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

    <div v-else>
      <div ref="staggerRef" class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="stagger-item">
          <StatCard title="N1 en attente" :value="counts.n1" icon="clock" color="blue" />
        </div>
        <div class="stagger-item">
          <StatCard title="N2 en attente" :value="counts.n2" icon="clock" color="purple" />
        </div>
        <div class="stagger-item">
          <StatCard title="Urgents (< 24h)" :value="counts.urgent" icon="exclamation" color="red" :alert="counts.urgent > 0" />
        </div>
        <div class="stagger-item">
          <StatCard title="Total" :value="counts.total" icon="clipboard-list" color="gray" />
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
