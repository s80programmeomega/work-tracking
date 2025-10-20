<template>
  <div class="space-y-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard Projets</h2>

    <!-- Stats Cards -->
    <div class="grid gap-4 md:grid-cols-4">
      <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="text-center">
          <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats?.total_projets || 0 }}</div>
          <div class="text-sm text-gray-500 dark:text-gray-400">Total Projets</div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="text-center">
          <div class="text-3xl font-bold text-blue-600">{{ stats?.active_projets || 0 }}</div>
          <div class="text-sm text-gray-500 dark:text-gray-400">Actifs</div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="text-center">
          <div class="text-3xl font-bold text-green-600">{{ stats?.completed_projets || 0 }}</div>
          <div class="text-sm text-gray-500 dark:text-gray-400">Terminés</div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="text-center">
          <div class="text-3xl font-bold text-red-600">{{ stats?.overdue_projets || 0 }}</div>
          <div class="text-sm text-gray-500 dark:text-gray-400">En retard</div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-4">
      <button
        @click="$emit('view-all')"
        class="px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600"
      >
        Voir tous les projets
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useProjets } from '@/composables/useProjets'

defineEmits(['view-all', 'view-projet'])

const { fetchDashboardStats, stats } = useProjets()

onMounted(() => {
  fetchDashboardStats()
})
</script>