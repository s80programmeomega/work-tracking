<!-- resources\js\pages\Documents.vue -->
<template>
  <admin-layout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />

    <div class="space-y-6">
      <!-- Header avec Stats Globales -->
      <div class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ $t('documents_page.title') }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              {{ $t('documents_page.subtitle') }}
            </p>
          </div>

          <!-- Quick Stats -->
          <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-3 bg-blue-50 px-4 py-3 dark:bg-blue-900/20">
              <p class="text-xs font-medium text-blue-600 dark:text-blue-400">
                {{ $t('documents_page.stat_workspaces') }}
              </p>
              <p class="mt-1 text-2xl font-bold text-blue-900 dark:text-blue-300">
                {{ stats.workspaces || 0 }}
              </p>
            </div>
            <div class="rounded-3 bg-green-50 px-4 py-3 dark:bg-green-900/20">
              <p class="text-xs font-medium text-green-600 dark:text-green-400">
                {{ $t('documents_page.stat_projects') }}
              </p>
              <p class="mt-1 text-2xl font-bold text-green-900 dark:text-green-300">
                {{ stats.projects || 0 }}
              </p>
            </div>
            <div class="rounded-3 bg-purple-50 px-4 py-3 dark:bg-purple-900/20">
              <p class="text-xs font-medium text-purple-600 dark:text-purple-400">
                {{ $t('documents_page.stat_documents') }}
              </p>
              <p class="mt-1 text-2xl font-bold text-purple-900 dark:text-purple-300">
                {{ stats.documents || 0 }}
              </p>
            </div>
            <div class="rounded-3 bg-orange-50 px-4 py-3 dark:bg-orange-900/20">
              <p class="text-xs font-medium text-orange-600 dark:text-orange-400">
                {{ $t('documents_page.stat_storage') }}
              </p>
              <p class="mt-1 text-2xl font-bold text-orange-900 dark:text-orange-300">
                {{ formatBytes(stats.totalSize || 0) }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation par Niveau -->
      <div class="rounded-3 border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 dark:border-gray-800">
          <nav class="flex space-x-1 overflow-x-auto px-6" aria-label="Tabs">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                'flex items-center gap-2 px-4 py-4 text-sm font-medium transition-all whitespace-nowrap',
                activeTab === tab.id
                  ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400'
                  : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
              ]"
            >
              <component :is="tab.icon" class="h-5 w-5" />
              {{ tab.name }}
              <span
                v-if="tab.count"
                :class="[
                  'ml-2 rounded-full px-2 py-0.5 text-xs font-medium',
                  activeTab === tab.id
                    ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
                    : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                ]"
              >
                {{ tab.count }}
              </span>
            </button>
          </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
          <!-- Par Workspace -->
          <div v-if="activeTab === 'workspaces'">
            <workspace-documents-browser
              @select="handleWorkspaceSelect"
            />
          </div>

          <!-- Par Projet -->
          <div v-else-if="activeTab === 'projects'">
            <project-documents-browser
              @select="handleProjectSelect"
            />
          </div>

          <!-- Par Activité -->
          <div v-else-if="activeTab === 'activities'">
            <activity-documents-browser
              @select="handleActivitySelect"
            />
          </div>

          <!-- Par Tâche -->
          <div v-else-if="activeTab === 'tasks'">
            <task-documents-browser
              @select="handleTaskSelect"
            />
          </div>

          <!-- Récents -->
          <div v-else-if="activeTab === 'recent'">
            <recent-documents-list />
          </div>

          <!-- Partagés -->
          <div v-else-if="activeTab === 'shared'">
            <shared-documents-list />
          </div>

          <!-- Mes Documents -->
          <div v-else-if="activeTab === 'my-documents'">
            <my-documents-list />
          </div>
        </div>
      </div>
    </div>

    <!-- Document Manager Modal (Vue détaillée) -->
    <document-manager-modal
      v-if="selectedEntity"
      :entity-type="selectedEntity.type"
      :entity-id="selectedEntity.id"
      :entity-label="selectedEntity.label"
      @close="selectedEntity = null"
    />
  </admin-layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import WorkspaceDocumentsBrowser from '@/components/documents/browsers/WorkspaceDocumentsBrowser.vue'
import ProjectDocumentsBrowser from '@/components/documents/browsers/ProjectDocumentsBrowser.vue'
import ActivityDocumentsBrowser from '@/components/documents/browsers/ActivityDocumentsBrowser.vue'
import TaskDocumentsBrowser from '@/components/documents/browsers/TaskDocumentsBrowser.vue'
import RecentDocumentsList from '@/components/documents/lists/RecentDocumentsList.vue'
import SharedDocumentsList from '@/components/documents/lists/SharedDocumentsList.vue'
import MyDocumentsList from '@/components/documents/lists/MyDocumentsList.vue'
import DocumentManagerModal from '@/components/documents/DocumentManagerModal.vue'
import {
  FolderIcon,
  BriefcaseIcon,
  RectangleStackIcon,
  CheckCircleIcon,
  ClockIcon,
  UserGroupIcon,
  DocumentIcon
} from '@heroicons/vue/24/outline'
import api from '@/api/axios'

const { t } = useI18n()
const route = useRoute()
const validTabs = ['workspaces', 'projects', 'activities', 'tasks', 'recent', 'shared', 'my-documents']
const currentPageTitle = computed(() => t('documents_page.page_title'))
const activeTab = ref(validTabs.includes(route.query.tab) ? route.query.tab : 'workspaces')
const selectedEntity = ref(null)
const stats = ref({
  workspaces: 0,
  projects: 0,
  activities: 0,
  tasks: 0,
  documents: 0,
  totalSize: 0
})

const tabs = computed(() => [
  {
    id: 'workspaces',
    name: t('documents_page.tab_workspaces'),
    icon: FolderIcon,
    count: stats.value.workspaces
  },
  {
    id: 'projects',
    name: t('documents_page.tab_projects'),
    icon: BriefcaseIcon,
    count: stats.value.projects
  },
  {
    id: 'activities',
    name: t('documents_page.tab_activities'),
    icon: RectangleStackIcon,
    count: stats.value.activities
  },
  {
    id: 'tasks',
    name: t('documents_page.tab_tasks'),
    icon: CheckCircleIcon,
    count: stats.value.tasks
  },
  {
    id: 'recent',
    name: t('documents_page.tab_recent'),
    icon: ClockIcon
  },
  {
    id: 'shared',
    name: t('documents_page.tab_shared'),
    icon: UserGroupIcon
  },
  {
    id: 'my-documents',
    name: t('documents_page.tab_my_documents'),
    icon: DocumentIcon
  }
])

const handleWorkspaceSelect = (workspace) => {
  selectedEntity.value = {
    type: 'App\\Models\\Workspace',
    id: workspace.id,
    label: workspace.nom
  }
}

const handleProjectSelect = (project) => {
  selectedEntity.value = {
    type: 'App\\Models\\Projet',
    id: project.id,
    label: project.nom
  }
}

const handleActivitySelect = (activity) => {
  selectedEntity.value = {
    type: 'App\\Models\\Activite',
    id: activity.id,
    label: activity.nom
  }
}

const handleTaskSelect = (task) => {
  selectedEntity.value = {
    type: 'App\\Models\\Tache',
    id: task.id,
    label: task.titre
  }
}

const loadStats = async () => {
  try {
    const response = await api.get('/documents/stats')
    stats.value = response.data.data
  } catch (error) {
    console.error('Error loading stats:', error)
  }
}

const formatBytes = (bytes) => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
}

onMounted(() => {
  loadStats()
})
</script>