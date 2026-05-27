<template>
  <div class="bg-white dark:bg-gray-800 border-2 rounded-3 p-6 space-y-4"
       :class="getBorderClass()">
    
    <!-- Header -->
    <div class="flex justify-between items-start">
      <div class="flex-1">
        <div class="flex items-center gap-3 mb-2">
          <!-- Badge de statut -->
          <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
                :class="getStatusBadgeClass()">
            {{ getStatusLabel() }}
          </span>
          
          <!-- Taux de réalisation -->
          <span class="inline-flex items-center gap-1 text-sm font-semibold"
                :class="getProgressColorClass()">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            {{ resultat.taux_realisation }}%
          </span>
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400">
          Soumis le {{ formatDate(resultat.soumis_le) }}
        </p>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-2">
        <button
          v-if="canEdit && !isSubmitted"
          @click="$emit('edit', resultat)"
          class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-3 transition-colors"
          title="Modifier"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
        </button>

        <button
          v-if="canDelete && !isSubmitted"
          @click="$emit('delete', resultat)"
          class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-3 transition-colors"
          title="Supprimer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Contenu principal -->
    <div class="space-y-4">
      <!-- Résultats attendus -->
      <div>
        <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">
          📋 Résultats attendus
        </h5>
        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
          {{ resultat.resultats_attendus || 'Non spécifié' }}
        </p>
      </div>

      <!-- Résultats obtenus -->
      <div>
        <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">
          ✅ Résultats obtenus
        </h5>
        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
          {{ resultat.resultats_obtenus || 'Non spécifié' }}
        </p>
      </div>

      <!-- Difficultés -->
      <div v-if="resultat.difficultes_rencontrees">
        <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">
          ⚠️ Difficultés rencontrées
        </h5>
        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
          {{ resultat.difficultes_rencontrees }}
        </p>
      </div>

      <!-- Solutions -->
      <div v-if="resultat.solutions_envisagees">
        <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">
          💡 Solutions envisagées
        </h5>
        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
          {{ resultat.solutions_envisagees }}
        </p>
      </div>

      <!-- Observations -->
      <div v-if="resultat.observations">
        <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">
          💬 Observations
        </h5>
        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
          {{ resultat.observations }}
        </p>
      </div>
    </div>

    <!-- Validations -->
    <div v-if="isSubmitted" class="pt-4 border-t border-gray-200 dark:border-gray-700 space-y-3">
      <!-- Validation N1 -->
      <div v-if="resultat.valide_par_n1" class="p-3 bg-green-50 dark:bg-green-900/20 rounded-3">
        <div class="flex items-center gap-2 mb-1">
          <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span class="text-sm font-semibold text-green-900 dark:text-green-100">
            Validé N1 par {{ resultat.validateur_n1?.nom }}
          </span>
        </div>
        <p v-if="resultat.commentaire_n1" class="text-sm text-green-700 dark:text-green-300 mt-2">
          {{ resultat.commentaire_n1 }}
        </p>
      </div>

      <!-- Validation N2 -->
      <div v-if="resultat.valide_par_n2" class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-3">
        <div class="flex items-center gap-2 mb-1">
          <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
          <span class="text-sm font-semibold text-purple-900 dark:text-purple-100">
            Validé N2 par {{ resultat.validateur_n2?.nom }}
          </span>
        </div>
        <p v-if="resultat.commentaire_n2" class="text-sm text-purple-700 dark:text-purple-300 mt-2">
          {{ resultat.commentaire_n2 }}
        </p>
      </div>
    </div>

    <!-- Actions de validation -->
    <div v-if="showValidationActions" class="pt-4 border-t border-gray-200 dark:border-gray-700 flex gap-3">
      <button
        v-if="canValidateN1"
        @click="$emit('validate-n1', resultat)"
        class="flex-1 px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-3 font-medium transition-all"
      >
        Valider N1
      </button>

      <button
        v-if="canValidateN2"
        @click="$emit('validate-n2', resultat)"
        class="flex-1 px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-3 font-medium transition-all"
      >
        Valider N2
      </button>

      <button
        v-if="canReject"
        @click="$emit('reject', resultat, canValidateN2 ? 'n2' : 'n1')"
        class="px-4 py-2 border-2 border-red-500 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-3 font-medium transition-all"
      >
        Rejeter
      </button>
    </div>

    <!-- Action de soumission -->
    <div v-if="!isSubmitted && canSubmit" class="pt-4 border-t border-gray-200 dark:border-gray-700">
      <button
        @click="$emit('submit', resultat)"
        class="w-full px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-3 font-medium transition-all"
      >
        Soumettre pour validation
      </button>
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

defineEmits(['edit', 'delete', 'submit', 'validate-n1', 'validate-n2', 'reject'])

const isSubmitted = computed(() => !!props.resultat.soumis_le)
const isValidatedN1 = computed(() => !!props.resultat.valide_par_n1)
const isValidatedN2 = computed(() => !!props.resultat.valide_par_n2)

const canEdit = computed(() => !isSubmitted.value)
const canDelete = computed(() => !isSubmitted.value)
const canSubmit = computed(() => !isSubmitted.value)

const canValidateN1 = computed(() => 
  isSubmitted.value && 
  !isValidatedN1.value && 
  props.tache.permissions?.can_validate_n1
)

const canValidateN2 = computed(() => 
  isValidatedN1.value && 
  !isValidatedN2.value && 
  props.tache.permissions?.can_validate_n2
)

const canReject = computed(() => canValidateN1.value || canValidateN2.value)

const showValidationActions = computed(() => 
  canValidateN1.value || canValidateN2.value || canReject.value
)

const getBorderClass = () => {
  if (isValidatedN2.value) return 'border-purple-200 dark:border-purple-700'
  if (isValidatedN1.value) return 'border-green-200 dark:border-green-700'
  if (isSubmitted.value) return 'border-blue-200 dark:border-blue-700'
  return 'border-gray-200 dark:border-gray-700'
}

const getStatusBadgeClass = () => {
  if (isValidatedN2.value) return 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300'
  if (isValidatedN1.value) return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
  if (isSubmitted.value) return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
  return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
}

const getStatusLabel = () => {
  if (isValidatedN2.value) return '✓✓ Validation complète'
  if (isValidatedN1.value) return '✓ Validé N1'
  if (isSubmitted.value) return '⏳ En attente de validation'
  return '📝 Brouillon'
}

const getProgressColorClass = () => {
  const rate = props.resultat.taux_realisation
  if (rate >= 90) return 'text-green-600 dark:text-green-400'
  if (rate >= 75) return 'text-blue-600 dark:text-blue-400'
  if (rate >= 50) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-red-600 dark:text-red-400'
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>