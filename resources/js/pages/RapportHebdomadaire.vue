<template>
   <AdminLayout>
    <PageBreadcrumb :pageTitle="$t('rapport_hebdo.title')" />
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          {{ $t('rapport_hebdo.title') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
          {{ $t('rapport_hebdo.subtitle', { week: filters.week_number, year: filters.year }) }}
        </p>
      </div>

      <div class="flex gap-3">
        <!-- Sélecteur de semaine -->
        <div class="flex gap-2">
          <button
            @click="previousWeek"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <button
            @click="currentWeek"
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-medium text-gray-700 dark:text-gray-300"
          >
            {{ $t('rapport_hebdo.today') }}
          </button>
          <button
            @click="nextWeek"
            :disabled="isCurrentWeek"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>

        <!-- Bouton Statistiques -->
        <button
          @click="showStats = !showStats"
          :disabled="!report"
          :class="showStats
            ? 'border-blue-400 dark:border-blue-600 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300'
            : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'"
          class="inline-flex items-center gap-2 rounded-3 border px-4 py-2 text-sm font-medium transition-all duration-200 disabled:opacity-50"
        >
          <svg class="w-4 h-4 transition-transform duration-300" :class="showStats ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          {{ $t('rapport_hebdo.statistics') }}
        </button>

        <!-- Export PDF -->
        <button
          @click="exportPDF"
          :disabled="loading"
          class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-3 font-medium transition-all flex items-center gap-2 disabled:opacity-50"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <span>{{ $t('rapport_hebdo.export_pdf') }}</span>
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-500"></div>
    </div>

    <template v-else-if="report">
      <!-- Panneau statistiques -->
      <transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 -translate-y-4"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-4"
      >
        <RapportHebdomadaireStats
          v-if="showStats"
          :statistics="report.statistics"
          :loading="false"
          @close="showStats = false"
        />
      </transition>

      <!-- Tâches par activité -->
      <div class="bg-white dark:bg-gray-800 rounded-3 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">
            {{ $t('rapport_hebdo.tasks_by_activity') }}
          </h2>
        </div>

        <div v-if="report.tasks_by_activite && report.tasks_by_activite.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
          <div
            v-for="group in report.tasks_by_activite"
            :key="group.activite.id"
            class="p-6 hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors"
          >
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">
                  {{ group.activite.nom }}
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  {{ group.activite.projet_nom }}
                </p>
              </div>
              <div class="text-right">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                  {{ group.completed }}/{{ group.count }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  {{ $t('rapport_hebdo.completed_pct', { pct: Math.round((group.completed / group.count) * 100) }) }}
                </div>
              </div>
            </div>

            <!-- Liste des tâches -->
            <div class="space-y-2">
              <div
                v-for="task in group.tasks"
                :key="task.id"
                class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900 rounded-3"
              >
                <div class="flex-shrink-0">
                  <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-semibold"
                        :class="getTaskStatusClass(task.statut)">
                    {{ getTaskStatusIcon(task.statut) }}
                  </span>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                    {{ task.titre }}
                  </p>
                </div>
                <div class="flex-shrink-0 flex items-center gap-2">
                  <span v-if="task.validation?.n2_validated_at" 
                        class="text-xs px-2 py-1 bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 rounded-full">
                    ✓✓ N2
                  </span>
                  <span v-else-if="task.validation?.n1_validated_at"
                        class="text-xs px-2 py-1 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 rounded-full">
                    ✓ N1
                  </span>
                  <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                    {{ task.taux_realisation }}%
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="p-12 text-center">
          <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
          <p class="text-gray-600 dark:text-gray-400">
            {{ $t('rapport_hebdo.no_tasks') }}
          </p>
        </div>
      </div>
    </template>
  </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/components/layout/AdminLayout.vue'
import RapportHebdomadaireStats from '@/components/evaluations/RapportHebdomadaireStats.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@/api/axios'

const { t } = useI18n()

const loading = ref(true)
const showStats = ref(false)
const report = ref(null)
const filters = ref({
  week_number: getCurrentWeek(),
  year: new Date().getFullYear()
})

const isCurrentWeek = computed(() => {
  const now = new Date()
  return filters.value.week_number === getCurrentWeek() && 
         filters.value.year === now.getFullYear()
})

function getCurrentWeek() {
  const now = new Date()
  const start = new Date(now.getFullYear(), 0, 1)
  const diff = now - start
  const oneWeek = 1000 * 60 * 60 * 24 * 7
  return Math.ceil(diff / oneWeek)
}

const loadReport = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/evaluations/mon-rapport-hebdomadaire', {
      params: filters.value
    })
    report.value = data
  } catch (error) {
    console.error('Error loading report:', error)
  } finally {
    loading.value = false
  }
}

const previousWeek = () => {
  filters.value.week_number--
  if (filters.value.week_number < 1) {
    filters.value.week_number = 52
    filters.value.year--
  }
  loadReport()
}

const nextWeek = () => {
  if (isCurrentWeek.value) return
  filters.value.week_number++
  if (filters.value.week_number > 52) {
    filters.value.week_number = 1
    filters.value.year++
  }
  loadReport()
}

const currentWeek = () => {
  filters.value.week_number = getCurrentWeek()
  filters.value.year = new Date().getFullYear()
  loadReport()
}

const exportPDF = async () => {
  try {
    const response = await api.post('/evaluations/export-pdf', filters.value, {
      responseType: 'blob'
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `rapport-S${filters.value.week_number}-${filters.value.year}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    console.error('Error exporting PDF:', error)
    alert(t('rapport_hebdo.export_error'))
  }
}

const getVarianceColorClass = (variance) => {
  if (variance > 10) return 'text-red-600 dark:text-red-400'
  if (variance < -10) return 'text-green-600 dark:text-green-400'
  return 'text-gray-600 dark:text-gray-400'
}

const getTaskStatusClass = (statut) => {
  const classes = {
    'a_faire': 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    'en_cours': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    'termine': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
  }
  return classes[statut] || classes.a_faire
}

const getTaskStatusIcon = (statut) => {
  const icons = {
    'a_faire': '○',
    'en_cours': '◐',
    'termine': '●'
  }
  return icons[statut] || '○'
}

onMounted(() => {
  loadReport()
})
</script>