<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col">
    <!-- Header -->
    <header class="flex-shrink-0 flex items-center justify-center pt-10 pb-6 px-4">
      <div class="text-center">
        <img
          width="160"
          height="144"
          :src="LogoDark"
          alt="Uptiimum Work Tracking"
          class="mx-auto rounded-3 mb-4"
        />
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          {{ $t('workspace_picker.title') }}
        </h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ $t('workspace_picker.subtitle') }}
        </p>
      </div>
    </header>

    <!-- Main -->
    <main class="flex-1 w-full max-w-5xl mx-auto px-4 pb-12">
      <!-- Loading -->
      <div v-if="loading" class="flex items-center justify-center py-20">
        <div class="text-center">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mx-auto mb-3"></div>
          <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('workspace_picker.loading') }}</p>
        </div>
      </div>

      <!-- Empty -->
      <div v-else-if="workspaces.length === 0" class="text-center py-20">
        <p class="text-gray-500 dark:text-gray-400 mb-4">{{ $t('workspace_picker.no_workspaces') }}</p>
        <button
          @click="$router.push({ name: 'workspaces.create' })"
          class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-3 transition-colors"
        >
          {{ $t('workspace_picker.create_btn') }}
        </button>
      </div>

      <!-- Grid -->
      <div
        v-else
        ref="staggerRef"
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5"
      >
        <div
          v-for="workspace in workspaces"
          :key="workspace.id"
          :dusk="`workspace-card-${workspace.id}`"
          class="stagger-item group relative bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 hover:border-blue-400 dark:hover:border-blue-500 hover:shadow-lg transition-all duration-200 cursor-pointer overflow-hidden"
          @click="select(workspace)"
        >
          <!-- Top accent -->
          <div
            class="absolute top-0 left-0 w-full h-1.5"
            :class="workspace.is_active ? 'bg-blue-500' : 'bg-gray-300 dark:bg-gray-600'"
          ></div>

          <div class="p-5 pt-6">
            <!-- Logo + name -->
            <div class="flex items-start gap-4 mb-4">
              <div class="flex-shrink-0">
                <div v-if="workspace.logo_url" class="w-14 h-14 rounded-3 overflow-hidden border border-gray-200 dark:border-gray-600">
                  <img :src="workspace.logo_url" :alt="workspace.nom" class="w-full h-full object-cover" />
                </div>
                <div
                  v-else
                  class="w-14 h-14 rounded-3 flex items-center justify-center bg-gradient-to-br from-blue-500 to-blue-700"
                >
                  <span class="text-white font-bold text-lg">{{ getInitials(workspace.nom) }}</span>
                </div>
              </div>

              <div class="flex-1 min-w-0">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                  {{ workspace.nom }}
                </h2>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ workspace.code }}</p>

                <!-- Badges -->
                <div class="flex flex-wrap items-center gap-1.5 mt-2">
                  <span
                    v-if="workspace.user_role === 'owner'"
                    dusk="owner-badge"
                    class="px-2 py-0.5 text-xs font-medium rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400"
                  >
                    {{ $t('workspace_picker.badge_owner') }}
                  </span>
                  <span
                    v-else-if="workspace.user_role"
                    dusk="role-badge"
                    class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 capitalize"
                  >
                    {{ workspace.user_role }}
                  </span>

                  <span
                    :class="[
                      'px-2 py-0.5 text-xs font-medium rounded-full',
                      workspace.is_active
                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                        : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                    ]"
                  >
                    {{ workspace.is_active ? $t('workspace_picker.active') : $t('workspace_picker.inactive') }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Description -->
            <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2 mb-4">
              {{ workspace.description || $t('workspace_picker.no_description') }}
            </p>

            <!-- Stats -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
              <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                <span>
                  <span class="font-medium text-gray-700 dark:text-gray-300">{{ workspace.projets_count || 0 }}</span>
                  {{ $t('workspace_picker.projects') }}
                </span>
                <span>
                  <span class="font-medium text-gray-700 dark:text-gray-300">{{ workspace.member_count || 0 }}</span>
                  {{ $t('workspace_picker.members') }}
                </span>
              </div>
              <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="flex-shrink-0 text-center pb-6 text-xs text-gray-400 dark:text-gray-600">
      {{ $t('workspace_picker.footer') }}
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api/axios'
import { useWorkspace } from '@/composables/useWorkspace'
import { useStagger } from '@/composables/useAnimations'

const LogoDark = new URL('@/assets/images/logo/logo-transparent.png', import.meta.url).href

const router = useRouter()
const { selectWorkspace } = useWorkspace()
const { staggerRef, applyStagger } = useStagger(50)

const workspaces = ref<any[]>([])
const loading = ref(true)

const getInitials = (name: string): string => {
  if (!name) return 'WS'
  return name
    .split(' ')
    .map((w: string) => w[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const select = async (workspace: any) => {
  await selectWorkspace(workspace)
  router.push({ name: 'Dashboard' })
}

onMounted(async () => {
  // Ne pas charger si plus de token (ex: déconnexion en cours)
  if (!localStorage.getItem('auth_token')) {
    loading.value = false
    return
  }
  try {
    const { data } = await api.get('/workspaces', { params: { per_page: 100 } })
    workspaces.value = data.data ?? data
  } catch {
    // Le guard de route ou l'intercepteur axios gère la redirection
  } finally {
    loading.value = false
    applyStagger()
  }
})
</script>
