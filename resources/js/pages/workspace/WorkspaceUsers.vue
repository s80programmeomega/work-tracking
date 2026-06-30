<!-- resources/js/pages/workspace/WorkspaceUsers.vue -->
<template>
  <AdminLayout>
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 dusk="workspace-users-title" class="text-2xl font-semibold text-gray-900 dark:text-white">
            {{ $t('workspace_users.title') }}
          </h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $t('workspace_users.subtitle') }}
          </p>
        </div>
        <router-link
          to="/dashboard"
          class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
        >
          <i class="fas fa-arrow-left"></i> {{ $t('workspace_users.back') }}
        </router-link>
      </div>

      <!-- Profile Modal -->
      <UserProfileModal
        v-if="profileModal.open"
        :user-id="profileModal.userId"
        @close="profileModal.open = false"
      />

      <!-- Activity Modal -->
      <div v-if="activityModal.open" dusk="ws-activity-modal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-3 shadow-xl w-full max-w-5xl max-h-[90vh] flex flex-col">
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ $t('admin.users.activity_modal_title', { name: activityModal.user?.nom }) }}
            </h3>
            <button
              @click="activityModal.open = false"
              class="p-2 text-gray-400 transition-colors rounded-3 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="overflow-y-auto flex-1">
            <ActivityLogTab
              v-if="activityModal.open"
              :fixed-causer-id="activityModal.user?.id"
              :fixed-causer-label="activityModal.user?.nom"
            />
          </div>
        </div>
      </div>

      <!-- Change Role Modal -->
      <div v-if="roleModal.open" dusk="role-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-3 shadow-xl w-full max-w-md mx-4">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ $t('workspace_users.change_role_title') }}
            </h3>
            <button @click="roleModal.open = false" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="px-6 py-4 space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
              {{ $t('workspace_users.change_role_desc', { name: roleModal.user?.nom }) }}
            </p>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ $t('workspace_users.col_role') }}
              </label>
              <select
                v-model="roleModal.role"
                dusk="role-select"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
              >
                <option v-for="r in workspaceRoles" :key="r.value" :value="r.value">{{ r.label }}</option>
              </select>
            </div>
            <div v-if="roleModal.error" class="text-sm text-red-600 dark:text-red-400">{{ roleModal.error }}</div>
          </div>
          <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
            <button
              @click="roleModal.open = false"
              class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              {{ $t('common.cancel') }}
            </button>
            <button
              @click="saveRole"
              :disabled="roleModal.saving"
              dusk="save-role-button"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-3 hover:bg-blue-700 disabled:opacity-50"
            >
              {{ roleModal.saving ? $t('common.saving') : $t('common.save') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Search -->
      <div>
        <input
          v-model="search"
          @input="debouncedFetch"
          type="text"
          :placeholder="$t('workspace_users.search_placeholder')"
          dusk="workspace-users-search"
          class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 w-72"
        />
      </div>

      <!-- Error -->
      <div v-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-3">
        {{ error }}
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-600"></div>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs text-gray-500 dark:text-gray-400 uppercase">
              <tr>
                <th class="px-4 py-3 text-left">{{ $t('workspace_users.col_name') }}</th>
                <th class="px-4 py-3 text-left">{{ $t('workspace_users.col_email') }}</th>
                <th class="px-4 py-3 text-left">{{ $t('workspace_users.col_role') }}</th>
                <th class="px-4 py-3 text-left">{{ $t('workspace_users.col_joined') }}</th>
                <th class="px-4 py-3 text-left">{{ $t('workspace_users.col_last_login') }}</th>
                <th class="px-4 py-3 text-left">{{ $t('workspace_users.col_actions') }}</th>
              </tr>
            </thead>
            <tbody ref="tbodyRef" class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr v-if="!users.length">
                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                  {{ $t('workspace_users.no_users') }}
                </td>
              </tr>
              <tr
                v-for="user in users"
                :key="user.id"
                class="stagger-item hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                dusk="workspace-user-row"
              >
                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ user.nom }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ user.email }}</td>
                <td class="px-4 py-3">
                  <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                    {{ user.workspace_role ?? '—' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                  {{ user.joined_at ? formatDate(user.joined_at) : '—' }}
                </td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                  {{ user.last_login_at ? formatDate(user.last_login_at) : $t('workspace_users.never_logged_in') }}
                </td>
                <td class="px-4 py-3">
                  <div class="flex gap-2">
                    <button
                      v-if="user.workspace_role !== 'owner'"
                      dusk="ws-change-role-button"
                      @click="openRoleModal(user)"
                      class="text-xs px-2 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded hover:bg-blue-200 transition-colors"
                      :title="$t('workspace_users.btn_change_role')"
                    >
                      <i class="fas fa-user-tag mr-1"></i>{{ $t('workspace_users.btn_change_role') }}
                    </button>
                    <button
                      dusk="ws-view-activity-button"
                      @click="openActivityModal(user)"
                      class="text-xs px-2 py-1 bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 rounded hover:bg-purple-200 transition-colors"
                      :title="$t('admin.users.btn_activity')"
                    >
                      <i class="fas fa-history mr-1"></i>{{ $t('admin.users.btn_activity') }}
                    </button>
                    <button
                      dusk="ws-view-profile-button"
                      @click="openProfileModal(user)"
                      class="text-xs px-2 py-1 bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400 rounded hover:bg-teal-200 transition-colors"
                      :title="$t('admin.users.btn_view_profile')"
                    >
                      <i class="fas fa-user mr-1"></i>{{ $t('admin.users.btn_view_profile') }}
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination && pagination.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
          <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ pagination.current_page }} / {{ pagination.last_page }}
          </p>
          <div class="flex gap-2">
            <button
              @click="page--; fetchUsers()"
              :disabled="pagination.current_page <= 1"
              class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded disabled:opacity-40"
            >{{ $t('common.previous') }}</button>
            <button
              @click="page++; fetchUsers()"
              :disabled="pagination.current_page >= pagination.last_page"
              class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded disabled:opacity-40"
            >{{ $t('common.next') }}</button>
          </div>
        </div>
      </div>

    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import ActivityLogTab from '@/components/admin/logs/ActivityLogTab.vue'
import UserProfileModal from '@/components/admin/UserProfileModal.vue'
import { useStagger } from '@/composables/useAnimations'
import { useAuthStore } from '@/stores/authStore'
import api from '@/api/axios'

const { t } = useI18n()
const authStore = useAuthStore()

const users = ref([])
const pagination = ref(null)
const loading = ref(false)
const error = ref(null)
const search = ref('')
const page = ref(1)

const { staggerRef: tbodyRef, applyStagger } = useStagger(40)

const activityModal = ref({ open: false, user: null })
const profileModal = ref({ open: false, userId: null })
const roleModal = ref({ open: false, user: null, role: '', saving: false, error: null })

const workspaceRoles = [
  { value: 'manager', label: t('workspace_users.role_manager') },
  { value: 'cadre', label: t('workspace_users.role_cadre') },
  { value: 'collaborateur', label: t('workspace_users.role_collaborateur') },
  { value: 'stagiaire', label: t('workspace_users.role_stagiaire') },
  { value: 'observateur', label: t('workspace_users.role_observateur') },
]

const openActivityModal = (user) => {
  activityModal.value = { open: true, user }
}

const openProfileModal = (user) => {
  profileModal.value = { open: true, userId: user.id }
}

const openRoleModal = (user) => {
  roleModal.value = { open: true, user, role: user.workspace_role ?? 'collaborateur', saving: false, error: null }
}

const saveRole = async () => {
  const workspaceId = authStore.currentWorkspaceId
  if (!workspaceId || !roleModal.value.user) { return }

  roleModal.value.saving = true
  roleModal.value.error = null
  try {
    await api.put(`/workspaces/${workspaceId}/members/${roleModal.value.user.id}`, { role: roleModal.value.role })
    const idx = users.value.findIndex((u) => u.id === roleModal.value.user.id)
    if (idx !== -1) {
      users.value[idx] = { ...users.value[idx], workspace_role: roleModal.value.role }
    }
    roleModal.value.open = false
  } catch (e) {
    roleModal.value.error = e.response?.data?.message ?? t('workspace_users.role_save_error')
  } finally {
    roleModal.value.saving = false
  }
}

const formatDate = (iso) => iso ? new Date(iso).toLocaleDateString() : '—'

let debounceTimer = null
const debouncedFetch = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => { page.value = 1; fetchUsers() }, 350)
}

const fetchUsers = async () => {
  const workspaceId = authStore.currentWorkspaceId
  if (!workspaceId) { return }

  loading.value = true
  error.value = null
  try {
    const params = { page: page.value, per_page: 20, search: search.value || undefined }
    const { data } = await api.get(`/workspaces/${workspaceId}/users`, { params })
    users.value = data.data
    pagination.value = { current_page: data.current_page, last_page: data.last_page }
    applyStagger()
  } catch (e) {
    error.value = e.response?.data?.message ?? t('workspace_users.load_error')
  } finally {
    loading.value = false
  }
}

onMounted(fetchUsers)
</script>
