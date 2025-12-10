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
import { useInvitationPermissions } from '@/composables/useInvitationPermissions'
import { useWorkspacePermissions } from '@/composables/useWorkspacePermissions'
import { 
  XIcon, 
  MailIcon, 
  AlertCircleIcon, 
  CheckCircleIcon,
  TrashIcon
} from '@/icons'

const props = defineProps({
  workspaceId: {
    type: Number,
    required: true
  }
})

// Initialiser les permissions
const workspace = ref({ id: props.workspaceId })
const permissions = useWorkspacePermissions(workspace)
const invitationPermissions = useInvitationPermissions(workspace)

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

// Computed property pour les permissions disponibles
const availablePermissions = computed(() => {
  return invitationPermissions.getAvailablePermissionsForRole(form.value.role)
})

const form = ref({
  role: 'member',
  message: '',
  permissions: {
    can_create_projects: true,
    can_view_all_projects: false,
    can_invite_members: false,
    can_manage_settings: false,
    can_transfer_ownership: false,
    can_delete_members: false
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
  event.preventDefault()
  const pastedData = event.clipboardData.getData('text')
  emailInput.value = pastedData
  processEmailInput()
}

// Validate emails
const validateEmails = () => {
  emailErrors.value = []
  
  emailTags.value.forEach(email => {
    if (!isValidEmail(email)) {
      emailErrors.value.push(`"${email}" n'est pas une adresse email valide`)
    }
  })
  
  // Check for duplicate emails
  const uniqueEmails = new Set(emailTags.value)
  if (uniqueEmails.size !== emailTags.value.length) {
    emailErrors.value.push('Certaines adresses email sont en double')
  }
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
  successResults.value = []
  errorResults.value = []
}

// Update email input from tags
const updateEmailInput = () => {
  emailInput.value = emailTags.value.join(', ')
}

// Watch for role changes to update permissions
watch(() => form.value.role, (newRole) => {
  if (newRole) {
    const defaultPermissions = invitationPermissions.getDefaultPermissionsForRole(newRole)
    form.value.permissions = { ...defaultPermissions }
    
    // Validation optionnelle (pour le debug)
    const validation = invitationPermissions.validatePermissions(form.value.permissions, newRole)
    if (!validation.isValid) {
      console.warn('Permissions validation errors:', validation.errors)
    }
  }
}, { immediate: true })

// La fonction handleSubmit corrigée
const handleSubmit = async () => {
  try {
    submitting.value = true
    generalError.value = null
    successResults.value = []
    errorResults.value = []

    // 1. Validation de base des emails
    validateEmails()
    if (emailErrors.value.length > 0) {
      generalError.value = 'Veuillez corriger les erreurs dans les adresses email'
      submitting.value = false
      return
    }

    if (emailTags.value.length === 0) {
      generalError.value = 'Veuillez saisir au moins une adresse email valide'
      submitting.value = false
      return
    }

    if (!form.value.role) {
      generalError.value = 'Veuillez sélectionner un rôle'
      submitting.value = false
      return
    }

    // 2. Validation des permissions
    const permissionValidation = invitationPermissions.validatePermissions(form.value.permissions, form.value.role)
    if (!permissionValidation.isValid) {
      generalError.value = permissionValidation.errors.join(', ')
      submitting.value = false
      return
    }

    console.log('Données à envoyer:', {
      workspaceId: props.workspaceId,
      emails: emailTags.value,
      role: form.value.role,
      permissions: form.value.permissions,
      message: form.value.message,
      send_email: form.value.send_email
    })

    // 3. Envoi de l'invitation via l'API
    const response = await inviteMembers(props.workspaceId, {
      emails: emailTags.value,
      role: form.value.role,
      message: form.value.message,
      permissions: form.value.permissions,
      send_email: form.value.send_email
    })

    console.log('Réponse API:', response)

    // 4. Traitement des résultats
    if (response.data) {
      const { success_count, error_count, invitations = [], errors = [] } = response.data
      
      // Remplir les résultats de succès
      if (invitations && invitations.length > 0) {
        successResults.value = invitations.map(inv => ({
          email: inv.email,
          status: inv.status || 'invited'
        }))
      }
      
      // Remplir les erreurs
      if (errors && errors.length > 0) {
        errorResults.value = errors.map(err => ({
          email: err.email,
          message: err.message
        }))
      }

      // Afficher un message de synthèse
      if (success_count > 0) {
        generalError.value = null
        // Émettre l'événement d'invitation réussie
        setTimeout(() => {
          if (error_count === 0) {
            emit('invited')
            emit('close')
          }
        }, 3000)
      } else {
        generalError.value = 'Aucune invitation n\'a pu être envoyée'
      }
    } else {
      generalError.value = 'Réponse inattendue du serveur'
    }

  } catch (err) {
    console.error('Erreur détaillée lors de l\'invitation:', err)
    
    // Gestion d'erreur détaillée
    if (err.response) {
      // Erreur de réponse HTTP
      if (err.response.status === 422) {
        // Validation errors from Laravel
        const validationErrors = err.response.data.errors
        if (validationErrors) {
          const errorMessages = []
          Object.keys(validationErrors).forEach(key => {
            validationErrors[key].forEach(msg => errorMessages.push(msg))
          })
          generalError.value = errorMessages.join(', ')
        } else {
          generalError.value = err.response.data.message || 'Erreur de validation'
        }
      } else if (err.response.status === 403) {
        generalError.value = 'Vous n\'avez pas la permission d\'inviter des membres'
      } else if (err.response.status === 404) {
        generalError.value = 'Workspace non trouvé'
      } else {
        generalError.value = err.response.data?.message || `Erreur serveur (${err.response.status})`
      }
    } else if (err.request) {
      // Aucune réponse reçue
      generalError.value = 'Impossible de contacter le serveur. Vérifiez votre connexion.'
    } else {
      // Erreur de configuration
      generalError.value = err.message || 'Une erreur inattendue est survenue'
    }
  } finally {
    submitting.value = false
  }
}

// Debug: Afficher l'état du formulaire
watch(() => form.value, (newForm) => {
  console.log('Form updated:', newForm)
}, { deep: true })

watch(() => emailTags.value, (newTags) => {
  console.log('Email tags updated:', newTags)
})
</script>