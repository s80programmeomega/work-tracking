<!-- resources/js/components/taches/EvaluationRow.vue -->
<template>
  <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
      :class="getRowClass()">
    <!-- N° -->
    <td class="px-6 py-4 whitespace-nowrap">
      <div class="flex items-center gap-2">
        <span class="text-sm font-semibold text-gray-900 dark:text-white">
          {{ index }}
        </span>
      </div>
    </td>

    <!-- Tâche -->
    <td class="px-6 py-4">
      <div class="space-y-1">
        <div class="flex items-center gap-2">
          <span class="font-medium text-gray-900 dark:text-white">
            {{ task.titre }}
          </span>
          <span class="text-xs text-gray-500">{{ task.code }}</span>
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-400">
          {{ task.activite?.nom }}
        </div>
        <PriorityBadge :priority="task.priorite" />
      </div>
    </td>

    <!-- Résultats attendus -->
    <td class="px-6 py-4">
      <div class="text-sm text-gray-700 dark:text-gray-300">
        {{ task.indicateurs_resultats || task.objectif || '-' }}
      </div>
    </td>

    <!-- Échéance -->
    <td class="px-6 py-4">
      <div class="flex flex-col gap-1">
        <span class="text-sm text-gray-900 dark:text-white">
          {{ formatDate(task.echeance) }}
        </span>
        <span v-if="task.is_overdue && myStatut !== 'termine'" 
              class="text-xs text-red-600 dark:text-red-400 font-medium flex items-center gap-1 animate-pulse">
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
          ⚠️ RETARD !
        </span>
        <span v-else-if="daysUntilDeadline !== null && daysUntilDeadline <= 3 && myStatut !== 'termine'"
              class="text-xs text-orange-600 dark:text-orange-400 font-medium flex items-center gap-1">
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
          </svg>
          {{ daysUntilDeadline }} jour(s) restant(s)
        </span>
      </div>
    </td>

    <!-- Mon Statut Individuel -->
    <td class="px-6 py-4">
      <div class="flex justify-center">
        <StatusBadge 
          :status="myStatut" 
          :is-overdue="task.is_overdue && myStatut !== 'termine'"
        />
      </div>
    </td>

    <!-- Ma Progression -->
    <td class="px-6 py-4">
      <div class="flex flex-col items-center gap-2">
        <div class="flex items-center gap-2">
          <span class="text-2xl font-bold" :class="getProgressionColor(myProgression)">
            {{ myProgression }}%
          </span>
        </div>
        <div class="w-full max-w-[120px] bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
          <div 
            class="h-full transition-all duration-500"
            :class="getProgressionBgColor(myProgression)"
            :style="{ width: myProgression + '%' }">
          </div>
        </div>
        <div v-if="task.my_status?.started_at" class="text-xs text-gray-500 dark:text-gray-400">
          Démarré {{ formatRelativeTime(task.my_status.started_at) }}
        </div>
      </div>
    </td>

    <!-- Résultats obtenus -->
    <td class="px-6 py-4">
      <div v-if="task.my_result" class="text-sm space-y-2">
        <p class="text-gray-900 dark:text-white line-clamp-3">
          {{ task.my_result.resultats_obtenus || '-' }}
        </p>
        <button 
          v-if="task.my_result.resultats_obtenus"
          @click="$emit('view-result', task, task.my_result)"
          class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium flex items-center gap-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
          Voir détails
        </button>
      </div>
      <button
        v-else-if="canAddResult"
        @click="$emit('add-result', task)"
        class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium flex items-center gap-1 px-3 py-2 border border-blue-300 dark:border-blue-700 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Soumettre résultat
      </button>
      <span v-else class="text-sm text-gray-400">-</span>
    </td>

    <!-- Taux de réalisation -->
    <td class="px-6 py-4">
      <div v-if="task.my_result" class="flex flex-col items-center gap-1">
        <span class="text-lg font-bold" :class="getTauxColor(task.my_result.taux_realisation)">
          {{ task.my_result.taux_realisation }}%
        </span>
        <div class="w-16 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
          <div 
            class="h-full transition-all"
            :class="getTauxBgColor(task.my_result.taux_realisation)"
            :style="{ width: task.my_result.taux_realisation + '%' }">
          </div>
        </div>
      </div>
      <span v-else class="text-sm text-gray-400">-</span>
    </td>

    <!-- Validation -->
    <td class="px-6 py-4">
      <ValidationStatus 
        v-if="task.my_result && task.my_result.soumis_le"
        :result="task.my_result"
        :task="task"
      />
      <div v-else class="text-center">
        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-gray-500 bg-gray-100 dark:bg-gray-700 dark:text-gray-400 rounded-full">
          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 000 2h4a1 1 0 100-2H8z" clip-rule="evenodd" />
          </svg>
          Non soumis
        </span>
      </div>
    </td>

    <!-- Actions -->
    <td class="px-6 py-4">
      <div class="flex items-center justify-center gap-2">
        <!-- Éditer résultat -->
        <button
          v-if="canEditResult"
          @click="$emit('edit-result', task, task.my_result)"
          class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
          title="Modifier le résultat">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
        </button>

        <!-- Voir résultat -->
        <button
          v-if="task.my_result"
          @click="$emit('view-result', task, task.my_result)"
          class="p-2 text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
          title="Voir le résultat">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
        </button>

        <!-- Rafraîchir -->
        <button
          @click="$emit('refresh')"
          class="p-2 text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
          title="Rafraîchir">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </button>
      </div>
    </td>
  </tr>
</template>

<script setup>
import { computed } from 'vue'
import StatusBadge from './StatusBadge.vue'
import PriorityBadge from './PriorityBadge.vue'
import ValidationStatus from './ValidationStatus.vue'

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

defineEmits(['add-result', 'edit-result', 'view-result', 'refresh'])

// ✅ Obtenir MON statut individuel
const myStatut = computed(() => {
  return props.task.my_status?.statut || 'a_faire'
})

// ✅ Obtenir MA progression individuelle
const myProgression = computed(() => {
  return props.task.my_status?.progression || 0
})

// ✅ Calculer les jours restants
const daysUntilDeadline = computed(() => {
  if (!props.task.echeance) return null
  const deadline = new Date(props.task.echeance)
  const today = new Date()
  const diffTime = deadline - today
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return diffDays
})

const canAddResult = computed(() => {
  return myStatut.value === 'termine' && !props.task.my_result?.soumis_le
})

const canEditResult = computed(() => {
  return props.task.my_result 
    && props.task.my_result.soumis_le
    && !props.task.my_result.valide_par_n1
})

function getRowClass() {
  if (props.task.is_overdue && myStatut.value !== 'termine') {
    return 'bg-red-50 dark:bg-red-900/10 border-l-4 border-red-500'
  }
  if (props.task.validation_status === 'fully_validated') {
    return 'bg-green-50 dark:bg-green-900/10'
  }
  return ''
}

function getProgressionColor(progression) {
  if (progression >= 80) return 'text-green-600 dark:text-green-400'
  if (progression >= 50) return 'text-yellow-600 dark:text-yellow-400'
  if (progression >= 25) return 'text-orange-600 dark:text-orange-400'
  return 'text-gray-600 dark:text-gray-400'
}

function getProgressionBgColor(progression) {
  if (progression >= 80) return 'bg-green-500'
  if (progression >= 50) return 'bg-yellow-500'
  if (progression >= 25) return 'bg-orange-500'
  return 'bg-gray-400'
}

function getTauxColor(taux) {
  if (taux >= 80) return 'text-green-600 dark:text-green-400'
  if (taux >= 50) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-red-600 dark:text-red-400'
}

function getTauxBgColor(taux) {
  if (taux >= 80) return 'bg-green-500'
  if (taux >= 50) return 'bg-yellow-500'
  return 'bg-red-500'
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

function formatRelativeTime(date) {
  if (!date) return ''
  const now = new Date()
  const past = new Date(date)
  const diffDays = Math.floor((now - past) / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return 'aujourd\'hui'
  if (diffDays === 1) return 'hier'
  if (diffDays < 7) return `il y a ${diffDays} jours`
  if (diffDays < 30) return `il y a ${Math.floor(diffDays / 7)} semaines`
  return `il y a ${Math.floor(diffDays / 30)} mois`
}
</script>