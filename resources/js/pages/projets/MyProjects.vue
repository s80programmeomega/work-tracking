<!-- resources/js/pages/projets/MyProjects.vue - VERSION CORRIGÉE -->
<template>
  <AdminLayout>
    <div class="bg-gray-50 dark:bg-gray-900">
      <!-- Header Section - Trello Style -->
      <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-full mx-auto px-4 py-4">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <!-- Left Side -->
            <div class="flex min-w-0 items-center gap-4">
              <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                  {{ pageTitle }}
                </h1>
                <p class="text-gray-500 dark:text-gray-400 flex items-center gap-2 text-sm mt-1">
                  <FolderIcon class="w-4 h-4" />
                  {{ $t('my_projects.count_in_workspace', { count: filteredProjets.length, workspace: currentWorkspaceName || $t('my_projects.current_workspace') }) }}
                </p>
              </div>
            </div>

            <!-- Right Side -->
            <div class="flex flex-wrap items-center gap-3">
              <!-- Workspace Selector -->
              <div v-if="hasWorkspaces" class="relative">
                <select v-model="selectedWorkspaceId" @change="onWorkspaceChange"
                  class="pl-10 pr-8 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 appearance-none cursor-pointer">
                  <option v-for="workspace in workspaces" :key="workspace.id" :value="workspace.id"
                    class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    {{ workspace.nom }}
                  </option>
                </select>
                <BuildingOfficeIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 dark:text-gray-400" />
                <ChevronDownIcon
                  class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 dark:text-gray-400 pointer-events-none" />
              </div>

              <!-- Display Mode Toggle for Super Admin -->
              <button v-if="isSuperAdmin" @click="toggleDisplayMode" :class="[
                'inline-flex items-center gap-2 px-4 py-2 rounded-3 text-sm font-medium transition-colors',
                displayMode === 'all-projects'
                  ? 'bg-blue-600 text-white hover:bg-blue-700'
                  : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'
              ]">
                <FolderIcon class="w-4 h-4" />
                {{ displayMode === 'all-projects' ? $t('my_projects.toggle_all') : $t('my_projects.toggle_mine') }}
              </button>

              <!-- Bouton Statistiques -->
              <button
                @click="showStats = !showStats"
                :disabled="loading || !hasStats"
                class="inline-flex items-center gap-2 rounded-3 border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition-colors disabled:opacity-50"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                {{ $t('my_projects.statistics_btn') }}
              </button>

              <!-- Create Project Button -->
              <button dusk="create-projet-btn" @click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-3 hover:bg-blue-700 transition-colors font-medium">
                <PlusIcon class="w-4 h-4" />
                {{ $t('my_projects.new_project') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Panneau statistiques -->
      <div v-if="!loading && hasStats" class="px-4 pt-4">
        <transition
          enter-active-class="transition-all duration-300 ease-out"
          enter-from-class="opacity-0 -translate-y-4"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-200 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-4"
        >
          <MyProjectsStats
            v-if="showStats"
            :stats="stats"
            :loading="false"
            @close="showStats = false"
          />
        </transition>
      </div>

      <!-- Main Content -->
      <div class="px-4 py-4">
        <div class="max-w-full mx-auto">
          <!-- Filters and Search - Trello Style -->
          <div class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-4 mb-4">
            <!-- Search Bar -->
            <div class="relative mb-4">
              <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input v-model="searchTerm" type="text" :placeholder="$t('my_projects.search_placeholder')"
                class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" />
            </div>

            <!-- Filter Buttons -->
            <div class="flex flex-wrap gap-2">
              <!-- Status Filter -->
              <select v-model="filters.status"
                class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 transition-all">
                <option value="all">{{ $t('my_projects.filter_all_statuses') }}</option>
                <option value="active">{{ $t('my_projects.filter_active') }}</option>
                <option value="completed">{{ $t('my_projects.filter_completed') }}</option>
                <option value="archived">{{ $t('my_projects.filter_archived') }}</option>
              </select>

              <!-- Favorites Button -->
              <button @click="filters.favorites = !filters.favorites" :class="[
                'inline-flex items-center gap-2 px-3 py-2 rounded-3 text-sm font-medium transition-colors',
                filters.favorites
                  ? 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-300 border border-yellow-300 dark:border-yellow-700'
                  : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600'
              ]">
                <StarIcon :class="filters.favorites ? 'fill-yellow-400 text-yellow-400' : 'text-gray-400'"
                  class="w-4 h-4" />
                {{ $t('my_projects.filter_favorites') }}
              </button>

              <!-- Overdue Button -->
              <button @click="filters.overdue = !filters.overdue" :class="[
                'inline-flex items-center gap-2 px-3 py-2 rounded-3 text-sm font-medium transition-colors',
                filters.overdue
                  ? 'bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300 border border-red-300 dark:border-red-700'
                  : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600'
              ]">
                <AlertCircleIcon class="w-4 h-4" />
                {{ $t('my_projects.filter_overdue') }}
              </button>

              <!-- Reset Filters -->
              <button v-if="hasActiveFilters" @click="resetFilters"
                class="inline-flex items-center gap-2 px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                <XIcon class="w-4 h-4" />
                {{ $t('my_projects.reset_filters') }}
              </button>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="flex justify-center py-16">
            <div class="relative">
              <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
              <div class="absolute inset-0 flex items-center justify-center">
                <FolderIcon class="w-6 h-6 text-blue-600" />
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else-if="filteredProjets.length === 0"
            class="text-center py-16 bg-white dark:bg-gray-800 rounded-3 border-2 border-dashed border-gray-300 dark:border-gray-700">
            <div
              class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
              <FolderOpenIcon class="w-8 h-8 text-gray-400" />
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
              {{ $t('my_projects.empty_title') }}
            </h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
              {{ searchTerm || hasActiveFilters
                ? $t('my_projects.empty_with_filter')
                : $t('my_projects.empty_no_filter')
              }}
            </p>
            <button v-if="!searchTerm && !hasActiveFilters" @click="openCreateModal"
              class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-3 hover:bg-blue-700 transition-colors font-medium">
              <PlusIcon class="w-5 h-5" />
              {{ $t('my_projects.create_first') }}
            </button>
          </div>

          <!-- Projects Grid - Trello Style Cards -->
          <div v-else ref="gridRef" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div v-for="projet in filteredProjets" :key="projet.id"
              class="stagger-item bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 transition-shadow overflow-hidden cursor-pointer"
              @click="viewProjet(projet.id)">
              <!-- Card Header with Color Band -->
              <div class="h-2" :style="{ backgroundColor: projet.couleur || '#3B82F6' }"></div>

              <div class="p-4">
                <!-- Top Section -->
                <div class="flex items-start justify-between mb-3">
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                      <span
                        class="text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                        {{ projet.code }}
                      </span>
                      <span v-if="projet.responsable_id === currentUserId"
                        class="text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-2 py-1 rounded">
                        {{ $t('my_projects.owner_badge') }}
                      </span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors line-clamp-1">
                      {{ projet.nom }}
                    </h3>
                  </div>

                  <div class="flex items-center gap-1">
                    <button @click.stop="toggleFavorite(projet)"
                      class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                      <StarIcon :class="projet.is_favorite ? 'fill-yellow-400 text-yellow-400' : 'text-gray-400'"
                        class="w-4 h-4" />
                    </button>

                    <div class="relative">
                      <button @click.stop="toggleMenu(projet.id)"
                        class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <HorizontalDots class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                      </button>

                      <!-- Dropdown Menu -->
                      <div v-if="activeMenuId === projet.id" v-click-outside="() => activeMenuId = null"
                        class="absolute right-0 mt-1 w-48 bg-white dark:bg-gray-700 rounded-3 border border-gray-200 dark:border-gray-600 z-10">
                        <button @click.stop="viewProjet(projet.id)"
                          class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-t-lg transition-colors">
                          <EyeIcon class="w-4 h-4" />
                          {{ $t('my_projects.menu_view') }}
                        </button>
                        <button v-if="projet.responsable_id === currentUserId" @click.stop="editProjet(projet)"
                          class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                          <PencilIcon class="w-4 h-4" />
                          {{ $t('my_projects.menu_edit') }}
                        </button>
                        <button @click.stop="duplicateProjet(projet)"
                          class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                          <CopyIcon class="w-4 h-4" />
                          {{ $t('my_projects.menu_duplicate') }}
                        </button>
                        <button v-if="projet.status === 'active' && projet.responsable_id === currentUserId"
                          @click.stop="archiveProjet(projet)"
                          class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                          <ArchiveIcon class="w-4 h-4" />
                          {{ $t('my_projects.menu_archive') }}
                        </button>
                        <button v-if="projet.responsable_id === currentUserId" @click.stop="deleteProjet(projet)"
                          class="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-b-lg transition-colors">
                          <TrashIcon class="w-4 h-4" />
                          {{ $t('my_projects.menu_delete') }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Description -->
                <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3 min-h-[2.5rem]">
                  {{ projet.description || $t('my_projects.no_description') }}
                </p>

                <!-- Status Badges -->
                <div class="flex items-center gap-2 mb-3">
                  <span :class="[
                    'inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium',
                    getStatusColor(projet.status)
                  ]">
                    <component :is="getStatusIcon(projet.status)" class="w-3 h-3" />
                    {{ getStatusLabel(projet.status) }}
                  </span>

                  <span v-if="projet.is_overdue && projet.status === 'active'"
                    class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                    <AlertCircleIcon class="w-3 h-3" />
                    {{ $t('my_projects.overdue_badge') }}
                  </span>
                </div>

                <!-- Progress Bar -->
                <div class="mb-3">
                  <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                      {{ $t('my_projects.progression') }}
                    </span>
                    <span class="text-xs font-bold text-gray-900 dark:text-white">
                      {{ projet.progression }}%
                    </span>
                  </div>
                  <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all duration-300"
                      :class="projet.progression >= 100 ? 'bg-green-500' : 'bg-blue-600'"
                      :style="{ width: `${projet.progression}%` }">
                    </div>
                  </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-3 gap-2 mb-3 pb-3 border-b border-gray-200 dark:border-gray-700">
                  <div class="text-center">
                    <div class="text-sm font-bold text-gray-900 dark:text-white">
                      {{ getProjectActivitiesCount(projet) }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                      {{ $t('my_projects.activities') }}
                    </div>
                  </div>
                  <div class="text-center">
                    <div class="text-sm font-bold text-gray-900 dark:text-white">
                      {{ getProjectTasksCount(projet) }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                      {{ $t('my_projects.tasks') }}
                    </div>
                  </div>
                  <div class="text-center">
                    <div class="text-sm font-bold text-gray-900 dark:text-white">
                      {{ projet.member_count || 0 }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                      {{ $t('my_projects.members') }}
                    </div>
                  </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between">
                  <!-- Dates -->
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ formatDate(projet.date_debut) }} - {{ formatDate(projet.date_fin) }}
                  </div>

                  <!-- Responsable -->
                  <div class="flex items-center gap-2">
                    <div v-if="projet.responsable?.avatar"
                      class="w-6 h-6 rounded-full overflow-hidden ring-1 ring-gray-200 dark:ring-gray-700">
                      <img :src="projet.responsable.avatar" :alt="projet.responsable.nom"
                        class="w-full h-full object-cover" />
                    </div>
                    <div v-else
                      class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-semibold ring-1 ring-gray-200 dark:ring-gray-700">
                      {{ getInitials(projet.responsable?.nom) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="pagination.last_page > 1"
            class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-4 mt-4">
            <div class="text-sm text-gray-700 dark:text-gray-400">
              {{ $t('my_projects.pagination_showing', {
                from: (pagination.current_page - 1) * pagination.per_page + 1,
                to: Math.min(pagination.current_page * pagination.per_page, pagination.total),
                total: pagination.total
              }) }}
            </div>
            <div class="flex gap-1">
              <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
                class="px-3 py-2 rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm">
                {{ $t('common.previous') }}
              </button>
              <button v-for="page in paginationButtons" :key="page" @click="changePage(page)" :disabled="page === '...'"
                :class="[
                  'px-3 py-2 rounded border text-sm font-medium transition-colors',
                  page === pagination.current_page
                    ? 'bg-blue-600 text-white border-blue-600'
                    : page === '...'
                      ? 'border-gray-300 dark:border-gray-600 text-gray-400 cursor-default'
                      : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                ]">
                {{ page }}
              </button>
              <button @click="changePage(pagination.current_page + 1)"
                :disabled="pagination.current_page === pagination.last_page"
                class="px-3 py-2 rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm">
                {{ $t('common.next') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <ProjetFormModal v-if="showFormModal" :projet="selectedProjet" @close="closeFormModal" @saved="handleProjetSaved" />

    <ConfirmModal v-if="showDeleteModal" :title="$t('my_projects.delete_modal_title')"
      :message="`${$t('projects.delete_confirm')} ${projetToDelete?.nom} ?`"
      :confirm-text="$t('common.delete')" confirm-class="bg-red-600 hover:bg-red-700" @confirm="confirmDelete"
      @cancel="showDeleteModal = false" />
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useProjets } from '@/composables/useProjets'
import { useWorkspace } from '@/composables/useWorkspace'
import { useAuthStore } from '@/stores/authStore'
import { useStagger } from '@/composables/useAnimations'
import AdminLayout from "@/components/layout/AdminLayout.vue"
import MyProjectsStats from '@/components/projets/MyProjectsStats.vue'
import ProjetFormModal from '@/components/projets/ProjetFormModal.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import {
  PlusIcon, SearchIcon, StarIcon, AlertCircleIcon, XIcon,
  ListIcon, FolderOpenIcon, HorizontalDots, PencilIcon, CopyIcon,
  ArchiveIcon, TrashIcon, CheckCircleIcon, ClockIcon,
  TrendingUpIcon, FolderIcon, ChevronDownIcon, BuildingOfficeIcon, EyeIcon
} from '@/icons'

const router = useRouter()
const { t } = useI18n()
const showStats = ref(false)
const authStore = useAuthStore()

const {
  loading,
  projets,
  stats,
  pagination,
  errors,
  isSuperAdmin,
  fetchDashboardStats,
  fetchProjets,
  fetchAllProjets,
  fetchProjetsByWorkspace,
  deleteProjet: deleteProjetService,
  toggleFavorite: toggleFavoriteService,
  archiveProjet: archiveProjetService,
  unarchiveProjet: unarchiveProjetService,
  cloneProjet: cloneProjetService
} = useProjets()

const {
  currentWorkspace,
  workspaces,
  hasWorkspaces,
  currentWorkspaceId,
  currentWorkspaceName,
  selectWorkspace,
  fetchWorkspaces
} = useWorkspace()

// State
const searchTerm = ref('')
const activeMenuId = ref(null)
const showFormModal = ref(false)
const showDeleteModal = ref(false)
const selectedProjet = ref(null)
const projetToDelete = ref(null)
const selectedWorkspaceId = ref(null)
const displayMode = ref('my-projects')

const filters = ref({
  status: 'all',
  favorites: false,
  overdue: false
})

// Current User ID
const currentUserId = computed(() => authStore.user?.id)

// Computed
const pageTitle = computed(() => {
  if (isSuperAdmin.value && displayMode.value === 'all-projects') {
    return selectedWorkspaceId.value
      ? `${t('my_projects.all_projects')} - ${currentWorkspaceName.value}`
      : t('my_projects.all_projects_global')
  }
  return t('my_projects.my_projects')
})

const hasActiveFilters = computed(() => {
  return filters.value.status !== 'all' ||
    filters.value.favorites ||
    filters.value.overdue
})

const filteredProjets = computed(() => {
  let result = projets.value

  // Search
  if (searchTerm.value) {
    const term = searchTerm.value.toLowerCase()
    result = result.filter(p =>
      p.nom.toLowerCase().includes(term) ||
      p.code.toLowerCase().includes(term) ||
      p.description?.toLowerCase().includes(term)
    )
  }

  // Status filter
  if (filters.value.status !== 'all') {
    result = result.filter(p => p.status === filters.value.status)
  }

  // Favorites filter
  if (filters.value.favorites) {
    result = result.filter(p => p.is_favorite)
  }

  // Overdue filter
  if (filters.value.overdue) {
    result = result.filter(p => p.is_overdue)
  }

  return result
})

const hasStats = computed(() => {
  return stats.value && (stats.value.total_projets > 0 || stats.value.total_activites > 0 || stats.value.total_taches > 0)
})

const paginationButtons = computed(() => {
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const delta = 2
  const range = []
  const rangeWithDots = []

  for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
    range.push(i)
  }

  if (current - delta > 2) {
    rangeWithDots.push(1, '...')
  } else {
    rangeWithDots.push(1)
  }

  rangeWithDots.push(...range)

  if (current + delta < last - 1) {
    rangeWithDots.push('...', last)
  } else if (last > 1) {
    rangeWithDots.push(last)
  }

  return rangeWithDots
})

// Methods
const loadData = async () => {
  const workspaceId = selectedWorkspaceId.value || currentWorkspaceId.value

  // Charger les statistiques
  await fetchDashboardStats(workspaceId)

  // Charger les projets selon le mode
  if (isSuperAdmin.value && displayMode.value === 'all-projects') {
    await fetchAllProjets({
      per_page: 12,
      workspace_id: workspaceId
    })
  } else {
    await fetchProjetsByWorkspace(workspaceId, { per_page: 12 })
  }

  // Charger les workspaces
  await fetchWorkspaces()
  applyStagger()

  // Set selected workspace to current workspace
  if (currentWorkspaceId.value && !selectedWorkspaceId.value) {
    selectedWorkspaceId.value = currentWorkspaceId.value
  }
}

// ✅ NOUVELLES MÉTHODES POUR CORRIGER LES STATISTIQUES
const getProjectProgression = (projet) => {
  // Priorité 1: Utiliser la progression calculée par le backend
  if (projet.progression !== undefined && projet.progression !== null) {
    return Math.round(projet.progression)
  }

  // Priorité 2: Calculer à partir des activités
  if (projet.activites && projet.activites.length > 0) {
    const totalProgression = projet.activites.reduce((sum, activite) => sum + (activite.progression || 0), 0)
    return Math.round(totalProgression / projet.activites.length)
  }

  // Fallback
  return 0
}

const getProjectActivitiesCount = (projet) => {
  // Priorité 1: Utiliser le count du backend
  if (projet.activites_count !== undefined && projet.activites_count !== null) {
    return projet.activites_count
  }

  // Priorité 2: Compter les activités chargées
  if (projet.activites && Array.isArray(projet.activites)) {
    return projet.activites.length
  }

  // Fallback
  return 0
}

const getProjectTasksCount = (projet) => {
  // Priorité 1: Utiliser le count du backend
  if (projet.taches_count !== undefined && projet.taches_count !== null) {
    return projet.taches_count
  }

  // Priorité 2: Calculer à partir des activités
  if (projet.activites && Array.isArray(projet.activites)) {
    return projet.activites.reduce((sum, activite) => sum + (activite.tache_count || 0), 0)
  }

  // Fallback
  return 0
}

const toggleDisplayMode = () => {
  displayMode.value = displayMode.value === 'my-projects' ? 'all-projects' : 'my-projects'
  loadData()
}

const onWorkspaceChange = async () => {
  if (selectedWorkspaceId.value && selectedWorkspaceId.value !== currentWorkspaceId.value) {
    const workspace = workspaces.value.find(w => w.id === selectedWorkspaceId.value)
    if (workspace) {
      selectWorkspace(workspace)
      await loadData()
    }
  }
}

const openCreateModal = () => {
  selectedProjet.value = null
  showFormModal.value = true
}

const editProjet = (projet) => {
  selectedProjet.value = projet
  showFormModal.value = true
  activeMenuId.value = null
}

const closeFormModal = () => {
  showFormModal.value = false
  selectedProjet.value = null
}

const handleProjetSaved = () => {
  loadData()
  closeFormModal()
}

const viewProjet = (projetId) => {
  router.push({ name: 'projets.show', params: { id: projetId } })
}

const deleteProjet = (projet) => {
  projetToDelete.value = projet
  showDeleteModal.value = true
  activeMenuId.value = null
}

const confirmDelete = async () => {
  try {
    await deleteProjetService(projetToDelete.value.id)
    showDeleteModal.value = false
    projetToDelete.value = null
    await loadData()
  } catch (error) {
    console.error('Error deleting projet:', error)
  }
}

const toggleFavorite = async (projet) => {
  try {
    await toggleFavoriteService(projet.id)
    await loadData()
  } catch (error) {
    console.error('Error toggling favorite:', error)
  }
}

const archiveProjet = async (projet) => {
  try {
    await archiveProjetService(projet.id)
    activeMenuId.value = null
    await loadData()
  } catch (error) {
    console.error('Error archiving projet:', error)
  }
}

const unarchiveProjet = async (projet) => {
  try {
    await unarchiveProjetService(projet.id)
    activeMenuId.value = null
    await loadData()
  } catch (error) {
    console.error('Error unarchiving projet:', error)
  }
}

const duplicateProjet = async (projet) => {
  try {
    await cloneProjetService(projet.id, {
      nom: `${projet.nom} (Copie)`
    })
    activeMenuId.value = null
    await loadData()
  } catch (error) {
    console.error('Error duplicating projet:', error)
  }
}

const toggleMenu = (projetId) => {
  activeMenuId.value = activeMenuId.value === projetId ? null : projetId
}

const resetFilters = () => {
  filters.value = {
    status: 'all',
    favorites: false,
    overdue: false
  }
  searchTerm.value = ''
}

const changePage = async (page) => {
  if (page === '...' || page < 1 || page > pagination.value.last_page) return

  const workspaceId = selectedWorkspaceId.value || currentWorkspaceId.value

  if (isSuperAdmin.value && displayMode.value === 'all-projects') {
    await fetchAllProjets({
      page,
      per_page: pagination.value.per_page,
      workspace_id: workspaceId
    })
  } else {
    await fetchProjetsByWorkspace(workspaceId, {
      page,
      per_page: pagination.value.per_page
    })
  }
}

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
  const map = {
    active: 'my_projects.status_active',
    completed: 'my_projects.status_completed',
    archived: 'my_projects.status_archived',
    pending: 'my_projects.status_pending',
  }
  return map[status] ? t(map[status]) : status
}

const getStatusIcon = (status) => {
  const icons = {
    active: TrendingUpIcon,
    completed: CheckCircleIcon,
    archived: ArchiveIcon,
    pending: ClockIcon
  }
  return icons[status] || ClockIcon
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getInitials = (name) => {
  if (!name) return 'U'
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

// Lifecycle
onMounted(() => {
  loadData()
})

// Watchers
watch(currentWorkspaceId, (newWorkspaceId) => {
  if (newWorkspaceId) {
    selectedWorkspaceId.value = newWorkspaceId
    loadData()
  }
})

watch(displayMode, () => {
  loadData()
})

// Click outside directive
const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = (event) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value()
      }
    }
    document.addEventListener('click', el.clickOutsideEvent)
  },
  unmounted(el) {
    document.removeEventListener('click', el.clickOutsideEvent)
  }
}
</script>

<style scoped>
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>