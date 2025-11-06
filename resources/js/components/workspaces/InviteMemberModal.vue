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
          <div class="flex items-center gap-3">
            <div class="p-2 bg-brand-100 dark:bg-brand-900/30 rounded-lg">
              <MailIcon class="w-6 h-6 text-brand-600 dark:text-brand-400" />
            </div>
            <div>
              <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                Inviter des membres
              </h2>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Ajoutez de nouveaux membres à votre workspace
              </p>
            </div>
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
          <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Email Addresses -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                Adresses email <span class="text-red-500">*</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 font-normal ml-2">
                  (une par ligne ou séparées par des virgules)
                </span>
              </label>
              
              <!-- Email Input Area -->
              <div class="relative">
                <textarea
                  v-model="emailInput"
                  @input="processEmailInput"
                  @paste="handlePaste"
                  rows="3"
                  placeholder="exemple@email.com, autre@domaine.com"
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent resize-none font-mono text-sm"
                ></textarea>
                <div class="absolute top-2 right-2">
                  <button
                    type="button"
                    @click="clearEmails"
                    class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    title="Effacer tout"
                  >
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </div>

              <!-- Email Tags -->
              <div v-if="emailTags.length > 0" class="mt-3">
                <div class="flex flex-wrap gap-2">
                  <div
                    v-for="(email, index) in emailTags"
                    :key="index"
                    class="flex items-center gap-2 px-3 py-1.5 bg-brand-100 dark:bg-brand-900/30 text-brand-700 dark:text-brand-300 rounded-full text-sm"
                  >
                    <span>{{ email }}</span>
                    <button
                      type="button"
                      @click="removeEmailTag(index)"
                      class="text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-200"
                    >
                      <XIcon class="w-3 h-3" />
                    </button>
                  </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                  {{ emailTags.length }} adresse(s) email à inviter
                </p>
              </div>

              <!-- Email Validation Errors -->
              <div v-if="emailErrors.length > 0" class="mt-3 space-y-1">
                <div
                  v-for="(error, index) in emailErrors"
                  :key="index"
                  class="flex items-center gap-2 text-xs text-red-600 dark:text-red-400"
                >
                  <AlertCircleIcon class="w-3 h-3 flex-shrink-0" />
                  <span>{{ error }}</span>
                </div>
              </div>
            </div>

            <!-- Role Selection -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                Rôle dans le workspace <span class="text-red-500">*</span>
              </label>
              
              <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div
                  v-for="role in availableRoles"
                  :key="role.value"
                  @click="form.role = role.value"
                  :class="[
                    'p-4 border-2 rounded-lg cursor-pointer transition-all duration-200',
                    form.role === role.value
                      ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20 dark:border-brand-400'
                      : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                  ]"
                >
                  <div class="flex items-center gap-3 mb-2">
                    <div
                      :class="[
                        'w-4 h-4 rounded-full border-2 flex items-center justify-center',
                        form.role === role.value
                          ? 'border-brand-500 bg-brand-500'
                          : 'border-gray-300 dark:border-gray-500'
                      ]"
                    >
                      <div
                        v-if="form.role === role.value"
                        class="w-1.5 h-1.5 bg-white rounded-full"
                      ></div>
                    </div>
                    <span class="font-medium text-gray-900 dark:text-white">
                      {{ role.label }}
                    </span>
                  </div>
                  <p class="text-xs text-gray-600 dark:text-gray-400">
                    {{ role.description }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Permissions Section -->
            <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4">
              <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                Permissions supplémentaires
              </h4>
              <div class="space-y-3">
                <div
                  v-for="permission in availablePermissions"
                  :key="permission.key"
                  class="flex items-start gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600"
                >
                  <input
                    :id="permission.key"
                    v-model="form.permissions[permission.key]"
                    type="checkbox"
                    :disabled="permission.disabled"
                    class="mt-1 w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                  />
                  <div class="flex-1">
                    <div class="flex items-center gap-2">
                      <label
                        :for="permission.key"
                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                      >
                        {{ permission.label }}
                      </label>
                      <span
                        v-if="permission.recommended"
                        class="px-1.5 py-0.5 text-xs bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full"
                      >
                        Recommandé
                      </span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      {{ permission.description }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Custom Message -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Message personnalisé
                <span class="text-xs text-gray-500 dark:text-gray-400 font-normal">
                  (optionnel)
                </span>
              </label>
              <div class="relative">
                <textarea
                  v-model="form.message"
                  rows="3"
                  placeholder="Bonjour, je vous invite à rejoindre notre workspace..."
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent resize-none"
                  maxlength="500"
                ></textarea>
                <div class="absolute bottom-2 right-2">
                  <span class="text-xs text-gray-400">
                    {{ form.message.length }}/500
                  </span>
                </div>
              </div>
            </div>

            <!-- Notification Options -->
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
              <div class="flex items-start gap-3">
                <input
                  v-model="form.send_email"
                  type="checkbox"
                  id="send_email"
                  class="mt-1 w-4 h-4 text-brand-600 bg-white border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                />
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <label for="send_email" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Envoyer une invitation par email
                    </label>
                    <!-- <BadgeCheckIcon class="w-4 h-4 text-blue-500" /> -->
                  </div>
                  <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                    Les membres recevront un email avec un lien pour rejoindre le workspace directement.
                    Désactivez cette option si vous préférez partager le lien manuellement.
                  </p>
                </div>
              </div>
            </div>

            <!-- Success/Error Messages -->
            <div
              v-if="successResults.length > 0"
              class="p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800"
            >
              <h4 class="text-sm font-medium text-green-800 dark:text-green-400 mb-2">
                Invitations envoyées avec succès
              </h4>
              <div class="space-y-1">
                <div
                  v-for="result in successResults"
                  :key="result.email"
                  class="flex items-center gap-2 text-sm text-green-700 dark:text-green-300"
                >
                  <CheckCircleIcon class="w-4 h-4" />
                  <span>{{ result.email }}</span>
                  <span class="text-xs bg-green-200 dark:bg-green-800 text-green-800 dark:text-green-200 px-2 py-0.5 rounded-full">
                    {{ result.status === 'added' ? 'Ajouté' : 'Invité' }}
                  </span>
                </div>
              </div>
            </div>

            <div
              v-if="errorResults.length > 0"
              class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800"
            >
              <h4 class="text-sm font-medium text-red-800 dark:text-red-400 mb-2">
                Erreurs lors de l'envoi
              </h4>
              <div class="space-y-1">
                <div
                  v-for="result in errorResults"
                  :key="result.email"
                  class="flex items-center gap-2 text-sm text-red-700 dark:text-red-300"
                >
                  <AlertCircleIcon class="w-4 h-4" />
                  <span>{{ result.email }}</span>
                  <span class="text-xs">- {{ result.message }}</span>
                </div>
              </div>
            </div>

            <!-- General Error -->
            <div
              v-if="generalError"
              class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800"
            >
              <div class="flex items-center gap-2">
                <AlertCircleIcon class="w-5 h-5 text-red-600 dark:text-red-400" />
                <p class="text-sm text-red-800 dark:text-red-400">
                  {{ generalError }}
                </p>
              </div>
            </div>
          </form>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
          <div class="text-sm text-gray-600 dark:text-gray-400">
            <span v-if="emailTags.length > 0">
              {{ emailTags.length }} invitation(s) à envoyer
            </span>
            <span v-else>
              Prêt à inviter des membres
            </span>
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
              <MailIcon v-if="!submitting" class="w-4 h-4" />
              <div v-else class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
              {{ submitting ? 'Envoi en cours...' : `Inviter (${emailTags.length})` }}
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
import { 
  XIcon, 
  MailIcon, 
  AlertCircleIcon, 
  CheckCircleIcon,
  // BadgeCheckIcon,
  TrashIcon
} from '@/icons'

const props = defineProps({
  workspaceId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['close', 'invited'])

const { inviteMembers, getAvailableRoles } = useWorkspace()

const submitting = ref(false)
const generalError = ref(null)
const emailInput = ref('')
const emailTags = ref([])
const emailErrors = ref([])
const successResults = ref([])
const errorResults = ref([])

const availableRoles = getAvailableRoles()

const availablePermissions = ref([
  {
    key: 'can_create_projects',
    label: 'Créer des projets',
    description: 'Autoriser la création de nouveaux projets dans le workspace',
    recommended: true,
    disabled: false
  },
  {
    key: 'can_invite_members',
    label: 'Inviter des membres',
    description: 'Autoriser l\'invitation de nouveaux membres au workspace',
    recommended: false,
    disabled: false
  },
  {
    key: 'can_manage_settings',
    label: 'Gérer les paramètres',
    description: 'Autoriser la modification des paramètres du workspace',
    recommended: false,
    disabled: false
  }
])

const form = ref({
  role: 'member',
  message: '',
  permissions: {
    can_create_projects: true,
    can_invite_members: false,
    can_manage_settings: false
  },
  send_email: true
})

const canSubmit = computed(() => {
  return emailTags.value.length > 0 && form.value.role
})

// Email validation function
const isValidEmail = (email) => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

// Process email input
const processEmailInput = () => {
  const emails = emailInput.value
    .split(/[\n,]/)
    .map(email => email.trim())
    .filter(email => email.length > 0)
  
  emailTags.value = [...new Set(emails)] // Remove duplicates
  validateEmails()
}

// Handle paste event
const handlePaste = (event) => {
  const pastedData = event.clipboardData.getData('text')
  // Auto-process pasted emails
  setTimeout(() => {
    processEmailInput()
  }, 0)
}

// Validate emails
const validateEmails = () => {
  emailErrors.value = []
  
  emailTags.value.forEach(email => {
    if (!isValidEmail(email)) {
      emailErrors.value.push(`"${email}" n'est pas une adresse email valide`)
    }
  })
}

// Remove email tag
const removeEmailTag = (index) => {
  emailTags.value.splice(index, 1)
  updateEmailInput()
}

// Clear all emails
const clearEmails = () => {
  emailTags.value = []
  emailInput.value = ''
  emailErrors.value = []
}

// Update email input from tags
const updateEmailInput = () => {
  emailInput.value = emailTags.value.join(', ')
}

// Auto-set permissions based on role
watch(() => form.value.role, (newRole) => {
  if (newRole === 'admin') {
    form.value.permissions = {
      can_create_projects: true,
      can_invite_members: true,
      can_manage_settings: true
    }
    // Disable checkboxes for admin role
    availablePermissions.value.forEach(p => p.disabled = true)
  } else {
    // Re-enable checkboxes for other roles
    availablePermissions.value.forEach(p => p.disabled = false)
    
    if (newRole === 'member') {
      form.value.permissions = {
        can_create_projects: true,
        can_invite_members: false,
        can_manage_settings: false
      }
    } else if (newRole === 'viewer') {
      form.value.permissions = {
        can_create_projects: false,
        can_invite_members: false,
        can_manage_settings: false
      }
    }
  }
}, { immediate: true })

const handleSubmit = async () => {
  try {
    submitting.value = true
    generalError.value = null
    successResults.value = []
    errorResults.value = []

    // Final validation
    validateEmails()
    if (emailErrors.value.length > 0) {
      generalError.value = 'Veuillez corriger les erreurs dans les adresses email'
      return
    }

    if (emailTags.value.length === 0) {
      generalError.value = 'Veuillez saisir au moins une adresse email valide'
      return
    }

    if (!form.value.role) {
      generalError.value = 'Veuillez sélectionner un rôle'
      return
    }

    const response = await inviteMembers(props.workspaceId, {
      emails: emailTags.value,
      role: form.value.role,
      message: form.value.message,
      permissions: form.value.permissions,
      send_email: form.value.send_email
    })

    // Process results
    if (response.invitations) {
      response.invitations.forEach(invitation => {
        successResults.value.push({
          email: invitation.email,
          status: invitation.status
        })
      })
    }

    if (response.errors && response.errors.length > 0) {
      errorResults.value = response.errors
    }

    // If no errors, close modal after success
    if (errorResults.value.length === 0) {
      setTimeout(() => {
        emit('invited')
      }, 2000)
    }

  } catch (err) {
    generalError.value = err.response?.data?.message || 'Une erreur est survenue lors de l\'envoi des invitations'
    console.error('Error inviting members:', err)
  } finally {
    submitting.value = false
  }
}
</script>