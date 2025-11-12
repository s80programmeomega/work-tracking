<!-- resources/js/components/projets/AddMemberModal.vue -->
<template>
  <Teleport to="body">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div
        class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden"
        @click.stop
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
            Ajouter un membre au projet
          </h2>
          <button
            @click="$emit('close')"
            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <XIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Body -->
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
          <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- User Selection -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Sélectionner un utilisateur <span class="text-red-500">*</span>
              </label>
              
              <!-- Search Users -->
              <div class="relative mb-3">
                <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input
                  v-model="searchTerm"
                  type="text"
                  placeholder="Rechercher par nom ou email..."
                  class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                />
              </div>

              <!-- Users List -->
              <div class="border border-gray-300 dark:border-gray-600 rounded-lg max-h-64 overflow-y-auto">
                <div v-if="loading" class="flex justify-center py-8">
                  <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-600"></div>
                </div>
                
                <div v-else-if="filteredUsers.length === 0" class="text-center py-8">
                  <UsersIcon class="mx-auto h-12 w-12 text-gray-400" />
                  <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Aucun utilisateur disponible
                  </p>
                </div>

                <div v-else>
                  <button
                    v-for="user in filteredUsers"
                    :key="user.id"
                    type="button"
                    @click="selectUser(user)"
                    :class="[
                      'w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors border-b border-gray-200 dark:border-gray-700 last:border-0',
                      form.user_id === user.id ? 'bg-brand-50 dark:bg-brand-900/20' : ''
                    ]"
                  >
                    <div
                      v-if="user.avatar"
                      class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0"
                    >
                      <img :src="user.avatar" :alt="user.nom" class="w-full h-full object-cover" />
                    </div>
                    <div
                      v-else
                      class="w-10 h-10 rounded-full bg-brand-600 flex items-center justify-center text-white font-medium flex-shrink-0"
                    >
                      {{ getInitials(user.nom) }}
                    </div>
                    <div class="flex-1 text-left min-w-0">
                      <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                        {{ user.nom }}
                      </div>
                      <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                        {{ user.email }}
                      </div>
                    </div>
                    <CheckIcon
                      v-if="form.user_id === user.id"
                      class="w-5 h-5 text-brand-600 dark:text-brand-400 flex-shrink-0"
                    />
                  </button>
                </div>
              </div>
            </div>

            <!-- Role Selection -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Rôle <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.role"
                required
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent"
              >
                <option value="">Sélectionner un rôle</option>
                <option value="admin">Administrateur - Tous les droits sauf suppression</option>
                <option value="member">Membre - Peut voir et éditer le projet</option>
                <option value="viewer">Observateur - Lecture seule</option>
              </select>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Le rôle définit les permissions de base du membre
              </p>
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
                    type="checkbox"
                    id="can_edit"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                  />
                  <div>
                    <label for="can_edit" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Peut modifier le projet
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      Permet de modifier les informations du projet, créer des activités et tâches
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <input
                    v-model="form.can_delete"
                    type="checkbox"
                    id="can_delete"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                  />
                  <div>
                    <label for="can_delete" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Peut supprimer
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      Permet de supprimer des activités, tâches et documents
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <input
                    v-model="form.can_invite"
                    type="checkbox"
                    id="can_invite"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                  />
                  <div>
                    <label for="can_invite" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Peut inviter des membres
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      Permet d'ajouter ou retirer des membres du projet
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Note -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
              <div class="flex gap-3">
                <AlertCircleIcon class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                <div>
                  <p class="text-sm font-medium text-blue-900 dark:text-blue-300">
                    Important
                  </p>
                  <p class="text-sm text-blue-800 dark:text-blue-400 mt-1">
                    Une fois ajouté, le membre recevra une notification et aura accès au projet selon ses permissions.
                  </p>
                </div>
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
            @click="handleSubmit"
            :disabled="submitting || !form.user_id || !form.role"
            class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
          >
            <span v-if="submitting" class="animate-spin">⏳</span>
            Ajouter le membre
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useProjets } from '@/composables/useProjets'
import { useWorkspace } from '@/composables/useWorkspace'
import { XIcon, SearchIcon, UsersIcon, CheckIcon, AlertCircleIcon } from '@/icons'

const props = defineProps({
  projetId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['close', 'added'])

const { addMember } = useProjets()
const { fetchMembers } = useWorkspace()

const loading = ref(false)
const submitting = ref(false)
const error = ref(null)
const users = ref([])
const searchTerm = ref('')

const form = ref({
  user_id: null,
  role: '',
  can_edit: false,
  can_delete: false,
  can_invite: false
})

const filteredUsers = computed(() => {
  if (!searchTerm.value) return users.value

  const term = searchTerm.value.toLowerCase()
  return users.value.filter(user =>
    user.nom.toLowerCase().includes(term) ||
    user.email.toLowerCase().includes(term)
  )
})

const selectUser = (user) => {
  form.value.user_id = user.id
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

// Auto-set permissions based on role
watch(() => form.value.role, (newRole) => {
  if (newRole === 'admin') {
    form.value.can_edit = true
    form.value.can_delete = true
    form.value.can_invite = true
  } else if (newRole === 'member') {
    form.value.can_edit = true
    form.value.can_delete = false
    form.value.can_invite = false
  } else if (newRole === 'viewer') {
    form.value.can_edit = false
    form.value.can_delete = false
    form.value.can_invite = false
  }
})

const handleSubmit = async () => {
  try {
    submitting.value = true
    error.value = null

    if (!form.value.user_id) {
      error.value = 'Veuillez sélectionner un utilisateur'
      return
    }

    if (!form.value.role) {
      error.value = 'Veuillez sélectionner un rôle'
      return
    }

    await addMember(props.projetId, form.value)
    emit('added')
  } catch (err) {
    error.value = err.response?.data?.message || 'Une erreur est survenue'
    console.error('Error adding member:', err)
  } finally {
    submitting.value = false
  }
}

// ✅ Charger les membres du workspace
onMounted(async () => {
  try {
    loading.value = true
    
    // Récupérer le workspace_id du projet
    const projetResponse = await api.get(`/projets/${props.projetId}`)
    const workspaceId = projetResponse.data.data.workspace_id
    
    if (workspaceId) {
      // Charger les membres du workspace
      const { fetchMembers } = useWorkspace()
      const workspaceMembers = await fetchMembers(workspaceId)
      
      // Exclure les membres déjà dans le projet
      const projetMembers = projetResponse.data.data.members || []
      const projetMemberIds = projetMembers.map(m => m.id)
      
      users.value = workspaceMembers.filter(m => !projetMemberIds.includes(m.id))
    }
  } catch (err) {
    console.error('Error loading users:', err)
  } finally {
    loading.value = false
  }
})
</script>