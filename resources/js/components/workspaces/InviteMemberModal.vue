<!-- resources/js/components/workspaces/InviteMemberModal.vue -->
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
            Inviter des membres au workspace
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
            <!-- Email Addresses -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Adresses email <span class="text-red-500">*</span>
              </label>
              <div class="space-y-3">
                <div
                  v-for="(email, index) in form.emails"
                  :key="index"
                  class="flex items-center gap-2"
                >
                  <input
                    v-model="form.emails[index]"
                    type="email"
                    required
                    placeholder="exemple@email.com"
                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                  />
                  <button
                    v-if="form.emails.length > 1"
                    type="button"
                    @click="removeEmail(index)"
                    class="p-2 text-red-600 hover:text-red-700 dark:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20"
                  >
                    <TrashIcon class="w-5 h-5" />
                  </button>
                </div>
              </div>
              <button
                type="button"
                @click="addEmail"
                class="mt-3 inline-flex items-center gap-2 text-sm text-brand-600 hover:text-brand-700 dark:text-brand-400 font-medium"
              >
                <PlusIcon class="w-4 h-4" />
                Ajouter une adresse
              </button>
              <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Invitez plusieurs personnes en ajoutant leurs adresses email
              </p>
            </div>

            <!-- Role Selection -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Rôle par défaut <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.role"
                required
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent"
              >
                <option value="">Sélectionner un rôle</option>
                <option value="admin">Administrateur</option>
                <option value="member">Membre</option>
                <option value="viewer">Observateur</option>
              </select>
              
              <!-- Role Description -->
              <div v-if="form.role" class="mt-3 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <p class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-2">
                  {{ getRoleLabel(form.role) }}
                </p>
                <p class="text-sm text-blue-800 dark:text-blue-400">
                  {{ getRoleDescription(form.role) }}
                </p>
              </div>
            </div>

            <!-- Message personnalisé -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Message personnalisé (optionnel)
              </label>
              <textarea
                v-model="form.message"
                rows="4"
                placeholder="Ajoutez un message personnel à votre invitation..."
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent resize-none"
              ></textarea>
            </div>

            <!-- Permissions workspace -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                Permissions du workspace
              </label>
              <div class="space-y-3">
                <div class="flex items-start gap-3">
                  <input
                    v-model="form.can_create_projects"
                    type="checkbox"
                    id="can_create_projects"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                  />
                  <div>
                    <label for="can_create_projects" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Peut créer des projets
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      Autorise la création de nouveaux projets dans le workspace
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <input
                    v-model="form.can_invite_members"
                    type="checkbox"
                    id="can_invite_members"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                  />
                  <div>
                    <label for="can_invite_members" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Peut inviter des membres
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      Autorise l'invitation de nouveaux membres au workspace
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <input
                    v-model="form.can_manage_settings"
                    type="checkbox"
                    id="can_manage_settings"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                  />
                  <div>
                    <label for="can_manage_settings" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Peut gérer les paramètres
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      Autorise la modification des paramètres du workspace
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Notification options -->
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
              <div class="flex items-start gap-3">
                <input
                  v-model="form.send_email"
                  type="checkbox"
                  id="send_email"
                  class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                />
                <div>
                  <label for="send_email" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Envoyer une invitation par email
                  </label>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Un email d'invitation sera envoyé avec un lien pour rejoindre le workspace
                  </p>
                </div>
              </div>
            </div>

            <!-- Important Notice -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
              <div class="flex gap-3">
                <AlertCircleIcon class="w-5 h-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" />
                <div>
                  <p class="text-sm font-medium text-yellow-900 dark:text-yellow-300">
                    Important
                  </p>
                  <p class="text-sm text-yellow-800 dark:text-yellow-400 mt-1">
                    Les membres invités auront accès à tous les projets publics et pourront être ajoutés aux projets privés par les responsables.
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
        <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
          <div class="text-sm text-gray-600 dark:text-gray-400">
            {{ form.emails.filter(e => e).length }} personne(s) à inviter
          </div>
          <div class="flex items-center gap-3">
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
              <MailIcon v-else class="w-5 h-5" />
              Envoyer les invitations
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useWorkspace } from '@/composables/useWorkspace'
import { XIcon, PlusIcon, TrashIcon, MailIcon, AlertCircleIcon } from '@/icons'

const props = defineProps({
  workspaceId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['close', 'invited'])

const { inviteMembers } = useWorkspace()

const submitting = ref(false)
const error = ref(null)

const form = ref({
  emails: [''],
  role: 'member',
  message: '',
  can_create_projects: false,
  can_invite_members: false,
  can_manage_settings: false,
  send_email: true
})

const canSubmit = computed(() => {
  return form.value.emails.some(email => email && email.includes('@')) &&
    form.value.role
})

const addEmail = () => {
  form.value.emails.push('')
}

const removeEmail = (index) => {
  form.value.emails.splice(index, 1)
}

const getRoleLabel = (role) => {
  const labels = {
    admin: 'Administrateur',
    member: 'Membre',
    viewer: 'Observateur'
  }
  return labels[role] || role
}

const getRoleDescription = (role) => {
  const descriptions = {
    admin: 'Accès complet au workspace : peut créer des projets, inviter des membres et gérer les paramètres.',
    member: 'Peut créer et gérer ses propres projets, participer aux projets auxquels il est invité.',
    viewer: 'Accès en lecture seule aux projets publics, peut voir les informations mais ne peut pas modifier.'
  }
  return descriptions[role] || ''
}

// Auto-set permissions based on role
watch(() => form.value.role, (newRole) => {
  if (newRole === 'admin') {
    form.value.can_create_projects = true
    form.value.can_invite_members = true
    form.value.can_manage_settings = true
  } else if (newRole === 'member') {
    form.value.can_create_projects = true
    form.value.can_invite_members = false
    form.value.can_manage_settings = false
  } else if (newRole === 'viewer') {
    form.value.can_create_projects = false
    form.value.can_invite_members = false
    form.value.can_manage_settings = false
  }
})

const handleSubmit = async () => {
  try {
    submitting.value = true
    error.value = null

    // Filter empty emails
    const validEmails = form.value.emails.filter(email => email && email.includes('@'))

    if (validEmails.length === 0) {
      error.value = 'Veuillez saisir au moins une adresse email valide'
      return
    }

    if (!form.value.role) {
      error.value = 'Veuillez sélectionner un rôle'
      return
    }

    await inviteMembers(props.workspaceId, {
      emails: validEmails,
      role: form.value.role,
      message: form.value.message,
      permissions: {
        can_create_projects: form.value.can_create_projects,
        can_invite_members: form.value.can_invite_members,
        can_manage_settings: form.value.can_manage_settings
      },
      send_email: form.value.send_email
    })

    emit('invited')
  } catch (err) {
    error.value = err.response?.data?.message || 'Une erreur est survenue lors de l\'envoi des invitations'
    console.error('Error inviting members:', err)
  } finally {
    submitting.value = false
  }
}
</script>