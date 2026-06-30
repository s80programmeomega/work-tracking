<!-- resources/js/components/taches/DetailedTaskView.vue -->
<template>
  <div class="space-y-6">

    <!-- En-tête avec statistiques clés -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <!-- Progression inline-editable -->
      <div
        class="rounded-3 p-4 border border-blue-200 dark:border-blue-800">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-blue-700 dark:text-blue-300">Progression</span>
          <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
          </svg>
        </div>
        <div v-if="editing.field === 'taux_realisation'" class="space-y-2">
          <div class="flex items-center gap-2">
            <input
              type="range" min="0" max="100" step="5"
              v-model.number="editing.value"
              class="flex-1 accent-blue-500"
            />
            <input
              type="number" min="0" max="100"
              v-model.number="editing.value"
              class="w-14 text-sm text-center border border-blue-400 rounded px-1 py-0.5 focus:outline-none bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            />
            <span class="text-xs text-gray-500 dark:text-gray-400">%</span>
          </div>
          <div class="flex gap-1">
            <button @click="cancelEdit" class="flex-1 px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300">Annuler</button>
            <button @click="saveEdit" class="flex-1 px-2 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded">OK</button>
          </div>
        </div>
        <div
          v-else
          :class="tache.permissions?.can_inline_edit ? 'cursor-pointer' : ''"
          :title="tache.permissions?.can_inline_edit ? 'Cliquer pour modifier' : ''"
          @click="startEdit('taux_realisation', localTache.taux_realisation ?? 0)"
        >
          <div class="flex items-end gap-2">
            <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ localTache.taux_realisation }}%</span>
          </div>
          <div class="w-full bg-blue-200 dark:bg-blue-900 rounded-full h-2 mt-3">
            <div class="h-2 rounded-full transition-all duration-300" :class="getProgressColor(localTache.taux_realisation)"
              :style="{ width: `${localTache.taux_realisation}%` }"></div>
          </div>
        </div>
      </div>

      <!-- Temps (display only) -->
      <div
        class="rounded-3 p-4 border border-purple-200 dark:border-purple-800">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-purple-700 dark:text-purple-300">Temps</span>
          <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div class="flex items-end gap-2">
          <span class="text-3xl font-bold text-gray-900 dark:text-white">
            {{ tache.actual_hours || tache.estimated_hours || 0 }}h
          </span>
          <span class="text-sm text-gray-600 dark:text-gray-400 mb-1">/ {{ tache.estimated_hours || 0 }}h</span>
        </div>
        <p v-if="tache.estimated_hours && tache.actual_hours" class="text-xs mt-1"
          :class="getVarianceClass(tache.actual_hours - tache.estimated_hours)">
          {{ formatVariance(tache.actual_hours - tache.estimated_hours) }}
        </p>
      </div>

      <!-- Échéance inline-editable -->
      <div
        class="rounded-3 p-4 border border-amber-200 dark:border-amber-800">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-amber-700 dark:text-amber-300">Échéance</span>
          <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <div v-if="editing.field === 'echeance'">
          <DatePicker
            v-model="editing.value"
            :enable-time-picker="false"
            auto-apply
            :format="'dd-MM-yyyy'"
            :locale="'fr'"
            :dark="isDark"
            placeholder="Sélectionner une date"
            @update:model-value="saveEdit"
            @keydown.escape="cancelEdit"
            inline
            class="w-full"
          />
        </div>
        <div
          v-else
          :class="tache.permissions?.can_inline_edit ? 'cursor-pointer' : ''"
          :title="tache.permissions?.can_inline_edit ? 'Cliquer pour modifier l\'échéance' : ''"
          @click="startEdit('echeance', localTache.echeance)"
        >
          <div v-if="localTache.echeance">
            <p class="text-lg font-bold"
              :class="localTache.is_overdue ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'">
              {{ formatDate(localTache.echeance) }}
            </p>
            <p v-if="localTache.is_overdue" class="text-xs text-red-600 dark:text-red-400 mt-1 flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              En retard
            </p>
            <p v-else class="text-xs text-gray-600 dark:text-gray-400 mt-1">
              {{ getTimeRemaining(localTache.echeance) }}
            </p>
          </div>
          <p v-else class="text-sm text-gray-500 dark:text-gray-400">
            Non définie
            <span v-if="tache.permissions?.can_inline_edit" class="text-amber-600 text-xs"> — cliquer pour ajouter</span>
          </p>
        </div>
      </div>

      <!-- Validation (display only) -->
      <div
        class="rounded-3 p-4 border border-green-200 dark:border-green-800">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-green-700 dark:text-green-300">Validation</span>
          <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div class="space-y-1">
          <div v-if="tache.validation?.n2_validated_at" class="flex items-center gap-2">
            <div class="w-6 h-6 bg-purple-500 rounded-full flex items-center justify-center">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <span class="text-sm font-semibold text-purple-700 dark:text-purple-300">Validé N2</span>
          </div>
          <div v-else-if="tache.validation?.n1_validated_at" class="flex items-center gap-2">
            <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <span class="text-sm font-semibold text-green-700 dark:text-green-300">Validé N1</span>
          </div>
          <div v-else class="flex items-center gap-2">
            <div class="w-6 h-6 bg-gray-300 dark:bg-gray-700 rounded-full flex items-center justify-center">
              <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <span class="text-sm text-gray-600 dark:text-gray-400">En attente</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Description et objectifs -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Description inline-editable -->
      <div class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Description
        </h3>
        <div v-if="editing.field === 'description'">
          <textarea
            v-model="editing.value"
            @blur="saveEdit"
            @keydown.escape="cancelEdit"
            autofocus
            rows="5"
            class="w-full text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900 border border-brand-400 rounded-3 p-3 focus:outline-none focus:ring-2 focus:ring-brand-500 resize-y"
          />
          <p class="text-xs text-gray-400 mt-1">Cliquer ailleurs pour enregistrer · Echap pour annuler</p>
        </div>
        <div
          v-else-if="localTache.description"
          class="prose prose-sm dark:prose-invert max-w-none"
          :class="tache.permissions?.can_inline_edit ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900 rounded-3 p-2 -m-2 transition-colors' : ''"
          :title="tache.permissions?.can_inline_edit ? 'Cliquer pour modifier' : ''"
          @click="startEdit('description', localTache.description)"
        >
          <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ localTache.description }}</p>
        </div>
        <div
          v-else
          class="text-center py-8"
          :class="tache.permissions?.can_inline_edit ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900 rounded-3 transition-colors' : ''"
          @click="startEdit('description', '')"
        >
          <svg class="w-12 h-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            Aucune description
            <span v-if="tache.permissions?.can_inline_edit" class="block text-brand-400 text-xs mt-1">Cliquer pour ajouter</span>
          </p>
        </div>
      </div>

      <!-- Objectif inline-editable -->
      <div class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
          </svg>
          Objectif
        </h3>
        <div v-if="editing.field === 'objectif'">
          <textarea
            v-model="editing.value"
            @blur="saveEdit"
            @keydown.escape="cancelEdit"
            autofocus
            rows="5"
            class="w-full text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900 border border-brand-400 rounded-3 p-3 focus:outline-none focus:ring-2 focus:ring-brand-500 resize-y"
          />
          <p class="text-xs text-gray-400 mt-1">Cliquer ailleurs pour enregistrer · Echap pour annuler</p>
        </div>
        <div
          v-else-if="localTache.objectif"
          class="prose prose-sm dark:prose-invert max-w-none"
          :class="tache.permissions?.can_inline_edit ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900 rounded-3 p-2 -m-2 transition-colors' : ''"
          :title="tache.permissions?.can_inline_edit ? 'Cliquer pour modifier' : ''"
          @click="startEdit('objectif', localTache.objectif)"
        >
          <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ localTache.objectif }}</p>
        </div>
        <div
          v-else
          class="text-center py-8"
          :class="tache.permissions?.can_inline_edit ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900 rounded-3 transition-colors' : ''"
          @click="startEdit('objectif', '')"
        >
          <svg class="w-12 h-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            Aucun objectif défini
            <span v-if="tache.permissions?.can_inline_edit" class="block text-brand-400 text-xs mt-1">Cliquer pour ajouter</span>
          </p>
        </div>
      </div>
    </div>

    <!-- Indicateurs de résultats inline-editable -->
    <div
      class="rounded-3 border border-blue-200 dark:border-blue-800 p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
        Indicateurs de résultats
      </h3>
      <div v-if="editing.field === 'indicateurs_resultats'">
        <textarea
          v-model="editing.value"
          @blur="saveEdit"
          @keydown.escape="cancelEdit"
          autofocus
          rows="4"
          class="w-full text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-brand-400 rounded-3 p-3 focus:outline-none focus:ring-2 focus:ring-brand-500 resize-y"
        />
        <p class="text-xs text-gray-400 mt-1">Cliquer ailleurs pour enregistrer · Echap pour annuler</p>
      </div>
      <p
        v-else-if="localTache.indicateurs_resultats"
        class="text-gray-700 dark:text-gray-300 whitespace-pre-line"
        :class="tache.permissions?.can_inline_edit ? 'cursor-pointer hover:bg-white/50 dark:hover:bg-gray-800/50 rounded-3 p-2 -m-2 transition-colors' : ''"
        :title="tache.permissions?.can_inline_edit ? 'Cliquer pour modifier' : ''"
        @click="startEdit('indicateurs_resultats', localTache.indicateurs_resultats)"
      >{{ localTache.indicateurs_resultats }}</p>
      <p
        v-else
        class="text-sm text-gray-500 dark:text-gray-400 italic"
        :class="tache.permissions?.can_inline_edit ? 'cursor-pointer' : ''"
        @click="startEdit('indicateurs_resultats', '')"
      >
        Aucun indicateur défini
        <span v-if="tache.permissions?.can_inline_edit" class="text-brand-400 not-italic"> — cliquer pour ajouter</span>
      </p>
    </div>

    <!-- Informations détaillées en grille -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Dates (display only — already editable above) -->
      <div class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-6">
        <h4 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          Calendrier
        </h4>
        <div class="space-y-4">
          <div v-if="tache.date_debut" class="flex items-start gap-3">
            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-3 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
              </svg>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400">Date de début</p>
              <p class="text-sm font-medium text-gray-900 dark:text-white">{{ formatDate(tache.date_debut) }}</p>
            </div>
          </div>

          <div v-if="localTache.echeance" class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-3 flex items-center justify-center shrink-0"
              :class="localTache.is_overdue ? 'bg-red-100 dark:bg-red-900/30' : 'bg-amber-100 dark:bg-amber-900/30'">
              <svg class="w-5 h-5"
                :class="localTache.is_overdue ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400'"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400">Échéance</p>
              <p class="text-sm font-medium"
                :class="localTache.is_overdue ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'">
                {{ formatDate(localTache.echeance) }}
              </p>
            </div>
          </div>

          <div v-if="tache.week_number" class="flex items-start gap-3">
            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-3 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400">Semaine</p>
              <p class="text-sm font-medium text-gray-900 dark:text-white">S{{ tache.week_number }} - {{ tache.year }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Équipe -->
      <AssigneesPanel :assignees="tache.assignees" />

      <!-- Labels -->
      <LabelsPanel v-if="tache.labels?.length > 0" :labels="tache.labels" />
    </div>

    <!-- Validation Status (display only) -->
    <div v-if="tache.validation"
      class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>
        Statut de validation
      </h3>
      <div class="space-y-4">
        <div class="p-4 rounded-3 border-2 transition-all" :class="tache.validation.n1_validated_at
          ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800'
          : 'bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700'">
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
              <div v-if="tache.validation.n1_validated_at" class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center ">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <div v-else class="w-12 h-12 bg-gray-300 dark:bg-gray-700 rounded-full flex items-center justify-center">
                <span class="text-white font-bold">N1</span>
              </div>
            </div>
            <div class="flex-1">
              <p class="font-semibold text-gray-900 dark:text-white text-lg">Validation N1 (Responsable activité)</p>
              <p v-if="tache.validation.n1_validated_at" class="text-sm text-gray-600 dark:text-gray-400 mt-1 flex items-center gap-2">
                Validé par <span class="font-medium">{{ tache.validation.n1_validated_by?.nom }}</span>
                le {{ formatDateTime(tache.validation.n1_validated_at) }}
              </p>
              <p v-if="tache.validation.n1_commentaire" class="text-sm text-gray-700 dark:text-gray-300 mt-2 p-3 bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 italic">
                "{{ tache.validation.n1_commentaire }}"
              </p>
              <p v-if="!tache.validation.n1_validated_at && tache.validation.n1_required" class="text-sm text-amber-600 dark:text-amber-400 mt-1">
                En attente de validation
              </p>
            </div>
          </div>
        </div>

        <div v-if="tache.validation.n2_required" class="p-4 rounded-3 border-2 transition-all" :class="tache.validation.n2_validated_at
          ? 'bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-800'
          : 'bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700'">
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
              <div v-if="tache.validation.n2_validated_at" class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center ">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <div v-else class="w-12 h-12 bg-gray-300 dark:bg-gray-700 rounded-full flex items-center justify-center">
                <span class="text-white font-bold">N2</span>
              </div>
            </div>
            <div class="flex-1">
              <p class="font-semibold text-gray-900 dark:text-white text-lg">Validation N2 (Responsable projet)</p>
              <p v-if="tache.validation.n2_validated_at" class="text-sm text-gray-600 dark:text-gray-400 mt-1 flex items-center gap-2">
                Validé par <span class="font-medium">{{ tache.validation.n2_validated_by?.nom }}</span>
                le {{ formatDateTime(tache.validation.n2_validated_at) }}
              </p>
              <p v-if="tache.validation.n2_commentaire" class="text-sm text-gray-700 dark:text-gray-300 mt-2 p-3 bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 italic">
                "{{ tache.validation.n2_commentaire }}"
              </p>
              <p v-if="!tache.validation.n2_validated_at && tache.validation.n1_validated_at" class="text-sm text-amber-600 dark:text-amber-400 mt-1">
                En attente de validation
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Commentaire inline-editable -->
    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-3 border border-amber-200 dark:border-amber-800 p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
        </svg>
        Commentaire interne
      </h3>
      <div v-if="editing.field === 'commentaire'">
        <textarea
          v-model="editing.value"
          @blur="saveEdit"
          @keydown.escape="cancelEdit"
          autofocus
          rows="3"
          class="w-full text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-amber-400 rounded-3 p-3 focus:outline-none focus:ring-2 focus:ring-amber-500 resize-y"
        />
        <p class="text-xs text-gray-400 mt-1">Cliquer ailleurs pour enregistrer · Echap pour annuler</p>
      </div>
      <p
        v-else-if="localTache.commentaire"
        class="text-gray-700 dark:text-gray-300 whitespace-pre-line"
        :class="tache.permissions?.can_inline_edit ? 'cursor-pointer hover:bg-amber-100/50 dark:hover:bg-amber-900/30 rounded-3 p-2 -m-2 transition-colors' : ''"
        :title="tache.permissions?.can_inline_edit ? 'Cliquer pour modifier' : ''"
        @click="startEdit('commentaire', localTache.commentaire)"
      >{{ localTache.commentaire }}</p>
      <p
        v-else
        class="text-sm text-gray-500 dark:text-gray-400 italic"
        :class="tache.permissions?.can_inline_edit ? 'cursor-pointer' : ''"
        @click="startEdit('commentaire', '')"
      >
        Aucun commentaire
        <span v-if="tache.permissions?.can_inline_edit" class="text-amber-600 not-italic"> — cliquer pour ajouter</span>
      </p>
    </div>

    <!-- Métadonnées (display only) -->
    <div class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Informations système
      </h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-3">
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Code</p>
          <p class="font-mono font-semibold text-gray-900 dark:text-white">{{ tache.code }}</p>
        </div>
        <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-3">
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Créé le</p>
          <p class="text-sm text-gray-900 dark:text-white">{{ formatDate(tache.created_at) }}</p>
        </div>
        <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-3">
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Modifié le</p>
          <p class="text-sm text-gray-900 dark:text-white">{{ formatDate(localTache.updated_at) }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import api from '@/api/axios'
import DatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import AssigneesPanel from './panels/AssigneesPanel.vue'
import LabelsPanel from './panels/LabelsPanel.vue'

const props = defineProps({
  tache: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['refresh'])

const localTache = ref({ ...props.tache })
const editing = reactive({ field: null, value: null })
const isDark = computed(() => document.documentElement.classList.contains('dark'))

const startEdit = (field, value) => {
  if (!props.tache.permissions?.can_inline_edit) return
  editing.field = field
  editing.value = value ? new Date(value) : null
}

const cancelEdit = () => {
  editing.field = null
  editing.value = null
}

const formatDateForApi = (date) => {
  if (!date) return null
  const d = date instanceof Date ? date : new Date(date)
  if (isNaN(d.getTime())) return null
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const saveEdit = async () => {
  const { field, value } = editing
  if (!field) return

  const apiValue = field === 'echeance' ? formatDateForApi(value) : value
  const oldValue = localTache.value[field]
  if ((apiValue ?? '') === (oldValue ?? '')) {
    cancelEdit()
    return
  }

  cancelEdit()
  try {
    await api.patch(`/taches/${props.tache.id}`, { [field]: apiValue })
    localTache.value[field] = apiValue
    emit('refresh')
  } catch (err) {
    console.error('Erreur mise à jour:', err)
    localTache.value[field] = oldValue
  }
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const formatDateTime = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getProgressColor = (progress) => {
  if (progress >= 75) return 'bg-green-500'
  if (progress >= 50) return 'bg-blue-500'
  if (progress >= 25) return 'bg-yellow-500'
  return 'bg-red-500'
}

const getVarianceClass = (variance) => {
  if (variance > 0) return 'text-red-600 dark:text-red-400'
  if (variance < 0) return 'text-green-600 dark:text-green-400'
  return 'text-gray-600 dark:text-gray-400'
}

const formatVariance = (variance) => {
  const sign = variance > 0 ? '+' : ''
  return `${sign}${variance.toFixed(1)}h`
}

const getTimeRemaining = (date) => {
  const now = new Date()
  const target = new Date(date)
  const diffMs = target - now
  const diffDays = Math.ceil(diffMs / (1000 * 60 * 60 * 24))

  if (diffDays < 0) return 'Échue'
  if (diffDays === 0) return 'Aujourd\'hui'
  if (diffDays === 1) return 'Demain'
  if (diffDays <= 7) return `Dans ${diffDays} jours`
  if (diffDays <= 30) return `Dans ${Math.ceil(diffDays / 7)} semaines`
  return `Dans ${Math.ceil(diffDays / 30)} mois`
}

const handleKeydown = (e) => {
  if (e.key === 'Escape' && editing.field) cancelEdit()
}

onMounted(() => { window.addEventListener('keydown', handleKeydown) })
onBeforeUnmount(() => { window.removeEventListener('keydown', handleKeydown) })
</script>
