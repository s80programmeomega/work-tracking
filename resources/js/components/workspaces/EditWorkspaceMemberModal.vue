<!-- resources/js/components/workspaces/EditWorkspaceMemberModal.vue -->
<template>
  <Teleport to="body">
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div
        class="bg-white dark:bg-gray-800 rounded-3 max-w-lg w-full max-h-[90vh] overflow-hidden"
        @click.stop
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-brand-100 dark:bg-brand-900/30 rounded-3">
              <UserCircleIcon class="w-6 h-6 text-brand-600 dark:text-brand-400" />
            </div>
            <div>
              <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                Modifier les permissions
              </h2>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Gérer le rôle et les permissions de {{ member.nom }}
              </p>
            </div>
          </div>
          <button
            @click="$emit('close')"
            class="p-2 text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            aria-label="Fermer"
          >
            <XIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Body -->
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
          <!-- Member Info Card -->
          <div class="flex items-center gap-4 p-4 mb-6 rounded-3 border border-gray-200 dark:border-gray-700">
            <div class="relative">
              <div
                v-if="member.avatar"
                class="w-14 h-14 rounded-full overflow-hidden ring-2 ring-white dark:ring-gray-600"
              >
                <img :src="member.avatar" :alt="member.nom" class="w-full h-full object-cover" />
              </div>
              <div
                v-else
                class="w-14 h-14 rounded-full flex items-center justify-center text-white font-bold text-lg ring-2 ring-white dark:ring-gray-600"
              >
                {{ getInitials(member.nom) }}
              </div>
              <div class="absolute -bottom-1 -right-1">
                <span :class="[
                  'px-2 py-0.5 text-xs font-medium rounded-full',
                  getRoleColor(form.role)
                ]">
                  {{ getRoleLabel(form.role) }}
                </span>
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                {{ member.nom }}
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                {{ member.email }}
              </p>
              <div class="flex items-center gap-2 mt-1">
                <span class="text-xs text-gray-500 dark:text-gray-400">
                  Membre depuis {{ formatDate(member.pivot?.created_at) }}
                </span>
              </div>
            </div>
          </div>

          <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Role Selection Section -->
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                  Rôle principal
                </h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                  Définit les permissions de base
                </span>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div
                  v-for="role in availableRoles"
                  :key="role.value"
                  @click="updateRole(role.value)"
                  :class="[
                    'p-4 border-2 rounded-3 cursor-pointer transition-all duration-200 transform hover:scale-[1.02]',
                    form.role === role.value
                      ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20 dark:border-brand-400 '
                      : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                  ]"
                >
                  <div class="flex items-center gap-3 mb-2">
                    <div
                      :class="[
                        'w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all',
                        form.role === role.value
                          ? 'border-brand-500 bg-brand-500 shadow-inner'
                          : 'border-gray-300 dark:border-gray-500'
                      ]"
                    >
                      <div
                        v-if="form.role === role.value"
                        class="w-2 h-2 bg-white rounded-full"
                      ></div>
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white">
                      {{ role.label }}
                    </span>
                  </div>
                  <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                    {{ role.description }}
                  </p>
                  <div class="flex items-center gap-1 mt-2">
                    <span
                      v-for="perm in role.permissions"
                      :key="perm"
                      class="text-xs px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400"
                      :title="perm"
                    >
                      {{ perm.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Current Role Badge -->
              <div
                v-if="member.pivot?.role !== form.role"
                class="mt-2 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-3"
              >
                <div class="flex items-center gap-2">
                  <AlertCircleIcon class="w-5 h-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0" />
                  <div>
                    <p class="text-sm font-medium text-yellow-800 dark:text-yellow-300">
                      Changement de rôle
                    </p>
                    <p class="text-xs text-yellow-700 dark:text-yellow-400 mt-1">
                      De <span :class="getRoleClass(member.pivot?.role)">{{ getRoleLabel(member.pivot?.role) }}</span>
                      à <span :class="getRoleClass(form.role)">{{ getRoleLabel(form.role) }}</span>
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Permissions Section -->
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                  Permissions détaillées
                </h3>
                <button
                  type="button"
                  @click="toggleAllPermissions"
                  class="text-xs text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 font-medium"
                >
                  {{ allPermissionsSelected ? 'Tout désélectionner' : 'Tout sélectionner' }}
                </button>
              </div>

              <!-- Permissions Grid -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div
                  v-for="permission in availablePermissions"
                  :key="permission.key"
                  :class="[
                    'p-4 rounded-3 border transition-all',
                    form.permissions[permission.key]
                      ? 'border-brand-300 dark:border-brand-700 bg-brand-50 dark:bg-brand-900/10'
                      : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                  ]"
                >
                  <div class="flex items-start gap-3">
                    <div class="flex items-center h-6">
                      <input
                        :id="permission.key"
                        v-model="form.permissions[permission.key]"
                        type="checkbox"
                        :disabled="permission.disabled || permission.required"
                        :class="[
                          'w-4 h-4 rounded transition-all',
                          permission.disabled || permission.required
                            ? 'cursor-not-allowed opacity-60'
                            : 'cursor-pointer'
                        ]"
                      />
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-2 mb-1">
                        <label
                          :for="permission.key"
                          :class="[
                            'text-sm font-medium cursor-pointer',
                            permission.disabled || permission.required
                              ? 'text-gray-500 dark:text-gray-500 cursor-not-allowed'
                              : 'text-gray-900 dark:text-white'
                          ]"
                        >
                          {{ permission.label }}
                        </label>
                        <div class="flex items-center gap-1">
                          <span
                            v-if="permission.required"
                            class="px-1.5 py-0.5 text-[10px] bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 rounded-full"
                          >
                            Obligatoire
                          </span>
                          <span
                            v-if="permission.recommended"
                            class="px-1.5 py-0.5 text-[10px] bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full"
                          >
                            Recommandé
                          </span>
                        </div>
                      </div>
                      <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                        {{ permission.description }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Permission Summary -->
              <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700/30 rounded-3">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-700 dark:text-gray-300">
                    Permissions activées :
                  </span>
                  <span class="text-sm font-medium text-brand-600 dark:text-brand-400">
                    {{ enabledPermissionsCount }} / {{ availablePermissions.length }}
                  </span>
                </div>
                <div class="mt-2 flex flex-wrap gap-1">
                  <span
                    v-for="permission in enabledPermissions"
                    :key="permission.key"
                    class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-brand-100 dark:bg-brand-900/30 text-brand-700 dark:text-brand-300 rounded-full"
                  >
                    <CheckCircleIcon class="w-3 h-3" />
                    {{ permission.shortLabel }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Warning for permission conflicts -->
            <div
              v-if="permissionConflicts.length > 0"
              class="p-4 rounded-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800"
            >
              <div class="flex items-start gap-3">
                <AlertCircleIcon class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" />
                <div>
                  <h4 class="text-sm font-medium text-red-800 dark:text-red-400 mb-1">
                    Conflits de permissions détectés
                  </h4>
                  <ul class="text-xs text-red-700 dark:text-red-300 space-y-1">
                    <li v-for="conflict in permissionConflicts" :key="conflict">
                      • {{ conflict }}
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- Success/Error Messages -->
            <div
              v-if="successMessage"
              class="p-4 rounded-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800"
            >
              <div class="flex items-center gap-2">
                <CheckCircleIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
                <p class="text-sm text-green-800 dark:text-green-400">
                  {{ successMessage }}
                </p>
              </div>
            </div>

            <div
              v-if="error"
              class="p-4 rounded-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800"
            >
              <div class="flex items-center gap-2">
                <AlertCircleIcon class="w-5 h-5 text-red-600 dark:text-red-400" />
                <p class="text-sm text-red-800 dark:text-red-400">
                  {{ error }}
                </p>
              </div>
            </div>
          </form>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
          <!-- <div class="text-sm text-gray-600 dark:text-gray-400">
            <div class="flex items-center gap-2">
              <div class="flex items-center">
                <div class="w-2 h-2 rounded-full bg-green-500 mr-1"></div>
                <span>{{ enabledPermissionsCount }} permissions</span>
              </div>
              <span>•</span>
              <span>{{ getRoleLabel(form.role) }}</span>
            </div>
          </div> -->
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-50"
            >
              Annuler
            </button>
            <button
              @click="handleSubmit"
              :disabled="submitting || !isFormValid || permissionConflicts.length > 0"
              :class="[
                'px-6 py-2 rounded-3 transition-all focus:outline-none focus:ring-2 focus:ring-offset-2',
                submitting || !isFormValid || permissionConflicts.length > 0
                  ? 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                  : 'bg-brand-600 hover:bg-brand-700 text-white focus:ring-brand-500 hover:shadow'
              ]"
            >
              <div class="flex items-center gap-2">
                <CheckCircleIcon v-if="!submitting" class="w-4 h-4" />
                <div v-else class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                {{ submitting ? 'Mise à jour...' : 'Confirmer les modifications' }}
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useWorkspace } from '@/composables/useWorkspace'
import { useInvitationPermissions } from '@/composables/useInvitationPermissions'
import { 
  XIcon, 
  AlertCircleIcon,
  CheckCircleIcon,
  UserCircleIcon
} from '@/icons'

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

const emit = defineEmits(['close', 'updated'])

const { updateMember, getRoleLabel, getRoleColor } = useWorkspace()

// Initialize permissions
const workspace = ref({ id: props.workspaceId })
const invitationPermissions = useInvitationPermissions(workspace)

const submitting = ref(false)
const error = ref(null)
const successMessage = ref(null)

// Available roles with detailed info
const availableRoles = [
  {
    value: 'manager',
    label: 'Manager',
    description: 'Gestion complète du workspace',
    permissions: ['Créer projets', 'Gérer membres', 'Modifier paramètres']
  },
  {
    value: 'cadre',
    label: 'Cadre',
    description: 'Participation active aux projets',
    permissions: ['Créer projets', 'Voir projets']
  },
  {
    value: 'collaborateur',
    label: 'Collaborateur',
    description: 'Participation aux tâches assignées',
    permissions: ['Voir projets', 'Soumettre résultats']
  },
  {
    value: 'stagiaire',
    label: 'Stagiaire',
    description: 'Participation aux tâches assignées',
    permissions: ['Voir projets', 'Soumettre résultats']
  },
  {
    value: 'observateur',
    label: 'Observateur',
    description: 'Accès en lecture seule',
    permissions: ['Voir projets']
  }
]

const form = ref({
  role: '',
  permissions: {
    can_create_projects: false,
    can_view_all_projects: false,
    can_invite_members: false,
    can_delete_members: false,
    can_manage_settings: false,
    can_transfer_ownership: false
  }
})

// Computed properties
const availablePermissions = computed(() => {
  const permissions = [
    {
      key: 'can_create_projects',
      label: 'Créer des projets',
      shortLabel: 'Créer',
      description: 'Autoriser la création de nouveaux projets dans le workspace',
      recommended: true,
      required: form.value.role === 'manager'
    },
    {
      key: 'can_view_all_projects',
      label: 'Voir tous les projets',
      shortLabel: 'Voir tout',
      description: 'Visualiser tous les projets du workspace',
      recommended: false,
      required: false
    },
    {
      key: 'can_invite_members',
      label: 'Inviter des membres',
      shortLabel: 'Inviter',
      description: 'Autoriser l\'invitation de nouveaux membres',
      recommended: false,
      required: false,
      disabled: form.value.role === 'viewer'
    },
    {
      key: 'can_delete_members',
      label: 'Supprimer des membres',
      shortLabel: 'Supprimer',
      description: 'Autoriser la suppression de membres du workspace',
      recommended: false,
      required: false,
      disabled: form.value.role === 'viewer'
    },
    {
      key: 'can_manage_settings',
      label: 'Gérer les paramètres',
      shortLabel: 'Paramètres',
      description: 'Modifier les paramètres du workspace',
      recommended: false,
      required: false,
      disabled: form.value.role === 'viewer'
    },
    {
      key: 'can_transfer_ownership',
      label: 'Transférer la propriété',
      shortLabel: 'Transférer',
      description: 'Transférer la propriété du workspace',
      recommended: false,
      required: false,
      disabled: form.value.role !== 'owner'
    }
  ]

  return permissions.map(perm => ({
    ...perm,
    disabled: perm.disabled || (form.value.role === 'viewer' && 
              ['can_create_projects', 'can_invite_members', 'can_delete_members', 'can_manage_settings'].includes(perm.key))
  }))
})

const enabledPermissions = computed(() => {
  return availablePermissions.value.filter(perm => form.value.permissions[perm.key])
})

const enabledPermissionsCount = computed(() => enabledPermissions.value.length)

const allPermissionsSelected = computed(() => {
  return availablePermissions.value.every(perm => 
    perm.required || form.value.permissions[perm.key]
  )
})

const permissionConflicts = computed(() => {
  const conflicts = []
  
  // Viewer cannot have creation permissions
  if (form.value.role === 'viewer' && form.value.permissions.can_create_projects) {
    conflicts.push('Un viewer ne peut pas créer de projets')
  }
  
  // Viewer cannot have management permissions
  if (form.value.role === 'viewer' && 
      (form.value.permissions.can_invite_members || 
       form.value.permissions.can_delete_members || 
       form.value.permissions.can_manage_settings)) {
    conflicts.push('Un viewer ne peut pas gérer le workspace')
  }
  
  // Member cannot transfer ownership
  if (form.value.role === 'member' && form.value.permissions.can_transfer_ownership) {
    conflicts.push('Un membre ne peut pas transférer la propriété')
  }

  return conflicts
})

const isFormValid = computed(() => {
  return form.value.role && enabledPermissionsCount.value > 0 && permissionConflicts.value.length === 0
})

// Methods
const getInitials = (name) => {
  if (!name) return 'U'
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const formatDate = (dateString) => {
  if (!dateString) return 'récemment'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

const getRoleClass = (role) => {
  const classes = {
    owner: 'text-purple-600 dark:text-purple-400 font-medium',
    admin: 'text-blue-600 dark:text-blue-400 font-medium',
    member: 'text-green-600 dark:text-green-400 font-medium',
    viewer: 'text-gray-600 dark:text-gray-400 font-medium'
  }
  return classes[role] || 'text-gray-600 dark:text-gray-400'
}

const updateRole = (newRole) => {
  form.value.role = newRole
  
  // Apply default permissions for the role
  const defaultPermissions = invitationPermissions.getDefaultPermissionsForRole(newRole)
  form.value.permissions = { ...defaultPermissions }
}

const toggleAllPermissions = () => {
  const newState = !allPermissionsSelected.value
  availablePermissions.value.forEach(permission => {
    if (!permission.required && !permission.disabled) {
      form.value.permissions[permission.key] = newState
    }
  })
}

// Parse member permissions from pivot
const parseMemberPermissions = (pivot) => {
  if (!pivot || !pivot.permissions) return {}
  
  let permissions = pivot.permissions
  
  // Handle string JSON
  if (typeof permissions === 'string') {
    try {
      permissions = JSON.parse(permissions)
    } catch {
      permissions = {}
    }
  }
  
  // Handle 'all' permissions
  if (permissions === 'all' || (Array.isArray(permissions) && permissions[0] === 'all')) {
    return {
      can_create_projects: true,
      can_view_all_projects: true,
      can_invite_members: true,
      can_delete_members: true,
      can_manage_settings: true,
      can_transfer_ownership: true
    }
  }
  
  return {
    can_create_projects: permissions.can_create_projects || false,
    can_view_all_projects: permissions.can_view_all_projects || false,
    can_invite_members: permissions.can_invite_members || false,
    can_delete_members: permissions.can_delete_members || false,
    can_manage_settings: permissions.can_manage_settings || false,
    can_transfer_ownership: permissions.can_transfer_ownership || false
  }
}

const handleSubmit = async () => {
  try {
    submitting.value = true
    error.value = null
    successMessage.value = null

    // Validate permissions
    const validation = invitationPermissions.validatePermissions(form.value.permissions, form.value.role)
    if (!validation.isValid) {
      error.value = validation.errors.join(', ')
      submitting.value = false
      return
    }

    // Prepare update data
    const updateData = {
      role: form.value.role,
      permissions: form.value.permissions
    }

    console.log('Updating member with:', updateData)

    // Make API call
    const response = await updateMember(props.workspaceId, props.member.id, updateData)

    // Show success message
    successMessage.value = `Permissions de ${props.member.nom} mises à jour avec succès!`

    // Close modal after delay
    setTimeout(() => {
      emit('updated')
      emit('close')
    }, 1500)

  } catch (err) {
    console.error('Error updating member:', err)
    error.value = err.response?.data?.message || 
                  err.response?.data?.errors?.permissions?.join(', ') ||
                  'Une erreur est survenue lors de la mise à jour'
  } finally {
    submitting.value = false
  }
}

// Initialize form with current member data
onMounted(() => {
  // Set role
  form.value.role = props.member.pivot?.role || 'member'
  
  // Parse and set permissions
  const currentPermissions = parseMemberPermissions(props.member.pivot)
  form.value.permissions = { ...currentPermissions }
  
  console.log('Initialized form:', {
    role: form.value.role,
    permissions: form.value.permissions,
    memberPivot: props.member.pivot
  })
})

// Watch for role changes to update permissions
watch(() => form.value.role, (newRole) => {
  console.log('Role changed to:', newRole)
}, { immediate: true })
</script>

<style scoped>
/* Custom scrollbar */
.scrollbar-custom {
  scrollbar-width: thin;
  scrollbar-color: #e5e7eb transparent;
}

.scrollbar-custom::-webkit-scrollbar {
  width: 6px;
}

.scrollbar-custom::-webkit-scrollbar-track {
  background: transparent;
}

.scrollbar-custom::-webkit-scrollbar-thumb {
  background-color: #e5e7eb;
  border-radius: 3px;
}

.dark .scrollbar-custom::-webkit-scrollbar-thumb {
  background-color: #4b5563;
}
</style>