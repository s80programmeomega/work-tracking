<!-- resources/js/pages/Dashboard.vue - VERSION AMÉLIORÉE AVEC KANBAN -->
<template>
  <AdminLayout>
    <div
      class="min-h-screen bg-gray-100 dark:bg-gray-900 p-6 transition-colors duration-300">
      <!-- Header avec navigation workspace et membre -->
      <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
          <div class="flex items-center gap-4">
            <div
              class="p-3 rounded-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
              <FolderKanbanIcon class="w-8 h-8 text-brand-500 dark:text-brand-400" />
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Tableau de Bord
              </h1>
              <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ currentWorkspaceName || 'Vue d\'ensemble de vos projets et performances' }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-3 flex-wrap">
            <!-- Filtre Workspace -->
            <div class="relative group">
              <select v-model="selectedWorkspace" @change="onWorkspaceChange"
                class="appearance-none rounded-[4px] border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 pl-10 pr-8 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-1 focus:ring-brand-500 transition-colors duration-200 cursor-pointer">
                <option value="all">Tous les workspaces</option>
                <option v-for="workspace in workspaces" :key="workspace.id" :value="workspace.id">
                  {{ workspace.nom }}
                </option>
              </select>
              <BuildingOfficeIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
              <ChevronDownIcon class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
            </div>

            <!-- Filtre Membre -->
            <!-- <div class="relative group" v-if="workspaceMembers.length > 0 && selectedWorkspace !== 'all'">
              <select v-model="selectedMember" @change="loadDashboardData"
                class="appearance-none rounded-[4px] border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 pl-10 pr-8 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-1 focus:ring-brand-500 transition-colors duration-200 cursor-pointer">
                <option value="all">Tous les membres</option>
                <option v-for="member in workspaceMembers" :key="member.id" :value="member.id">
                  {{ member.prenom }} {{ member.nom }}
                </option>
              </select>
              <UsersIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
              <ChevronDownIcon class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
            </div> -->

            <!-- Filtre Période -->
            <div class="relative">
              <select v-model="selectedPeriod" @change="loadDashboardData"
                class="rounded-[4px] border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 pr-8 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-1 focus:ring-brand-500 transition-colors duration-200 cursor-pointer">
                <option value="week">Cette semaine</option>
                <option value="month">Ce mois</option>
                <option value="quarter">Ce trimestre</option>
                <option value="year">Cette année</option>
              </select>
            </div>

            <!-- Bouton Actualiser -->
            <button @click="loadDashboardData"
              class="rounded-[4px] bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-all duration-200 flex items-center gap-2">
              <RefreshIcon class="w-4 h-4" :class="{ 'animate-spin': loading }" />
              Actualiser
            </button>
          </div>
        </div>

        <!-- Filtres avancés -->
        <div class="mt-4 flex flex-wrap gap-2">
          <div class="relative">
            <select v-model="filters.projectStatus" @change="loadDashboardData"
              class="rounded-[4px] border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-1 focus:ring-brand-500 transition-colors duration-200 cursor-pointer">
              <option value="all">Tous les statuts</option>
              <option value="active">Projets actifs</option>
              <option value="completed">Projets terminés</option>
              <option value="archived">Projets archivés</option>
            </select>
          </div>

          <div class="relative">
            <select v-model="filters.priority" @change="loadDashboardData"
              class="rounded-[4px] border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-1 focus:ring-brand-500 transition-colors duration-200 cursor-pointer">
              <option value="all">Toutes priorités</option>
              <option value="high">Priorité élevée</option>
              <option value="medium">Priorité moyenne</option>
              <option value="low">Priorité faible</option>
            </select>
          </div>

          <button v-if="hasActiveFilters" @click="resetFilters"
            class="rounded-[4px] border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200 flex items-center gap-1">
            <XIcon class="w-3 h-3" />
            Réinitialiser
          </button>
        </div>
      </div>

      <!-- Loading State -->
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
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
          <div v-for="(stat, index) in statsCards" :key="index"
            class="group bg-white dark:bg-gray-800 rounded-3 p-5 border border-gray-200 dark:border-gray-700 hover:border-brand-300 dark:hover:border-brand-600 transition-colors duration-200">

            <div class="flex items-start justify-between">
              <div class="flex-1">
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">
                  {{ stat.title }}
                </p>
                <p class="mt-3 text-4xl font-bold text-gray-900 dark:text-white">
                  {{ stat.value }}
                </p>
                <div class="mt-3 flex items-center">
                  <span :class="[
                    'flex items-center text-[10px] font-medium px-1.5 py-0.5 rounded-1 border',
                    stat.trend === 'up'
                      ? 'bg-success-50 text-success-500 border-success-300 dark:bg-success-500/15 dark:text-success-300 dark:border-success-500/30'
                      : 'bg-error-50 text-error-500 border-error-300 dark:bg-error-500/15 dark:text-error-300 dark:border-error-500/30'
                  ]">
                    <TrendingUpIcon v-if="stat.trend === 'up'" class="mr-1 h-4 w-4" />
                    <TrendingDownIcon v-else class="mr-1 h-4 w-4" />
                    {{ stat.change }}
                  </span>
                  <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">vs mois dernier</span>
                </div>
              </div>
              <div :class="['rounded-3 p-2.5', stat.lightColor]">
                <component :is="stat.icon" :class="['h-6 w-6', stat.textColor]" />
              </div>
            </div>
          </div>
        </div>

        <!-- Main Grid -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
          <!-- Left Column - 2/3 width -->
          <div class="space-y-8 lg:col-span-2">
            <!-- Vue Kanban des Projets -->
            <div
              class="rounded-3 bg-white dark:bg-gray-800 p-6 border border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between mb-6">
                <div>
                  <h2 class="text-xl font-bold text-gray-900 dark:text-white">Vue Kanban des Projets</h2>
                  <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Organisez vos projets par statut</p>
                </div>
                <router-link to="/projets/mes-projets"
                  class="group flex items-center gap-2 text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition-all duration-200 font-semibold text-sm">
                  Voir tout
                  <ArrowRightIcon
                    class="w-4 h-4 transform group-hover:translate-x-1 transition-transform duration-200" />
                </router-link>
              </div>

              <!-- Kanban Board -->
              <div
                class="flex gap-4 overflow-x-auto pb-4 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-transparent">
                <KanbanColumn v-for="column in kanbanColumns" :key="column.status" :column="column"
                  :tasks="getProjectsByStatus(column.status)" @task-click="goToProject"
                  @task-drop="handleProjectDrop" />
              </div>
            </div>

            <!-- Progression Mensuelle -->
            <div
              class="rounded-3 bg-white dark:bg-gray-800 p-6 border border-gray-200 dark:border-gray-700">
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
          </div>

          <!-- Right Column - 1/3 width -->
          <div class="space-y-8">
            <!-- Mes Tâches Assignées -->
            <div
              class="rounded-3 bg-white dark:bg-gray-800 p-6 border border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Mes Tâches</h2>
                <span
                  class="rounded-1 bg-brand-50 dark:bg-brand-500/15 px-2 py-0.5 text-[11px] font-semibold text-brand-500 dark:text-brand-400 border border-brand-200 dark:border-brand-500/30">
                  {{ myTasks.length }}
                </span>
              </div>

              <div class="space-y-3">
                <div v-for="task in myTasks" :key="task.id"
                  class="group bg-white dark:bg-gray-800 rounded-3 p-3 border border-gray-200 dark:border-gray-700 hover:border-brand-300 dark:hover:border-brand-600 transition-colors duration-200 cursor-pointer"
                  :class="taskBorderClass(task)" @click="goToTask(task.id)">
                  <div class="flex items-start justify-between mb-2">
                    <h4
                      class="font-medium text-gray-900 dark:text-white group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors line-clamp-2 flex-1">
                      {{ task.title }}
                    </h4>
                    <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-1 border ml-2" :class="priorityClass(task)">
                      {{ task.priority }}
                    </span>
                  </div>

                  <p class="text-xs text-gray-500 dark:text-gray-400 mb-2 line-clamp-1">{{ task.project }}</p>

                  <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-500 dark:text-gray-400 flex items-center gap-1">
                      <ClockIcon class="w-3 h-3" />
                      {{ task.due_date }}
                    </span>
                    <span class="font-medium" :class="statusClass(task)">
                      {{ task.status }}
                    </span>
                  </div>
                </div>

                <div v-if="myTasks.length === 0" class="text-center py-8">
                  <CheckCircleIcon class="mx-auto h-12 w-12 text-green-500 dark:text-green-400 mb-3" />
                  <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Aucune tâche assignée</p>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Vous êtes à jour !</p>
                </div>
              </div>
            </div>

            <!-- Membres de l'équipe -->
            <div
              class="rounded-3 bg-white dark:bg-gray-800 p-6 border border-gray-200 dark:border-gray-700">
              <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Membres de l'équipe</h2>
              <div class="space-y-3">
                <div v-for="member in teamMembers" :key="member.id"
                  class="flex items-center gap-3 p-3 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200 group">
                  <div class="shrink-0">
                    <div v-if="member.avatar"
                      class="w-10 h-10 rounded-full overflow-hidden ring-2 ring-white dark:ring-gray-700">
                      <img :src="member.avatar" :alt="memberName(member)" class="w-full h-full object-cover">
                    </div>
                    <div v-else
                      class="w-10 h-10 rounded-full bg-brand-500 flex items-center justify-center text-white font-semibold text-sm ring-2 ring-white dark:ring-gray-700">
                      {{ memberInitials(member) }}
                    </div>
                  </div>

                  <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                      {{ memberName(member) }}
                    </h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                      {{ member.email }}
                    </p>
                  </div>

                  <div class="shrink-0 text-right">
                    <div class="text-sm font-bold text-gray-900 dark:text-white">
                      {{ member.taches_count || 0 }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                      tâches
                    </div>
                  </div>
                </div>

                <div v-if="teamMembers.length === 0" class="text-center py-8">
                  <UsersIcon class="mx-auto h-12 w-12 text-gray-400 mb-3" />
                  <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Aucun membre</p>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Les membres de l'équipe apparaîtront ici</p>
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
import { ref, onMounted, nextTick, computed, onBeforeUnmount, markRaw } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api/axios'
import Chart from 'chart.js/auto'
import AdminLayout from '../components/layout/AdminLayout.vue'
import KanbanColumn from './dashboard/KanbanColumn.vue' 
import {
  RefreshIcon,
  TrendingUpIcon,
  TrendingDownIcon,
  ArrowRightIcon,
  UsersIcon,
  CheckCircleIcon,
  ClockIcon,
  FolderKanbanIcon,
  ListTodoIcon,
  TargetIcon,
  AlertCircleIcon,
  BuildingOfficeIcon,
  ChevronDownIcon,
  XIcon
} from '@/icons'
import { useWorkspace } from '@/composables/useWorkspace'

const router = useRouter()
const loading = ref(true)
const selectedPeriod = ref('month')
const selectedWorkspace = ref('all')
const selectedMember = ref('all')

// Utiliser le composable workspace
const {
  currentWorkspace,
  workspaces,
  fetchWorkspaces,
  selectWorkspace,
  onWorkspaceChanged,
  fetchMembers
} = useWorkspace()

const workspaceMembers = ref([])

// Filtres avancés
const filters = ref({
  projectStatus: 'all',
  priority: 'all'
})

// Données du dashboard
const dashboardData = ref({
  stats: {},
  monthly_progress: [],
  recent_projects: [],
  my_tasks: [],
  team_members: []
})

// Computed
const currentWorkspaceName = computed(() => {
  if (selectedWorkspace.value === 'all') {
    return 'Tous les workspaces'
  }
  const workspace = workspaces.value.find(w => w.id === selectedWorkspace.value)
  return workspace?.nom || 'Vue d\'ensemble'
})

const hasActiveFilters = computed(() => {
  return selectedWorkspace.value !== 'all' ||
    selectedMember.value !== 'all' ||
    filters.value.projectStatus !== 'all' ||
    filters.value.priority !== 'all'
})

const myTasks = computed(() => {
  return dashboardData.value.my_tasks || []
})

const teamMembers = computed(() => {
  return dashboardData.value.team_members || []
})

// Colonnes Kanban
const kanbanColumns = ref([
    {
    status: 'pending',
    title: 'En attente',
    color: 'bg-yellow-500',
    textColor: 'text-yellow-700 dark:text-yellow-400',
    bgColor: 'bg-yellow-50 dark:bg-yellow-900/20'
  },
  {
    status: 'active',
    title: 'En cours',
    color: 'bg-green-500',
    textColor: 'text-green-700 dark:text-green-400',
    bgColor: 'bg-green-50 dark:bg-green-900/20'
  },

  {
    status: 'completed',
    title: 'Terminés',
    color: 'bg-blue-500',
    textColor: 'text-blue-700 dark:text-blue-400',
    bgColor: 'bg-blue-50 dark:bg-blue-900/20'
  }
])

// Cartes de statistiques — markRaw() évite la proxification réactive
// des composants d'icônes (Vue émet sinon un avertissement).
const statsCards = ref([
  {
    title: 'Projets Actifs',
    value: 0,
    change: '+0%',
    trend: 'up',
    icon: markRaw(FolderKanbanIcon),
    lightColor: 'bg-blue-50 dark:bg-blue-900/20 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/30',
    textColor: 'text-blue-600 dark:text-blue-400',
    hoverColor: '#3b82f6'
  },
  {
    title: 'Mes Tâches',
    value: 0,
    change: '+0%',
    trend: 'up',
    icon: markRaw(ListTodoIcon),
    lightColor: 'bg-purple-50 dark:bg-purple-900/20 group-hover:bg-purple-100 dark:group-hover:bg-purple-900/30',
    textColor: 'text-purple-600 dark:text-purple-400',
    hoverColor: '#8b5cf6'
  },
  {
    title: 'Taux Complétion',
    value: '0%',
    change: '+0%',
    trend: 'up',
    icon: markRaw(TargetIcon),
    lightColor: 'bg-green-50 dark:bg-green-900/20 group-hover:bg-green-100 dark:group-hover:bg-green-900/30',
    textColor: 'text-green-600 dark:text-green-400',
    hoverColor: '#10b981'
  },
  {
    title: 'En Retard',
    value: 0,
    change: '0%',
    trend: 'down',
    icon: markRaw(AlertCircleIcon),
    lightColor: 'bg-red-50 dark:bg-red-900/20 group-hover:bg-red-100 dark:group-hover:bg-red-900/30',
    textColor: 'text-red-600 dark:text-red-400',
    hoverColor: '#ef4444'
  }
])

const chartLegends = ref([
  { label: 'Projets', color: '#6366f1' },
  { label: 'Tâches totales', color: '#f59e0b' },
  { label: 'Complétées', color: '#10b981' }
])

// Méthodes
const loadDashboardData = async () => {
  loading.value = true
  try {
    const params = {
      period: selectedPeriod.value,
      workspace_id: selectedWorkspace.value === 'all' ? null : selectedWorkspace.value,
      member_id: selectedMember.value === 'all' ? null : selectedMember.value,
      project_status: filters.value.projectStatus === 'all' ? null : filters.value.projectStatus,
      priority: filters.value.priority === 'all' ? null : filters.value.priority
    }

    const response = await api.get('/dashboard', { params })
    dashboardData.value = response.data

    updateStatsCards()
    await nextTick()
    createCharts()
  } catch (error) {
    console.error('Error loading dashboard data:', error)
  } finally {
    loading.value = false
  }
}

const onWorkspaceChange = async () => {
  selectedMember.value = 'all'

  if (selectedWorkspace.value !== 'all') {
    await loadWorkspaceMembers()
  } else {
    workspaceMembers.value = []
  }

  loadDashboardData()
}

const loadWorkspaceMembers = async () => {
  if (selectedWorkspace.value === 'all') {
    workspaceMembers.value = []
    return
  }

  try {
    const members = await fetchMembers(selectedWorkspace.value)
    workspaceMembers.value = members
  } catch (error) {
    console.error('Error loading workspace members:', error)
    workspaceMembers.value = []
  }
}

const updateStatsCards = () => {
  const stats = dashboardData.value.stats || {}
  console.log('Dashboard', dashboardData);
  console.log('Dashboard value', dashboardData.value);
  
  statsCards.value[0].value = stats.projets_actifs?.value || 0
  statsCards.value[0].change = stats.projets_actifs?.change || '+0%'
  statsCards.value[0].trend = stats.projets_actifs?.trend || 'up'

  // 2️⃣ Mes tâches (count uniquement)
  const myTasksCount = Array.isArray(dashboardData.value.my_tasks)
    ? dashboardData.value.my_tasks.length
    : 0

  statsCards.value[1].value = myTasksCount
  statsCards.value[1].change = '+0%'          // ou calcul personnalisé
  statsCards.value[1].trend = 'neutral'        // ou "up/down"

  statsCards.value[2].value = (stats.taux_completion?.value || 0) + '%'
  statsCards.value[2].change = stats.taux_completion?.change || '+0%'
  statsCards.value[2].trend = stats.taux_completion?.trend || 'up'

  statsCards.value[3].value = stats.taches_en_retard?.value || 0
  statsCards.value[3].change = stats.taches_en_retard?.change || '0%'
  statsCards.value[3].trend = stats.taches_en_retard?.trend || 'down'
}

const getProjectsByStatus = (status) => {
  const projects = dashboardData.value.recent_projects || []
  return projects.filter(p => p.status === status)
}

const handleProjectDrop = async (projectId, newStatus) => {
  console.log('Project drop:', projectId, newStatus)
  // TODO: Implémenter la mise à jour du statut du projet
}



const resetFilters = () => {
  selectedWorkspace.value = 'all'
  selectedMember.value = 'all'
  selectedPeriod.value = 'month'
  filters.value = {
    projectStatus: 'all',
    priority: 'all'
  }
  workspaceMembers.value = []
  loadDashboardData()
}
const goToProject = (project) => {
  router.push({ name: 'projets.show', params: { id: project.id } })
}
const goToTask = (taskId) => {
  router.push({ name: 'taches.show', params: { id: taskId } })
}
const taskBorderClass = (task) => {
  if (task.is_overdue) {
    return 'border-l-4 border-l-red-500 bg-red-50/50 dark:bg-red-900/10'
  }
  if (task.priority === 'Élevée') {
    return 'border-l-4 border-l-orange-500 bg-orange-50/50 dark:bg-orange-900/10'
  }
  return ''
}
const priorityClass = (task) => {
  const classes = {
    'Élevée': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    'Moyenne': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
    'Faible': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
  }
  return classes[task.priority] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
}
const statusClass = (task) => {
  const classes = {
    'Terminé': 'text-green-600 dark:text-green-400',
    'En cours': 'text-blue-600 dark:text-blue-400',
    'À faire': 'text-gray-600 dark:text-gray-400'
  }
  return classes[task.status] || 'text-gray-600 dark:text-gray-400'
}
 
const memberName = (member) => {
  return `${member.prenom} ${member.nom}`.trim()
}

const memberInitials = (member) => {
  return `${member?.prenom?.[0] ?? ''}${member?.nom?.[0] ?? ''}`.toUpperCase() || 'U'
}

// Gestion des charts
const monthlyChart = ref(null)
let monthlyChartInstance = null
const createCharts = () => {
  if (monthlyChartInstance) monthlyChartInstance.destroy()
  if (monthlyChart.value && dashboardData.value.monthly_progress?.length) {
    const isDark = document.documentElement.classList.contains('dark')
    const textColor = isDark ? '#d1d5db' : '#4b5563'
    const gridColor = isDark ? '#374151' : '#e5e7eb'


    monthlyChartInstance = new Chart(monthlyChart.value, {
      type: 'line',
      data: {
        labels: dashboardData.value.monthly_progress.map(d => d.month),
        datasets: [
          {
            label: 'Projets',
            data: dashboardData.value.monthly_progress.map(d => d.projets),
            borderColor: '#6366f1',
            backgroundColor: '#6366f120',
            borderWidth: 3,
            tension: 0.4,
            fill: true
          },
          {
            label: 'Tâches totales',
            data: dashboardData.value.monthly_progress.map(d => d.taches),
            borderColor: '#f59e0b',
            backgroundColor: '#f59e0b20',
            borderWidth: 3,
            tension: 0.4,
            fill: true
          },
          {
            label: 'Complétées',
            data: dashboardData.value.monthly_progress.map(d => d.completes),
            borderColor: '#10b981',
            backgroundColor: '#10b98120',
            borderWidth: 3,
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
            labels: { color: textColor }
          }
        },
        scales: {
          x: { grid: { color: gridColor }, ticks: { color: textColor } },
          y: { grid: { color: gridColor }, ticks: { color: textColor } }
        }
      }
    })
  }
}
// Écoute des changements depuis la sidebar
const handleWorkspaceChange = (event) => {
  console.log('🔄 Dashboard: Workspace changé depuis sidebar', event.detail)
  selectedWorkspace.value = event.detail.workspace.id
  loadDashboardData()
}
// Lifecycle
let unsubscribeWorkspace = null
onMounted(async () => {
  console.log('🚀 Montage du Dashboard')
  try {
    // Charger les workspaces
    await fetchWorkspaces()

    // Si un workspace est sélectionné, charger ses membres
    if (currentWorkspace.value) {
      selectedWorkspace.value = currentWorkspace.value.id
      await loadWorkspaceMembers()
    }

    // Charger les données du dashboard
    await loadDashboardData()

    // Écouter les changements de workspace
    unsubscribeWorkspace = onWorkspaceChanged(handleWorkspaceChange)

    console.log('✅ Dashboard initialisé')
  } catch (error) {
    console.error('❌ Erreur initialisation dashboard:', error)
  }
})
onBeforeUnmount(() => {
  if (unsubscribeWorkspace) {
    unsubscribeWorkspace()
  }
  if (monthlyChartInstance) {
    monthlyChartInstance.destroy()
  }
})
</script>
<style scoped>
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

.scrollbar-thin::-webkit-scrollbar {
  height: 6px;
}

.scrollbar-thin::-webkit-scrollbar-track {
  background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 3px;
}

.dark .scrollbar-thin::-webkit-scrollbar-thumb {
  background: #4b5563;
}
</style>