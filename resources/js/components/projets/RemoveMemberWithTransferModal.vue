<!-- resources\js\components\projets\RemoveMemberWithTransferModal.vue -->
<template>
  <Teleport to="body">
    <div class="fixed inset-0 bg-black/30 flex items-center justify-center z-50 p-4">
      <div ref="dialogRef" :style="dragStyle" class="bg-white dark:bg-gray-800 rounded-3 max-w-2xl w-full" @click.stop>
        <!-- Header -->
        <div ref="handleRef" class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 cursor-move select-none">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-3">
              <AlertCircleIcon class="w-6 h-6 text-red-600 dark:text-red-400" />
            </div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
              Retirer {{ member.nom }}
            </h2>
          </div>

          <button
            @click="$emit('close')"
            class="p-2 text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <XIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Body -->
        <div class="p-6">
          <!-- Member Info -->
          <div class="flex items-center gap-3 mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-3">
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

          <!-- Loading -->
          <div v-if="loadingData" class="flex justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-600"></div>
          </div>

          <div v-else>
            <!-- Cas avec transfert requis -->
            <div v-if="requiresTransfer" class="space-y-4">
              <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-3 p-4">
                <div class="flex gap-3">
                  <AlertCircleIcon class="w-5 h-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-yellow-900 dark:text-yellow-300">
                      Ce membre possède encore des responsabilités dans ce projet
                    </p>
                    <p class="text-sm text-yellow-800 dark:text-yellow-400 mt-1">
                      Vous devez transférer ses responsabilités avant de le retirer.
                    </p>
                  </div>
                </div>
              </div>

              <div class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                <div v-if="removalImpact?.is_project_responsable">
                  • Responsable principal du projet
                </div>
                <div v-if="(removalImpact?.activities_count || 0) > 0">
                  • Responsable de {{ removalImpact.activities_count }} activité(s)
                </div>
                <div v-if="(removalImpact?.responsable_tasks_count || 0) > 0">
                  • Responsable de {{ removalImpact.responsable_tasks_count }} tâche(s)
                </div>
                <div v-if="(removalImpact?.assigned_tasks_count || 0) > 0">
                  • Assigné sur {{ removalImpact.assigned_tasks_count }} tâche(s)
                </div>
              </div>

              <div v-if="removalImpact?.activities?.length" class="space-y-2">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Activités concernées :
                </p>

                <div class="border border-gray-200 dark:border-gray-700 rounded-3 divide-y divide-gray-200 dark:divide-gray-700">
                  <div
                    v-for="activite in removalImpact.activities"
                    :key="activite.id"
                    class="px-4 py-3"
                  >
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ activite.nom }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                      {{ activite.code }}
                    </div>
                  </div>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Transférer à <span class="text-red-500">*</span>
                </label>

                <div v-if="loadingCandidates" class="flex justify-center py-4">
                  <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-brand-600"></div>
                </div>

                <select
                  v-else
                  v-model="selectedNewResponsable"
                  required
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                >
                  <option value="">Sélectionner un membre</option>
                  <option
                    v-for="candidate in transferCandidates"
                    :key="candidate.id"
                    :value="candidate.id"
                  >
                    {{ candidate.nom }}
                  </option>
                </select>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  Cette personne recevra les responsabilités transférables du membre retiré.
                </p>
              </div>
            </div>

            <!-- Cas simple -->
            <div
              v-else
              class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-3 p-4"
            >
              <div class="flex gap-3">
                <AlertCircleIcon class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                <div>
                  <p class="text-sm font-medium text-blue-900 dark:text-blue-300">
                    Confirmer le retrait
                  </p>
                  <p class="text-sm text-blue-800 dark:text-blue-400 mt-1">
                    Ce membre sera retiré du projet et perdra ses accès associés.
                  </p>
                </div>
              </div>
            </div>

            <!-- Actions automatiques -->
            <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-3">
              <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                Actions automatiques :
              </p>

              <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                <li class="flex items-center gap-2">
                  <CheckIcon class="w-3 h-3 text-gray-400" />
                  Retrait du projet
                </li>
                <li class="flex items-center gap-2">
                  <CheckIcon class="w-3 h-3 text-gray-400" />
                  Suppression des assignations de tâches
                </li>
                <li class="flex items-center gap-2">
                  <CheckIcon class="w-3 h-3 text-gray-400" />
                  Suppression des accès membres sur les activités
                </li>
                <li v-if="requiresTransfer" class="flex items-center gap-2">
                  <CheckIcon class="w-3 h-3 text-gray-400" />
                  Transfert des responsabilités projet / activités / tâches
                </li>
              </ul>
            </div>
          </div>

          <!-- Error -->
          <div
            v-if="error"
            class="mt-4 p-4 rounded-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800"
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
            class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
          >
            Annuler
          </button>

          <button
            @click="handleRemove"
            :disabled="submitting || (requiresTransfer && !selectedNewResponsable)"
            class="px-6 py-2 bg-red-600 text-white rounded-3 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
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
import { ref, onMounted, computed } from 'vue'
import { useProjetInvitations } from '@/composables/useProjetInvitations'
import { useDraggable } from '@/composables/useDraggable'

const { dialogRef, handleRef, dragStyle, attachHandle } = useDraggable()
onMounted(attachHandle)
import { XIcon, AlertCircleIcon, CheckIcon } from '@/icons'

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
    default: 'projet',
    validator: (value) => ['workspace', 'projet'].includes(value)
  },
  projetId: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['close', 'removed'])

const { getProjectMemberRemovalImpact, removeMemberWithTransfer } = useProjetInvitations()

const loadingData = ref(false)
const loadingCandidates = ref(false)
const submitting = ref(false)
const error = ref(null)

const removalImpact = ref(null)
const transferCandidates = ref([])
const selectedNewResponsable = ref('')

const requiresTransfer = computed(() => {
  return removalImpact.value?.requires_transfer || false
})

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
    loadingData.value = true
    loadingCandidates.value = true
    error.value = null

    if (props.context !== 'projet' || !props.projetId) {
      error.value = 'Le retrait de membre au niveau projet nécessite un projet valide.'
      return
    }

    const impact = await getProjectMemberRemovalImpact(props.projetId, props.member.id)
    removalImpact.value = impact
    transferCandidates.value = impact?.candidates || []
  } catch (err) {
    console.error('Error loading removal impact:', err)
    error.value = err.response?.data?.message || 'Erreur lors du chargement des données'
  } finally {
    loadingData.value = false
    loadingCandidates.value = false
  }
}

const handleRemove = async () => {
  try {
    submitting.value = true
    error.value = null

    if (requiresTransfer.value && !selectedNewResponsable.value) {
      error.value = 'Veuillez sélectionner un membre à qui transférer les responsabilités'
      return
    }

    const result = await removeMemberWithTransfer(
      props.projetId,
      props.member.id,
      selectedNewResponsable.value || null
    )

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