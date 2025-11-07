<template>
  <admin-layout>
    <div class="min-h-screen bg-gray-50 p-6">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Tableau de Bord</h1>
            <p class="mt-1 text-sm text-gray-500">
              Vue d'ensemble de vos projets et tâches
            </p>
          </div>
          <div class="flex items-center gap-3">
            <select
              v-model="selectedPeriod"
              @change="loadDashboardData"
              class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="week">Cette semaine</option>
              <option value="month">Ce mois</option>
              <option value="quarter">Ce trimestre</option>
              <option value="year">Cette année</option>
            </select>
            <button
              @click="loadDashboardData"
              class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
            >
              Actualiser
            </button>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="h-12 w-12 animate-spin rounded-full border-4 border-blue-600 border-t-transparent"></div>
      </div>

      <!-- Dashboard Content -->
      <div v-else>
        <!-- Stats Cards -->
        <div class="mb-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
          <div
            v-for="(stat, index) in statsCards"
            :key="index"
            class="relative overflow-hidden rounded-xl bg-white p-6 shadow-sm transition-all hover:shadow-md"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-600">{{ stat.title }}</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ stat.value }}</p>
                <div class="mt-2 flex items-center">
                  <span :class="[
                    'flex items-center text-sm font-medium',
                    stat.trend === 'up' ? 'text-green-600' : 'text-red-600'
                  ]">
                    <svg
                      v-if="stat.trend === 'up'"
                      class="mr-1 h-4 w-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    <svg
                      v-else
                      class="mr-1 h-4 w-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                    </svg>
                    {{ stat.change }}
                  </span>
                  <span class="ml-2 text-xs text-gray-500">vs mois dernier</span>
                </div>
              </div>
              <div :class="['rounded-lg p-3', stat.lightColor]">
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
            <div class="rounded-xl bg-white p-6 shadow-sm">
              <div class="mb-6 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Progression Mensuelle</h2>
              </div>
              <div class="h-80">
                <canvas ref="monthlyChart"></canvas>
              </div>
            </div>

            <!-- Projets Récents -->
            <div class="rounded-xl bg-white p-6 shadow-sm">
              <div class="mb-6 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Projets Récents</h2>
                <router-link
                  to="/projets/mes-projets"
                  class="text-sm font-medium text-blue-600 hover:text-blue-700"
                >
                  Voir tout →
                </router-link>
              </div>
              <div class="space-y-4">
                <div
                  v-for="project in dashboardData.recent_projects"
                  :key="project.id"
                  class="rounded-lg border border-gray-200 p-4 transition-all hover:border-blue-300 hover:shadow-sm cursor-pointer"
                  @click="goToProject(project.id)"
                >
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <div class="flex items-center gap-3">
                        <h3 class="font-semibold text-gray-900">{{ project.name }}</h3>
                        <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">
                          {{ project.code }}
                        </span>
                      </div>
                      <div class="mt-3 flex items-center gap-6 text-sm text-gray-600">
                        <div class="flex items-center gap-1">
                          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                          </svg>
                          <span>{{ project.team }} membres</span>
                        </div>
                        <div class="flex items-center gap-1">
                          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                          </svg>
                          <span>{{ project.tasks.completed }}/{{ project.tasks.total }} tâches</span>
                        </div>
                        <div v-if="project.due_date" class="flex items-center gap-1">
                          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                          </svg>
                          <span>{{ project.due_date }}</span>
                        </div>
                      </div>
                      <div class="mt-3">
                        <div class="flex items-center justify-between text-sm">
                          <span class="font-medium text-gray-700">Progression</span>
                          <span class="font-semibold text-gray-900">{{ project.progress }}%</span>
                        </div>
                        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-200">
                          <div
                            class="h-full rounded-full bg-blue-500 transition-all"
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
            <div class="rounded-xl bg-white p-6 shadow-sm">
              <h2 class="mb-6 text-lg font-semibold text-gray-900">Répartition par Statut</h2>
              <div class="h-48">
                <canvas ref="statusChart"></canvas>
              </div>
              <div class="mt-4 space-y-2">
                <div
                  v-for="item in dashboardData.status_distribution"
                  :key="item.name"
                  class="flex items-center justify-between"
                >
                  <div class="flex items-center gap-2">
                    <div
                      class="h-3 w-3 rounded-full"
                      :style="{ backgroundColor: item.color }"
                    />
                    <span class="text-sm text-gray-600">{{ item.name }}</span>
                  </div>
                  <span class="text-sm font-semibold text-gray-900">{{ item.value }}</span>
                </div>
              </div>
            </div>

            <!-- Répartition par Priorité -->
            <div class="rounded-xl bg-white p-6 shadow-sm">
              <h2 class="mb-6 text-lg font-semibold text-gray-900">Répartition par Priorité</h2>
              <div class="h-48">
                <canvas ref="priorityChart"></canvas>
              </div>
            </div>

            <!-- Tâches Urgentes -->
            <div class="rounded-xl bg-white p-6 shadow-sm">
              <div class="mb-6 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Tâches Urgentes</h2>
                <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">
                  {{ dashboardData.urgent_tasks?.length || 0 }}
                </span>
              </div>
              <div class="space-y-3">
                <div
                  v-for="task in dashboardData.urgent_tasks"
                  :key="task.id"
                  class="rounded-lg border-l-4 p-3 transition-all cursor-pointer"
                  :class="[
                    task.is_overdue ? 'border-red-500 bg-red-50 hover:bg-red-100' : 'border-orange-500 bg-orange-50 hover:bg-orange-100'
                  ]"
                  @click="goToTask(task.id)"
                >
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <h4 class="font-medium text-gray-900">{{ task.title }}</h4>
                      <p class="mt-1 text-xs text-gray-600">{{ task.project }}</p>
                      <div class="mt-2 flex items-center gap-2">
                        <span :class="[
                          'rounded-full px-2 py-0.5 text-xs font-medium',
                          getPriorityColor(task.priority)
                        ]">
                          {{ task.priority }}
                        </span>
                        <span class="text-xs text-gray-500">
                          <svg class="mr-1 inline h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
                          {{ task.due_date }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </admin-layout>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api/axios'
import Chart from 'chart.js/auto'
import AdminLayout from '../components/layout/AdminLayout.vue'

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

const statsCards = ref([
  {
    title: 'Projets Actifs',
    value: 0,
    change: '+0%',
    trend: 'up',
    icon: 'folder-kanban',
    color: 'bg-blue-500',
    lightColor: 'bg-blue-50',
    textColor: 'text-blue-600'
  },
  {
    title: 'Tâches En Cours',
    value: 0,
    change: '+0%',
    trend: 'up',
    icon: 'list-todo',
    color: 'bg-purple-500',
    lightColor: 'bg-purple-50',
    textColor: 'text-purple-600'
  },
  {
    title: 'Taux Complétion',
    value: '0%',
    change: '+0%',
    trend: 'up',
    icon: 'target',
    color: 'bg-green-500',
    lightColor: 'bg-green-50',
    textColor: 'text-green-600'
  },
  {
    title: 'Tâches En Retard',
    value: 0,
    change: '0%',
    trend: 'down',
    icon: 'alert-circle',
    color: 'bg-red-500',
    lightColor: 'bg-red-50',
    textColor: 'text-red-600'
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
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4
          },
          {
            label: 'Tâches totales',
            data: dashboardData.value.monthly_progress.map(d => d.taches),
            borderColor: '#8b5cf6',
            backgroundColor: 'rgba(139, 92, 246, 0.1)',
            tension: 0.4
          },
          {
            label: 'Complétées',
            data: dashboardData.value.monthly_progress.map(d => d.completes),
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom'
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
          backgroundColor: dashboardData.value.status_distribution.map(d => d.color)
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
          backgroundColor: dashboardData.value.priority_distribution.map(d => d.color)
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
}

const getPriorityColor = (priority) => {
  const colors = {
    'Élevée': 'bg-red-100 text-red-700',
    'Moyenne': 'bg-orange-100 text-orange-700',
    'faible': 'bg-blue-100 text-blue-700'
  }
  return colors[priority] || 'bg-gray-100 text-gray-700'
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