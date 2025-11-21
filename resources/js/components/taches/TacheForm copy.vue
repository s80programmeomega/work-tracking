<!-- resources/js/components/taches/TacheForm.vue -->
<template>
  <!-- ✅ z-index très élevé pour passer au-dessus de sidebar et header -->
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-20"
    @click.self="$emit('close')" >
    <div
      class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-3xl max-h-[95vh] overflow-hidden flex flex-col animate-fade-in"
    >
      <!-- Header avec gradient moderne -->
      <div
        class="px-8 py-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div
              class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-lg ring-2 ring-white/30"
            >
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
            <div>
              <h2 class="text-2xl font-bold text-white">
                {{ tache ? 'Modifier la tâche' : 'Nouvelle tâche' }}
              </h2>
              <p class="text-sm text-white/80 mt-1">
                {{ activiteContext ? `Activité: ${activiteContext.nom}` : 'Créez une nouvelle tâche' }}
              </p>
            </div>
          </div>
          <button 
            @click="$emit('close')"
            class="text-white/80 hover:text-white transition-colors p-2 hover:bg-white/10 rounded-lg"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Message de permission refusée -->
      <div 
        v-if="!hasPermission" 
        class="mx-8 mt-6 p-4 bg-red-50 border-l-4 border-red-500 dark:bg-red-900/20 dark:border-red-800 rounded-r-xl"
      >
        <div class="flex items-start gap-3">
          <svg class="w-6 h-6 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
          <div class="flex-1">
            <h3 class="font-semibold text-red-800 dark:text-red-200">Permission refusée</h3>
            <p class="text-sm text-red-700 dark:text-red-300 mt-1">
              {{ getPermissionDeniedMessage(tache ? 'edit_tasks' : 'create_tasks') }}
            </p>
          </div>
        </div>
      </div>

      <!-- Error Alert amélioré -->
      <div 
        v-if="errorMessage"
        class="mx-8 mt-6 p-4 bg-red-50 border-l-4 border-red-500 dark:bg-red-900/20 dark:border-red-800 rounded-r-xl animate-shake"
      >
        <div class="flex items-start gap-3">
          <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div class="flex-1">
            <p class="font-semibold text-red-800 dark:text-red-200">{{ errorMessage }}</p>
            <ul v-if="validationErrors.length > 0" class="mt-2 space-y-1">
              <li v-for="(error, index) in validationErrors" :key="index" class="text-sm text-red-700 dark:text-red-300 flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                {{ error }}
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Body avec scroll -->
      <div class="flex-1 overflow-y-auto custom-scrollbar">
        <form @submit.prevent="handleSubmit" class="p-8">
          <div class="space-y-8">
            
            <!-- Section Informations Générales -->
            <div class="space-y-5">
              <div class="flex items-center gap-3 pb-3 border-b-2 border-gray-200 dark:border-gray-700">
                <div class="p-2 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg">
                  <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                  Informations générales
                </h3>
              </div>

              <!-- Activité Context Card -->
              <div v-if="activiteContext" class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border-2 border-blue-200 dark:border-blue-800">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-lg bg-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                  </div>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-blue-700 dark:text-blue-300">Activité sélectionnée</p>
                    <p class="text-lg font-bold text-blue-900 dark:text-blue-100">{{ activiteContext.nom }}</p>
                  </div>
                  <div class="text-right">
                    <p class="text-xs text-blue-600 dark:text-blue-400">Projet</p>
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">{{ activiteContext.projet?.nom }}</p>
                  </div>
                </div>
              </div>

              <!-- Sélection activité si pas de contexte -->
              <div v-else>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                  <span class="text-red-500">*</span>
                  Activité
                </label>
                <select 
                  v-model="formData.activite_id" 
                  required
                  class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                >
                  <option value="">Sélectionner une activité</option>
                  <option v-for="activite in activites" :key="activite.id" :value="activite.id">
                    {{ activite.nom }} - {{ activite.projet?.nom }}
                  </option>
                </select>
              </div>

              <!-- Titre -->
              <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                  <span class="text-red-500">*</span>
                  Titre de la tâche
                </label>
                <input 
                  v-model="formData.titre" 
                  type="text" 
                  required
                  placeholder="Ex: Implémenter l'authentification utilisateur"
                  class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                />
              </div>

              <!-- Description -->
              <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                  Description
                </label>
                <textarea 
                  v-model="formData.description" 
                  rows="4" 
                  placeholder="Décrivez la tâche en détail..."
                  class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none"
                ></textarea>
              </div>

              <!-- Objectif & Indicateurs -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    Objectif
                  </label>
                  <textarea 
                    v-model="formData.objectif" 
                    rows="3" 
                    placeholder="Quel est l'objectif de cette tâche ?"
                    class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none"
                  ></textarea>
                </div>
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    Indicateurs de résultats
                  </label>
                  <textarea 
                    v-model="formData.indicateurs_resultats" 
                    rows="3" 
                    placeholder="Comment mesurer le succès ?"
                    class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none"
                  ></textarea>
                </div>
              </div>
            </div>

            <!-- Section Validation -->
            <div class="space-y-5">
              <div class="flex items-center gap-3 pb-3 border-b-2 border-gray-200 dark:border-gray-700">
                <div class="p-2 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg">
                  <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                  Validation
                </h3>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="relative flex items-start gap-3 p-4 border-2 rounded-xl cursor-pointer transition-all"
                  :class="formData.validation_n1_required ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-gray-400'"
                >
                  <input 
                    v-model="formData.validation_n1_required" 
                    type="checkbox"
                    class="mt-1 rounded border-gray-300 text-green-600 focus:ring-green-500"
                  />
                  <div class="flex-1">
                    <span class="block text-sm font-bold text-gray-900 dark:text-white">
                      Validation N1 requise
                    </span>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                      Par le responsable d'activité ou membre avec permission
                    </p>
                  </div>
                  <svg v-if="formData.validation_n1_required" class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </label>

                <label class="relative flex items-start gap-3 p-4 border-2 rounded-xl cursor-pointer transition-all"
                  :class="formData.validation_n2_required ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-gray-400'"
                >
                  <input 
                    v-model="formData.validation_n2_required" 
                    type="checkbox"
                    class="mt-1 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                  />
                  <div class="flex-1">
                    <span class="block text-sm font-bold text-gray-900 dark:text-white">
                      Validation N2 requise
                    </span>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                      Par le responsable de projet
                    </p>
                  </div>
                  <svg v-if="formData.validation_n2_required" class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </label>
              </div>
              
              <!-- Info validation -->
              <div v-if="formData.validation_n1_required || formData.validation_n2_required" 
                class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800"
              >
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div>
                    <p class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-2">
                      ℹ️ Validation automatique selon les permissions
                    </p>
                    <ul class="text-xs text-blue-700 dark:text-blue-400 space-y-1">
                      <li v-if="formData.validation_n1_required" class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                        <strong>Validation N1</strong> : Responsable activité + membres avec permission
                      </li>
                      <li v-if="formData.validation_n2_required" class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                        <strong>Validation N2</strong> : Responsable projet uniquement
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <!-- Section Planification -->
            <div class="space-y-5">
              <div class="flex items-center gap-3 pb-3 border-b-2 border-gray-200 dark:border-gray-700">
                <div class="p-2 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg">
                  <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                  Planification
                </h3>
              </div>

              <!-- Statut & Priorité -->
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Statut -->
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                    Statut
                  </label>
                  <div class="space-y-2">
                    <label v-for="statut in statutOptions" :key="statut.value"
                      class="relative flex items-center gap-3 p-3.5 border-2 rounded-xl cursor-pointer transition-all group"
                      :class="formData.statut === statut.value
                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 shadow-sm'
                        : 'border-gray-300 dark:border-gray-600 hover:border-blue-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                    >
                      <input type="radio" v-model="formData.statut" :value="statut.value" class="sr-only" />
                      <span :class="statut.color" class="w-4 h-4 rounded-full ring-2 ring-white dark:ring-gray-800 shadow-sm"></span>
                      <span class="flex-1 text-sm font-semibold text-gray-900 dark:text-white">{{ statut.label }}</span>
                      <svg v-if="formData.statut === statut.value" class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                      </svg>
                    </label>
                  </div>
                </div>

                <!-- Priorité -->
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                    Priorité
                  </label>
                  <div class="space-y-2">
                    <label v-for="priorite in prioriteOptions" :key="priorite.value"
                      class="relative flex items-center gap-3 p-3.5 border-2 rounded-xl cursor-pointer transition-all group"
                      :class="formData.priorite === priorite.value
                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 shadow-sm'
                        : 'border-gray-300 dark:border-gray-600 hover:border-blue-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                    >
                      <input type="radio" v-model="formData.priorite" :value="priorite.value" class="sr-only" />
                      <span class="text-xl">{{ priorite.icon }}</span>
                      <span class="flex-1 text-sm font-semibold text-gray-900 dark:text-white">{{ priorite.label }}</span>
                      <svg v-if="formData.priorite === priorite.value" class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                      </svg>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Dates -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    Date de début
                  </label>
                  <input 
                    v-model="formData.date_debut" 
                    type="date"
                    class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                  />
                </div>
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    Date d'échéance
                  </label>
                  <input 
                    v-model="formData.echeance" 
                    type="date"
                    class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                  />
                </div>
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    Date de fin réelle
                  </label>
                  <input 
                    v-model="formData.date_fin_reelle" 
                    type="date"
                    class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                  />
                </div>
              </div>

              <!-- Progression & Heures -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    Progression (%)
                  </label>
                  <input 
                    v-model.number="formData.taux_realisation" 
                    type="number" 
                    min="0" 
                    max="100"
                    class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                  />
                  <div class="mt-3 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                    <div 
                      class="h-2.5 rounded-full transition-all duration-500"
                      :class="getProgressColorClass(formData.taux_realisation)"
                      :style="{ width: `${formData.taux_realisation}%` }"
                    ></div>
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    Heures estimées
                  </label>
                  <input 
                    v-model.number="formData.estimated_hours" 
                    type="number" 
                    min="0" 
                    step="0.5" 
                    placeholder="0"
                    class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                  />
                </div>
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    Heures réelles
                  </label>
                  <input 
                    v-model.number="formData.actual_hours" 
                    type="number" 
                    min="0" 
                    step="0.5" 
                    placeholder="0"
                    class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                  />
                </div>
              </div>
            </div>

            <!-- Section Équipe & Organisation -->
            <div class="space-y-5">
                <div class="flex items-center gap-3 pb-3 border-b-2 border-gray-200 dark:border-gray-700">
                  <div class="p-2 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                  </div>
                  <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                    Équipe & Organisation
                  </h3>
                </div>

              <!-- Assignation avec permissions -->
              <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                  Assigner à
                </label>
                <!-- Chargement des membres -->
                <div v-if="loadingMembers" class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl mb-4">
                  <div class="flex items-center gap-3">
                    <svg class="animate-spin w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-sm text-blue-700 dark:text-blue-300">
                      Chargement des membres de l'activité...
                    </p>
                  </div>
                </div>
                
                <!-- Message si pas de permission -->
               <div v-else-if="!canAssignUsers" class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border-l-4 border-amber-500 mb-4">
                  <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <div>
                      <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                        Permission requise
                      </p>
                      <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">
                        Vous n'avez pas la permission d'assigner des membres à cette tâche
                      </p>
                    </div>
                  </div>
                </div>
                
                <!-- Sélecteur multiple amélioré -->
                <div class="relative">
                   <select 
                      v-model="formData.assignee_ids" 
                      multiple
                      :disabled="!canAssignUsers || availableUsers.length === 0 || loadingMembers"
                      class="w-full px-4 py-3 border-2 rounded-xl transition-all"
                      :class="[
                        !canAssignUsers || availableUsers.length === 0 || loadingMembers
                          ? 'border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 opacity-60 cursor-not-allowed' 
                          : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 hover:border-blue-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500'
                      ]"
                      size="5" >
                      <option v-for="user in availableUsers" :key="user.id" :value="user.id" class="py-2">
                        {{ user.nom }} ({{ user.email }})
                        <template v-if="user.id === activiteContext?.responsable_id"> - 👑 Responsable</template>
                        <template v-else-if="getUserRole(user)"> - {{ getUserRole(user) }}</template>
                      </option>
                  </select>
                  
                  <!-- Icône indicative -->
                   <div class="absolute top-3 right-3 pointer-events-none">
                      <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                      </svg>
                    </div>
                </div>
                
                <!-- Info ou message -->
                  <div v-if="!loadingMembers && availableUsers.length === 0" class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                      Aucun membre disponible dans cette activité
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1 text-center">
                      Les membres doivent être ajoutés à l'activité avant de pouvoir être assignés
                    </p>
                  </div>
                <p v-else-if="!loadingMembers" class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Maintenez Ctrl/Cmd pour sélectionner plusieurs utilisateurs
                </p>
                
                <!-- Membres sélectionnés -->
                <div v-if="formData.assignee_ids.length > 0" class="mt-4 flex flex-wrap gap-2">
                  <span 
                    v-for="userId in formData.assignee_ids" 
                    :key="userId"
                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 rounded-lg text-sm font-medium"
                  >
                    <div class="w-6 h-6 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-bold">
                      {{ getInitials(getUserById(userId)?.nom) }}
                    </div>
                    {{ getUserById(userId)?.nom }}
                    <button 
                      v-if="canAssignUsers"
                      @click="removeAssignee(userId)" 
                      type="button"
                      class="hover:bg-blue-200 dark:hover:bg-blue-800 rounded-full p-0.5 transition-colors"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </span>
                </div>
              </div>

              <!-- Labels -->
              <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                  Labels
                </label>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="label in availableLabels" 
                    :key="label.id"
                    type="button"
                    @click="toggleLabel(label.id)"
                    class="px-4 py-2 text-sm font-medium rounded-xl transition-all border-2"
                    :style="{
                      backgroundColor: formData.label_ids.includes(label.id) ? label.couleur + '20' : 'transparent',
                      color: label.couleur,
                      borderColor: formData.label_ids.includes(label.id) ? label.couleur : 'transparent'
                    }"
                    :class="formData.label_ids.includes(label.id) ? 'shadow-sm scale-105' : 'border-gray-300 dark:border-gray-600 hover:border-gray-400'"
                  >
                    <span class="flex items-center gap-2">
                      <span 
                        class="w-2.5 h-2.5 rounded-full" 
                        :style="{ backgroundColor: label.couleur }"
                      ></span>
                      {{ label.nom }}
                      <svg v-if="formData.label_ids.includes(label.id)" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                      </svg>
                    </span>
                  </button>
                  <div v-if="availableLabels.length === 0" class="w-full text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
                    Aucun label disponible
                  </div>
                </div>
              </div>
            </div>

            <!-- Section Visibilité -->
            <div class="space-y-5">
              <div class="flex items-center gap-3 pb-3 border-b-2 border-gray-200 dark:border-gray-700">
                <div class="p-2 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg">
                  <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                  Visibilité
                </h3>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <label 
                  v-for="visibility in visibilityOptions" 
                  :key="visibility.value"
                  class="relative flex flex-col items-center gap-3 p-5 border-2 rounded-xl cursor-pointer transition-all text-center group"
                  :class="formData.visibility === visibility.value
                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 shadow-md scale-105'
                    : 'border-gray-300 dark:border-gray-600 hover:border-blue-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                >
                  <input type="radio" v-model="formData.visibility" :value="visibility.value" class="sr-only" />
                  <span class="text-3xl">{{ visibility.icon }}</span>
                  <div>
                    <span class="block text-sm font-bold text-gray-900 dark:text-white">{{ visibility.label }}</span>
                    <span class="block text-xs text-gray-600 dark:text-gray-400 mt-1">{{ visibility.description }}</span>
                  </div>
                  <svg v-if="formData.visibility === visibility.value" 
                    class="absolute top-3 right-3 w-5 h-5 text-blue-600" 
                    fill="currentColor" 
                    viewBox="0 0 20 20"
                  >
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </label>
              </div>
            </div>

            <!-- Section Commentaire -->
            <div class="space-y-5">
              <div class="flex items-center gap-3 pb-3 border-b-2 border-gray-200 dark:border-gray-700">
                <div class="p-2 bg-gradient-to-br from-amber-500 to-yellow-600 rounded-lg">
                  <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                  </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                  Commentaire
                </h3>
              </div>
              
              <textarea 
                v-model="formData.commentaire" 
                rows="3" 
                placeholder="Ajoutez un commentaire ou une note..."
                class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none"
              ></textarea>
            </div>

          </div>
        </form>
      </div>

      <!-- Footer avec actions -->
      <div class="px-8 py-5 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex items-center justify-between gap-4">
        <div v-if="!hasPermission" class="flex items-center gap-2 text-red-600 dark:text-red-400">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
          <span class="text-sm font-medium">Action non autorisée</span>
        </div>
        
        <div v-else class="text-sm text-gray-500 dark:text-gray-400">
          <span class="text-red-500">*</span> Champs obligatoires
        </div>

        <div class="flex items-center gap-3">
          <button 
            type="button" 
            @click="$emit('close')"
            class="px-6 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 font-semibold text-gray-700 dark:text-gray-300 transition-all hover:scale-105"
          >
            Annuler
          </button>
          <button 
            type="button" 
            @click="handleSubmit" 
            :disabled="loading || !hasPermission"
            class="px-6 py-3 rounded-xl font-semibold shadow-lg transition-all flex items-center gap-2 hover:scale-105 disabled:scale-100"
            :class="loading || !hasPermission 
              ? 'bg-gray-300 dark:bg-gray-700 text-gray-500 cursor-not-allowed' 
              : 'bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 hover:from-blue-700 hover:via-indigo-700 hover:to-purple-700 text-white shadow-blue-500/50'"
          >
            <svg v-if="loading" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
              </path>
            </svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ loading ? 'Enregistrement...' : (hasPermission ? (tache ? 'Mettre à jour' : 'Créer la tâche') : 'Permission refusée') }}
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { useTaches } from '@/composables/useTaches'
import api from '@/api/axios'
import { useActivityPermissions } from '@/composables/useActivityPermissions'
import { useActivityMembers } from '@/composables/useActivityMembers'  

const props = defineProps({
  tache: {
    type: Object,
    default: null
  },
  activiteId: {
    type: Number,
    default: null
  },
  activiteContext: {
    type: Object,
    default: null
  },
  initialStatut: {
    type: String,
    default: 'a_faire'
  }
})

const emit = defineEmits(['close', 'saved'])

const authStore = useAuthStore()
const { createTache, updateTache } = useTaches()

// ✅ CORRECTION : Utiliser le composable pour les membres d'activité
const {
  loadingMembers,
  activityMembers,
  availableMembers,
  loadActivityMembers,
  loadProjectMembers
} = useActivityMembers()

// ✅ CORRECTION : Créer une référence réactive pour l'activité
const currentActivite = ref(props.activiteContext)

// ✅ CORRECTION : Utiliser useActivityPermissions avec l'activité réactive
const {
  canCreateTasks,
  canEditTasks,
  canAssignUsers,
  getPermissionDeniedMessage,
  canView,
  isSuperAdmin,
  isActivityResponsable,
  isProjectResponsable
} = useActivityPermissions(currentActivite)

const loading = ref(false)
const activites = ref([])
const availableLabels = ref([])
const errorMessage = ref('')
const validationErrors = ref([])

// ✅ CORRECTION : Permission calculée avec fallback sécurisé
const hasPermission = computed(() => {
  if (!currentActivite.value) {
    console.warn('⚠️ Aucune activité sélectionnée pour vérifier les permissions')
    return false
  }

  if (props.tache) {
    return canEditTasks.value
  } else {
    return canCreateTasks.value
  }
})

const formData = ref({
  activite_id: props.activiteId || '',
  titre: '',
  description: '',
  objectif: '',
  indicateurs_resultats: '',
  statut: props.initialStatut || 'a_faire',
  priorite: 'moyenne',
  echeance: '',
  date_debut: '',
  date_fin_reelle: '',
  taux_realisation: 0,
  estimated_hours: null,
  actual_hours: null,
  validation_n1_required: false,
  validation_n2_required: false,
  couleur: '#3B82F6',
  commentaire: '',
  assignee_ids: [],
  label_ids: [],
  visibility: 'members_only'
})

const statutOptions = [
  { value: 'a_faire', label: 'À faire', color: 'bg-gray-500' },
  { value: 'en_cours', label: 'En cours', color: 'bg-blue-500' },
  { value: 'termine', label: 'Terminé', color: 'bg-green-500' }
]

const prioriteOptions = [
  { value: 'faible', label: 'Faible', icon: '🟢' },
  { value: 'moyenne', label: 'Moyenne', icon: '🟡' },
  { value: 'elevee', label: 'Élevée', icon: '🟠' },
  { value: 'critique', label: 'Critique', icon: '🔴' }
]

const visibilityOptions = [
  { value: 'public', label: 'Public', icon: '👁️', description: 'Tous les membres du projet' },
  { value: 'members_only', label: 'Membres', icon: '👥', description: 'Membres de l\'activité' },
  { value: 'private', label: 'Privé', icon: '🔒', description: 'Seulement les assignés' }
]

// ✅ CORRECTION : Utiliser les membres de l'activité
const availableUsers = computed(() => {
  return availableMembers.value || []
})

// ✅ CORRECTION : Obtenir le rôle de l'utilisateur dans l'activité
const getUserRole = (user) => {
  if (!currentActivite.value) return null
  
  // Si c'est le responsable de l'activité
  if (currentActivite.value.responsable_id === user.id) {
    return 'Responsable'
  }
  
  // Si c'est le responsable du projet
  if (currentActivite.value.projet?.responsable_id === user.id) {
    return 'Responsable Projet'
  }
  
  // Vérifier les rôles dans les membres de l'activité
  const membre = currentActivite.value.membres?.find(m => m.id === user.id)
  if (membre) {
    return membre.pivot?.role || 'Membre'
  }
  
  return null
}

// ✅ CORRECTION : Charger les membres quand l'activité change
const loadMembersForActivity = async (activiteId) => {
  if (!activiteId) return
  
  try {
    console.log('🔄 Chargement des membres pour activité:', activiteId)
    await loadActivityMembers(activiteId)
  } catch (error) {
    console.error('❌ Erreur chargement membres:', error)
    // Fallback: essayer de charger via le projet
    if (currentActivite.value?.projet_id) {
      console.log('🔄 Fallback: Chargement via projet:', currentActivite.value.projet_id)
      await loadProjectMembers(currentActivite.value.projet_id)
    }
  }
}

// ✅ CORRECTION : Mettre à jour l'activité courante
const updateCurrentActivite = async (activiteId) => {
  if (!activiteId) {
    currentActivite.value = null
    return
  }

  try {
    // Si on a déjà l'activité contextuelle, l'utiliser
    if (props.activiteContext && props.activiteContext.id === activiteId) {
      currentActivite.value = props.activiteContext
      return
    }

    // Sinon charger l'activité depuis l'API
    console.log('🔄 Chargement des détails de l\'activité:', activiteId)
    const { data } = await api.get(`/activites/${activiteId}?with_members=true`)
    currentActivite.value = data.data
    
    console.log('✅ Activité chargée pour permissions:', {
      id: currentActivite.value.id,
      nom: currentActivite.value.nom,
      responsable_id: currentActivite.value.responsable_id,
      membres_count: currentActivite.value.membres?.length || 0
    })
  } catch (error) {
    console.error('❌ Erreur chargement activité:', error)
    currentActivite.value = null
  }
}

// Methods
const toggleLabel = (labelId) => {
  const index = formData.value.label_ids.indexOf(labelId)
  if (index > -1) {
    formData.value.label_ids.splice(index, 1)
  } else {
    formData.value.label_ids.push(labelId)
  }
}

const removeAssignee = (userId) => {
  const index = formData.value.assignee_ids.indexOf(userId)
  if (index > -1) {
    formData.value.assignee_ids.splice(index, 1)
  }
}

const getUserById = (userId) => {
  return availableUsers.value.find(u => u.id === userId)
}

const getInitials = (name) => {
  if (!name) return '??'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const getProgressColorClass = (progress) => {
  if (progress < 30) return 'bg-red-500'
  if (progress < 70) return 'bg-amber-500'
  return 'bg-green-500'
}

const loadActivites = async () => {
  try {
    const { data } = await api.get('/activites/mes-activites')
    activites.value = data.data || []
  } catch (error) {
    console.error('Error loading activites:', error)
  }
}

const loadLabels = async () => {
  try {
    const { data } = await api.get('/labels')
    availableLabels.value = data.data || []
  } catch (error) {
    console.error('Error loading labels:', error)
  }
}

const handleSubmit = async () => {
  // ✅ CORRECTION : Vérification robuste des permissions
  if (!hasPermission.value) {
    const action = props.tache ? 'edit_tasks' : 'create_tasks'
    errorMessage.value = getPermissionDeniedMessage(action)
    
    console.warn('🚫 Permission refusée:', {
      action,
      hasPermission: hasPermission.value,
      canCreateTasks: canCreateTasks.value,
      canEditTasks: canEditTasks.value,
      activite: currentActivite.value?.id,
      user: authStore.user?.id
    })
    return
  }

  loading.value = true
  errorMessage.value = ''
  validationErrors.value = []

  try {
    if (currentActivite.value) {
      formData.value.activite_id = currentActivite.value.id
    }

    if (props.tache) {
      await updateTache(props.tache.id, formData.value)
    } else {
      await createTache(formData.value)
    }
    emit('saved')
  } catch (error) {
    console.error('❌ Erreur soumission formulaire:', error)
    handleError(error)
  } finally {
    loading.value = false
  }
}

const handleError = (error) => {
  if (error.response && error.response.status === 422) {
    const errors = error.response.data.errors
    if (errors) {
      validationErrors.value = Object.values(errors).flat()
      errorMessage.value = 'Veuillez corriger les erreurs suivantes :'
    } else {
      errorMessage.value = error.response.data.message || 'Erreur de validation'
    }
  } else if (error.response && error.response.data && error.response.data.message) {
    errorMessage.value = error.response.data.message
  } else if (error.message === 'Network Error') {
    errorMessage.value = 'Erreur de connexion. Veuillez vérifier votre connexion internet.'
  } else {
    errorMessage.value = 'Une erreur s\'est produite. Veuillez réessayer.'
  }
}

// ✅ CORRECTION : Chargement initial amélioré
onMounted(async () => {
  try {
    await Promise.all([loadActivites(), loadLabels()])

    // Initialiser l'activité courante
    if (props.activiteContext) {
      currentActivite.value = props.activiteContext
      formData.value.activite_id = props.activiteContext.id
      await loadMembersForActivity(props.activiteContext.id)
    }

    if (props.tache) {
      formData.value = {
        activite_id: props.tache.activite_id || '',
        titre: props.tache.titre || '',
        description: props.tache.description || '',
        objectif: props.tache.objectif || '',
        indicateurs_resultats: props.tache.indicateurs_resultats || '',
        statut: props.tache.statut || 'a_faire',
        priorite: props.tache.priorite || 'moyenne',
        echeance: props.tache.echeance || '',
        date_debut: props.tache.date_debut || '',
        date_fin_reelle: props.tache.date_fin_reelle || '',
        taux_realisation: props.tache.taux_realisation || 0,
        estimated_hours: props.tache.estimated_hours || null,
        actual_hours: props.tache.actual_hours || null,
        validation_n1_required: props.tache.validation_n1_required || false,
        validation_n2_required: props.tache.validation_n2_required || false,
        couleur: props.tache.couleur || '#3B82F6',
        commentaire: props.tache.commentaire || '',
        assignee_ids: props.tache.assignees?.map(a => a.id) || [],
        label_ids: props.tache.labels?.map(l => l.id) || [],
        visibility: props.tache.visibility || 'members_only'
      }
      
      // Charger l'activité et les membres pour la tâche existante
      if (props.tache.activite_id) {
        await updateCurrentActivite(props.tache.activite_id)
        await loadMembersForActivity(props.tache.activite_id)
      }
    }
  } catch (error) {
    console.error('❌ Erreur chargement données formulaire:', error)
  }
})

// ✅ CORRECTION : Watchers améliorés
watch(() => formData.value.activite_id, async (newActiviteId) => {
  console.log('🔄 Changement d\'activité:', newActiviteId)
  
  // Reset assignees quand l'activité change
  formData.value.assignee_ids = []
  
  if (newActiviteId) {
    await updateCurrentActivite(newActiviteId)
    await loadMembersForActivity(newActiviteId)
  } else {
    currentActivite.value = null
  }
})

// ✅ CORRECTION : Watcher pour l'activité contextuelle
watch(() => props.activiteContext, (newContext) => {
  if (newContext) {
    console.log('🔄 Mise à jour contexte activité:', newContext.id)
    currentActivite.value = newContext
    formData.value.activite_id = newContext.id
  }
})

// ✅ CORRECTION : Debug des permissions
watch([currentActivite, canCreateTasks, canEditTasks], () => {
  console.log('🔍 État des permissions:', {
    activite: currentActivite.value?.id,
    canCreateTasks: canCreateTasks.value,
    canEditTasks: canEditTasks.value,
    canAssignUsers: canAssignUsers.value,
    hasPermission: hasPermission.value,
    isSuperAdmin: isSuperAdmin.value,
    isActivityResponsable: isActivityResponsable.value,
    isProjectResponsable: isProjectResponsable.value
  })
})
</script>

<style scoped>
@reference "tailwindcss";

/* Custom scrollbar */
.custom-scrollbar::-webkit-scrollbar {
  width: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  @apply bg-gray-100 dark:bg-gray-800 rounded-full;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  @apply bg-gray-300 dark:bg-gray-600 rounded-full hover:bg-gray-400 dark:hover:bg-gray-500;
}

/* Animations */
.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}

.animate-shake {
  animation: shake 0.5s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(-10px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

@keyframes shake {
  0%, 100% {
    transform: translateX(0);
  }
  10%, 30%, 50%, 70%, 90% {
    transform: translateX(-4px);
  }
  20%, 40%, 60%, 80% {
    transform: translateX(4px);
  }
}

/* Hover effects for interactive elements */
.group:hover .group-hover\:scale-105 {
  transform: scale(1.05);
}

/* Smooth transitions for all interactive elements */
button, select, input, textarea, label {
  transition: all 0.2s ease-in-out;
}

/* Focus states for accessibility */
input:focus, textarea:focus, select:focus {
  outline: none;
  ring: 2px;
}

/* Custom checkbox and radio styles */
input[type="checkbox"], input[type="radio"] {
  border-radius: 4px;
}

input[type="checkbox"]:checked {
  background-color: rgb(37, 99, 235);
  border-color: rgb(37, 99, 235);
}

/* Gradient text for headers */
.gradient-text {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* Glass morphism effect for modal */
.glass-effect {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Custom shadow for depth */
.custom-shadow {
  box-shadow: 
    0 10px 25px -3px rgba(0, 0, 0, 0.1),
    0 4px 6px -2px rgba(0, 0, 0, 0.05),
    0 0 0 1px rgba(0, 0, 0, 0.05);
}

.dark .custom-shadow {
  box-shadow: 
    0 10px 25px -3px rgba(0, 0, 0, 0.3),
    0 4px 6px -2px rgba(0, 0, 0, 0.2),
    0 0 0 1px rgba(255, 255, 255, 0.1);
}

/* Progress bar animation */
.progress-bar {
  transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Loading spinner animation */
.loading-spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Pulse animation for loading states */
.pulse-soft {
  animation: pulseSoft 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulseSoft {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}

/* Slide in animations for form sections */
.slide-in-up {
  animation: slideInUp 0.4s ease-out;
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Border glow effect for focused inputs */
.glow-border:focus {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  border-color: rgb(59, 130, 246);
}

/* Custom select dropdown styling */
select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 0.5rem center;
  background-repeat: no-repeat;
  background-size: 1.5em 1.5em;
  padding-right: 2.5rem;
}

.dark select {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%239ca3af' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
}

/* Multi-select styling */
select[multiple] {
  background-image: none;
  padding-right: 1rem;
}

/* Label animations */
.label-float {
  transition: all 0.2s ease-in-out;
}

.label-float:focus-within {
  transform: translateY(-2px);
}

/* Button hover effects */
.btn-hover {
  position: relative;
  overflow: hidden;
}

.btn-hover::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s;
}

.btn-hover:hover::before {
  left: 100%;
}

/* Card hover effects */
.card-hover {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.card-hover:hover {
  transform: translateY(-2px);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.dark .card-hover:hover {
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
}

/* Gradient border animation */
.gradient-border {
  position: relative;
  background: linear-gradient(white, white) padding-box,
              linear-gradient(135deg, #667eea, #764ba2) border-box;
  border: 2px solid transparent;
}

.dark .gradient-border {
  background: linear-gradient(#1f2937, #1f2937) padding-box,
              linear-gradient(135deg, #667eea, #764ba2) border-box;
}

/* Success state animations */
.success-check {
  animation: successCheck 0.5s ease-in-out;
}

@keyframes successCheck {
  0% {
    transform: scale(0);
    opacity: 0;
  }
  50% {
    transform: scale(1.2);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

/* Error state animations */
.error-shake {
  animation: errorShake 0.5s ease-in-out;
}

@keyframes errorShake {
  0%, 100% {
    transform: translateX(0);
  }
  25% {
    transform: translateX(-5px);
  }
  75% {
    transform: translateX(5px);
  }
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .custom-scrollbar::-webkit-scrollbar {
    width: 4px;
  }
  
  .animate-fade-in {
    animation-duration: 0.2s;
  }
}

/* Print styles */
@media print {
  .no-print {
    display: none !important;
  }
  
  .print-break {
    page-break-after: always;
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .border-gray-300 {
    border-color: #000;
  }
  
  .dark .border-gray-600 {
    border-color: #fff;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Custom focus indicators for better accessibility */
.focus-indicator:focus {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}

/* Selection styles */
::selection {
  @apply bg-blue-200 dark:bg-blue-800 text-blue-900 dark:text-blue-100;
}

/* Placeholder styling */
::placeholder {
  @apply text-gray-400 dark:text-gray-500;
}

/* Autofill styling */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus,
textarea:-webkit-autofill,
textarea:-webkit-autofill:hover,
textarea:-webkit-autofill:focus,
select:-webkit-autofill,
select:-webkit-autofill:hover,
select:-webkit-autofill:focus {
  -webkit-text-fill-color: #1f2937;
  -webkit-box-shadow: 0 0 0px 1000px #f9fafb inset;
  transition: background-color 5000s ease-in-out 0s;
}

.dark input:-webkit-autofill,
.dark input:-webkit-autofill:hover,
.dark input:-webkit-autofill:focus,
.dark textarea:-webkit-autofill,
.dark textarea:-webkit-autofill:hover,
.dark textarea:-webkit-autofill:focus,
.dark select:-webkit-autofill,
.dark select:-webkit-autofill:hover,
.dark select:-webkit-autofill:focus {
  -webkit-text-fill-color: #f9fafb;
  -webkit-box-shadow: 0 0 0px 1000px #1f2937 inset;
}

/* Smooth scrolling for the entire modal */
.modal-content {
  scroll-behavior: smooth;
}

/* Custom backdrop blur for modern browsers */
@supports (backdrop-filter: blur(10px)) {
  .backdrop-blur-sm {
    backdrop-filter: blur(8px);
  }
}

/* Loading skeleton animation */
.skeleton {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: skeletonLoading 1.5s infinite;
}

.dark .skeleton {
  background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
  background-size: 200% 100%;
}

@keyframes skeletonLoading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

/* Custom tooltip styles */
.tooltip {
  position: relative;
}

.tooltip::before {
  content: attr(data-tooltip);
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  background: #1f2937;
  color: white;
  padding: 0.5rem 0.75rem;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  white-space: nowrap;
  opacity: 0;
  visibility: hidden;
  transition: all 0.2s ease-in-out;
  z-index: 1000;
}

.tooltip:hover::before {
  opacity: 1;
  visibility: visible;
  transform: translateX(-50%) translateY(-0.5rem);
}

.dark .tooltip::before {
  background: #f9fafb;
  color: #1f2937;
}
</style>