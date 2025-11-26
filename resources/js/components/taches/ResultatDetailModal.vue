<!-- resources\js\components\taches\ResultatDetailModal.vue -->
<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 backdrop-blur-sm"
       @click.self="$emit('close')">
    
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
      
      <!-- Header -->
      <div class="px-8 py-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20">
        <div class="flex justify-between items-start">
          <div class="flex-1">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
              Détail du résultat
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              {{ tache.titre }}
            </p>
            <div class="flex items-center gap-2 mt-2">
              <!-- Badge statut validation -->
              <span v-if="resultat.validation_n2?.valide" 
                    class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Validé N2 (Complet)
              </span>
              <span v-else-if="resultat.validation_n1?.valide" 
                    class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Validé N1
              </span>
              <span v-else-if="resultat.soumis_le"
                    class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                En attente de validation
              </span>
              <span v-else
                    class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                Brouillon
              </span>
            </div>
          </div>

          <button @click="$emit('close')" 
                  class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Content -->
      <div class="flex-1 overflow-y-auto p-8 space-y-6">
        
        <!-- Taux de réalisation -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                Taux de réalisation
              </h3>
              <div class="flex items-end gap-2">
                <span class="text-5xl font-bold text-blue-600">{{ resultat.taux_realisation }}%</span>
                <span class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                  {{ getProgressLabel(resultat.taux_realisation) }}
                </span>
              </div>
            </div>
            <div class="relative w-24 h-24">
              <svg class="transform -rotate-90 w-24 h-24">
                <circle
                  cx="48"
                  cy="48"
                  r="40"
                  stroke="currentColor"
                  stroke-width="8"
                  fill="none"
                  class="text-gray-200 dark:text-gray-700"
                />
                <circle
                  cx="48"
                  cy="48"
                  r="40"
                  stroke="currentColor"
                  stroke-width="8"
                  fill="none"
                  :class="getProgressColorClass(resultat.taux_realisation)"
                  stroke-linecap="round"
                  :stroke-dasharray="circumference"
                  :stroke-dashoffset="circumference - (resultat.taux_realisation / 100) * circumference"
                  class="transition-all duration-500"
                />
              </svg>
            </div>
          </div>
          <div class="w-full bg-blue-200 dark:bg-blue-900 rounded-full h-3 mt-4">
            <div 
              class="h-3 rounded-full transition-all duration-500"
              :class="getProgressColorClass(resultat.taux_realisation)"
              :style="{ width: resultat.taux_realisation + '%' }">
            </div>
          </div>
        </div>

        <!-- Résultats -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Résultats attendus -->
          <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              Résultats attendus
            </h3>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">
              {{ resultat.resultats_attendus }}
            </p>
          </div>

          <!-- Résultats obtenus -->
          <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Résultats obtenus
            </h3>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">
              {{ resultat.resultats_obtenus }}
            </p>
          </div>
        </div>

        <!-- Difficultés et solutions -->
        <div v-if="resultat.difficultes_rencontrees || resultat.solutions_envisagees" 
             class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Difficultés -->
          <div v-if="resultat.difficultes_rencontrees" 
               class="bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              Difficultés rencontrées
            </h3>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">
              {{ resultat.difficultes_rencontrees }}
            </p>
          </div>

          <!-- Solutions -->
          <div v-if="resultat.solutions_envisagees"
               class="bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
              </svg>
              Solutions envisagées
            </h3>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">
              {{ resultat.solutions_envisagees }}
            </p>
          </div>
        </div>

        <!-- Observations -->
        <div v-if="resultat.observations" 
             class="bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-200 dark:border-purple-800 p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
            </svg>
            Observations
          </h3>
          <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">
            {{ resultat.observations }}
          </p>
        </div>

        <!-- Validations -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            Statut de validation
          </h3>

          <div class="space-y-4">
            <!-- Validation N1 -->
            <div class="p-4 rounded-lg border-2"
                 :class="resultat.validation_n1?.valide 
                   ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800'
                   : 'bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700'">
              <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                  <div v-if="resultat.validation_n1?.valide" 
                       class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                  <div v-else class="w-10 h-10 bg-gray-300 dark:bg-gray-700 rounded-full flex items-center justify-center">
                    <span class="text-white font-bold">N1</span>
                  </div>
                </div>
                <div class="flex-1">
                  <p class="font-semibold text-gray-900 dark:text-white">
                    Validation N1 (Responsable activité)
                  </p>
                  <p v-if="resultat.validation_n1?.valide" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    ✓ Validé par {{ resultat.validation_n1.validateur?.nom }} 
                    le {{ formatDateTime(resultat.validation_n1.valide_le) }}
                  </p>
                  <p v-if="resultat.validation_n1?.commentaire" 
                     class="text-sm text-gray-700 dark:text-gray-300 mt-2 p-3 bg-white dark:bg-gray-800 rounded-lg italic">
                    "{{ resultat.validation_n1.commentaire }}"
                  </p>
                </div>
              </div>
            </div>

            <!-- Validation N2 -->
            <div v-if="resultat.validation_n1?.valide" 
                 class="p-4 rounded-lg border-2"
                 :class="resultat.validation_n2?.valide 
                   ? 'bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-800'
                   : 'bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700'">
              <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                  <div v-if="resultat.validation_n2?.valide" 
                       class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                  <div v-else class="w-10 h-10 bg-gray-300 dark:bg-gray-700 rounded-full flex items-center justify-center">
                    <span class="text-white font-bold">N2</span>
                  </div>
                </div>
                <div class="flex-1">
                  <p class="font-semibold text-gray-900 dark:text-white">
                    Validation N2 (Responsable projet)
                  </p>
                  <p v-if="resultat.validation_n2?.valide" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    ✓ Validé par {{ resultat.validation_n2.validateur?.nom }} 
                    le {{ formatDateTime(resultat.validation_n2.valide_le) }}
                  </p>
                  <p v-if="resultat.validation_n2?.commentaire" 
                     class="text-sm text-gray-700 dark:text-gray-300 mt-2 p-3 bg-white dark:bg-gray-800 rounded-lg italic">
                    "{{ resultat.validation_n2.commentaire }}"
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Métadonnées -->
        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
              <p class="text-gray-500 dark:text-gray-400">Soumis le</p>
              <p class="font-medium text-gray-900 dark:text-white">
                {{ resultat.soumis_le ? formatDate(resultat.soumis_le) : 'Non soumis' }}
              </p>
            </div>
            <div>
              <p class="text-gray-500 dark:text-gray-400">Créé le</p>
              <p class="font-medium text-gray-900 dark:text-white">{{ formatDate(resultat.created_at) }}</p>
            </div>
            <div>
              <p class="text-gray-500 dark:text-gray-400">Modifié le</p>
              <p class="font-medium text-gray-900 dark:text-white">{{ formatDate(resultat.updated_at) }}</p>
            </div>
            <div>
              <p class="text-gray-500 dark:text-gray-400">Par</p>
              <p class="font-medium text-gray-900 dark:text-white">{{ resultat.user?.nom || 'Inconnu' }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-between items-center">
        <button
          @click="$emit('close')"
          class="px-5 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-700 dark:text-gray-300 transition-all">
          Fermer
        </button>
        
        <button
          v-if="!resultat.is_fully_validated"
          @click="$emit('edit', resultat)"
          class="px-5 py-2.5 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
          Modifier
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  resultat: {
    type: Object,
    required: true
  },
  tache: {
    type: Object,
    required: true
  }
})

defineEmits(['close', 'edit'])

const circumference = 2 * Math.PI * 40

function getProgressLabel(value) {
  if (value >= 90) return 'Excellent'
  if (value >= 75) return 'Très bien'
  if (value >= 50) return 'Satisfaisant'
  if (value >= 25) return 'Insuffisant'
  return 'Faible'
}

function getProgressColorClass(value) {
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

function formatDateTime(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>