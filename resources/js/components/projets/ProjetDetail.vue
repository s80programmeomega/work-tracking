<!-- resources/js/components/projets/ProjetDetail.vue -->
<template>
  <div class="space-y-6">
    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-600"></div>
    </div>

    <template v-else-if="projet">
      <!-- Header -->
      <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <!-- Banner avec couleur du projet -->
        <div class="h-32" :style="{ backgroundColor: projet.couleur || '#3B82F6' }"></div>

        <div class="px-6 py-4">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-2">
                <button @click="$emit('back')"
                  class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                  <ChevronLeftIcon class="w-5 h-5" />
                </button>
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                  {{ projet.code }}
                </span>
                <StarIcon v-if="projet.is_favorite" class="w-5 h-5 fill-yellow-400 text-yellow-400" />
              </div>
              <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                {{ projet.nom }}
              </h1>
              <p class="text-gray-600 dark:text-gray-400">
                {{ projet.description }}
              </p>
            </div>

            <div class="flex items-center gap-2 ml-4">
              <button @click="editProjet"
                class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                <EditIcon class="w-4 h-4" />
                Modifier
              </button>
              <button
                class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors">
                <SettingsIcon class="w-4 h-4" />
                Paramètres
              </button>
            </div>
          </div>

          <!-- Metadata -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                <CalendarIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
              </div>
              <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Début</div>
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ formatDate(projet.date_debut) }}
                </div>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                <CalendarIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
              </div>
              <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Fin</div>
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ formatDate(projet.date_fin) }}
                </div>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                <UsersIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
              </div>
              <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Responsable</div>
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ projet.responsable?.nom }}
                </div>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                <component :is="getStatusIcon(projet.status)" class="w-5 h-5 text-gray-600 dark:text-gray-400" />
              </div>
              <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Statut</div>
                <div>
                  <span :class="[
                    'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                    getStatusColor(projet.status)
                  ]">
                    {{ getStatusLabel(projet.status) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="border-b border-gray-200 dark:border-gray-700">
          <nav class="flex space-x-8 px-6" aria-label="Tabs">
            <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" :class="[
              activeTab === tab.id
                ? 'border-brand-500 text-brand-600 dark:text-brand-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
              'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2'
            ]">
              <component :is="tab.icon" class="w-5 h-5" />
              {{ tab.label }}
              <span v-if="tab.count" :class="[
                'ml-2 py-0.5 px-2 rounded-full text-xs font-medium',
                activeTab === tab.id
                  ? 'bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400'
                  : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
              ]">
                {{ tab.count }}
              </span>
            </button>
          </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
          <!-- Overview Tab -->
          <div v-if="activeTab === 'overview'" class="space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                  {{ projectStats.activites_count || 0 }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  Activités
                </div>
              </div>
              <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                  {{ projectStats.taches_count || 0 }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  Tâches
                </div>
              </div>
              <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                  {{ projectStats.taches_terminees || 0 }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  Complétées
                </div>
              </div>
              <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                  {{ projet.member_count || 0 }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  Membres
                </div>
              </div>
            </div>

            <!-- Progress -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Progression globale
                </span>
                <span class="text-sm font-bold text-gray-900 dark:text-white">
                  {{ projet.progression || 0 }}%
                </span>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                <div class="bg-brand-600 h-3 rounded-full transition-all duration-500"
                  :style="{ width: `${projet.progression || 0}%` }"></div>
              </div>
            </div>

            <!-- Objectifs -->
            <div v-if="projet.objectifs">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                Objectifs
              </h3>
              <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">
                {{ projet.objectifs }}
              </p>
            </div>

            <!-- Budget -->
            <div v-if="projet.budget"
              class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
              <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-blue-900 dark:text-blue-300">
                  Budget alloué
                </span>
                <span class="text-lg font-bold text-blue-900 dark:text-blue-300">
                  {{ formatCurrency(projet.budget) }} XAF
                </span>
              </div>
            </div>
          </div>

          <!-- Activities Tab -->
          <div v-if="activeTab === 'activities'" class="space-y-4">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Liste des activités
              </h3>
              <div class="flex items-center gap-4">
                <span class="text-sm text-gray-500">
                  {{ activities.length }} / {{ projectStats.activites_count || 0 }} activité(s)
                </span>
                <button @click="$emit('create-activity')"
                  class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors">
                  <PlusIcon class="w-4 h-4" />
                  Nouvelle activité
                </button>
              </div>
            </div>

            <!-- Message de débogage -->
            <div v-if="projectStats.activites_count > 0 && activities.length === 0"
              class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
              <p class="text-yellow-800 text-sm">
                ⚠️ Les statistiques indiquent {{ projectStats.activites_count }} activité(s)
                mais aucune n'est affichée.
                <button @click="loadProjet" class="underline ml-2">Recharger</button>
              </p>
            </div>

            <div v-if="activities.length === 0 && projectStats.activites_count === 0" class="text-center py-12">
              <ListIcon class="mx-auto h-12 w-12 text-gray-400" />
              <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Aucune activité pour ce projet
              </p>
            </div>

            <div v-else class="space-y-3">
              <div v-for="activity in activities" :key="activity.id" @click="$emit('view-activity', activity.id)"
                class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors">
                <div class="flex-1">
                  <h4 class="font-medium text-gray-900 dark:text-white">
                    {{ activity.nom || 'Activité sans nom' }}
                  </h4>
                  <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ activity.tache_count || 0 }} tâche(s) •
                    Progression: {{ activity.progression || 0 }}%
                  </p>
                </div>
                <div class="flex items-center gap-3">
                  <div class="text-right">
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                      {{ activity.progression || 0 }}%
                    </div>
                  </div>
                  <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                </div>
              </div>
            </div>
          </div>

          <!-- Members Tab -->
          <div v-if="activeTab === 'members'" class="space-y-4">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Membres du projet
              </h3>
              <button @click="showAddMemberModal = true"
                class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors">
                <PlusIcon class="w-4 h-4" />
                Ajouter un membre
              </button>
            </div>

            <div
              class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
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
                      Permissions
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                  <tr v-for="member in members" :key="member.id">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center gap-3">
                        <div v-if="member.avatar" class="w-10 h-10 rounded-full overflow-hidden">
                          <img :src="member.avatar" :alt="member.nom" class="w-full h-full object-cover" />
                        </div>
                        <div v-else
                          class="w-10 h-10 rounded-full bg-brand-600 flex items-center justify-center text-white font-medium">
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
                      <span :class="[
                        'px-2 py-1 rounded-full text-xs font-medium',
                        getRoleColor(member.pivot?.role)
                      ]">
                        {{ getRoleLabel(member.pivot?.role) }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center gap-2">
                        <span v-if="member.pivot?.can_edit"
                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                          Éditer
                        </span>
                        <span v-if="member.pivot?.can_delete"
                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                          Supprimer
                        </span>
                        <span v-if="member.pivot?.can_invite"
                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                          Inviter
                        </span>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                      <div class="flex items-center justify-end gap-2">
                        <button v-if="member.pivot?.role !== 'owner'" @click="editMember(member)"
                          class="text-brand-600 hover:text-brand-900 dark:text-brand-400 text-sm">
                          Modifier
                        </button>
                        <button v-if="member.pivot?.role !== 'owner'" @click="removeMember(member)"
                          class="text-red-600 hover:text-red-900 dark:text-red-400 text-sm">
                          Retirer
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Add Member Modal -->
    <AddMemberModal v-if="showAddMemberModal" :projet-id="projetId" @close="showAddMemberModal = false"
      @added="handleMemberAdded" />

    <!-- Edit Member Modal -->
    <EditMemberModal v-if="showEditMemberModal" :membre="selectedMember" :projet-id="projetId"
      @close="showEditMemberModal = false" @updated="handleMemberUpdated" />

    <!-- Remove Member Confirmation -->
    <ConfirmModal v-if="showRemoveMemberModal" title="Retirer le membre"
      :message="`Êtes-vous sûr de vouloir retirer ${memberToRemove?.nom} du projet ? Il perdra l'accès à toutes les tâches et documents du projet.`"
      confirm-text="Retirer" confirm-class="bg-red-600 hover:bg-red-700" @confirm="confirmRemoveMember"
      @cancel="showRemoveMemberModal = false" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useProjets } from '@/composables/useProjets'
import {
  ChevronLeftIcon,
  ChevronRightIcon,
  EditIcon,
  SettingsIcon,
  CalendarIcon,
  UsersIcon,
  StarIcon,
  ListIcon,
  PlusIcon,
  CheckCircleIcon,
  TrendingUpIcon,
  ArchiveIcon,
  ClockIcon
} from '@/icons'
import AddMemberModal from './AddMemberModal.vue'
import EditMemberModal from './EditMemberModal.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const props = defineProps({
  projetId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['back', 'create-activity', 'view-activity'])

const { fetchProjet, removeMember: removeMemberService } = useProjets()

const loading = ref(false)
// const projet = ref(null)
// const projectStats = ref({})
// const activities = ref([])
// const members = ref([])

const activeTab = ref('overview')
const showAddMemberModal = ref(false)
const showEditMemberModal = ref(false)
const showRemoveMemberModal = ref(false)
const selectedMember = ref(null)
const memberToRemove = ref(null)

const tabs = computed(() => [
  { id: 'overview', label: 'Vue d\'ensemble', icon: TrendingUpIcon },
  { id: 'activities', label: 'Activités', icon: ListIcon, count: activities.value.length },
  { id: 'members', label: 'Membres', icon: UsersIcon, count: members.value.length }
])

const projet = ref({
  activites: [],
  members: [],
  responsable: {},
  progression: 0,
  budget: 0
})
const projectStats = ref({})
const activities = ref([])
const members = ref([])

const loadProjet = async () => {
  try {
    loading.value = true
    const response = await fetchProjet(props.projetId)

    // Debug détaillé
    console.log('🔍 API Response structure:', response)
    console.log('📊 Response data:', response?.data)
    console.log('🎯 Projet activites:', response?.data?.activites)
    console.log('📈 Stats:', response?.stats)

    // Gestion robuste de la structure de réponse
    if (response && response.data) {
      projet.value = response.data
      projectStats.value = response.stats || {}

      // Assurez-vous que les tableaux existent
      activities.value = Array.isArray(response.data.activites)
        ? response.data.activites
        : []
      members.value = Array.isArray(response.data.members)
        ? response.data.members
        : []
    } else {
      // Fallback si la structure est différente
      projet.value = response || {}
      projectStats.value = {}
      activities.value = []
      members.value = []
    }

    // Debug: vérifiez les données chargées
    console.log('Projet loaded:', projet.value)
    console.log('Activities:', activities.value)

  } catch (error) {
    console.error('Error loading projet:', error)
    // Réinitialisez les données en cas d'erreur
    projet.value = null
    activities.value = []
    members.value = []
  } finally {
    loading.value = false
  }
}

const editProjet = () => {
  // TODO: Open edit modal or navigate to edit page
  console.log('Edit projet:', projet.value.id)
}

const editMember = (member) => {
  selectedMember.value = member
  showEditMemberModal.value = true
}

const removeMember = (member) => {
  memberToRemove.value = member
  showRemoveMemberModal.value = true
}

const confirmRemoveMember = async () => {
  try {
    await removeMemberService(props.projetId, memberToRemove.value.id)
    showRemoveMemberModal.value = false
    memberToRemove.value = null
    await loadProjet()
  } catch (error) {
    console.error('Error removing member:', error)
  }
}

const handleMemberAdded = () => {
  showAddMemberModal.value = false
  loadProjet()
}

const handleMemberUpdated = () => {
  showEditMemberModal.value = false
  selectedMember.value = null
  loadProjet()
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
  const labels = {
    active: 'Actif',
    completed: 'Terminé',
    archived: 'Archivé',
    pending: 'En attente'
  }
  return labels[status] || status
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

const getRoleColor = (role) => {
  const colors = {
    owner: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    admin: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    member: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    viewer: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
  }
  return colors[role] || colors.viewer
}

const getRoleLabel = (role) => {
  const labels = {
    owner: 'Propriétaire',
    admin: 'Administrateur',
    member: 'Membre',
    viewer: 'Observateur'
  }
  return labels[role] || role
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR').format(amount)
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

onMounted(() => {
  loadProjet()
})

watch(() => props.projetId, () => {
  if (props.projetId) {
    loadProjet()
  }
})
</script>