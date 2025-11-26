<!-- resources\js\components\taches\ResultatForm.vue -->
<template>
  <TransitionRoot appear :show="true" as="template">
    <Dialog as="div" @close="$emit('close')" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/25 backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-3xl transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-xl transition-all">
              <!-- Header -->
              <div class="flex items-center justify-between mb-6">
                <div>
                  <DialogTitle class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                      <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </div>
                    {{ resultat ? 'Modifier' : 'Soumettre' }} mon résultat
                  </DialogTitle>
                  <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ tache.titre }}
                  </p>
                </div>
                <button
                  @click="$emit('close')"
                  class="rounded-lg p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Form -->
              <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Résultats attendus -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Résultats attendus <span class="text-red-500">*</span>
                  </label>
                  <textarea
                    v-model="form.resultats_attendus"
                    rows="3"
                    required
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 resize-none"
                    placeholder="Décrivez les résultats qui étaient attendus pour cette tâche..."
                  ></textarea>
                </div>

                <!-- Résultats obtenus -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Résultats obtenus <span class="text-red-500">*</span>
                  </label>
                  <textarea
                    v-model="form.resultats_obtenus"
                    rows="4"
                    required
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 resize-none"
                    placeholder="Décrivez en détail les résultats que vous avez obtenus..."
                  ></textarea>
                </div>

                <!-- Taux de réalisation -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Taux de réalisation <span class="text-red-500">*</span>
                  </label>
                  <div class="flex items-center gap-4">
                    <input
                      v-model.number="form.taux_realisation"
                      type="range"
                      min="0"
                      max="100"
                      step="5"
                      class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
                    />
                    <div class="flex items-center gap-2">
                      <input
                        v-model.number="form.taux_realisation"
                        type="number"
                        min="0"
                        max="100"
                        required
                        class="w-20 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-center font-bold"
                      />
                      <span class="text-gray-600 dark:text-gray-400 font-medium">%</span>
                    </div>
                  </div>
                  <div class="mt-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div
                      class="h-2 rounded-full transition-all"
                      :class="getProgressColor(form.taux_realisation)"
                      :style="{ width: `${form.taux_realisation}%` }"
                    ></div>
                  </div>
                </div>

                <!-- Difficultés rencontrées -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Difficultés rencontrées
                  </label>
                  <textarea
                    v-model="form.difficultes_rencontrees"
                    rows="3"
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 resize-none"
                    placeholder="Décrivez les difficultés que vous avez rencontrées (optionnel)..."
                  ></textarea>
                </div>

                <!-- Solutions envisagées -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Solutions envisagées
                  </label>
                  <textarea
                    v-model="form.solutions_envisagees"
                    rows="3"
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 resize-none"
                    placeholder="Décrivez les solutions que vous proposez (optionnel)..."
                  ></textarea>
                </div>

                <!-- Observations -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Observations complémentaires
                  </label>
                  <textarea
                    v-model="form.observations"
                    rows="2"
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 resize-none"
                    placeholder="Ajoutez vos observations (optionnel)..."
                  ></textarea>
                </div>

                <!-- Documents -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Documents justificatifs
                  </label>
                  <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center hover:border-green-500 transition-colors">
                    <input
                      ref="fileInput"
                      type="file"
                      multiple
                      accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif"
                      @change="handleFileUpload"
                      class="hidden"
                    />
                    <button
                      type="button"
                      @click="$refs.fileInput.click()"
                      class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                      </svg>
                      Ajouter des fichiers
                    </button>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                      PDF, Word, Excel, Images (max 10 Mo chacun)
                    </p>
                  </div>

                  <!-- Liste des fichiers -->
                  <div v-if="selectedFiles.length > 0" class="mt-4 space-y-2">
                    <div
                      v-for="(file, index) in selectedFiles"
                      :key="index"
                      class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-lg"
                    >
                      <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <div>
                          <p class="text-sm font-medium text-gray-900 dark:text-white">{{ file.name }}</p>
                          <p class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</p>
                        </div>
                      </div>
                      <button
                        type="button"
                        @click="removeFile(index)"
                        class="p-1 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded"
                      >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                  <button
                    type="button"
                    @click="$emit('close')"
                    class="px-6 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                  >
                    Annuler
                  </button>
                  <button
                    type="submit"
                    :disabled="submitting"
                    class="px-6 py-2.5 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 transition-all shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                  >
                    <svg
                      v-if="submitting"
                      class="animate-spin h-5 w-5"
                      fill="none"
                      viewBox="0 0 24 24"
                    >
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span v-if="submitting">Envoi en cours...</span>
                    <span v-else>
                      {{ resultat ? 'Mettre à jour' : 'Soumettre le résultat' }}
                    </span>
                  </button>
                </div>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import api from '@/api/axios'

const props = defineProps({
  tache: {
    type: Object,
    required: true
  },
  resultat: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

const submitting = ref(false)
const selectedFiles = ref([])
const fileInput = ref(null)

const form = reactive({
  resultats_attendus: '',
  resultats_obtenus: '',
  taux_realisation: 100,
  difficultes_rencontrees: '',
  solutions_envisagees: '',
  observations: ''
})

// Charger données si édition
onMounted(() => {
  if (props.resultat) {
    form.resultats_attendus = props.resultat.resultats_attendus || ''
    form.resultats_obtenus = props.resultat.resultats_obtenus || ''
    form.taux_realisation = props.resultat.taux_realisation || 100
    form.difficultes_rencontrees = props.resultat.difficultes_rencontrees || ''
    form.solutions_envisagees = props.resultat.solutions_envisagees || ''
    form.observations = props.resultat.observations || ''
  }
})

function handleFileUpload(event) {
  const files = Array.from(event.target.files)
  selectedFiles.value.push(...files)
}

function removeFile(index) {
  selectedFiles.value.splice(index, 1)
}

function formatFileSize(bytes) {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

function getProgressColor(progress) {
  if (progress < 30) return 'bg-red-500'
  if (progress < 70) return 'bg-amber-500'
  return 'bg-green-500'
}

async function handleSubmit() {
  submitting.value = true

  try {
    const formData = new FormData()
    formData.append('resultats_attendus', form.resultats_attendus)
    formData.append('resultats_obtenus', form.resultats_obtenus)
    formData.append('taux_realisation', form.taux_realisation)
    if (form.difficultes_rencontrees) {
      formData.append('difficultes_rencontrees', form.difficultes_rencontrees)
    }
    if (form.solutions_envisagees) {
      formData.append('solutions_envisagees', form.solutions_envisagees)
    }
    if (form.observations) {
      formData.append('observations', form.observations)
    }

    // Ajouter les fichiers
    selectedFiles.value.forEach((file, index) => {
      formData.append(`documents[${index}]`, file)
    })

    await api.post(`/taches/${props.tache.id}/submit-result`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    emit('saved')
  } catch (error) {
    console.error('Erreur soumission résultat:', error)
    alert(error.response?.data?.message || 'Erreur lors de la soumission du résultat')
  } finally {
    submitting.value = false
  }
}
</script>