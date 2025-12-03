<template>
  <Teleport to="body">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div
        class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-lg w-full"
        @click.stop
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
            Modifier les permissions
          </h2>
          <button
            @click="$emit('close')"
            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <XIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Body -->
        <div class="p-6">
          <!-- Member Info -->
          <div class="flex items-center gap-3 mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <div
              v-if="membre.avatar"
              class="w-12 h-12 rounded-full overflow-hidden flex-shrink-0"
            >
              <img :src="membre.avatar" :alt="membre.nom" class="w-full h-full object-cover" />
            </div>
            <div
              v-else
              class="w-12 h-12 rounded-full bg-brand-600 flex items-center justify-center text-white font-medium flex-shrink-0"
            >
              {{ getInitials(membre.nom) }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                {{ membre.nom }}
                <span v-if="membre.id === projetResponsableId" class="ml-1 text-purple-600 text-xs">★ Responsable</span>
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                {{ membre.email }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Rôle actuel: <span :class="getRoleBadgeClass(membre.role)">{{ getRoleLabel(membre.role) }}</span>
              </div>
            </div>
          </div>

          <!-- Avertissement pour le responsable -->
          <div v-if="membre.id === projetResponsableId" class="mb-4 p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg">
            <p class="text-xs text-purple-800 dark:text-purple-300">
              ⭐ Cet utilisateur est le responsable du projet. Les permissions ne peuvent pas être modifiées.
            </p>
          </div>

          <form @submit.prevent="handleSubmit" class="space-y-6" v-else>
            <!-- Role Selection -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Rôle <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.role"
                required
                @change="handleRoleChange"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent"
              >
                <option value="admin">Administrateur</option>
                <option value="member">Membre</option>
                <option value="viewer">Observateur</option>
              </select>
            </div>

            <!-- Permissions -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                Permissions spécifiques
              </label>
              <div class="space-y-3">
                <div class="flex items-start gap-3">
                  <input
                    v-model="form.can_edit"
                    :disabled="form.role === 'viewer'"
                    type="checkbox"
                    id="edit_can_edit"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                  />
                  <div>
                    <label for="edit_can_edit" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Peut modifier le projet
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      Permet de modifier les informations du projet
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <input
                    v-model="form.can_delete"
                    :disabled="form.role === 'member' || form.role === 'viewer'"
                    type="checkbox"
                    id="edit_can_delete"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                  />
                  <div>
                    <label for="edit_can_delete" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Peut supprimer le projet
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      Permet de supprimer un projet
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <input
                    v-model="form.can_invite"
                    :disabled="form.role === 'member' || form.role === 'viewer'"
                    type="checkbox"
                    id="edit_can_invite"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                  />
                  <div>
                    <label for="edit_can_invite" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Peut inviter des membres
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      Permet d'ajouter des membres au projet
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <input
                    v-model="form.can_delete_member"
                    :disabled="form.role === 'member' || form.role === 'viewer'"
                    type="checkbox"
                    id="can_delete_member"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                  />
                  <div>
                    <label for="can_delete_member" class="text-sm font-medium text-orange-700 dark:text-orange-300">
                      Peut supprimer des membres
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      Permet de retirer des membres du projet
                    </p>
                  </div>
                </div>
              </div>

              <!-- Avertissement pour le rôle membre -->
              <div v-if="form.role === 'member'" class="mt-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <p class="text-xs text-yellow-800 dark:text-yellow-300">
                  ⚠️ Les membres ne peuvent pas avoir les permissions de suppression ou d'invitation
                </p>
              </div>
            </div>

            <!-- Error Message -->
            <div
              v-if="error"
              class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800"
            >
              <p class="text-sm text-red-800 dark:text-red-400">
                {{ error }}
              </p>
            </div>
          </form>

          <!-- Message si c'est le responsable -->
          <div v-if="membre.id === projetResponsableId" class="text-center py-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Le responsable du projet a automatiquement toutes les permissions.
            </p>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
          >
            Annuler
          </button>
          <button
            v-if="membre.id !== projetResponsableId"
            @click="handleSubmit"
            :disabled="submitting"
            class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
          >
            <span v-if="submitting" class="animate-spin">⏳</span>
            Mettre à jour
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import { useProjets } from '@/composables/useProjets'
import { XIcon } from '@/icons'

const props = defineProps({
  membre: {
    type: Object,
    required: true
  },
  projetId: {
    type: Number,
    required: true
  },
  projetResponsableId: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['close', 'updated'])

const { updateMember } = useProjets()

const submitting = ref(false)
const error = ref(null)

const form = ref({
  role: '',
  can_edit: false,
  can_delete: false,
  can_invite: false,
  can_delete_member: false,
})

// ✅ Gestion intelligente des permissions basées sur le rôle
const handleRoleChange = () => {
  const role = form.value.role
  
  if (role === 'admin') {
    form.value.can_edit = true
    form.value.can_delete = true
    form.value.can_invite = true
    form.value.can_delete_member = true
  } else if (role === 'member') {
    form.value.can_edit = true
    form.value.can_delete = false
    form.value.can_invite = false
    form.value.can_delete_member = false
  } else if (role === 'viewer') {
    form.value.can_edit = false
    form.value.can_delete = false
    form.value.can_invite = false
    form.value.can_delete_member = false
  }
}

// ✅ Empêcher la modification manuelle des permissions pour les rôles restreints
watch(() => form.value.role, (newRole) => {
  if (newRole === 'member' || newRole === 'viewer') {
    if (form.value.can_delete) form.value.can_delete = false
    if (form.value.can_invite) form.value.can_invite = false
    if (form.value.can_delete_member) form.value.can_delete_member = false
  }
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

const getRoleLabel = (role) => {
  const labels = {
    owner: 'Propriétaire',
    admin: 'Administrateur',
    member: 'Membre',
    viewer: 'Observateur'
  }
  return labels[role] || role
}

const getRoleBadgeClass = (role) => {
  const classes = {
    owner: 'text-purple-800 bg-purple-100 dark:bg-purple-900/30 dark:text-purple-300',
    admin: 'text-blue-800 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300',
    member: 'text-green-800 bg-green-100 dark:bg-green-900/30 dark:text-green-300',
    viewer: 'text-gray-800 bg-gray-100 dark:bg-gray-700 dark:text-gray-300'
  }
  return classes[role] || classes.viewer
}

const handleSubmit = async () => {
  try {
    submitting.value = true
    error.value = null

    // ✅ Validation des permissions selon le rôle
    if (form.value.role === 'member' && (form.value.can_delete || form.value.can_invite || form.value.can_delete_member)) {
      error.value = 'Les membres ne peuvent pas avoir les permissions de suppression ou d\'invitation'
      return
    }

    if (form.value.role === 'viewer' && (form.value.can_edit || form.value.can_delete || form.value.can_invite || form.value.can_delete_member)) {
      error.value = 'Les observateurs ne peuvent avoir aucune permission'
      return
    }

    await updateMember(props.projetId, props.membre.id, form.value)
    emit('updated')
  } catch (err) {
    error.value = err.response?.data?.message || 'Une erreur est survenue'
    console.error('Error updating member:', err)
  } finally {
    submitting.value = false
  }
}

// Initialize form with current member data
onMounted(() => {
  form.value = {
    role: props.membre.role || 'member',
    can_edit: props.membre.can_edit || false,
    can_delete: props.membre.can_delete || false,
    can_invite: props.membre.can_invite || false,
    can_delete_member: props.membre.can_delete_member || false
  }
})
</script>