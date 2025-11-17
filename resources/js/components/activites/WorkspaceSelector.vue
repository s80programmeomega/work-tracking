<!-- resources/js/components/activites/WorkspaceSelector.vue -->
<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center text-white font-bold text-sm shadow-md">
          {{ currentWorkspaceInitials }}
        </div>
        <div>
          <p class="text-sm font-semibold text-gray-900 dark:text-white">
            {{ currentWorkspaceName || 'Aucun workspace' }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            Workspace actuel
          </p>
        </div>
      </div>

      <button
        @click="showDropdown = !showDropdown"
        class="relative px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors flex items-center gap-2"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12M8 12h12m-12 5h12M3 7h.01M3 12h.01M3 17h.01" />
        </svg>
        <span>Changer</span>
        <svg 
          :class="['w-4 h-4 transition-transform', { 'rotate-180': showDropdown }]"
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>

        <!-- Dropdown -->
        <transition name="fade-slide">
          <div
            v-if="showDropdown"
            class="absolute right-0 top-full mt-2 w-72 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50"
            @click.stop
          >
            <div class="p-2 max-h-80 overflow-y-auto">
              <button
                v-for="workspace in workspaces"
                :key="workspace.id"
                @click="selectWorkspace(workspace)"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-left"
                :class="{ 'bg-brand-50 dark:bg-brand-900/20': workspace.id === currentWorkspaceId }"
              >
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold text-sm">
                  {{ getWorkspaceInitials(workspace.nom) }}
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                    {{ workspace.nom }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ workspace.projets_count || 0 }} projet(s)
                  </p>
                </div>
                <svg
                  v-if="workspace.id === currentWorkspaceId"
                  class="w-5 h-5 text-brand-500 flex-shrink-0"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </button>

              <div v-if="loading" class="text-center py-4">
                <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-brand-500"></div>
              </div>

              <div v-if="!loading && workspaces.length === 0" class="text-center py-8 text-gray-500 text-sm">
                Aucun workspace disponible
              </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 p-2">
              <router-link
                to="/workspaces/create"
                class="w-full flex items-center gap-2 px-3 py-2 text-sm font-medium text-brand-600 dark:text-brand-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors"
                @click="showDropdown = false"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Créer un workspace</span>
              </router-link>
            </div>
          </div>
        </transition>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, onBeforeUnmount } from 'vue'
import { useWorkspace } from '@/composables/useWorkspace'

const emit = defineEmits(['workspace-changed'])

const showDropdown = ref(false)

const {
  currentWorkspace,
  currentWorkspaceId,
  workspaces,
  loading,
  fetchWorkspaces,
  selectWorkspace: setWorkspace
} = useWorkspace()

const currentWorkspaceName = computed(() => currentWorkspace.value?.nom || '')

const currentWorkspaceInitials = computed(() => {
  if (!currentWorkspace.value) return 'WS'
  return getWorkspaceInitials(currentWorkspace.value.nom)
})

const getWorkspaceInitials = (name) => {
  return name
    ?.split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2) || 'WS'
}

const selectWorkspace = async (workspace) => {
  try {
    await setWorkspace(workspace)
    showDropdown.value = false
    emit('workspace-changed', workspace)
  } catch (error) {
    console.error('Erreur lors du changement de workspace:', error)
  }
}

// Click outside to close
const handleClickOutside = (event) => {
  if (!event.target.closest('.relative')) {
    showDropdown.value = false
  }
}

watch(showDropdown, (isOpen) => {
  if (isOpen) {
    document.addEventListener('click', handleClickOutside)
  } else {
    document.removeEventListener('click', handleClickOutside)
  }
})

onMounted(async () => {
  if (workspaces.value.length === 0) {
    await fetchWorkspaces()
  }
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.2s ease;
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>