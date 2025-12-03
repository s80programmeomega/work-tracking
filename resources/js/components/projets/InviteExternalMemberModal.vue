<template>
  <Teleport to="body">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div
        class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden"
        @click.stop
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
            Inviter des membres au projet
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
            <!-- Tabs -->
            <div class="border-b border-gray-200 dark:border-gray-700">
              <nav class="flex space-x-8">
                <button
                  type="button"
                  @click="activeTab = 'workspace'"
                  :class="[
                    'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                    activeTab === 'workspace'
                      ? 'border-brand-600 text-brand-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
                  ]"
                >
                  <div class="flex items-center gap-2">
                    <UsersIcon class="w-5 h-5" />
                    Membres du workspace ({{ workspaceMembers.length }})
                  </div>
                </button>
                <button
                  type="button"
                  @click="activeTab = 'email'"
                  :class="[
                    'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                    activeTab === 'email'
                      ? 'border-brand-600 text-brand-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
                  ]"
                >
                  <div class="flex items-center gap-2">
                    <MailIcon class="w-5 h-5" />
                    Par email
                  </div>
                </button>
              </nav>
            </div>

            <!-- Workspace Members Tab -->
            <div v-if="activeTab === 'workspace'">
              <!-- Search -->
              <div class="relative mb-4">
                <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input
                  v-model="searchTerm"
                  type="text"
                  placeholder="Rechercher par nom ou email..."
                  class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                />
              </div>

              <!-- Members List -->
              <div class="border border-gray-300 dark:border-gray-600 rounded-lg max-h-80 overflow-y-auto">
                <div v-if="loadingMembers" class="flex justify-center py-8">
                  <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-600"></div>
                </div>
                
                <div v-else-if="filteredWorkspaceMembers.length === 0" class="text-center py-8">
                  <UsersIcon class="mx-auto h-12 w-12 text-gray-400" />
                  <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Aucun membre disponible
                  </p>
                </div>

                <div v-else>
                  <label
                    v-for="member in filteredWorkspaceMembers"
                    :key="member.id"
                    class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-200 dark:border-gray-700 last:border-0"
                  >
                    <input
                      type="checkbox"
                      :value="member.id"
                      v-model="selectedMembers"
                      class="w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500"
                    />
                    <div
                      v-if="member.avatar"
                      class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0"
                    >
                      <img :src="member.avatar" :alt="member.nom" class="w-full h-full object-cover" />
                    </div>
                    <div
                      v-else
                      class="w-10 h-10 rounded-full bg-brand-600 flex items-center justify-center text-white font-medium flex-shrink-0"
                    >
                      {{ getInitials(member.nom) }}
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                        {{ member.nom }}
                      </div>
                      <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                        {{ member.email }}
                      </div>
                    </div>
                  </label>
                </div>
              </div>

              <p v-if="selectedMembers.length > 0" class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ selectedMembers.length }} membre(s) sélectionné(s)
              </p>
            </div>

            <!-- Email Tab -->
            <div v-if="activeTab === 'email'">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Adresses email <span class="text-red-500">*</span>
              </label>
              <textarea
                v-model="emailsInput"
                rows="4"
                placeholder="Entrez une ou plusieurs adresses email (séparées par des virgules ou des retours à la ligne)&#10;Exemple: jean@example.com, marie@example.com"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
              ></textarea>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Les utilisateurs externes recevront un email pour créer un compte
              </p>

              <!-- Parsed emails preview -->
              <div v-if="parsedEmails.length > 0" class="mt-3 flex flex-wrap gap-2">
                <span
                  v-for="(email, index) in parsedEmails"
                  :key="index"
                  class="inline-flex items-center gap-1 px-3 py-1 bg-brand-100 dark:bg-brand-900/30 text-brand-800 dark:text-brand-300 rounded-full text-sm"
                >
                  {{ email }}
                  <button
                    type="button"
                    @click="removeEmail(index)"
                    class="hover:text-brand-900 dark:hover:text-brand-100"
                  >
                    <XIcon class="w-3 h-3" />
                  </button>
                </span>
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
                @change="handleRoleChange"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent"
              >
                <option value="">Sélectionner un rôle</option>
                <option value="admin">Administrateur - Tous les droits</option>
                <option value="member">Membre - Peut voir et éditer le projet</option>
                <option value="viewer">Observateur - Lecture seule</option>
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
                    id="inv_can_edit"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500"
                  />
                  <div>
                    <label for="inv_can_edit" class="text-sm font-medium text-gray-700 dark:text-gray-300">
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
                    id="inv_can_delete"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500"
                  />
                  <div>
                    <label for="inv_can_delete" class="text-sm font-medium text-gray-700 dark:text-gray-300">
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
                    id="inv_can_invite"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500"
                  />
                  <div>
                    <label for="inv_can_invite" class="text-sm font-medium text-gray-700 dark:text-gray-300">
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

            <!-- Message -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Message personnel (optionnel)
              </label>
              <textarea
                v-model="form.message"
                rows="3"
                placeholder="Ajouter un message pour les invités..."
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
              ></textarea>
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

            <!-- Success Summary -->
            <div
              v-if="invitationResult"
              class="p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800"
            >
              <p class="text-sm font-medium text-green-900 dark:text-green-300 mb-2">
                ✓ {{ invitationResult.success_count }} invitation(s) envoyée(s) avec succès
              </p>
              <div v-if="invitationResult.errors && invitationResult.errors.length > 0" class="mt-2">
                <p class="text-xs text-green-800 dark:text-green-400 mb-1">Erreurs :</p>
                <ul class="text-xs text-green-700 dark:text-green-400 list-disc list-inside">
                  <li v-for="err in invitationResult.errors" :key="err.email">
                    {{ err.email }}: {{ err.message }}
                  </li>
                </ul>
              </div>
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
            :disabled="submitting || !canSubmit"
            class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
          >
            <span v-if="submitting" class="animate-spin">⏳</span>
            Envoyer {{ totalInvitations }} invitation(s)
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useWorkspace } from '@/composables/useWorkspace'
import { useProjetInvitations } from '@/composables/useProjetInvitations'
import { XIcon, SearchIcon, UsersIcon, MailIcon } from '@/icons'
import api from '@/api/axios'

const props = defineProps({
  projetId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['close', 'invited'])

const { fetchMembers } = useWorkspace()
const { inviteMembers } = useProjetInvitations()

const activeTab = ref('workspace')
const loadingMembers = ref(false)
const submitting = ref(false)
const error = ref(null)
const invitationResult = ref(null)

const workspaceMembers = ref([])
const selectedMembers = ref([])
const searchTerm = ref('')
const emailsInput = ref('')

const form = ref({
  role: '',
  can_edit: false,
  can_delete: false,
  can_invite: false,
  can_delete_member: false,
  message: ''
})

// Computed
const filteredWorkspaceMembers = computed(() => {
  if (!searchTerm.value) return workspaceMembers.value

  const term = searchTerm.value.toLowerCase()
  return workspaceMembers.value.filter(member =>
    member.nom.toLowerCase().includes(term) ||
    member.email.toLowerCase().includes(term)
  )
})

const parsedEmails = computed(() => {
  if (!emailsInput.value.trim()) return []

  return emailsInput.value
    .split(/[\n,;]+/)
    .map(email => email.trim())
    .filter(email => email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email))
})

const totalInvitations = computed(() => {
  if (activeTab.value === 'workspace') {
    return selectedMembers.value.length
  } else {
    return parsedEmails.value.length
  }
})

const canSubmit = computed(() => {
  return form.value.role && totalInvitations.value > 0
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

const removeEmail = (index) => {
  const emails = parsedEmails.value
  emails.splice(index, 1)
  emailsInput.value = emails.join(', ')
}

// ✅ Gestion intelligente des permissions basées sur le rôle
const handleRoleChange = () => {
  const role = form.value.role
  
  if (role === 'admin') {
    // Admin a toutes les permissions
    form.value.can_edit = true
    form.value.can_delete = true
    form.value.can_invite = true
    form.value.can_delete_member = true
  } else if (role === 'member') {
    // Membre peut seulement éditer
    form.value.can_edit = true
    form.value.can_delete = false
    form.value.can_invite = false
    form.value.can_delete_member = false
  } else if (role === 'viewer') {
    // Viewer n'a aucune permission
    form.value.can_edit = false
    form.value.can_delete = false
    form.value.can_invite = false
    form.value.can_delete_member = false
  }
}

// ✅ Empêcher la modification manuelle des permissions pour les rôles restreints
watch(() => form.value.role, (newRole) => {
  if (newRole === 'member' || newRole === 'viewer') {
    // Forcer les valeurs correctes si l'utilisateur essaie de les modifier manuellement
    if (form.value.can_delete) form.value.can_delete = false
    if (form.value.can_invite) form.value.can_invite = false
    if (form.value.can_delete_member) form.value.can_delete_member = false
  }
})

const handleSubmit = async () => {
  try {
    submitting.value = true
    error.value = null
    invitationResult.value = null

    if (!form.value.role) {
      error.value = 'Veuillez sélectionner un rôle'
      return
    }

    // ✅ Validation des permissions selon le rôle
    if (form.value.role === 'member' && (form.value.can_delete || form.value.can_invite || form.value.can_delete_member)) {
      error.value = 'Les membres ne peuvent pas avoir les permissions de suppression ou d\'invitation'
      return
    }

    if (form.value.role === 'viewer' && (form.value.can_edit || form.value.can_delete || form.value.can_invite || form.value.can_delete_member)) {
      error.value = 'Les observateurs ne peuvent avoir aucune permission'
      return
    }

    let emails = []

    if (activeTab.value === 'workspace') {
      emails = workspaceMembers.value
        .filter(m => selectedMembers.value.includes(m.id))
        .map(m => m.email)
    } else {
      emails = parsedEmails.value
    }

    if (emails.length === 0) {
      error.value = 'Veuillez sélectionner au moins un membre ou entrer un email'
      return
    }

    const result = await inviteMembers(props.projetId, {
      emails,
      role: form.value.role,
      can_edit: form.value.can_edit,
      can_delete: form.value.can_delete,
      can_invite: form.value.can_invite,
      can_delete_member: form.value.can_delete_member,
      message: form.value.message,
      send_email: true
    })

    invitationResult.value = result.data

    if (result.data.success_count > 0) {
      setTimeout(() => {
        emit('invited')
      }, 2000)
    }

  } catch (err) {
    error.value = err.response?.data?.message || 'Une erreur est survenue'
    console.error('Error inviting members:', err)
  } finally {
    submitting.value = false
  }
}

// Load workspace members
onMounted(async () => {
  try {
    loadingMembers.value = true
    
    const projetResponse = await api.get(`/projets/${props.projetId}`)
    const workspaceId = projetResponse.data.data.workspace_id
    
    if (workspaceId) {
      const allMembers = await fetchMembers(workspaceId)
      const projetMembers = projetResponse.data.data.members || []
      const projetMemberIds = projetMembers.map(m => m.id)
      
      workspaceMembers.value = allMembers.filter(m => !projetMemberIds.includes(m.id))
    }
  } catch (err) {
    console.error('Error loading members:', err)
  } finally {
    loadingMembers.value = false
  }
})
</script>