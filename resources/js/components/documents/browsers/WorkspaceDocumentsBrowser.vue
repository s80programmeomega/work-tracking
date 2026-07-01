<!-- resources\js\components\documents\browsers\WorkspaceDocumentsBrowser.vue -->
<template>
  <div class="space-y-4">
    <!-- Search Bar -->
    <div class="relative">
      <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
      <input
        v-model="searchQuery"
        type="text"
        :placeholder="$t('documents_page.workspace_browser.search_placeholder')"
        class="w-full rounded-3 border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
      />
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
      <div v-for="i in 6" :key="i" class="h-48 animate-pulse rounded-3 bg-gray-200 dark:bg-gray-800"></div>
    </div>

    <!-- Workspaces Grid -->
    <div v-else-if="filteredWorkspaces.length > 0" ref="staggerRef" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="workspace in filteredWorkspaces"
        :key="workspace.id"
        @click="$emit('select', workspace)"
        class="stagger-item group relative cursor-pointer overflow-hidden rounded-3 border border-gray-200 bg-white p-5 transition-all hover:border-blue-500 dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-blue-400"
      >
        <!-- Badge Role -->
        <div class="absolute right-4 top-4">
          <span
            :class="[
              'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
              getRoleBadgeClass(workspace.user_role)
            ]"
          >
            {{ getRoleLabel(workspace.user_role) }}
          </span>
        </div>

        <!-- Workspace Icon/Logo -->
        <div class="mb-4 flex items-center gap-3">
          <div
            v-if="workspace.logo"
            class="h-12 w-12 flex-shrink-0 overflow-hidden rounded-3"
          >
            <img :src="workspace.logo" :alt="workspace.nom" class="h-full w-full object-cover" />
          </div>
          <div
            v-else
            class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-3 text-white"
          >
            <FolderIcon class="h-6 w-6" />
          </div>
          <div class="min-w-0 flex-1">
            <h3 class="truncate text-lg font-semibold text-gray-900 dark:text-white">
              {{ workspace.nom }}
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              {{ workspace.code }}
            </p>
          </div>
        </div>

        <!-- Description -->
        <p
          v-if="workspace.description"
          class="mb-4 line-clamp-2 text-sm text-gray-600 dark:text-gray-400"
        >
          {{ workspace.description }}
        </p>

        <!-- Stats -->
        <div class="mt-4 grid grid-cols-3 gap-2 border-t border-gray-200 pt-4 dark:border-gray-800">
          <div class="text-center">
            <p class="text-lg font-bold text-gray-900 dark:text-white">
              {{ workspace.documents_count || 0 }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              {{ $t('documents_page.workspace_browser.stat_documents') }}
            </p>
          </div>
          <div class="text-center">
            <p class="text-lg font-bold text-gray-900 dark:text-white">
              {{ workspace.projects_count || 0 }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              {{ $t('documents_page.workspace_browser.stat_projects') }}
            </p>
          </div>
          <div class="text-center">
            <p class="text-lg font-bold text-gray-900 dark:text-white">
              {{ workspace.members_count || 0 }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              {{ $t('documents_page.workspace_browser.stat_members') }}
            </p>
          </div>
        </div>

        <!-- Hover Effect -->
        <div class="absolute inset-x-0 bottom-0 h-1 opacity-0 transition-opacity group-hover:opacity-100"></div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="rounded-3 border-2 border-dashed border-gray-300 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-800/50">
      <FolderIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
        {{ $t('documents_page.workspace_browser.empty_title') }}
      </h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ searchQuery ? $t('documents_page.workspace_browser.empty_search') : $t('documents_page.workspace_browser.empty_default') }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useStagger } from '@/composables/useAnimations'
import { FolderIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import { useWorkspace } from '@/composables/useWorkspace'
import { getRoleLabel } from '@/permissions/Permission'

defineEmits(['select'])

const { t } = useI18n()
const { workspaces, loading, fetchWorkspaces } = useWorkspace()
const { staggerRef, applyStagger } = useStagger(50)
const searchQuery = ref('')

const filteredWorkspaces = computed(() => {
  if (!searchQuery.value) return workspaces.value

  const query = searchQuery.value.toLowerCase()
  return workspaces.value.filter(ws =>
    ws.nom.toLowerCase().includes(query) ||
    ws.code.toLowerCase().includes(query) ||
    ws.description?.toLowerCase().includes(query)
  )
})


const getRoleBadgeClass = (role) => {
  const classes = {
    owner: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    admin: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    member: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    viewer: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
  }
  return classes[role] || classes.viewer
}

onMounted(async () => {
  await fetchWorkspaces()
  applyStagger()
})
</script>