<!-- resources/js/components/taches/ValidationModal.vue -->
<template>
  <TransitionRoot :show="true" as="template">
    <Dialog as="div" class="relative z-50" @close="$emit('close')">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="relative w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-xl transition-all">
              <!-- Header -->
              <div 
                class="border-b border-gray-200 dark:border-gray-700 px-6 py-4"
                :class="action === 'validate' ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20'"
              >
                <div class="flex items-center justify-between">
                  <div>
                    <DialogTitle class="text-xl font-bold flex items-center gap-3" :class="action === 'validate' ? 'text-green-900 dark:text-green-300' : 'text-red-900 dark:text-red-300'">
                      <div 
                        class="w-10 h-10 rounded-xl flex items-center justify-center"
                        :class="action === 'validate' ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30'"
                      >
                        <svg v-if="action === 'validate'" class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg v-else class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </div>
                      {{ action === 'validate' ? 'Valider le résultat' : 'Refuser le résultat' }}
                    </DialogTitle>
                    <p class="mt-1 text-sm" :class="action === 'validate' ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'">
                      Résultat de {{ user.nom }} pour : <span class="font-medium">{{ tache.titre }}</span>
                    </p>
                  </div>
                  <button @click="$emit('close')" class="rounded-lg p-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Body -->
              <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
                <!-- Résumé du résultat -->
                <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                  <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Résumé du résultat soumis</h4>
                  
                  <div class="space-y-3 text-sm">
                    <div>
                      <span class="font-medium text-gray-700 dark:text-gray-300">Résultats obtenus :</span>
                      <p class="mt-1 text-gray-600 dark:text-gray-400">{{ resultSummary.resultats_obtenus }}</p>
                    </div>
                    
                    <div class="flex items-center justify-between">
                      <span class="font-medium text-gray-700 dark:text-gray-300">Taux de réalisation :</span>
                      <div class="flex items-center gap-2">
                        <div class="w-24 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                          <div 
                            class="h-full rounded-full transition-all"
                            :class="getTauxColorClass(resultSummary.taux_realisation)"
                            :style="{ width: `${resultSummary.taux_realisation}%` }"
                          ></div>
                        </div>
                        <span class="font-bold text-gray-900 dark:text-white">{{ resultSummary.taux_realisation }}%</span>
                      </div>
                    </div>

                    <div v-if="resultSummary.difficultes_rencontrees">
                      <span class="font-medium text-gray-700 dark:text-gray-300">Difficultés :</span>
                      <p class="mt-1 text-gray-600 dark:text-gray-400">{{ resultSummary.difficultes_rencontrees }}</p>
                    </div>
                  </div>
                </div>

                <!-- Commentaire de validation -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ action === 'validate' ? 'Commentaire de validation' : 'Motif du refus' }}
                    <span v-if="action === 'reject'" class="text-red-500">*</span>
                  </label>
                  <textarea
                    v-model="form.commentaire"
                    rows="4"
                    :required="action === 'reject'"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 resize-none"
                    :class="action === 'validate' ? 'focus:ring-green-500' : 'focus:ring-red-500'"
                    :placeholder="action === 'validate' 
                      ? 'Félicitations ! Excellent travail...' 
                      : 'Expliquez pourquoi le résultat ne peut pas être validé...'"
                  ></textarea>
                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ action === 'reject' ? 'Obligatoire en cas de refus' : 'Optionnel' }} • {{ form.commentaire.length }}/1000
                  </p>
                </div>

                <!-- Messages d'aide -->
                <div 
                  class="p-4 rounded-lg border"
                  :class="action === 'validate' 
                    ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' 
                    : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800'"
                >
                  <div class="flex gap-3">
                    <svg 
                      class="w-5 h-5 flex-shrink-0 mt-0.5"
                      :class="action === 'validate' ? 'text-green-500' : 'text-red-500'"
                      fill="currentColor" 
                      viewBox="0 0 20 20"
                    >
                      <path v-if="action === 'validate'" fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                      <path v-else fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <div class="flex-1">
                      <h4 
                        class="text-sm font-medium mb-1"
                        :class="action === 'validate' ? 'text-green-900 dark:text-green-300' : 'text-red-900 dark:text-red-300'"
                      >
                        {{ action === 'validate' ? 'Validation' : 'Refus' }}
                      </h4>
                      <p 
                        class="text-xs"
                        :class="action === 'validate' ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'"
                      >
                        <template v-if="action === 'validate'">
                          En validant, vous confirmez que le résultat correspond aux attentes.
                          {{ isN2Validation ? 'Cette validation finale permettra de clôturer la tâche.' : 'Le résultat devra encore être validé par le niveau N2.' }}
                        </template>
                        <template v-else>
                          En refusant, le résultat sera renvoyé à {{ user.nom }} pour correction.
                          Assurez-vous de fournir un motif clair et des indications précises.
                        </template>
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Niveau de validation -->
                <div v-if="action === 'validate'" class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                  <div class="flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span class="font-medium text-blue-900 dark:text-blue-300">
                      Validation {{ isN2Validation ? 'N2 (finale)' : 'N1' }}
                    </span>
                  </div>
                </div>
              </form>

              <!-- Footer -->
              <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 bg-gray-50 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                  <button type="button" @click="$emit('close')" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    Annuler
                  </button>
                  <button 
                    @click="handleSubmit"
                    :disabled="!isFormValid || submitting"
                    class="px-6 py-2 font-medium rounded-lg transition-all shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    :class="action === 'validate' 
                      ? 'bg-gradient-to-r from-green-500 to-green-600 text-white hover:from-green-600 hover:to-green-700' 
                      : 'bg-gradient-to-r from-red-500 to-red-600 text-white hover:from-red-600 hover:to-red-700'"
                  >
                    <svg v-if="submitting" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ submitting ? 'Traitement...' : action === 'validate' ? '✓ Valider' : '✗ Refuser' }}</span>
                  </button>
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue'
import api from '@/api/axios'

const props = defineProps({
  tache: {
    type: Object,
    required: true
  },
  user: {
    type: Object,
    required: true
  },
  action: {
    type: String,
    required: true,
    validator: (value) => ['validate', 'reject'].includes(value)
  }
})

const emit = defineEmits(['close', 'validated'])

// State
const submitting = ref(false)
const form = ref({
  commentaire: ''
})

// Computed
const resultSummary = computed(() => {
  // TODO: Récupérer le vrai résultat depuis l'API
  return {
    resultats_obtenus: props.tache.resultats_obtenus || 'Résultat non disponible',
    taux_realisation: props.tache.taux_realisation || 100,
    difficultes_rencontrees: props.tache.difficultes_rencontrees || null
  }
})

const isN2Validation = computed(() => {
  // Déterminer si c'est une validation N2
  return props.tache.validation_n1_validated && !props.tache.validation_n2_validated
})

const isFormValid = computed(() => {
  // En cas de refus, le commentaire est obligatoire
  if (props.action === 'reject') {
    return form.value.commentaire.length >= 10
  }
  return true
})

// Methods
function getTauxColorClass(taux) {
  if (taux >= 90) return 'bg-green-500'
  if (taux >= 70) return 'bg-blue-500'
  if (taux >= 50) return 'bg-amber-500'
  return 'bg-red-500'
}

async function handleSubmit() {
  if (!isFormValid.value || submitting.value) return

  submitting.value = true

  try {
    const endpoint = props.action === 'validate' 
      ? `/taches/resultats-individuels/${props.tache.result_id}/validate-n1`
      : `/taches/resultats-individuels/${props.tache.result_id}/reject`

    await api.post(endpoint, {
      commentaire: form.value.commentaire
    })

    emit('validated')
    emit('close')
  } catch (error) {
    console.error('Erreur validation:', error)
    alert(error.response?.data?.message || 'Erreur lors de la validation')
  } finally {
    submitting.value = false
  }
}

// Lifecycle
onMounted(() => {
  // Suggestions de commentaires selon l'action
  if (props.action === 'validate') {
    form.value.commentaire = `Excellent travail ! Le résultat correspond aux attentes.`
  }
})
</script>