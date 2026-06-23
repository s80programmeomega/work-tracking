<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="$t('performance_equipe.title')" />
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
        {{ $t('performance_equipe.title') }}
      </h1>
      <p class="text-gray-600 dark:text-gray-400 mt-1">
        {{ $t('performance_equipe.subtitle') }}
      </p>
    </div>

    <!-- Sélection activité -->
    <div class="bg-white dark:bg-gray-800 rounded-3 border p-6">
      <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-3">
        {{ $t('performance_equipe.select_activity') }}
      </label>
      <select
        v-model="selectedActivite"
        @change="loadPerformance"
        class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 dark:bg-gray-900"
      >
        <option value="">{{ $t('performance_equipe.choose_activity') }}</option>
        <option v-for="act in activites" :key="act.id" :value="act.id">
          {{ act.nom }} ({{ act.projet_nom }})
        </option>
      </select>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-500"></div>
    </div>

    <!-- Performance data -->
    <template v-else-if="performance">
      <!-- Vue d'ensemble -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-3 border p-6">
          <p class="text-sm text-gray-600 dark:text-gray-400">{{ $t('performance_equipe.total_tasks') }}</p>
          <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
            {{ performance.overall.total_tasks }}
          </p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-3 border p-6">
          <p class="text-sm text-gray-600 dark:text-gray-400">{{ $t('performance_equipe.completed') }}</p>
          <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">
            {{ performance.overall.completed }}
          </p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-3 border p-6">
          <p class="text-sm text-gray-600 dark:text-gray-400">{{ $t('performance_equipe.completion_rate') }}</p>
          <p class="text-3xl font-bold text-brand-600 dark:text-brand-400 mt-2">
            {{ performance.overall.completion_rate }}%
          </p>
        </div>
      </div>

      <!-- Statistiques par membre -->
      <div class="bg-white dark:bg-gray-800 rounded-3 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">
            {{ $t('performance_equipe.member_stats') }}
          </h2>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full">
            <thead class="bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">
                  {{ $t('performance_equipe.col_member') }}
                </th>
                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300">
                  {{ $t('performance_equipe.col_total') }}
                </th>
                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300">
                  {{ $t('performance_equipe.col_done') }}
                </th>
                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300">
                  {{ $t('performance_equipe.col_in_progress') }}
                </th>
                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300">
                  {{ $t('performance_equipe.col_overdue') }}
                </th>
                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300">
                  {{ $t('performance_equipe.col_rate') }}
                </th>
              </tr>
            </thead>
            <tbody ref="tbodyRef" class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr
                v-for="member in performance.team_stats"
                :key="member.user.id"
                class="stagger-item hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors"
              >
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <img
                      v-if="member.user.avatar"
                      :src="member.user.avatar"
                      :alt="member.user.nom"
                      class="w-10 h-10 rounded-full"
                    />
                    <div
                      v-else
                      class="w-10 h-10 rounded-full bg-brand-500 flex items-center justify-center text-white font-semibold"
                    >
                      {{ getInitials(member.user.nom) }}
                    </div>
                    <div>
                      <p class="font-medium text-gray-900 dark:text-white">
                        {{ member.user.nom }}
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">{{ member.user.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-center text-gray-900 dark:text-white font-semibold">
                  {{ member.total }}
                </td>
                <td class="px-6 py-4 text-center text-green-600 dark:text-green-400 font-semibold">
                  {{ member.completed }}
                </td>
                <td class="px-6 py-4 text-center text-blue-600 dark:text-blue-400 font-semibold">
                  {{ member.in_progress }}
                </td>
                <td class="px-6 py-4 text-center text-red-600 dark:text-red-400 font-semibold">
                  {{ member.overdue }}
                </td>
                <td class="px-6 py-4 text-center">
                  <div class="flex items-center justify-center gap-2">
                    <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                      <div
                        class="h-2 rounded-full transition-all"
                        :class="getProgressColor(member.completion_rate)"
                        :style="{ width: `${member.completion_rate}%` }"
                      ></div>
                    </div>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                      {{ member.completion_rate }}%
                    </span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api/axios'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import { useStagger } from '@/composables/useAnimations'

const loading = ref(false)
const { staggerRef: tbodyRef, applyStagger } = useStagger(50)
const activites = ref([])
const selectedActivite = ref('')
const performance = ref(null)

const loadActivites = async () => {
  try {
    const { data } = await api.get('/mes-activites', {
      params: { workspace_id: localStorage.getItem('current_workspace_id') }
    })
    activites.value = data.data
  } catch (error) {
    console.error('Error loading activites:', error)
  }
}

const loadPerformance = async () => {
  if (!selectedActivite.value) return

  loading.value = true
  try {
    const { data } = await api.get(
      `/evaluations/performance-equipe/${selectedActivite.value}`
    )
    performance.value = data
    applyStagger()
  } catch (error) {
    console.error('Error loading performance:', error)
  } finally {
    loading.value = false
  }
}

const getInitials = (nom) => {
  return nom.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const getProgressColor = (rate) => {
  if (rate >= 75) return 'bg-green-500'
  if (rate >= 50) return 'bg-blue-500'
  if (rate >= 25) return 'bg-yellow-500'
  return 'bg-red-500'
}

onMounted(() => {
  loadActivites()
})
</script>