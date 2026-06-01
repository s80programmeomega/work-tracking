<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="$emit('close')">
    <div ref="dialogRef" :style="dragStyle" class="bg-white dark:bg-gray-800 rounded-3 max-w-3xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
      <!-- Header -->
      <div ref="handleRef" class="px-8 py-6 border-b border-gray-200 dark:border-gray-700 cursor-move select-none">
          <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-3 flex items-center justify-center ">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <div class="flex-1">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ mode === 'create' ? 'Nouvel utilisateur' : mode === 'view' ? 'Détails de l\'utilisateur' : 'Modifier l\'utilisateur' }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ mode === 'create' ? 'Créer un nouveau compte utilisateur' : mode === 'view' ? 'Informations du compte utilisateur' : 'Mettre à jour les informations du compte' }}
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
      <div v-if="Object.keys(errors).length > 0" class="mx-8 mt-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-3">
        <div class="flex items-start gap-3">
          <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div class="flex-1">
            <p class="font-medium text-red-800 dark:text-red-200">Veuillez corriger les erreurs suivantes :</p>
            <ul class="mt-2 space-y-1">
              <li v-for="(error, field) in errors" :key="field" class="text-sm text-red-700 dark:text-red-300 flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                {{ error[0] }}
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Form Body -->
      <div class="flex-1 overflow-y-auto px-8 py-6">
        <form @submit.prevent="handleSubmit" class="space-y-6">
          <!-- Section 1: Informations personnelles -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              Informations personnelles
            </h3>

            <div>
              <label for="nom" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Nom complet <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.nom"
                type="text"
                id="nom"
                placeholder="Ex: Jean Dupont"
                class="w-full px-4 py-3 border-2 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 transition-all"
                :class="errors.nom
                  ? 'border-red-500 focus:border-red-500 focus:ring-4 focus:ring-red-500/10'
                  : 'border-gray-300 dark:border-gray-600 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10'"
                :disabled="mode === 'view'"
                required
              />
            </div>

            <div>
              <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Adresse email <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.email"
                type="email"
                id="email"
                placeholder="jean.dupont@example.com"
                class="w-full px-4 py-3 border-2 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 transition-all"
                :class="errors.email
                  ? 'border-red-500 focus:border-red-500 focus:ring-4 focus:ring-red-500/10'
                  : 'border-gray-300 dark:border-gray-600 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10'"
                :disabled="mode === 'view'"
                required
              />
            </div>

            <div v-if="mode === 'create'">
              <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Mot de passe <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.password"
                type="password"
                id="password"
                placeholder="••••••••"
                class="w-full px-4 py-3 border-2 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 transition-all"
                :class="errors.password
                  ? 'border-red-500 focus:border-red-500 focus:ring-4 focus:ring-red-500/10'
                  : 'border-gray-300 dark:border-gray-600 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10'"
                :required="mode === 'create'"
              />
              <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Minimum 8 caractères recommandés
              </p>
            </div>

            <div>
              <label for="numero_telephone" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Numéro de téléphone
              </label>
              <input
                v-model="form.numero_telephone"
                type="tel"
                id="numero_telephone"
                placeholder="+33 6 12 34 56 78"
                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all"
                :disabled="mode === 'view'"
              />
            </div>
          </div>

          <!-- Section 2: Rôle & Fonction -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              Rôle & Fonction
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="role" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Rôle <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.role"
                  id="role"
                  class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all"
                  :disabled="mode === 'view'"
                  required
                >
                  <option value="">Sélectionner un rôle</option>
                  <option v-for="role in roleOptions" :key="role.value" :value="role.value">
                    {{ role.label }}
                  </option>
                </select>
              </div>

              <div>
                <label for="fonction" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Fonction
                </label>
                <input
                  v-model="form.fonction"
                  type="text"
                  id="fonction"
                  placeholder="Ex: Chef de projet"
                  class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all"
                  :disabled="mode === 'view'"
                />
              </div>
            </div>

            <!-- Role Badges Display -->
            <div v-if="form.role" class="flex items-center gap-2 p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-3">
              <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300">
                Rôle sélectionné : {{ roleOptions.find(r => r.value === form.role)?.label }}
              </span>
            </div>
          </div>

          <!-- Section 3: Équipe & Organisation -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              Équipe & Organisation
            </h3>

            <div>
              <label for="team_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Équipe
              </label>
              <select
                v-model="form.team_id"
                id="team_id"
                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all"
                :disabled="mode === 'view'"
              >
                <option :value="null">Aucune équipe</option>
                <option v-for="team in teams" :key="team.id" :value="team.id">
                  {{ team.name }}
                </option>
              </select>
              <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                L'utilisateur sera membre de cette équipe
              </p>
            </div>
          </div>

          <!-- Section 4: Biographie -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Biographie
            </h3>

            <div>
              <label for="bio" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                À propos de l'utilisateur
              </label>
              <textarea
                v-model="form.bio"
                id="bio"
                rows="3"
                placeholder="Décrivez l'utilisateur, ses compétences, son expérience..."
                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all resize-none"
                :disabled="mode === 'view'"
              ></textarea>
              <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Maximum 500 caractères
              </p>
            </div>
          </div>
        </form>
      </div>

      <!-- Footer -->
      <div class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
        <div class="flex justify-end gap-3">
          <button
            @click="$emit('close')"
            type="button"
            class="px-6 py-2.5 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-3 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all"
          >
            {{ mode === 'view' ? 'Fermer' : 'Annuler' }}
          </button>
          <button
            v-if="mode !== 'view'"
            @click="handleSubmit"
            type="submit"
            :disabled="submitting"
            class="px-6 py-2.5 text-white font-semibold rounded-3 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
          >
            <span v-if="submitting" class="flex items-center gap-2">
              <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Enregistrement...
            </span>
            <span v-else>{{ mode === 'create' ? 'Créer l\'utilisateur' : 'Mettre à jour' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useUsers } from '@/composables/useUsers'
import { useDraggable } from '@/composables/useDraggable'

const { dialogRef, handleRef, dragStyle, attachHandle } = useDraggable()
onMounted(attachHandle)
import { useTeams } from '@/composables/useTeams'

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
const { teams, loadTeams } = useTeams()

const submitting = ref(false)
const errors = ref({})

const roleOptions = [
  { value: 'super_admin', label: 'Super Admin' },
  { value: 'manager', label: 'Manager' },
  { value: 'responsable_n1', label: 'Responsable N1' },
  { value: 'responsable_n2', label: 'Responsable N2' },
  { value: 'cadre', label: 'Cadre' },
  { value: 'stagiaire', label: 'Stagiaire' }
]

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

onMounted(async () => {
  // Load teams for the select dropdown
  await loadTeams()
})
</script>
