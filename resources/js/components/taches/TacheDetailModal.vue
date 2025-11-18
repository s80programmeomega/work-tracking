<!-- resources/js/components/taches/TacheDetailModal.vue -->
<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 backdrop-blur-sm" @click.self="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[95vh] overflow-hidden flex flex-col">
      
      <!-- Header -->
      <div class="px-8 py-6 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div 
              class="w-12 h-12 rounded-xl flex items-center justify-center shadow-lg"
              :style="{ backgroundColor: tache.couleur }"
            >
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
            <div>
              <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ tache.titre }}
              </h2>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ tache.code }} • {{ tache.activite?.nom }}
              </p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <!-- Validation status -->
            <div v-if="tache.validation" class="flex items-center gap-2">
              <span
                v-if="tache.validation.is_fully_validated"
                class="px-3 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 flex items-center gap-1"
              >
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Validée
              </span>
              <span
                v-else-if="tache.validation.n1_validated_at"
                class="px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 flex items-center gap-1"
              >
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Validée N1
              </span>
              <span
                v-else-if="tache.statut === 'termine'"
                class="px-3 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 flex items-center gap-1"
              >
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                En attente de validation
              </span>
            </div>

            <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-2">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Body -->
      <div class="flex-1 overflow-y-auto">
        <div class="px-8 py-6">
          <div class="grid grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="col-span-2 space-y-6">
              <!-- Description -->
              <div v-if="tache.description">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Description</h3>
                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ tache.description }}</p>
              </div>

              <!-- Objectif -->
              <div v-if="tache.objectif">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Objectif</h3>
                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ tache.objectif }}</p>
              </div>

              <!-- Indicateurs de résultats -->
              <div v-if="tache.indicateurs_resultats">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Indicateurs de résultats</h3>
                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ tache.indicateurs_resultats }}</p>
              </div>

              <!-- Commentaire -->
              <div v-if="tache.commentaire">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Commentaire</h3>
                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ tache.commentaire }}</p>
              </div>

              <!-- Résultats hebdomadaires -->
              <div v-if="tache.resultats && tache.resultats.length > 0">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Résultats hebdomadaires</h3>
                <div class="space-y-4">
                  <div
                    v-for="resultat in tache.resultats"
                    :key="resultat.id"
                    class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                  >
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-sm font-medium text-gray-900 dark:text-white">
                        Semaine {{ resultat.week_number }}/{{ resultat.year }}
                      </span>
                      <span class="text-xs px-2 py-1 rounded-full" :class="getValidationBadgeClass(resultat)">
                        {{ getValidationStatusText(resultat) }}
                      </span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                      <strong>Résultats obtenus:</strong> {{ resultat.resultats_obtenus }}
                    </p>
                    <div class="flex items-center justify-between text-xs text-gray-500">
                      <span>Taux de réalisation: {{ resultat.taux_realisation }}%</span>
                      <span>Soumis le: {{ formatDate(resultat.soumis_le) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
              <!-- Actions -->
              <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Actions</h3>
                <div class="space-y-2">
                  <button
                    v-if="tache.permissions?.can_edit"
                    @click="$emit('edit', tache)"
                    class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Modifier
                  </button>

                  <!-- Validation actions -->
                  <button
                    v-if="tache.permissions?.can_validate_n1 && tache.statut === 'termine' && !tache.validation?.n1_validated_at"
                    @click="$emit('validate-n1', tache)"
                    class="w-full px-3 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700 flex items-center gap-2"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Valider N1
                  </button>

                  <button
                    v-if="tache.permissions?.can_validate_n2 && tache.validation?.n1_validated_at && !tache.validation?.n2_validated_at"
                    @click="$emit('validate-n2', tache)"
                    class="w-full px-3 py-2 text-sm bg-purple-600 text-white rounded-md hover:bg-purple-700 flex items-center gap-2"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Valider N2
                  </button>

                  <button
                    v-if="tache.permissions?.can_complete && tache.statut !== 'termine'"
                    @click="handleCompleteTask"
                    class="w-full px-3 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center gap-2"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Marquer terminé
                  </button>
                </div>
              </div>

              <!-- Informations -->
              <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Informations</h3>
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

              <!-- Assignés -->
              <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Assignés</h3>
                <div class="space-y-2">
                  <div
                    v-for="assignee in tache.assignees"
                    :key="assignee.id"
                    class="flex items-center gap-3 p-2 rounded-md hover:bg-white dark:hover:bg-gray-800"
                  >
                    <div
                      v-if="assignee.avatar"
                      class="w-8 h-8 rounded-full overflow-hidden"
                    >
                      <img :src="assignee.avatar" :alt="assignee.nom" class="w-full h-full object-cover" />
                    </div>
                    <div
                      v-else
                      class="w-8 h-8 rounded-full bg-brand-500 text-white flex items-center justify-center text-sm font-medium"
                    >
                      {{ getInitials(assignee.nom) }}
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                        {{ assignee.nom }}
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                        {{ assignee.email }}
                      </p>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                      {{ assignee.pivot?.role || 'assignee' }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Labels -->
              <div v-if="tache.labels?.length > 0" class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Labels</h3>
                <div class="flex flex-wrap gap-2">
                  <span
                    v-for="label in tache.labels"
                    :key="label.id"
                    class="px-2 py-1 text-xs font-medium rounded-md"
                    :style="{
                      backgroundColor: label.couleur + '20',
                      color: label.couleur,
                      border: `1px solid ${label.couleur}`
                    }"
                  >
                    {{ label.nom }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useTaches } from '@/composables/useTaches'

const props = defineProps({
  tache: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'edit', 'validate-n1', 'validate-n2'])

const { completeTache } = useTaches()

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

const getValidationBadgeClass = (resultat) => {
  if (resultat.is_fully_validated) {
    return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300'
  }
  if (resultat.validation_n1?.valide) {
    return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
  }
  return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
}

const getValidationStatusText = (resultat) => {
  if (resultat.is_fully_validated) return 'Validé'
  if (resultat.validation_n1?.valide) return 'Validé N1'
  return 'En attente'
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

const handleCompleteTask = async () => {
  if (!props.tache.permissions?.can_complete) {
    alert('Vous n\'avez pas la permission de marquer cette tâche comme terminée')
    return
  }

  try {
    await completeTache(props.tache.id)
    // La modal sera fermée et la tâche rafraîchie par le parent
    emit('close')
  } catch (error) {
    console.error('Error completing task:', error)
    alert(error.response?.data?.message || 'Erreur lors de la complétion de la tâche')
  }
}
</script>