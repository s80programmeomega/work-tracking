<!-- resources/js/pages/users/Invitations.vue -->
<template>
  <AdminLayout>
    <PageBreadcrumb
      :pageTitle="$t('invitations.page_title')"
      :breadcrumbs="[
        { label: $t('invitations.breadcrumb_users'), path: '/users' },
        { label: $t('invitations.breadcrumb_invitations'), path: '/users/invitations' }
      ]"
    />
    
    <div class="bg-gray-50 dark:bg-gray-900 py-8">
      <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
              <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ $t('invitations.title') }}
              </h1>
              <p class="text-gray-600 dark:text-gray-400 mt-2">
                {{ $t('invitations.subtitle') }}
              </p>
            </div>
            <div class="flex flex-wrap items-center gap-4">
              <!-- Inviter (workspace courant, hors super-admin) -->
              <button
                v-if="!isSuperAdmin && currentWorkspaceId"
                @click="showInviteModal = true"
                class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white rounded-3 hover:bg-brand-700 transition-colors"
              >
                <UserPlusIcon class="w-5 h-5" />
                {{ $t('invitations.new_invitation') }}
              </button>

              <!-- Global Stats -->
              <div class="flex items-center gap-6">
                <div class="text-center">
                  <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ statistics.total_invitations || 0 }}
                  </div>
                  <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('invitations.stat_total') }}
                  </div>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                    {{ statistics.pending_invitations || 0 }}
                  </div>
                  <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('invitations.stat_pending') }}
                  </div>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ statistics.accepted_invitations || 0 }}
                  </div>
                  <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('invitations.stat_accepted') }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Filters and Search -->
        <div class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-6 mb-6">
          <div class="flex items-center gap-4">
            <!-- Search -->
            <div class="relative flex-1 max-w-md">
              <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                v-model="filters.search"
                type="text"
                :placeholder="$t('invitations.search_placeholder')"
                class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
              />
            </div>

            <!-- Status Filter -->
            <select
              v-model="filters.status"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500"
            >
              <option value="all">{{ $t('invitations.filter_all_statuses') }}</option>
              <option value="pending">{{ $t('invitations.filter_pending') }}</option>
              <option value="accepted">{{ $t('invitations.filter_accepted') }}</option>
              <option value="expired">{{ $t('invitations.filter_expired') }}</option>
              <option value="cancelled">{{ $t('invitations.filter_cancelled') }}</option>
            </select>

            <!-- Workspace Filter -->
            <select
              v-model="filters.workspace_id"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500"
            >
              <option value="all">{{ $t('invitations.filter_all_workspaces') }}</option>
              <option 
                v-for="workspace in accessibleWorkspaces" 
                :key="workspace.id" 
                :value="workspace.id"
              >
                {{ workspace.nom }}
              </option>
            </select>

            <!-- Refresh Button -->
            <button
              @click="loadData"
              :disabled="loading"
              class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-3 transition-colors"
            >
              <RefreshIcon class="w-5 h-5" />
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-600"></div>
        </div>

        <!-- Empty State -->
        <div v-else-if="filteredInvitations.length === 0" class="text-center py-16 bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700">
          <MailIcon class="mx-auto h-16 w-16 text-gray-400" />
          <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
            {{ $t('invitations.empty_title') }}
          </h3>
          <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            {{ filters.search || filters.status !== 'all' || filters.workspace_id !== 'all'
              ? $t('invitations.empty_filtered')
              : $t('invitations.empty_all_done') }}
          </p>
        </div>

        <!-- Invitations List -->
        <div v-else ref="listRef" class="space-y-4">
          <div
            v-for="invitation in filteredInvitations"
            :key="invitation.id"
            class="stagger-item bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-6 transition-shadow"
          >
            <div class="flex items-start justify-between">
              <!-- Left Section -->
              <div class="flex items-start gap-4 flex-1">
                <!-- Status Icon -->
                <div 
                  :class="[
                    'p-3 rounded-3 flex-shrink-0',
                    getStatusColor(invitation.status)
                  ]"
                >
                  <MailIcon class="w-6 h-6" />
                </div>

                <!-- Invitation Details -->
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-2">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                      {{ invitation.email }}
                    </h3>
                    <span
                      :class="[
                        'px-2 py-1 rounded-full text-xs font-medium',
                        getStatusBadgeColor(invitation.status)
                      ]"
                    >
                      {{ getStatusLabel(invitation.status) }}
                    </span>
                    <span
                      :class="[
                        'px-2 py-1 rounded-full text-xs font-medium',
                        getRoleColor(invitation.role)
                      ]"
                    >
                      {{ getRoleLabel(invitation.role) }}
                    </span>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                      <span class="text-gray-500 dark:text-gray-400">{{ $t('invitations.label_workspace') }}</span>
                      <span class="ml-2 font-medium text-gray-900 dark:text-white">
                        {{ invitation.workspace?.nom }}
                      </span>
                    </div>
                    <div>
                      <span class="text-gray-500 dark:text-gray-400">{{ $t('invitations.label_invited_by') }}</span>
                      <span class="ml-2 font-medium text-gray-900 dark:text-white">
                        {{ invitation.invited_by?.nom }}
                      </span>
                    </div>
                    <div>
                      <span class="text-gray-500 dark:text-gray-400">{{ $t('invitations.label_expires') }}</span>
                      <span class="ml-2 font-medium text-gray-900 dark:text-white">
                        {{ formatDate(invitation.expires_at) }}
                      </span>
                    </div>
                  </div>

                  <!-- Message -->
                  <div v-if="invitation.message" class="mt-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-3">
                    <p class="text-sm text-gray-600 dark:text-gray-400 italic">
                      "{{ invitation.message }}"
                    </p>
                  </div>

                  <!-- Permissions -->
                  <div v-if="invitation.permissions" class="mt-3">
                    <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                      <span v-if="invitation.permissions.can_create_projects" class="flex items-center gap-1">
                        <CheckCircleIcon class="w-4 h-4 text-green-500" />
                        {{ $t('invitations.perm_create_projects') }}
                      </span>
                      <span v-if="invitation.permissions.can_invite_members" class="flex items-center gap-1">
                        <CheckCircleIcon class="w-4 h-4 text-green-500" />
                        {{ $t('invitations.perm_invite_members') }}
                      </span>
                      <span v-if="invitation.permissions.can_manage_settings" class="flex items-center gap-1">
                        <CheckCircleIcon class="w-4 h-4 text-green-500" />
                        {{ $t('invitations.perm_manage_settings') }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex items-center gap-2 flex-shrink-0 ml-4">
                <!-- View Workspace -->
                <button
                  v-if="canManageWorkspace(invitation.workspace_id)"
                  @click="viewWorkspace(invitation.workspace_id)"
                  class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-3 transition-colors"
                  :title="$t('invitations.btn_view_workspace')"
                >
                  <EyeIcon class="w-4 h-4" />
                </button>

                <!-- Resend Invitation -->
                <button
                  v-if="invitation.status === 'pending' && canManageWorkspace(invitation.workspace_id)"
                  @click="resendInvitation(invitation)"
                  :disabled="resending === invitation.id"
                  class="p-2 text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300 hover:bg-brand-50 dark:hover:bg-brand-900/20 rounded-3 transition-colors"
                  :title="$t('invitations.btn_resend')"
                >
                  <RefreshIcon v-if="resending === invitation.id" class="w-4 h-4 animate-spin" />
                  <MailIcon v-else class="w-4 h-4" />
                </button>

                <!-- Cancel Invitation -->
                <button
                  v-if="invitation.status === 'pending' && canManageWorkspace(invitation.workspace_id)"
                  @click="cancelInvitation(invitation)"
                  :disabled="cancelling === invitation.id"
                  class="p-2 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-3 transition-colors"
                  :title="$t('invitations.btn_cancel')"
                >
                  <XIcon v-if="cancelling === invitation.id" class="w-4 h-4 animate-spin" />
                  <XIcon v-else class="w-4 h-4" />
                </button>

                <!-- Copy Invitation Link -->
                <button
                  v-if="invitation.status === 'pending'"
                  @click="copyInvitationLink(invitation)"
                  class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-3 transition-colors"
                  :title="$t('invitations.btn_copy_link')"
                >
                  <CopyIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination && pagination.total > pagination.per_page" class="mt-8 flex items-center justify-between">
          <div class="text-sm text-gray-700 dark:text-gray-300">
            {{ $t('invitations.pagination_showing', { from: pagination.from, to: pagination.to, total: pagination.total }) }}
          </div>
          <div class="flex gap-2">
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="!pagination.prev_page_url"
              class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ $t('invitations.btn_previous') }}
            </button>
            <button
              @click="changePage(pagination.current_page + 1)"
              :disabled="!pagination.next_page_url"
              class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ $t('invitations.btn_next') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modale d'invitation : la feature existante, branchée sur le workspace courant -->
    <InviteMemberModal
      v-if="showInviteModal && currentWorkspaceId"
      :workspace-id="currentWorkspaceId"
      @close="showInviteModal = false"
      @invited="handleInvited"
    />
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useWorkspace } from '@/composables/useWorkspace'
import { useAuthStore } from '@/stores/authStore'
import { useStagger } from '@/composables/useAnimations'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import InviteMemberModal from '@/components/workspaces/InviteMemberModal.vue'
import {
  MailIcon,
  SearchIcon,
  RefreshIcon,
  EyeIcon,
  XIcon,
  CheckCircleIcon,
  UserPlusIcon,
  CopyIcon
} from '@/icons'

const { t } = useI18n()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const {
  fetchAllInvitations,
  fetchInvitations,
  resendInvitation: resendInvitationService,
  cancelInvitation: cancelInvitationService,
  getRoleLabel,
  getRoleColor,
} = useWorkspace()
const { staggerRef: listRef, applyStagger } = useStagger(60)

// Super-admin : vue plateforme (toutes les invitations). Sinon : invitations du
// workspace courant — réutilise exactement la feature existante (useWorkspace).
const isSuperAdmin = computed(() => !!authStore.user?.is_super_admin)
const currentWorkspaceId = computed(() => authStore.user?.current_workspace_id)

const loading = ref(false)
const invitations = ref([])
const accessibleWorkspaces = ref([])
const statistics = ref({})
const pagination = ref(null)
const resending = ref(null)
const cancelling = ref(null)
const showInviteModal = ref(false)

const filters = ref({
  search: '',
  status: 'all',
  workspace_id: 'all',
  page: 1
})

const filteredInvitations = computed(() => {
  let result = invitations.value

  // Search filter
  if (filters.value.search) {
    const term = filters.value.search.toLowerCase()
    result = result.filter(inv => 
      inv.email.toLowerCase().includes(term) ||
      inv.workspace?.nom.toLowerCase().includes(term) ||
      inv.invited_by?.nom.toLowerCase().includes(term)
    )
  }

  // Status filter
  if (filters.value.status !== 'all') {
    result = result.filter(inv => inv.status === filters.value.status)
  }

  // Workspace filter
  if (filters.value.workspace_id !== 'all') {
    result = result.filter(inv => inv.workspace_id === parseInt(filters.value.workspace_id))
  }

  return result
})

const getStatusLabel = (status) => {
  const labels = {
    pending: t('invitations.status_pending'),
    accepted: t('invitations.status_accepted'),
    expired: t('invitations.status_expired'),
    cancelled: t('invitations.status_cancelled')
  }
  return labels[status] || status
}

const getStatusColor = (status) => {
  const colors = {
    pending: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400',
    accepted: 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
    expired: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
    cancelled: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
  }
  return colors[status] || colors.pending
}

const getStatusBadgeColor = (status) => {
  const colors = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    accepted: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    expired: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
  }
  return colors[status] || colors.pending
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Super-admin gère tout ; sinon, gestion limitée au workspace courant.
const canManageWorkspace = (workspaceId) => {
  return isSuperAdmin.value || workspaceId === currentWorkspaceId.value
}

const viewWorkspace = (workspaceId) => {
  router.push(`/workspaces/${workspaceId}`)
}

const copyInvitationLink = async (invitation) => {
  const link = `${window.location.origin}/workspace-invitations/${invitation.token}`
  try {
    await navigator.clipboard.writeText(link)
    toast.success(t('invitations.link_copied'))
  } catch (err) {
    console.error('Erreur lors de la copie:', err)
    toast.error(t('invitations.link_copy_error'))
  }
}

const resendInvitation = async (invitation) => {
  try {
    resending.value = invitation.id
    await resendInvitationService(invitation.workspace_id, invitation.id)
    toast.success(t('invitations.resend_success'))
    await loadData()
  } catch (error) {
    console.error('Error resending invitation:', error)
    toast.error(error.response?.data?.message ?? t('invitations.resend_error'))
  } finally {
    resending.value = null
  }
}

const cancelInvitation = async (invitation) => {
  if (!confirm(t('invitations.confirm_cancel'))) {
    return
  }

  try {
    cancelling.value = invitation.id
    await cancelInvitationService(invitation.workspace_id, invitation.id)
    toast.success(t('invitations.cancel_success'))
    await loadData()
  } catch (error) {
    console.error('Error cancelling invitation:', error)
    toast.error(error.response?.data?.message ?? t('invitations.cancel_error'))
  } finally {
    cancelling.value = null
  }
}

const handleInvited = () => {
  showInviteModal.value = false
  loadData()
}

const changePage = (page) => {
  filters.value.page = page
  loadData()
}

const loadData = async () => {
  loading.value = true
  try {
    if (isSuperAdmin.value) {
      // Vue plateforme : toutes les invitations (endpoint super-admin).
      const response = await fetchAllInvitations(filters.value)
      invitations.value = response?.data ?? []
      pagination.value = response?.meta ?? null
      statistics.value = response?.statistics ?? {}
    } else if (currentWorkspaceId.value) {
      // Vue workspace courant : la feature existante (useWorkspace).
      const list = await fetchInvitations(currentWorkspaceId.value)
      invitations.value = Array.isArray(list) ? list : (list?.data ?? [])
      pagination.value = null
      statistics.value = {
        total_invitations: invitations.value.length,
        pending_invitations: invitations.value.filter(i => i.status === 'pending').length,
        accepted_invitations: invitations.value.filter(i => i.status === 'accepted').length,
      }
    } else {
      invitations.value = []
      statistics.value = {}
    }
    await applyStagger()
  } catch (error) {
    console.error('Error loading invitations:', error)
    toast.error(error.response?.data?.message ?? t('invitations.load_error'))
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>