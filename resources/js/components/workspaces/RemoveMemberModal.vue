<template>
  <Teleport to="body">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div
        class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto"
        @click.stop
      >
        <!-- Header -->
        <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 z-10">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                <AlertCircleIcon class="w-6 h-6 text-red-600 dark:text-red-400" />
              </div>
              <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                  Retirer {{ member.nom }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                  {{ context === 'workspace' ? 'du workspace' : 'du projet' }}
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
            <div
              v-if="member.pivot?.role"
              :class="[
                'px-2.5 py-1 rounded-full text-xs font-medium',
                getRoleColor(member.pivot.role)
              ]"
            >
              {{ getRoleLabel(member.pivot.role) }}
            </div>
          </div>

          <!-- Loading preview -->
          <div v-if="loadingPreview" class="flex flex-col items-center justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-600 mb-4"></div>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Analyse de l'impact du retrait...
            </p>
          </div>

          <div v-else-if="preview">
            <!-- Impact Summary -->
            <div class="mb-6">
              <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                Impact du retrait
              </h3>
              
              <!-- Stats Grid -->
              <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                  <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                    {{ preview.impact.projets_as_responsable }}
                  </div>
                  <div class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                    Projet(s) responsable
                  </div>
                </div>
                
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3">
                  <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                    {{ preview.impact.activites_as_responsable }}
                  </div>
                  <div class="text-xs text-purple-700 dark:text-purple-300 mt-1">
                    Activité(s) responsable
                  </div>
                </div>
                
                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-3">
                  <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                    {{ preview.impact.taches_non_terminees }}
                  </div>
                  <div class="text-xs text-orange-700 dark:text-orange-300 mt-1">
                    Tâche(s) non terminée(s)
                  </div>
                </div>
                
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                  <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">
                    {{ preview.impact.projets_as_member }}
                  </div>
                  <div class="text-xs text-gray-700 dark:text-gray-300 mt-1">
                    Total projets
                  </div>
                </div>
              </div>

              <!-- Transfer Required Alert -->
              <div
                v-if="preview.impact.requires_transfer"
                class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4"
              >
                <div class="flex gap-3">
                  <AlertCircleIcon class="w-5 h-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-yellow-900 dark:text-yellow-300">
                      Transfert de responsabilités requis
                    </p>
                    <p class="text-sm text-yellow-800 dark:text-yellow-400 mt-1">
                      Ce membre est responsable de ressources critiques. Vous devez désigner un nouveau responsable.
                    </p>
                  </div>
                </div>
              </div>

              <!-- No Transfer Required -->
              <div
                v-else
                class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4"
              >
                <div class="flex gap-3">
                  <CheckCircleIcon class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-green-900 dark:text-green-300">
                      Retrait sans complications
                    </p>
                    <p class="text-sm text-green-800 dark:text-green-400 mt-1">
                      Ce membre n'a pas de responsabilités critiques. Le retrait sera simple.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Projects List (if responsible) -->
            <div
              v-if="preview.impact.projets_as_responsable > 0"
              class="mb-6"
            >
              <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                Projets dont il est responsable
              </h3>
              
              <div v-if="loadingProjects" class="flex justify-center py-4">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-brand-600"></div>
              </div>
              
              <div
                v-else
                class="border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-200 dark:divide-gray-700 max-h-48 overflow-y-auto"
              >
                <div
                  v-for="projet in userProjects"
                  :key="projet.id"
                  class="px-4 py-3 flex items-center gap-3 hover:bg-gray-50 dark:hover:bg-gray-700/50"
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
                      {{ projet.code }} • {{ projet.activites_count || 0 }} activité(s)
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Transfer Selection -->
            <div v-if="preview.impact.requires_transfer" class="mb-6">
              <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-3">
                Transférer les responsabilités à <span class="text-red-500">*</span>
              </label>

              <div v-if="loadingCandidates" class="flex justify-center py-4">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-brand-600"></div>
              </div>

              <div v-else class="space-y-3">
                <!-- Default Owner Option -->
                <label
                  v-if="candidatesData?.default_candidate"
                  class="flex items-center gap-3 p-4 border-2 rounded-lg cursor-pointer transition-all"
                  :class="selectedResponsable === candidatesData.default_candidate.id
                    ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20'
                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                >
                  <input
                    type="radio"
                    :value="candidatesData.default_candidate.id"
                    v-model="selectedResponsable"
                    class="text-brand-600 focus:ring-brand-500"
                  />
                  <div
                    v-if="candidatesData.default_candidate.avatar"
                    class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0"
                  >
                    <img
                      :src="candidatesData.default_candidate.avatar"
                      :alt="candidatesData.default_candidate.nom"
                      class="w-full h-full object-cover"
                    />
                  </div>
                  <div
                    v-else
                    class="w-10 h-10 rounded-full bg-brand-600 flex items-center justify-center text-white font-medium flex-shrink-0"
                  >
                    {{ getInitials(candidatesData.default_candidate.nom) }}
                  </div>
                  <div class="flex-1">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ candidatesData.default_candidate.nom }}
                      <span class="ml-2 px-2 py-0.5 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 rounded-full">
                        Propriétaire
                      </span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                      {{ candidatesData.default_candidate.email }}
                    </div>
                  </div>
                  <CheckCircleIcon
                    v-if="selectedResponsable === candidatesData.default_candidate.id"
                    class="w-5 h-5 text-brand-600"
                  />
                </label>

                <!-- Other Candidates -->
                <div v-if="candidatesData?.data && candidatesData.data.length > 0">
                  <p class="text-xs text-gray-500 dark:text-gray-400 mb-2 px-1">
                    Autres membres du workspace
                  </p>
                  
                  <div class="space-y-2">
                    <label
                      v-for="candidate in candidatesData.data"
                      :key="candidate.id"
                      class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-all"
                      :class="selectedResponsable === candidate.id
                        ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20'
                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                    >
                      <input
                        type="radio"
                        :value="candidate.id"
                        v-model="selectedResponsable"
                        class="text-brand-600 focus:ring-brand-500"
                      />
                      <div
                        v-if="candidate.avatar"
                        class="w-8 h-8 rounded-full overflow-hidden flex-shrink-0"
                      >
                        <img
                          :src="candidate.avatar"
                          :alt="candidate.nom"
                          class="w-full h-full object-cover"
                        />
                      </div>
                      <div
                        v-else
                        class="w-8 h-8 rounded-full bg-gray-600 flex items-center justify-center text-white text-xs font-medium flex-shrink-0"
                      >
                        {{ getInitials(candidate.nom) }}
                      </div>
                      <div class="flex-1">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                          {{ candidate.nom }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                          {{ candidate.email }}
                        </div>
                      </div>
                      <CheckCircleIcon
                        v-if="selectedResponsable === candidate.id"
                        class="w-5 h-5 text-brand-600"
                      />
                    </label>
                  </div>
                </div>
              </div>

              <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Cette personne deviendra responsable de {{ preview.impact.projets_as_responsable }} projet(s) 
                et {{ preview.impact.activites_as_responsable }} activité(s)
              </p>
            </div>

            <!-- Actions Impact -->
            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
              <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Actions automatiques lors du retrait :
              </p>
              <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1.5">
                <li class="flex items-start gap-2">
                  <CheckIcon class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" />
                  <span>Retrait de tous les {{ preview.impact.projets_as_member }} projets du workspace</span>
                </li>
                <li class="flex items-start gap-2">
                  <CheckIcon class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" />
                  <span>Désassignation des {{ preview.impact.taches_non_terminees }} tâches non terminées</span>
                </li>
                <li class="flex items-start gap-2">
                  <CheckIcon class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" />
                  <span>Révocation de tous les accès temporaires</span>
                </li>
                <li v-if="preview.impact.requires_transfer" class="flex items-start gap-2">
                  <CheckIcon class="w-4 h-4 text-orange-400 flex-shrink-0 mt-0.5" />
                  <span class="font-medium text-orange-600 dark:text-orange-400">
                    Transfert de {{ preview.impact.projets_as_responsable }} projet(s) 
                    et {{ preview.impact.activites_as_responsable }} activité(s)
                  </span>
                </li>
              </ul>
            </div>
          </div>

          <!-- Error Message -->
          <div
            v-if="error"
            class="mt-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800"
          >
            <div class="flex gap-2">
              <AlertCircleIcon class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" />
              <p class="text-sm text-red-800 dark:text-red-400">
                {{ error }}
              </p>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="sticky bottom-0 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700 px-6 py-4">
          <div class="flex items-center justify-between">
            <div class="text-xs text-gray-500 dark:text-gray-400">
              Cette action est irréversible
            </div>
            <div class="flex items-center gap-3">
              <button
                type="button"
                @click="$emit('close')"
                :disabled="submitting"
                class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors disabled:opacity-50"
              >
                Annuler
              </button>
              <button
                @click="handleRemove"
                :disabled="submitting || (preview?.impact.requires_transfer && !selectedResponsable)"
                class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2 font-medium"
              >
                <span v-if="submitting" class="animate-spin">⏳</span>
                <span v-else>Retirer définitivement</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useMemberRemoval } from '@/composables/useMemberRemoval'
import { useWorkspace } from '@/composables/useWorkspace'
import { 
  XIcon, 
  AlertCircleIcon, 
  CheckIcon, 
  CheckCircleIcon 
} from '@/icons'

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
    default: 'workspace',
    validator: (value) => ['workspace', 'projet'].includes(value)
  }
})

const emit = defineEmits(['close', 'removed'])

const {
  loading: loadingPreview,
  error,
  getRemovalPreview,
  getUserProjects,
  getTransferCandidates,
  removeMemberWithTransfer,
} = useMemberRemoval()

const { getRoleLabel, getRoleColor } = useWorkspace()

const preview = ref(null)
const userProjects = ref([])
const candidatesData = ref(null)
const loadingProjects = ref(false)
const loadingCandidates = ref(false)
const submitting = ref(false)
const selectedResponsable = ref('')

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
    // 1. Charger l'aperçu
    preview.value = await getRemovalPreview(props.workspaceId, props.member.id)
    
    // 2. Si des projets, les charger
    if (preview.value.impact.projets_as_responsable > 0) {
      loadingProjects.value = true
      userProjects.value = await getUserProjects(props.workspaceId, props.member.id)
      loadingProjects.value = false
    }
    
    // 3. Si transfert requis, charger les candidats
    if (preview.value.impact.requires_transfer) {
      loadingCandidates.value = true
      candidatesData.value = await getTransferCandidates(props.workspaceId, props.member.id)
      
      // Sélectionner le owner par défaut
      if (candidatesData.value?.default_candidate) {
        selectedResponsable.value = candidatesData.value.default_candidate.id
      }
      
      loadingCandidates.value = false
    }
  } catch (err) {
    console.error('Error loading removal data:', err)
  }
}

const handleRemove = async () => {
  if (preview.value?.impact.requires_transfer && !selectedResponsable.value) {
    error.value = 'Veuillez sélectionner un nouveau responsable'
    return
  }

  try {
    submitting.value = true
    error.value = null

    const result = await removeMemberWithTransfer(
      props.workspaceId,
      props.member.id,
      selectedResponsable.value || null
    )

    emit('removed', result.data)
  } catch (err) {
    // L'erreur est déjà gérée par le composable
    console.error('Error removing member:', err)
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>