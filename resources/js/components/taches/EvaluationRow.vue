<!-- resources/js/views/evaluations/components/EvaluationRow.vue -->
<template>
  <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
      :class="{ 'bg-green-50 dark:bg-green-900/10': isFullyValidated }">
    
    <!-- N° -->
    <td class="px-6 py-4 whitespace-nowrap">
      <div class="flex items-center gap-2">
        <span class="text-sm font-semibold text-gray-900 dark:text-white">
          {{ index }}
        </span>
        <span v-if="task.is_overdue" class="text-red-500" title="En retard">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </span>
      </div>
    </td>

    <!-- Tâche -->
    <td class="px-6 py-4">
      <div class="space-y-1">
        <p class="text-sm font-medium text-gray-900 dark:text-white">
          {{ task.titre }}
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          {{ task.activite.nom }}
        </p>
        <div class="flex items-center gap-2 mt-1">
          <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full"
                :class="getStatusClass(task.statut)">
            {{ task.statut_label }}
          </span>
          <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full"
                :class="getPriorityClass(task.priorite)">
            {{ task.priorite_label }}
          </span>
        </div>
      </div>
    </td>

    <!-- Résultats attendus -->
    <td class="px-6 py-4">
      <div class="text-sm text-gray-700 dark:text-gray-300">
        <p class="line-clamp-3" :title="task.objectif">
          {{ task.objectif || task.description || 'Non défini' }}
        </p>
      </div>
    </td>

    <!-- Échéance -->
    <td class="px-6 py-4 whitespace-nowrap">
      <div class="text-sm">
        <p v-if="task.echeance" :class="task.is_overdue ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-900 dark:text-white'">
          {{ formatDate(task.echeance) }}
        </p>
        <p v-else class="text-gray-500 dark:text-gray-400 italic">
          Non définie
        </p>
      </div>
    </td>

    <!-- Résultats obtenus -->
    <td class="px-6 py-4">
      <div v-if="latestResult" class="space-y-1">
        <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-3">
          {{ latestResult.resultats_obtenus }}
        </p>
        <button
          @click="$emit('view-result', task, latestResult)"
          class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 flex items-center gap-1">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
          Voir détails
        </button>
      </div>
      <div v-else class="text-center">
        <button
          @click="$emit('add-result', task)"
          class="inline-flex items-center px-3 py-1.5 bg-blue-500 text-white text-xs rounded-lg hover:bg-blue-600 transition-colors">
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Ajouter
        </button>
      </div>
    </td>

    <!-- Taux de réalisation -->
    <td class="px-6 py-4">
      <div class="flex flex-col items-center gap-2">
        <div class="relative w-16 h-16">
          <svg class="transform -rotate-90 w-16 h-16">
            <circle
              cx="32"
              cy="32"
              r="28"
              stroke="currentColor"
              stroke-width="4"
              fill="none"
              class="text-gray-200 dark:text-gray-700"
            />
            <circle
              cx="32"
              cy="32"
              r="28"
              stroke="currentColor"
              stroke-width="4"
              fill="none"
              :class="getProgressColor(currentRate)"
              stroke-linecap="round"
              :stroke-dasharray="circumference"
              :stroke-dashoffset="dashOffset"
              class="transition-all duration-500"
            />
          </svg>
          <div class="absolute inset-0 flex items-center justify-center">
            <span class="text-sm font-bold" :class="getProgressColor(currentRate)">
              {{ currentRate }}%
            </span>
          </div>
        </div>
      </div>
    </td>

    <!-- Difficultés rencontrées -->
    <td class="px-6 py-4">
      <div v-if="latestResult?.difficultes_rencontrees" class="text-sm text-gray-700 dark:text-gray-300">
        <p class="line-clamp-3">{{ latestResult.difficultes_rencontrees }}</p>
      </div>
      <div v-else class="text-center text-gray-400 dark:text-gray-500 italic text-xs">
        Aucune difficulté
      </div>
    </td>

    <!-- Solutions envisagées -->
    <td class="px-6 py-4">
      <div v-if="latestResult?.solutions_envisagees" class="text-sm text-gray-700 dark:text-gray-300">
        <p class="line-clamp-3">{{ latestResult.solutions_envisagees }}</p>
      </div>
      <div v-else class="text-center text-gray-400 dark:text-gray-500 italic text-xs">
        -
      </div>
    </td>

    <!-- Validation -->
    <td class="px-6 py-4">
      <div class="flex flex-col items-center gap-1">
        <!-- N1 -->
        <div v-if="latestResult" class="flex items-center gap-1">
          <div v-if="latestResult.validation_n1?.valide" 
               class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center"
               title="Validé N1">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <div v-else class="w-6 h-6 bg-gray-300 dark:bg-gray-700 rounded-full flex items-center justify-center"
               title="En attente N1">
            <span class="text-xs font-bold text-gray-600 dark:text-gray-400">N1</span>
          </div>
        </div>

        <!-- N2 -->
        <div v-if="latestResult && latestResult.validation_n1?.valide" class="flex items-center gap-1">
          <div v-if="latestResult.validation_n2?.valide" 
               class="w-6 h-6 bg-purple-500 rounded-full flex items-center justify-center"
               title="Validé N2">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <div v-else class="w-6 h-6 bg-gray-300 dark:bg-gray-700 rounded-full flex items-center justify-center"
               title="En attente N2">
            <span class="text-xs font-bold text-gray-600 dark:text-gray-400">N2</span>
          </div>
        </div>

        <div v-if="!latestResult" class="text-xs text-gray-400 italic">
          Aucun résultat
        </div>
      </div>
    </td>

    <!-- Actions -->
    <td class="px-6 py-4">
      <div class="flex items-center justify-center gap-2">
        <button
          v-if="latestResult && canEdit"
          @click="$emit('edit-result', task, latestResult)"
          class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
          title="Modifier">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
        </button>
        
        <button
          v-if="latestResult"
          @click="$emit('view-result', task, latestResult)"
          class="p-2 text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
          title="Voir détails">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
        </button>
      </div>
    </td>
  </tr>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  task: {
    type: Object,
    required: true
  },
  index: {
    type: Number,
    required: true
  }
})

defineEmits(['add-result', 'edit-result', 'view-result'])

// Computed
const latestResult = computed(() => {
  if (!props.task.resultats || props.task.resultats.length === 0) return null
  return props.task.resultats[0] // Assumant qu'ils sont triés par date
})

const currentRate = computed(() => {
  if (latestResult.value) {
    return latestResult.value.taux_realisation
  }
  return props.task.taux_realisation || 0
})

const isFullyValidated = computed(() => {
  return latestResult.value?.validation_n2?.valide || false
})

const canEdit = computed(() => {
  if (!latestResult.value) return false
  return !isFullyValidated.value
})

const circumference = 2 * Math.PI * 28
const dashOffset = computed(() => {
  return circumference - (currentRate.value / 100) * circumference
})

// Méthodes
function getStatusClass(statut) {
  const classes = {
    'a_faire': 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    'en_cours': 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
    'termine': 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
  }
  return classes[statut] || classes.a_faire
}

function getPriorityClass(priorite) {
  const classes = {
    'faible': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    'moyenne': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    'elevee': 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    'critique': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
  }
  return classes[priorite] || classes.moyenne
}

function getProgressColor(value) {
  if (value >= 75) return 'text-green-500'
  if (value >= 50) return 'text-blue-500'
  if (value >= 25) return 'text-yellow-500'
  return 'text-red-500'
}

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}
</script>