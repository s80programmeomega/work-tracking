<!-- resources\js\components\modals\ResultatDetailModal.vue -->
 <template>
  <teleport to="body">
    <transition name="modal-fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/80 backdrop-blur-xl"
        @click.self="close"
      >
        <div
          class="relative w-full max-w-6xl bg-gradient-to-br from-white via-white to-gray-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 rounded-3xl shadow-2xl shadow-black/30 max-h-[95vh] overflow-hidden border border-white/20 dark:border-gray-700/50"
          @click.stop
        >
          <!-- Loading State -->
          <div v-if="loading" class="flex items-center justify-center min-h-[400px]">
            <div class="text-center space-y-4">
              <div class="relative">
                <div class="w-20 h-20 border-4 border-gray-200 dark:border-gray-700 rounded-full"></div>
                <div class="absolute top-0 left-0 w-20 h-20 border-4 border-t-brand-600 dark:border-t-brand-400 border-transparent rounded-full animate-spin"></div>
              </div>
              <p class="text-gray-600 dark:text-gray-400 text-lg font-medium animate-pulse">Chargement des détails...</p>
            </div>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="p-8">
            <div class="text-center py-16 space-y-6">
              <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-red-500 to-pink-600 rounded-2xl shadow-lg">
                <i class="fas fa-exclamation-triangle text-white text-2xl"></i>
              </div>
              <h3 class="text-2xl font-bold bg-gradient-to-r from-red-600 to-pink-600 bg-clip-text text-transparent">Erreur de chargement</h3>
              <p class="text-gray-600 dark:text-gray-400 max-w-md mx-auto text-lg">{{ error }}</p>
              <button
                @click="close"
                class="px-8 py-3.5 bg-gradient-to-r from-gray-800 to-gray-900 dark:from-gray-700 dark:to-gray-800 text-white rounded-xl hover:shadow-xl transition-all duration-300 hover:scale-105 font-semibold"
              >
                <i class="fas fa-times mr-2"></i>
                Fermer
              </button>
            </div>
          </div>

          <!-- Content -->
          <div v-else-if="resultat" class="flex flex-col h-full">
            <!-- Header Premium -->
            <div class="relative overflow-hidden bg-gradient-to-r from-gray-900 via-brand-900 to-purple-900">
              <!-- Animated Background -->
              <div class="absolute inset-0 opacity-20">
                <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.3) 1px, transparent 0); background-size: 40px 40px; animation: float 20s linear infinite;"></div>
                <div class="absolute top-1/4 -left-20 w-80 h-80 bg-brand-500/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-1/4 -right-20 w-80 h-80 bg-purple-500/10 rounded-full blur-3xl"></div>
              </div>
              
              <div class="relative z-10 px-10 py-8">
                <!-- Top Bar -->
                <div class="flex items-center justify-between mb-8">
                  <div class="flex items-center gap-3">
                    <div class="px-4 py-1.5 bg-white/10 backdrop-blur-sm rounded-full border border-white/20">
                      <span class="text-sm font-bold text-white/90 uppercase tracking-wider">
                        {{ resultat.tache?.code || 'TACHE' }}
                      </span>
                    </div>
                    <div :class="['px-4 py-1.5 rounded-full text-sm font-bold uppercase tracking-wider shadow-lg backdrop-blur-sm border', getStatusBadgeClass()]">
                      {{ getStatusLabel() }}
                    </div>
                  </div>

                  <div class="flex items-center gap-3">
                    <button
                      v-if="canEdit"
                      @click="handleEdit"
                      class="group relative px-4 py-2.5 bg-white/10 backdrop-blur-sm rounded-xl border border-white/20 text-white hover:bg-white/20 transition-all"
                    >
                      <i class="fas fa-edit mr-2"></i>
                      Modifier
                      <div class="absolute -top-1 -right-1 w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                    </button>
                    
                    <button
                      @click="close"
                      class="group p-3 bg-white/10 backdrop-blur-sm rounded-xl border border-white/20 text-white hover:bg-white/20 transition-all hover:rotate-90 hover:scale-110"
                    >
                      <i class="fas fa-times text-lg"></i>
                      <div class="absolute inset-0 rounded-xl border border-white/30 group-hover:border-white/50 transition-colors"></div>
                    </button>
                  </div>
                </div>

                <!-- Main Header Content -->
                <div class="flex items-start gap-8">
                  <!-- Icon -->
                  <div class="flex-shrink-0">
                    <div class="relative">
                      <div class="w-24 h-24 rounded-3xl bg-gradient-to-br from-white/20 to-transparent backdrop-blur-sm border-2 border-white/30 shadow-2xl flex items-center justify-center">
                        <i :class="['fas', getStatusIcon(), 'text-white text-4xl']"></i>
                      </div>
                      <div class="absolute -inset-4 bg-gradient-to-r from-brand-500/30 to-purple-500/30 blur-xl rounded-full -z-10"></div>
                    </div>
                  </div>

                  <!-- Content -->
                  <div class="flex-1 min-w-0 space-y-4">
                    <h3 class="text-3xl font-black text-white drop-shadow-lg leading-tight">
                      {{ resultat.tache?.titre }}
                    </h3>
                    
                    <div class="grid grid-cols-2 gap-6">
                      <!-- User Info -->
                      <div class="flex items-center gap-3">
                        <div class="relative">
                          <img 
                            v-if="resultat.user?.avatar" 
                            :src="getImageUrl(resultat.user.avatar)" 
                            :alt="resultat.user.nom" 
                            class="w-12 h-12 rounded-xl border-2 border-white/30"
                          />
                          <div v-else class="w-12 h-12 rounded-xl border-2 border-white/30 bg-gradient-to-br from-brand-600 to-purple-600 flex items-center justify-center">
                            <span class="text-white font-bold text-sm">{{ getInitials(resultat.user?.nom) }}</span>
                          </div>
                          <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white/30"></div>
                        </div>
                        <div>
                          <p class="text-sm font-semibold text-white">{{ resultat.user?.nom }}</p>
                          <p class="text-xs text-white/70">{{ resultat.user?.email }}</p>
                        </div>
                      </div>

                      <!-- Date Info -->
                      <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-white/10 backdrop-blur-sm border border-white/20 flex items-center justify-center">
                          <i class="fas fa-clock text-white/90"></i>
                        </div>
                        <div>
                          <p class="text-sm font-semibold text-white/90">Soumis le</p>
                          <p class="text-xs text-white/70">{{ formatDateTime(resultat.soumis_le) }}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Body with Navigation -->
            <div class="flex-1 overflow-hidden">
              <!-- Navigation Tabs -->
              <div class="border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
                <div class="flex items-center gap-1 px-8 py-2">
                  <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="[
                      'flex items-center gap-2 px-6 py-3 text-sm font-semibold rounded-t-xl transition-all',
                      activeTab === tab.id
                        ? 'bg-white dark:bg-gray-900 text-brand-600 dark:text-brand-400 border-t-2 border-brand-600 dark:border-brand-400 shadow-sm'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                    ]"
                  >
                    <i :class="tab.icon"></i>
                    {{ tab.label }}
                    <span v-if="tab.badge" class="ml-2 px-2 py-0.5 text-xs rounded-full" :class="tab.badgeClass">
                      {{ tab.badge }}
                    </span>
                  </button>
                </div>
              </div>

              <!-- Tab Content -->
              <div class="overflow-y-auto p-8 max-h-[calc(95vh-400px)]">
                <!-- Résultats Tab -->
                <div v-show="activeTab === 'results'" class="space-y-8">
                  <!-- Progress Section -->
                  <div class="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 rounded-3xl p-8 border-2 border-gray-100 dark:border-gray-700 shadow-xl">
                    <div class="flex items-center justify-between mb-8">
                      <div>
                        <h4 class="text-2xl font-black text-gray-900 dark:text-white mb-2">Taux de réalisation</h4>
                        <p class="text-gray-600 dark:text-gray-400">Performance globale du résultat</p>
                      </div>
                      <div class="text-right">
                        <span class="text-5xl font-black bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                          {{ resultat.taux_realisation }}%
                        </span>
                        <div class="flex items-center gap-2 mt-2">
                          <div class="w-3 h-3 rounded-full" :class="getProgressColorClass(resultat.taux_realisation)"></div>
                          <span class="text-sm text-gray-600 dark:text-gray-400">{{ getProgressLabel(resultat.taux_realisation) }}</span>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Animated Progress Bar -->
                    <div class="relative h-6 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                      <div
                        class="absolute inset-0 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500 rounded-full transition-all duration-1000 ease-out shadow-lg"
                        :style="{ width: `${resultat.taux_realisation}%` }"
                      >
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent animate-shimmer"></div>
                      </div>
                      <div class="absolute inset-0 flex items-center px-4">
                        <div class="w-full flex justify-between text-xs font-bold text-white drop-shadow">
                          <span>0%</span>
                          <span>25%</span>
                          <span>50%</span>
                          <span>75%</span>
                          <span>100%</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Results Grid -->
                  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Expected Results -->
                    <div class="relative group">
                      <div class="absolute -inset-4 bg-gradient-to-br from-blue-500/10 to-transparent blur-xl rounded-3xl -z-10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                      <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 border-2 border-blue-200 dark:border-blue-800 shadow-xl hover:shadow-2xl transition-all duration-300">
                        <div class="flex items-start gap-4 mb-6">
                          <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg">
                            <i class="fas fa-bullseye text-white text-xl"></i>
                          </div>
                          <div>
                            <h4 class="text-xl font-black text-gray-900 dark:text-white mb-1">Résultats attendus</h4>
                            <p class="text-sm text-blue-600 dark:text-blue-400">Objectifs définis</p>
                          </div>
                        </div>
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-2xl">
                          <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">{{ resultat.resultats_attendus || 'Non spécifié' }}</p>
                        </div>
                      </div>
                    </div>

                    <!-- Achieved Results -->
                    <div class="relative group">
                      <div class="absolute -inset-4 bg-gradient-to-br from-green-500/10 to-transparent blur-xl rounded-3xl -z-10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                      <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 border-2 border-green-200 dark:border-green-800 shadow-xl hover:shadow-2xl transition-all duration-300">
                        <div class="flex items-start gap-4 mb-6">
                          <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-lg">
                            <i class="fas fa-check-circle text-white text-xl"></i>
                          </div>
                          <div>
                            <h4 class="text-xl font-black text-gray-900 dark:text-white mb-1">Résultats obtenus</h4>
                            <p class="text-sm text-green-600 dark:text-green-400">Livrables réalisés</p>
                          </div>
                        </div>
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-2xl">
                          <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">{{ resultat.resultats_obtenus || 'Non spécifié' }}</p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Analysis Section -->
                  <div v-if="resultat.difficultes_rencontrees || resultat.solutions_envisagees || resultat.observations" class="space-y-8">
                    <h4 class="text-2xl font-black text-gray-900 dark:text-white">Analyse du collaborateur</h4>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                      <!-- Difficulties -->
                      <div v-if="resultat.difficultes_rencontrees" class="relative group">
                        <div class="absolute -inset-4 bg-gradient-to-br from-orange-500/10 to-transparent blur-xl rounded-3xl -z-10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 rounded-3xl p-8 border-2 border-orange-200 dark:border-orange-800 shadow-xl hover:shadow-2xl transition-all duration-300">
                          <div class="flex items-start gap-4 mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center shadow-lg">
                              <i class="fas fa-exclamation-triangle text-white"></i>
                            </div>
                            <div>
                              <h4 class="text-lg font-black text-orange-700 dark:text-orange-400 mb-1">Difficultés</h4>
                              <p class="text-sm text-orange-600 dark:text-orange-500">Rencontrées</p>
                            </div>
                          </div>
                          <p class="text-gray-700 dark:text-gray-300 leading-relaxed">{{ resultat.difficultes_rencontrees }}</p>
                        </div>
                      </div>

                      <!-- Solutions -->
                      <div v-if="resultat.solutions_envisagees" class="relative group">
                        <div class="absolute -inset-4 bg-gradient-to-br from-emerald-500/10 to-transparent blur-xl rounded-3xl -z-10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 rounded-3xl p-8 border-2 border-emerald-200 dark:border-emerald-800 shadow-xl hover:shadow-2xl transition-all duration-300">
                          <div class="flex items-start gap-4 mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-green-600 flex items-center justify-center shadow-lg">
                              <i class="fas fa-lightbulb text-white"></i>
                            </div>
                            <div>
                              <h4 class="text-lg font-black text-emerald-700 dark:text-emerald-400 mb-1">Solutions</h4>
                              <p class="text-sm text-emerald-600 dark:text-emerald-500">Envisagées</p>
                            </div>
                          </div>
                          <p class="text-gray-700 dark:text-gray-300 leading-relaxed">{{ resultat.solutions_envisagees }}</p>
                        </div>
                      </div>

                      <!-- Observations -->
                      <div v-if="resultat.observations" class="relative group">
                        <div class="absolute -inset-4 bg-gradient-to-br from-purple-500/10 to-transparent blur-xl rounded-3xl -z-10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-3xl p-8 border-2 border-purple-200 dark:border-purple-800 shadow-xl hover:shadow-2xl transition-all duration-300">
                          <div class="flex items-start gap-4 mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center shadow-lg">
                              <i class="fas fa-comment-dots text-white"></i>
                            </div>
                            <div>
                              <h4 class="text-lg font-black text-purple-700 dark:text-purple-400 mb-1">Observations</h4>
                              <p class="text-sm text-purple-600 dark:text-purple-500">Commentaires</p>
                            </div>
                          </div>
                          <p class="text-gray-700 dark:text-gray-300 leading-relaxed">{{ resultat.observations }}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Documents Tab -->
                <div v-show="activeTab === 'documents'" class="space-y-8">
                  <div class="flex items-center justify-between mb-8">
                    <div>
                      <h4 class="text-2xl font-black text-gray-900 dark:text-white mb-2">Documents joints</h4>
                      <p class="text-gray-600 dark:text-gray-400">{{ resultat.documents?.length || 0 }} fichier(s) attaché(s)</p>
                    </div>
                    <button
                      v-if="resultat.documents && resultat.documents.length > 0"
                      @click="downloadAllDocuments"
                      class="group flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-brand-600 to-purple-600 text-white rounded-xl hover:shadow-xl transition-all duration-300 hover:scale-105 font-semibold"
                    >
                      <i class="fas fa-download group-hover:animate-bounce"></i>
                      Télécharger tout
                    </button>
                  </div>

                  <div v-if="resultat.documents && resultat.documents.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div
                      v-for="doc in resultat.documents"
                      :key="doc.id"
                      class="group relative bg-white dark:bg-gray-800 rounded-2xl p-6 border-2 border-gray-200 dark:border-gray-700 hover:border-brand-500 dark:hover:border-brand-500 shadow-lg hover:shadow-2xl transition-all duration-300"
                    >
                      <div class="flex items-start gap-4">
                        <!-- File Icon -->
                        <div :class="getFileIconClass(doc)" class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-lg">
                          <i class="fas text-white text-2xl" :class="getFileIcon(doc)"></i>
                        </div>

                        <!-- File Info -->
                        <div class="flex-1 min-w-0">
                          <h5 class="text-lg font-bold text-gray-900 dark:text-white mb-1 truncate">{{ doc.nom }}</h5>
                          <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400 mb-3">
                            <span>{{ formatFileSize(doc.taille || doc.taille_fichier) }}</span>
                            <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded-lg font-medium uppercase">{{ doc.extension }}</span>
                          </div>
                          
                          <!-- Actions -->
                          <div class="flex items-center gap-2">
                            <a
                              :href="doc.url"
                              target="_blank"
                              class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                            >
                              <i class="fas fa-eye"></i>
                              Voir
                            </a>
                            <a
                              :href="doc.url"
                              download
                              class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-brand-500 to-purple-500 text-white rounded-lg hover:opacity-90 transition-all"
                            >
                              <i class="fas fa-download"></i>
                              Télécharger
                            </a>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div v-else class="text-center py-16">
                    <div class="w-24 h-24 mx-auto mb-6 rounded-3xl bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 flex items-center justify-center">
                      <i class="fas fa-file text-gray-400 text-3xl"></i>
                    </div>
                    <h5 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Aucun document</h5>
                    <p class="text-gray-600 dark:text-gray-400">Aucun fichier n'a été joint à ce résultat.</p>
                  </div>
                </div>

                <!-- Validation Tab -->
                <div v-show="activeTab === 'validation'" class="space-y-8">
                  <div class="flex items-center justify-between mb-8">
                    <div>
                      <h4 class="text-2xl font-black text-gray-900 dark:text-white mb-2">Historique de validation</h4>
                      <p class="text-gray-600 dark:text-gray-400">Suivi des approbations et commentaires</p>
                    </div>
                  </div>

                  <!-- Validation Timeline -->
                  <div class="relative">
                    <!-- Timeline Line -->
                    <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gradient-to-b from-gray-200 via-gray-300 to-gray-200 dark:from-gray-700 dark:via-gray-600 dark:to-gray-700"></div>

                    <!-- N1 Validation -->
                    <div class="relative ml-12 mb-10">
                      <div class="absolute -left-12 top-0">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-xl" :class="resultat.valide_par_n1 ? 'bg-gradient-to-br from-green-500 to-emerald-600' : 'bg-gradient-to-br from-gray-400 to-gray-600'">
                          <i :class="['fas', resultat.valide_par_n1 ? 'fa-check' : 'fa-clock', 'text-white text-lg']"></i>
                        </div>
                      </div>
                      
                      <div :class="['rounded-3xl p-8 shadow-xl', resultat.valide_par_n1 ? 'bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-green-200 dark:border-emerald-800' : 'bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/50 dark:to-gray-900/50 border-2 border-gray-200 dark:border-gray-700']">
                        <div class="flex items-start justify-between mb-6">
                          <div>
                            <h5 class="text-xl font-black text-gray-900 dark:text-white mb-2">Validation Niveau 1</h5>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Responsable d'activité</p>
                          </div>
                          <span v-if="resultat.valide_par_n1" class="px-4 py-1.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-sm font-bold">
                            <i class="fas fa-check-circle mr-2"></i>
                            Validé
                          </span>
                          <span v-else class="px-4 py-1.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-full text-sm font-bold">
                            <i class="fas fa-clock mr-2"></i>
                            En attente
                          </span>
                        </div>

                        <div v-if="resultat.valide_par_n1" class="space-y-6">
                          <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                              <i class="fas fa-user text-gray-600 dark:text-gray-400 text-lg"></i>
                            </div>
                            <div>
                              <p class="font-bold text-gray-900 dark:text-white">{{ resultat.validateur_n1?.nom || 'Non spécifié' }}</p>
                              <p class="text-sm text-gray-600 dark:text-gray-400">Validateur N1</p>
                            </div>
                          </div>

                          <div v-if="resultat.commentaire_n1" class="bg-white dark:bg-gray-800 rounded-2xl p-6 border-2 border-green-200 dark:border-green-800">
                            <div class="flex items-start gap-3">
                              <i class="fas fa-comment text-green-500 text-lg mt-1"></i>
                              <div>
                                <h6 class="text-sm font-bold text-green-700 dark:text-green-400 mb-2">Commentaire</h6>
                                <p class="text-gray-700 dark:text-gray-300 italic">"{{ resultat.commentaire_n1 }}"</p>
                              </div>
                            </div>
                          </div>

                          <p class="text-sm text-gray-600 dark:text-gray-400">
                            <i class="fas fa-clock mr-2"></i>
                            Validé le {{ formatDateTime(resultat.valide_le_n1) }}
                          </p>
                        </div>
                      </div>
                    </div>

                    <!-- N2 Validation -->
                    <div v-if="resultat.tache?.validation_n2_required" class="relative ml-12 mb-10">
                      <div class="absolute -left-12 top-0">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-xl" :class="resultat.valide_par_n2 ? 'bg-gradient-to-br from-blue-500 to-cyan-600' : 'bg-gradient-to-br from-gray-400 to-gray-600'">
                          <i :class="['fas', resultat.valide_par_n2 ? 'fa-trophy' : 'fa-clock', 'text-white text-lg']"></i>
                        </div>
                      </div>
                      
                      <div :class="['rounded-3xl p-8 shadow-xl', resultat.valide_par_n2 ? 'bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 border-2 border-blue-200 dark:border-cyan-800' : 'bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/50 dark:to-gray-900/50 border-2 border-gray-200 dark:border-gray-700']">
                        <div class="flex items-start justify-between mb-6">
                          <div>
                            <h5 class="text-xl font-black text-gray-900 dark:text-white mb-2">Validation Niveau 2</h5>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Responsable de projet</p>
                          </div>
                          <span v-if="resultat.valide_par_n2" class="px-4 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-full text-sm font-bold">
                            <i class="fas fa-trophy mr-2"></i>
                            Validé final
                          </span>
                          <span v-else class="px-4 py-1.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-full text-sm font-bold">
                            <i class="fas fa-clock mr-2"></i>
                            {{ resultat.valide_par_n1 ? 'En attente' : 'Nécessite N1' }}
                          </span>
                        </div>

                        <div v-if="resultat.valide_par_n2" class="space-y-6">
                          <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                              <i class="fas fa-user text-gray-600 dark:text-gray-400 text-lg"></i>
                            </div>
                            <div>
                              <p class="font-bold text-gray-900 dark:text-white">{{ resultat.validateur_n2?.nom || 'Non spécifié' }}</p>
                              <p class="text-sm text-gray-600 dark:text-gray-400">Validateur N2</p>
                            </div>
                          </div>

                          <div v-if="resultat.commentaire_n2" class="bg-white dark:bg-gray-800 rounded-2xl p-6 border-2 border-blue-200 dark:border-blue-800">
                            <div class="flex items-start gap-3">
                              <i class="fas fa-comment text-blue-500 text-lg mt-1"></i>
                              <div>
                                <h6 class="text-sm font-bold text-blue-700 dark:text-blue-400 mb-2">Commentaire</h6>
                                <p class="text-gray-700 dark:text-gray-300 italic">"{{ resultat.commentaire_n2 }}"</p>
                              </div>
                            </div>
                          </div>

                          <p class="text-sm text-gray-600 dark:text-gray-400">
                            <i class="fas fa-clock mr-2"></i>
                            Validé le {{ formatDateTime(resultat.valide_le_n2) }}
                          </p>
                        </div>
                      </div>
                    </div>

                    <!-- Rejet -->
                    <div v-if="resultat.rejete_le" class="relative ml-12">
                      <div class="absolute -left-12 top-0">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-xl bg-gradient-to-br from-red-500 to-pink-600">
                          <i class="fas fa-times text-white text-lg"></i>
                        </div>
                      </div>
                      
                      <div class="rounded-3xl p-8 shadow-xl bg-gradient-to-br from-red-50 to-pink-50 dark:from-red-900/20 dark:to-pink-900/20 border-2 border-red-200 dark:border-pink-800">
                        <div class="flex items-start justify-between mb-6">
                          <div>
                            <h5 class="text-xl font-black text-gray-900 dark:text-white mb-2">Rejet</h5>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Niveau {{ resultat.niveau_rejet?.toUpperCase() }}</p>
                          </div>
                          <span class="px-4 py-1.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full text-sm font-bold">
                            <i class="fas fa-times-circle mr-2"></i>
                            Rejeté
                          </span>
                        </div>

                        <div class="space-y-6">
                          <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                              <i class="fas fa-user text-gray-600 dark:text-gray-400 text-lg"></i>
                            </div>
                            <div>
                              <p class="font-bold text-gray-900 dark:text-white">Système</p>
                              <p class="text-sm text-gray-600 dark:text-gray-400">Rejet automatique</p>
                            </div>
                          </div>

                          <div v-if="resultat.motif_rejet" class="bg-white dark:bg-gray-800 rounded-2xl p-6 border-2 border-red-200 dark:border-red-800">
                            <div class="flex items-start gap-3">
                              <i class="fas fa-exclamation-circle text-red-500 text-lg mt-1"></i>
                              <div>
                                <h6 class="text-sm font-bold text-red-700 dark:text-red-400 mb-2">Motif du rejet</h6>
                                <p class="text-gray-700 dark:text-gray-300 italic">"{{ resultat.motif_rejet }}"</p>
                              </div>
                            </div>
                          </div>

                          <p class="text-sm text-gray-600 dark:text-gray-400">
                            <i class="fas fa-clock mr-2"></i>
                            Rejeté le {{ formatDateTime(resultat.rejete_le) }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="border-t-2 border-gray-100 dark:border-gray-800 bg-gradient-to-r from-white to-gray-50 dark:from-gray-900 dark:to-gray-800 p-6">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-6 text-sm text-gray-600 dark:text-gray-400">
                  <span class="flex items-center gap-2">
                    <i class="fas fa-hashtag"></i>
                    ID: <span class="font-mono text-gray-900 dark:text-white">{{ resultat.id }}</span>
                  </span>
                  <span class="flex items-center gap-2">
                    <i class="fas fa-sync-alt"></i>
                    Dernière modification: {{ formatDateTime(resultat.updated_at) }}
                  </span>
                </div>
                
                <button
                  @click="close"
                  class="px-8 py-3 bg-gradient-to-r from-gray-800 to-gray-900 dark:from-gray-700 dark:to-gray-800 text-white rounded-xl hover:shadow-xl transition-all duration-300 hover:scale-105 font-semibold"
                >
                  <i class="fas fa-times mr-2"></i>
                  Fermer le détail
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/api/axios';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  resultatId: {
    type: [Number, String],
    default: null
  }
});

const emit = defineEmits(['close']);
const router = useRouter();

const loading = ref(false);
const error = ref(null);
const resultat = ref(null);
const activeTab = ref('results');

const tabs = computed(() => [
  {
    id: 'results',
    label: 'Résultats',
    icon: 'fas fa-chart-line',
    badge: resultat.value?.taux_realisation + '%',
    badgeClass: 'bg-brand-100 text-brand-700 dark:bg-brand-900/30 dark:text-brand-400'
  },
  {
    id: 'documents',
    label: 'Documents',
    icon: 'fas fa-file-alt',
    badge: resultat.value?.documents?.length || 0,
    badgeClass: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'
  },
  {
    id: 'validation',
    label: 'Validation',
    icon: 'fas fa-check-circle',
    badge: getValidationBadge(),
    badgeClass: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
  }
]);

const canEdit = computed(() => {
  return resultat.value && 
         !resultat.value.is_fully_validated && 
         !resultat.value.rejete_le;
});

const fetchResultat = async () => {
  if (!props.resultatId) return;
  
  loading.value = true;
  error.value = null;

  try {
    const response = await api.get(`/tache-resultats/${props.resultatId}`);
    resultat.value = response.data.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors du chargement des détails';
    console.error('Error fetching resultat:', err);
  } finally {
    loading.value = false;
  }
};

const close = () => {
  emit('close');
};

const handleEdit = () => {
  if (resultat.value && canEdit.value) {
    router.push(`/taches/${resultat.value.tache_id}/resultats/${resultat.value.id}/edit`);
    close();
  }
};

const getStatusIcon = () => {
  if (!resultat.value) return 'fa-file';
  
  if (resultat.value.rejete_le) return 'fa-times-circle';
  if (resultat.value.valide_par_n2) return 'fa-trophy';
  if (resultat.value.valide_par_n1) return 'fa-check-circle';
  if (resultat.value.soumis_le) return 'fa-clock';
  return 'fa-file';
};

const getStatusLabel = () => {
  if (!resultat.value) return '';
  
  if (resultat.value.rejete_le) return 'Rejeté';
  if (resultat.value.valide_par_n2) return 'Validé final';
  if (resultat.value.valide_par_n1) return 'Validé N1';
  if (resultat.value.soumis_le) return 'En attente';
  return 'Brouillon';
};

const getStatusBadgeClass = () => {
  if (!resultat.value) return '';
  
  if (resultat.value.rejete_le) return 'bg-gradient-to-r from-red-500 to-pink-600 text-white border-red-300/50 dark:border-red-700/50';
  if (resultat.value.valide_par_n2) return 'bg-gradient-to-r from-emerald-500 to-cyan-600 text-white border-emerald-300/50 dark:border-emerald-700/50';
  if (resultat.value.valide_par_n1) return 'bg-gradient-to-r from-green-500 to-emerald-600 text-white border-green-300/50 dark:border-green-700/50';
  if (resultat.value.soumis_le) return 'bg-gradient-to-r from-orange-500 to-amber-600 text-white border-orange-300/50 dark:border-orange-700/50';
  return 'bg-gradient-to-r from-gray-500 to-gray-600 text-white border-gray-300/50 dark:border-gray-700/50';
};

const getProgressColorClass = (taux) => {
  if (taux >= 90) return 'bg-gradient-to-r from-green-500 to-emerald-500';
  if (taux >= 70) return 'bg-gradient-to-r from-blue-500 to-indigo-500';
  if (taux >= 50) return 'bg-gradient-to-r from-amber-500 to-orange-500';
  return 'bg-gradient-to-r from-red-500 to-pink-500';
};

const getProgressLabel = (taux) => {
  if (taux >= 90) return 'Excellent';
  if (taux >= 70) return 'Bon';
  if (taux >= 50) return 'Satisfaisant';
  return 'À améliorer';
};

const getValidationBadge = () => {
  if (!resultat.value) return '';
  
  if (resultat.value.rejete_le) return 'Rejeté';
  if (resultat.value.valide_par_n2) return 'Final';
  if (resultat.value.valide_par_n1) return 'N1';
  return 'N/A';
};

const getFileIconClass = (doc) => {
  const ext = doc.extension?.toLowerCase();
  if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].includes(ext)) {
    return 'bg-gradient-to-br from-green-500 to-emerald-600';
  }
  if (ext === 'pdf') {
    return 'bg-gradient-to-br from-red-500 to-pink-600';
  }
  if (['doc', 'docx'].includes(ext)) {
    return 'bg-gradient-to-br from-blue-500 to-cyan-600';
  }
  if (['xls', 'xlsx'].includes(ext)) {
    return 'bg-gradient-to-br from-emerald-500 to-green-600';
  }
  return 'bg-gradient-to-br from-gray-600 to-gray-700';
};

const getFileIcon = (doc) => {
  const ext = doc.extension?.toLowerCase();
  if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].includes(ext)) {
    return 'fa-image';
  }
  if (ext === 'pdf') {
    return 'fa-file-pdf';
  }
  if (['doc', 'docx'].includes(ext)) {
    return 'fa-file-word';
  }
  if (['xls', 'xlsx'].includes(ext)) {
    return 'fa-file-excel';
  }
  return 'fa-file';
};

const downloadAllDocuments = () => {
  if (resultat.value?.documents) {
    resultat.value.documents.forEach(doc => {
      window.open(doc.url, '_blank');
    });
  }
};

const formatDateTime = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B';
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(1024));
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
};

const getImageUrl = (path) => {
  if (!path) return '';
  if (path.startsWith('http')) return path;
  return `${import.meta.env.VITE_APP_URL}/storage/${path}`;
};

const getInitials = (name) => {
  if (!name) return '??';
  const parts = name.trim().split(' ');
  if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
  return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
};

watch(() => props.isOpen, (newVal) => {
  if (newVal && props.resultatId) {
    fetchResultat();
    activeTab.value = 'results';
  }
});

watch(() => props.resultatId, (newVal) => {
  if (newVal && props.isOpen) {
    fetchResultat();
  }
});
</script>

<style scoped>
@keyframes float {
  0%, 100% { transform: translateY(0) translateX(0); }
  50% { transform: translateY(-20px) translateX(20px); }
}

@keyframes shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

.animate-shimmer {
  animation: shimmer 2s infinite;
}

.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-fade-enter-active > div,
.modal-fade-leave-active > div {
  transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modal-fade-enter-from > div,
.modal-fade-leave-to > div {
  transform: scale(0.95) translateY(20px);
  opacity: 0;
}

/* Scrollbar styling */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.05);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: linear-gradient(to bottom, #667eea, #764ba2);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(to bottom, #5a67d8, #6b46c1);
}

.dark ::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.05);
}

.dark ::-webkit-scrollbar-thumb {
  background: linear-gradient(to bottom, #4c51bf, #6b46c1);
}
</style>