<template>
  <div class="space-y-4">
    <!-- Filters -->
    <div class="flex flex-col gap-4 sm:flex-row">
      <!-- Search Bar -->
      <div class="relative flex-1">
        <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Rechercher une tâche..."
          class="w-full rounded-3 border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
        />
      </div>

      <!-- Activity Filter -->
      <select
        v-model="selectedActivityId"
        class="rounded-3 border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
      >
        <option value="">Toutes les activités</option>
        <option
          v-for="activity in activities"
          :key="activity.id"
          :value="activity.id"
        >
          {{ activity.nom }}
        </option>
      </select>

      <!-- Status Filter -->
      <select
        v-model="statusFilter"
        class="rounded-3 border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
      >
        <option value="">Tous les statuts</option>
        <option value="a_faire">À faire</option>
        <option value="en_cours">En cours</option>
        <option value="termine">Terminé</option>
      </select>

      <!-- Priority Filter -->
      <select
        v-model="priorityFilter"
        class="rounded-3 border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
      >
        <option value="">Toutes priorités</option>
        <option value="faible">Faible</option>
        <option value="moyenne">Moyenne</option>
        <option value="elevee">Élevée</option>
        <option value="critique">Critique</option>
      </select>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 10" :key="i" class="h-28 animate-pulse rounded-3 bg-gray-200 dark:bg-gray-800"></div>
    </div>

    <!-- Tasks List -->
    <div v-else-if="filteredTasks.length > 0" class="space-y-3">
      <div
        v-for="task in filteredTasks"
        :key="task.id"
        @click="$emit('select', task)"
        class="group cursor-pointer overflow-hidden rounded-3 border border-gray-200 bg-white transition-all hover:border-blue-500 dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-blue-400"
      >
        <div class="flex items-start gap-4 p-4">
          <!-- Priority Indicator -->
          <div class="flex flex-col items-center gap-2">
            <div
              :class="[
                'flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-3',
                getPriorityBgClass(task.priorite)
              ]"
            >
              <component
                :is="getPriorityIcon(task.priorite)"
                :class="['h-5 w-5', getPriorityTextClass(task.priorite)]"
              />
            </div>
            <span
              :class="[
                'text-xs font-medium',
                getPriorityTextClass(task.priorite)
              ]"
            >
              {{ getPriorityLabel(task.priorite) }}
            </span>
          </div>

          <!-- Task Info -->
          <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                  <h3 class="truncate text-base font-semibold text-gray-900 dark:text-white">
                    {{ task.titre }}
                  </h3>
                  <span
                    v-if="task.code"
                    class="inline-flex items-center rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400"
                  >
                    {{ task.code }}
                  </span>
                </div>

                <!-- Activity Badge -->
                <div class="mt-2 flex items-center gap-2">
                  <RectangleStackIcon class="h-4 w-4 text-gray-400" />
                  <span class="truncate text-xs text-gray-600 dark:text-gray-400">
                    {{ task.activite?.nom || 'Sans activité' }}
                  </span>
                </div>
              </div>

              <!-- Status Badge -->
              <span
                :class="[
                  'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap',
                  getStatusBadgeClass(task.statut)
                ]"
              >
                {{ getStatusLabel(task.statut) }}
              </span>
            </div>

            <!-- Description -->
            <p
              v-if="task.description"
              class="mt-2 line-clamp-1 text-sm text-gray-600 dark:text-gray-400"
            >
              {{ task.description }}
            </p>

            <!-- Stats Bar -->
            <div class="mt-3 flex flex-wrap items-center gap-4">
              <!-- Documents -->
              <div class="flex items-center gap-1.5">
                <DocumentIcon class="h-4 w-4 text-gray-400" />
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ task.documents_count || 0 }}
                </span>
              </div>

              <!-- Assignees -->
              <div class="flex items-center gap-1.5">
                <UserGroupIcon class="h-4 w-4 text-gray-400" />
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ task.assignees_count || 0 }}
                </span>
              </div>

              <!-- Deadline -->
              <div v-if="task.echeance" class="flex items-center gap-1.5">
                <CalendarIcon class="h-4 w-4 text-gray-400" />
                <span class="text-xs text-gray-600 dark:text-gray-400">
                  {{ formatDate(task.echeance) }}
                </span>
              </div>

              <!-- Progress -->
              <div class="ml-auto flex items-center gap-2">
                <div class="h-2 w-24 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                  <div
                    :class="[
                      'h-full transition-all',
                      task.taux_realisation === 100
                        ? 'bg-success-500'
                        : 'bg-brand-500'
                    ]"
                    :style="{ width: (task.taux_realisation || 0) + '%' }"
                  ></div>
                </div>
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ task.taux_realisation || 0 }}%
                </span>
              </div>
            </div>
          </div>

          <!-- Arrow -->
          <ChevronRightIcon class="h-5 w-5 text-gray-400 transition-transform group-hover:translate-x-1 group-hover:text-blue-500" />
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="rounded-3 border-2 border-dashed border-gray-300 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-800/50">
      <CheckCircleIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
        Aucune tâche trouvée
      </h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ searchQuery ? 'Essayez une autre recherche' : 'Vous n\'avez accès à aucune tâche' }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  CheckCircleIcon,
  MagnifyingGlassIcon,
  RectangleStackIcon,
  DocumentIcon,
  UserGroupIcon,
  CalendarIcon,
  ChevronRightIcon,
  ExclamationTriangleIcon,
  FlagIcon,
  ArrowUpIcon
} from '@heroicons/vue/24/outline'
import api from '@/api/axios'

defineEmits(['select'])

const tasks = ref([])
const activities = ref([])
const loading = ref(false)
const searchQuery = ref('')
const selectedActivityId = ref('')
const statusFilter = ref('')
const priorityFilter = ref('')

const filteredTasks = computed(() => {
  let filtered = tasks.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(t =>
      t.titre.toLowerCase().includes(query) ||
      t.code?.toLowerCase().includes(query) ||
      t.description?.toLowerCase().includes(query)
    )
  }

  if (selectedActivityId.value) {
    filtered = filtered.filter(t => t.activite_id === parseInt(selectedActivityId.value))
  }

  if (statusFilter.value) {
    filtered = filtered.filter(t => t.statut === statusFilter.value)
  }

  if (priorityFilter.value) {
    filtered = filtered.filter(t => t.priorite === priorityFilter.value)
  }

  return filtered
})

const getStatusLabel = (status) => {
  const labels = {
    a_faire: 'À faire',
    en_cours: 'En cours',
    termine: 'Terminé'
  }
  return labels[status] || status
}

const getStatusBadgeClass = (status) => {
  const classes = {
    a_faire: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
    en_cours: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    termine: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
  }
  return classes[status] || classes.a_faire
}

const getPriorityLabel = (priority) => {
  const labels = {
    faible: 'Faible',
    moyenne: 'Moyenne',
    elevee: 'Élevée',
    critique: 'Critique'
  }
  return labels[priority] || priority
}

const getPriorityIcon = (priority) => {
  const icons = {
    faible: FlagIcon,
    moyenne: ArrowUpIcon,
    elevee: ExclamationTriangleIcon,
    critique: ExclamationTriangleIcon
  }
  return icons[priority] || FlagIcon
}

const getPriorityBgClass = (priority) => {
  const classes = {
    faible: 'bg-gray-100 dark:bg-gray-800',
    moyenne: 'bg-blue-100 dark:bg-blue-900/30',
    elevee: 'bg-orange-100 dark:bg-orange-900/30',
    critique: 'bg-red-100 dark:bg-red-900/30'
  }
  return classes[priority] || classes.moyenne
}

const getPriorityTextClass = (priority) => {
  const classes = {
    faible: 'text-gray-600 dark:text-gray-400',
    moyenne: 'text-blue-600 dark:text-blue-400',
    elevee: 'text-orange-600 dark:text-orange-400',
    critique: 'text-red-600 dark:text-red-400'
  }
  return classes[priority] || classes.moyenne
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const loadTasks = async () => {
  loading.value = true
  try {
    const response = await api.get('/taches', {
      params: {
        with_counts: true
      }
    })
    tasks.value = response.data.data
  } catch (error) {
    console.error('Error loading tasks:', error)
  } finally {
    loading.value = false
  }
}

const loadActivities = async () => {
  try {
    const response = await api.get('activites/mes-activites')
    activities.value = response.data.data
  } catch (error) {
    console.error('Error loading activities:', error)
  }
}

onMounted(() => {
  loadTasks()
  loadActivities()
})
</script>