<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 " @click.self="$emit('close')">
    <div ref="dialogRef" :style="dragStyle" class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 max-w-2xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
      <!-- Header -->
      <div ref="handleRef" class="px-8 py-6 border-b border-gray-200 dark:border-gray-700 cursor-move select-none">
          <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-3 flex items-center justify-center ">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
          </div>
          <div class="flex-1">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
              Ajouter un membre
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Inviter un utilisateur à rejoindre l'équipe
            </p>
          </div>
          <button
            @click="$emit('close')"
            class="p-2 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Error Alert -->
      <div v-if="errorMessage" class="mx-8 mt-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-3">
        <div class="flex items-start gap-3">
          <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div class="flex-1">
            <p class="font-medium text-red-800 dark:text-red-200">{{ errorMessage }}</p>
          </div>
        </div>
      </div>

      <!-- Form Body -->
      <div class="flex-1 overflow-y-auto px-8 py-6">
        <form @submit.prevent="handleSubmit" class="space-y-6">
          <!-- Section 1: Sélection de l'utilisateur -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              Utilisateur
            </h3>

            <div>
              <label for="user_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Sélectionner un utilisateur <span class="text-red-500">*</span>
              </label>
              <select
                v-model="formData.user_id"
                id="user_id"
                required
                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-green-500 focus:ring-4 focus:ring-green-500/10 transition-all"
              >
                <option value="">Choisir un utilisateur...</option>
                <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                  {{ user.nom }} ({{ user.email }})
                </option>
              </select>
              <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Seuls les utilisateurs qui ne font pas déjà partie de l'équipe sont affichés
              </p>
            </div>
          </div>

          <!-- Section 2: Rôle -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              Rôle dans l'équipe
            </h3>

            <div class="space-y-3">
              <label
                v-for="role in roleOptions"
                :key="role.value"
                class="relative flex items-start gap-3 p-4 border-2 rounded-3 cursor-pointer transition-all "
                :class="formData.role === role.value
                  ? 'border-green-500 bg-green-50 dark:bg-green-900/20'
                  : 'border-gray-300 dark:border-gray-600 hover:border-green-300 dark:hover:border-green-700'"
              >
                <input
                  type="radio"
                  v-model="formData.role"
                  :value="role.value"
                  class="sr-only"
                />
                <span class="text-2xl mt-0.5">{{ role.icon }}</span>
                <div class="flex-1">
                  <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    {{ role.label }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ role.description }}
                  </div>
                </div>
                <svg
                  v-if="formData.role === role.value"
                  class="absolute right-3 top-3 w-5 h-5 text-green-500"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
              </label>
            </div>
          </div>

          <!-- Info box -->
          <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-3">
            <div class="flex items-start gap-3">
              <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div class="text-sm text-blue-700 dark:text-blue-300">
                <p class="font-semibold mb-1">À propos des rôles :</p>
                <ul class="list-disc list-inside space-y-1 text-xs">
                  <li><strong>Admin :</strong> Peut gérer les membres et les paramètres</li>
                  <li><strong>Modérateur :</strong> Peut modérer le contenu et les discussions</li>
                  <li><strong>Membre :</strong> Accès standard aux fonctionnalités de l'équipe</li>
                </ul>
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- Footer -->
      <div class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
        <div class="flex justify-end gap-3">
          <button
            type="button"
            @click="$emit('close')"
            class="px-6 py-2.5 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-3 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all"
          >
            Annuler
          </button>
          <button
            type="submit"
            @click="handleSubmit"
            :disabled="loading || !formData.user_id"
            class="px-6 py-2.5 text-white font-semibold rounded-3 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
          >
            <span v-if="loading" class="flex items-center gap-2">
              <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Ajout en cours...
            </span>
            <span v-else>Ajouter le membre</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useDraggable } from '@/composables/useDraggable'
import api from '@/api/axios'

// Modale déplaçable par son en-tête.
const { dialogRef, handleRef, dragStyle, attachHandle } = useDraggable()

const props = defineProps({
  teamId: {
    type: String,
    required: true
  },
  existingMembers: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'saved'])

const loading = ref(false)
const errorMessage = ref('')
const allUsers = ref([])

const roleOptions = [
  {
    value: 'admin',
    label: 'Administrateur',
    icon: '👑',
    description: 'Peut gérer l\'équipe, les membres et toutes les ressources'
  },
  {
    value: 'moderator',
    label: 'Modérateur',
    icon: '⭐',
    description: 'Peut modérer le contenu et gérer les ressources'
  },
  {
    value: 'member',
    label: 'Membre',
    icon: '👤',
    description: 'Accès standard aux fonctionnalités de l\'équipe'
  }
]

const formData = reactive({
  user_id: '',
  role: 'member'
})

const availableUsers = computed(() => {
  const existingMemberIds = props.existingMembers.map(m => m.id)
  return allUsers.value.filter(user => !existingMemberIds.includes(user.id))
})

const loadUsers = async () => {
  try {
    const { data } = await api.get('/users')
    allUsers.value = data.data || []
  } catch (error) {
    console.error('Error loading users:', error)
  }
}

const handleSubmit = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await fetch(`/api/teams/${props.teamId}/members`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'include',
      body: JSON.stringify(formData)
    })

    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Une erreur est survenue')
    }

    emit('saved')
  } catch (error) {
    errorMessage.value = error.message
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  attachHandle()
  loadUsers()
})
</script>
