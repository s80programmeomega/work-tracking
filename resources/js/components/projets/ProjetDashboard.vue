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
      <button @click="refreshData" :disabled="loading"
        class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
        <RefreshIcon :class="{ 'animate-spin': loading }" class="w-5 h-5" />
        Actualiser
      </button>
    </div>

    <!-- Error Alert -->
    <div v-if="errors.stats"
      class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
      <div class="flex items-center gap-3">
        <AlertCircleIcon class="w-5 h-5 text-red-600 dark:text-red-400" />
        <div>
          <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
            Erreur de chargement
          </h3>
          <p class="text-sm text-red-700 dark:text-red-300 mt-1">
            {{ errors.stats }}
          </p>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading && !stats.total_projets" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-600"></div>
    </div>

    <template v-else>
      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Projects -->
        <div
          class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                Total Projets
              </p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                {{ stats.total_projets }}
              </p>
            </div>
            <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
              <FolderIcon class="w-8 h-8 text-blue-600 dark:text-blue-400" />
            </div>
          </div>
          <div class="mt-4 flex items-center justify-between">
            <span class="text-sm text-green-600 dark:text-green-400 font-medium">
              {{ stats.projets_actifs }} actifs
            </span>
            <span class="text-xs text-gray-500 dark:text-gray-400">
              {{ stats.projets_termines }} terminés
            </span>
          </div>
        </div>

        <!-- Activities -->
        <div
          class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                Total Activités
              </p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                {{ stats.total_activites }}
              </p>
            </div>
            <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
              <ListIcon class="w-8 h-8 text-green-600 dark:text-green-400" />
            </div>
          </div>
          <div class="mt-4">
            <span class="text-sm text-gray-600 dark:text-gray-400">
              Réparties sur {{ stats.total_projets }} projet(s)
            </span>
          </div>
        </div>

        <!-- Tasks -->
        <div
          class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                Total Tâches
              </p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                {{ stats.total_taches }}
              </p>
            </div>
            <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
              <CheckCircleIcon class="w-8 h-8 text-purple-600 dark:text-purple-400" />
            </div>
          </div>
          <div class="mt-4 flex items-center justify-between">
            <span class="text-sm text-green-600 dark:text-green-400 font-medium">
              {{ stats.taux_completion }}% complétées
            </span>
            <span class="text-xs text-gray-500 dark:text-gray-400">
              {{ stats.taches_terminees }}/{{ stats.total_taches }}
            </span>
          </div>
        </div>

        <!-- Overdue -->
        <div
          class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                En Retard
              </p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                {{ stats.projets_en_retard }}
              </p>
            </div>
            <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
              <AlertCircleIcon class="w-8 h-8 text-red-600 dark:text-red-400" />
            </div>
          </div>
          <div class="mt-4">
            <span class="text-sm font-medium"
              :class="stats.projets_en_retard > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
              {{ stats.projets_en_retard > 0 ? 'Nécessite attention' : 'Aucun retard' }}
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
          <div v-if="stats.total_projets === 0" class="text-center py-8">
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Aucun projet pour le moment
            </p>
          </div>
          <div v-else class="space-y-3">
            <div v-for="status in statusData" :key="status.label" class="flex items-center justify-between">
              <div class="flex items-center gap-3 flex-1">
                <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: status.color }"></div>
                <span class="text-sm text-gray-700 dark:text-gray-300">
                  {{ status.label }}
                </span>
              </div>
              <div class="flex items-center gap-3">
                <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                  <div class="h-2 rounded-full transition-all" :style="{
                    width: `${status.percentage}%`,
                    backgroundColor: status.color
                  }"></div>
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
          <div v-if="!recentActivities || recentActivities.length === 0" class="text-center py-8">
            <ClockIcon class="w-12 h-12 text-gray-400 mx-auto mb-2" />
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Aucune activité récente
            </p>
          </div>
          <div v-else class="space-y-4">
            <div v-for="activity in recentActivities.slice(0, 5)" :key="activity.id"
              class="flex items-start gap-3 pb-3 border-b border-gray-200 dark:border-gray-700 last:border-0">
              <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                :class="getActivityStyle(activity.description).bgColor">
                <component :is="getActivityStyle(activity.description).icon"
                  :class="getActivityStyle(activity.description).iconColor" class="w-4 h-4" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-900 dark:text-white font-medium">
                  {{ formatActivityTitle(activity.description) }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  {{ activity.projet_nom || 'Projet' }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                  {{ formatDate(activity.created_at) }}
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
          <button @click="$emit('view-all')"
            class="text-sm text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300 font-medium">
            Voir tout
          </button>
        </div>

        <div class="p-6">
          <div v-if="recentProjects.length === 0" class="text-center py-8">
            <FolderOpenIcon class="mx-auto h-12 w-12 text-gray-400" />
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
              Aucun projet récent
            </p>
            
            <button @click="openCreateModal"
              class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors">
              <PlusIcon class="w-5 h-5" />
              Nouveau projet
            </button>
          </div>

          <div v-else class="space-y-3">
            <div v-for="projet in recentProjects" :key="projet.id" @click="$emit('view-projet', projet.id)"
              class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors group">
              <div class="flex items-center gap-4 flex-1 min-w-0">
                <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0"
                  :style="{ backgroundColor: (projet.couleur || '#3B82F6') + '20' }">
                  <FolderIcon class="w-6 h-6" :style="{ color: projet.couleur || '#3B82F6' }" />
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-1">
                    <h4
                      class="text-sm font-semibold text-gray-900 dark:text-white truncate group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors">
                      {{ projet.nom }}
                    </h4>
                    <StarIcon v-if="projet.is_favorite" class="w-4 h-4 fill-yellow-400 text-yellow-400 flex-shrink-0" />
                  </div>
                  <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                    {{ projet.code }} • {{ projet.responsable?.nom || 'Non assigné' }}
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
                    <div class="bg-brand-600 h-1.5 rounded-full transition-all"
                      :style="{ width: `${projet.progression || 0}%` }"></div>
                  </div>
                </div>

                <!-- Stats -->
                <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                  <div class="flex items-center gap-1" :title="`${projet.activites_count || 0} activité(s)`">
                    <ListIcon class="w-4 h-4" />
                    <span>{{ projet.activites_count || 0 }}</span>
                  </div>
                  <div class="flex items-center gap-1" :title="`${projet.taches_count || 0} tâche(s)`">
                    <CheckCircleIcon class="w-4 h-4" />
                    <span>{{ projet.taches_count || 0 }}</span>
                  </div>
                  <div class="flex items-center gap-1" :title="`${projet.members_count || 0} membre(s)`">
                    <UsersIcon class="w-4 h-4" />
                    <span>{{ projet.members_count || 0 }}</span>
                  </div>
                </div>

                <!-- Status Badge -->
                <span :class="[
                  'px-3 py-1 rounded-full text-xs font-medium',
                  getStatusColor(projet.status)
                ]">
                  {{ getStatusLabel(projet.status) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <button @click="$emit('view-all')"
          class="flex items-center gap-4 p-6 bg-gradient-to-br from-brand-500 to-brand-600 rounded-lg text-white hover:from-brand-600 hover:to-brand-700 transition-all group">
          <div class="p-3 bg-white/20 rounded-lg">
            <FolderIcon class="w-6 h-6" />
          </div>
          <div class="text-left">
            <div class="text-lg font-semibold">Tous les projets</div>
            <div class="text-sm opacity-90">{{ stats.total_projets }} projet(s)</div>
          </div>
          <ChevronRightIcon class="w-5 h-5 ml-auto group-hover:translate-x-1 transition-transform" />
        </button>

        <button @click="$emit('view-favorites')"
          class="flex items-center gap-4 p-6 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg text-white hover:from-purple-600 hover:to-purple-700 transition-all group">
          <div class="p-3 bg-white/20 rounded-lg">
            <StarIcon class="w-6 h-6" />
          </div>
          <div class="text-left">
            <div class="text-lg font-semibold">Mes favoris</div>
            <div class="text-sm opacity-90">{{ stats.projets_favoris }} projet(s)</div>
          </div>
          <ChevronRightIcon class="w-5 h-5 ml-auto group-hover:translate-x-1 transition-transform" />
        </button>

        <button @click="$emit('view-overdue')"
          class="flex items-center gap-4 p-6 bg-gradient-to-br from-red-500 to-red-600 rounded-lg text-white hover:from-red-600 hover:to-red-700 transition-all group">
          <div class="p-3 bg-white/20 rounded-lg">
            <AlertCircleIcon class="w-6 h-6" />
          </div>
          <div class="text-left">
            <div class="text-lg font-semibold">En retard</div>
            <div class="text-sm opacity-90">{{ stats.projets_en_retard }} projet(s)</div>
          </div>
          <ChevronRightIcon class="w-5 h-5 ml-auto group-hover:translate-x-1 transition-transform" />
        </button>
      </div>
    </template>

     <!-- Modal Create/Edit Projet -->
    <ProjetFormModal
      v-if="showFormModal"
      :projet="selectedProjet"
      @close="closeFormModal"
      @saved="handleProjetSaved"
    />

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
  ArchiveIcon,
  ClockIcon,
  RefreshIcon
} from '@/icons'
import ProjetFormModal from './ProjetFormModal.vue'

const emit = defineEmits(['view-all', 'view-projet', 'create-projet', 'view-favorites', 'view-overdue'])

// state
const selectedProjet = ref(null)
const showFormModal = ref(false)

const { loading, stats, projets, errors, fetchDashboardStats, fetchProjets } = useProjets()

const recentProjects = computed(() => {
  return projets.value.slice(0, 5)
})

const recentActivities = computed(() => {
  return stats.value?.recent_activities || []
})

const statusData = computed(() => {
  const total = stats.value?.total_projets || 0
  if (total === 0) return []

  return [
    {
      label: 'Actifs',
      count: stats.value?.projets_actifs || 0,
      percentage: ((stats.value?.projets_actifs || 0) / total) * 100,
      color: '#10B981'
    },
    {
      label: 'Terminés',
      count: stats.value?.projets_termines || 0,
      percentage: ((stats.value?.projets_termines || 0) / total) * 100,
      color: '#3B82F6'
    },
    {
      label: 'Archivés',
      count: stats.value?.projets_archives || 0,
      percentage: ((stats.value?.projets_archives || 0) / total) * 100,
      color: '#6B7280'
    },
    {
      label: 'En retard',
      count: stats.value?.projets_en_retard || 0,
      percentage: ((stats.value?.projets_en_retard || 0) / total) * 100,
      color: '#EF4444'
    }
  ].filter(item => item.count > 0)
})

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

const getActivityStyle = (description) => {
    if (!description) description = '' // <- s'assure que c'est une string

  if (description.includes('created') || description.includes('créé')) {
    return {
      icon: PlusIcon,
      bgColor: 'bg-blue-100 dark:bg-blue-900/30',
      iconColor: 'text-blue-600 dark:text-blue-400'
    }
  } else if (description.includes('completed') || description.includes('terminé')) {
    return {
      icon: CheckCircleIcon,
      bgColor: 'bg-green-100 dark:bg-green-900/30',
      iconColor: 'text-green-600 dark:text-green-400'
    }
  } else if (description.includes('archived') || description.includes('archivé')) {
    return {
      icon: ArchiveIcon,
      bgColor: 'bg-gray-100 dark:bg-gray-700',
      iconColor: 'text-gray-600 dark:text-gray-400'
    }
  } else {
    return {
      icon: ClockIcon,
      bgColor: 'bg-purple-100 dark:bg-purple-900/30',
      iconColor: 'text-purple-600 dark:text-purple-400'
    }
  }
}

const formatActivityTitle = (description) => {
  const titles = {
    'created': 'Projet créé',
    'updated': 'Projet mis à jour',
    'completed': 'Projet terminé',
    'archived': 'Projet archivé',
    'member_added': 'Membre ajouté',
    'member_removed': 'Membre retiré'
  }
  return titles[description] || description
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'

  const date = new Date(dateString)
  const now = new Date()
  const diff = now - date
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)

  if (minutes < 1) return 'À l\'instant'
  if (minutes < 60) return `Il y a ${minutes} minute${minutes > 1 ? 's' : ''}`
  if (hours < 24) return `Il y a ${hours} heure${hours > 1 ? 's' : ''}`
  if (days < 7) return `Il y a ${days} jour${days > 1 ? 's' : ''}`

  return date.toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Methods
const openCreateModal = () => {
  selectedProjet.value = null
  showFormModal.value = true
}

const closeFormModal = () => {
  showFormModal.value = false
  selectedProjet.value = null
}
const refreshData = async () => {
  await Promise.all([
    fetchDashboardStats(),
    fetchProjets({ per_page: 5 })
  ])
}

onMounted(async () => {
  await refreshData()
})
</script>