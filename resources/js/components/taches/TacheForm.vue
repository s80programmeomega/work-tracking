<!-- resources/js/components/taches/TacheForm.vue - VERSION OPTIMISÉE -->
<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-20"
    @click.self="$emit('close')">
     <div
      class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-3xl max-h-[95vh] overflow-hidden flex flex-col animate-fade-in"
    >
      <!-- Header -->
      <div class="px-8 py-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-lg ring-2 ring-white/30">
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
          <button @click="$emit('close')"
            class="text-white/80 hover:text-white transition-colors p-2 hover:bg-white/10 rounded-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Permission Check - Loading State -->
      <div v-if="isCheckingPermissions"
        class="mx-8 mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border-l-4 border-blue-500">
        <div class="flex items-center gap-3">
          <svg class="animate-spin w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
            </path>
          </svg>
          <p class="text-sm text-blue-700 dark:text-blue-300">Vérification des permissions...</p>
        </div>
      </div>

      <!-- Permission Denied -->
      <div v-else-if="permissionChecked && !hasPermission"
        class="mx-8 mt-6 p-4 bg-red-50 border-l-4 border-red-500 dark:bg-red-900/20 dark:border-red-800 rounded-r-xl">
        <div class="flex items-start gap-3">
          <svg class="w-6 h-6 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
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

      <!-- Error Alert -->
      <div v-if="errorMessage"
        class="mx-8 mt-6 p-4 bg-red-50 border-l-4 border-red-500 dark:bg-red-900/20 dark:border-red-800 rounded-r-xl animate-shake">
        <div class="flex items-start gap-3">
          <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div class="flex-1">
            <p class="font-semibold text-red-800 dark:text-red-200">{{ errorMessage }}</p>
            <ul v-if="validationErrors.length > 0" class="mt-2 space-y-1">
              <li v-for="(error, index) in validationErrors" :key="index"
                class="text-sm text-red-700 dark:text-red-300 flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                {{ error }}
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Form Body -->
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
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Informations générales</h3>
              </div>

              <!-- Activité Context Card -->
              <div v-if="activiteContext"
                class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border-2 border-blue-200 dark:border-blue-800">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-lg bg-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
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

              <!-- Activité Selector -->
              <div v-else>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                  <span class="text-red-500">*</span> Activité
                </label>
                <select v-model="formData.activite_id" required
                  class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                  <option value="">Sélectionner une activité</option>
                  <option v-for="activite in activites" :key="activite.id" :value="activite.id">
                    {{ activite.nom }} - {{ activite.projet?.nom }}
                  </option>
                </select>
              </div>

              <!-- Titre -->
              <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                  <span class="text-red-500">*</span> Titre de la tâche
                </label>
                <input v-model="formData.titre" type="text" required
                  placeholder="Ex: Implémenter l'authentification utilisateur"
                  class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" />
              </div>

              <!-- Description -->
              <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Description</label>
                <textarea v-model="formData.description" rows="4" placeholder="Décrivez la tâche en détail..."
                  class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none"></textarea>
              </div>

              <!-- Objectif & Indicateurs -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Objectif</label>
                  <textarea v-model="formData.objectif" rows="3" placeholder="Quel est l'objectif de cette tâche ?"
                    class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none"></textarea>
                </div>
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Indicateurs de résultats</label>
                  <textarea v-model="formData.indicateurs_resultats" rows="3" placeholder="Comment mesurer le succès ?"
                    class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none"></textarea>
                </div>
              </div>
            </div>

            <!-- Section Validation -->
            <div class="space-y-5">
              <div class="flex items-center gap-3 pb-3 border-b-2 border-gray-200 dark:border-gray-700">
                <div class="p-2 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg">
                  <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Validation</h3>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="relative flex items-start gap-3 p-4 border-2 rounded-xl cursor-pointer transition-all"
                  :class="formData.validation_n1_required ? 'border-green-500 bg-green-50 dark:bg-green-900/20'
                    : 'border-gray-300 dark:border-gray-600 hover:border-gray-400'">
                  <input v-model="formData.validation_n1_required" type="checkbox"
                    class="mt-1 rounded border-gray-300 text-green-600 focus:ring-green-500" />
                  <div class="flex-1">
                    <span class="block text-sm font-bold text-gray-900 dark:text-white">Validation N1 requise</span>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Par le responsable d'activité</p>
                  </div>
                </label>

                <label class="relative flex items-start gap-3 p-4 border-2 rounded-xl cursor-pointer transition-all"
                  :class="formData.validation_n2_required ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                    : 'border-gray-300 dark:border-gray-600 hover:border-gray-400'">
                  <input v-model="formData.validation_n2_required" type="checkbox"
                    class="mt-1 rounded border-gray-300 text-purple-600 focus:ring-purple-500" />
                  <div class="flex-1">
                    <span class="block text-sm font-bold text-gray-900 dark:text-white">Validation N2 requise</span>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Par le responsable de projet</p>
                  </div>
                </label>
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
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Planification</h3>
              </div>

              <!-- Statut & Priorité -->
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Statut -->
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">Statut</label>
                  <div class="space-y-2">
                    <label v-for="statut in statutOptions" :key="statut.value"
                      class="relative flex items-center gap-3 p-3.5 border-2 rounded-xl cursor-pointer transition-all"
                      :class="formData.statut === statut.value
                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 shadow-sm'
                        : 'border-gray-300 dark:border-gray-600 hover:border-blue-300'">
                      <input type="radio" v-model="formData.statut" :value="statut.value" class="sr-only" />
                      <span :class="statut.color"
                        class="w-4 h-4 rounded-full ring-2 ring-white dark:ring-gray-800"></span>
                      <span class="flex-1 text-sm font-semibold text-gray-900 dark:text-white">{{ statut.label }}</span>
                    </label>
                  </div>
                </div>

                <!-- Priorité -->
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">Priorité</label>
                  <div class="space-y-2">
                    <label v-for="priorite in prioriteOptions" :key="priorite.value"
                      class="relative flex items-center gap-3 p-3.5 border-2 rounded-xl cursor-pointer transition-all"
                      :class="formData.priorite === priorite.value
                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 shadow-sm'
                        : 'border-gray-300 dark:border-gray-600 hover:border-blue-300'">
                      <input type="radio" v-model="formData.priorite" :value="priorite.value" class="sr-only" />
                      <span class="text-xl">{{ priorite.icon }}</span>
                      <span class="flex-1 text-sm font-semibold text-gray-900 dark:text-white">{{ priorite.label }}</span>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Dates - Version améliorée avec DatePicker -->
<div class="space-y-4">
  <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
    <CalendarIcon class="w-5 h-5 text-green-500" />
    Planification de la tâche
  </h3>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

    <!-- Date de début -->
    <div>
      <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
        Date de début
      </label>
      <DatePicker
        v-model="formData.date_debut"
        :enable-time-picker="false"
        auto-apply
        :format="'yyyy-MM-dd'"
        :locale="'fr'"
        :dark="isDark"
        placeholder="Sélectionner une date"
        class="w-full date-input"
      >
        <template #input-icon>
          <CalendarIcon class="w-5 h-5 text-gray-400" />
        </template>
      </DatePicker>
    </div>

    <!-- Date d'échéance -->
    <div>
      <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
        Date d'échéance
      </label>
      <DatePicker
        v-model="formData.echeance"
        :enable-time-picker="false"
        auto-apply
        :format="'yyyy-MM-dd'"
        :locale="'fr'"
        :dark="isDark"
        :min-date="formData.date_debut"
        placeholder="Sélectionner une date"
        class="w-full date-input"
      >
        <template #input-icon>
          <CalendarIcon class="w-5 h-5 text-gray-400" />
        </template>
      </DatePicker>
    </div>

    <!-- Date de fin réelle -->
    <div>
      <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
        Date de fin réelle
      </label>
      <DatePicker
        v-model="formData.date_fin_reelle"
        :enable-time-picker="false"
        auto-apply
        :format="'yyyy-MM-dd'"
        :locale="'fr'"
        :dark="isDark"
        placeholder="Sélectionner une date"
        class="w-full date-input"
      >
        <template #input-icon>
          <CalendarIcon class="w-5 h-5 text-gray-400" />
        </template>
      </DatePicker>
    </div>

  </div>
</div>


              <!-- Progression & Heures -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Progression (%)</label>
                  <input v-model.number="formData.taux_realisation" type="number" min="0" max="100"
                    class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" />
                  <div class="mt-3 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                    <div class="h-2.5 rounded-full transition-all duration-500"
                      :class="getProgressColorClass(formData.taux_realisation)"
                      :style="{ width: `${formData.taux_realisation}%` }"></div>
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Heures estimées</label>
                  <input v-model.number="formData.estimated_hours" type="number" min="0" step="0.5" placeholder="0"
                    class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" />
                </div>
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Heures réelles</label>
                  <input v-model.number="formData.actual_hours" type="number" min="0" step="0.5" placeholder="0"
                    class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" />
                </div>
              </div>
            </div>

            <!-- Section Équipe -->
            <div class="space-y-5">
              <div class="flex items-center gap-3 pb-3 border-b-2 border-gray-200 dark:border-gray-700">
                <div class="p-2 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg">
                  <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Équipe & Organisation</h3>
              </div>

              <!-- Assignation -->
              <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Assigner à</label>
                <div v-if="loadingMembers" class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                  <div class="flex items-center gap-3">
                    <svg class="animate-spin w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                      </path>
                    </svg>
                    <p class="text-sm text-blue-700 dark:text-blue-300">Chargement des membres...</p>
                  </div>
                </div>

                <select v-else v-model="formData.assignee_ids" multiple
                  :disabled="!canAssignUsers || availableUsers.length === 0"
                  class="w-full px-4 py-3 border-2 rounded-xl transition-all"
                  :class="!canAssignUsers || availableUsers.length === 0
                    ? 'border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 opacity-60 cursor-not-allowed'
                    : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 hover:border-blue-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500'" 
                  size="5">
                  <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                    {{ user.nom }} ({{ user.email }})
                  </option>
                </select>

                <div v-if="!loadingMembers && availableUsers.length === 0"
                  class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                  <p class="text-sm text-gray-600 dark:text-gray-400 text-center">Aucun membre disponible</p>
                </div>
                
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Maintenez Ctrl/Cmd pour sélectionner plusieurs utilisateurs
                </p>
              </div>

              <!-- Labels -->
              <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">Labels</label>
                <div class="flex flex-wrap gap-2">
                  <button v-for="label in availableLabels" :key="label.id" type="button" @click="toggleLabel(label.id)"
                    class="px-4 py-2 text-sm font-medium rounded-xl transition-all border-2" :style="{
                      backgroundColor: formData.label_ids.includes(label.id) ? label.couleur + '20' : 'transparent',
                      color: label.couleur,
                      borderColor: formData.label_ids.includes(label.id) ? label.couleur : 'transparent'
                    }" :class="formData.label_ids.includes(label.id) ? 'shadow-sm scale-105'
                      : 'border-gray-300 dark:border-gray-600 hover:border-gray-400'">
                    <span class="flex items-center gap-2">
                      <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: label.couleur }"></span>
                      {{ label.nom }}
                    </span>
                  </button>
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
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Visibilité</h3>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <label v-for="visibility in visibilityOptions" :key="visibility.value"
                  class="relative flex flex-col items-center gap-3 p-5 border-2 rounded-xl cursor-pointer transition-all text-center"
                  :class="formData.visibility === visibility.value
                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 shadow-md'
                    : 'border-gray-300 dark:border-gray-600 hover:border-blue-300'">
                  <input type="radio" v-model="formData.visibility" :value="visibility.value" class="sr-only" />
                  <span class="text-3xl">{{ visibility.icon }}</span>
                  <div>
                    <span class="block text-sm font-bold text-gray-900 dark:text-white">{{ visibility.label }}</span>
                    <span class="block text-xs text-gray-600 dark:text-gray-400 mt-1">{{ visibility.description }}</span>
                  </div>
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
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Commentaire</h3>
              </div>

              <textarea v-model="formData.commentaire" rows="3" placeholder="Ajoutez un commentaire ou une note..."
                class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none"></textarea>
            </div>

          </div>
        </form>
      </div>

      <!-- Footer -->
      <div class="px-8 py-5 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex items-center justify-between gap-4">
        <div v-if="isCheckingPermissions" class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
          <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
            </path>
          </svg>
          <span class="text-sm font-medium">Vérification...</span>
        </div>

        <div v-else-if="permissionChecked && !hasPermission"
          class="flex items-center gap-2 text-red-600 dark:text-red-400">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
          <span class="text-sm font-medium">Action non autorisée</span>
        </div>

        <div v-else class="text-sm text-gray-500 dark:text-gray-400">
          <span class="text-red-500">*</span> Champs obligatoires
        </div>

        <div class="flex items-center gap-3">
          <button type="button" @click="$emit('close')"
            class="px-6 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 font-semibold text-gray-700 dark:text-gray-300 transition-all hover:scale-105">
            Annuler
          </button>
          <button type="button" @click="handleSubmit" :disabled="loading || !hasPermission || isCheckingPermissions"
            class="px-6 py-3 rounded-xl font-semibold shadow-lg transition-all flex items-center gap-2 hover:scale-105 disabled:scale-100"
            :class="loading || !hasPermission || isCheckingPermissions
              ? 'bg-gray-300 dark:bg-gray-700 text-gray-500 cursor-not-allowed'
              : 'bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 hover:from-blue-700 hover:via-indigo-700 hover:to-purple-700 text-white shadow-blue-500/50'">
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
import { ref, computed, watch, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useTaches } from '@/composables/useTaches'
import api from '@/api/axios'
import { useActivityPermissions } from '@/composables/useActivityPermissions'
import { useActivityMembers } from '@/composables/useActivityMembers'
import DatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import { CalendarIcon } from '@/icons'

const isDark = computed(() => document.documentElement.classList.contains('dark'))


const props = defineProps({
  tache: { type: Object, default: null },
  activiteId: { type: Number, default: null },
  activiteContext: { type: Object, default: null },
  initialStatut: { type: String, default: 'a_faire' }
})

const emit = defineEmits(['close', 'saved'])

// ==================== STORES & COMPOSABLES ====================
const authStore = useAuthStore()
const { createTache, updateTache } = useTaches()
const { loadingMembers, availableMembers, loadActivityMembers, loadProjectMembers } = useActivityMembers()

// ==================== ÉTAT RÉACTIF ====================
const currentActivite = ref(props.activiteContext)
const loading = ref(false)
const activites = ref([])
const availableLabels = ref([])
const errorMessage = ref('')
const validationErrors = ref([])
const isCheckingPermissions = ref(true)
const permissionChecked = ref(false)

// ==================== PERMISSIONS ====================
const {
  canCreateTasks,
  canEditTasks,
  canAssignUsers,
  getPermissionDeniedMessage
} = useActivityPermissions(currentActivite)

const hasPermission = computed(() => {
  if (!currentActivite.value) {
    console.warn('⚠️ Aucune activité sélectionnée pour vérifier les permissions')
    return false
  }
  return props.tache ? canEditTasks.value : canCreateTasks.value
})

// ==================== UTILISATEURS DISPONIBLES ====================
const availableUsers = computed(() => {
  if (!Array.isArray(availableMembers.value)) {
    console.warn('⚠️ availableMembers n\'est pas un tableau')
    return []
  }
  return availableMembers.value
})

// ==================== FORMULAIRE ====================
const formData = ref({
  activite_id: props.activiteContext?.id || props.activiteId || '',
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

// ==================== MÉTHODES ====================

/**
 * Vérifier les permissions pour une activité
 */
const checkPermissions = async (activiteId) => {
  if (!activiteId) {
    permissionChecked.value = false
    return
  }

  isCheckingPermissions.value = true
  try {
    if (props.activiteContext && props.activiteContext.id == activiteId) {
      currentActivite.value = props.activiteContext
    } else {
      const { data } = await api.get(`/activites/${activiteId}?with_members=true`)
      currentActivite.value = data.data
    }

    formData.value.activite_id = activiteId
    permissionChecked.value = true

    console.log('✅ Permissions vérifiées:', {
      activite: activiteId,
      canCreate: canCreateTasks.value,
      canEdit: canEditTasks.value,
      canAssign: canAssignUsers.value
    })

    // Charger les membres
    await loadActivityMembers(activiteId).catch(err => {
      console.warn('⚠️ Erreur chargement membres, fallback vers projet')
      if (currentActivite.value?.projet_id) {
        return loadProjectMembers(currentActivite.value.projet_id)
      }
    })
  } catch (error) {
    console.error('❌ Erreur vérification permissions:', error)
    permissionChecked.value = true
  } finally {
    isCheckingPermissions.value = false
  }
}

/**
 * Basculer un label
 */
const toggleLabel = (labelId) => {
  const index = formData.value.label_ids.indexOf(labelId)
  index > -1 ? formData.value.label_ids.splice(index, 1) : formData.value.label_ids.push(labelId)
}

/**
 * Obtenir la classe de couleur pour la progression
 */
const getProgressColorClass = (progress) => {
  if (progress < 30) return 'bg-red-500'
  if (progress < 70) return 'bg-amber-500'
  return 'bg-green-500'
}

/**
 * Charger toutes les données nécessaires
 */
const loadData = async () => {
  try {
    const [activitesRes, labelsRes] = await Promise.all([
      api.get('/activites/mes-activites'),
      api.get('/labels')
    ])
    activites.value = activitesRes.data.data || []
    availableLabels.value = labelsRes.data.data || []
  } catch (error) {
    console.error('❌ Erreur chargement données:', error)
  }
}

/**
 * Soumettre le formulaire
 */
const handleSubmit = async () => {
  if (!hasPermission.value) {
    const action = props.tache ? 'edit_tasks' : 'create_tasks'
    errorMessage.value = getPermissionDeniedMessage(action)
    console.warn('🚫 Permission refusée')
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
    console.error('❌ Erreur soumission:', error)
    handleError(error)
  } finally {
    loading.value = false
  }
}

/**
 * Gérer les erreurs de soumission
 */
const handleError = (error) => {
  if (error.response?.status === 422) {
    const errors = error.response.data.errors
    if (errors) {
      validationErrors.value = Object.values(errors).flat()
      errorMessage.value = 'Veuillez corriger les erreurs suivantes :'
    } else {
      errorMessage.value = error.response.data.message || 'Erreur de validation'
    }
  } else if (error.response?.data?.message) {
    errorMessage.value = error.response.data.message
  } else if (error.message === 'Network Error') {
    errorMessage.value = 'Erreur de connexion. Veuillez vérifier votre connexion internet.'
  } else {
    errorMessage.value = 'Une erreur s\'est produite. Veuillez réessayer.'
  }
}

// ==================== WATCHERS ====================

/**
 * Surveiller les changements d'activité
 */
watch(() => formData.value.activite_id, async (newActiviteId) => {
  if (!newActiviteId || newActiviteId === currentActivite.value?.id) return
  
  console.log('🔄 Changement d\'activité:', newActiviteId)
  formData.value.assignee_ids = []
  
  await checkPermissions(newActiviteId)
}, { immediate: false })

/**
 * Surveiller les changements de contexte
 */
watch(() => props.activiteContext, (newContext) => {
  if (newContext && newContext.id !== currentActivite.value?.id) {
    console.log('🔄 Mise à jour contexte:', newContext.id)
    currentActivite.value = newContext
    formData.value.activite_id = newContext.id
  }
}, { immediate: true })

// ==================== LIFECYCLE ====================

onMounted(async () => {
  try {
    await loadData()

    // Initialiser l'activité
    if (props.activiteContext) {
      currentActivite.value = props.activiteContext
      formData.value.activite_id = props.activiteContext.id
      await checkPermissions(props.activiteContext.id)
    } else if (props.activiteId) {
      await checkPermissions(props.activiteId)
    }

    // Charger les données de la tâche existante
    if (props.tache) {
      formData.value = {
        ...formData.value,
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

      if (props.tache.activite_id) {
        await checkPermissions(props.tache.activite_id)
      }
    }
  } catch (error) {
    console.error('❌ Erreur initialisation:', error)
  } finally {
    if (!permissionChecked.value) {
      isCheckingPermissions.value = false
      permissionChecked.value = true
    }
  }
})
</script>

<style scoped>
/* Styles identiques au fichier original */
@reference "tailwindcss";

.custom-scrollbar::-webkit-scrollbar {
  width: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  @apply bg-gray-100 dark:bg-gray-800 rounded-full;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  @apply bg-gray-300 dark:bg-gray-600 rounded-full hover:bg-gray-400 dark:hover:bg-gray-500;
}

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
  0%, 100% { transform: translateX(0); }
  10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); }
  20%, 40%, 60%, 80% { transform: translateX(4px); }
}

/* ✅ STYLES SPÉCIFIQUES AU DATEPICKER */
.date-input {
  position: relative;
  z-index: 1;
}

/* S'assurer que le calendrier s'affiche au-dessus de la modal */
.date-input :deep(.dp__input) {
  width: 100%;
  padding: 0.875rem 1rem 0.875rem 2.5rem;
  border: 2px solid #d1d5db;
  border-radius: 0.75rem;
  background-color: white;
  color: #1f2937;
  transition: all 0.2s ease-in-out;
}

.dark .date-input :deep(.dp__input) {
  border-color: #4b5563;
  background-color: #111827;
  color: white;
}

.date-input :deep(.dp__input):focus {
  border-color: #3b82f6;
  ring: 2px;
  ring-color: #3b82f6;
}

.date-input :deep(.dp__input_icon) {
  left: 0.75rem;
  padding: 0;
}

.date-input :deep(.dp__clear_icon) {
  padding: 0;
  margin-right: 0.5rem;
}

/* Z-index élevé pour le calendrier dans les modals */
.date-input :deep(.dp__menu) {
  z-index: 10000;
}

/* Styles pour le mode sombre */
.dark .date-input :deep(.dp__menu) {
  background-color: #1f2937;
  border: 1px solid #374151;
}

.dark .date-input :deep(.dp__calendar_header) {
  color: #f9fafb;
  border-color: #374151;
}

.dark .date-input :deep(.dp__calendar_item) {
  color: #f9fafb;
}

.dark .date-input :deep(.dp__today) {
  border-color: #3b82f6;
}

.dark .date-input :deep(.dp__active_date) {
  background-color: #3b82f6;
  color: white;
}

.dark .date-input :deep(.dp__cell_inner) {
  color: #f9fafb;
}

.dark .date-input :deep(.dp__button) {
  color: #f9fafb;
}

.dark .date-input :deep(.dp__button:hover) {
  background-color: #374151;
}
</style>