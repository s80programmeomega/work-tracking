<!-- resources/js/components/taches/PendingValidationsModal.vue -->
<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 " @click.self="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-3 w-full max-w-6xl max-h-[95vh] overflow-hidden flex flex-col">
      
      <!-- Header -->
      <div class="px-8 py-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-3 flex items-center justify-center ">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                Validations en attente
              </h2>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Tâches nécessitant votre validation
              </p>
            </div>
          </div>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300 transition-colors">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Body -->
      <div class="flex-1 overflow-y-auto">
        <div class="px-8 py-6">
          <!-- Tabs -->
          <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
            <nav class="-mb-px flex space-x-8">
              <button
                v-for="tab in tabs"
                :key="tab.key"
                @click="activeTab = tab.key"
                :class="[
                  'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm',
                  activeTab === tab.key
                    ? 'border-amber-500 text-amber-600 dark:text-amber-400'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                ]"
              >
                {{ tab.name }}
                <span
                  class="ml-2 py-0.5 px-2 text-xs rounded-full"
                  :class="activeTab === tab.key
                    ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300'
                    : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'"
                >
                  {{ getTabCount(tab.key) }}
                </span>
              </button>
            </nav>
          </div>

          <!-- Loading -->
          <div v-if="loading" class="flex justify-center items-center py-12">
            <div class="text-center">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-500 mx-auto mb-4"></div>
              <p class="text-gray-600 dark:text-gray-400">Chargement des validations...</p>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else-if="getCurrentTabTasks().length === 0" class="text-center py-12">
            <div class="w-24 h-24 mx-auto mb-6 bg-amber-100 dark:bg-amber-900/20 rounded-3 flex items-center justify-center">
              <svg class="w-12 h-12 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">
              Aucune validation en attente
            </h3>
            <p class="text-gray-500 dark:text-gray-400">
              {{ activeTab === 'n1' 
                ? 'Aucune tâche ne nécessite votre validation N1 pour le moment.' 
                : 'Aucune tâche ne nécessite votre validation N2 pour le moment.' 
              }}
            </p>
          </div>

          <!-- Tasks List -->
          <div v-else ref="staggerRef" class="space-y-4">
            <div
              v-for="tache in getCurrentTabTasks()"
              :key="tache.id"
              class="stagger-item border border-gray-200 dark:border-gray-700 rounded-3 p-4 transition-shadow"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                  <!-- Task Header -->
                  <div class="flex items-center gap-2 mb-2">
                    <span class="text-sm font-mono text-gray-500 dark:text-gray-400">
                      {{ tache.code }}
                    </span>
                    <span
                      class="px-2 py-1 text-xs font-medium rounded"
                      :class="getPriorityClass(tache.priorite)"
                    >
                      {{ tache.priorite_label }}
                    </span>
                    <span
                      v-if="tache.is_overdue"
                      class="px-2 py-1 text-xs font-medium rounded bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300"
                    >
                      En retard
                    </span>
                  </div>

                  <!-- Task Title -->
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    {{ tache.titre }}
                  </h3>

                  <!-- Task Description -->
                  <p
                    v-if="tache.description"
                    class="text-gray-600 dark:text-gray-400 mb-3 line-clamp-2"
                  >
                    {{ tache.description }}
                  </p>

                  <!-- Activity & Project -->
                  <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mb-3">
                    <div class="flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                      </svg>
                      <span>{{ tache.activite?.nom }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                      </svg>
                      <span>{{ tache.activite?.projet_nom }}</span>
                    </div>
                  </div>

                  <!-- Assignees -->
                  <div class="flex items-center gap-2 mb-3">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Assigné à:</span>
                    <div class="flex -space-x-2">
                      <div
                        v-for="assignee in tache.assignees.slice(0, 3)"
                        :key="assignee.id"
                        class="relative"
                        :title="assignee.nom"
                      >
                        <div
                          v-if="assignee.avatar"
                          class="w-6 h-6 rounded-full border-2 border-white dark:border-gray-800 overflow-hidden"
                        >
                          <img :src="assignee.avatar" :alt="assignee.nom" class="w-full h-full object-cover" />
                        </div>
                        <div
                          v-else
                          class="w-6 h-6 rounded-full border-2 border-white dark:border-gray-800 bg-brand-500 text-white flex items-center justify-center text-xs font-medium"
                        >
                          {{ getInitials(assignee.nom) }}
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Dates -->
                  <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                    <div class="flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      <span>Terminé le: {{ formatDate(tache.date_fin_reelle) }}</span>
                    </div>
                    <div v-if="tache.echeance" class="flex items-center gap-1">
                      <span>Échéance: {{ formatDate(tache.echeance) }}</span>
                    </div>
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col gap-2 ml-4">
                  <!-- View Details -->
                  <button
                    @click="viewTaskDetails(tache)"
                    class="px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2 transition-colors"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Détails
                  </button>

                  <!-- Validate Button -->
                  <button
                    @click="validateTask(tache)"
                    class="px-3 py-2 text-sm text-white rounded-md flex items-center gap-2 transition-colors"
                    :class="activeTab === 'n1' 
                      ? 'bg-green-600 hover:bg-green-700' 
                      : 'bg-purple-600 hover:bg-purple-700'"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Valider {{ activeTab.toUpperCase() }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-between items-center">
        <div class="text-sm text-gray-500 dark:text-gray-400">
          Total: {{ pendingValidations.n1.length + pendingValidations.n2.length }} validation(s) en attente
        </div>
        <button
          @click="$emit('close')"
          class="px-5 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-700 dark:text-gray-300 transition-all"
        >
          Fermer
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { useStagger } from '@/composables/useAnimations'
import { useTaches } from '@/composables/useTaches'

const props = defineProps({
  // Props if needed
})

const emit = defineEmits(['close', 'validated'])

const { fetchPendingValidations, validateTacheN1, validateTacheN2 } = useTaches()

const { staggerRef, applyStagger } = useStagger(50)

const activeTab = ref('n1')
const loading = ref(false)
const pendingValidations = ref({
  n1: [],
  n2: []
})

const tabs = [
  { key: 'n1', name: 'Validation N1' },
  { key: 'n2', name: 'Validation N2' }
]

// Computed
const getTabCount = (tabKey) => {
  return pendingValidations.value[tabKey]?.length || 0
}

const getCurrentTabTasks = () => {
  return pendingValidations.value[activeTab.value] || []
}

// Methods
const loadPendingValidations = async () => {
  loading.value = true
  try {
    const data = await fetchPendingValidations()
    pendingValidations.value = {
      n1: data.pending_n1 || [],
      n2: data.pending_n2 || []
    }
    await nextTick()
    applyStagger()
  } catch (error) {
    console.error('Error loading pending validations:', error)
  } finally {
    loading.value = false
  }
}

const viewTaskDetails = (tache) => {
  // Implémenter l'ouverture des détails de la tâche
  console.log('View task details:', tache)
  // Vous pouvez émettre un événement ou utiliser un store pour gérer cela
}

const validateTask = async (tache) => {
  const commentaire = prompt(
    activeTab.value === 'n1' 
      ? 'Commentaire de validation N1 (optionnel):' 
      : 'Commentaire de validation N2 (optionnel):'
  )
  
  if (commentaire === null) return // User cancelled

  try {
    if (activeTab.value === 'n1') {
      await validateTacheN1(tache.id, commentaire)
    } else {
      await validateTacheN2(tache.id, commentaire)
    }
    
    // Remove task from list
    const index = pendingValidations.value[activeTab.value].findIndex(t => t.id === tache.id)
    if (index > -1) {
      pendingValidations.value[activeTab.value].splice(index, 1)
    }
    
    emit('validated', tache)
  } catch (error) {
    console.error('Error validating task:', error)
    alert(error.response?.data?.message || 'Erreur lors de la validation')
  }
}

const getPriorityClass = (priorite) => {
  const classes = {
    faible: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    moyenne: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
    elevee: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
    critique: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
  }
  return classes[priorite] || classes.moyenne
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const getInitials = (name) => {
  return name
    .split(' ')
    .map(part => part.charAt(0))
    .join('')
    .toUpperCase()
    .substring(0, 2)
}

onMounted(() => {
  loadPendingValidations()
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>