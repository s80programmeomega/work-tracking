<template>
  <div class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-4 space-y-4">
    <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Informations</h4>
    
    <!-- Dates -->
    <div class="space-y-3">
      <!-- Date de début -->
      <div v-if="tache.date_debut" class="flex items-start gap-3">
        <div class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-3 flex items-center justify-center">
          <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <div class="flex-1">
          <p class="text-xs text-gray-500 dark:text-gray-400">Début</p>
          <p class="text-sm font-medium text-gray-900 dark:text-white">{{ formatDate(tache.date_debut) }}</p>
        </div>
      </div>

      <!-- Échéance -->
      <div v-if="tache.echeance" class="flex items-start gap-3">
        <div class="flex-shrink-0 w-8 h-8 rounded-3 flex items-center justify-center"
             :class="tache.is_overdue 
               ? 'bg-red-100 dark:bg-red-900/30' 
               : 'bg-orange-100 dark:bg-orange-900/30'">
          <svg class="w-4 h-4" 
               :class="tache.is_overdue 
                 ? 'text-red-600 dark:text-red-400' 
                 : 'text-orange-600 dark:text-orange-400'"
               fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div class="flex-1">
          <p class="text-xs text-gray-500 dark:text-gray-400">Échéance</p>
          <p class="text-sm font-medium" 
             :class="tache.is_overdue 
               ? 'text-red-600 dark:text-red-400' 
               : 'text-gray-900 dark:text-white'">
            {{ formatDate(tache.echeance) }}
            <span v-if="tache.is_overdue" class="text-xs ml-1">⚠️ En retard</span>
          </p>
        </div>
      </div>

      <!-- Semaine -->
      <div v-if="tache.week_number" class="flex items-start gap-3">
        <div class="flex-shrink-0 w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-3 flex items-center justify-center">
          <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
        </div>
        <div class="flex-1">
          <p class="text-xs text-gray-500 dark:text-gray-400">Semaine</p>
          <p class="text-sm font-medium text-gray-900 dark:text-white">
            S{{ tache.week_number }} - {{ tache.year }}
          </p>
        </div>
      </div>
    </div>

    <!-- Progression -->
    <div v-if="tache.taux_realisation !== null" class="pt-3 border-t border-gray-200 dark:border-gray-700">
      <div class="flex justify-between items-center mb-2">
        <span class="text-xs text-gray-500 dark:text-gray-400">Progression</span>
        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ tache.taux_realisation }}%</span>
      </div>
      <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
        <div 
          class="h-2 rounded-full transition-all duration-300"
          :class="getProgressColor(tache.taux_realisation)"
          :style="{ width: `${tache.taux_realisation}%` }"
        ></div>
      </div>
    </div>

    <!-- Heures estimées/réelles -->
    <div v-if="tache.estimated_hours || tache.actual_hours" class="pt-3 border-t border-gray-200 dark:border-gray-700 space-y-2">
      <div v-if="tache.estimated_hours" class="flex justify-between text-sm">
        <span class="text-gray-600 dark:text-gray-400">⏱️ Estimé</span>
        <span class="font-medium text-gray-900 dark:text-white">{{ tache.estimated_hours }}h</span>
      </div>
      <div v-if="tache.actual_hours" class="flex justify-between text-sm">
        <span class="text-gray-600 dark:text-gray-400">⏰ Réel</span>
        <span class="font-medium text-gray-900 dark:text-white">{{ tache.actual_hours }}h</span>
      </div>
      <div v-if="tache.estimated_hours && tache.actual_hours" class="flex justify-between text-sm pt-2 border-t border-gray-100 dark:border-gray-700">
        <span class="text-gray-600 dark:text-gray-400">Écart</span>
        <span class="font-medium" :class="getVarianceClass(tache.actual_hours - tache.estimated_hours)">
          {{ formatVariance(tache.actual_hours - tache.estimated_hours) }}
        </span>
      </div>
    </div>

    <!-- Activité parent -->
    <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
      <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Activité</p>
      <p class="text-sm font-medium text-gray-900 dark:text-white">{{ tache.activite.nom }}</p>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ tache.activite.projet_nom }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  tache: {
    type: Object,
    required: true
  }
})

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const getProgressColor = (progress) => {
  if (progress >= 75) return 'bg-green-500'
  if (progress >= 50) return 'bg-blue-500'
  if (progress >= 25) return 'bg-yellow-500'
  return 'bg-red-500'
}

const getVarianceClass = (variance) => {
  if (variance > 0) return 'text-red-600 dark:text-red-400'
  if (variance < 0) return 'text-green-600 dark:text-green-400'
  return 'text-gray-600 dark:text-gray-400'
}

const formatVariance = (variance) => {
  const sign = variance > 0 ? '+' : ''
  return `${sign}${variance.toFixed(1)}h`
}
</script>