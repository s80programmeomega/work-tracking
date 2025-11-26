<!-- resources/js/components/taches/TacheForm.vue - VERSION AVEC ONGLETS -->
<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 sm:p-6 lg:p-20"
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
      <div v-if="isCheckingPermissions" class="mx-8 mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border-l-4 border-blue-500">
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

      <!-- Navigation par onglets -->
      <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
        <div class="px-8">
          <nav class="flex space-x-8" aria-label="Tabs">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              class="py-4 px-1 border-b-2 font-medium text-sm transition-all duration-200"
              :class="activeTab === tab.id
                ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
            >
              <div class="flex items-center gap-2">
                <component :is="tab.icon" class="w-5 h-5" />
                <span>{{ tab.name }}</span>
                <span v-if="tab.badge" class="ml-2 py-0.5 px-2 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                  {{ tab.badge }}
                </span>
              </div>
            </button>
          </nav>
        </div>
      </div>

      <!-- Form Body avec contenu des onglets -->
      <div class="flex-1 overflow-y-auto custom-scrollbar">
        <form @submit.prevent="handleSubmit" class="p-8">
          
          <!-- Onglet 1: Informations de base -->
          <div v-if="activeTab === 'informations'" class="space-y-8">
            <!-- Section Informations Générales -->
            <div class="space-y-5">
              <div class="flex items-center gap-3 pb-3 border-b-2 border-gray-200 dark:border-gray-700">
                <div class="p-2 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg">
                  <InformationCircleIcon class="w-5 h-5 text-white" />
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Informations générales</h3>
              </div>

              <!-- Activité Context Card -->
              <div v-if="activiteContext"
                class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border-2 border-blue-200 dark:border-blue-800">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-lg bg-blue-600 flex items-center justify-center">
                    <FolderIcon class="w-5 h-5 text-white" />
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

            <!-- Section Planification -->
            <div class="space-y-5">
              <div class="flex items-center gap-3 pb-3 border-b-2 border-gray-200 dark:border-gray-700">
                <div class="p-2 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg">
                  <CalendarIcon class="w-5 h-5 text-white" />
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

              <!-- Dates -->
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
                      :format="'dd-MM-yyyy'"
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
                      :format="'dd-MM-yyyy'"
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

            <!-- Section Commentaire -->
            <div class="space-y-5">
              <div class="flex items-center gap-3 pb-3 border-b-2 border-gray-200 dark:border-gray-700">
                <div class="p-2 bg-gradient-to-br from-amber-500 to-yellow-600 rounded-lg">
                  <ChatBubbleLeftRightIcon class="w-5 h-5 text-white" />
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Commentaire</h3>
              </div>

              <textarea v-model="formData.commentaire" rows="3" placeholder="Ajoutez un commentaire ou une note..."
                class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none"></textarea>
            </div>
          </div>

          <!-- Onglet 2: Équipe & Validation -->
          <div v-if="activeTab === 'equipe'" class="space-y-8">
            <!-- Section Équipe & Organisation -->
            <div class="space-y-5">
              <div class="flex items-center gap-3 pb-3 border-b-2 border-gray-200 dark:border-gray-700">
                <div class="p-2 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg">
                  <UserGroupIcon class="w-5 h-5 text-white" />
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
                  <InformationCircleIcon class="w-4 h-4" />
                  Maintenez Ctrl/Cmd pour sélectionner plusieurs utilisateurs
                </p>
              </div>

              <!-- Labels -->
              <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">Labels</label>
                <TaskLabelsSelector
                  v-model="formData.label_ids"
                  :projet-id="currentActivite?.projet_id"
                  :show-create-button="true"
                  :show-scope-filter="true"
                  @create-label="showLabelModal = true"
                />
              </div>
            </div>

            <!-- Section Validation -->
            <div class="space-y-5">
              <div class="flex items-center gap-3 pb-3 border-b-2 border-gray-200 dark:border-gray-700">
                <div class="p-2 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg">
                  <CheckBadgeIcon class="w-5 h-5 text-white" />
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

              <!-- Validateurs assignés (si besoin) -->
              <div v-if="formData.validation_n1_required || formData.validation_n2_required" class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                <p class="text-sm text-blue-700 dark:text-blue-300">
                  <InformationCircleIcon class="w-4 h-4 inline mr-2" />
                  Les validateurs seront automatiquement assignés en fonction des rôles dans l'activité et le projet.
                </p>
              </div>
            </div>

            <!-- Section Visibilité -->
            <div class="space-y-5">
              <div class="flex items-center gap-3 pb-3 border-b-2 border-gray-200 dark:border-gray-700">
                <div class="p-2 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg">
                  <EyeIcon class="w-5 h-5 text-white" />
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
          </div>

          <!-- Onglet 3: Fichiers & Ressources -->
          <div v-if="activeTab === 'fichiers'" class="space-y-8">
            <!-- Section Fichiers attachés -->
            <div class="space-y-5">
              <div class="flex items-center gap-3 pb-3 border-b-2 border-gray-200 dark:border-gray-700">
                <div class="p-2 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-lg">
                  <PaperClipIcon class="w-5 h-5 text-white" />
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Fichiers & Ressources</h3>
              </div>

              <!-- Upload de fichiers -->
              <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                  Fichiers attachés
                </label>
                
                <!-- Zone de dépôt de fichiers -->
                <div 
                  @drop.prevent="handleFileDrop"
                  @dragover.prevent="isDragOver = true"
                  @dragleave="isDragOver = false"
                  class="border-2 border-dashed rounded-xl p-8 text-center transition-all duration-200"
                  :class="isDragOver 
                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' 
                    : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'"
                >
                  <PaperClipIcon class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                  <p class="text-lg font-semibold text-gray-600 dark:text-gray-400 mb-2">
                    Glissez-déposez vos fichiers ici
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-500 mb-4">
                    ou
                  </p>
                  <input
                    type="file"
                    ref="fileInput"
                    multiple
                    @change="handleFileUpload"
                    class="hidden"
                  />
                  <button
                    type="button"
                    @click="$refs.fileInput.click()"
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
                  >
                    Parcourir les fichiers
                  </button>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">
                    Formats supportés: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG, GIF, ZIP
                  </p>
                  <p class="text-xs text-gray-400 dark:text-gray-500">
                    Taille maximale: 10 Mo par fichier
                  </p>
                </div>
              </div>

              <!-- Liste des fichiers uploadés -->
              <div v-if="uploadedFiles.length > 0" class="space-y-3">
                <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300">
                  Fichiers sélectionnés ({{ uploadedFiles.length }})
                </h4>
                <div class="space-y-2">
                  <div
                    v-for="(file, index) in uploadedFiles"
                    :key="index"
                    class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
                  >
                    <div class="flex items-center gap-3">
                      <DocumentIcon class="w-5 h-5 text-gray-400" />
                      <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ file.name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                          {{ formatFileSize(file.size) }}
                        </p>
                      </div>
                    </div>
                    <button
                      type="button"
                      @click="removeFile(index)"
                      class="p-1 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                    >
                      <TrashIcon class="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>

              <!-- Liens externes -->
              <div class="space-y-4">
                <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300">
                  Liens externes
                </h4>
                
                <!-- Ajout de lien -->
                <div class="flex gap-3">
                  <input
                    v-model="newLink.url"
                    type="url"
                    placeholder="https://example.com"
                    class="flex-1 px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                  />
                  <input
                    v-model="newLink.title"
                    type="text"
                    placeholder="Titre du lien"
                    class="flex-1 px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                  />
                  <button
                    type="button"
                    @click="addLink"
                    class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium"
                  >
                    Ajouter
                  </button>
                </div>

                <!-- Liste des liens -->
                <div v-if="externalLinks.length > 0" class="space-y-2">
                  <div
                    v-for="(link, index) in externalLinks"
                    :key="index"
                    class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
                  >
                    <div class="flex items-center gap-3">
                      <LinkIcon class="w-5 h-5 text-blue-500" />
                      <div>
                        <a :href="link.url" target="_blank" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
                          {{ link.title || link.url }}
                        </a>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ link.url }}</p>
                      </div>
                    </div>
                    <button
                      type="button"
                      @click="removeLink(index)"
                      class="p-1 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                    >
                      <TrashIcon class="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Section Apparence --> 
            <div class="space-y-5">
              <div class="flex items-center gap-3 pb-3 border-b-2 border-gray-200 dark:border-gray-700">
                <div class="p-2 bg-gradient-to-br from-pink-500 to-rose-600 rounded-lg">
                  <PaintBrushIcon class="w-5 h-5 text-white" />
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Apparence</h3>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <!-- Couleur -->
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Couleur</label>
                  <div class="flex gap-3 items-center">
                    <input
                      v-model="formData.couleur"
                      type="color"
                      class="h-12 w-20 border-2 border-gray-300 dark:border-gray-700 rounded-lg cursor-pointer"
                    />
                    <input
                      v-model="formData.couleur"
                      type="text"
                      placeholder="#3B82F6"
                      pattern="^#[0-9A-Fa-f]{6}$"
                      class="flex-1 px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                    />
                  </div>
                </div>

                <!-- Image de couverture - CORRIGÉE -->
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Image de couverture</label>
                  <input
                    ref="coverImageInput"
                    type="file"
                    accept="image/*"
                    @change="handleCoverImageUpload"
                    class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                  />
                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PNG, JPG, GIF jusqu'à 2 Mo</p>
                </div>
              </div>

              <!-- Preview Image -->
              <div v-if="coverImagePreview" class="mt-3">
                <img :src="coverImagePreview" alt="Preview" class="h-40 w-full rounded-xl object-cover shadow-lg" />
              </div>
            </div>
          </div>

        </form>
      </div>

      <!-- Footer -->
      <div class="px-8 py-5 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <!-- Navigation entre les onglets -->
          <div class="flex gap-2">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              class="p-2 rounded-lg transition-colors"
              :class="activeTab === tab.id
                ? 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-400'
                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
              :title="tab.name"
            >
              <component :is="tab.icon" class="w-4 h-4" />
            </button>
          </div>

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

    <!-- Label Modal -->
    <LabelModal
      v-if="showLabelModal"
      :projet-id="currentActivite?.projet_id"
      @saved="handleLabelCreated"
      @close="showLabelModal = false"
    />

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
import { 
  CalendarIcon,
  InformationCircleIcon,
  UserGroupIcon,
  CheckBadgeIcon,
  EyeIcon,
  ChatBubbleLeftRightIcon,
  PaperClipIcon,
  DocumentIcon,
  LinkIcon,
  TrashIcon,
  PaintBrushIcon,
  FolderIcon
} from '@heroicons/vue/24/outline'

import { TaskLabelsSelector, LabelModal } from '@/components/labels'
import { useLabels } from '@/composables/useLabels'

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
const { labels, fetchLabels, fetchLabelsForProject } = useLabels()

// ==================== ÉTAT RÉACTIF ====================
const currentActivite = ref(props.activiteContext)
const loading = ref(false)
const activites = ref([])
const errorMessage = ref('')
const validationErrors = ref([])
const isCheckingPermissions = ref(true)
const permissionChecked = ref(false)

// Variables pour les onglets
const activeTab = ref('informations')

// Variables pour les fichiers et liens
const isDragOver = ref(false)
const uploadedFiles = ref([])
const externalLinks = ref([])
const newLink = ref({ url: '', title: '' })

// Variables labels et apparence
const showLabelModal = ref(false)
const coverImagePreview = ref(null)
const coverImageFile = ref(null) // ✅ NOUVEAU : Référence séparée pour le fichier

// Refs pour les inputs
const fileInputRef = ref(null)
const coverImageInputRef = ref(null)

// ==================== CONFIGURATION DES ONGLETS ====================
const tabs = [
  { id: 'informations', name: 'Informations', icon: InformationCircleIcon },
  { id: 'equipe', name: 'Équipe & Validation', icon: UserGroupIcon },
  { 
    id: 'fichiers', 
    name: 'Fichiers & Ressources', 
    icon: PaperClipIcon,
    badge: computed(() => {
      const total = uploadedFiles.value.length + externalLinks.value.length
      return total > 0 ? total : null
    })
  }
]

// ==================== PERMISSIONS ====================
const {
  canCreateTasks,
  canEditTasks,
  canAssignUsers,
  getPermissionDeniedMessage
} = useActivityPermissions(currentActivite)

const hasPermission = computed(() => {
  if (!currentActivite.value) {
    return false
  }
  return props.tache ? canEditTasks.value : canCreateTasks.value
})

// ==================== UTILISATEURS DISPONIBLES ====================
const availableUsers = computed(() => {
  if (!Array.isArray(availableMembers.value)) {
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
  echeance: null,
  date_debut: null,
  date_fin_reelle: null,
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

// ==================== MÉTHODES FICHIERS & LIENS ====================

/**
 * ✅ CORRIGÉ : Gérer le drag & drop de fichiers
 */
const handleFileDrop = (event) => {
  isDragOver.value = false
  const files = Array.from(event.dataTransfer.files)
  handleFiles(files)
}

/**
 * ✅ CORRIGÉ : Gérer l'upload de fichiers via input
 */
const handleFileUpload = (event) => {
  const files = Array.from(event.target.files)
  handleFiles(files)
  event.target.value = '' // Reset l'input
}

/**
 * ✅ CORRIGÉ : Traiter les fichiers uploadés avec validation stricte
 */
const handleFiles = (files) => {
  const allowedTypes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/gif',
    'application/zip',
    'application/x-zip-compressed'
  ]
  
  const validFiles = []
  const errors = []
  
  files.forEach(file => {
    // Vérifier la taille (10 Mo max)
    if (file.size > 10 * 1024 * 1024) {
      errors.push(`Le fichier "${file.name}" dépasse la taille maximale de 10 Mo`)
      return
    }
    
    // Vérifier le type MIME
    if (!allowedTypes.includes(file.type)) {
      errors.push(`Le format du fichier "${file.name}" (${file.type}) n'est pas supporté`)
      return
    }
    
    // Vérifier que le fichier n'est pas déjà ajouté
    const exists = uploadedFiles.value.some(f => 
      f.name === file.name && f.size === file.size
    )
    
    if (exists) {
      errors.push(`Le fichier "${file.name}" est déjà ajouté`)
      return
    }
    
    validFiles.push(file)
  })
  
  if (errors.length > 0) {
    errorMessage.value = errors.join('\n')
    setTimeout(() => {
      errorMessage.value = ''
    }, 5000)
  }
  
  if (validFiles.length > 0) {
    uploadedFiles.value.push(...validFiles)
    console.log('✅ Fichiers ajoutés:', validFiles.length, 'Total:', uploadedFiles.value.length, 'uploaded files', uploadedFiles.value, 'validFiles', validFiles)
  }
}

/**
 * Supprimer un fichier
 */
const removeFile = (index) => {
  uploadedFiles.value.splice(index, 1)
}

/**
 * Formater la taille du fichier
 */
const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

/**
 * ✅ CORRIGÉ : Ajouter un lien externe avec validation
 */
const addLink = () => {
  if (!newLink.value.url || !newLink.value.url.trim()) {
    errorMessage.value = 'Veuillez saisir une URL'
    setTimeout(() => { errorMessage.value = '' }, 3000)
    return
  }
  
  // Validation URL
  try {
    const url = new URL(newLink.value.url)
    if (!['http:', 'https:'].includes(url.protocol)) {
      throw new Error('Protocol invalide')
    }
  } catch {
    errorMessage.value = 'Veuillez saisir une URL valide (doit commencer par http:// ou https://)'
    setTimeout(() => { errorMessage.value = '' }, 3000)
    return
  }
  
  // Vérifier doublon
  const exists = externalLinks.value.some(link => link.url === newLink.value.url)
  if (exists) {
    errorMessage.value = 'Ce lien a déjà été ajouté'
    setTimeout(() => { errorMessage.value = '' }, 3000)
    return
  }
  
  externalLinks.value.push({
    url: newLink.value.url.trim(),
    title: newLink.value.title.trim() || newLink.value.url.trim()
  })
  
  // Réinitialiser
  newLink.value = { url: '', title: '' }
  
  console.log('✅ Lien ajouté. Total:', externalLinks.value.length)
}

/**
 * Supprimer un lien
 */
const removeLink = (index) => {
  externalLinks.value.splice(index, 1)
}

/**
 * ✅ CORRIGÉ : Gérer l'upload d'image de couverture
 */
const handleCoverImageUpload = (event) => {
  const file = event.target.files?.[0]
  
  if (!file) {
    return
  }
  
  // Réinitialiser les erreurs
  errorMessage.value = ''
  
  // Vérifier la taille (2 Mo max)
  if (file.size > 2 * 1024 * 1024) {
    errorMessage.value = 'L\'image ne doit pas dépasser 2 Mo'
    event.target.value = ''
    return
  }
  
  // Vérifier le type MIME strictement
  const validImageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif']
  if (!validImageTypes.includes(file.type)) {
    errorMessage.value = `Format d'image non supporté (${file.type}). Formats acceptés: JPEG, PNG, GIF`
    event.target.value = ''
    return
  }
  
  // Stocker le fichier
  coverImageFile.value = file
  
  // Générer preview
  const reader = new FileReader()
  reader.onload = (e) => {
    coverImagePreview.value = e.target.result
  }
  reader.onerror = () => {
    errorMessage.value = 'Erreur lors de la lecture du fichier'
    coverImageFile.value = null
  }
  reader.readAsDataURL(file)
  
  console.log('✅ Image de couverture sélectionnée:', {
    name: file.name,
    type: file.type,
    size: formatFileSize(file.size)
  })
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
 * ✅ CORRIGÉ : Formater une date pour l'API (YYYY-MM-DD)
 */
const formatDateForApi = (date) => {
  if (!date) return null
  
  // Si c'est déjà une string au bon format
  if (typeof date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(date)) {
    return date
  }
  
  // Si c'est un objet Date
  let dateObj
  if (date instanceof Date) {
    dateObj = date
  } else {
    dateObj = new Date(date)
  }
  
  if (isNaN(dateObj.getTime())) {
    return null
  }
  
  const year = dateObj.getFullYear()
  const month = String(dateObj.getMonth() + 1).padStart(2, '0')
  const day = String(dateObj.getDate()).padStart(2, '0')
  
  return `${year}-${month}-${day}`
}

/**
 * ✅ CORRIGÉ : Soumettre le formulaire avec gestion optimale des fichiers
 */
const handleSubmit = async () => {
  if (!hasPermission.value) {
    const action = props.tache ? 'edit_tasks' : 'create_tasks'
    errorMessage.value = getPermissionDeniedMessage(action)
    return
  }

  loading.value = true
  errorMessage.value = ''
  validationErrors.value = []

  try {
    if (currentActivite.value) {
      formData.value.activite_id = currentActivite.value.id
    }

    // ✅ TOUJOURS utiliser FormData pour éviter les problèmes
    const formDataObj = new FormData()
    
    console.log('=== FormData Debug ===')
for (let [key, value] of formDataObj.entries()) {
  if (value instanceof File) {
    console.log(`${key}: [FILE] ${value.name} (${value.type})`)
  } else {
    console.log(`${key}:`, value)
  }
}

    // ✅ Ajouter les champs simples
    formDataObj.append('activite_id', formData.value.activite_id)
    formDataObj.append('titre', formData.value.titre || '')
    formDataObj.append('description', formData.value.description || '')
    formDataObj.append('objectif', formData.value.objectif || '')
    formDataObj.append('indicateurs_resultats', formData.value.indicateurs_resultats || '')
    formDataObj.append('statut', formData.value.statut)
    formDataObj.append('priorite', formData.value.priorite)
    
    // ✅ Dates formatées
    const dateDebut = formatDateForApi(formData.value.date_debut)
    const echeance = formatDateForApi(formData.value.echeance)
    const dateFinReelle = formatDateForApi(formData.value.date_fin_reelle)
    
    if (dateDebut) formDataObj.append('date_debut', dateDebut)
    if (echeance) formDataObj.append('echeance', echeance)
    if (dateFinReelle) formDataObj.append('date_fin_reelle', dateFinReelle)
    
    formDataObj.append('taux_realisation', formData.value.taux_realisation || 0)
    
    if (formData.value.estimated_hours) {
      formDataObj.append('estimated_hours', formData.value.estimated_hours)
    }
    if (formData.value.actual_hours) {
      formDataObj.append('actual_hours', formData.value.actual_hours)
    }
    
    // ✅ Booléens en 0/1
    formDataObj.append('validation_n1_required', formData.value.validation_n1_required ? '1' : '0')
    formDataObj.append('validation_n2_required', formData.value.validation_n2_required ? '1' : '0')
    
    formDataObj.append('couleur', formData.value.couleur || '#3B82F6')
    formDataObj.append('commentaire', formData.value.commentaire || '')
    formDataObj.append('visibility', formData.value.visibility || 'members_only')
    
    // ✅ Tableaux d'IDs
    if (formData.value.assignee_ids && formData.value.assignee_ids.length > 0) {
      formData.value.assignee_ids.forEach(id => {
        formDataObj.append('assignee_ids[]', id)
      })
    }
    
    if (formData.value.label_ids && formData.value.label_ids.length > 0) {
      formData.value.label_ids.forEach(id => {
        formDataObj.append('label_ids[]', id)
      })
    }
    
    // ✅ FICHIERS : Ajouter chaque fichier individuellement
    if (uploadedFiles.value.length > 0) {
      uploadedFiles.value.forEach((file, index) => {
        formDataObj.append(`uploaded_files[${index}]`, file, file.name)
      })
      console.log('✅ Fichiers ajoutés au FormData:', uploadedFiles.value.length)
    }
    
    // ✅ LIENS EXTERNES : Sérialiser proprement en JSON
    if (externalLinks.value.length > 0) {
      formDataObj.append('external_links', JSON.stringify(externalLinks.value))
      console.log('✅ Liens externes:', externalLinks.value.length)
    }
    
    // ✅ IMAGE DE COUVERTURE
    if (coverImageFile.value) {
      formDataObj.append('cover_image', coverImageFile.value, coverImageFile.value.name)
      console.log('✅ Image de couverture ajoutée:', coverImageFile.value.name)
    }
    
    // ✅ Debug: Afficher le contenu du FormData
    console.log('=== FormData Content ===')
    for (let [key, value] of formDataObj.entries()) {
      if (value instanceof File) {
        console.log(`${key}:`, {
          name: value.name,
          type: value.type,
          size: value.size
        })
      } else {
        console.log(`${key}:`, value)
      }
    }
    
    // ✅ Envoyer
    if (props.tache) {
      formDataObj.append('_method', 'PUT')
      await updateTache(props.tache.id, formDataObj)
    } else {
      await createTache(formDataObj)
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

    // Charger les membres
    await loadActivityMembers(activiteId).catch(err => {
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
 * Gérer la création d'un nouveau label
 */
const handleLabelCreated = async () => {
  showLabelModal.value = false
  try {
    if (currentActivite.value?.projet_id) {
      await fetchLabelsForProject(currentActivite.value.projet_id)
    } else {
      await fetchLabels()
    }
  } catch (error) {
    console.error('❌ Erreur rechargement labels:', error)
  }
}

/**
 * Charger toutes les données nécessaires
 */
const loadData = async () => {
  try {
    const [activitesRes] = await Promise.all([
      api.get('/activites/mes-activites')
    ])
    activites.value = activitesRes.data.data || []
    
    if (currentActivite.value?.projet_id) {
      await fetchLabelsForProject(currentActivite.value.projet_id)
    } else {
      await fetchLabels()
    }
  } catch (error) {
    console.error('❌ Erreur chargement données:', error)
  }
}

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
        echeance: props.tache.echeance || null,
        date_debut: props.tache.date_debut || null,
        date_fin_reelle: props.tache.date_fin_reelle || null,
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

      if (props.tache.external_links) {
        externalLinks.value = props.tache.external_links
      }

      if (props.tache.cover_image) {
        coverImagePreview.value = props.tache.cover_image
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

/* Styles spécifiques au DatePicker */
.date-input {
  position: relative;
  z-index: 1;
}

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

.date-input :deep(.dp__menu) {
  z-index: 10000;
}

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