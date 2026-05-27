<!-- resources/js/components/taches/ResultatForm.vue -->
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
            <DialogPanel class="relative w-full max-w-3xl transform overflow-hidden rounded-3 bg-white dark:bg-gray-800 transition-all">
              <!-- Header -->
              <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <div class="flex items-center justify-between">
                  <div>
                    <DialogTitle class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                      <div class="w-10 h-10 rounded-3 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                      </div>
                      {{ isEditing ? 'Modifier le résultat' : 'Soumettre un résultat' }}
                    </DialogTitle>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                      Tâche : <span class="font-medium">{{ tache.titre }}</span>
                    </p>
                  </div>
                  <button
                    @click="$emit('close')"
                    class="rounded-3 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                  >
                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Body -->
              <form @submit.prevent="handleSubmit" class="p-6 space-y-6 max-h-[calc(100vh-300px)] overflow-y-auto">
                
                <!-- Résultats attendus -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Résultats attendus <span class="text-red-500">*</span>
                  </label>
                  <textarea
                    v-model="form.resultats_attendus"
                    rows="3"
                    required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 resize-none"
                    placeholder="Décrivez ce qui était attendu de cette tâche..."
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
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 resize-none"
                    placeholder="Décrivez en détail ce que vous avez accompli..."
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
                      class="flex-1 h-3 bg-gray-200 rounded-3 appearance-none cursor-pointer dark:bg-gray-700"
                    />
                    <div class="flex items-center gap-2">
                      <input
                        v-model.number="form.taux_realisation"
                        type="number"
                        min="0"
                        max="100"
                        class="w-20 px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-center font-bold"
                      />
                      <span class="text-lg font-bold text-gray-700 dark:text-gray-300">%</span>
                    </div>
                  </div>
                </div>

                <!-- Difficultés -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Difficultés rencontrées
                  </label>
                  <textarea
                    v-model="form.difficultes_rencontrees"
                    rows="3"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 resize-none"
                    placeholder="Décrivez les obstacles rencontrés (optionnel)..."
                  ></textarea>
                </div>

                <!-- Solutions -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Solutions envisagées
                  </label>
                  <textarea
                    v-model="form.solutions_envisagees"
                    rows="3"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 resize-none"
                    placeholder="Comment avez-vous résolu ces difficultés ? (optionnel)..."
                  ></textarea>
                </div>

                <!-- Observations -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Observations / Commentaires
                  </label>
                  <textarea
                    v-model="form.observations"
                    rows="2"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 resize-none"
                    placeholder="Autres remarques (optionnel)..."
                  ></textarea>
                </div>

                <!-- Documents justificatifs -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Documents justificatifs
                  </label>
                  
                  <!-- 📁 Documents existants -->
                  <div v-if="existingDocuments.length > 0" class="mb-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-2">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" />
                      </svg>
                      Documents déjà soumis ({{ existingDocuments.length }})
                    </p>
                    <div class="space-y-2">
                      <div
                        v-for="document in existingDocuments"
                        :key="document.id"
                        class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-3 group hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors"
                      >
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                          <div class="w-8 h-8 rounded bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                          </div>
                          <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ document.nom }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatFileSize(document.taille) }}</p>
                          </div>
                        </div>
                        <div class="flex items-center gap-2">
                          <a
                            :href="document.url"
                            target="_blank"
                            class="p-2 hover:bg-blue-200 dark:hover:bg-blue-900/50 rounded transition-colors"
                            title="Voir le document"
                          >
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                          </a>
                          <button
                            v-if="isEditing"
                            type="button"
                            @click="removeExistingDocument(document.id)"
                            class="p-2 hover:bg-red-100 dark:hover:bg-red-900/30 rounded transition-colors"
                            title="Supprimer le document"
                          >
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Zone d'upload -->
                  <div
                    @drop.prevent="handleDrop"
                    @dragover.prevent="isDragging = true"
                    @dragleave="isDragging = false"
                    class="border-2 border-dashed rounded-3 p-6 text-center transition-colors"
                    :class="isDragging ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'border-gray-300 dark:border-gray-700'"
                  >
                    <input
                      ref="fileInput"
                      type="file"
                      multiple
                      accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                      @change="handleFileSelect"
                      class="hidden"
                    />
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                      Glissez-déposez des fichiers ou
                      <button
                        type="button"
                        @click="$refs.fileInput.click()"
                        class="text-purple-600 dark:text-purple-400 hover:underline font-medium"
                      >
                        parcourez
                      </button>
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      PDF, DOC, DOCX, XLS, XLSX, JPG, PNG (max 10 Mo)
                    </p>
                  </div>

                  <!-- Nouveaux fichiers sélectionnés -->
                  <div v-if="form.documents.length > 0" class="mt-3 space-y-2">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-2">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                      </svg>
                      Nouveaux fichiers à ajouter ({{ form.documents.length }})
                    </p>
                    <div
                      v-for="(file, index) in form.documents"
                      :key="index"
                      class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-3 group hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    >
                      <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div class="w-8 h-8 rounded bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0">
                          <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                          </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                          <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ file.name }}</p>
                          <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatFileSize(file.size) }}</p>
                        </div>
                      </div>
                      <button
                        type="button"
                        @click="removeFile(index)"
                        class="p-2 hover:bg-red-100 dark:hover:bg-red-900/30 rounded transition-colors opacity-0 group-hover:opacity-100"
                      >
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Info validation -->
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-3">
                  <div class="flex gap-3">
                    <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <div class="flex-1">
                      <h4 class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-1">
                        Validation du résultat
                      </h4>
                      <p class="text-xs text-blue-700 dark:text-blue-400">
                        Une fois soumis, ce résultat sera examiné par les validateurs. Vous pourrez le modifier jusqu'à sa validation.
                      </p>
                    </div>
                  </div>
                </div>

              </form>

              <!-- Footer -->
              <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 bg-gray-50 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                  <button
                    type="button"
                    @click="$emit('close')"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-3 transition-colors"
                  >
                    Annuler
                  </button>
                  <button
                    @click="handleSubmit"
                    :disabled="!isFormValid || submitting"
                    class="px-6 py-2 text-white font-medium rounded-3 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2"
                  >
                    <svg v-if="submitting" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ submitting ? 'Enregistrement...' : (isEditing ? 'Mettre à jour' : 'Enregistrer') }}</span>
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
  resultat: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

// State
const submitting = ref(false)
const isDragging = ref(false)
const fileInput = ref(null)
const existingDocuments = ref([])

const form = ref({
  resultats_attendus: '',
  resultats_obtenus: '',
  taux_realisation: 100,
  difficultes_rencontrees: '',
  solutions_envisagees: '',
  observations: '',
  documents: [],
  documents_to_delete: []
})

// Computed
const isEditing = computed(() => !!props.resultat)

const isFormValid = computed(() => {
  return form.value.resultats_attendus.length >= 10 &&
         form.value.resultats_obtenus.length >= 10 &&
         form.value.taux_realisation >= 0 &&
         form.value.taux_realisation <= 100
})

// Methods
async function loadExistingDocuments() {
  if (isEditing.value && props.resultat) {
    try {
      const { data } = await api.get(`/taches/${props.tache.id}/resultats/${props.resultat.id}/documents`)
      existingDocuments.value = data.data || []
    } catch (error) {
      console.error('Erreur chargement documents:', error)
    }
  }
}

function removeExistingDocument(documentId) {
  form.value.documents_to_delete.push(documentId)
  existingDocuments.value = existingDocuments.value.filter(doc => doc.id !== documentId)
}

function handleFileSelect(event) {
  const files = Array.from(event.target.files)
  addFiles(files)
  event.target.value = ''
}

function handleDrop(event) {
  isDragging.value = false
  const files = Array.from(event.dataTransfer.files)
  addFiles(files)
}

function addFiles(files) {
  const validFiles = files.filter(file => {
    if (file.size > 10 * 1024 * 1024) {
      alert(`Le fichier "${file.name}" dépasse 10 Mo`)
      return false
    }
    
    const validTypes = ['.pdf', '.doc', '.docx', '.xls', '.xlsx', '.jpg', '.jpeg', '.png']
    const ext = '.' + file.name.split('.').pop().toLowerCase()
    if (!validTypes.includes(ext)) {
      alert(`Le fichier "${file.name}" n'est pas un format accepté`)
      return false
    }
    
    return true
  })

  form.value.documents.push(...validFiles)
}

function removeFile(index) {
  form.value.documents.splice(index, 1)
}

function formatFileSize(bytes) {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

async function handleSubmit() {
  if (!isFormValid.value || submitting.value) return

  submitting.value = true

  try {
    const formData = new FormData()
    
    // Champs texte
    formData.append('resultats_attendus', form.value.resultats_attendus)
    formData.append('resultats_obtenus', form.value.resultats_obtenus)
    formData.append('taux_realisation', form.value.taux_realisation)
    
    if (form.value.difficultes_rencontrees) {
      formData.append('difficultes_rencontrees', form.value.difficultes_rencontrees)
    }
    if (form.value.solutions_envisagees) {
      formData.append('solutions_envisagees', form.value.solutions_envisagees)
    }
    if (form.value.observations) {
      formData.append('observations', form.value.observations)
    }

    // Nouveaux documents
    form.value.documents.forEach((file, index) => {
      formData.append(`documents[${index}]`, file)
    })

    // Documents à supprimer
    form.value.documents_to_delete.forEach((docId, index) => {
      formData.append(`documents_to_delete[${index}]`, docId)
    })

    let response
    if (isEditing.value) {
      // Mise à jour
      formData.append('_method', 'PUT')
      response = await api.put(
        `/taches/${props.tache.id}/resultats/${props.resultat.id}`,
        formData,
        {
          headers: { 'Content-Type': 'multipart/form-data' }
        }
      )
    } else {
      // Création
      response = await api.post(
        `/taches/${props.tache.id}/resultats`,
        formData,
        {
          headers: { 'Content-Type': 'multipart/form-data' }
        }
      )
    }

    emit('saved', response.data.data)
    emit('close')
  } catch (error) {
    console.error('Erreur:', error)
    alert(error.response?.data?.message || 'Erreur lors de l\'enregistrement')
  } finally {
    submitting.value = false
  }
}

// Lifecycle
onMounted(async () => {
  if (isEditing.value && props.resultat) {
    form.value.resultats_attendus = props.resultat.resultats_attendus || ''
    form.value.resultats_obtenus = props.resultat.resultats_obtenus || ''
    form.value.taux_realisation = props.resultat.taux_realisation || 100
    form.value.difficultes_rencontrees = props.resultat.difficultes_rencontrees || ''
    form.value.solutions_envisagees = props.resultat.solutions_envisagees || ''
    form.value.observations = props.resultat.observations || ''
    
    await loadExistingDocuments()
  }
})
</script>