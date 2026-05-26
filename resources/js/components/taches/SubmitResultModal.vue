<!-- resources/js/components/taches/SubmitResultModal.vue -->
<template>
  <TransitionRoot :show="true" as="template">
    <Dialog as="div" class="relative z-50" @close="$emit('close')">
      <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100"
        leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100" leave="ease-in duration-200" leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95">
            <DialogPanel
              class="relative w-full max-w-3xl transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-xl transition-all">
              <!-- Header -->
              <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <div class="flex items-center justify-between">
                  <div>
                    <DialogTitle class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                      <div
                        class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                      </div>
                      {{ isEditing ? 'Modifier mon résultat' : 'Soumettre mon résultat' }}
                    </DialogTitle>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                      Tâche : <span class="font-medium">{{ tache.titre }}</span>
                    </p>
                  </div>
                  <button @click="$emit('close')"
                    class="rounded-lg p-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Body -->
              <form @submit.prevent="handleSubmit" class="p-6 space-y-6 max-h-[calc(100vh-300px)] overflow-y-auto">

                <!-- Documents existants (en mode édition) -->
                <div v-if="isEditing && existingDocuments.length > 0"
                  class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                  <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Documents déjà soumis ({{ existingDocuments.length }})
                  </h4>
                  <div class="space-y-2">
                    <div v-for="doc in existingDocuments" :key="doc.id"
                      class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                      <div class="flex items-center gap-3 flex-1 min-w-0">
                        <!-- Icône du type de fichier -->
                        <div :class="getFileIconClass(doc)"
                          class="w-8 h-8 rounded flex items-center justify-center flex-shrink-0">
                          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                          </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                          <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ doc.nom }}<span class="text-gray-400">.{{ doc.extension }}</span>
                          </p>
                          <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ formatFileSize(doc.taille) }}</span>
                            <span>•</span>
                            <span class="capitalize">{{ doc.extension }}</span>
                            <span v-if="doc.download_count > 0">•</span>
                            <span v-if="doc.download_count > 0" class="flex items-center gap-1">
                              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                              </svg>
                              {{ doc.download_count }}
                            </span>
                          </div>
                        </div>
                      </div>
                      <div class="flex items-center gap-1">
                        <!-- Bouton Prévisualiser -->
                        <button v-if="doc.can_view && (doc.is_image || doc.is_pdf)" type="button"
                          @click="previewDocument(doc)"
                          class="p-2 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded transition-colors group"
                          title="Prévisualiser le document">
                          <svg class="w-4 h-4 text-blue-500 group-hover:text-blue-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
                        </button>

                        <!-- Bouton Télécharger -->
                        <button v-if="doc.can_download" type="button" @click="downloadDocument(doc)"
                          class="p-2 hover:bg-green-100 dark:hover:bg-green-900/30 rounded transition-colors group"
                          title="Télécharger le document">
                          <svg class="w-4 h-4 text-green-500 group-hover:text-green-600" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                          </svg>
                        </button>

                        <!-- Bouton Supprimer -->
                        <button type="button" @click="markForDeletion(doc.id)"
                          class="p-2 hover:bg-red-100 dark:hover:bg-red-900/30 rounded transition-colors group"
                          :class="{ 'bg-red-100 dark:bg-red-900/30': isMarkedForDeletion(doc.id) }"
                          :title="isMarkedForDeletion(doc.id) ? 'Annuler la suppression' : 'Supprimer le document'">
                          <svg class="w-4 h-4 text-red-500 group-hover:text-red-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                          </svg>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>


                <!-- Résultats attendus -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Résultats attendus <span class="text-red-500">*</span>
                  </label>
                  <textarea v-model="form.resultats_attendus" rows="3" required
                    dusk="submit-result-attendus"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 resize-none"
                    placeholder="Décrivez ce qui était attendu de cette tâche..."></textarea>
                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Minimum 10 caractères • {{ form.resultats_attendus.length }}/1000
                  </p>
                </div>

                <!-- Résultats obtenus -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Résultats obtenus <span class="text-red-500">*</span>
                  </label>
                  <textarea v-model="form.resultats_obtenus" rows="4" required
                    dusk="submit-result-textarea"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 resize-none"
                    placeholder="Décrivez en détail ce que vous avez accompli..."></textarea>
                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Minimum 10 caractères • {{ form.resultats_obtenus.length }}/2000
                  </p>
                </div>

                <!-- Taux de réalisation -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Taux de réalisation <span class="text-red-500">*</span>
                  </label>
                  <div class="flex items-center gap-4">
                    <input v-model.number="form.taux_realisation" type="range" min="0" max="100" step="5"
                      class="flex-1 h-3 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
                      :style="tauxSliderStyle" />
                    <div class="flex items-center gap-2">
                      <input v-model.number="form.taux_realisation" type="number" min="0" max="100"
                        class="w-20 px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-center font-bold text-lg" />
                      <span class="text-lg font-bold text-gray-700 dark:text-gray-300">%</span>
                    </div>
                  </div>
                  <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ getTauxLabel(form.taux_realisation) }}
                  </p>
                </div>

                <!-- Difficultés rencontrées -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Difficultés rencontrées
                  </label>
                  <textarea v-model="form.difficultes_rencontrees" rows="3"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 resize-none"
                    placeholder="Décrivez les obstacles ou challenges rencontrés (optionnel)..."></textarea>
                </div>

                <!-- Solutions envisagées -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Solutions envisagées
                  </label>
                  <textarea v-model="form.solutions_envisagees" rows="3"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 resize-none"
                    placeholder="Comment avez-vous résolu ou comptez résoudre ces difficultés ? (optionnel)..."></textarea>
                </div>

                <!-- Observations -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Observations / Commentaires
                  </label>
                  <textarea v-model="form.observations" rows="2"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 resize-none"
                    placeholder="Autres remarques ou suggestions (optionnel)..."></textarea>
                </div>

                <!-- Documents justificatifs -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Documents justificatifs {{ isEditing ? '(nouveaux)' : '' }}
                  </label>
                  <div @drop.prevent="handleDrop" @dragover.prevent="isDragging = true" @dragleave="isDragging = false"
                    class="border-2 border-dashed rounded-lg p-6 text-center transition-colors"
                    :class="isDragging ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'border-gray-300 dark:border-gray-700'">
                    <input ref="fileInput" type="file" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                      @change="handleFileSelect" class="hidden" />
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor"
                      viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                      Glissez-déposez des fichiers ou
                      <button type="button" @click="$refs.fileInput.click()"
                        class="text-purple-600 dark:text-purple-400 hover:underline font-medium">
                        parcourez
                      </button>
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      PDF, DOC, DOCX, XLS, XLSX, JPG, PNG (max 10 Mo par fichier)
                    </p>
                  </div>

                  <!-- Liste des nouveaux fichiers -->
                  <div v-if="form.documents.length > 0" class="mt-3 space-y-2">
                    <div v-for="(file, index) in form.documents" :key="index"
                      class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                      <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div
                          class="w-8 h-8 rounded bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0">
                          <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                          </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                          <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ file.name }}</p>
                          <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatFileSize(file.size) }}</p>
                        </div>
                      </div>
                      <button type="button" @click="removeFile(index)"
                        class="p-1 hover:bg-red-100 dark:hover:bg-red-900/30 rounded transition-colors">
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                        </svg>
                      </button>
                    </div>
                  </div>

                </div>
              </form>

              <!-- Footer -->
              <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 bg-gray-50 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                  <button type="button" @click="$emit('close')"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    Annuler
                  </button>
                  <button @click="handleSubmit" :disabled="!isFormValid || submitting"
                    dusk="submit-result-submit-btn"
                    class="px-6 py-2 bg-gradient-to-r from-purple-500 to-indigo-600 text-white font-medium rounded-lg hover:from-purple-600 hover:to-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                    <svg v-if="submitting" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                      </path>
                    </svg>
                    <span>{{ submitting ? 'Soumission...' : isEditing ? 'Mettre à jour' : 'Soumettre mon résultat'
                    }}</span>
                  </button>
                </div>
              </div>

              <!-- Modal de prévisualisation amélioré -->
              <transition name="fade">
                <div v-if="showPreviewModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80">
                  <div
                    class="bg-white dark:bg-gray-800 rounded-xl max-w-4xl w-full max-h-[90vh] overflow-hidden shadow-2xl">
                    <!-- Header du modal de prévisualisation -->
                    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                      <div class="flex items-center gap-3">
                        <div :class="getFileIconClass(previewDoc)"
                          class="w-8 h-8 rounded flex items-center justify-center">
                          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                          </svg>
                        </div>
                        <div>
                          <h3 class="font-semibold text-gray-900 dark:text-white">{{ previewDoc?.nom }}</h3>
                          <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ formatFileSize(previewDoc?.taille) }} • {{ previewDoc?.extension?.toUpperCase() }}
                          </p>
                        </div>
                      </div>
                      <div class="flex items-center gap-2">
                        <!-- Bouton Télécharger dans le modal -->
                        <button v-if="previewDoc?.can_download" @click="downloadDocument(previewDoc)"
                          class="p-2 hover:bg-green-100 dark:hover:bg-green-900/30 rounded transition-colors"
                          title="Télécharger">
                          <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                          </svg>
                        </button>
                        <button @click="closePreviewModal"
                          class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors">
                          <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12" />
                          </svg>
                        </button>
                      </div>
                    </div>

                    <!-- Contenu de prévisualisation -->
                    <div class="p-4 max-h-[calc(90vh-80px)] overflow-auto">
                      <!-- Loader -->
                      <div v-if="previewLoading" class="flex items-center justify-center py-12">
                        <svg class="animate-spin h-8 w-8 text-purple-600" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                          </circle>
                          <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                          </path>
                        </svg>
                      </div>

                      <!-- Erreur -->
                      <div v-else-if="previewError" class="text-center py-12">
                        <div
                          class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900/20 flex items-center justify-center">
                          <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12" />
                          </svg>
                        </div>
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                          Erreur de chargement
                        </h4>
                        <p class="text-gray-500 dark:text-gray-400">
                          Impossible de charger le document
                        </p>
                      </div>


                      <div v-else-if="previewDoc && !previewLoading">
                        <!-- Image -->
                        <img v-if="previewDoc.is_image" :src="previewDoc.url" :alt="previewDoc.nom"
                          class="max-w-full max-h-[70vh] mx-auto rounded-lg shadow-lg" />

                        <!-- PDF -->
                        <iframe v-else-if="previewDoc.is_pdf" :src="previewDoc.url"
                          class="w-full h-[70vh] rounded-lg border-0" frameborder="0">
                        </iframe>

                        <!-- Fichier non prévisualisable -->
                        <div v-else class="text-center py-12">
                          <div
                            class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                          </div>
                          <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                            Aucune prévisualisation disponible
                          </h4>
                          <p class="text-gray-500 dark:text-gray-400 mb-4">
                            Ce type de fichier ne peut pas être prévisualisé dans le navigateur.
                          </p>
                          <button @click="downloadDocument(previewDoc)"
                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors flex items-center gap-2 mx-auto">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Télécharger le fichier
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </transition>


            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue'
import api from '@/api/axios'
import { useToast } from "vue-toastification"
const toast = useToast()

const props = defineProps({
  tache: {
    type: Object,
    required: true
  }
})

// States pour prévisualisation
const showPreviewModal = ref(false)
const previewDoc = ref(null)
const previewLoading = ref(false)
const previewError = ref(false)

const emit = defineEmits(['close', 'submitted'])

// State
const submitting = ref(false)
const isDragging = ref(false)
const fileInput = ref(null)
const existingDocuments = ref([])
const documentsToDelete = ref([])

const form = ref({
  resultats_attendus: '',
  resultats_obtenus: '',
  taux_realisation: 100,
  difficultes_rencontrees: '',
  solutions_envisagees: '',
  observations: '',
  documents: []
})

// Computed
const isEditing = computed(() => !!props.tache.my_result)

const isFormValid = computed(() => {
  return form.value.resultats_attendus.length >= 10 &&
    form.value.resultats_obtenus.length >= 10 && form.value.taux_realisation >= 0 && form.value.taux_realisation <= 100
})

const tauxSliderStyle = computed(() => {
  const color = getTauxColor(form.value.taux_realisation)
  return {
    background: `linear-gradient(to right, ${color} 0%, ${color} ${form.value.taux_realisation}%, #e5e7eb ${form.value.taux_realisation}%, #e5e7eb 100%)`
  }
})

// Methods
function getTauxColor(taux) {
  if (taux >= 90) return '#10B981'
  if (taux >= 70) return '#3B82F6'
  if (taux >= 50) return '#F59E0B'
  return '#EF4444'
}

function getTauxLabel(taux) {
  if (taux >= 90) return '✅ Excellent - Objectifs largement atteints'
  if (taux >= 70) return '👍 Bien - Objectifs majoritairement atteints'
  if (taux >= 50) return '⚠️ Moyen - Objectifs partiellement atteints'
  return '❌ Faible - Objectifs peu atteints'
}

function getFileIconClass(doc) {
  const baseClasses = 'w-8 h-8 rounded flex items-center justify-center flex-shrink-0'

  if (doc.is_image) return `${baseClasses} bg-green-500`
  if (doc.is_pdf) return `${baseClasses} bg-red-500`
  if (doc.is_video) return `${baseClasses} bg-purple-500`
  if (doc.is_audio) return `${baseClasses} bg-yellow-500`

  // Par extension
  const ext = doc.extension?.toLowerCase()
  if (['doc', 'docx'].includes(ext)) return `${baseClasses} bg-blue-500`
  if (['xls', 'xlsx'].includes(ext)) return `${baseClasses} bg-green-600`
  if (['zip', 'rar'].includes(ext)) return `${baseClasses} bg-orange-500`

  return `${baseClasses} bg-gray-500`
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
      toast.error(`Le fichier "${file.name}" dépasse 10 Mo`)
      return false
    }

    const validTypes = ['.pdf', '.doc', '.docx', '.xls', '.xlsx', '.jpg', '.jpeg', '.png']
    const ext = '.' + file.name.split('.').pop().toLowerCase()
    if (!validTypes.includes(ext)) {
      toast.error(`Le fichier "${file.name}" n'est pas un format accepté`)
      return false
    }

    return true
  })

  form.value.documents.push(...validFiles)

  if (validFiles.length > 0) {
    toast.success(`${validFiles.length} fichier(s) ajouté(s)`)
  }
}

function removeFile(index) {
  const file = form.value.documents[index]
  form.value.documents.splice(index, 1)
  toast.info(`Fichier "${file.name}" retiré`)
}

function markForDeletion(docId) {
  const index = documentsToDelete.value.indexOf(docId)
  const doc = existingDocuments.value.find(d => d.id === docId)

  if (index === -1) {
    documentsToDelete.value.push(docId)
    toast.info(`"${doc.nom}" marqué pour suppression`)
  } else {
    documentsToDelete.value.splice(index, 1)
    toast.info(`Suppression de "${doc.nom}" annulée`)
  }
}

function isMarkedForDeletion(docId) {
  return documentsToDelete.value.includes(docId)
}

// Prévisualiser un document
async function previewDocument(doc) {
  previewLoading.value = true
  previewError.value = false
  
  try {
    // Utiliser Axios pour récupérer le fichier avec le token
    const response = await api.get(
      `/taches/${props.tache.id}/resultats/${props.tache.my_result.id}/documents/${doc.id}/view`,
      { 
        responseType: 'blob',
        headers: {
          'Accept': doc.is_pdf ? 'application/pdf' : (doc.is_image ? 'image/*' : '*/*')
        }
      }
    )
    
    // Créer une URL blob locale
    const blobUrl = window.URL.createObjectURL(response.data)
    
    previewDoc.value = {
      ...doc,
      url: blobUrl,
      is_image: doc.is_image,
      is_pdf: doc.is_pdf,
      blobCreated: true // Pour savoir qu'il faut le nettoyer
    }
    
    showPreviewModal.value = true
    previewLoading.value = false
  } catch (error) {
    console.error('Erreur prévisualisation:', error)
    previewLoading.value = false
    previewError.value = true
    
    if (error.response?.status === 401) {
      toast.error('Session expirée. Veuillez vous reconnecter.')
    } else if (error.response?.status === 403) {
      toast.error('Vous n\'avez pas la permission de voir ce document')
    } else {
      toast.error('Erreur lors de la prévisualisation du document')
    }
  }
}

// Fermer le modal de prévisualisation
function closePreviewModal() {
  // Libérer la mémoire du blob URL
  if (previewDoc.value?.blobCreated && previewDoc.value?.url) {
    window.URL.revokeObjectURL(previewDoc.value.url)
  }
  
  showPreviewModal.value = false
  previewDoc.value = null
  previewLoading.value = false
  previewError.value = false
}

// Télécharger un document
async function downloadDocument(doc) {
  try {
    const response = await api.get(
      `/taches/${props.tache.id}/resultats/${props.tache.my_result.id}/documents/${doc.id}/download`,
      {
        responseType: 'blob',
        onDownloadProgress: (progressEvent) => {
          // Optionnel: Afficher une barre de progression
          const percent = Math.round((progressEvent.loaded * 100) / progressEvent.total)
          console.log(`Téléchargement: ${percent}%`)
        }
      }
    )

    // Créer un lien de téléchargement
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `${doc.nom}.${doc.extension}`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)

    toast.success('📥 Document téléchargé avec succès')
  } catch (error) {
    console.error('Erreur téléchargement:', error)
    toast.error('❌ Erreur lors du téléchargement')
  }
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

    // Documents
    form.value.documents.forEach((file, index) => {
      formData.append(`documents[${index}]`, file)
    })

    // Documents à supprimer
    if (documentsToDelete.value.length > 0) {
      documentsToDelete.value.forEach((docId, index) => {
        formData.append(`documents_to_delete[${index}]`, docId)
      })
    }

    await api.post(`/taches/${props.tache.id}/submit-my-result`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    toast.success('✅ Résultat soumis avec succès')
    emit('submitted')
    emit('close')
  } catch (error) {
    console.error('Erreur soumission:', error)
    toast.error(error.response?.data?.message || '❌ Erreur lors de la soumission du résultat')
  } finally {
    submitting.value = false
  }
}

// Lifecycle
onMounted(async () => {
  if (isEditing.value) {
    const result = props.tache.my_result
    form.value.resultats_attendus = result.resultats_attendus || ''
    form.value.resultats_obtenus = result.resultats_obtenus || ''
    form.value.taux_realisation = result.taux_realisation || 100
    form.value.difficultes_rencontrees = result.difficultes_rencontrees || ''
    form.value.solutions_envisagees = result.solutions_envisagees || ''
    form.value.observations = result.observations || ''

    // Charger les documents existants
    try {
      const { data } = await api.get(`/taches/${props.tache.id}/resultats/${result.id}`)
      existingDocuments.value = data.data.documents || []
    } catch (error) {
      console.error('Erreur chargement documents:', error)
      toast.error('Erreur lors du chargement des documents')
    }
  }
})

// Nettoyer au démontage du composant
onBeforeUnmount(() => {
  if (previewDoc.value?.blobCreated && previewDoc.value?.url) {
    window.URL.revokeObjectURL(previewDoc.value.url)
  }
})

</script>

<style scoped>
input[type="range"]::-webkit-slider-thumb {
  appearance: none;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: white;
  border: 3px solid currentColor;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

input[type="range"]::-moz-range-thumb {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: white;
  border: 3px solid currentColor;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s, transform 0.3s;
}

.fade-enter-from {
  opacity: 0;
  transform: scale(0.9);
}

.fade-leave-to {
  opacity: 0;
  transform: scale(1.1);
}
</style>