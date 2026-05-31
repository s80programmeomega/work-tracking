<!-- resources/js/components/taches/TachesParUtilisateurStats.vue -->
<template>
  <div class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Statistiques — Vue coordination</h3>
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

    <div v-else ref="staggerRef" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="stagger-item">
        <StatCard title="Membres" :value="totalUsers" icon="clipboard-list" color="gray" />
      </div>
      <div class="stagger-item">
        <StatCard title="Tâches totales" :value="totalTasks" icon="clipboard-list" color="blue" />
      </div>
      <div class="stagger-item">
        <StatCard title="Progression moyenne" :value="`${averageProgress}%`" icon="chart-pie" color="brand" :progress="averageProgress" />
      </div>
      <div class="stagger-item">
        <StatCard title="En attente validation" :value="totalPendingValidation" icon="exclamation" color="amber" :alert="totalPendingValidation > 0" />
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
  totalUsers: { type: Number, default: 0 },
  totalTasks: { type: Number, default: 0 },
  averageProgress: { type: Number, default: 0 },
  totalPendingValidation: { type: Number, default: 0 },
  loading: { type: Boolean, default: false },
})

defineEmits(['close'])

const { staggerRef, applyStagger } = useStagger(60)

onMounted(() => applyStagger())
</script>
