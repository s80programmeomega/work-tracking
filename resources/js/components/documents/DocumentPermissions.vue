<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Gestion des permissions
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Contrôlez qui peut accéder à ce document
        </p>
      </div>

      <button
        @click="showAddUser = true"
        class="inline-flex items-center gap-2 rounded-3 bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
      >
        <UserPlusIcon class="h-5 w-5" />
        Ajouter un utilisateur
      </button>
    </div>

    <!-- Current Permissions List -->
    <div v-if="loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-600 border-t-transparent"></div>
    </div>

    <div v-else-if="permissions.length === 0" class="rounded-3 border-2 border-dashed border-gray-300 bg-gray-50 p-8 text-center dark:border-gray-700 dark:bg-gray-900/50">
      <UserGroupIcon class="mx-auto h-12 w-12 text-gray-400" />
      <p class="mt-3 text-sm font-medium text-gray-900 dark:text-white">
        Aucune permission configurée
      </p>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Commencez par ajouter des utilisateurs
      </p>
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="permission in permissions"
        :key="permission.id"
        class="flex items-center justify-between rounded-3 border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]"
      >
        <!-- User Info -->
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-sm font-medium text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
            {{ getInitials(permission.permissionable.name) }}
          </div>
          <div>
            <p class="text-sm font-medium text-gray-900 dark:text-white">
              {{ permission.permissionable.name }}
            </p>
            <div class="mt-1 flex flex-wrap gap-2">
              <span
                v-if="permission.can_view"
                class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400"
              >
                <EyeIcon class="h-3 w-3" />
                Voir
              </span>
              <span
                v-if="permission.can_download"
                class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400"
              >
                <ArrowDownTrayIcon class="h-3 w-3" />
                Télécharger
              </span>
              <span
                v-if="permission.can_edit"
                class="inline-flex items-center gap-1 rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-400"
              >
                <PencilIcon class="h-3 w-3" />
                Modifier
              </span>
              <span
                v-if="permission.can_delete"
                class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400"
              >
                <TrashIcon class="h-3 w-3" />
                Supprimer
              </span>
              <span
                v-if="permission.can_share"
                class="inline-flex items-center gap-1 rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-900/30 dark:text-orange-400"
              >
                <ShareIcon class="h-3 w-3" />
                Partager
              </span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2">
          <button
            @click="editPermission(permission)"
            class="rounded-3 p-2 text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
            title="Modifier"
          >
            <PencilIcon class="h-4 w-4" />
          </button>
          <button
            @click="revokePermission(permission)"
            class="rounded-3 p-2 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
            title="Révoquer"
          >
            <TrashIcon class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Add/Edit User Modal -->
    <TransitionRoot as="template" :show="showAddUser || editingPermission !== null">
      <Dialog as="div" class="relative z-50" @close="closeModal">
        <TransitionChild
          as="template"
          enter="ease-out duration-300"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="ease-in duration-200"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-black/50 " />
        </TransitionChild>

        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4">
            <TransitionChild
              as="template"
              enter="ease-out duration-300"
              enter-from="opacity-0 scale-95"
              enter-to="opacity-100 scale-100"
              leave="ease-in duration-200"
              leave-from="opacity-100 scale-100"
              leave-to="opacity-0 scale-95"
            >
              <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-3 bg-white p-6 transition-all dark:bg-gray-900">
                <DialogTitle class="text-lg font-semibold text-gray-900 dark:text-white">
                  {{ editingPermission ? 'Modifier les permissions' : 'Ajouter un utilisateur' }}
                </DialogTitle>

                <form @submit.prevent="savePermission" class="mt-6 space-y-4">
                  <!-- User Selection -->
                  <div v-if="!editingPermission">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Utilisateur
                    </label>
                    <select
                      v-model="formData.user_id"
                      required
                      class="mt-1 w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                      <option value="">Sélectionner un utilisateur</option>
                      <option
                        v-for="user in availableUsers"
                        :key="user.id"
                        :value="user.id"
                      >
                        {{ user.nom }}
                      </option>
                    </select>
                  </div>

                  <!-- Permissions Checkboxes -->
                  <div class="space-y-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Permissions
                    </label>

                    <label class="flex items-center gap-3">
                      <input
                        v-model="formData.can_view"
                        type="checkbox"
                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500/20"
                      />
                      <span class="text-sm text-gray-900 dark:text-white">Consulter</span>
                    </label>

                    <label class="flex items-center gap-3">
                      <input
                        v-model="formData.can_download"
                        type="checkbox"
                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500/20"
                      />
                      <span class="text-sm text-gray-900 dark:text-white">Télécharger</span>
                    </label>

                    <label class="flex items-center gap-3">
                      <input
                        v-model="formData.can_edit"
                        type="checkbox"
                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500/20"
                      />
                      <span class="text-sm text-gray-900 dark:text-white">Modifier</span>
                    </label>

                    <label class="flex items-center gap-3">
                      <input
                        v-model="formData.can_delete"
                        type="checkbox"
                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500/20"
                      />
                      <span class="text-sm text-gray-900 dark:text-white">Supprimer</span>
                    </label>

                    <label class="flex items-center gap-3">
                      <input
                        v-model="formData.can_share"
                        type="checkbox"
                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500/20"
                      />
                      <span class="text-sm text-gray-900 dark:text-white">Partager</span>
                    </label>
                  </div>

                  <!-- Expiration Date -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Date d'expiration (optionnelle)
                    </label>
                    <input
                      v-model="formData.expires_at"
                      type="datetime-local"
                      class="mt-1 w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                  </div>

                  <!-- Actions -->
                  <div class="flex justify-end gap-3 pt-4">
                    <button
                      type="button"
                      @click="closeModal"
                      class="rounded-3 border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                      Annuler
                    </button>
                    <button
                      type="submit"
                      :disabled="saving"
                      class="inline-flex items-center gap-2 rounded-3 bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                      <div v-if="saving" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                      {{ editingPermission ? 'Mettre à jour' : 'Ajouter' }}
                    </button>
                  </div>
                </form>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionRoot,
  TransitionChild
} from '@headlessui/vue'
import {
  UserPlusIcon,
  UserGroupIcon,
  EyeIcon,
  ArrowDownTrayIcon,
  PencilIcon,
  TrashIcon,
  ShareIcon
} from '@heroicons/vue/24/outline'
import api from '@/api/axios'

const props = defineProps({
  document: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['updated'])

const permissions = ref([])
const availableUsers = ref([])
const loading = ref(false)
const saving = ref(false)
const showAddUser = ref(false)
const editingPermission = ref(null)

const formData = ref({
  user_id: '',
  can_view: true,
  can_download: true,
  can_edit: false,
  can_delete: false,
  can_share: false,
  expires_at: ''
})

const loadPermissions = async () => {
  loading.value = true
  try {
    const response = await api.get(`/documents/${props.document.id}/permissions`)
    permissions.value = response.data.data
  } catch (error) {
    console.error('Error loading permissions:', error)
  } finally {
    loading.value = false
  }
}

const loadAvailableUsers = async () => {
  try {
    // Fetch users from workspace/project
    const response = await api.get('/users') // Adjust endpoint
    availableUsers.value = response.data.data
  } catch (error) {
    console.error('Error loading users:', error)
  }
}

const savePermission = async () => {
  saving.value = true
  try {
    if (editingPermission.value) {
      await api.post(`/documents/${props.document.id}/permissions/grant`, {
        user_id: editingPermission.value.permissionable_id,
        ...formData.value
      })
    } else {
      await api.post(`/documents/${props.document.id}/permissions/grant`, formData.value)
    }

    await loadPermissions()
    emit('updated')
    closeModal()
  } catch (error) {
    console.error('Error saving permission:', error)
  } finally {
    saving.value = false
  }
}

const editPermission = (permission) => {
  editingPermission.value = permission
  formData.value = {
    user_id: permission.permissionable_id,
    can_view: permission.can_view,
    can_download: permission.can_download,
    can_edit: permission.can_edit,
    can_delete: permission.can_delete,
    can_share: permission.can_share,
    expires_at: permission.expires_at || ''
  }
}

const revokePermission = async (permission) => {
  if (!confirm('Êtes-vous sûr de vouloir révoquer ces permissions ?')) return

  try {
    await api.post(`/documents/${props.document.id}/permissions/revoke`, {
      user_id: permission.permissionable_id
    })
    await loadPermissions()
    emit('updated')
  } catch (error) {
    console.error('Error revoking permission:', error)
  }
}

const closeModal = () => {
  showAddUser.value = false
  editingPermission.value = null
  formData.value = {
    user_id: '',
    can_view: true,
    can_download: true,
    can_edit: false,
    can_delete: false,
    can_share: false,
    expires_at: ''
  }
}

const getInitials = (name) => {
  if (!name) return '?'
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .substring(0, 2)
}

onMounted(() => {
  loadPermissions()
  loadAvailableUsers()
})
</script>