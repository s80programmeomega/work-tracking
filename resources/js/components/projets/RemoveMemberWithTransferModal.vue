<template>
  <Teleport to="body">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div
        class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full"
        @click.stop
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
              <AlertCircleIcon class="w-6 h-6 text-red-600 dark:text-red-400" />
            </div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
              Retirer {{ member.nom }}
            </h2>
          </div>
          <button
            @click="$emit('close')"
            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <XIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Body -->
        <div class="p-6">
          <!-- Member Info -->
          <div class="flex items-center gap-3 mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <div
              v-if="member.avatar"
              class="w-12 h-12 rounded-full overflow-hidden flex-shrink-0"
            >
              <img :src="member.avatar" :alt="member.nom" class="w-full h-full object-cover" />
            </div>
            <div
              v-else
              class="w-12 h-12 rounded-full bg-brand-600 flex items-center justify-center text-white font-medium flex-shrink-0"
            >
              {{ getInitials(member.nom) }}
            </div>
            <div class="flex-1">
              <div class="text-sm font-medium text-gray-900 dark:text-white">
                {{ member.nom }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400">
                {{ member.email }}
              </div>
            </div>
          </div>

          <!-- Loading projects -->
          <div v-if="loadingProjects" class="flex justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-600"></div>
          </div>

          <div v-else>
            <!-- Warning if responsible for projects -->
            <div v-if="responsableProjects.length > 0" class="space-y-4">
              <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <div class="flex gap-3">
                  <AlertCircleIcon class="w-5 h-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-yellow-900 dark:text-yellow-300">
                      Ce membre est responsable de {{ responsableProjects.length }} projet(s)
                    </p>
                    <p class="text-sm text-yellow-800 dark:text-yellow-400 mt-1">
                      Vous devez transférer la responsabilité de ces projets avant de le retirer.
                    </p>
                  </div>
                </div>
              </div>

              <!-- Projects list -->
              <div class="space-y-2">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Projets concernés :
                </p>
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-200 dark:divide-gray-700">
                  <div
                    v-for="projet in responsableProjects"
                    :key="projet.id"
                    class="px-4 py-3 flex items-center gap-3"
                  >
                    <div
                      class="w-3 h-3 rounded-full flex-shrink-0"
                      :style="{ backgroundColor: projet.couleur || '#3B82F6' }"
                    ></div>
                    <div class="flex-1 min-w-0">
                      <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                        {{ projet.nom }}
                      </div>
                      <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ projet.code }}
                      </div>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                      {{ projet.activites_count || 0 }} activité(s)
                    </span>
                  </div>
                </div>
              </div>

              <!-- Transfer selection -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Transférer à <span class="text-red-500">*</span>
                </label>

                <!-- Loading candidates -->
                <div v-if="loadingCandidates" class="flex justify-center py-4">
                  <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-brand-600"></div>
                </div>

                <!-- Candidates list -->
                <select
                  v-else
                  v-model="selectedNewResponsable"
                  required
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                >
                  <option value="">Sélectionner un membre</option>
                  <optgroup v-if="workspace" label="Owner du workspace">
                    <option :value="workspace.owner_id">
                      {{ workspace.owner?.nom || 'Owner' }} (Propriétaire)
                    </option>
                  </optgroup>
                  <optgroup v-if="transferCandidates.length > 0" label="Autres membres">
                    <option
                      v-for="candidate in transferCandidates"
                      :key="candidate.id"
                      :value="candidate.id"
                    >
                      {{ candidate.nom }}
                    </option>
                  </optgroup>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  Cette personne deviendra responsable des {{ responsableProjects.length }} projet(s)
                </p>
              </div>
            </div>

            <!-- Simple confirmation if not responsible -->
            <div v-else class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
              <div class="flex gap-3">
                <AlertCircleIcon class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                <div>
                  <p class="text-sm font-medium text-blue-900 dark:text-blue-300">
                    Confirmer le retrait
                  </p>
                  <p class="text-sm text-blue-800 dark:text-blue-400 mt-1">
                    Ce membre perdra l'accès au {{ context === 'workspace' ? 'workspace' : 'projet' }} et à toutes ses ressources.
                  </p>
                </div>
              </div>
            </div>

            <!-- Actions impact -->
            <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
              <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                Actions automatiques :
              </p>
              <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                <li class="flex items-center gap-2">
                  <CheckIcon class="w-3 h-3 text-gray-400" />
                  Retrait de tous les projets
                </li>
                <li class="flex items-center gap-2">
                  <CheckIcon class="w-3 h-3 text-gray-400" />
                  Désassignation de toutes les tâches
                </li>
                <li class="flex items-center gap-2">
                  <CheckIcon class="w-3 h-3 text-gray-400" />
                  Révocation des accès aux documents
                </li>
                <li v-if="responsableProjects.length > 0" class="flex items-center gap-2">
                  <CheckIcon class="w-3 h-3 text-gray-400" />
                  Transfert de {{ responsableProjects.length }} projet(s)
                </li>
              </ul>
            </div>
          </div>

          <!-- Error Message -->
          <div
            v-if="error"
            class="mt-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800"
          >
            <p class="text-sm text-red-800 dark:text-red-400">
              {{ error }}
            </p>
          </div>
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
            @click="handleRemove"
            :disabled="submitting || (responsableProjects.length > 0 && !selectedNewResponsable)"
            class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
          >
            <span v-if="submitting" class="animate-spin">⏳</span>
            Retirer définitivement
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useProjetInvitations } from '@/composables/useProjetInvitations'
import { useWorkspace } from '@/composables/useWorkspace'
import { XIcon, AlertCircleIcon, CheckIcon } from '@/icons'
import api from '@/api/axios'

const props = defineProps({
  member: {
    type: Object,
    required: true
  },
  workspaceId: {
    type: Number,
    required: true
  },
  context: {
    type: String,
    default: 'workspace', // 'workspace' ou 'projet'
    validator: (value) => ['workspace', 'projet'].includes(value)
  },
  projetId: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['close', 'removed'])

const { getUserProjects, getTransferCandidates, removeMemberWithTransfer } = useProjetInvitations()
const { fetchWorkspace } = useWorkspace()

const loadingProjects = ref(false)
const loadingCandidates = ref(false)
const submitting = ref(false)
const error = ref(null)

const responsableProjects = ref([])
const transferCandidates = ref([])
const selectedNewResponsable = ref('')
const workspace = ref(null)

const getInitials = (name) => {
  if (!name) return 'U'
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const loadData = async () => {
  try {
    loadingProjects.value = true
    loadingCandidates.value = true

    // Charger le workspace
    workspace.value = await fetchWorkspace(props.workspaceId)

    // Charger les projets où le membre est responsable
    const projects = await getUserProjects(props.workspaceId, props.member.id)
    responsableProjects.value = projects || []

    // Charger les candidats pour le transfert
    if (responsableProjects.value.length > 0) {
      const candidates = await getTransferCandidates(props.workspaceId, props.member.id)
      transferCandidates.value = candidates || []
    }

  } catch (err) {
    console.error('Error loading data:', err)
    error.value = 'Erreur lors du chargement des données'
  } finally {
    loadingProjects.value = false
    loadingCandidates.value = false
  }
}

const handleRemove = async () => {
  try {
    submitting.value = true
    error.value = null

    if (responsableProjects.value.length > 0 && !selectedNewResponsable.value) {
      error.value = 'Veuillez sélectionner un nouveau responsable'
      return
    }

    let result

    if (props.context === 'workspace') {
      // Retrait du workspace
      result = await api.delete(
        `/workspaces/${props.workspaceId}/members/${props.member.id}/remove`,
        {
          data: {
            new_responsable_id: selectedNewResponsable.value || null
          }
        }
      )
    } else {
      // Retrait du projet
      result = await removeMemberWithTransfer(
        props.projetId,
        props.member.id,
        selectedNewResponsable.value || null
      )
    }

    emit('removed', result.data)

  } catch (err) {
    error.value = err.response?.data?.message || 'Une erreur est survenue'
    console.error('Error removing member:', err)
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>