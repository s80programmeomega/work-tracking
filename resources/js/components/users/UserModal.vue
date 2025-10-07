<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" @click.self="$emit('close')">
    <div class="flex items-center justify-center min-h-screen px-4">
      <div class="fixed inset-0 bg-black opacity-50"></div>

      <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ mode === 'create' ? 'Créer un utilisateur' : mode === 'view' ? 'Détails de l\'utilisateur' : 'Modifier l\'utilisateur' }}
          </h3>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Name -->
          <div>
            <label for="nom" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Nom complet <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.nom"
              type="text"
              id="nom"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              :class="{ 'border-red-500': errors.nom }"
              :disabled="mode === 'view'"
              required
            />
            <p v-if="errors.nom" class="mt-1 text-sm text-red-500">{{ errors.nom[0] }}</p>
          </div>

          <!-- Email -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Email <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.email"
              type="email"
              id="email"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              :class="{ 'border-red-500': errors.email }"
              :disabled="mode === 'view'"
              required
            />
            <p v-if="errors.email" class="mt-1 text-sm text-red-500">{{ errors.email[0] }}</p>
          </div>

          <!-- Password (only for create) -->
          <div v-if="mode === 'create'">
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Mot de passe <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.password"
              type="password"
              id="password"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              :class="{ 'border-red-500': errors.password }"
              :required="mode === 'create'"
            />
            <p v-if="errors.password" class="mt-1 text-sm text-red-500">{{ errors.password[0] }}</p>
          </div>

          <!-- Role & Function Row -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Rôle <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.role"
                id="role"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                :disabled="mode === 'view'"
                required
              >
                <option value="">Sélectionner un rôle</option>
                <option value="super_admin">Super Admin</option>
                <option value="manager">Manager</option>
                <option value="responsable_n1">Responsable N1</option>
                <option value="responsable_n2">Responsable N2</option>
                <option value="cadre">Cadre</option>
                <option value="stagiaire">Stagiaire</option>
              </select>
            </div>

            <div>
              <label for="fonction" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Fonction
              </label>
              <input
                v-model="form.fonction"
                type="text"
                id="fonction"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                :disabled="mode === 'view'"
              />
            </div>
          </div>

          <!-- Phone & Team Row -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="numero_telephone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Téléphone
              </label>
              <input
                v-model="form.numero_telephone"
                type="tel"
                id="numero_telephone"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                :disabled="mode === 'view'"
              />
            </div>

            <div>
              <label for="team_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Équipe
              </label>
              <select
                v-model="form.team_id"
                id="team_id"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                :disabled="mode === 'view'"
              >
                <option :value="null">Aucune équipe</option>
                <!-- TODO: Load teams dynamically -->
              </select>
            </div>
          </div>

          <!-- Bio -->
          <div>
            <label for="bio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Bio
            </label>
            <textarea
              v-model="form.bio"
              id="bio"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              :disabled="mode === 'view'"
            ></textarea>
          </div>

          <!-- Footer -->
          <div class="flex justify-end gap-3 pt-4">
            <button
              @click="$emit('close')"
              type="button"
              class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition"
            >
              {{ mode === 'view' ? 'Fermer' : 'Annuler' }}
            </button>
            <button
              v-if="mode !== 'view'"
              type="submit"
              :disabled="submitting"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50"
            >
              {{ submitting ? 'Enregistrement...' : mode === 'create' ? 'Créer' : 'Mettre à jour' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useUsers } from '@/composables/useUsers'

const props = defineProps({
  user: {
    type: Object,
    default: null,
  },
  mode: {
    type: String,
    default: 'create',
    validator: (value) => ['create', 'edit', 'view'].includes(value),
  },
})

const emit = defineEmits(['close', 'save'])

const { createUser, updateUser } = useUsers()

const submitting = ref(false)
const errors = ref({})

const form = reactive({
  nom: props.user?.nom || '',
  email: props.user?.email || '',
  password: '',
  role: props.user?.role || '',
  fonction: props.user?.fonction || '',
  numero_telephone: props.user?.numero_telephone || '',
  team_id: props.user?.team_id || null,
  bio: props.user?.bio || '',
})

const handleSubmit = async () => {
  submitting.value = true
  errors.value = {}

  try {
    if (props.mode === 'create') {
      await createUser(form)
    } else {
      await updateUser(props.user.id, form)
    }

    emit('save')
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    }
  } finally {
    submitting.value = false
  }
}
</script>
