<!-- resources/js/components/taches/panels/EssentialInfoPanel.vue -->
<template>
  <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
    <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
      <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      Informations
    </h3>
    
    <div class="space-y-3">
      <!-- Statut -->
      <div>
        <span class="text-xs text-gray-500 dark:text-gray-400">Statut</span>
        <div class="flex items-center gap-2 mt-1">
          <span :class="getStatusColor(tache.statut)" class="w-2 h-2 rounded-full"></span>
          <span class="text-sm font-medium text-gray-900 dark:text-white">{{ tache.statut_label }}</span>
        </div>
      </div>

      <!-- Priorité -->
      <div>
        <span class="text-xs text-gray-500 dark:text-gray-400">Priorité</span>
        <div class="flex items-center gap-2 mt-1">
          <span class="text-sm">{{ getPriorityIcon(tache.priorite) }}</span>
          <span class="text-sm font-medium text-gray-900 dark:text-white">{{ tache.priorite_label }}</span>
        </div>
      </div>

      <!-- Dates -->
      <div v-if="tache.date_debut || tache.echeance">
        <span class="text-xs text-gray-500 dark:text-gray-400">Dates</span>
        <div class="text-sm space-y-1 mt-1">
          <div v-if="tache.date_debut" class="flex justify-between">
            <span>Début:</span>
            <span class="font-medium">{{ formatDate(tache.date_debut) }}</span>
          </div>
          <div v-if="tache.echeance" class="flex justify-between" :class="{ 'text-red-600 font-medium': tache.is_overdue }">
            <span>Échéance:</span>
            <span>{{ formatDate(tache.echeance) }}</span>
          </div>
          <div v-if="tache.date_fin_reelle" class="flex justify-between text-green-600">
            <span>Terminé le:</span>
            <span class="font-medium">{{ formatDate(tache.date_fin_reelle) }}</span>
          </div>
        </div>
      </div>

      <!-- Progression -->
      <div>
        <span class="text-xs text-gray-500 dark:text-gray-400">Progression</span>
        <div class="mt-1">
          <div class="flex justify-between text-sm mb-1">
            <span class="font-medium">{{ tache.taux_realisation }}%</span>
          </div>
          <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
            <div
              class="h-2 rounded-full transition-all"
              :class="getProgressColor(tache.taux_realisation)"
              :style="{ width: `${tache.taux_realisation}%` }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Heures -->
      <div v-if="tache.estimated_hours || tache.actual_hours">
        <span class="text-xs text-gray-500 dark:text-gray-400">Temps</span>
        <div class="text-sm space-y-1 mt-1">
          <div v-if="tache.estimated_hours" class="flex justify-between">
            <span>Estimé:</span>
            <span class="font-medium">{{ tache.estimated_hours }}h</span>
          </div>
          <div v-if="tache.actual_hours" class="flex justify-between" :class="getTimeVarianceClass(tache)">
            <span>Réel:</span>
            <span>{{ tache.actual_hours }}h</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  tache: {
    type: Object,
    required: true
  }
})

const getStatusColor = (statut) => {
  const colors = {
    a_faire: 'bg-gray-500',
    en_cours: 'bg-blue-500',
    termine: 'bg-green-500'
  }
  return colors[statut] || 'bg-gray-500'
}

const getPriorityIcon = (priorite) => {
  const icons = {
    faible: '🟢',
    moyenne: '🟡',
    elevee: '🟠',
    critique: '🔴'
  }
  return icons[priorite] || '🟡'
}

const getProgressColor = (progress) => {
  if (progress < 30) return 'bg-red-500'
  if (progress < 70) return 'bg-amber-500'
  return 'bg-green-500'
}

const getTimeVarianceClass = (tache) => {
  if (!tache.estimated_hours || !tache.actual_hours) return ''

  const variance = tache.actual_hours - tache.estimated_hours
  if (variance > 0) return 'text-red-600 dark:text-red-400 font-medium'
  if (variance < 0) return 'text-green-600 dark:text-green-400 font-medium'
  return ''
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}
</script>