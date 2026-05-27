<!-- resources/js/components/resultats/ValidationModal.vue -->
<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-900/75 transition-opacity" @click="$emit('close')"></div>

    <!-- Modal -->
    <div class="flex min-h-screen items-center justify-center p-4">
      <div class="relative bg-white dark:bg-gray-900 rounded-3 w-full max-w-2xl transform transition-all">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200 dark:border-gray-700" :class="getHeaderClass()">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-3 flex items-center justify-center" :class="getIconContainerClass()">
                <svg v-if="action === 'validate'" class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <svg v-else class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
              </div>
              <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                  {{ action === 'validate' ? 'Valider le résultat' : 'Rejeter le résultat' }}
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Niveau {{ level.toUpperCase() }} • {{ resultat?.user?.nom }}
                </p>
              </div>
            </div>

            <button 
              @click="$emit('close')"
              class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-3 transition-colors"
            >
              <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-4">
          <!-- Résumé du résultat -->
          <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-3">
            <h4 class="font-medium text-gray-900 dark:text-white mb-3">📋 Résumé</h4>
            <div class="space-y-2 text-sm">
              <div class="flex items-start gap-2">
                <span class="text-gray-500 dark:text-gray-400 min-w-[120px]">Tâche:</span>
                <span class="text-gray-900 dark:text-white font-medium">{{ resultat?.tache?.titre }}</span>
              </div>
              <div class="flex items-start gap-2">
                <span class="text-gray-500 dark:text-gray-400 min-w-[120px]">Taux réalisation:</span>
                <span class="text-gray-900 dark:text-white font-bold">{{ resultat?.taux_realisation }}%</span>
              </div>
              <div class="flex items-start gap-2">
                <span class="text-gray-500 dark:text-gray-400 min-w-[120px]">Soumis le:</span>
                <span class="text-gray-900 dark:text-white">{{ formatDateTime(resultat?.soumis_le) }}</span>
              </div>
              <div class="flex items-start gap-2">
                <span class="text-gray-500 dark:text-gray-400 min-w-[120px]">Par:</span>
                <span class="text-gray-900 dark:text-white">{{ resultat?.user?.nom }}</span>
              </div>
            </div>
          </div>

          <!-- Résultats -->
          <div class="grid grid-cols-2 gap-4">
            <div class="p-4 bg-blue-50 dark:bg-blue-900/10 rounded-3">
              <p class="text-xs font-medium text-blue-600 dark:text-blue-400 mb-2">Résultats attendus</p>
              <p class="text-sm text-gray-900 dark:text-white">{{ resultat?.resultats_attendus }}</p>
            </div>
            <div class="p-4 bg-green-50 dark:bg-green-900/10 rounded-3">
              <p class="text-xs font-medium text-green-600 dark:text-green-400 mb-2">Résultats obtenus</p>
              <p class="text-sm text-gray-900 dark:text-white">{{ resultat?.resultats_obtenus }}</p>
            </div>
          </div>

          <!-- Warning en cas de rejet -->
          <div v-if="action === 'reject'" class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-3">
            <div class="flex gap-3">
              <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              <div>
                <p class="text-sm font-medium text-red-800 dark:text-red-300 mb-1">⚠️ Attention - Action importante</p>
                <p class="text-sm text-red-700 dark:text-red-400">
                  En rejetant ce résultat, le statut individuel de <strong>{{ resultat?.user?.nom }}</strong> sera automatiquement remis à <strong>"À faire"</strong> 
                  avec une progression de <strong>0%</strong>. L'utilisateur devra retravailler et soumettre un nouveau résultat.
                </p>
              </div>
            </div>
          </div>

          <!-- Info validation N2 -->
          <div v-if="action === 'validate' && level === 'n2'" class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-3">
            <div class="flex gap-3">
              <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
              <div>
                <p class="text-sm font-medium text-green-800 dark:text-green-300 mb-1">✅ Validation finale</p>
                <p class="text-sm text-green-700 dark:text-green-400">
                  Cette validation N2 complète le processus. Le résultat sera marqué comme <strong>entièrement validé</strong> 
                  et le statut individuel de l'utilisateur sera confirmé à <strong>"Terminé"</strong> avec 100% de progression.
                </p>
              </div>
            </div>
          </div>

          <!-- Commentaire -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              {{ action === 'validate' ? 'Commentaire (optionnel)' : 'Motif du rejet (obligatoire)' }}
              <span v-if="action === 'reject'" class="text-red-500">*</span>
            </label>
            <textarea
              v-model="commentaire"
              :placeholder="action === 'validate' 
                ? 'Ajoutez un commentaire sur ce résultat...' 
                : 'Expliquez les raisons du rejet et les améliorations attendues...'"
              rows="4"
              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-offset-0 transition-all"
              :class="action === 'validate' ? 'focus:ring-green-500' : 'focus:ring-red-500'"
            ></textarea>
            <p v-if="action === 'reject' && !commentaire" class="text-xs text-red-600 dark:text-red-400 mt-1">
              Le commentaire est obligatoire pour un rejet
            </p>
          </div>

          <!-- Quick templates (pour rejet) -->
          <div v-if="action === 'reject'" class="space-y-2">
            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Modèles rapides:</p>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="template in rejectTemplates"
                :key="template"
                @click="commentaire = template"
                class="px-3 py-1 text-xs bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-3 transition-colors text-gray-700 dark:text-gray-300"
              >
                {{ template }}
              </button>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex items-center justify-between gap-3">
          <button
            @click="$emit('close')"
            class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-3 transition-colors"
          >
            Annuler
          </button>

          <button
            @click="confirm"
            :disabled="processing || (action === 'reject' && !commentaire)"
            class="px-6 py-2.5 text-sm font-medium text-white rounded-3 transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
            :class="action === 'validate' 
              ? 'bg-green-600 hover:bg-green-700 shadow-green-500/30' 
              : 'bg-red-600 hover:bg-red-700 shadow-red-500/30'"
          >
            <svg v-if="processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-else-if="action === 'validate'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <span>
              {{ processing ? 'Traitement...' : (action === 'validate' ? 'Confirmer la validation' : 'Confirmer le rejet') }}
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  resultat: {
    type: Object,
    required: true
  },
  action: {
    type: String,
    required: true,
    validator: (value) => ['validate', 'reject'].includes(value)
  },
  level: {
    type: String,
    required: true,
    validator: (value) => ['n1', 'n2'].includes(value)
  }
})

const emit = defineEmits(['close', 'confirmed'])

const commentaire = ref('')
const processing = ref(false)

const rejectTemplates = [
  'Les résultats ne correspondent pas aux objectifs fixés.',
  'Les justificatifs fournis sont insuffisants.',
  'Les données présentées nécessitent plus de précisions.',
  'Le taux de réalisation ne reflète pas le travail effectué.',
  'Des documents complémentaires sont nécessaires.'
]

function getHeaderClass() {
  if (props.action === 'validate') {
    return 'bg-success-50 dark:bg-success-500/10'
  }
  return 'bg-error-50 dark:bg-error-500/10'
}

function getIconContainerClass() {
  if (props.action === 'validate') {
    return 'bg-green-600'
  }
  return 'bg-red-600'
}

function formatDateTime(date) {
  if (!date) return ''
  return new Date(date).toLocaleString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

async function confirm() {
  if (props.action === 'reject' && !commentaire.value.trim()) {
    return
  }

  processing.value = true

  try {
    emit('confirmed', {
      resultat: props.resultat,
      action: props.action,
      level: props.level,
      commentaire: commentaire.value.trim()
    })
  } finally {
    processing.value = false
  }
}
</script>