<!-- resources/js/components/projets/ProjetDashboard.vue -->
<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
          Tableau de bord
        </h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Vue d'ensemble de vos projets
        </p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-600"></div>
    </div>

    <template v-else>
      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Projects -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                Total Projets
              </p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                {{ stats.total_projets || 0 }}
              </p>
            </div>
            <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
              <FolderIcon class="w-8 h-8 text-blue-600 dark:text-blue-400" />
            </div>
          </div>
          <div class="mt-4">
            <span class="text-sm text-green-600 dark:text-green-400 font-medium">
              {{ stats.projets_actifs || 0 }} actifs
            </span>
          </div>
        </div>

        <!-- Activities -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                Total Activités
              </p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                {{ stats.total_activites || 0 }}
              </p>
            </div>
            <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
              <ListIcon class="w-8 h-8 text-green-600 dark:text-green-400" />
            </div>
          </div>
          <div class="mt-4">
            <span class="text-sm text-gray-600 dark:text-gray-400">
              Réparties sur {{ stats.total_projets || 0 }} projets
            </span>
          </div>
        </div>

        <!-- Tasks -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                Total Tâches
              </p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                {{ stats.total_taches || 0 }}
              </p>
            </div>
            <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
              <CheckCircleIcon class="w-8 h-8 text-purple-600 dark:text-purple-400" />
            </div>
          </div>
          <div class="mt-4">
            <span class="text-sm text-green-600 dark:text-green-400 font-medium">
              {{ stats.taux_completion || 0 }}% complétées
            </span>
          </div>
        </div>

        <!-- Overdue -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                En Retard
              </p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                {{ stats.projets_en_retard || 0 }}
              </p>
            </div>
            <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
              <AlertCircleIcon class="w-8 h-8 text-red-600 dark:text-red-400" />
            </div>
          </div>
          <div class="mt-4">
            <span class="text-sm text-red-600 dark:text-red-400 font-medium">
              Nécessite attention
            </span>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Projects by Status -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Projets par statut
          </h3>
          <div class="space-y-3">
            <div
              v-for="status in statusData"
              :key="status.label"
              class="flex items-center justify-between"
            >
              <div class="flex items-center gap-3 flex-1">
                <div
                  class="w-3 h-3 rounded-full"
                  :style="{ backgroundColor: status.color }"
                ></div>
                <span class="text-sm text-gray-700 dark:text-gray-300">
                  {{ status.label }}
                </span>
              </div>
              <div class="flex items-center gap-3">
                <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                  <div
                    class="h-2 rounded-full transition-all"
                    :style="{
                      width: `${status.percentage}%`,
                      backgroundColor: status.color
                    }"
                  ></div>
                </div>
                <span class="text-sm font-semibold text-gray-900 dark:text-white w-12 text-right">
                  {{ status.count }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Activité récente
          </h3>
          <div class="space-y-4">
            <div
              v-for="activity in recentActivities"
              :key="activity.id"
              class="flex items-start gap-3 pb-3 border-b border-gray-200 dark:border-gray-700 last:border-0"
            >
              <div
                class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                :class="activity.bgColor"
              >
                <component :is="activity.icon" :class="activity.iconColor" class="w-4 h-4" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-900 dark:text-white font-medium">
                  {{ activity.title }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  {{ activity.description }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                  {{ activity.time }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Projects -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Projets récents
          </h3>
          <button
            @click="$emit('view-all')"
            class="text-sm text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300 font-medium"
          >
            Voir tout
          </button>
        </div>
        
        <div class="p-6">
          <div v-if="recentProjects.length === 0" class="text-center py-8">
            <FolderOpenIcon class="mx-auto h-12 w-12 text-gray-400" />
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
              Aucun projet récent
            </p>
          </div>
          
          <div v-else class="space-y-3">
            <div
              v-for="projet in recentProjects"
              :key="projet.id"
              @click="$emit('view-projet', projet.id)"
              class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors group"
            >
              <div class="flex items-center gap-4 flex-1 min-w-0">
                <div
                  class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0"
                  :style="{ backgroundColor: projet.couleur + '20' }"
                >
                  <FolderIcon
                    class="w-6 h-6"
                    :style="{ color: projet.couleur }"
                  />
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-1">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors">
                      {{ projet.nom }}
                    </h4>
                    <StarIcon
                      v-if="projet.is_favorite"
                      class="w-4 h-4 fill-yellow-400 text-yellow-400 flex-shrink-0"
                    />
                  </div>
                  <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                    {{ projet.code }} • {{ projet.responsable?.nom }}
                  </p>
                </div>
              </div>
              
              <div class="flex items-center gap-6 ml-4">
                <!-- Progress -->
                <div class="text-right">
                  <div class="text-xs font-semibold text-gray-900 dark:text-white mb-1">
                    {{ projet.progression || 0 }}%
                  </div>
                  <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                    <div
                      class="bg-brand-600 h-1.5 rounded-full transition-all"
                      :style="{ width: `${projet.progression || 0}%` }"
                    ></div>
                  </div>
                </div>
                
                <!-- Stats -->
                <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                  <div class="flex items-center gap-1">
                    <ListIcon class="w-4 h-4" />
                    <span>{{ projet.activites_count || 0 }}</span>
                  </div>
                  <div class="flex items-center gap-1">
                    <CheckCircleIcon class="w-4 h-4" />
                    <span>{{ projet.taches_count || 0 }}</span>
                  </div>
                  <div class="flex items-center gap-1">
                    <UsersIcon class="w-4 h-4" />
                    <span>{{ projet.member_count || 0 }}</span>
                  </div>
                </div>
                
                <!-- Status Badge -->
                <span
                  :class="[
                    'px-3 py-1 rounded-full text-xs font-medium',
                    getStatusColor(projet.status)
                  ]"
                >
                  {{ getStatusLabel(projet.status) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <button
          @click="$emit('view-all')"
          class="flex items-center gap-4 p-6 bg-gradient-to-br from-brand-500 to-brand-600 rounded-lg text-white hover:from-brand-600 hover:to-brand-700 transition-all group"
        >
          <div class="p-3 bg-white/20 rounded-lg">
            <FolderIcon class="w-6 h-6" />
          </div>
          <div class="text-left">
            <div class="text-lg font-semibold">Tous les projets</div>
            <div class="text-sm opacity-90">Voir la liste complète</div>
          </div>
          <ChevronRightIcon class="w-5 h-5 ml-auto group-hover:translate-x-1 transition-transform" />
        </button>

        <button
          class="flex items-center gap-4 p-6 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg text-white hover:from-purple-600 hover:to-purple-700 transition-all group"
        >
          <div class="p-3 bg-white/20 rounded-lg">
            <StarIcon class="w-6 h-6" />
          </div>
          <div class="text-left">
            <div class="text-lg font-semibold">Mes favoris</div>
            <div class="text-sm opacity-90">{{ stats.projets_favoris || 0 }} projets</div>
          </div>
          <ChevronRightIcon class="w-5 h-5 ml-auto group-hover:translate-x-1 transition-transform" />
        </button>

        <button
          class="flex items-center gap-4 p-6 bg-gradient-to-br from-red-500 to-red-600 rounded-lg text-white hover:from-red-600 hover:to-red-700 transition-all group"
        >
          <div class="p-3 bg-white/20 rounded-lg">
            <AlertCircleIcon class="w-6 h-6" />
          </div>
          <div class="text-left">
            <div class="text-lg font-semibold">En retard</div>
            <div class="text-sm opacity-90">{{ stats.projets_en_retard || 0 }} projets</div>
          </div>
          <ChevronRightIcon class="w-5 h-5 ml-auto group-hover:translate-x-1 transition-transform" />
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useProjets } from '@/composables/useProjets'
import {
  FolderIcon,
  ListIcon,
  CheckCircleIcon,
  AlertCircleIcon,
  StarIcon,
  UsersIcon,
  ChevronRightIcon,
  FolderOpenIcon,
  PlusIcon,
  TrendingUpIcon,
  ArchiveIcon
} from '@/icons'

const emit = defineEmits(['view-all', 'view-projet'])

const { loading, stats, projets, fetchDashboardStats, fetchProjets } = useProjets()

const recentProjects = computed(() => {
  return projets.value.slice(0, 5)
})

const statusData = computed(() => {
  const total = stats.value.total_projets || 0
  if (total === 0) return []
  
  return [
    {
      label: 'Actifs',
      count: stats.value.projets_actifs || 0,
      percentage: ((stats.value.projets_actifs || 0) / total) * 100,
      color: '#10B981'
    },
    {
      label: 'Terminés',
      count: stats.value.projets_termines || 0,
      percentage: ((stats.value.projets_termines || 0) / total) * 100,
      color: '#3B82F6'
    },
    {
      label: 'Archivés',
      count: stats.value.projets_archives || 0,
      percentage: ((stats.value.projets_archives || 0) / total) * 100,
      color: '#6B7280'
    },
    {
      label: 'En retard',
      count: stats.value.projets_en_retard || 0,
      percentage: ((stats.value.projets_en_retard || 0) / total) * 100,
      color: '#EF4444'
    }
  ]
})

const recentActivities = ref([
  {
    id: 1,
    title: 'Nouveau projet créé',
    description: 'Système de Gestion RH',
    time: 'Il y a 2 heures',
    icon: PlusIcon,
    bgColor: 'bg-blue-100 dark:bg-blue-900/30',
    iconColor: 'text-blue-600 dark:text-blue-400'
  },
  {
    id: 2,
    title: 'Projet terminé',
    description: 'Refonte Site Web Corporate',
    time: 'Il y a 5 heures',
    icon: CheckCircleIcon,
    bgColor: 'bg-green-100 dark:bg-green-900/30',
    iconColor: 'text-green-600 dark:text-green-400'
  },
  {
    id: 3,
    title: 'Projet archivé',
    description: 'Application Mobile Legacy',
    time: 'Hier',
    icon: ArchiveIcon,
    bgColor: 'bg-gray-100 dark:bg-gray-700',
    iconColor: 'text-gray-600 dark:text-gray-400'
  }
])

const getStatusColor = (status) => {
  const colors = {
    active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    completed: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    archived: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
  }
  return colors[status] || colors.pending
}

const getStatusLabel = (status) => {
  const labels = {
    active: 'Actif',
    completed: 'Terminé',
    archived: 'Archivé',
    pending: 'En attente'
  }
  return labels[status] || status
}

onMounted(async () => {
  await Promise.all([
    fetchDashboardStats(),
    fetchProjets({ per_page: 5 })
  ])
})
</script>