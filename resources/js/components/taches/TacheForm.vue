<!-- resources/js/components/taches/TacheForm.vue - VERSION AVEC ONGLETS -->
<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 sm:p-6 lg:p-20"
    @click.self="$emit('close')">
    <div
      class="bg-white dark:bg-gray-800 rounded-3 w-full max-w-3xl max-h-[95vh] overflow-hidden flex flex-col animate-fade-in">
      <!-- Header -->
      <div
        class="px-8 py-6 border-b border-gray-200 dark:border-gray-700 text-white">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div
              class="w-14 h-14 rounded-3 bg-white/20 flex items-center justify-center ring-2 ring-white/30">
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
            class="text-white/80 hover:text-white transition-colors p-2 hover:bg-white/10 rounded-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Permission Check - Loading State -->
      <div v-if="isCheckingPermissions"
        class="mx-8 mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-3 border-l-4 border-blue-500">
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
            <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
              :dusk="`tache-form-tab-${tab.id}`"
              class="py-4 px-1 border-b-2 font-medium text-sm transition-all duration-200"
              :class="activeTab === tab.id
                ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'">
              <div class="flex items-center gap-2">
                <component :is="tab.icon" class="w-5 h-5" />
                <span>{{ tab.name }}</span>
                <span v-if="tab.badge"
                  class="ml-2 py-0.5 px-2 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
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
                <div class="p-2 rounded-3">
                  <InformationCircleIcon class="w-5 h-5 text-white" />
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Informations générales</h3>
              </div>

              <!-- Activité Context Card -->
              <div v-if="activiteContext"
                class="p-4 rounded-3 border-2 border-blue-200 dark:border-blue-800">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-3 bg-blue-600 flex items-center justify-center">
                    <FolderIcon class="w-5 h-5 text-white" />
                  </div>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-blue-700 dark:text-blue-300">Activité sélectionnée</p>
                    <p class="text-lg font-bold text-blue-900 dark:text-blue-100">{{ activiteContext.nom }}</p>
                  </div>
                  <div class="text-right">
                    <p class="text-xs text-blue-600 dark:text-blue-400">Projet</p>
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">{{ activiteContext.projet?.nom }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Activité Selector -->
              <div v-else>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                  <span class="text-red-500">*</span> Activité
                </label>
                <select v-model="formData.activite_id" required
                  class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
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
                  dusk="tache-form-titre"
                  placeholder="Ex: Implémenter l'authentification utilisateur"
                  class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" />
              </div>

              <!-- Description -->
              <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Description</label>
                <textarea v-model="formData.description" rows="4" placeholder="Décrivez la tâche en détail..."
                  class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none"></textarea>
              </div>

              <!-- Objectif & Indicateurs -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Objectif</label>
                  <textarea v-model="formData.objectif" rows="3" placeholder="Quel est l'objectif de cette tâche ?"
                    class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none"></textarea>
                </div>
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Indicateurs de
                    résultats</label>
                  <textarea v-model="formData.indicateurs_resultats" rows="3" placeholder="Comment mesurer le succès ?"
                    class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none"></textarea>
                </div>
              </div>
            </div>

            <!-- Section Planification -->
            <div class="space-y-5">
              <div class="flex items-center gap-3 pb-3 border-b-2 border-gray-200 dark:border-gray-700">
                <div class="p-2 rounded-3">
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
                      class="relative flex items-center gap-3 p-3.5 border-2 rounded-3 cursor-pointer transition-all"
                      :class="formData.statut === statut.value
                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 '
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
                      class="relative flex items-center gap-3 p-3.5 border-2 rounded-3 cursor-pointer transition-all"
                      :class="formData.priorite === priorite.value
                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 '
                        : 'border-gray-300 dark:border-gray-600 hover:border-blue-300'">
                      <input type="radio" v-model="formData.priorite" :value="priorite.value" class="sr-only" />
                      <span class="text-xl">{{ priorite.icon }}</span>
                      <span class="flex-1 text-sm font-semibold text-gray-900 dark:text-white">{{ priorite.label
                      }}</span>
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
                    <DatePicker v-model="formData.date_debut" :enable-time-picker="false" auto-apply
                      :format="'dd-MM-yyyy'" :locale="'fr'" :dark="isDark" placeholder="Sélectionner une date"
                      class="w-full date-input">
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
                    <DatePicker v-model="formData.echeance" :enable-time-picker="false" auto-apply
                      :format="'dd-MM-yyyy'" :locale="'fr'" :dark="isDark" :min-date="formData.date_debut"
                      placeholder="Sélectionner une date" class="w-full date-input">
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
                    <DatePicker v-model="formData.date_fin_reelle" :enable-time-picker="false" auto-apply
                      :format="'yyyy-MM-dd'" :locale="'fr'" :dark="isDark" placeholder="Sélectionner une date"
                      class="w-full date-input">
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
                    class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" />
                  <div class="mt-3 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                    <div class="h-2.5 rounded-full transition-all duration-500"
                      :class="getProgressColorClass(formData.taux_realisation)"
                      :style="{ width: `${formData.taux_realisation}%` }">
                    </div>
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Heures estimées</label>
                  <input v-model.number="formData.estimated_hours" type="number" min="0" step="0.5" placeholder="0"
                    class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" />
                </div>
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Heures réelles</label>
                  <input v-model.number="formData.actual_hours" type="number" min="0" step="0.5" placeholder="0"
                    class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" />
                </div>
              </div>
            </div>

            <!-- Section Commentaire -->
            <div class="space-y-5">
              <div class="flex items-center gap-3 pb-3 border-b-2 border-gray-200 dark:border-gray-700">
                <div class="p-2 rounded-3">
                  <ChatBubbleLeftRightIcon class="w-5 h-5 text-white" />
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Commentaire</h3>
              </div>

              <textarea v-model="formData.commentaire" rows="3" placeholder="Ajoutez un commentaire ou une note..."
                class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none"></textarea>
            </div>
          </div>

         <!-- Onglet 2: Équipe & Validation -->
  <div v-if="activeTab === 'equipe'" class="space-y-8">
    <!-- Section Équipe & Organisation -->
    <div class="space-y-5">
      <div class="flex items-center gap-3 pb-3 border-b-2 border-gray-200 dark:border-gray-700">
        <div class="p-2 rounded-3">
          <UserGroupIcon class="w-5 h-5 text-white" />
        </div>
        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Équipe & Organisation</h3>
      </div>

      <!-- ✅ ORDRE MODIFIÉ : Responsable EN PREMIER -->
      <!-- Responsable de la tâche -->
      <div>
        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
          <UserIcon class="w-4 h-4 text-purple-500" />
          <span class="text-red-500">*</span>
          Responsable de la tâche
        </label>
        
        <div v-if="loadingMembers" class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-3">
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

        <select
          v-else
          v-model="formData.responsable_id"
          required
          dusk="tache-form-responsable"
          :disabled="availableUsers.length === 0"
          class="w-full px-4 py-3.5 border-2 rounded-3 transition-all"
          :class="availableUsers.length === 0
            ? 'border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 opacity-60 cursor-not-allowed'
            : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 hover:border-purple-400 focus:ring-2 focus:ring-purple-500 focus:border-purple-500'"
        >
          <option :value="null">-- Sélectionner un responsable --</option>
          <option v-for="user in availableUsers" :key="user.id" :value="user.id">
            {{ user.nom }} ({{ user.email }})
          </option>
        </select>

        <!-- ✅ Message informatif -->
        <!-- <div class="mt-3 p-3 bg-purple-50 dark:bg-purple-900/20 rounded-3 border border-purple-200 dark:border-purple-800">
          <div class="flex items-start gap-2">
            <InformationCircleIcon class="w-5 h-5 text-purple-600 dark:text-purple-400 flex-shrink-0 mt-0.5" />
            <div class="text-sm text-purple-700 dark:text-purple-300">
              <p class="font-semibold mb-1">À propos du responsable :</p>
              <ul class="space-y-1 list-disc list-inside">
                <li>Le responsable pilote l'exécution de la tâche</li>
                <li>Il sera automatiquement ajouté aux intervenants</li>
                <li>Il aura tous les droits sur la tâche</li>
              </ul>
            </div>
          </div>
        </div> -->

        <!-- Affichage du responsable actuel si tâche existante -->
        <div v-if="tache && tache.responsable && formData.responsable_id === tache.responsable.id"
          class="mt-3 p-3 rounded-3 border-2 border-purple-200 dark:border-purple-800">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full overflow-hidden bg-purple-100 dark:bg-purple-900 ring-2 ring-purple-500">
              <img v-if="tache.responsable.avatar" :src="tache.responsable.avatar" :alt="tache.responsable.nom" 
                class="w-full h-full object-cover" />
              <div v-else class="w-full h-full flex items-center justify-center text-purple-600 dark:text-purple-400 font-bold">
                {{ tache.responsable.nom.charAt(0).toUpperCase() }}
              </div>
            </div>
            <div class="flex-1">
              <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ tache.responsable.nom }}</p>
              <p class="text-xs text-gray-600 dark:text-gray-400">{{ tache.responsable.email }}</p>
            </div>
            <div class="px-3 py-1 bg-purple-600 rounded-full">
              <span class="text-xs font-semibold text-white flex items-center gap-1">
                <CheckBadgeIcon class="w-3 h-3" />
                Responsable
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- SÉPARATEUR VISUEL -->
      <div class="relative">
        <div class="absolute inset-0 flex items-center">
          <div class="w-full border-t-2 border-gray-200 dark:border-gray-700"></div>
        </div>
        <div class="relative flex justify-center">
          <span class="px-3 bg-white dark:bg-gray-800 text-sm font-semibold text-gray-500 dark:text-gray-400">
            Intervenants additionnels
          </span>
        </div>
      </div>

      <!-- Assignation (Intervenants) -->
      <div>
        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
          <UserGroupIcon class="w-4 h-4 text-blue-500" />
          Autres intervenants
        </label>

        <div v-if="loadingMembers" class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-3">
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

        <div v-else-if="availableUsers.length === 0"
          class="p-3 bg-gray-50 dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700">
          <p class="text-sm text-gray-600 dark:text-gray-400 text-center">Aucun membre disponible</p>
        </div>

        <div v-else class="space-y-2">
          <!-- Tags des intervenants sélectionnés -->
          <div v-if="selectedAssignees.length > 0" class="flex flex-wrap gap-2 p-3 bg-blue-50 dark:bg-blue-900/10 rounded-3 border border-blue-200 dark:border-blue-800">
            <span
              v-for="user in selectedAssignees"
              :key="user.id"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium"
              :class="user.id === formData.responsable_id
                ? 'bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-200 ring-1 ring-purple-400'
                : 'bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 ring-1 ring-gray-300 dark:ring-gray-600'"
            >
              <span
                class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                :style="{ backgroundColor: stringToColor(user.nom) }"
              >{{ user.nom.charAt(0).toUpperCase() }}</span>
              {{ user.nom }}
              <CheckBadgeIcon v-if="user.id === formData.responsable_id" class="w-3.5 h-3.5 text-purple-500" />
              <button
                v-else
                type="button"
                @click="toggleAssignee(user.id)"
                class="ml-0.5 hover:text-red-500 transition-colors"
                :title="`Retirer ${user.nom}`"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </span>
          </div>

          <!-- Champ de recherche -->
          <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              v-model="assigneeSearch"
              type="text"
              placeholder="Rechercher un membre..."
              :disabled="!canAssignUsers"
              class="w-full pl-9 pr-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-sm"
            />
          </div>

          <!-- Liste avec cases à cocher -->
          <div class="max-h-52 overflow-y-auto border-2 border-gray-200 dark:border-gray-700 rounded-3 divide-y divide-gray-100 dark:divide-gray-700">
            <label
              v-for="user in filteredAssigneeUsers"
              :key="user.id"
              class="flex items-center gap-3 px-4 py-2.5 cursor-pointer transition-colors"
              :class="[
                user.id === formData.responsable_id
                  ? 'bg-purple-50 dark:bg-purple-900/20 cursor-default'
                  : 'hover:bg-gray-50 dark:hover:bg-gray-800',
                formData.assignee_ids.includes(user.id) && user.id !== formData.responsable_id
                  ? 'bg-blue-50 dark:bg-blue-900/10'
                  : ''
              ]"
            >
              <input
                type="checkbox"
                :checked="formData.assignee_ids.includes(user.id)"
                :disabled="user.id === formData.responsable_id || !canAssignUsers"
                @change="toggleAssignee(user.id)"
                class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-blue-600 focus:ring-blue-500 flex-shrink-0"
              />
              <span
                class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                :style="{ backgroundColor: stringToColor(user.nom) }"
              >{{ user.nom.charAt(0).toUpperCase() }}</span>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ user.nom }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ user.email }}</p>
              </div>
              <span v-if="user.id === formData.responsable_id"
                class="text-xs px-2 py-0.5 bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 rounded-full font-medium flex-shrink-0">
                Responsable
              </span>
            </label>
            <div v-if="filteredAssigneeUsers.length === 0" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
              Aucun résultat pour "{{ assigneeSearch }}"
            </div>
          </div>

          <div v-if="formData.responsable_id" class="p-2 bg-purple-50 dark:bg-purple-900/20 rounded-3 border border-purple-200 dark:border-purple-800">
            <p class="text-xs text-purple-700 dark:text-purple-300 flex items-center gap-1">
              <CheckBadgeIcon class="w-4 h-4" />
              Le responsable est automatiquement inclus dans les intervenants
            </p>
          </div>
        </div>
      </div>

              <!-- Labels -->
              <!-- <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">Labels</label>
                <TaskLabelsSelector v-model="formData.label_ids" :projet-id="currentActivite?.projet_id"
                  :show-create-button="true" :show-scope-filter="true" @create-label="showLabelModal = true" />
              </div> -->
            </div>

            <!-- Section Validation -->
            <!-- <div class="space-y-5">
              <div class="flex items-center gap-3 pb-3 border-b-2 border-gray-200 dark:border-gray-700">
                <div class="p-2 rounded-3">
                  <CheckBadgeIcon class="w-5 h-5 text-white" />
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Validation</h3>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="relative flex items-start gap-3 p-4 border-2 rounded-3 cursor-pointer transition-all"
                  :class="formData.validation_n1_required ? 'border-green-500 bg-green-50 dark:bg-green-900/20'
                    : 'border-gray-300 dark:border-gray-600 hover:border-gray-400'">
                  <input v-model="formData.validation_n1_required" type="checkbox"
                    class="mt-1 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-green-600 focus:ring-green-500" />
                  <div class="flex-1">
                    <span class="block text-sm font-bold text-gray-900 dark:text-white">Validation N1 requise</span>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Par le responsable d'activité</p>
                  </div>
                </label>

                <label class="relative flex items-start gap-3 p-4 border-2 rounded-3 cursor-pointer transition-all"
                  :class="formData.validation_n2_required ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                    : 'border-gray-300 dark:border-gray-600 hover:border-gray-400'">
                  <input v-model="formData.validation_n2_required" type="checkbox"
                    class="mt-1 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-purple-600 focus:ring-purple-500" />
                  <div class="flex-1">
                    <span class="block text-sm font-bold text-gray-900 dark:text-white">Validation N2 requise</span>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Par le responsable de projet</p>
                  </div>
                </label>
              </div>
 
              <div v-if="formData.validation_n1_required || formData.validation_n2_required"
                class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-3">
                <p class="text-sm text-blue-700 dark:text-blue-300">
                  <InformationCircleIcon class="w-4 h-4 inline mr-2" />
                  Les validateurs seront automatiquement assignés en fonction des rôles dans l'activité et le projet.
                </p>
              </div>
            </div> -->

          <!-- Onglet 3: Fichiers & Ressources -->
          <div v-if="activeTab === 'fichiers'" class="space-y-8">
            <!-- Section Fichiers attachés -->
            <div class="space-y-5">
              <div class="flex items-center gap-3 pb-3 border-b-2 border-gray-200 dark:border-gray-700">
                <div class="p-2 rounded-3">
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
                <div @drop.prevent="handleFileDrop" @dragover.prevent="isDragOver = true"
                  @dragleave="isDragOver = false"
                  class="border-2 border-dashed rounded-3 p-8 text-center transition-all duration-200" :class="isDragOver
                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                    : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'">
                  <PaperClipIcon class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                  <p class="text-lg font-semibold text-gray-600 dark:text-gray-400 mb-2">
                    Glissez-déposez vos fichiers ici
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-500 mb-4">
                    ou
                  </p>
                  <input type="file" :ref="el => fileInputRef = el" multiple @change="handleFileUpload" class="hidden" />
                  <button type="button" @click="fileInputRef?.click()"
                    class="px-6 py-3 bg-blue-600 text-white rounded-3 hover:bg-blue-700 transition-colors font-medium">
                    Parcourir les fichiers
                  </button>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">
                    Formats supportés: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG, GIF, ZIP, TXT
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
                  <div v-for="(file, index) in uploadedFiles" :key="index"
                    class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                      <DocumentIcon class="w-5 h-5 text-gray-400" />
                      <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ file.name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                          {{ formatFileSize(file.size) }}
                        </p>
                      </div>
                    </div>
                    <button type="button" @click="removeFile(index)"
                      class="p-1 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-3 transition-colors">
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
                  <input v-model="newLink.url" type="url" placeholder="https://example.com"
                    class="flex-1 px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" />
                  <input v-model="newLink.title" type="text" placeholder="Titre du lien"
                    class="flex-1 px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" />
                  <button type="button" @click="addLink"
                    class="px-6 py-3 bg-green-600 text-white rounded-3 hover:bg-green-700 transition-colors font-medium">
                    Ajouter
                  </button>
                </div>

                <!-- Liste des liens -->
                <div v-if="externalLinks.length > 0" class="space-y-2">
                  <div v-for="(link, index) in externalLinks" :key="index"
                    class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                      <LinkIcon class="w-5 h-5 text-blue-500" />
                      <div>
                        <a :href="link.url" target="_blank"
                          class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
                          {{ link.title || link.url }}
                        </a>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ link.url }}</p>
                      </div>
                    </div>
                    <button type="button" @click="removeLink(index)"
                      class="p-1 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-3 transition-colors">
                      <TrashIcon class="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Section Apparence -->
            <div class="space-y-5">
              <div class="flex items-center gap-3 pb-3 border-b-2 border-gray-200 dark:border-gray-700">
                <div class="p-2 rounded-3">
                  <PaintBrushIcon class="w-5 h-5 text-white" />
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Apparence</h3>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <!-- Couleur -->
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Couleur</label>
                  <div class="flex gap-3 items-center">
                    <input v-model="formData.couleur" type="color"
                      class="h-12 w-20 border-2 border-gray-300 dark:border-gray-700 rounded-3 cursor-pointer" />
                    <input v-model="formData.couleur" type="text" placeholder="#3B82F6" pattern="^#[0-9A-Fa-f]{6}$"
                      class="flex-1 px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" />
                  </div>
                </div>

                <!-- Image de couverture - CORRIGÉE -->
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Image de
                    couverture</label>
                  <input :ref="el => coverImageInputRef = el" type="file" accept="image/*" @change="handleCoverImageUpload"
                    class="w-full px-4 py-3.5 border-2 border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all file:mr-4 file:py-2 file:px-4 file:rounded-3 file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PNG, JPG, GIF jusqu'à 2 Mo</p>
                </div>
              </div>

              <!-- Preview Image -->
              <div v-if="coverImagePreview" class="mt-3">
                <img :src="coverImagePreview" alt="Preview" class="h-40 w-full rounded-3 object-cover " />
              </div>
            </div>
          </div>

        </form>
      </div>

      <!-- Footer -->
      <div
        class="px-8 py-5 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <!-- Navigation entre les onglets -->
          <div class="flex gap-1">
            <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
              class="flex items-center gap-1.5 px-3 py-1.5 rounded-3 transition-colors text-xs font-medium"
              :class="activeTab === tab.id
                ? 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-400'
                : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-800'">
              <component :is="tab.icon" class="w-4 h-4 flex-shrink-0" />
              <span>{{ tab.name }}</span>
              <span v-if="tab.badge && tab.badge.value"
                class="ml-0.5 py-0.5 px-1.5 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                {{ tab.badge.value }}
              </span>
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
          <button type="button" @click="$emit('close')" dusk="tache-form-cancel"
            class="px-6 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 font-semibold text-gray-700 dark:text-gray-300 transition-all ">
            Annuler
          </button>
          <button type="button" @click="handleSubmit" :disabled="loading || !hasPermission || isCheckingPermissions"
            dusk="tache-form-submit"
            class="px-6 py-3 rounded-3 font-semibold transition-all flex items-center gap-2 disabled:scale-100"
            :class="loading || !hasPermission || isCheckingPermissions
              ? 'bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed'
              : 'text-white shadow-blue-500/50'">
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
    <LabelModal v-if="showLabelModal" :projet-id="currentActivite?.projet_id" @saved="handleLabelCreated"
      @close="showLabelModal = false" />

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { useAuthStore } from '@/stores/authStore'
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
  FolderIcon,
  UserIcon
} from '@heroicons/vue/24/outline'

import { TaskLabelsSelector, LabelModal } from '@/components/labels'
import { useLabels } from '@/composables/useLabels'
import { useToast } from 'vue-toastification'

const isDark = computed(() => document.documentElement.classList.contains('dark'))

// ✅ Toast pour notifications
const toast = useToast()

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
const coverImageFile = ref(null)

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

const stringToColor = (str) => {
  if (!str) return '#6B7280'
  let hash = 0
  for (let i = 0; i < str.length; i++) {
    hash = str.charCodeAt(i) + ((hash << 5) - hash)
  }
  return `hsl(${hash % 360}, 65%, 50%)`
}

const assigneeSearch = ref('')

const filteredAssigneeUsers = computed(() => {
  const q = assigneeSearch.value.toLowerCase().trim()
  if (!q) return availableUsers.value
  return availableUsers.value.filter(u =>
    u.nom.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
  )
})

const selectedAssignees = computed(() =>
  availableUsers.value.filter(u => formData.value.assignee_ids.includes(u.id))
)

const toggleAssignee = (userId) => {
  if (userId === formData.value.responsable_id) return
  const idx = formData.value.assignee_ids.indexOf(userId)
  if (idx === -1) {
    formData.value.assignee_ids.push(userId)
  } else {
    formData.value.assignee_ids.splice(idx, 1)
  }
}

// ==================== FORMULAIRE ====================
const formData = ref({
  activite_id: props.activiteContext?.id || props.activiteId || '',
  responsable_id: null,
  titre: '',
  description: '',
  objectif: '',
  indicateurs_resultats: '',
  statut: 'a_faire', // ✅ Par défaut à "a_faire"
  priorite: 'moyenne',
  echeance: null,
  date_debut: null,
  date_fin_reelle: null,
  taux_realisation: 0,
  estimated_hours: null,
  actual_hours: null,
  validation_n1_required: true, // ✅ Par défaut cochée
  validation_n2_required: true, // ✅ Par défaut cochée
  couleur: '#3B82F6',
  commentaire: '',
  assignee_ids: [],
  label_ids: [],
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

// ==================== MÉTHODES FICHIERS & LIENS ====================

const handleFileDrop = (event) => {
  isDragOver.value = false
  const files = Array.from(event.dataTransfer.files)
  handleFiles(files)
}

const handleFileUpload = (event) => {
  const files = Array.from(event.target.files)
  handleFiles(files)
  event.target.value = ''
}

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
    'image/png',
    'image/gif',
    'application/zip',
    'application/x-zip-compressed',
    'text/plain',
  ]

  const validFiles = []
  const errors = []

  files.forEach(file => {
    if (file.size > 10 * 1024 * 1024) {
      errors.push(`Le fichier "${file.name}" dépasse la taille maximale de 10 Mo`)
      return
    }

    if (!allowedTypes.includes(file.type)) {
      errors.push(`Le format du fichier "${file.name}" (${file.type}) n'est pas supporté`)
      return
    }

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
    // ✅ Toast pour les erreurs de fichiers
    errors.forEach(error => toast.error(error, { timeout: 5000 }))
  }

  if (validFiles.length > 0) {
    uploadedFiles.value.push(...validFiles)
    toast.success(`${validFiles.length} fichier(s) ajouté(s)`, { timeout: 2000 })
  }
}

const removeFile = (index) => {
  const fileName = uploadedFiles.value[index].name
  uploadedFiles.value.splice(index, 1)
  toast.info(`Fichier "${fileName}" retiré`, { timeout: 2000 })
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const addLink = () => {
  if (!newLink.value.url || !newLink.value.url.trim()) {
    toast.error('Veuillez saisir une URL')
    return
  }

  try {
    const url = new URL(newLink.value.url)
    if (!['http:', 'https:'].includes(url.protocol)) {
      throw new Error('Protocol invalide')
    }
  } catch {
    toast.error('URL invalide (doit commencer par http:// ou https://)')
    return
  }

  const exists = externalLinks.value.some(link => link.url === newLink.value.url)
  if (exists) {
    toast.warning('Ce lien a déjà été ajouté')
    return
  }

  externalLinks.value.push({
    url: newLink.value.url.trim(),
    title: newLink.value.title.trim() || newLink.value.url.trim()
  })

  newLink.value = { url: '', title: '' }
  toast.success('Lien ajouté avec succès')
}

const removeLink = (index) => {
  const linkTitle = externalLinks.value[index].title
  externalLinks.value.splice(index, 1)
  toast.info(`Lien "${linkTitle}" retiré`)
}

const handleCoverImageUpload = (event) => {
  const file = event.target.files?.[0]

  if (!file) return

  if (file.size > 2 * 1024 * 1024) {
    toast.error('L\'image ne doit pas dépasser 2 Mo')
    event.target.value = ''
    return
  }

  const validImageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif']
  if (!validImageTypes.includes(file.type)) {
    toast.error(`Format non supporté (${file.type}). Formats acceptés: JPEG, PNG, GIF`)
    event.target.value = ''
    return
  }

  coverImageFile.value = file

  const reader = new FileReader()
  reader.onload = (e) => {
    coverImagePreview.value = e.target.result
    toast.success('Image de couverture ajoutée')
  }
  reader.onerror = () => {
    toast.error('Erreur lors de la lecture du fichier')
    coverImageFile.value = null
  }
  reader.readAsDataURL(file)
}

const getProgressColorClass = (progress) => {
  if (progress < 30) return 'bg-red-500'
  if (progress < 70) return 'bg-amber-500'
  return 'bg-green-500'
}

const formatDateForApi = (date) => {
  if (!date) return null

  if (typeof date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(date)) {
    return date
  }

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
 * ✅ AMÉLIORATION : Validation avant soumission
 */
const validateForm = () => {
  const errors = []

  if (!formData.value.responsable_id) {
    errors.push('Vous devez sélectionner un responsable pour la tâche')
  }

  if (!formData.value.titre || formData.value.titre.trim() === '') {
    errors.push('Le titre de la tâche est obligatoire')
  }

  if (!formData.value.activite_id) {
    errors.push('Vous devez sélectionner une activité')
  }

  return errors
}

/**
 * ✅ AMÉLIORATION : Soumission avec notifications explicites
 */
const handleSubmit = async () => {
  // ✅ Validation des permissions
  if (!hasPermission.value) {
    const action = props.tache ? 'edit_tasks' : 'create_tasks'
    toast.error(getPermissionDeniedMessage(action))
    return
  }

  // ✅ Validation du formulaire
  const errors = validateForm()
  if (errors.length > 0) {
    errors.forEach(error => toast.error(error, { timeout: 5000 }))
    return
  }

  loading.value = true

  try {
    if (currentActivite.value) {
      formData.value.activite_id = currentActivite.value.id
    }

    const formDataObj = new FormData()

    formDataObj.append('activite_id', formData.value.activite_id)
    formDataObj.append('responsable_id', formData.value.responsable_id)
    formDataObj.append('titre', formData.value.titre || '')
    formDataObj.append('description', formData.value.description || '')
    formDataObj.append('objectif', formData.value.objectif || '')
    formDataObj.append('indicateurs_resultats', formData.value.indicateurs_resultats || '')
    formDataObj.append('statut', formData.value.statut)
    formDataObj.append('priorite', formData.value.priorite)

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

    formDataObj.append('validation_n1_required', formData.value.validation_n1_required ? '1' : '0')
    formDataObj.append('validation_n2_required', formData.value.validation_n2_required ? '1' : '0')

    formDataObj.append('couleur', formData.value.couleur || '#3B82F6')
    formDataObj.append('commentaire', formData.value.commentaire || '')

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

    if (uploadedFiles.value.length > 0) {
      uploadedFiles.value.forEach((file, index) => {
        formDataObj.append(`uploaded_files[${index}]`, file, file.name)
      })
    }

    if (externalLinks.value.length > 0) {
      formDataObj.append('external_links', JSON.stringify(externalLinks.value))
    }

    if (coverImageFile.value) {
      formDataObj.append('cover_image', coverImageFile.value, coverImageFile.value.name)
    }

    // ✅ Envoyer
    if (props.tache) {
      formDataObj.append('_method', 'PUT')
      await updateTache(props.tache.id, formDataObj)
      toast.success('✅ Tâche mise à jour avec succès !', {
        timeout: 4000
      })
    } else {
      await createTache(formDataObj)
      toast.success('✅ Tâche créée avec succès !', {
        timeout: 4000
      })
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
 * ✅ AMÉLIORATION : Gestion des erreurs avec toasts
 */
const handleError = (error) => {
  if (error.response?.status === 422) {
    const errors = error.response.data.errors
    if (errors) {
      Object.values(errors).flat().forEach(err => {
        toast.error(err, { timeout: 6000 })
      })
    } else {
      toast.error(error.response.data.message || 'Erreur de validation')
    }
  } else if (error.response?.data?.message) {
    toast.error(error.response.data.message)
  } else if (error.message === 'Network Error') {
    toast.error('Erreur de connexion. Vérifiez votre connexion internet.')
  } else {
    toast.error('Une erreur s\'est produite. Veuillez réessayer.')
  }
}

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

    await loadActivityMembers(activiteId).catch(err => {
      if (currentActivite.value?.projet_id) {
        return loadProjectMembers(currentActivite.value.projet_id)
      }
    })
  } catch (error) {
    console.error('❌ Erreur vérification permissions:', error)
    toast.error('Erreur lors de la vérification des permissions')
    permissionChecked.value = true
  } finally {
    isCheckingPermissions.value = false
  }
}

const handleLabelCreated = async () => {
  showLabelModal.value = false
  try {
    if (currentActivite.value?.projet_id) {
      await fetchLabelsForProject(currentActivite.value.projet_id)
    } else {
      await fetchLabels()
    }
    toast.success('Label créé avec succès')
  } catch (error) {
    console.error('❌ Erreur rechargement labels:', error)
    toast.error('Erreur lors du rechargement des labels')
  }
}

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
    toast.error('Erreur lors du chargement des données')
  }
}

// ==================== WATCHERS ====================

/**
 * ✅ Watcher pour auto-sélectionner le responsable dans les assignés
 */
watch(() => formData.value.responsable_id, (newResponsableId, oldResponsableId) => {
  if (newResponsableId) {
    if (!formData.value.assignee_ids.includes(newResponsableId)) {
      formData.value.assignee_ids.push(newResponsableId)
      console.log('✅ Responsable automatiquement ajouté aux assignés:', newResponsableId)
    }
  } else if (oldResponsableId) {
    const index = formData.value.assignee_ids.indexOf(oldResponsableId)
    if (index > -1) {
      formData.value.assignee_ids.splice(index, 1)
      console.log('⚠️ Ancien responsable retiré des assignés:', oldResponsableId)
    }
  }
}, { immediate: false })

/**
 * ✅ CORRIGÉ : Empêcher de retirer le responsable avec nextTick pour éviter les réactions multiples
 */
watch(() => formData.value.assignee_ids, (newAssignees, oldAssignees) => {
  const responsableId = formData.value.responsable_id
  
  // Vérifier si le responsable existe et n'est plus dans la liste
  if (responsableId && !newAssignees.includes(responsableId)) {
    // Vérifier si le responsable était dans l'ancienne liste
    if (oldAssignees && oldAssignees.includes(responsableId)) {
      // Le responsable a été ACTIVEMENT retiré par l'utilisateur
      nextTick(() => {
        if (!formData.value.assignee_ids.includes(responsableId)) {
          formData.value.assignee_ids.push(responsableId)
          
          toast.warning('Le responsable ne peut pas être retiré des intervenants', {
            timeout: 3000
          })
        }
      })
    }
  }
}, { deep: true })

// ==================== LIFECYCLE ====================

onMounted(async () => {
  try {
    await loadData()

    if (props.activiteContext) {
      currentActivite.value = props.activiteContext
      formData.value.activite_id = props.activiteContext.id
      await checkPermissions(props.activiteContext.id)
    } else if (props.activiteId) {
      await checkPermissions(props.activiteId)
    }

    if (props.tache) {
      formData.value = {
        ...formData.value,
        activite_id: props.tache.activite_id || '',
        responsable_id: props.tache.responsable_id || null,
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
        // validation_n1_required: props.tache.validation?.n1_required ?? true,
        // validation_n2_required: props.tache.validation?.n2_required ?? true,
        validation_n1_required: true,
        validation_n2_required:  true,
        couleur: props.tache.couleur || '#3B82F6',
        commentaire: props.tache.commentaire || '',
        assignee_ids: props.tache.assignees?.map(a => a.id) || [],
        label_ids: props.tache.labels?.map(l => l.id) || [],
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
    toast.error('Erreur lors de l\'initialisation du formulaire')
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

  0%,
  100% {
    transform: translateX(0);
  }

  10%,
  30%,
  50%,
  70%,
  90% {
    transform: translateX(-4px);
  }

  20%,
  40%,
  60%,
  80% {
    transform: translateX(4px);
  }
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