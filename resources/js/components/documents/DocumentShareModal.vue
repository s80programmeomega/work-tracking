<template>
  <TransitionRoot as="template" :show="true">
    <Dialog as="div" class="relative z-50" @close="$emit('close')">
      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel ref="dialogRef" :style="dragStyle" class="relative transform overflow-hidden rounded-3 border border-gray-200 dark:border-gray-700 bg-white text-left transition-all dark:bg-gray-900 sm:my-8 sm:w-full sm:max-w-2xl">
              <!-- Header -->
              <div ref="handleRef" class="border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-800 dark:bg-gray-900 cursor-move select-none">
                <div class="flex items-center justify-between">
                  <DialogTitle class="text-lg font-semibold text-gray-900 dark:text-white">
                    Partager "{{ document.nom }}"
                  </DialogTitle>
                  <button
                    @click="$emit('close')"
                    class="rounded-3 p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-500 dark:text-gray-400 dark:hover:bg-gray-800"
                  >
                    <XMarkIcon class="h-6 w-6" />
                  </button>
                </div>
              </div>

              <!-- Body -->
              <div class="bg-white px-6 py-5 dark:bg-gray-900">
                <!-- Add User Section -->
                <div class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Partager avec
                    </label>
                    <div class="mt-1 flex gap-2">
                      <div class="relative flex-1">
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                        <input
                          v-model="searchQuery"
                          @input="searchUsers"
                          type="text"
                          placeholder="Rechercher un utilisateur..."
                          class="w-full rounded-3 border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                        />
                        
                        <!-- Search Results Dropdown -->
                        <div
                          v-if="showSearchResults && searchResults.length > 0"
                          class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-3 border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
                        >
                          <button
                            v-for="user in searchResults"
                            :key="user.id"
                            @click="selectUser(user)"
                            class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700"
                          >
                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-sm font-medium text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 shrink-0">
                              {{ getInitials(user.nom) }}
                            </div>
                            <div class="min-w-0 flex-1">
                              <div class="flex items-center gap-2">
                                <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                  {{ user.nom }}
                                </p>
                              </div>
                              <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                {{ user.email }}
                              </p>
                            </div>
                          </button>
                        </div>

                        <!-- Aucun utilisateur trouvé mais saisie = email valide → partage externe -->
                        <div
                          v-else-if="showSearchResults && searchResults.length === 0 && isEmail(searchQuery)"
                          class="absolute z-10 mt-1 w-full rounded-3 border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
                        >
                          <p class="text-sm text-gray-700 dark:text-gray-300">
                            Aucun utilisateur trouvé pour
                            <strong class="font-medium">{{ searchQuery }}</strong>.
                          </p>
                          <button
                            dusk="share-by-email-button"
                            @click="handleSendByEmail"
                            :disabled="sendingByEmail"
                            class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-3 bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                          >
                            <ShareIcon class="h-5 w-5" />
                            {{ sendingByEmail ? 'Envoi...' : 'Envoyer une invitation par email' }}
                          </button>
                          <p v-if="emailShareSuccess" class="mt-2 text-xs text-green-600 dark:text-green-400">
                            {{ emailShareSuccess }}
                          </p>
                          <p v-if="emailShareError" class="mt-2 text-xs text-red-600 dark:text-red-400">
                            {{ emailShareError }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Selected User & Permissions -->
                  <div v-if="selectedUser" class="rounded-3 border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                    <div class="flex items-start justify-between">
                      <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-sm font-medium text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                          {{ getInitials(selectedUser.nom) }}
                        </div>
                        <div>
                          <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ selectedUser.nom }}
                          </p>
                          <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ selectedUser.email }}
                          </p>
                        </div>
                      </div>
                      <button
                        @click="selectedUser = null"
                        class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300"
                      >
                        <XMarkIcon class="h-5 w-5" />
                      </button>
                    </div>

                    <!-- Permissions Checkboxes -->
                    <div class="mt-4 space-y-2">
                      <label class="flex items-center gap-2 text-sm">
                        <input
                          v-model="permissions.can_view"
                          type="checkbox"
                          class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600"
                        />
                        <span class="text-gray-700 dark:text-gray-300">Consulter</span>
                      </label>
                      <label class="flex items-center gap-2 text-sm">
                        <input
                          v-model="permissions.can_download"
                          type="checkbox"
                          class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600"
                        />
                        <span class="text-gray-700 dark:text-gray-300">Télécharger</span>
                      </label>
                      <label class="flex items-center gap-2 text-sm">
                        <input
                          v-model="permissions.can_edit"
                          type="checkbox"
                          class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600"
                        />
                        <span class="text-gray-700 dark:text-gray-300">Modifier</span>
                      </label>
                      <label class="flex items-center gap-2 text-sm">
                        <input
                          v-model="permissions.can_delete"
                          type="checkbox"
                          class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600"
                        />
                        <span class="text-gray-700 dark:text-gray-300">Supprimer</span>
                      </label>
                      <label class="flex items-center gap-2 text-sm">
                        <input
                          v-model="permissions.can_share"
                          type="checkbox"
                          class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600"
                        />
                        <span class="text-gray-700 dark:text-gray-300">Partager</span>
                      </label>
                    </div>

                    <!-- Expiration Date -->
                    <div class="mt-4">
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Date d'expiration (optionnelle)
                      </label>
                      <input
                        v-model="expiresAt"
                        type="date"
                        :min="today"
                        :max="maxDate"
                        class="mt-1 block w-full rounded-3 border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                      />
                    </div>

                    <button
                      @click="handleGrantPermission"
                      :disabled="loading"
                      class="mt-4 w-full inline-flex items-center justify-center gap-2 rounded-3 bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                      <ShareIcon class="h-5 w-5" />
                      Accorder les permissions
                    </button>
                  </div>
                </div>

                <!-- Current Permissions List -->
                <div class="mt-6">
                  <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                    Accès partagé ({{ currentPermissions.length }})
                  </h3>
                  
                  <div v-if="loadingPermissions" class="mt-4 text-center">
                    <div class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-blue-600 border-t-transparent"></div>
                  </div>

                  <div v-else-if="currentPermissions.length === 0" class="mt-4 text-center py-6">
                    <UserGroupIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                      Ce document n'est pas encore partagé
                    </p>
                  </div>

                  <div v-else class="mt-4 space-y-2">
                    <div
                      v-for="permission in currentPermissions"
                      :key="permission.id"
                      class="flex items-center justify-between rounded-3 border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800"
                    >
                      <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-xs font-medium text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                          {{ getInitials(permission.permissionable?.nom || 'U') }}
                        </div>
                        <div>
                          <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ permission.permissionable?.nom || 'Utilisateur' }}
                          </p>
                          <div class="flex flex-wrap gap-1 mt-1">
                            <span v-if="permission.can_view" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                              Consulter
                            </span>
                            <span v-if="permission.can_download" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                              Télécharger
                            </span>
                            <span v-if="permission.can_edit" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">
                              Modifier
                            </span>
                            <span v-if="permission.can_share" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                              Partager
                            </span>
                          </div>
                        </div>
                      </div>
                      <button
                        @click="handleRevokePermission(permission)"
                        class="text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                        title="Révoquer"
                      >
                        <TrashIcon class="h-5 w-5" />
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Error Message -->
                <div v-if="error" class="mt-4 rounded-3 bg-red-50 p-4 dark:bg-red-900/20">
                  <div class="flex items-start gap-3">
                    <ExclamationTriangleIcon class="h-5 w-5 flex-shrink-0 text-red-600 dark:text-red-400" />
                    <p class="text-sm text-red-800 dark:text-red-300">{{ error }}</p>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-800 dark:bg-gray-800/50">
                <div class="flex justify-end">
                  <button
                    @click="$emit('close')"
                    class="rounded-3 border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                  >
                    Fermer
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
import { ref, reactive, computed, onMounted } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { useDraggable } from '@/composables/useDraggable'

// Modale déplaçable par son en-tête (panneau Headless UI).
const { dialogRef, handleRef, dragStyle, attachHandle } = useDraggable()
import {
  XMarkIcon,
  MagnifyingGlassIcon,
  ShareIcon,
  UserGroupIcon,
  TrashIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'
import { useDocuments } from '@/composables/useDocuments'
import api from '@/api/axios'

const props = defineProps({
  document: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'shared'])

const { grantPermission, revokePermission, loading, error } = useDocuments()

const searchQuery = ref('')
const searchResults = ref([])
const showSearchResults = ref(false)
const selectedUser = ref(null)
const currentPermissions = ref([])
const loadingPermissions = ref(false)

const permissions = reactive({
  can_view: true,
  can_download: true,
  can_edit: false,
  can_delete: false,
  can_share: false
})

const expiresAt = ref('')
const sendingByEmail = ref(false)
const emailShareSuccess = ref('')
const emailShareError = ref('')

const isEmail = (str) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(str).trim())

const today = computed(() => new Date().toISOString().split('T')[0])
const maxDate = computed(() => {
  const d = new Date()
  d.setFullYear(d.getFullYear() + 5)
  return d.toISOString().split('T')[0]
})

const searchUsers = async () => {
  if (searchQuery.value.length < 2) {
    searchResults.value = []
    showSearchResults.value = false
    return
  }

  if (!props.document.workspace_id) return

  try {
    const alreadySharedIds = currentPermissions.value.map(p => p.permissionable_id)
    const response = await api.get(`/workspaces/${props.document.workspace_id}/members/search`, {
      params: {
        q: searchQuery.value,
        exclude_user_ids: alreadySharedIds,
      },
    })
    searchResults.value = response.data.data
    showSearchResults.value = true
  } catch (err) {
    console.error('Search error:', err)
  }
}

const selectUser = (user) => {
  selectedUser.value = user
  searchQuery.value = ''
  showSearchResults.value = false
}

const handleGrantPermission = async () => {
  if (!selectedUser.value) return

  try {
    await grantPermission(
      props.document.id,
      selectedUser.value.id,
      permissions,
      expiresAt.value ? expiresAt.value : null
    )
    
    selectedUser.value = null
    expiresAt.value = ''
    permissions.can_view = true
    permissions.can_download = true
    permissions.can_edit = false
    permissions.can_delete = false
    permissions.can_share = false
    
    loadPermissions()
    emit('shared')
  } catch (err) {
    console.error('Grant permission error:', err)
  }
}

const handleRevokePermission = async (permission) => {
  if (!confirm('Êtes-vous sûr de vouloir révoquer ces permissions ?')) return

  try {
    await revokePermission(props.document.id, permission.permissionable_id)
    loadPermissions()
  } catch (err) {
    console.error('Revoke permission error:', err)
  }
}

const handleSendByEmail = async () => {
  if (!isEmail(searchQuery.value)) return
  sendingByEmail.value = true
  emailShareSuccess.value = ''
  emailShareError.value = ''
  try {
    const response = await api.post(`/documents/${props.document.id}/share-by-email`, {
      email: searchQuery.value.trim().toLowerCase(),
    })
    emailShareSuccess.value = response.data?.message ?? 'Email envoyé.'
    searchQuery.value = ''
    showSearchResults.value = false
    emit('shared')
  } catch (err) {
    emailShareError.value = err.response?.data?.message ?? 'Échec de l\'envoi.'
  } finally {
    sendingByEmail.value = false
  }
}

const loadPermissions = async () => {
  loadingPermissions.value = true
  try {
    const response = await api.get(`/documents/${props.document.id}/permissions`)
    currentPermissions.value = response.data.data
  } catch (err) {
    console.error('Load permissions error:', err)
  } finally {
    loadingPermissions.value = false
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
  attachHandle()
  loadPermissions()
})
</script>