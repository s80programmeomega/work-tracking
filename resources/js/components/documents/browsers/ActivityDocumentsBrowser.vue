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
          placeholder="Rechercher une activité..."
          class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
        />
      </div>

      <!-- Project Filter -->
      <select
        v-model="selectedProjectId"
        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
      >
        <option value="">Tous les projets</option>
        <option
          v-for="project in projects"
          :key="project.id"
          :value="project.id"
        >
          {{ project.nom }}
        </option>
      </select>

      <!-- Status Filter -->
      <select
        v-model="statusFilter"
        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
      >
        <option value="">Tous les statuts</option>
        <option value="active">Actives</option>
        <option value="archived">Archivées</option>
      </select>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 8" :key="i" class="h-24 animate-pulse rounded-lg bg-gray-200 dark:bg-gray-800"></div>
    </div>

    <!-- Activities List -->
    <div v-else-if="filteredActivities.length > 0" class="space-y-3">
      <div
        v-for="activity in filteredActivities"
        :key="activity.id"
        @click="$emit('select', activity)"
        class="group cursor-pointer overflow-hidden rounded-lg border border-gray-200 bg-white transition-all hover:border-purple-500 hover:shadow-md dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-purple-400"
      >
        <div class="flex items-center gap-4 p-4">
          <!-- Activity Icon with Color -->
          <div
            class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-lg"
            :style="{ backgroundColor: activity.couleur || '#8B5CF6' + '20' }"
          >
            <RectangleStackIcon
              class="h-7 w-7"
              :style="{ color: activity.couleur || '#8B5CF6' }"
            />
          </div>

          <!-- Activity Info -->
          <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0 flex-1">
                <h3 class="truncate text-base font-semibold text-gray-900 dark:text-white">
                  {{ activity.nom }}
                </h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  {{ activity.code }}
                </p>
              </div>

              <!-- Status Badge -->
              <span
                :class="[
                  'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                  activity.status === 'active'
                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                    : 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
                ]"
              >
                {{ activity.status === 'active' ? 'Active' : 'Archivée' }}
              </span>
            </div>

            <!-- Project Badge -->
            <div class="mt-2 flex items-center gap-2">
              <BriefcaseIcon class="h-4 w-4 text-gray-400" />
              <span class="truncate text-xs text-gray-600 dark:text-gray-400">
                {{ activity.projet?.nom || 'Sans projet' }}
              </span>
            </div>

            <!-- Stats Bar -->
            <div class="mt-3 flex items-center gap-6">
              <div class="flex items-center gap-1.5">
                <DocumentIcon class="h-4 w-4 text-gray-400" />
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ activity.documents_count || 0 }}
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">docs</span>
              </div>

              <div class="flex items-center gap-1.5">
                <CheckCircleIcon class="h-4 w-4 text-gray-400" />
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ activity.tasks_count || 0 }}
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">tâches</span>
              </div>

              <div class="flex items-center gap-1.5">
                <UserGroupIcon class="h-4 w-4 text-gray-400" />
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ activity.members_count || 0 }}
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">membres</span>
              </div>

              <!-- Progress -->
              <div class="ml-auto flex items-center gap-2">
                <div class="h-2 w-24 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                  <div
                    class="h-full bg-gradient-to-r from-purple-500 to-purple-600 transition-all"
                    :style="{ width: (activity.progression || 0) + '%' }"
                  ></div>
                </div>
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ activity.progression || 0 }}%
                </span>
              </div>
            </div>
          </div>

          <!-- Arrow -->
          <ChevronRightIcon class="h-5 w-5 text-gray-400 transition-transform group-hover:translate-x-1 group-hover:text-purple-500" />
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-800/50">
      <RectangleStackIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
        Aucune activité trouvée
      </h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ searchQuery ? 'Essayez une autre recherche' : 'Vous n\'avez accès à aucune activité' }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  RectangleStackIcon,
  MagnifyingGlassIcon,
  BriefcaseIcon,
  DocumentIcon,
  CheckCircleIcon,
  UserGroupIcon,
  ChevronRightIcon
} from '@heroicons/vue/24/outline'
import api from '@/api/axios'

defineEmits(['select'])

const activities = ref([])
const projects = ref([])
const loading = ref(false)
const searchQuery = ref('')
const selectedProjectId = ref('')
const statusFilter = ref('')

const filteredActivities = computed(() => {
  let filtered = activities.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(a =>
      a.nom.toLowerCase().includes(query) ||
      a.code.toLowerCase().includes(query) ||
      a.description?.toLowerCase().includes(query)
    )
  }

  if (selectedProjectId.value) {
    filtered = filtered.filter(a => a.projet_id === parseInt(selectedProjectId.value))
  }

  if (statusFilter.value) {
    filtered = filtered.filter(a => a.status === statusFilter.value)
  }

  return filtered
})

const loadActivities = async () => {
  loading.value = true
  try {
    const response = await api.get('/activites', {
      params: {
        with_counts: true
      }
    })
    activities.value = response.data.data
  } catch (error) {
    console.error('Error loading activities:', error)
  } finally {
    loading.value = false
  }
}

const loadProjects = async () => {
  try {
    const response = await api.get('/projets')
    projects.value = response.data.data
  } catch (error) {
    console.error('Error loading projects:', error)
  }
}

onMounted(() => {
  loadActivities()
  loadProjects()
})
</script>