<template>
  <div class="space-y-4">
    <!-- Filters -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <!-- Search Bar -->
      <div class="relative flex-1">
        <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="$t('documents_page.project_browser.search_placeholder')"
          class="w-full rounded-3 border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
        />
      </div>

      <!-- Status Filter -->
      <select
        v-model="statusFilter"
        class="rounded-3 border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
      >
        <option value="">{{ $t('documents_page.project_browser.filter_all_statuses') }}</option>
        <option value="active">{{ $t('documents_page.project_browser.filter_active') }}</option>
        <option value="completed">{{ $t('documents_page.project_browser.filter_completed') }}</option>
        <option value="archived">{{ $t('documents_page.project_browser.filter_archived') }}</option>
      </select>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
      <div v-for="i in 6" :key="i" class="h-56 animate-pulse rounded-3 bg-gray-200 dark:bg-gray-800"></div>
    </div>

    <!-- Projects Grid -->
    <div v-else-if="filteredProjects.length > 0" ref="staggerRef" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="project in filteredProjects"
        :key="project.id"
        @click="$emit('select', project)"
        class="stagger-item group relative cursor-pointer overflow-hidden rounded-3 border border-gray-200 bg-white transition-all hover:border-blue-500 dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-blue-400"
      >
        <!-- Header with Status -->
        <div class="relative h-24 p-4">
          <div class="absolute right-4 top-4">
            <span
              :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                getStatusBadgeClass(project.status)
              ]"
            >
              {{ getStatusLabel(project.status) }}
            </span>
          </div>
          
          <!-- Project Icon -->
          <div class="flex h-12 w-12 items-center justify-center rounded-3 bg-white/20 ">
            <BriefcaseIcon class="h-6 w-6 text-white" />
          </div>
        </div>

        <!-- Content -->
        <div class="p-5">
          <!-- Project Info -->
          <div class="mb-3">
            <h3 class="truncate text-lg font-semibold text-gray-900 dark:text-white">
              {{ project.nom }}
            </h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              {{ project.code }}
            </p>
          </div>

          <!-- Description -->
          <p
            v-if="project.description"
            class="mb-4 line-clamp-2 text-sm text-gray-600 dark:text-gray-400"
          >
            {{ project.description }}
          </p>

          <!-- Workspace Badge -->
          <div class="mb-4 flex items-center gap-2">
            <FolderIcon class="h-4 w-4 text-gray-400" />
            <span class="truncate text-xs text-gray-600 dark:text-gray-400">
              {{ project.workspace?.nom || $t('documents_page.project_browser.no_workspace') }}
            </span>
          </div>

          <!-- Stats -->
          <div class="grid grid-cols-3 gap-2 border-t border-gray-200 pt-4 dark:border-gray-800">
            <div class="text-center">
              <p class="text-lg font-bold text-gray-900 dark:text-white">
                {{ project.documents_count || 0 }}
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ $t('documents_page.project_browser.stat_docs') }}
              </p>
            </div>
            <div class="text-center">
              <p class="text-lg font-bold text-gray-900 dark:text-white">
                {{ project.activities_count || 0 }}
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ $t('documents_page.project_browser.stat_activities') }}
              </p>
            </div>
            <div class="text-center">
              <p class="text-lg font-bold text-gray-900 dark:text-white">
                {{ project.progression || 0 }}%
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ $t('documents_page.project_browser.stat_progress') }}
              </p>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="mt-4">
            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
              <div
                class="h-full transition-all"
                :style="{ width: (project.progression || 0) + '%' }"
              ></div>
            </div>
          </div>
        </div>

        <!-- Hover Effect -->
        <div class="absolute inset-x-0 bottom-0 h-1 opacity-0 transition-opacity group-hover:opacity-100"></div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="rounded-3 border-2 border-dashed border-gray-300 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-800/50">
      <BriefcaseIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
        {{ $t('documents_page.project_browser.empty_title') }}
      </h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ searchQuery ? $t('documents_page.project_browser.empty_search') : $t('documents_page.project_browser.empty_default') }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useStagger } from '@/composables/useAnimations'
import {
  BriefcaseIcon,
  MagnifyingGlassIcon,
  FolderIcon
} from '@heroicons/vue/24/outline'
import api from '@/api/axios'

defineEmits(['select'])

const { t } = useI18n()
const { staggerRef, applyStagger } = useStagger(50)

const projects = ref([])
const loading = ref(false)
const searchQuery = ref('')
const statusFilter = ref('')

const filteredProjects = computed(() => {
  let filtered = projects.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(p =>
      p.nom.toLowerCase().includes(query) ||
      p.code.toLowerCase().includes(query) ||
      p.description?.toLowerCase().includes(query)
    )
  }

  if (statusFilter.value) {
    filtered = filtered.filter(p => p.status === statusFilter.value)
  }

  return filtered
})

const getStatusLabel = (status) => {
  const key = `documents_page.project_browser.status_${status}`
  return t(key, status)
}

const getStatusBadgeClass = (status) => {
  const classes = {
    active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    completed: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    archived: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
  }
  return classes[status] || classes.active
}

const loadProjects = async () => {
  loading.value = true
  try {
    const response = await api.get('projets/mes-projets', {
      params: {
        with_counts: true
      }
    })
    projects.value = response.data.data
  } catch (error) {
    console.error('Error loading projects:', error)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadProjects()
  applyStagger()
})
</script>