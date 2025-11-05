<!-- resources/js/components/workspaces/WorkspaceMemberManagement.vue -->
<template>
  <div class="space-y-6">
    <!-- Header avec statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Membres</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
              {{ stats.total_members || 0 }}
            </p>
          </div>
          <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
            <UsersIcon class="w-8 h-8 text-blue-600 dark:text-blue-400" />
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Actifs</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
              {{ stats.active_members || 0 }}
            </p>
          </div>
          <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
            <CheckCircleIcon class="w-8 h-8 text-green-600 dark:text-green-400" />
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Invitations</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
              {{ stats.pending_invitations || 0 }}
            </p>
          </div>
          <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
            <MailIcon class="w-8 h-8 text-yellow-600 dark:text-yellow-400" />
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Administrateurs</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
              {{ stats.admin_count || 0 }}
            </p>
          </div>
          <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
            <ShieldIcon class="w-8 h-8 text-purple-600 dark:text-purple-400" />
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
      <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="flex space-x-8 px-6" aria-label="Tabs">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              activeTab === tab.id
                ? 'border-brand-500 text-brand-600 dark:text-brand-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
              'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2'
            ]"
          >
            <component :is="tab.icon" class="w-5 h-5" />
            {{ tab.label }}
            <span
              v-if="tab.count"
              :class="[
                'ml-2 py-0.5 px-2 rounded-full text-xs font-medium',
                activeTab === tab.id
                  ? 'bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400'
                  : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
              ]"
            >
              {{ tab.count }}
            </span>
          </button>
        </nav>
      </div>

      <!-- Tab Content -->
      <div class="p-6">
        <!-- Members Tab -->
        <div v-if="activeTab === 'members'">
          <!-- Actions Bar -->
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4 flex-1">
              <!-- Search -->
              <div class="relative flex-1 max-w-md">
                <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input
                  v-model="searchTerm"
                  type="text"
                  placeholder="Rechercher un membre..."
                  class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                />
              </div>

              <!-- Filter by Role -->
              <select
                v-model="filterRole"
                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500"
              >
                <option value="all">Tous les rôles</option>
                <option value="owner">Propriétaire</option>
                <option value="admin">Administrateur</option>
                <option value="member">Membre</option>
                <option value="viewer">Observateur</option>
              </select>
            </div>

            <button
              @click="showInviteModal = true"
              class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors"
            >
              <UserPlusIcon class="w-5 h-5" />
              Inviter un membre
            </button>
          </div>

          <!-- Loading -->
          <div v-if="loading" class="flex justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-600"></div>
          </div>

          <!-- Members List -->
          <div v-else class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Membre
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Rôle
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Projets
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Dernière activité
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Statut
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr
                  v-for="member in filteredMembers"
                  :key="member.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                >
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                      <div
                        v-if="member.avatar"
                        class="w-10 h-10 rounded-full overflow-hidden"
                      >
                        <img :src="member.avatar" :alt="member.nom" class="w-full h-full object-cover" />
                      </div>
                      <div
                        v-else
                        class="w-10 h-10 rounded-full bg-brand-600 flex items-center justify-center text-white font-medium"
                      >
                        {{ getInitials(member.nom) }}
                      </div>
                      <div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                          {{ member.nom }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                          {{ member.email }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      :class="[
                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                        getRoleColor(member.pivot?.role)
                      ]"
                    >
                      {{ getRoleLabel(member.pivot?.role) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900 dark:text-white">
                      {{ member.projets_count || 0 }} projet(s)
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ formatDate(member.last_activity_at) }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      :class="[
                        'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                        member.is_active
                          ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                          : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                      ]"
                    >
                      {{ member.is_active ? 'Actif' : 'Inactif' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-2">
                      <button
                        @click="viewMemberDetails(member)"
                        class="text-brand-600 hover:text-brand-900 dark:text-brand-400 text-sm font-medium"
                      >
                        Voir
                      </button>
                      <button
                        v-if="member.pivot?.role !== 'owner' && canManageMembers"
                        @click="editMember(member)"
                        class="text-gray-600 hover:text-gray-900 dark:text-gray-400 text-sm font-medium"
                      >
                        Modifier
                      </button>
                      <button
                        v-if="member.pivot?.role !== 'owner' && canManageMembers"
                        @click="removeMember(member)"
                        class="text-red-600 hover:text-red-900 dark:text-red-400 text-sm font-medium"
                      >
                        Retirer
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Invitations Tab -->
        <div v-if="activeTab === 'invitations'">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Invitations en attente
            </h3>
            <button
              @click="showInviteModal = true"
              class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors"
            >
              <UserPlusIcon class="w-5 h-5" />
              Nouvelle invitation
            </button>
          </div>

          <div v-if="invitations.length === 0" class="text-center py-12 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <MailIcon class="mx-auto h-12 w-12 text-gray-400" />
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
              Aucune invitation en attente
            </p>
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="invitation in invitations"
              :key="invitation.id"
              class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg"
            >
              <div class="flex items-center gap-4">
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                  <MailIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                </div>
                <div>
                  <div class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ invitation.email }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Invité le {{ formatDate(invitation.invited_at) }} • 
                    Rôle: {{ getRoleLabel(invitation.role) }}
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <button
                  @click="resendInvitation(invitation)"
                  class="px-3 py-1.5 text-sm text-brand-600 hover:text-brand-700 dark:text-brand-400 font-medium"
                >
                  Renvoyer
                </button>
                <button
                  @click="cancelInvitation(invitation)"
                  class="px-3 py-1.5 text-sm text-red-600 hover:text-red-700 dark:text-red-400 font-medium"
                >
                  Annuler
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Activity Tab -->
        <div v-if="activeTab === 'activity'">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
            Activités récentes des membres
          </h3>

          <div class="space-y-4">
            <div
              v-for="activity in recentActivities"
              :key="activity.id"
              class="flex items-start gap-4 p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg"
            >
              <div
                v-if="activity.user?.avatar"
                class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0"
              >
                <img :src="activity.user.avatar" :alt="activity.user.nom" class="w-full h-full object-cover" />
              </div>
              <div
                v-else
                class="w-10 h-10 rounded-full bg-brand-600 flex items-center justify-center text-white font-medium flex-shrink-0"
              >
                {{ getInitials(activity.user?.nom) }}
              </div>
              <div class="flex-1">
                <p class="text-sm text-gray-900 dark:text-white">
                  <span class="font-medium">{{ activity.user?.nom }}</span>
                  {{ activity.description }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  {{ formatDate(activity.created_at) }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Invite Member Modal -->
    <InviteMemberModal
      v-if="showInviteModal"
      :workspace-id="workspaceId"
      @close="showInviteModal = false"
      @invited="handleMemberInvited"
    />

    <!-- Edit Member Modal -->
    <EditWorkspaceMemberModal
      v-if="showEditModal"
      :member="selectedMember"
      :workspace-id="workspaceId"
      @close="showEditModal = false"
      @updated="handleMemberUpdated"
    />

    <!-- Member Details Modal -->
    <MemberDetailsModal
      v-if="showDetailsModal"
      :member="selectedMember"
      :workspace-id="workspaceId"
      @close="showDetailsModal = false"
    />

    <!-- Remove Member Confirmation -->
    <ConfirmModal
      v-if="showRemoveModal"
      title="Retirer le membre du workspace"
      :message="`Êtes-vous sûr de vouloir retirer ${memberToRemove?.nom} du workspace ? Cette action révoquera son accès à tous les projets, activités et tâches du workspace.`"
      confirm-text="Retirer définitivement"
      confirm-class="bg-red-600 hover:bg-red-700"
      type="danger"
      @confirm="confirmRemoveMember"
      @cancel="showRemoveModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useWorkspace } from '@/composables/useWorkspace'
import {
  UsersIcon,
  CheckCircleIcon,
  MailIcon,
  ShieldIcon,
  SearchIcon,
  UserPlusIcon
} from '@/icons'
import InviteMemberModal from './InviteMemberModal.vue'
import EditWorkspaceMemberModal from './EditWorkspaceMemberModal.vue'
import MemberDetailsModal from './MemberDetailsModal.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const props = defineProps({
  workspaceId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['member-updated'])

const {
  loading,
  fetchMembers,
  fetchInvitations,
  removeMember: removeMemberService,
  resendInvitation: resendInvitationService,
  cancelInvitation: cancelInvitationService,
    getRoleLabel,
  getRoleColor,
  canManageMembers
} = useWorkspace()

const members = ref([])
const invitations = ref([])
const recentActivities = ref([])
const stats = ref({})

const activeTab = ref('members')
const searchTerm = ref('')
const filterRole = ref('all')

const showInviteModal = ref(false)
const showEditModal = ref(false)
const showDetailsModal = ref(false)
const showRemoveModal = ref(false)
const selectedMember = ref(null)
const memberToRemove = ref(null)
 

const tabs = computed(() => [
  { id: 'members', label: 'Membres', icon: UsersIcon, count: members.value.length },
  { id: 'invitations', label: 'Invitations', icon: MailIcon, count: invitations.value.length },
  { id: 'activity', label: 'Activité', icon: CheckCircleIcon }
])

const filteredMembers = computed(() => {
  let result = members.value

  // Search filter
  if (searchTerm.value) {
    const term = searchTerm.value.toLowerCase()
    result = result.filter(m =>
      m.nom.toLowerCase().includes(term) ||
      m.email.toLowerCase().includes(term)
    )
  }

  // Role filter
  if (filterRole.value !== 'all') {
    result = result.filter(m => m.pivot?.role === filterRole.value)
  }

  return result
})

const getInitials = (name) => {
  if (!name) return 'U'
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}
  
const formatDate = (date) => {
  if (!date) return 'Jamais'
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const viewMemberDetails = (member) => {
  selectedMember.value = member
  showDetailsModal.value = true
}

const editMember = (member) => {
  selectedMember.value = member
  showEditModal.value = true
}

const removeMember = (member) => {
  memberToRemove.value = member
  showRemoveModal.value = true
}

const confirmRemoveMember = async () => {
  try {
    await removeMemberService(props.workspaceId, memberToRemove.value.id)
    showRemoveModal.value = false
    memberToRemove.value = null
    await loadData()
    emit('member-updated')
  } catch (error) {
    console.error('Error removing member:', error)
  }
}

const resendInvitation = async (invitation) => {
  try {
    await resendInvitationService(props.workspaceId, invitation.id)
    // Show success message
  } catch (error) {
    console.error('Error resending invitation:', error)
  }
}

const cancelInvitation = async (invitation) => {
  try {
    await cancelInvitationService(props.workspaceId, invitation.id)
    await loadData()
  } catch (error) {
    console.error('Error canceling invitation:', error)
  }
}

const handleMemberInvited = () => {
  showInviteModal.value = false
  loadData()
  emit('member-updated')
}

const handleMemberUpdated = () => {
  showEditModal.value = false
  selectedMember.value = null
  loadData()
  emit('member-updated')
}

const loadData = async () => {
  try {
    // Load members
    const membersResponse = await fetchMembers(props.workspaceId)
    members.value = membersResponse || []

    // Load invitations
    const invitationsResponse = await fetchInvitations(props.workspaceId)
    invitations.value = invitationsResponse || []

    // Calculate stats
    stats.value = {
      total_members: members.value.length,
      active_members: members.value.filter(m => m.is_active).length,
      pending_invitations: invitations.value.length,
      admin_count: members.value.filter(m => ['owner', 'admin','manager'].includes(m.pivot?.role)).length
    }

    // TODO: Charger les activités récentes depuis l'API
    recentActivities.value = [] // À implémenter avec notre endpoint d'activités
  } catch (error) {
    console.error('Error loading data:', error)
  }
}

onMounted(() => {
  loadData()
  // TODO: Récupérer l'utilisateur courant et vérifier les permissions
  // userCanManageMembers.value = canManageMembers(workspace, currentUser)
})

watch(() => props.workspaceId, () => {
  if (props.workspaceId) {
    loadData()
  }
})
</script>