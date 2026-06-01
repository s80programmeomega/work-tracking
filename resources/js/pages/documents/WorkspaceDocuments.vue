<template>
  <admin-layout>
    <div class="space-y-6">
      <!-- Breadcrumb -->
      <nav class="flex items-center gap-2 text-sm">
        <router-link
          :to="{ name: 'workspaces.show', params: { id: workspaceId } }"
          class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
        >
          {{ workspace?.nom || 'Workspace' }}
        </router-link>
        <ChevronRightIcon class="h-4 w-4 text-gray-400" />
        <span class="font-medium text-gray-900 dark:text-white">{{ $t('document_pages.documents') }}</span>
      </nav>

      <!-- Workspace Info Card -->
      <div
        v-if="workspace"
        class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]"
      >
        <div class="flex items-start gap-4">
          <div class="flex h-14 w-14 items-center justify-center rounded-3 bg-indigo-600 text-white">
            <BuildingOffice2Icon class="h-7 w-7" />
          </div>
          <div class="flex-1">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
              {{ workspace.nom }}
            </h2>
            <p v-if="workspace.description" class="mt-1 text-sm text-gray-600 dark:text-gray-400">
              {{ workspace.description }}
            </p>
          </div>
        </div>
      </div>

      <!-- Access denied -->
      <div
        v-if="accessDenied"
        class="rounded-3 border border-red-200 bg-red-50 p-6 text-center dark:border-red-800 dark:bg-red-900/20"
      >
        <LockClosedIcon class="mx-auto h-10 w-10 text-red-500" />
        <p class="mt-3 text-sm font-medium text-red-700 dark:text-red-400">
          {{ $t('document_pages.access_denied') }}
        </p>
      </div>

      <!-- Documents -->
      <template v-else-if="workspace">
        <document-manager
          :documentable-type="'App\\Models\\Workspace'"
          :documentable-id="workspaceId"
          :entity-label="workspace.nom"
          :can-upload="canManage"
        />
      </template>
    </div>
  </admin-layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ChevronRightIcon, BuildingOffice2Icon, LockClosedIcon } from '@heroicons/vue/24/outline'
import DocumentManager from '@/components/documents/DocumentManager.vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import api from '@/api/axios'
import { useAuthStore } from '@/stores/authStore'
import { useWorkspacePermissions } from '@/composables/useWorkspacePermissions'

const route = useRoute()
const authStore = useAuthStore()

const workspaceId = computed(() => Number(route.params.workspaceId))
const workspace = ref(null)
const accessDenied = ref(false)

const { canManageWorkspaceDocuments } = useWorkspacePermissions(workspace)

const canManage = computed(() => canManageWorkspaceDocuments.value)

const loadWorkspace = async () => {
    try {
        const response = await api.get(`/workspaces/${workspaceId.value}`)
        workspace.value = response.data.data ?? response.data
    } catch (error) {
        if (error.response?.status === 403) {
            accessDenied.value = true
        }
    }
}

onMounted(() => {
    loadWorkspace()
})
</script>
