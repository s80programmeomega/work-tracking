<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 " @click.self="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-3 max-w-3xl w-full mx-2 sm:mx-4 max-h-[90vh] overflow-hidden flex flex-col">
      <!-- Header -->
      <div class="px-4 py-4 sm:px-8 sm:py-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-3 flex items-center justify-center ">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
          </div>
          <div class="flex-1">
            <h2 class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white">
              {{ projet ? 'Modifier le projet' : 'Nouveau projet' }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ projet ? 'Mettre à jour les informations du projet' : 'Créer un nouveau projet pour votre organisation' }}
            </p>
             <!-- ✅ Affichage du workspace courant -->
            <div v-if="!projet" class="flex items-center gap-2 mt-2 text-xs text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-3 py-1.5 rounded-3 w-fit">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
              <span>Workspace : {{ currentWorkspaceName }}</span>
            </div>
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
      <div class="flex-1 overflow-y-auto px-4 py-4 sm:px-8 sm:py-6">
        <form @submit.prevent="handleSubmit" class="space-y-6">
          <!-- Section 1: Informations générales -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Informations générales
            </h3>

            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Nom du projet <span class="text-red-500">*</span>
              </label>
              <input
                v-model="formData.nom"
                type="text"
                required
                placeholder="Ex: Transformation digitale 2025"
                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all"
              />
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Description
              </label>
              <textarea
                v-model="formData.description"
                rows="3"
                placeholder="Décrivez les objectifs et le contexte du projet..."
                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all resize-none"
              ></textarea>
            </div>
          </div>

          <!-- Section 2: Planification -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              Planification
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Date de début <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="formData.date_debut"
                  type="date"
                  required
                  class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all"
                />
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Date de fin
                </label>
                <input
                  v-model="formData.date_fin"
                  type="date"
                  class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all"
                />
              </div>
            </div>
          </div>

          <!-- Section 3: Responsable & Progression -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              Responsable & Progression
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Responsable <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="formData.responsable_id"
                  required
                  class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all"
                >
                  <option value="">Sélectionner un responsable</option>
                  <option v-for="user in users" :key="user.id" :value="user.id">
                    {{ user.prenom ? user.prenom + ' ' + user.nom : user.nom }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Progression ({{ formData.progression }}%)
                </label>
                <input
                  v-model.number="formData.progression"
                  type="range"
                  min="0"
                  max="100"
                  step="5"
                  class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full appearance-none cursor-pointer accent-blue-500"
                />
                <div class="mt-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                  <div
                    class="h-2 rounded-full transition-all duration-300"
                    :style="{ width: `${formData.progression}%` }"
                  ></div>
                </div>
                <div class="flex justify-between mt-1 text-xs text-gray-500 dark:text-gray-400">
                  <span>0%</span>
                  <span>50%</span>
                  <span>100%</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Section 4: Statut -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
              Statut
            </h3>

            <div class="space-y-2">
              <label
                v-for="statut in statusOptions"
                :key="statut.value"
                class="relative flex items-center gap-3 p-3 border-2 rounded-3 cursor-pointer transition-all"
                :class="formData.status === statut.value
                  ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                  : 'border-gray-300 dark:border-gray-600 hover:border-blue-300 dark:hover:border-blue-700'"
              >
                <input
                  type="radio"
                  v-model="formData.status"
                  :value="statut.value"
                  class="sr-only"
                />
                <span :class="statut.color" class="w-3 h-3 rounded-full shrink-0"></span>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ statut.label }}</span>
                <svg v-if="formData.status === statut.value" class="absolute right-3 w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
              </label>
            </div>
          </div>

          <!-- Section 5: Intégration équipes -->
          <div class="space-y-3">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              Intégration équipes
            </h3>

            <!-- Toggle -->
            <div
              class="flex items-start gap-4 p-4 border-2 rounded-3 transition-all"
              :class="formData.use_teams ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-300 dark:border-gray-600'"
            >
              <div class="flex-1">
                <p class="text-sm font-semibold text-gray-900 dark:text-white">Gérer les membres via des équipes</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                  Les membres seront assignés en liant une ou plusieurs équipes. Les membres restent si une équipe est supprimée.
                </p>
              </div>
              <button
                type="button"
                role="switch"
                :aria-checked="formData.use_teams"
                @click="formData.use_teams = !formData.use_teams"
                class="relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors focus:outline-none"
                :class="formData.use_teams ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700'"
              >
                <span
                  class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform transition-transform"
                  :class="formData.use_teams ? 'translate-x-5' : 'translate-x-0'"
                />
              </button>
            </div>

            <!-- Team picker (visible when use_teams is on) -->
            <div v-if="formData.use_teams" class="border border-gray-200 dark:border-gray-700 rounded-3 overflow-hidden">
              <div class="px-3 py-2 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">
                  Équipes à lier
                  <span v-if="selectedTeamIds.length" class="ml-1 text-indigo-600 dark:text-indigo-400">({{ selectedTeamIds.length }} sélectionnée{{ selectedTeamIds.length > 1 ? 's' : '' }})</span>
                </span>
              </div>

              <div v-if="loadingTeams" class="p-4 text-center text-sm text-gray-500 dark:text-gray-400">
                Chargement des équipes…
              </div>
              <div v-else-if="workspaceTeams.length === 0" class="p-4 text-center text-sm text-gray-500 dark:text-gray-400">
                Aucune équipe disponible dans ce workspace.
              </div>
              <div v-else class="divide-y divide-gray-100 dark:divide-gray-700 max-h-44 overflow-y-auto">
                <label
                  v-for="team in workspaceTeams"
                  :key="team.id"
                  class="flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                  :class="selectedTeamIds.includes(team.id) ? 'bg-indigo-50 dark:bg-indigo-900/20' : ''"
                >
                  <input
                    type="checkbox"
                    :checked="selectedTeamIds.includes(team.id)"
                    @change="toggleTeamSelection(team.id)"
                    class="w-4 h-4 text-indigo-600 rounded border-gray-300 dark:border-gray-600 focus:ring-indigo-500"
                  />
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ team.name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ team.members_count ?? team.members?.length ?? 0 }} membre{{ (team.members_count ?? 0) !== 1 ? 's' : '' }}</p>
                  </div>
                  <svg v-if="selectedTeamIds.includes(team.id)" class="w-4 h-4 text-indigo-600 dark:text-indigo-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                </label>
              </div>
            </div>
          </div>
        </form>
      </div>

       <!-- Footer -->
      <div class="px-4 py-4 sm:px-8 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
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
            :disabled="loading"
            class="px-6 py-2.5 text-white font-semibold rounded-3 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
          >
            <span v-if="loading" class="flex items-center gap-2">
              <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Enregistrement...
            </span>
            <span v-else>{{ projet ? 'Mettre à jour' : 'Créer le projet' }}</span>
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { useProjets } from '@/composables/useProjets'
import api from '@/api/axios'
import { useWorkspace } from '@/composables/useWorkspace'

const props = defineProps({
  projet: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

const authStore = useAuthStore()
const { createProjet, updateProjet } = useProjets()

// ✅ UTILISER LE WORKSPACE COURANT
const { 
  currentWorkspace, 
  currentWorkspaceId, 
  currentWorkspaceName 
} = useWorkspace()

const loading = ref(false)
const users = ref([])
const errorMessage = ref('')

// ── Intégration équipes ───────────────────────────────────────────────────────
const workspaceTeams = ref([])
const loadingTeams = ref(false)
const selectedTeamIds = ref([])

const loadTeams = async () => {
  const workspaceId = currentWorkspaceId.value
  if (!workspaceId) { return }
  loadingTeams.value = true
  try {
    const { data } = await api.get('/teams', { params: { workspace_id: workspaceId, per_page: 100 } })
    // API retourne { success, teams: { data: [...] } }
    workspaceTeams.value = data.teams?.data ?? data.teams ?? data.data ?? []
  } catch {
    workspaceTeams.value = []
  } finally {
    loadingTeams.value = false
  }
}

const toggleTeamSelection = (teamId) => {
  const idx = selectedTeamIds.value.indexOf(teamId)
  if (idx === -1) {
    selectedTeamIds.value.push(teamId)
  } else {
    selectedTeamIds.value.splice(idx, 1)
  }
}

watch(() => formData.value.use_teams, (enabled) => {
  if (enabled && workspaceTeams.value.length === 0) {
    loadTeams()
  }
  if (!enabled) {
    selectedTeamIds.value = []
  }
})

const statusOptions = [
  { value: 'active', label: 'Actif', color: 'bg-green-500' },
  { value: 'completed', label: 'Terminé', color: 'bg-blue-500' },
  { value: 'archived', label: 'Archivé', color: 'bg-gray-500' }
]

const formData = ref({
  nom: '',
  description: '',
  date_debut: '',
  date_fin: '',
  responsable_id: authStore.user?.id,
  progression: 0,
  status: 'active',
  use_teams: false,
  // ✅ AJOUTER LE WORKSPACE_ID AUTOMATIQUEMENT
  workspace_id: currentWorkspaceId.value
})

const loadUsers = async () => {
  const workspaceId = currentWorkspaceId.value
  if (!workspaceId) { return }
  try {
    const { data } = await api.get(`/workspaces/${workspaceId}/users`)
    users.value = data.data || data || []
  } catch (error) {
    console.error('Erreur lors du chargement des utilisateurs:', error)
  }
}

const handleSubmit = async () => {
  // ✅ VALIDER QUE LE WORKSPACE EST DISPONIBLE
  if (!currentWorkspaceId.value && !props.projet) {
    errorMessage.value = 'Aucun workspace sélectionné. Veuillez sélectionner un workspace avant de créer un projet.'
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
     const dataToSend = {
      ...formData.value,
      // ✅ S'ASSURER QUE LE WORKSPACE_ID EST TOUJOURS INCLUS
      workspace_id: props.projet ? formData.value.workspace_id : currentWorkspaceId.value
    }

    if (props.projet) {
      await updateProjet(props.projet.id, formData.value)
    } else {
      const created = await createProjet(formData.value)
      // Lier les équipes sélectionnées après création
      if (formData.value.use_teams && selectedTeamIds.value.length && created?.id) {
        await Promise.all(
          selectedTeamIds.value.map((teamId) =>
            api.post(`/projets/${created.id}/teams`, { team_id: teamId }).catch(() => {})
          )
        )
      }
    }
    emit('saved')
  } catch (error) {
    console.error(error)

    if (error.response && error.response.data && error.response.data.message) {
      errorMessage.value = error.response.data.message
    } else if (error.message === 'Network Error') {
      errorMessage.value = 'Erreur de connexion. Veuillez vérifier votre connexion internet.'
    } else {
      errorMessage.value = 'Une erreur s\'est produite lors de l\'enregistrement du projet.'
    }
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadUsers()

  if (props.projet) {
    formData.value = {
      nom: props.projet.nom || '',
      description: props.projet.description || '',
      date_debut: props.projet.date_debut || '',
      date_fin: props.projet.date_fin || '',
      responsable_id: props.projet.responsable_id || authStore.user?.id,
      progression: props.projet.progression || 0,
      status: props.projet.status || 'active',
      workspace_id: props.projet.workspace_id,
      use_teams: props.projet.use_teams ?? false
    }
  } else {
    // ✅ EN CRÉATION, FORCER LE WORKSPACE COURANT
    formData.value.workspace_id = currentWorkspaceId.value
  }
  
})
</script>
