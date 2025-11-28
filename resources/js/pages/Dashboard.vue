<!-- resources\js\pages\Dashboard.vue -->
<template>
  <AdminLayout>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 p-6 transition-all duration-300">
      <!-- Header avec navigation workspace -->
      <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
          <div class="flex items-center gap-4">
            <div class="p-3 rounded-2xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-gray-200/50 dark:border-gray-700/50 shadow-sm">
              <FolderKanbanIcon class="w-8 h-8 text-brand-600 dark:text-brand-400" />
            </div>
            <div>
              <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                Tableau de Bord
              </h1>
              <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Vue d'ensemble de vos projets et performances
              </p>
            </div>
          </div>

          <div class="flex items-center gap-3 flex-wrap">
            <!-- Filtre Workspace -->
            <div class="relative group">
              <select
                v-model="selectedWorkspace"
                @change="loadDashboardData"
                class="appearance-none rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 pl-10 pr-8 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all duration-200 cursor-pointer"
              >
                <option value="all">Tous les workspaces</option>
                <!-- CORRECTION : Ajouter une vérification de null -->
                <option 
                  v-for="workspace in filteredWorkspaces" 
                  :key="workspace?.id || 'null'" 
                  :value="workspace?.id"
                >
                  {{ workspace?.nom || 'Workspace inconnu' }}
                </option>
              </select>
              <BuildingOfficeIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
              <ChevronDownIcon class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
            </div>

            <!-- Filtre Période -->
            <div class="relative">
              <select
                v-model="selectedPeriod"
                @change="loadDashboardData"
                class="rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 pr-8 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all duration-200 cursor-pointer"
              >
                <option value="week">Cette semaine</option>
                <option value="month">Ce mois</option>
                <option value="quarter">Ce trimestre</option>
                <option value="year">Cette année</option>
              </select>
            </div>

            <!-- Bouton Actualiser -->
            <button
              @click="loadDashboardData"
              class="rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 px-4 py-2.5 text-sm font-medium text-white hover:from-brand-700 hover:to-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-all duration-200 flex items-center gap-2 shadow-lg hover:shadow-xl transform hover:scale-105"
            >
              <RefreshIcon class="w-4 h-4" :class="{ 'animate-spin': loading }" />
              Actualiser
            </button>
          </div>
        </div>
      </div>

      <!-- Loading State Animé -->
      <div v-if="loading" class="flex items-center justify-center py-20">
        <div class="text-center">
          <div class="relative">
            <div class="w-16 h-16 border-4 border-brand-200 dark:border-brand-800 rounded-full animate-spin"></div>
            <div
              class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-8 h-8 border-4 border-transparent border-t-brand-600 rounded-full animate-spin">
            </div>
          </div>
          <p class="mt-4 text-gray-600 dark:text-gray-400 font-medium">Chargement des données...</p>
        </div>
      </div>

      <!-- Dashboard Content -->
      <div v-else class="space-y-8">
        <!-- Stats Cards Améliorées -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
          <div v-for="(stat, index) in statsCards" :key="index"
            class="group relative overflow-hidden rounded-2xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm p-6 shadow-lg hover:shadow-2xl transition-all duration-500 border border-gray-200/50 dark:border-gray-700/50 hover:border-brand-300/30 dark:hover:border-brand-600/30"
            :style="`--hover-color: ${stat.hoverColor}`">
            <!-- Effet de fond animé -->
            <div
              class="absolute inset-0 bg-gradient-to-br from-transparent via-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500">
            </div>

            <!-- Point lumineux au hover -->
            <div
              class="absolute -inset-1 bg-gradient-to-r from-transparent via-[var(--hover-color)]/10 to-transparent opacity-0 group-hover:opacity-100 blur-lg transition-all duration-500">
            </div>

            <div class="relative flex items-start justify-between">
              <div class="flex-1">
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">
                  {{ stat.title }}
                </p>
                <p class="mt-3 text-4xl font-bold text-gray-900 dark:text-white">
                  {{ stat.value }}
                </p>
                <div class="mt-3 flex items-center">
                  <span :class="[
                    'flex items-center text-sm font-semibold px-2 py-1 rounded-full transition-all duration-300',
                    stat.trend === 'up'
                      ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
                      : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'
                  ]">
                    <TrendingUpIcon v-if="stat.trend === 'up'" class="mr-1 h-4 w-4" />
                    <TrendingDownIcon v-else class="mr-1 h-4 w-4" />
                    {{ stat.change }}
                  </span>
                  <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">vs mois dernier</span>
                </div>
              </div>
              <div :class="['rounded-xl p-3 transition-all duration-300 group-hover:scale-110', stat.lightColor]">
                <component :is="stat.icon"
                  :class="['h-7 w-7 transition-transform duration-300 group-hover:scale-110', stat.textColor]" />
              </div>
            </div>
          </div>
        </div>

        <!-- Main Grid -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
          <!-- Left Column - 2/3 width -->
          <div class="space-y-8 lg:col-span-2">
            <!-- Progression Mensuelle avec Graphique Amélioré -->
            <div
              class="rounded-2xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm p-6 shadow-lg border border-gray-200/50 dark:border-gray-700/50">
              <div class="flex items-center justify-between mb-6">
                <div>
                  <h2 class="text-xl font-bold text-gray-900 dark:text-white">Progression Mensuelle</h2>
                  <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Évolution des projets et tâches</p>
                </div>
                <div class="flex items-center gap-4 text-sm">
                  <div v-for="legend in chartLegends" :key="legend.label" class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: legend.color }"></div>
                    <span class="text-gray-600 dark:text-gray-400">{{ legend.label }}</span>
                  </div>
                </div>
              </div>
              <div class="h-80">
                <canvas ref="monthlyChart"></canvas>
              </div>
            </div>

            <!-- Projets Récents avec Design Trello Amélioré -->
            <div
              class="rounded-2xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm p-6 shadow-lg border border-gray-200/50 dark:border-gray-700/50">
              <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Projets Récents</h2>
                <router-link to="/projets/mes-projets"
                  class="group flex items-center gap-2 text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition-all duration-200 font-semibold">
                  Voir tout
                  <ArrowRightIcon
                    class="w-4 h-4 transform group-hover:translate-x-1 transition-transform duration-200" />
                </router-link>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="project in dashboardData.recent_projects" :key="project.id"
                  class="group relative rounded-xl bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-700/50 p-5 shadow-sm border border-gray-200/70 dark:border-gray-600/50 hover:shadow-xl transition-all duration-300 cursor-pointer hover:border-brand-300/50 dark:hover:border-brand-500/50 overflow-hidden"
                  @click="goToProject(project.id)">
                  <!-- Effet de fond au hover -->
                  <div
                    class="absolute inset-0 bg-gradient-to-r from-brand-500/5 to-accent-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                  </div>

                  <div class="relative">
                    <!-- En-tête avec badge de statut -->
                    <div class="flex items-start justify-between mb-3">
                      <div class="flex items-center gap-2">
                        <div class="w-2 h-8 rounded-full bg-gradient-to-b from-brand-500 to-accent-500"></div>
                        <h3
                          class="font-bold text-gray-900 dark:text-white group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors duration-200 line-clamp-1">
                          {{ project.name }}
                        </h3>
                      </div>
                      <span
                        class="rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:text-gray-300">
                        {{ project.code }}
                      </span>
                    </div>

                    <!-- Métriques du projet -->
                    <div class="flex items-center gap-4 mb-4 text-sm text-gray-600 dark:text-gray-400 flex-wrap">
                      <div class="flex items-center gap-1.5">
                        <UsersIcon class="h-4 w-4" />
                        <span>{{ project.team }} membres</span>
                      </div>
                      <div class="flex items-center gap-1.5">
                        <CheckCircleIcon class="h-4 w-4" />
                        <span>{{ project.tasks.completed }}/{{ project.tasks.total }} tâches</span>
                      </div>
                      <div v-if="project.due_date" class="flex items-center gap-1.5">
                        <CalendarIcon class="h-4 w-4" />
                        <span>{{ project.due_date }}</span>
                      </div>
                    </div>

                    <!-- Barre de progression améliorée -->
                    <div class="space-y-2">
                      <div class="flex items-center justify-between text-sm">
                        <span class="font-semibold text-gray-700 dark:text-gray-300">Progression</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ project.progress }}%</span>
                      </div>
                      <div class="w-full h-2.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div
                          class="h-full rounded-full bg-gradient-to-r from-brand-500 to-accent-500 transition-all duration-1000 ease-out"
                          :style="{ width: `${project.progress}%` }" />
                      </div>
                    </div>

                    <!-- Badge d'état -->
                    <div class="mt-4 flex justify-between items-center">
                      <div class="flex items-center gap-2">
                        <div class="flex -space-x-2">
                          <div v-for="member in project.preview_members" :key="member.id"
                            class="w-6 h-6 rounded-full border-2 border-white dark:border-gray-800 bg-gray-300 flex items-center justify-center text-xs font-semibold text-gray-700">
                            {{ member.initials }}
                          </div>
                        </div>
                        <span v-if="project.additional_members" class="text-xs text-gray-500 dark:text-gray-400">
                          +{{ project.additional_members }}
                        </span>
                      </div>
                      <div :class="[
                        'px-2.5 py-1 rounded-full text-xs font-semibold transition-colors duration-200',
                        getProjectStatusClass(project.status)
                      ]">
                        {{ getProjectStatusText(project.status) }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column - 1/3 width -->
          <div class="space-y-8">
            <!-- Répartition par Statut avec Design Circulaire -->
            <div
              class="rounded-2xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm p-6 shadow-lg border border-gray-200/50 dark:border-gray-700/50">
              <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Répartition par Statut</h2>
              <div class="flex items-center justify-center">
                <div class="relative h-48 w-48">
                  <canvas ref="statusChart"></canvas>
                  <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center">
                      <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ totalTasks }}
                      </div>
                      <div class="text-sm text-gray-500 dark:text-gray-400">Total tâches</div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="mt-6 space-y-3">
                <div v-for="item in dashboardData.status_distribution" :key="item.name"
                  class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200 group">
                  <div class="flex items-center gap-3">
                    <div class="w-4 h-4 rounded-full transition-transform duration-200 group-hover:scale-125"
                      :style="{ backgroundColor: item.color }" />
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ item.name }}</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="font-bold text-gray-900 dark:text-white">{{ item.value }}</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                      ({{ Math.round((item.value / totalTasks) * 100) }}%)
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tâches Urgentes avec Badges de Priorité -->
            <div
              class="rounded-2xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm p-6 shadow-lg border border-gray-200/50 dark:border-gray-700/50">
              <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Tâches Urgentes</h2>
                <span
                  class="rounded-full bg-red-100 dark:bg-red-900/30 px-3 py-1 text-sm font-semibold text-red-700 dark:text-red-400 flex items-center gap-2">
                  <ExclamationTriangleIcon class="w-4 h-4" />
                  {{ dashboardData.urgent_tasks?.length || 0 }}
                </span>
              </div>

              <div class="space-y-3">
                <div v-for="task in dashboardData.urgent_tasks" :key="task.id"
                  class="group relative rounded-xl p-4 transition-all duration-300 cursor-pointer border-l-4 hover:shadow-lg transform hover:scale-105"
                  :class="[
                    task.is_overdue
                      ? 'border-red-500 bg-gradient-to-r from-red-50 to-red-100/50 dark:from-red-900/20 dark:to-red-900/10 hover:from-red-100 hover:to-red-200/50 dark:hover:from-red-900/30 dark:hover:to-red-900/20'
                      : 'border-accent-500 bg-gradient-to-r from-accent-50 to-accent-100/50 dark:from-accent-900/20 dark:to-accent-900/10 hover:from-accent-100 hover:to-accent-200/50 dark:hover:from-accent-900/30 dark:hover:to-accent-900/20'
                  ]" @click="goToTask(task.id)">
                  <div class="flex items-start justify-between">
                    <div class="flex-1 min-w-0">
                      <h4
                        class="font-semibold text-gray-900 dark:text-white group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors duration-200 line-clamp-2 mb-2">
                        {{ task.title }}
                      </h4>
                      <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-1">{{ task.project }}</p>

                      <div class="flex items-center gap-2 flex-wrap">
                        <span :class="[
                          'rounded-full px-2.5 py-1 text-xs font-semibold transition-all duration-200 group-hover:scale-105',
                          getPriorityBadgeClass(task.priority)
                        ]">
                          <span class="flex items-center gap-1">
                            <ExclamationCircleIcon class="w-3 h-3" />
                            {{ task.priority }}
                          </span>
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                          <ClockIcon class="w-3 h-3" />
                          {{ task.due_date }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- État vide -->
                <div v-if="!dashboardData.urgent_tasks?.length" class="text-center py-8">
                  <CheckCircleIcon class="mx-auto h-12 w-12 text-green-500 dark:text-green-400 mb-3" />
                  <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Aucune tâche urgente</p>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Tout est sous contrôle !</p>
                </div>
              </div>
            </div>

            <!-- Activité Récente -->
            <div
              class="rounded-2xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm p-6 shadow-lg border border-gray-200/50 dark:border-gray-700/50">
              <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Activité Récente</h2>
              <div class="space-y-4">
                <div v-for="activity in recentActivities" :key="activity.id"
                  class="flex items-start gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200 group">
                  <div
                    class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-brand-500 to-accent-500 flex items-center justify-center">
                    <component :is="activity.icon" class="w-4 h-4 text-white" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-2">
                      {{ activity.description }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      {{ activity.timestamp }}
                    </p>
                  </div>
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
import { ref, onMounted, nextTick, computed, onBeforeUnmount } from 'vue'
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
  AlertCircleIcon,
  BuildingOfficeIcon,
  ChevronDownIcon,
  ExclamationTriangleIcon,
  ExclamationCircleIcon
} from '@/icons'

const router = useRouter()
const loading = ref(true)
const selectedPeriod = ref('month')
const selectedWorkspace = ref('all')
const workspaces = ref([])

// Nouveaux états pour les données améliorées
const recentActivities = ref([
  {
    id: 1,
    description: 'Nouveau projet "Refonte Site Web" créé',
    timestamp: 'Il y a 2 min',
    icon: FolderKanbanIcon
  },
  {
    id: 2,
    description: 'Tâche "Design UI" marquée comme terminée',
    timestamp: 'Il y a 15 min',
    icon: CheckCircleIcon
  },
  {
    id: 3,
    description: '3 nouvelles tâches assignées',
    timestamp: 'Il y a 1 heure',
    icon: ListTodoIcon
  }
])

// CORRECTION : Computed property avec gestion d'erreur
const filteredWorkspaces = computed(() => {
  if (!Array.isArray(workspaces.value)) {
    console.warn('⚠️ workspaces.value n\'est pas un tableau:', workspaces.value)
    return []
  }
  
  return workspaces.value
    .filter(workspace => workspace && workspace.id)
    .map(workspace => ({
      id: workspace.id,
      nom: workspace.nom || workspace.name || 'Workspace sans nom',
      projets_count: workspace.projets_count || workspace.projects_count || 0,
      code: workspace.code || '',
      description: workspace.description || ''
    }))
})

 

// ✅ Écoute des changements de filtre depuis la sidebar
const handleDashboardFilterChange = (event) => {
  console.log('🔄 Dashboard: Filtre changé', event.detail);
  selectedWorkspace.value = event.detail.workspaceId;
  loadDashboardData();
};

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

// Nouvelle palette de couleurs moderne
const colors = {
  brand: {
    500: '#6366f1',
    600: '#4f46e5',
    700: '#4338ca'
  },
  accent: {
    500: '#f59e0b',
    600: '#d97706'
  },
  success: '#10b981',
  warning: '#f59e0b',
  error: '#ef4444',
  gray: {
    50: '#f9fafb',
    100: '#f3f4f6',
    200: '#e5e7eb',
    300: '#d1d5db',
    400: '#9ca3af',
    500: '#6b7280',
    600: '#4b5563',
    700: '#374151',
    800: '#1f2937',
    900: '#111827'
  }
}

const statsCards = ref([
  {
    title: 'Projets Actifs',
    value: 0,
    change: '+0%',
    trend: 'up',
    icon: FolderKanbanIcon,
    lightColor: 'bg-blue-50 dark:bg-blue-900/20 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/30',
    textColor: 'text-blue-600 dark:text-blue-400',
    hoverColor: colors.brand[500]
  },
  {
    title: 'Tâches En Cours',
    value: 0,
    change: '+0%',
    trend: 'up',
    icon: ListTodoIcon,
    lightColor: 'bg-purple-50 dark:bg-purple-900/20 group-hover:bg-purple-100 dark:group-hover:bg-purple-900/30',
    textColor: 'text-purple-600 dark:text-purple-400',
    hoverColor: colors.accent[500]
  },
  {
    title: 'Taux Complétion',
    value: '0%',
    change: '+0%',
    trend: 'up',
    icon: TargetIcon,
    lightColor: 'bg-green-50 dark:bg-green-900/20 group-hover:bg-green-100 dark:group-hover:bg-green-900/30',
    textColor: 'text-green-600 dark:text-green-400',
    hoverColor: colors.success
  },
  {
    title: 'Tâches En Retard',
    value: 0,
    change: '0%',
    trend: 'down',
    icon: AlertCircleIcon,
    lightColor: 'bg-red-50 dark:bg-red-900/20 group-hover:bg-red-100 dark:group-hover:bg-red-900/30',
    textColor: 'text-red-600 dark:text-red-400',
    hoverColor: colors.error
  }
])

const chartLegends = ref([
  { label: 'Projets', color: colors.brand[500] },
  { label: 'Tâches totales', color: colors.accent[500] },
  { label: 'Complétées', color: colors.success }
])

// Computed properties
const totalTasks = computed(() => {
  return dashboardData.value.status_distribution?.reduce((total, item) => total + item.value, 0) || 0
})

const loadDashboardData = async () => {
  loading.value = true
  try {
    const response = await api.get('/dashboard', {
      params: {
        period: selectedPeriod.value,
        workspace_id: selectedWorkspace.value === 'all' ? null : selectedWorkspace.value
      }
    })

    dashboardData.value = response.data

    // Update stats cards avec animations
    statsCards.value.forEach((card, index) => {
      setTimeout(() => {
        const statKey = Object.keys(response.data.stats)[index]
        if (response.data.stats[statKey]) {
          card.value = index === 2 ? response.data.stats[statKey].value + '%' : response.data.stats[statKey].value
          card.change = response.data.stats[statKey].change
          card.trend = response.data.stats[statKey].trend
        }
      }, index * 150)
    })

    // Charger les workspaces
    await loadWorkspaces()

    // Wait for DOM update before creating charts
    await nextTick()
    createCharts()
  } catch (error) {
    console.error('Error loading dashboard data:', error)
  } finally {
    loading.value = false
  }
}

// CORRECTION : Fonction robuste pour charger les workspaces
const loadWorkspaces = async () => {
  try {
    console.log('🔄 Chargement des workspaces...')
    const response = await api.get('/workspaces')
    console.log('📦 Réponse workspaces:', response)
    console.log('📦 response.data:', response.data)
    
    // CORRECTION : Vérifier le type de response.data
    let workspacesData = []
    
    if (Array.isArray(response.data)) {
      // Si c'est directement un tableau
      workspacesData = response.data
    } else if (response.data && Array.isArray(response.data.data)) {
      // Si c'est un objet avec une propriété data (format Laravel typique)
      workspacesData = response.data.data
    } else if (response.data && response.data.workspaces) {
      // Si c'est un objet avec une propriété workspaces
      workspacesData = response.data.workspaces
    } else {
      console.warn('❌ Format de réponse inattendu:', response.data)
      workspacesData = []
    }
    
    // Filtrer les workspaces null et sans ID
    workspaces.value = workspacesData.filter(workspace => 
      workspace && 
      workspace.id !== null && 
      workspace.id !== undefined
    )
    
    console.log('✅ Workspaces chargés:', workspaces.value)
    
  } catch (error) {
    console.error('❌ Error loading workspaces:', error)
    console.error('❌ Détails de l\'erreur:', error.response?.data || error.message)
    // En cas d'erreur, initialiser avec un tableau vide
    workspaces.value = []
  }
}

const createCharts = () => {
  // Destroy existing charts
  [monthlyChartInstance, statusChartInstance, priorityChartInstance].forEach(chart => {
    if (chart) chart.destroy()
  })

  // Get theme for chart colors
  const isDark = document.documentElement.classList.contains('dark')
  const textColor = isDark ? colors.gray[300] : colors.gray[600]
  const gridColor = isDark ? colors.gray[700] : colors.gray[200]
  const bgColor = isDark ? colors.gray[800] : '#ffffff'

  // Monthly Progress Chart avec design amélioré
  if (monthlyChart.value && dashboardData.value.monthly_progress?.length) {
    monthlyChartInstance = new Chart(monthlyChart.value, {
      type: 'line',
      data: {
        labels: dashboardData.value.monthly_progress.map(d => d.month),
        datasets: [
          {
            label: 'Projets',
            data: dashboardData.value.monthly_progress.map(d => d.projets),
            borderColor: colors.brand[500],
            backgroundColor: `${colors.brand[500]}20`,
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: colors.brand[500],
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8
          },
          {
            label: 'Tâches totales',
            data: dashboardData.value.monthly_progress.map(d => d.taches),
            borderColor: colors.accent[500],
            backgroundColor: `${colors.accent[500]}20`,
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: colors.accent[500],
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8
          },
          {
            label: 'Complétées',
            data: dashboardData.value.monthly_progress.map(d => d.completes),
            borderColor: colors.success,
            backgroundColor: `${colors.success}20`,
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: colors.success,
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8
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
              usePointStyle: true,
              padding: 20,
              font: {
                size: 12,
                weight: '500'
              }
            }
          },
          tooltip: {
            backgroundColor: bgColor,
            titleColor: textColor,
            bodyColor: textColor,
            borderColor: gridColor,
            borderWidth: 1,
            cornerRadius: 8,
            displayColors: true
          }
        },
        scales: {
          x: {
            grid: {
              color: gridColor,
              drawBorder: false
            },
            ticks: {
              color: textColor
            }
          },
          y: {
            grid: {
              color: gridColor,
              drawBorder: false
            },
            ticks: {
              color: textColor
            }
          }
        },
        interaction: {
          intersect: false,
          mode: 'index'
        },
        animations: {
          tension: {
            duration: 1000,
            easing: 'linear'
          }
        }
      }
    })
  }

  // Status Distribution Chart avec design doughnut amélioré
  if (statusChart.value && dashboardData.value.status_distribution?.length) {
    statusChartInstance = new Chart(statusChart.value, {
      type: 'doughnut',
      data: {
        labels: dashboardData.value.status_distribution.map(d => d.name),
        datasets: [{
          data: dashboardData.value.status_distribution.map(d => d.value),
          backgroundColor: [
            colors.brand[500],
            colors.accent[500],
            colors.success,
            colors.warning,
            colors.error
          ],
          borderWidth: 0,
          borderRadius: 8,
          spacing: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: bgColor,
            titleColor: textColor,
            bodyColor: textColor,
            borderColor: gridColor,
            borderWidth: 1,
            cornerRadius: 8
          }
        },
        animation: {
          animateScale: true,
          animateRotate: true
        }
      }
    })
  }
}

// Nouvelles méthodes helpers
const getProjectStatusClass = (status) => {
  const classes = {
    'active': 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
    'completed': 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
    'archived': 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
    'on_hold': 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400'
  }
  return classes[status] || 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
}

const getProjectStatusText = (status) => {
  const texts = {
    'active': 'Actif',
    'completed': 'Terminé',
    'archived': 'Archivé',
    'on_hold': 'En pause'
  }
  return texts[status] || status
}

const getPriorityBadgeClass = (priority) => {
  const classes = {
    'Élevée': 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800',
    'Moyenne': 'bg-accent-100 dark:bg-accent-900/30 text-accent-700 dark:text-accent-400 border border-accent-200 dark:border-accent-800',
    'faible': 'bg-brand-100 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400 border border-brand-200 dark:border-brand-800'
  }
  return classes[priority] || 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600'
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
  loadDashboardData();
  window.addEventListener('dashboard-filter-changed', handleDashboardFilterChange);
})

onBeforeUnmount(() => {
  window.removeEventListener('dashboard-filter-changed', handleDashboardFilterChange);
})

</script>

<style scoped>
/* Custom styles for enhanced design */
.line-clamp-1 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 1;
}

.line-clamp-2 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

/* Smooth transitions for all interactive elements */
* {
  transition-property: color, background-color, border-color, transform, box-shadow;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

.dark ::-webkit-scrollbar-thumb {
  background: #4b5563;
}

.dark ::-webkit-scrollbar-thumb:hover {
  background: #6b7280;
}
</style>