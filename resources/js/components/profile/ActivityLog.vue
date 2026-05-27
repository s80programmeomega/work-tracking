<!-- resources\js\components\profile\ActivityLog.vue -->
<template>
  <div class="activity-log">
    <div class="mb-6">
      <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">
        Journal d'activité
      </h4>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Historique de vos activités récentes
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="p-8 text-center">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-500 dark:text-gray-400">Chargement des activités...</p>
    </div>

    <!-- Activity Content -->
    <div v-else class="space-y-6">
      <!-- Filters -->
      <div class="flex flex-col gap-4 p-4 bg-gray-50 border border-gray-200 rounded-3 dark:bg-gray-800 dark:border-gray-700 sm:flex-row sm:items-center">
        <div class="flex items-center space-x-4">
          <button
            v-for="filter in timeFilters"
            :key="filter.value"
            @click="selectedTimeFilter = filter.value"
            :class="[
              'px-3 py-1.5 text-sm font-medium rounded-3 transition-colors',
              selectedTimeFilter === filter.value
                ? 'bg-blue-600 text-white'
                : 'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
            ]"
          >
            {{ filter.label }}
          </button>
        </div>
        
        <div class="flex items-center space-x-4">
          <select
            v-model="selectedType"
            class="bg-white border border-gray-300 text-gray-700 text-sm rounded-3 focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
          >
            <option value="all">Tous les types</option>
            <option value="task">Tâches</option>
            <option value="project">Projets</option>
            <option value="profile">Profil</option>
            <option value="system">Système</option>
          </select>
        </div>
        
        <div class="flex items-center ml-auto space-x-2">
          <span class="text-sm text-gray-600 dark:text-gray-400">
            {{ filteredActivities.length }} activités
          </span>
          <button
            @click="refreshActivities"
            class="p-2 text-gray-500 transition-colors rounded-3 hover:bg-gray-200 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300"
            title="Actualiser"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </button>
          <button
            @click="exportActivities"
            class="p-2 text-gray-500 transition-colors rounded-3 hover:bg-gray-200 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300"
            title="Exporter"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Activity Timeline -->
      <div class="relative">
        <!-- Timeline line -->
        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

        <!-- Activities -->
        <div class="space-y-6">
          <div
            v-for="(activity, index) in paginatedActivities"
            :key="activity.id"
            class="relative flex gap-4"
          >
            <!-- Timeline dot -->
            <div class="relative z-10 flex-shrink-0">
              <div
                :class="[
                  'w-8 h-8 rounded-full flex items-center justify-center',
                  getActivityTypeClass(activity.type)
                ]"
              >
                <component :is="getActivityIcon(activity.type)" class="w-4 h-4 text-white" />
              </div>
            </div>

            <!-- Activity content -->
            <div class="flex-1 pb-6">
              <div class="p-4 bg-white border border-gray-200 rounded-3 dark:bg-gray-800 dark:border-gray-700">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                  <div>
                    <h5 class="font-medium text-gray-800 dark:text-white/90">
                      {{ activity.title }}
                    </h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                      {{ activity.description }}
                    </p>
                  </div>
                  <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                      {{ formatTime(activity.timestamp) }}
                    </span>
                    <span
                      :class="[
                        'px-2 py-1 text-xs font-medium rounded-full',
                        getActivityTypeBadgeClass(activity.type)
                      ]"
                    >
                      {{ getActivityTypeLabel(activity.type) }}
                    </span>
                  </div>
                </div>
                
                <!-- Activity details -->
                <div v-if="activity.details" class="mt-3">
                  <div class="p-3 text-sm bg-gray-50 rounded-3 dark:bg-gray-700">
                    <pre class="whitespace-pre-wrap text-gray-600 dark:text-gray-300">{{ activity.details }}</pre>
                  </div>
                </div>
                
                <!-- Action buttons -->
                <div v-if="activity.actions" class="flex gap-2 mt-4">
                  <button
                    v-for="action in activity.actions"
                    :key="action.label"
                    @click="handleAction(action, activity)"
                    class="px-3 py-1.5 text-sm font-medium text-blue-600 transition-colors bg-blue-50 rounded-3 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50"
                  >
                    {{ action.label }}
                  </button>
                </div>
              </div>
              
              <!-- Separator (except for last item) -->
              <div v-if="index < paginatedActivities.length - 1" class="absolute left-4 -bottom-3 w-0.5 h-6 bg-gray-200 dark:bg-gray-700"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="filteredActivities.length === 0" class="p-8 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 mb-4 text-gray-400 bg-gray-100 rounded-full dark:bg-gray-800">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <h5 class="mb-2 text-lg font-medium text-gray-800 dark:text-white/90">
          Aucune activité récente
        </h5>
        <p class="text-gray-500 dark:text-gray-400">
          Vos activités apparaîtront ici au fur et à mesure que vous utiliserez l'application
        </p>
      </div>

      <!-- Pagination -->
      <div v-if="filteredActivities.length > itemsPerPage" class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-800">
        <div class="text-sm text-gray-500 dark:text-gray-400">
          Affichage {{ startIndex + 1 }}-{{ endIndex }} sur {{ filteredActivities.length }}
        </div>
        <div class="flex gap-2">
          <button
            @click="currentPage--"
            :disabled="currentPage === 1"
            class="px-3 py-1.5 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-3 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            Précédent
          </button>
          <div class="flex items-center space-x-1">
            <button
              v-for="page in totalPages"
              :key="page"
              @click="currentPage = page"
              :class="[
                'px-3 py-1.5 text-sm font-medium rounded-3',
                currentPage === page
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
              ]"
            >
              {{ page }}
            </button>
          </div>
          <button
            @click="currentPage++"
            :disabled="currentPage === totalPages"
            class="px-3 py-1.5 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-3 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            Suivant
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  CheckCircleIcon,
  DocumentTextIcon,
  UserIcon,
  CogIcon,
  CalendarIcon,
  ClockIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  user: {
    type: Object,
    default: () => ({})
  }
})

const loading = ref(true)
const activities = ref([])
const selectedTimeFilter = ref('today')
const selectedType = ref('all')
const currentPage = ref(1)
const itemsPerPage = ref(10)

const timeFilters = [
  { label: 'Aujourd\'hui', value: 'today' },
  { label: 'Cette semaine', value: 'week' },
  { label: 'Ce mois', value: 'month' },
  { label: 'Tout', value: 'all' }
]

// Activity types with icons and colors
const activityTypes = {
  task: {
    label: 'Tâche',
    icon: CheckCircleIcon,
    bgColor: 'bg-green-500',
    badgeColor: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
  },
  project: {
    label: 'Projet',
    icon: DocumentTextIcon,
    bgColor: 'bg-blue-500',
    badgeColor: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
  },
  profile: {
    label: 'Profil',
    icon: UserIcon,
    bgColor: 'bg-purple-500',
    badgeColor: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300'
  },
  system: {
    label: 'Système',
    icon: CogIcon,
    bgColor: 'bg-gray-500',
    badgeColor: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
  },
  deadline: {
    label: 'Délai',
    icon: CalendarIcon,
    bgColor: 'bg-orange-500',
    badgeColor: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300'
  },
  login: {
    label: 'Connexion',
    icon: ClockIcon,
    bgColor: 'bg-indigo-500',
    badgeColor: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300'
  },
  error: {
    label: 'Erreur',
    icon: ExclamationTriangleIcon,
    bgColor: 'bg-red-500',
    badgeColor: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
  }
}

const loadActivities = async () => {
  loading.value = true
  
  // Simulate API call
  await new Promise(resolve => setTimeout(resolve, 1000))
  
  // Generate mock data
  activities.value = generateMockActivities()
  loading.value = false
}

const generateMockActivities = () => {
  const mockActivities = []
  const types = Object.keys(activityTypes)
  
  for (let i = 0; i < 25; i++) {
    const type = types[Math.floor(Math.random() * types.length)]
    const hoursAgo = Math.floor(Math.random() * 168) // Last 7 days
    
    mockActivities.push({
      id: i + 1,
      type: type,
      title: getMockTitle(type),
      description: getMockDescription(type),
      timestamp: new Date(Date.now() - hoursAgo * 60 * 60 * 1000),
      details: Math.random() > 0.5 ? JSON.stringify({
        project: 'Projet Alpha',
        task: 'Tâche #123',
        user: props.user?.nom || 'Utilisateur'
      }, null, 2) : null,
      actions: Math.random() > 0.7 ? [
        { label: 'Voir', action: 'view' },
        { label: 'Modifier', action: 'edit' }
      ] : null
    })
  }
  
  // Sort by timestamp (newest first)
  return mockActivities.sort((a, b) => b.timestamp - a.timestamp)
}

const getMockTitle = (type) => {
  const titles = {
    task: ['Tâche complétée', 'Tâche assignée', 'Tâche mise à jour'],
    project: ['Projet créé', 'Projet modifié', 'Membre ajouté au projet'],
    profile: ['Profil mis à jour', 'Mot de passe changé', 'Paramètres modifiés'],
    system: ['Notification reçue', 'Rapport généré', 'Sauvegarde effectuée'],
    deadline: ['Délai approchant', 'Délai dépassé', 'Rappel de délai'],
    login: ['Connexion réussie', 'Tentative de connexion', 'Session expirée'],
    error: ['Erreur système', 'Échec de sauvegarde', 'Problème réseau']
  }
  
  const typeTitles = titles[type] || ['Activité']
  return typeTitles[Math.floor(Math.random() * typeTitles.length)]
}

const getMockDescription = (type) => {
  const descriptions = {
    task: 'Une tâche a été mise à jour dans votre liste',
    project: 'Changements apportés à un projet que vous suivez',
    profile: 'Vos informations personnelles ont été modifiées',
    system: 'Opération système effectuée avec succès',
    deadline: 'Un délai important approche',
    login: 'Activité de connexion détectée sur votre compte',
    error: 'Une erreur nécessite votre attention'
  }
  
  return descriptions[type] || 'Activité enregistrée'
}

const filteredActivities = computed(() => {
  let filtered = [...activities.value]
  
  // Filter by time
  const now = new Date()
  if (selectedTimeFilter.value === 'today') {
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
    filtered = filtered.filter(a => a.timestamp >= today)
  } else if (selectedTimeFilter.value === 'week') {
    const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000)
    filtered = filtered.filter(a => a.timestamp >= weekAgo)
  } else if (selectedTimeFilter.value === 'month') {
    const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000)
    filtered = filtered.filter(a => a.timestamp >= monthAgo)
  }
  
  // Filter by type
  if (selectedType.value !== 'all') {
    filtered = filtered.filter(a => a.type === selectedType.value)
  }
  
  return filtered
})

const paginatedActivities = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredActivities.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(filteredActivities.value.length / itemsPerPage.value)
})

const startIndex = computed(() => {
  return (currentPage.value - 1) * itemsPerPage.value
})

const endIndex = computed(() => {
  return Math.min(startIndex.value + itemsPerPage.value, filteredActivities.value.length)
})

const getActivityIcon = (type) => {
  return activityTypes[type]?.icon || CogIcon
}

const getActivityTypeClass = (type) => {
  return activityTypes[type]?.bgColor || 'bg-gray-500'
}

const getActivityTypeBadgeClass = (type) => {
  return activityTypes[type]?.badgeColor || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
}

const getActivityTypeLabel = (type) => {
  return activityTypes[type]?.label || 'Système'
}

const formatTime = (timestamp) => {
  const now = new Date()
  const diffMs = now - timestamp
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMins / 60)
  const diffDays = Math.floor(diffHours / 24)
  
  if (diffMins < 1) return 'À l\'instant'
  if (diffMins < 60) return `Il y a ${diffMins} min`
  if (diffHours < 24) return `Il y a ${diffHours} h`
  if (diffDays < 7) return `Il y a ${diffDays} j`
  
  return timestamp.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const handleAction = (action, activity) => {
  console.log('Action:', action.action, 'for activity:', activity.id)
  // Implement action handling
  alert(`Action "${action.label}" pour l'activité ${activity.title}`)
}

const refreshActivities = async () => {
  await loadActivities()
}

const exportActivities = () => {
  const dataStr = JSON.stringify(filteredActivities.value, null, 2)
  const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr)
  
  const exportFileDefaultName = `activites-${new Date().toISOString().split('T')[0]}.json`
  
  const linkElement = document.createElement('a')
  linkElement.setAttribute('href', dataUri)
  linkElement.setAttribute('download', exportFileDefaultName)
  linkElement.click()
}

onMounted(() => {
  loadActivities()
})
</script>