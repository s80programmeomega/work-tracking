<template>
  <AdminLayout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 p-6 transition-colors duration-200">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Tableau de Bord</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Vue d'ensemble de vos projets et tâches
            </p>
          </div>
          <div class="flex items-center gap-3 flex-wrap">
            <select
              v-model="selectedPeriod"
              @change="loadDashboardData"
              class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors"
            >
              <option value="week">Cette semaine</option>
              <option value="month">Ce mois</option>
              <option value="quarter">Ce trimestre</option>
              <option value="year">Cette année</option>
            </select>
            <button
              @click="loadDashboardData"
              class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-colors flex items-center gap-2"
            >
              <RefreshIcon class="w-4 h-4" />
              Actualiser
            </button>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="h-12 w-12 animate-spin rounded-full border-4 border-brand-600 border-t-transparent"></div>
      </div>

      <!-- Dashboard Content -->
      <div v-else>
        <!-- Stats Cards -->
        <div class="mb-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
          <div
            v-for="(stat, index) in statsCards"
            :key="index"
            class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm transition-all hover:shadow-md border border-gray-200 dark:border-gray-700 group"
          >
            <!-- Background gradient effect -->
            <div class="absolute inset-0 bg-gradient-to-br from-brand-500/5 to-accent-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            
            <div class="relative flex items-start justify-between">
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ stat.title }}</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ stat.value }}</p>
                <div class="mt-2 flex items-center">
                  <span :class="[
                    'flex items-center text-sm font-medium',
                    stat.trend === 'up' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
                  ]">
                    <TrendingUpIcon v-if="stat.trend === 'up'" class="mr-1 h-4 w-4" />
                    <TrendingDownIcon v-else class="mr-1 h-4 w-4" />
                    {{ stat.change }}
                  </span>
                  <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">vs mois dernier</span>
                </div>
              </div>
              <div :class="['rounded-lg p-3 transition-colors duration-200', stat.lightColor]">
                <component :is="stat.icon" :class="['h-6 w-6', stat.textColor]" />
              </div>
            </div>
          </div>
        </div>

        <!-- Main Grid -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <!-- Left Column - 2/3 width -->
          <div class="space-y-6 lg:col-span-2">
            <!-- Progression Mensuelle -->
            <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-200 dark:border-gray-700">
              <div class="mb-6 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Progression Mensuelle</h2>
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                  <div class="flex items-center gap-1">
                    <div class="w-3 h-3 rounded-full bg-brand-500"></div>
                    <span>Projets</span>
                  </div>
                  <div class="flex items-center gap-1">
                    <div class="w-3 h-3 rounded-full bg-accent-500"></div>
                    <span>Tâches</span>
                  </div>
                  <div class="flex items-center gap-1">
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                    <span>Complétées</span>
                  </div>
                </div>
              </div>
              <div class="h-80">
                <canvas ref="monthlyChart"></canvas>
              </div>
            </div>

            <!-- Projets Récents -->
            <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-200 dark:border-gray-700">
              <div class="mb-6 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Projets Récents</h2>
                <router-link
                  to="/projets/mes-projets"
                  class="text-sm font-medium text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition-colors flex items-center gap-1"
                >
                  Voir tout
                  <ArrowRightIcon class="w-4 h-4" />
                </router-link>
              </div>
              <div class="space-y-4">
                <div
                  v-for="project in dashboardData.recent_projects"
                  :key="project.id"
                  class="rounded-lg border border-gray-200 dark:border-gray-600 p-4 transition-all hover:border-brand-300 dark:hover:border-brand-500 hover:shadow-sm cursor-pointer group"
                  @click="goToProject(project.id)"
                >
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <div class="flex items-center gap-3 mb-2">
                        <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors">
                          {{ project.name }}
                        </h3>
                        <span class="rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-1 text-xs font-medium text-gray-600 dark:text-gray-400">
                          {{ project.code }}
                        </span>
                      </div>
                      
                      <div class="mt-3 flex items-center gap-6 text-sm text-gray-600 dark:text-gray-400 flex-wrap">
                        <div class="flex items-center gap-1">
                          <UsersIcon class="h-4 w-4" />
                          <span>{{ project.team }} membres</span>
                        </div>
                        <div class="flex items-center gap-1">
                          <CheckCircleIcon class="h-4 w-4" />
                          <span>{{ project.tasks.completed }}/{{ project.tasks.total }} tâches</span>
                        </div>
                        <div v-if="project.due_date" class="flex items-center gap-1">
                          <CalendarIcon class="h-4 w-4" />
                          <span>{{ project.due_date }}</span>
                        </div>
                      </div>
                      
                      <div class="mt-4">
                        <div class="flex items-center justify-between text-sm mb-2">
                          <span class="font-medium text-gray-700 dark:text-gray-300">Progression</span>
                          <span class="font-semibold text-gray-900 dark:text-white">{{ project.progress }}%</span>
                        </div>
                        <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                          <div
                            class="h-full rounded-full bg-gradient-to-r from-brand-500 to-accent-500 transition-all duration-500"
                            :style="{ width: `${project.progress}%` }"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column - 1/3 width -->
          <div class="space-y-6">
            <!-- Répartition par Statut -->
            <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-200 dark:border-gray-700">
              <h2 class="mb-6 text-lg font-semibold text-gray-900 dark:text-white">Répartition par Statut</h2>
              <div class="h-48">
                <canvas ref="statusChart"></canvas>
              </div>
              <div class="mt-4 space-y-2">
                <div
                  v-for="item in dashboardData.status_distribution"
                  :key="item.name"
                  class="flex items-center justify-between py-1"
                >
                  <div class="flex items-center gap-2">
                    <div
                      class="h-3 w-3 rounded-full"
                      :style="{ backgroundColor: item.color }" />
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ item.name }}</span>
                  </div>
                  <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ item.value }}</span>
                </div>
              </div>
            </div>

            <!-- Répartition par Priorité -->
            <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-200 dark:border-gray-700">
              <h2 class="mb-6 text-lg font-semibold text-gray-900 dark:text-white">Répartition par Priorité</h2>
              <div class="h-48">
                <canvas ref="priorityChart"></canvas>
              </div>
            </div>

            <!-- Tâches Urgentes -->
            <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-200 dark:border-gray-700">
              <div class="mb-6 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Tâches Urgentes</h2>
                <span class="rounded-full bg-red-100 dark:bg-red-900/30 px-2 py-1 text-xs font-semibold text-red-700 dark:text-red-400">
                  {{ dashboardData.urgent_tasks?.length || 0 }}
                </span>
              </div>
              <div class="space-y-3">
                <div
                  v-for="task in dashboardData.urgent_tasks"
                  :key="task.id"
                  class="rounded-lg border-l-4 p-3 transition-all cursor-pointer group"
                  :class="[
                    task.is_overdue 
                      ? 'border-red-500 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30' 
                      : 'border-accent-500 bg-accent-50 dark:bg-accent-900/20 hover:bg-accent-100 dark:hover:bg-accent-900/30'
                  ]"
                  @click="goToTask(task.id)"
                >
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <h4 class="font-medium text-gray-900 dark:text-white group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors">
                        {{ task.title }}
                      </h4>
                      <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ task.project }}</p>
                      <div class="mt-2 flex items-center gap-2 flex-wrap">
                        <span :class="[
                          'rounded-full px-2 py-0.5 text-xs font-medium transition-colors',
                          getPriorityColor(task.priority)
                        ]">
                          {{ task.priority }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                          <ClockIcon class="w-3 h-3" />
                          {{ task.due_date }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div v-if="!dashboardData.urgent_tasks?.length" class="text-center py-4">
                  <CheckCircleIcon class="mx-auto h-8 w-8 text-green-500 dark:text-green-400 mb-2" />
                  <p class="text-sm text-gray-500 dark:text-gray-400">Aucune tâche urgente</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api/axios'
import Chart from 'chart.js/auto'
import AdminLayout from '../components/layout/AdminLayout.vue'
import {
  RefreshIcon,
  TrendingUpIcon,
  TrendingDownIcon,
  ArrowRightIcon,
  UsersIcon,
  CheckCircleIcon,
  CalendarIcon,
  ClockIcon,
  FolderKanbanIcon,
  ListTodoIcon,
  TargetIcon,
  AlertCircleIcon
} from '@/icons'

const router = useRouter()
const loading = ref(true)
const selectedPeriod = ref('month')
const dashboardData = ref({
  stats: {},
  monthly_progress: [],
  status_distribution: [],
  priority_distribution: [],
  recent_projects: [],
  urgent_tasks: [],
  workspace_stats: []
})

const monthlyChart = ref(null)
const statusChart = ref(null)
const priorityChart = ref(null)

let monthlyChartInstance = null
let statusChartInstance = null
let priorityChartInstance = null

// Couleurs de la charte graphique
const brandColor = '#4b71f9'
const accentColor = '#ecb73d'

const statsCards = ref([
  {
    title: 'Projets Actifs',
    value: 0,
    change: '+0%',
    trend: 'up',
    icon: FolderKanbanIcon,
    lightColor: 'bg-blue-50 dark:bg-blue-900/20',
    textColor: 'text-blue-600 dark:text-blue-400'
  },
  {
    title: 'Tâches En Cours',
    value: 0,
    change: '+0%',
    trend: 'up',
    icon: ListTodoIcon,
    lightColor: 'bg-purple-50 dark:bg-purple-900/20',
    textColor: 'text-purple-600 dark:text-purple-400'
  },
  {
    title: 'Taux Complétion',
    value: '0%',
    change: '+0%',
    trend: 'up',
    icon: TargetIcon,
    lightColor: 'bg-green-50 dark:bg-green-900/20',
    textColor: 'text-green-600 dark:text-green-400'
  },
  {
    title: 'Tâches En Retard',
    value: 0,
    change: '0%',
    trend: 'down',
    icon: AlertCircleIcon,
    lightColor: 'bg-red-50 dark:bg-red-900/20',
    textColor: 'text-red-600 dark:text-red-400'
  }
])

const loadDashboardData = async () => {
  loading.value = true
  try {
    const response = await api.get('/dashboard', {
      params: { period: selectedPeriod.value }
    })
    
    dashboardData.value = response.data
    
    // Update stats cards
    statsCards.value[0].value = response.data.stats.projets_actifs.value
    statsCards.value[0].change = response.data.stats.projets_actifs.change
    statsCards.value[0].trend = response.data.stats.projets_actifs.trend
    
    statsCards.value[1].value = response.data.stats.taches_en_cours.value
    statsCards.value[1].change = response.data.stats.taches_en_cours.change
    statsCards.value[1].trend = response.data.stats.taches_en_cours.trend
    
    statsCards.value[2].value = response.data.stats.taux_completion.value + '%'
    statsCards.value[2].change = response.data.stats.taux_completion.change
    statsCards.value[2].trend = response.data.stats.taux_completion.trend
    
    statsCards.value[3].value = response.data.stats.taches_en_retard.value
    statsCards.value[3].change = response.data.stats.taches_en_retard.change
    statsCards.value[3].trend = response.data.stats.taches_en_retard.trend
    
    // Wait for DOM update before creating charts
    await nextTick()
    createCharts()
  } catch (error) {
    console.error('Error loading dashboard data:', error)
  } finally {
    loading.value = false
  }
}

const createCharts = () => {
  // Destroy existing charts
  if (monthlyChartInstance) monthlyChartInstance.destroy()
  if (statusChartInstance) statusChartInstance.destroy()
  if (priorityChartInstance) priorityChartInstance.destroy()
  
  // Get theme for chart colors
  const isDark = document.documentElement.classList.contains('dark')
  const textColor = isDark ? '#f9fafb' : '#111827'
  const gridColor = isDark ? '#374151' : '#e5e7eb'
  
  // Monthly Progress Chart
  if (monthlyChart.value) {
    monthlyChartInstance = new Chart(monthlyChart.value, {
      type: 'line',
      data: {
        labels: dashboardData.value.monthly_progress.map(d => d.month),
        datasets: [
          {
            label: 'Projets',
            data: dashboardData.value.monthly_progress.map(d => d.projets),
            borderColor: brandColor,
            backgroundColor: `${brandColor}20`,
            tension: 0.4,
            fill: true
          },
          {
            label: 'Tâches totales',
            data: dashboardData.value.monthly_progress.map(d => d.taches),
            borderColor: accentColor,
            backgroundColor: `${accentColor}20`,
            tension: 0.4,
            fill: true
          },
          {
            label: 'Complétées',
            data: dashboardData.value.monthly_progress.map(d => d.completes),
            borderColor: '#10b981',
            backgroundColor: '#10b98120',
            tension: 0.4,
            fill: true
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              color: textColor,
              usePointStyle: true
            }
          }
        },
        scales: {
          x: {
            grid: {
              color: gridColor
            },
            ticks: {
              color: textColor
            }
          },
          y: {
            grid: {
              color: gridColor
            },
            ticks: {
              color: textColor
            }
          }
        }
      }
    })
  }
  
  // Status Distribution Chart
  if (statusChart.value) {
    statusChartInstance = new Chart(statusChart.value, {
      type: 'doughnut',
      data: {
        labels: dashboardData.value.status_distribution.map(d => d.name),
        datasets: [{
          data: dashboardData.value.status_distribution.map(d => d.value),
          backgroundColor: [
            brandColor,
            accentColor,
            '#10b981',
            '#f59e0b',
            '#ef4444'
          ]
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        }
      }
    })
  }
  
  // Priority Distribution Chart
  if (priorityChart.value) {
    priorityChartInstance = new Chart(priorityChart.value, {
      type: 'bar',
      data: {
        labels: dashboardData.value.priority_distribution.map(d => d.name),
        datasets: [{
          label: 'Tâches',
          data: dashboardData.value.priority_distribution.map(d => d.value),
          backgroundColor: [
            brandColor,
            accentColor,
            '#10b981',
            '#f59e0b'
          ]
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          x: {
            grid: {
              color: gridColor
            },
            ticks: {
              color: textColor
            }
          },
          y: {
            grid: {
              color: gridColor
            },
            ticks: {
              color: textColor
            }
          }
        }
      }
    })
  }
}

const getPriorityColor = (priority) => {
  const colors = {
    'Élevée': 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
    'Moyenne': 'bg-accent-100 dark:bg-accent-900/30 text-accent-700 dark:text-accent-400',
    'faible': 'bg-brand-100 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400'
  }
  return colors[priority] || 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
}

const goToProject = (projectId) => {
  router.push({ name: 'ProjectDetail', params: { id: projectId } })
}

const goToTask = (taskId) => {
  router.push({ name: 'TaskDetail', params: { id: taskId } })
}

onMounted(() => {
  loadDashboardData()
})
</script>

<style scoped>
/* Custom styles for the gradient progress bars */
.bg-brand-500 {
  background-color: #4b71f9;
}

.bg-accent-500 {
  background-color: #ecb73d;
}

.text-brand-600 {
  color: #4b71f9;
}

.text-accent-600 {
  color: #ecb73d;
}

.border-brand-300 {
  border-color: #93c5fd;
}

.border-brand-500 {
  border-color: #4b71f9;
}

.hover\:text-brand-600:hover {
  color: #4b71f9;
}

.hover\:text-brand-700:hover {
  color: #3b56c7;
}

.hover\:bg-brand-700:hover {
  background-color: #3b56c7;
}

/* Dark mode variants */
.dark .text-brand-400 {
  color: #7c9cff;
}

.dark .hover\:text-brand-300:hover {
  color: #a3c4ff;
}

.dark .border-brand-500 {
  border-color: #4b71f9;
}
</style>