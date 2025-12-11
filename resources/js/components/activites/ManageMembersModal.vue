<!-- resources/js/components/activites/ManageMembersModal.vue -->
<template>
  <TransitionRoot :show="true" appear>
    <Dialog as="div" class="relative z-50" @close="$emit('close')">
      <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100"
        leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100" leave="ease-in duration-200" leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95">
            <DialogPanel
              class="w-full max-w-4xl transform overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-xl transition-all">
              <!-- Header -->
              <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <div class="flex items-center space-x-3">
                  <DialogTitle class="text-lg font-semibold text-gray-900 dark:text-white">
                    Gérer les membres - {{ activite.nom }}
                  </DialogTitle>
                  <span v-if="currentUserPermissions" class="text-sm text-gray-500 dark:text-gray-400">
                    (Vous pouvez {{ getUserPermissionSummary(currentUserPermissions) }})
                  </span>
                </div>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Body -->
              <div class="p-6 max-h-[70vh] overflow-y-auto">
                <!-- En-tête des permissions -->
                <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                  <div class="flex items-center justify-between">
                    <div>
                      <h3 class="font-medium text-gray-900 dark:text-white mb-1">Légende des permissions</h3>
                      <div class="flex flex-wrap gap-2">
                        <div v-for="permission in availablePermissions" :key="permission.key" 
                             class="flex items-center space-x-1">
                          <span class="w-3 h-3 rounded" :class="permission.color"></span>
                          <span class="text-xs text-gray-600 dark:text-gray-400">{{ permission.label }}</span>
                        </div>
                      </div>
                    </div>
                    <div v-if="isSuperAdminOrResponsable" class="text-sm">
                      <button @click="showAllPermissions = !showAllPermissions" 
                              class="text-blue-600 dark:text-blue-400 hover:underline">
                        {{ showAllPermissions ? 'Masquer détails' : 'Afficher détails' }}
                      </button>
                    </div>
                  </div>
                  
                  <!-- Détails des permissions (optionnel) -->
                  <div v-if="showAllPermissions" class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    <p><strong>Responsable :</strong> Accès complet, peut gérer tous les membres et permissions</p>
                    <p><strong>Créer tâches :</strong> Peut créer de nouvelles tâches</p>
                    <p><strong>Modifier tâches :</strong> Peut modifier les tâches existantes</p>
                    <p><strong>Valider N1 :</strong> Peut valider les résultats de niveau 1</p>
                    <p><strong>Assigner membres :</strong> Peut ajouter/retirer des membres (nécessite aussi "Supprimer des membres" pour retirer)</p>
                    <p><strong>Supprimer des membres :</strong> Peut retirer des membres de l'activité</p>
                  </div>
                </div>

                <!-- Liste des membres -->
                <div class="space-y-4">
                  <div v-for="member in sortedMembers" :key="member.id"
                    class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors"
                    :class="getMemberCardClasses(member)">
                    
                    <!-- Avatar et info -->
                    <div class="flex items-center space-x-3 flex-1 min-w-0">
                      <div class="relative">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center text-white text-sm font-bold"
                          :class="getAvatarClasses(member)">
                          {{ getInitials(member.nom) }}
                        </div>
                        <!-- Badge responsable -->
                        <span v-if="member.id === activite.responsable_id"
                          class="absolute -top-1 -right-1 inline-flex items-center justify-center w-6 h-6 rounded-full bg-orange-500 text-white text-xs">
                          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                          </svg>
                        </span>
                      </div>

                      <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-2">
                          <span class="font-medium text-gray-900 dark:text-white truncate">
                            {{ member.nom }}
                            <span v-if="member.id === currentUserId" class="text-sm text-blue-600 dark:text-blue-400">
                              (Vous)
                            </span>
                          </span>
                        </div>

                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                          {{ member.email }}
                        </p>

                        <!-- Permissions visuelles améliorées -->
                        <div class="mt-2">
                          <div class="flex flex-wrap gap-1 mb-1">
                            <template v-for="permission in availablePermissions" :key="permission.key">
                              <span v-if="hasPermission(member, permission.key)"
                                :class="getPermissionBadgeClasses(permission, member)"
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium transition-all duration-200">
                                <span class="w-1.5 h-1.5 rounded-full mr-1" :class="permission.color"></span>
                                {{ permission.shortLabel || permission.label }}
                              </span>
                            </template>
                          </div>
                          
                          <!-- Résumé des permissions -->
                          <div v-if="showPermissionSummary" class="text-xs text-gray-500 dark:text-gray-400">
                            <span v-if="member.id === activite.responsable_id" class="font-medium">Responsable complet</span>
                            <span v-else>{{ countMemberPermissions(member) }} permission(s)</span>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center space-x-2 ml-4 flex-shrink-0">
                      <!-- Éditer permissions -->
                      <button v-if="canEditMemberPermissions(member)" 
                        @click="editMember(member)"
                        :disabled="isProcessing"
                        :class="getActionButtonClasses('edit', member)"
                        :title="getEditButtonTooltip(member)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>

                      <!-- Changer responsable -->
                      <button v-if="canChangeResponsable(member)" 
                        @click="promoteToResponsable(member)"
                        :disabled="isProcessing"
                        :class="getActionButtonClasses('promote', member)"
                        title="Définir comme responsable">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </button>

                      <!-- Retirer membre -->
                      <button v-if="canRemoveMember(member)" 
                        @click="removeMember(member)" :disabled="isProcessing" :class="getActionButtonClasses('remove', member)"
                        :title="getRemoveButtonTooltip(member)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>

                      <!-- Indicateur de traitement -->
                      <span v-if="isProcessingMember(member.id)" class="text-blue-600 dark:text-blue-400">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                        </svg>
                      </span>
                    </div>
                  </div>

                  <!-- État vide -->
                  <div v-if="members.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor"
                      viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <p class="font-medium">Aucun membre</p>
                    <p class="text-sm mt-1">Ajoutez des membres pour collaborer</p>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                <div class="flex items-center space-x-4">
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ members.length }} membre(s) • {{ activeMembersCount }} actif(s)
                  </p>
                  <div v-if="currentUserPermissions" class="text-xs text-gray-400 dark:text-gray-500">
                    Vos droits : {{ getUserPermissionSummary(currentUserPermissions) }}
                  </div>
                </div>
                <div class="flex items-center space-x-3">
                  <button @click="$emit('close')"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Fermer
                  </button>
                  <button v-if="currentUserPermissions?.can_assign_users" 
                    @click="openAddMemberModal"
                    :disabled="isProcessing"
                    class="px-4 py-2 bg-blue-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <span class="flex items-center">
                      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                      </svg>
                      Ajouter un membre
                    </span>
                  </button>
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue'
import api from '@/api/axios'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  activite: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'updated', 'edit-member', 'add-member', 'change-responsable'])

const authStore = useAuthStore()
const members = ref([])
const loading = ref(false)
const isProcessing = ref(false)
const processingMembers = ref([])
const showAllPermissions = ref(false)
const showPermissionSummary = ref(true)

// Permissions disponibles avec métadonnées
const availablePermissions = ref([
  { 
    key: 'can_create_tasks', 
    label: 'Créer tâches', 
    shortLabel: 'Créer',
    color: 'bg-green-500',
    badge: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300'
  },
  { 
    key: 'can_edit_tasks', 
    label: 'Modifier tâches', 
    shortLabel: 'Modifier',
    color: 'bg-blue-500',
    badge: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300'
  },
  { 
    key: 'can_delete_tasks', 
    label: 'Supprimer tâches', 
    shortLabel: 'Supprimer tâches',
    color: 'bg-red-500',
    badge: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300'
  },
  { 
    key: 'can_validate_results', 
    label: 'Valider N1', 
    shortLabel: 'Valider',
    color: 'bg-purple-500',
    badge: 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-300'
  },
  { 
    key: 'can_assign_users', 
    label: 'Assigner membres', 
    shortLabel: 'Assigner',
    color: 'bg-yellow-500',
    badge: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300'
  },
  { 
    key: 'can_delete_member', 
    label: 'Supprimer des membres', 
    shortLabel: 'Retirer membres',
    color: 'bg-orange-500',
    badge: 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-300'
  }
])

// Méthodes pour les classes CSS
const getAvatarClasses = (member) => {
  const base = "rounded-full w-12 h-12 flex items-center justify-center font-bold text-white"

  if (member.id === props.activite.responsable_id) {
    return base + " bg-orange-600"
  }

  if (member.id === currentUserId.value) {
    return base + " bg-blue-600"
  }

  return base + " bg-gray-500"
}

const getPermissionBadgeClasses = (permission, member) => {
  return permission.badge || "bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-300"
}

const getActionButtonClasses = (action, member) => {
  const base = "p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
  
  switch(action) {
    case 'edit':
      return base + " text-blue-600 dark:text-blue-400"
    case 'promote':
      return base + " text-green-600 dark:text-green-400"
    case 'remove':
      return base + " text-red-600 dark:text-red-400"
    default:
      return base + " text-gray-600 dark:text-gray-400"
  }
}

const getMemberCardClasses = (member) => {
  const classes = []

  if (member.id === props.activite.responsable_id) {
    classes.push("border-orange-400 bg-orange-50 dark:bg-orange-900/10 dark:border-orange-700")
  }

  if (member.id === currentUserId.value) {
    classes.push("border-blue-400 bg-blue-50 dark:bg-blue-900/10 dark:border-blue-700")
  }

  // Vérifier si le membre a des permissions
  const permissions = getMemberPermissions(member)
  const hasAnyPermission = Object.values(permissions).some(v => v)
  if (!hasAnyPermission && member.id !== props.activite.responsable_id) {
    classes.push("opacity-70")
  }

  return classes.join(" ")
}

// Données utilisateur courant
const currentUserId = computed(() => authStore.user?.id || null)
const projetResponsableId = ref(null)

// Permissions de l'utilisateur courant
const currentUserPermissions = computed(() => {
  if (!currentUserId.value || members.value.length === 0) return null

  const currentMember = members.value.find(m => m.id === currentUserId.value)
  if (!currentMember) return null

  return {
    can_create_tasks: !!currentMember.can_create_tasks,
    can_edit_tasks: !!currentMember.can_edit_tasks,
    can_delete_tasks: !!currentMember.can_delete_tasks,
    can_validate_results: !!currentMember.can_validate_results,
    can_assign_users: !!currentMember.can_assign_users,
    can_delete_member: !!currentMember.can_delete_member
  }
})

// Permissions d'un membre
const getMemberPermissions = (member) => {
  return {
    can_create_tasks: !!member.can_create_tasks,
    can_edit_tasks: !!member.can_edit_tasks,
    can_delete_tasks: !!member.can_delete_tasks,
    can_validate_results: !!member.can_validate_results,
    can_assign_users: !!member.can_assign_users,
    can_delete_member: !!member.can_delete_member
  }
}

// Vérifier si un membre a une permission spécifique
const hasPermission = (member, permissionKey) => {
  const permissions = getMemberPermissions(member)
  return !!permissions[permissionKey]
}

// Calculs
const sortedMembers = computed(() => {
  return [...members.value].sort((a, b) => {
    if (a.id === props.activite.responsable_id) return -1
    if (b.id === props.activite.responsable_id) return 1
    
    if (a.id === currentUserId.value) return -1
    if (b.id === currentUserId.value) return 1
    
    const aPerms = countMemberPermissions(a)
    const bPerms = countMemberPermissions(b)
    return bPerms - aPerms
  })
})

const activeMembersCount = computed(() => {
  return members.value.filter(m => countMemberPermissions(m) > 0).length
})

const isSuperAdminOrResponsable = computed(() => {
  const isSuperAdmin = authStore.user?.is_super_admin || false
  const isResponsable = currentUserId.value === props.activite.responsable_id
  return isSuperAdmin || isResponsable
})

// Méthodes utilitaires
const getInitials = (name) => {
  if (!name) return '??'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const countMemberPermissions = (member) => {
  const permissions = getMemberPermissions(member)
  return Object.values(permissions).filter(Boolean).length
}

const getUserPermissionSummary = (permissions) => {
  if (!permissions) return 'Aucun droit'
  
  const activePerms = availablePermissions.value
    .filter(p => permissions[p.key])
    .map(p => p.label.toLowerCase())
  
  if (activePerms.length === 0) return 'Lecture seule'
  if (activePerms.length <= 2) return activePerms.join(' et ')
  
  return `${activePerms.slice(0, 2).join(', ')}...`
}

// Logique de permissions
const canEditMemberPermissions = (member) => {
  if (member.id === currentUserId.value || member.id === props.activite.responsable_id) {
    return false
  }
  
  return currentUserPermissions.value?.can_assign_users === true
}

const canChangeResponsable = (member) => {
  if (!isSuperAdminOrResponsable.value) return false
  
  if (member.id === props.activite.responsable_id) return false
  
  return members.value.some(m => m.id === member.id)
}

// Ajouter cette méthode pour vérifier si un membre est responsable du projet
const isProjetResponsable = (member) => {
  return projetResponsableId.value && member.id === projetResponsableId.value
}

// Modifier la méthode canRemoveMember
const canRemoveMember = (member) => {
  // Ne pas pouvoir se retirer soi-même
  if (member.id === currentUserId.value) {
    return false
  }
  
  // Ne pas pouvoir retirer le responsable de l'activité
  if (member.id === props.activite.responsable_id) {
    return false
  }
  
  // NE PAS POUVOIR RETIRER LE RESPONSABLE DU PROJET <-- AJOUT
  if (isProjetResponsable(member)) {
    return false
  }
  
  // Vérifier les permissions nécessaires
  const hasDeletePermission = currentUserPermissions.value?.can_delete_member === true
  const hasAssignPermission = currentUserPermissions.value?.can_assign_users === true
  
  // Besoin des deux permissions pour retirer un membre
  return hasDeletePermission && hasAssignPermission
}



const getEditButtonTooltip = (member) => {
  if (member.id === currentUserId.value) return 'Vous ne pouvez pas modifier vos propres permissions'
  if (member.id === props.activite.responsable_id) return 'Le responsable a tous les droits'
  if (!currentUserPermissions.value?.can_assign_users) return 'Vous n\'avez pas le droit de modifier les permissions'
  return 'Modifier les permissions'
}

const getRemoveButtonTooltip = (member) => {
  if (member.id === currentUserId.value) return 'Vous ne pouvez pas vous retirer vous-même'
  if (member.id === props.activite.responsable_id) return 'Le responsable ne peut pas être retiré'
  if (!currentUserPermissions.value?.can_delete_member) return 'Vous n\'avez pas le droit de retirer des membres'
  if (!currentUserPermissions.value?.can_assign_users) return 'Vous n\'avez pas le droit de gérer les membres'
  return 'Retirer le membre'
}

const isProcessingMember = (memberId) => {
  return processingMembers.value.includes(memberId)
}

// Méthodes d'action
const editMember = (member) => {
  if (!canEditMemberPermissions(member)) return
  emit('edit-member', member)
}

const promoteToResponsable = async (member) => {
  if (!canChangeResponsable(member) || !confirm(`Définir ${member.nom} comme responsable ? Le responsable actuel perdra ses droits.`)) {
    return
  }

  isProcessing.value = true
  processingMembers.value.push(member.id)

  try {
    const response = await api.put(`/activites/${props.activite.id}/change-responsable`, {
      new_responsable_id: member.id
    })
    
    Object.assign(props.activite, response.data.data)
    await loadMembers()
    
    emit('updated', response.data.data)
    emit('change-responsable', member)
    
  } catch (error) {
    console.error('Erreur lors du changement de responsable:', error)
    alert(error.response?.data?.message || 'Erreur lors du changement de responsable')
  } finally {
    isProcessing.value = false
    processingMembers.value = processingMembers.value.filter(id => id !== member.id)
  }
}

const removeMember = async (member) => {
  if (!canRemoveMember(member) || !confirm(`Êtes-vous sûr de vouloir retirer ${member.nom} ?`)) {
    return
  }

  isProcessing.value = true
  processingMembers.value.push(member.id)

  try {
    await api.delete(`/activites/${props.activite.id}/members/${member.id}`)
    await loadMembers()
    emit('updated')
  } catch (error) {
    console.error('Erreur lors du retrait du membre:', error)
    alert(error.response?.data?.message || 'Erreur lors du retrait du membre')
  } finally {
    isProcessing.value = false
    processingMembers.value = processingMembers.value.filter(id => id !== member.id)
  }
}

const openAddMemberModal = () => {
  if (!currentUserPermissions.value?.can_assign_users) {
    alert('Vous n\'avez pas le droit d\'ajouter des membres')
    return
  }
  emit('add-member')
}

const loadMembers = async () => {
  loading.value = true

  try {
    const response = await api.get(`/activites/${props.activite.id}/members`)
    members.value = response.data.data || []
    
    console.log('Membres chargés:', members.value.map(m => ({
      nom: m.nom,
      permissions: getMemberPermissions(m)
    })))
    
  } catch (error) {
    console.error('Erreur lors du chargement des membres:', error)
    members.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadMembers()
})
</script>