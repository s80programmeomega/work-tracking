<!-- resources/js/components/workspaces/MemberDetailsModal.vue - CORRIGÉ -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { 
  XIcon, 
  ChevronRightIcon, 
  PencilIcon,
  FolderOpenIcon,
  CubeIcon,
  UserPlusIcon,
  CogIcon
} from '@/icons'
import { useWorkspace } from '@/composables/useWorkspace'

const props = defineProps({
  member: {
    type: Object,
    required: true
  },
  workspaceId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['close', 'edit'])

const router = useRouter()
const { getMemberPermissions, getRoleLabel, getRoleColor } = useWorkspace()

const loading = ref(false)
const showAllProjects = ref(false)

// ✅ Utiliser directement les données du membre (déjà chargées depuis le backend)
const memberProjects = computed(() => {
  return props.member.projets || []
})

const statistics = computed(() => {
  // ✅ Les statistiques sont déjà calculées côté backend
  if (props.member.statistics) {
    return props.member.statistics
  }
  
  // Fallback si pas de statistiques
  return {
    projets_count: memberProjects.value.length || 0,
    taches_count: 0,
    taches_completees: 0,
    taux_completion: 0
  }
})

// Liste des permissions avec icônes
const permissionsList = ref([
  {
    key: 'can_create_projects',
    label: 'Créer des projets',
    description: 'Peut créer de nouveaux projets',
    icon: CubeIcon
  },
  {
    key: 'can_invite_members',
    label: 'Inviter des membres',
    description: 'Peut inviter de nouveaux membres',
    icon: UserPlusIcon
  },
  {
    key: 'can_manage_settings',
    label: 'Gérer les paramètres',
    description: 'Peut modifier les paramètres',
    icon: CogIcon
  }
])

const getInitials = (name) => {
  if (!name) return 'U'
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const getProjectRoleColor = (role) => {
  const colors = {
    manager: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    editor: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    viewer: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
  }
  return colors[role] || colors.viewer
}

const getProjectRoleLabel = (role) => {
  const labels = {
    manager: 'Gestionnaire',
    editor: 'Éditeur',
    viewer: 'Observateur'
  }
  return labels[role] || role
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const isRecentlyActive = (date) => {
  if (!date) return false
  const lastActivity = new Date(date)
  const now = new Date()
  const diffMinutes = (now - lastActivity) / (1000 * 60)
  return diffMinutes < 15
}

// ✅ CORRIGÉ : Utiliser getMemberPermissions du composable
const getPermissionValue = (permissionKey) => {
  const permissions = getMemberPermissions(props.member)
  console.log('🔍 Permission check:', {
    key: permissionKey,
    value: permissions[permissionKey],
    allPermissions: permissions
  })
  return permissions[permissionKey] || false
}

const getActiveProjectsCount = computed(() => {
  return memberProjects.value.filter(p => p.status === 'active').length
})

const getMembershipDuration = computed(() => {
  if (!props.member.pivot?.invited_at) return 'N/A'
  
  const joinDate = new Date(props.member.pivot.invited_at)
  const now = new Date()
  const diffTime = Math.abs(now - joinDate)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays < 30) {
    return `${diffDays} jour${diffDays > 1 ? 's' : ''}`
  } else if (diffDays < 365) {
    const months = Math.floor(diffDays / 30)
    return `${months} mois`
  } else {
    const years = Math.floor(diffDays / 365)
    return `${years} an${years > 1 ? 's' : ''}`
  }
})

const canEditMember = computed(() => {
  return props.member.pivot?.role !== 'owner'
})

const viewProject = (projet) => {
  router.push({ name: 'projets.show', params: { id: projet.id } })
  emit('close')
}

const editMember = () => {
  emit('edit', props.member)
  emit('close')
}

// ✅ Plus besoin de charger les données - elles sont déjà là !
onMounted(() => {
  console.log('📊 Member Details:', {
    member: props.member,
    statistics: statistics.value,
    projects: memberProjects.value,
    permissions: getMemberPermissions(props.member)
  })
})
</script>

<!-- Le template reste identique, juste quelques ajustements pour debug -->
<template>
  <Teleport to="body">
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div
        class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 max-w-2xl w-full max-h-[90vh] overflow-hidden"
        @click.stop
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
              Détails du membre
            </h2>
            <span
              v-if="member.pivot?.role === 'owner'"
              class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-full"
            >
              Propriétaire
            </span>
          </div>
          <button
            @click="$emit('close')"
            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <XIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Body -->
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
          
          <!-- Member Profile -->
          <div class="flex items-start gap-4 mb-6">
            <div class="relative">
              <div
                v-if="member.avatar"
                class="w-20 h-20 rounded-full overflow-hidden flex-shrink-0"
              >
                <img :src="member.avatar" :alt="member.nom" class="w-full h-full object-cover" />
              </div>
              <div
                v-else
                class="w-20 h-20 rounded-full bg-brand-500 flex items-center justify-center text-white text-2xl font-medium shrink-0"
              >
                {{ getInitials(member.nom) }}
              </div>
              <div
                v-if="member.is_online || isRecentlyActive(member.last_activity_at)"
                class="absolute bottom-1 right-1 w-3 h-3 bg-green-500 rounded-full border-2 border-white dark:border-gray-800"
              ></div>
            </div>
            <div class="flex-1">
              <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                {{ member.nom }}
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                {{ member.email }}
              </p>
              <div class="flex items-center gap-2 mt-3">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    getRoleColor(member.pivot?.role)
                  ]"
                >
                  {{ getRoleLabel(member.pivot?.role) }}
                </span>
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
              </div>
            </div>
          </div>

          <!-- Statistics Grid -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-brand-50 dark:bg-brand-500/10 rounded-3 p-4 text-center border border-blue-200 dark:border-blue-700/50">
              <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                {{ statistics.projets_count || 0 }}
              </div>
              <div class="text-sm text-blue-700 dark:text-blue-300 mt-1 font-medium">
                Projets
              </div>
            </div>
            <div class="bg-success-50 dark:bg-success-500/10 rounded-3 p-4 text-center border border-green-200 dark:border-green-700/50">
              <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                {{ statistics.taches_count || 0 }}
              </div>
              <div class="text-sm text-green-700 dark:text-green-300 mt-1 font-medium">
                Tâches
              </div>
            </div>
            <div class="bg-purple-50 dark:bg-purple-500/10 rounded-3 p-4 text-center border border-purple-200 dark:border-purple-700/50">
              <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                {{ statistics.taches_completees || 0 }}
              </div>
              <div class="text-sm text-purple-700 dark:text-purple-300 mt-1 font-medium">
                Complétées
              </div>
            </div>
            <div class="bg-warning-50 dark:bg-warning-500/10 rounded-3 p-4 text-center border border-orange-200 dark:border-orange-700/50">
              <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                {{ Math.round(statistics.taux_completion || 0) }}%
              </div>
              <div class="text-sm text-orange-700 dark:text-orange-300 mt-1 font-medium">
                Taux
              </div>
            </div>
          </div>

          <!-- Permissions Section -->
          <div class="mb-6">
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                Permissions du workspace
              </h4>
              <span class="text-xs text-gray-500 dark:text-gray-400">
                Rôle: {{ getRoleLabel(member.pivot?.role) }}
              </span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div
                v-for="permission in permissionsList"
                :key="permission.key"
                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600"
              >
                <div class="flex items-center gap-3">
                  <component
                    :is="permission.icon"
                    :class="[
                      'w-4 h-4',
                      getPermissionValue(permission.key) 
                        ? 'text-green-600 dark:text-green-400'
                        : 'text-gray-400 dark:text-gray-500'
                    ]"
                  />
                  <div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 block">
                      {{ permission.label }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                      {{ permission.description }}
                    </span>
                  </div>
                </div>
                <span
                  :class="[
                    'px-2 py-1 rounded-full text-xs font-medium',
                    getPermissionValue(permission.key)
                      ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                      : 'bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-300'
                  ]"
                >
                  {{ getPermissionValue(permission.key) ? 'Autorisé' : 'Non autorisé' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Projects Section -->
          <div v-if="memberProjects.length > 0">
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                Projets ({{ memberProjects.length }})
              </h4>
              <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ getActiveProjectsCount }} actifs
              </span>
            </div>
            <div class="space-y-2">
              <div
                v-for="projet in (showAllProjects ? memberProjects : memberProjects.slice(0, 5))"
                :key="projet.id"
                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600/50 transition-colors cursor-pointer group"
                @click="viewProject(projet)"
              >
                <div class="flex items-center gap-3">
                  <div
                    class="w-3 h-3 rounded-full flex-shrink-0"
                    :style="{ backgroundColor: projet.couleur || '#3B82F6' }"
                  ></div>
                  <div>
                    <span class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-brand-600 dark:group-hover:text-brand-400">
                      {{ projet.nom }}
                    </span>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      {{ projet.members_count || 0 }} membres • 
                      {{ projet.activites_count || 0 }} activités
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <span
                    :class="[
                      'px-2 py-0.5 rounded-full text-xs font-medium',
                      getProjectRoleColor(projet.pivot?.role)
                    ]"
                  >
                    {{ getProjectRoleLabel(projet.pivot?.role) }}
                  </span>
                  <ChevronRightIcon class="w-4 h-4 text-gray-400 group-hover:text-brand-600 dark:group-hover:text-brand-400" />
                </div>
              </div>
              
              <div
                v-if="memberProjects.length > 5"
                class="text-center pt-2"
              >
                <button
                  @click="showAllProjects = !showAllProjects"
                  class="text-sm text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300 font-medium"
                >
                  {{ showAllProjects ? 'Voir moins' : `Voir les ${memberProjects.length - 5} projets supplémentaires` }}
                </button>
              </div>
            </div>
          </div>

          <!-- No Projects State -->
          <div v-else class="text-center py-8 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
            <FolderOpenIcon class="mx-auto h-8 w-8 text-gray-400" />
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
              Ce membre ne participe à aucun projet
            </p>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
          <div class="text-sm text-gray-500 dark:text-gray-400">
            Membre depuis {{ getMembershipDuration }}
          </div>
          <div class="flex items-center gap-3">
            <button
              v-if="canEditMember"
              @click="editMember"
              class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors flex items-center gap-2"
            >
              <PencilIcon class="w-4 h-4" />
              Modifier
            </button>
            <button
              @click="$emit('close')"
              class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors"
            >
              Fermer
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>