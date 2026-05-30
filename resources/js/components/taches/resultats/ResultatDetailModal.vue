<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-900/75 transition-opacity" @click="$emit('close')"></div>

    <!-- Modal -->
    <div class="flex min-h-screen items-center justify-center p-4">
      <div
        class="relative bg-white dark:bg-gray-900 rounded-3 w-full max-w-5xl max-h-[90vh] overflow-hidden transform transition-all">
        <!-- Header amélioré -->
        <div
          class="sticky top-0 z-20 p-6 border-b border-gray-200 dark:border-gray-700 ">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <span
                  class="text-xs font-mono px-2.5 py-1 bg-white/60 dark:bg-black/30 rounded-3 border border-gray-300/30 dark:border-gray-700/30">
                  {{ resultat.tache?.code }}
                </span>
                <span class="text-xs font-medium px-3 py-1 rounded-full " :class="validationStatusClass">
                  {{ validationStatusLabel }}
                </span>
                <span v-if="isUrgent"
                  class="text-xs font-medium px-2.5 py-1 text-white rounded-full animate-pulse flex items-center gap-1">
                  <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                      clip-rule="evenodd" />
                  </svg>
                  URGENT
                </span>
              </div>
              <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                {{ resultat.tache?.titre }}
              </h3>
              <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                <span class="flex items-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                  {{ resultat.tache?.activite?.nom }}
                </span>
                <span class="text-gray-400">•</span>
                <span class="flex items-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                  {{ resultat.tache?.activite?.projet?.nom }}
                </span>
              </div>
            </div>

            <button @click="$emit('close')"
              class="p-2 hover:bg-white/50 dark:hover:bg-black/20 rounded-3 transition-all duration-200 ">
              <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Body avec navigation améliorée -->
        <div class="flex h-[calc(90vh-140px)]">
          <!-- Sidebar de navigation -->
          <div
            class="w-64 border-r border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30 overflow-y-auto">
            <nav class="p-4 space-y-2">
              <a v-for="section in sections" :key="section.id" :href="`#${section.id}`"
                class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-3 transition-all duration-200"
                :class="activeSection === section.id
                  ? 'bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400'
                  : 'text-gray-600 dark:text-gray-400 hover:bg-white/50 dark:hover:bg-gray-800/50'"
                @click.prevent="scrollToSection(section.id)">
                <component :is="section.icon" class="w-4 h-4" />
                <span>{{ section.label }}</span>
                <span v-if="section.badge" class="ml-auto text-xs px-2 py-0.5 rounded-full" :class="section.badgeClass">
                  {{ section.badge }}
                </span>
              </a>
            </nav>
          </div>

          <!-- Contenu principal -->
          <div class="flex-1 overflow-y-auto" ref="contentRef">
            <div class="p-6 space-y-8">
              <!-- Section: Informations générales -->
              <section id="informations" class="scroll-mt-6">
                <div class="mb-6">
                  <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Informations générales
                  </h4>

                  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Carte Collaborateur -->
                    <div
                      class="rounded-3 p-5 border border-blue-200 dark:border-blue-800">
                      <h5 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                            clip-rule="evenodd" />
                        </svg>
                        Collaborateur
                      </h5>
                      <div class="flex items-center gap-3">
                        <div class="relative">
                          <img v-if="resultat.user?.avatar" :src="getImageUrl(resultat.user.avatar)"
                            :alt="resultat.user.nom"
                            class="w-14 h-14 rounded-full border-3 border-white dark:border-gray-700 " />
                          <div v-else
                            class="w-14 h-14 rounded-full flex items-center justify-center text-lg font-bold text-white border-3 border-white dark:border-gray-700 "
                            :style="{ backgroundColor: stringToColor(resultat.user?.nom) }">
                            {{ getInitials(resultat.user?.nom) }}
                          </div>
                        </div>
                        <div class="flex-1">
                          <p class="font-semibold text-gray-900 dark:text-white">{{ resultat.user?.nom }}</p>
                          <p class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ resultat.user?.email }}</p>
                          <div class="flex items-center gap-2 mt-1">
                            <span
                              class="text-xs px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded">
                              {{ resultat.user?.role || 'Membre' }}
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Carte Chronologie -->
                    <div
                      class="rounded-3 p-5 border border-gray-200 dark:border-gray-700">
                      <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Chronologie
                      </h5>
                      <div class="space-y-3">
                        <div class="flex items-center justify-between">
                          <span class="text-sm text-gray-600 dark:text-gray-400">Soumission</span>
                          <span class="text-sm font-medium text-gray-900 dark:text-white">{{
                            formatDateTime(resultat.soumis_le) }}</span>
                        </div>
                        <div v-if="resultat.valide_le_n1" class="flex items-center justify-between">
                          <span class="text-sm text-gray-600 dark:text-gray-400">Validation N1</span>
                          <span class="text-sm font-medium text-gray-900 dark:text-white">{{
                            formatDateTime(resultat.valide_le_n1) }}</span>
                        </div>
                        <div v-if="resultat.valide_le_n2" class="flex items-center justify-between">
                          <span class="text-sm text-gray-600 dark:text-gray-400">Validation N2</span>
                          <span class="text-sm font-medium text-gray-900 dark:text-white">{{
                            formatDateTime(resultat.valide_le_n2) }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Taux de réalisation amélioré -->
                <div
                  class="rounded-3 p-6 border border-emerald-200 dark:border-emerald-800">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Taux de réalisation</p>
                      <p
                        class="text-5xl font-bold bg-clip-text text-transparent">
                        {{ resultat.taux_realisation }}%
                      </p>
                      <div class="flex items-center gap-2 mt-3">
                        <div class="flex items-center gap-1">
                          <div class="w-3 h-3 rounded-full"
                            :class="getProgressIndicatorClass(resultat.taux_realisation)"></div>
                          <span class="text-xs text-gray-600 dark:text-gray-400">{{
                            getProgressLabel(resultat.taux_realisation) }}</span>
                        </div>
                      </div>
                    </div>
                    <div class="relative">
                      <div class="w-28 h-28">
                        <svg class="w-full h-full transform -rotate-90">
                          <circle cx="56" cy="56" r="52" stroke="currentColor" stroke-width="8" fill="none"
                            class="text-gray-200 dark:text-gray-700" />
                          <circle cx="56" cy="56" r="52" stroke="currentColor" stroke-width="8" fill="none"
                            stroke-linecap="round" :stroke-dasharray="`${2 * Math.PI * 52}`"
                            :stroke-dashoffset="`${2 * Math.PI * 52 * (1 - resultat.taux_realisation / 100)}`"
                            class="transition-all duration-1000 ease-out" :class="progressColor" />
                        </svg>
                      </div>
                      <div class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center">
                          <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ resultat.taux_realisation
                            }}%</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              <!-- Section: Résultats -->
              <section id="resultats" class="scroll-mt-6">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                  <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Résultats
                </h4>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                  <!-- Résultats attendus -->
                  <div
                    class="rounded-3 p-5 border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center gap-3 mb-4">
                      <div
                        class="w-10 h-10 rounded-3 bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                          viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                      </div>
                      <div>
                        <h5 class="font-semibold text-blue-900 dark:text-blue-300">Résultats attendus</h5>
                        <p class="text-sm text-blue-600 dark:text-blue-400">Objectifs fixés</p>
                      </div>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">
                      {{ resultat.resultats_attendus }}
                    </p>
                  </div>

                  <!-- Résultats obtenus -->
                  <div
                    class="rounded-3 p-5 border border-green-200 dark:border-green-800">
                    <div class="flex items-center gap-3 mb-4">
                      <div
                        class="w-10 h-10 rounded-3 bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                          viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </div>
                      <div>
                        <h5 class="font-semibold text-green-900 dark:text-green-300">Résultats obtenus</h5>
                        <p class="text-sm text-green-600 dark:text-green-400">Livré par le collaborateur</p>
                      </div>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">
                      {{ resultat.resultats_obtenus }}
                    </p>
                  </div>
                </div>
              </section>

              <!-- Section: Documents améliorée -->
              <section id="documents" class="scroll-mt-6" v-if="resultat.documents && resultat.documents.length > 0">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                  <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                  </svg>
                  Documents joints ({{ resultat.documents.length }})
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div v-for="doc in resultat.documents" :key="doc.id"
                    class="group relative bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-700 transition-all duration-300 overflow-hidden">
                    <div class="p-4">
                      <div class="flex items-start gap-3">
                        <!-- Icône de fichier -->
                        <div :class="getFileIconClass(doc)"
                          class="w-12 h-12 rounded-3 flex items-center justify-center flex-shrink-0 ">
                          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                          </svg>
                        </div>

                        <!-- Informations du fichier -->
                        <div class="flex-1 min-w-0">
                          <div class="flex items-start justify-between gap-2">
                            <div class="flex-1">
                              <p
                                class="text-sm font-semibold text-gray-900 dark:text-white truncate group-hover:text-purple-600 dark:group-hover:text-purple-400">
                                {{ doc.nom }}
                              </p>
                              <div class="flex items-center gap-3 mt-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                  {{ formatFileSize(doc.taille) }}
                                </span>
                                <span
                                  class="text-xs px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded font-medium uppercase">
                                  {{ doc.extension }}
                                </span>
                              </div>
                            </div>
                          </div>

                          <!-- Actions -->
                          <div class="flex items-center gap-2 mt-3">
                            <!-- Prévisualisation pour les images et PDF -->
                            <button v-if="canPreview(doc)" @click="previewDocument(doc)"
                              class="flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30 hover:bg-purple-100 dark:hover:bg-purple-900/50 rounded-3 transition-colors">
                              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                              </svg>
                              Prévisualiser
                            </button>

                            <!-- Téléchargement -->
                            <a :href="doc.url" download target="_blank"
                              class="flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-3 transition-colors">
                              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                              </svg>
                              Télécharger
                            </a>
                          </div>
                        </div>
                      </div>

                      <!-- Miniature pour les images -->
                      <div v-if="isImage(doc)"
                        class="mt-4 rounded-3 overflow-hidden border border-gray-200 dark:border-gray-700">
                        <img :src="doc.url" :alt="doc.nom"
                          class="w-full h-32 object-cover transition-transform duration-300" />
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              <!-- Section: Difficultés et solutions -->
              <section id="difficultes" class="scroll-mt-6"
                v-if="resultat.difficultes_rencontrees || resultat.solutions_envisagees">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                  <svg class="w-5 h-5 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                      d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                      clip-rule="evenodd" />
                  </svg>
                  Analyse du collaborateur
                </h4>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                  <!-- Difficultés -->
                  <div v-if="resultat.difficultes_rencontrees"
                    class="rounded-3 p-5 border border-orange-200 dark:border-orange-800">
                    <div class="flex items-center gap-3 mb-4">
                      <div
                        class="w-10 h-10 rounded-3 bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="currentColor"
                          viewBox="0 0 20 20">
                          <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                        </svg>
                      </div>
                      <div>
                        <h5 class="font-semibold text-orange-900 dark:text-orange-300">Difficultés rencontrées</h5>
                        <p class="text-sm text-orange-600 dark:text-orange-400">Problèmes identifiés</p>
                      </div>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">
                      {{ resultat.difficultes_rencontrées }}
                    </p>
                  </div>

                  <!-- Solutions -->
                  <div v-if="resultat.solutions_envisagees"
                    class="rounded-3 p-5 border border-purple-200 dark:border-purple-800">
                    <div class="flex items-center gap-3 mb-4">
                      <div
                        class="w-10 h-10 rounded-3 bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="currentColor"
                          viewBox="0 0 20 20">
                          <path fill-rule="evenodd"
                            d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"
                            clip-rule="evenodd" />
                        </svg>
                      </div>
                      <div>
                        <h5 class="font-semibold text-purple-900 dark:text-purple-300">Solutions envisagées</h5>
                        <p class="text-sm text-purple-600 dark:text-purple-400">Propositions du collaborateur</p>
                      </div>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">
                      {{ resultat.solutions_envisagees }}
                    </p>
                  </div>
                </div>
              </section>

              <!-- Section: Validation -->
              <section id="validation" class="scroll-mt-6">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                  <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                  </svg>
                  Historique de validation
                </h4>

                <!-- Timeline de validation -->
                <div class="space-y-4">
                  <!-- Étape 1: Validation N1 -->
                  <div class="relative">
                    <div class="flex gap-4">
                      <!-- Timeline dot -->
                      <div class="relative z-10">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center "
                          :class="resultat.validation_n1?.valide ? 'bg-green-500' : 'bg-gray-400'">
                          <svg v-if="resultat.validation_n1?.valide" class="w-5 h-5 text-white" fill="currentColor"
                            viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                              d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                              clip-rule="evenodd" />
                          </svg>
                          <span v-else class="text-white font-bold text-sm">1</span>
                        </div>
                        <!-- Timeline line -->
                        <div class="absolute top-10 left-1/2 transform -translate-x-1/2 w-0.5 h-8"
                          :class="resultat.validation_n1?.valide ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-700'">
                        </div>
                      </div>

                      <!-- Contenu -->
                      <div class="flex-1 pb-8">
                        <div class="bg-white dark:bg-gray-800 rounded-3 border"
                          :class="resultat.validation_n1?.valide ? 'border-green-200 dark:border-green-800' : 'border-gray-200 dark:border-gray-700'">
                          <div class="p-5">
                            <div class="flex items-center justify-between mb-3">
                              <div class="flex items-center gap-2">
                                <h5 class="font-semibold text-gray-900 dark:text-white">Validation Niveau 1</h5>
                                <span v-if="resultat.validation_n1?.valide"
                                  class="text-xs px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full font-medium">
                                  Validé
                                </span>
                                <span v-else
                                  class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-full font-medium">
                                  En attente
                                </span>
                              </div>
                              <span v-if="resultat.validation_n1?.valide_le"
                                class="text-xs text-gray-500 dark:text-gray-400">
                                {{ formatDate(resultat.validation_n1.valide_le) }}
                              </span>
                            </div>

                            <div v-if="resultat.validation_n1?.valide" class="space-y-4">
                              <!-- Validateur -->
                              <div v-if="resultat.validation_n1.validateur" class="flex items-center gap-3">
                                <div
                                  class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                  <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                      clip-rule="evenodd" />
                                  </svg>
                                </div>
                                <div>
                                  <p class="text-sm font-medium text-gray-900 dark:text-white">{{
                                    resultat.validation_n1.validateur.nom }}</p>
                                  <p class="text-xs text-gray-500 dark:text-gray-400">Responsable activité</p>
                                </div>
                              </div>

                              <!-- Commentaire N1 - TOUJOURS visible pour le N2 -->
                              <div v-if="resultat.validation_n1?.commentaire"
                                class="mt-3 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-3 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-start gap-2">
                                  <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 flex-shrink-0 mt-0.5"
                                    fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                      d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zM7 8H5v2h2V8zm2 0h2v2H9V8zm6 0h-2v2h2V8z"
                                      clip-rule="evenodd" />
                                  </svg>
                                  <div class="flex-1">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Commentaire du validateur
                                      N1</p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{
                                      resultat.validation_n1.commentaire }}</p>
                                  </div>
                                </div>
                              </div>
                            </div>

                            <div v-else class="text-center py-6">
                              <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                              </svg>
                              <p class="text-gray-500 dark:text-gray-400">En attente de validation</p>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Étape 2: Validation N2 -->
                  <div v-if="resultat.tache?.validation_n2_required" class="relative">
                    <div class="flex gap-4">
                      <!-- Timeline dot -->
                      <div class="relative z-10">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center "
                          :class="resultat.validation_n2?.valide ? 'bg-blue-500' : 'bg-gray-400'">
                          <svg v-if="resultat.validation_n2?.valide" class="w-5 h-5 text-white" fill="currentColor"
                            viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                              d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                              clip-rule="evenodd" />
                          </svg>
                          <span v-else class="text-white font-bold text-sm">2</span>
                        </div>
                      </div>

                      <!-- Contenu -->
                      <div class="flex-1">
                        <div class="bg-white dark:bg-gray-800 rounded-3 border"
                          :class="resultat.validation_n2?.valide ? 'border-blue-200 dark:border-blue-800' : 'border-gray-200 dark:border-gray-700'">
                          <div class="p-5">
                            <div class="flex items-center justify-between mb-3">
                              <div class="flex items-center gap-2">
                                <h5 class="font-semibold text-gray-900 dark:text-white">Validation Niveau 2</h5>
                                <span v-if="resultat.validation_n2?.valide"
                                  class="text-xs px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full font-medium">
                                  Validé
                                </span>
                                <span v-else
                                  class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-full font-medium">
                                  {{ resultat.validation_n1?.valide ? 'En attente' : 'Nécessite validation N1' }}
                                </span>
                              </div>
                              <span v-if="resultat.validation_n2?.valide_le"
                                class="text-xs text-gray-500 dark:text-gray-400">
                                {{ formatDate(resultat.validation_n2.valide_le) }}
                              </span>
                            </div>

                            <div v-if="resultat.validation_n2?.valide" class="space-y-4">
                              <!-- Validateur -->
                              <div v-if="resultat.validation_n2.validateur" class="flex items-center gap-3">
                                <div
                                  class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                  <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                      clip-rule="evenodd" />
                                  </svg>
                                </div>
                                <div>
                                  <p class="text-sm font-medium text-gray-900 dark:text-white">{{
                                    resultat.validation_n2.validateur.nom }}</p>
                                  <p class="text-xs text-gray-500 dark:text-gray-400">Responsable projet</p>
                                </div>
                              </div>

                              <!-- Commentaire N2 -->
                              <div v-if="resultat.validation_n2?.commentaire"
                                class="mt-3 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-3 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-start gap-2">
                                  <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 flex-shrink-0 mt-0.5"
                                    fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                      d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zM7 8H5v2h2V8zm2 0h2v2H9V8zm6 0h-2v2h2V8z"
                                      clip-rule="evenodd" />
                                  </svg>
                                  <div class="flex-1">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Commentaire du validateur
                                      N2</p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{
                                      resultat.validation_n2.commentaire }}</p>
                                  </div>
                                </div>
                              </div>
                            </div>

                            <div v-else-if="!resultat.validation_n1?.valide"
                              class="text-center py-6 bg-gray-50 dark:bg-gray-900/30 rounded-3">
                              <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                              </svg>
                              <p class="text-gray-500 dark:text-gray-400">La validation N1 est requise avant la
                                validation N2</p>
                            </div>

                            <div v-else class="text-center py-6">
                              <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                              </svg>
                              <p class="text-gray-500 dark:text-gray-400">En attente de validation</p>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

            </div>
          </div>
        </div>

        <!-- Footer amélioré -->
        <div
          class="sticky bottom-0 p-6 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 flex justify-between items-center">
          <div class="flex items-center gap-3">
            <span class="text-sm text-gray-500 dark:text-gray-400">
              ID: <span class="font-mono text-gray-900 dark:text-white">{{ resultat.id }}</span>
            </span>
            <span class="text-gray-400">•</span>
            <span class="text-sm text-gray-500 dark:text-gray-400">
              Dernière modification: {{ formatDateTime(resultat.updated_at) }}
            </span>
          </div>
          <div class="flex items-center gap-3">
            <button @click="$emit('close')"
              class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-3 transition-all duration-200 ">
              Fermer
            </button>
            <button v-if="canDownloadAll" @click="downloadAllDocuments"
              class="px-6 py-2.5 text-sm font-medium text-white rounded-3 transition-all duration-200 shadow-purple-500/30 ">
              <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Télécharger tout
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de prévisualisation -->
    <div v-if="showPreviewModal"
      class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 ">
      <div
        class="relative bg-white dark:bg-gray-900 rounded-3 max-w-5xl w-full max-h-[90vh] overflow-hidden ">
        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div :class="getFileIconClass(previewDocument)"
              class="w-10 h-10 rounded-3 flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <div>
              <h3 class="font-bold text-gray-900 dark:text-white">{{ previewDocument?.nom }}</h3>
              <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                <span>{{ formatFileSize(previewDocument?.taille) }}</span>
                <span>•</span>
                <span class="uppercase font-medium">{{ previewDocument?.extension }}</span>
              </div>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <button @click="downloadDocument(previewDocument)"
              class="p-2 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-3 transition-colors"
              title="Télécharger">
              <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
            </button>
            <button @click="closePreviewModal"
              class="p-2 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-3 transition-colors">
              <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
        <div class="p-4 max-h-[calc(90vh-80px)] overflow-auto">
          <div v-if="previewDocument">
            <img v-if="isImage(previewDocument)" :src="previewDocument.url" :alt="previewDocument.nom"
              class="max-w-full max-h-[70vh] mx-auto rounded-3 " />
            <iframe v-else-if="isPDF(previewDocument)" :src="previewDocument.url"
              class="w-full h-[70vh] rounded-3 border-0" frameborder="0"></iframe>
            <div v-else class="text-center py-16">
              <div
                class="w-20 h-20 mx-auto mb-6 rounded-3 flex items-center justify-center">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <p class="text-gray-500 dark:text-gray-400 mb-6">Ce fichier ne peut pas être prévisualisé</p>
              <button @click="downloadDocument(previewDocument)"
                class="px-6 py-3 text-white rounded-3 transition-all duration-200">
                Télécharger le fichier
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, h } from 'vue'

const props = defineProps({
  resultat: {
    type: Object,
    required: true
  }
})

defineEmits(['close'])

// Navigation
const sections = computed(() => [
  { id: 'informations', label: 'Informations', icon: InfoIcon, badge: resultat.value.taux_realisation + '%' },
  { id: 'resultats', label: 'Résultats', icon: ResultIcon },
  { id: 'documents', label: 'Documents', icon: DocumentIcon, badge: resultat.value.documents?.length || 0, badgeClass: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' },
  ...(resultat.value.difficultes_rencontrees || resultat.value.solutions_envisagees
    ? [{ id: 'difficultes', label: 'Analyse', icon: AlertIcon }]
    : []),
  { id: 'validation', label: 'Validation', icon: CheckIcon }
])

const activeSection = ref('informations')
const contentRef = ref(null)

// Modal de prévisualisation
const showPreviewModal = ref(false)
// const previewDocument = ref(null)

// Computed properties
const resultat = computed(() => props.resultat)

const validationStatusLabel = computed(() => {
  console.log('Resultat =', resultat.value);

  if (!resultat.value) return 'Non soumis'
  if (resultat.value.validation_n2.valide) return '✅ Entièrement validé'
  if (resultat.value.validation_n1.valide) return '⏳ En attente validation N2'
  return '⏳ En attente validation N1'
})

const validationStatusClass = computed(() => {
  const status = validationStatusLabel.value
  if (status.includes('Entièrement')) return 'bg-success-500 text-white'
  if (status.includes('N1')) return 'bg-warning-500 text-white'
  if (status.includes('N2')) return 'bg-brand-500 text-white'
  return 'bg-gray-500 text-white'
})

const progressColor = computed(() => {
  const taux = resultat.value?.taux_realisation || 0
  if (taux >= 90) return 'text-green-500'
  if (taux >= 70) return 'text-blue-500'
  if (taux >= 50) return 'text-amber-500'
  return 'text-red-500'
})

const isUrgent = computed(() => {
  if (!resultat.value.soumis_le) return false
  const soumisDate = new Date(resultat.value.soumis_le)
  const now = new Date()
  const diffHours = (now - soumisDate) / (1000 * 60 * 60)
  return diffHours > 48
})

const canDownloadAll = computed(() => {
  return resultat.value.documents && resultat.value.documents.length > 0
})

// Methods
function scrollToSection(sectionId) {
  activeSection.value = sectionId
  const element = document.getElementById(sectionId)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth' })
  }
}

function handleScroll() {
  if (!contentRef.value) return

  const scrollPosition = contentRef.value.scrollTop + 100
  const sectionElements = sections.value.map(s => document.getElementById(s.id))

  for (let i = sectionElements.length - 1; i >= 0; i--) {
    const element = sectionElements[i]
    if (element && scrollPosition >= element.offsetTop) {
      activeSection.value = sections.value[i].id
      break
    }
  }
}

function getProgressIndicatorClass(taux) {
  if (taux >= 90) return 'bg-green-500'
  if (taux >= 70) return 'bg-blue-500'
  if (taux >= 50) return 'bg-amber-500'
  return 'bg-red-500'
}

function getProgressLabel(taux) {
  if (taux >= 90) return 'Excellent'
  if (taux >= 70) return 'Bon'
  if (taux >= 50) return 'Moyen'
  return 'Insuffisant'
}

function getFileIconClass(doc) {
  const ext = doc.extension?.toLowerCase()

  if (isImage(doc)) return 'bg-success-500'
  if (isPDF(doc)) return 'bg-error-500'
  if (['doc', 'docx'].includes(ext)) return 'bg-brand-500'
  if (['xls', 'xlsx'].includes(ext)) return 'bg-success-500'
  if (['zip', 'rar'].includes(ext)) return 'bg-warning-500'
  if (['mp4', 'avi', 'mov'].includes(ext)) return 'bg-purple-500'

  return 'bg-gray-500'
}

function isImage(doc) {
  const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg']
  return imageExtensions.includes(doc.extension?.toLowerCase())
}

function isPDF(doc) {
  return doc.extension?.toLowerCase() === 'pdf'
}

function canPreview(doc) {
  return isImage(doc) || isPDF(doc)
}

function previewDocument(doc) {
  previewDocument.value = doc
  showPreviewModal.value = true
}

function closePreviewModal() {
  showPreviewModal.value = false
  previewDocument.value = null
}

function downloadDocument(doc) {
  window.open(doc.url, '_blank')
}

async function downloadAllDocuments() {
  // Implémentez la logique pour télécharger tous les documents
  console.log('Téléchargement de tous les documents')
}

function getN1ValidationClass() {
  if (resultat.value?.valide_par_n1) {
    return 'border-green-200 dark:border-green-800'
  }
  return 'border-gray-200 dark:border-gray-700'
}

function getN2ValidationClass() {
  if (resultat.value?.valide_par_n2) {
    return 'border-blue-200 dark:border-blue-800'
  }
  return 'border-gray-200 dark:border-gray-700'
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

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

function formatFileSize(bytes) {
  if (!bytes) return '0 B'
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}

function getImageUrl(path) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${import.meta.env.VITE_APP_URL}/storage/${path}`
}

function stringToColor(str) {
  if (!str) return '#999'
  let hash = 0
  for (let i = 0; i < str.length; i++) {
    hash = str.charCodeAt(i) + ((hash << 5) - hash)
  }
  return `hsl(${hash % 360}, 65%, 50%)`
}

function getInitials(name) {
  if (!name) return '?'
  const parts = name.trim().split(' ')
  if (parts.length === 1) return parts[0].charAt(0).toUpperCase()
  return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase()
}

// Lifecycle
onMounted(() => {
  if (contentRef.value) {
    contentRef.value.addEventListener('scroll', handleScroll)
  }
})

onUnmounted(() => {
  if (contentRef.value) {
    contentRef.value.removeEventListener('scroll', handleScroll)
  }
})

// Composants d'icônes — fonctions de rendu (h()) car le build runtime de
// Vue (utilisé par Vite) ne compile pas les chaînes template: à la volée.
const strokeIcon = (d) => ({
  render: () => h(
    'svg',
    { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' },
    [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': 2, d })]
  ),
})

const InfoIcon = strokeIcon('M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z')
const ResultIcon = strokeIcon('M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z')
const DocumentIcon = strokeIcon('M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13')
const CheckIcon = strokeIcon('M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4')

const AlertIcon = {
  render: () => h(
    'svg',
    { fill: 'currentColor', viewBox: '0 0 20 20' },
    [h('path', {
      'fill-rule': 'evenodd',
      'clip-rule': 'evenodd',
      d: 'M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z',
    })]
  ),
}
</script>

<style scoped>
.scroll-mt-6 {
  scroll-margin-top: 1.5rem;
}

/* Scrollbar personnalisée */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

.dark ::-webkit-scrollbar-track {
  background: #374151;
}

.dark ::-webkit-scrollbar-thumb {
  background: #6b7280;
}

.dark ::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}
</style>