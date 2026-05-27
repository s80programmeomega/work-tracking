<!-- resources/js/components/resultats/ResultatCard.vue -->
<template>
  <div class="rounded-3 border bg-white dark:bg-white/[0.03] overflow-hidden transition-all " :class="getCardBorderClass()">

    <!-- Header avec informations de validation -->
    <div class="p-6 " :class="getHeaderGradientClass()">
      <div class="flex items-start justify-between gap-4">
        <div class="flex-1">
          <!-- Badge de rôle de l'utilisateur -->
          <div v-if="userRoleBadge" class="mb-3">
            <span class="inline-flex items-center gap-2 text-xs font-semibold px-3 py-1.5 rounded-full"
              :class="userRoleBadge.class">
              <svg v-if="userRole === 'responsable_activite'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
              <svg v-else-if="userRole === 'responsable_projet'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                  d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                  clip-rule="evenodd" />
              </svg>
              <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
              </svg>
              {{ userRoleBadge.label }}
            </span>
          </div>

          <!-- Informations de la tâche -->
          <div class="flex items-center gap-2 mb-2">
            <span v-if="resultat.tache?.code"
              class="text-xs font-mono px-2 py-1 bg-white/20 dark:bg-black/20 rounded text-gray-900 dark:text-white">
              {{ resultat.tache.code }}
            </span>
            <span class="text-xs font-medium px-2 py-1 rounded" :class="getPriorityClass(resultat.tache?.priorite)">
              {{ getPriorityLabel(resultat.tache?.priorite) }}
            </span>
            <span v-if="isUrgent"
              class="text-xs font-medium px-2 py-1 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 rounded animate-pulse">
              🔥 En attente depuis {{ daysSinceSubmission }}
            </span>
          </div>

          <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">
            {{ resultat.tache?.titre }}
          </h3>

          <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
            {{ resultat.tache?.activite?.nom }} • {{ resultat.tache?.activite?.projet?.nom }}
          </p>

          <!-- Informations du collaborateur -->
          <div class="flex items-center gap-3">
            <img v-if="resultat.user?.avatar" :src="getImageUrl(resultat.user.avatar)" :alt="resultat.user.nom"
              class="w-10 h-10 rounded-full border-2 border-white dark:border-gray-700" />
            <div v-else
              class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white border-2 border-white dark:border-gray-700"
              :style="{ backgroundColor: stringToColor(resultat.user?.nom) }">
              {{ getInitials(resultat.user?.nom) }}
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900 dark:text-white">{{ resultat.user?.nom }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                Soumis le {{ formatDate(resultat.soumis_le) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Progression avec indicateur -->
        <div class="text-right">
          <div
            class="inline-flex items-center justify-center w-16 h-16 rounded-full relative bg-gray-100 dark:bg-gray-800">
            <svg class="w-16 h-16 transform -rotate-90 absolute top-0 left-0">
              <circle cx="32" cy="32" r="28" stroke="currentColor" :stroke-width="4" fill="none" stroke-linecap="round"
                :stroke-dasharray="176" :stroke-dashoffset="176 - (176 * resultat.taux_realisation / 100)"
                :class="getProgressStrokeClass(resultat.taux_realisation)" />
            </svg>
            <span class="text-lg font-bold text-gray-900 dark:text-white relative z-10">
              {{ resultat.taux_realisation }}%
            </span>
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Taux réalisé</p>
        </div>
      </div>

      <!-- Badges d'état de validation -->
      <div class="flex items-center gap-2 mt-4 flex-wrap">
        <!-- Badge niveau de validation -->
        <span v-if="currentLevel === 'n1' && availableActions.canValidateN1"
          class="text-xs px-3 py-1 bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300 rounded-full font-medium flex items-center gap-1 animate-pulse">
          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
              clip-rule="evenodd" />
          </svg>
          À valider par vous (N1)
        </span>

        <span v-else-if="currentLevel === 'n2' && availableActions.canValidateN2"
          class="text-xs px-3 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 rounded-full font-medium flex items-center gap-1 animate-pulse">
          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
              clip-rule="evenodd" />
          </svg>
          À valider par vous (N2)
        </span>

        <span v-else-if="currentLevel === 'n1'"
          class="text-xs px-3 py-1 bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 rounded-full font-medium">
          En attente validation N1
        </span>

        <span v-else-if="currentLevel === 'n2'"
          class="text-xs px-3 py-1 bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 rounded-full font-medium">
          En attente validation N2
        </span>

        <!-- Badge documents -->
        <span v-if="resultat.documents && resultat.documents.length > 0"
          class="text-xs px-3 py-1 bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 rounded-full flex items-center gap-1">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          {{ resultat.documents.length }} document(s) joint(s)
        </span>

        <!-- Badge délai -->
        <span v-if="isUrgent"
          class="text-xs px-3 py-1 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 rounded-full flex items-center gap-1">
          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
              clip-rule="evenodd" />
          </svg>
          À traiter en priorité
        </span>
      </div>
    </div>

    <!-- Contenu principal -->
    <div class="p-6 border-t border-gray-200 dark:border-gray-700">
      <!-- Résumé rapide -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div class="bg-blue-50 dark:bg-blue-900/10 p-3 rounded-3">
          <p class="text-xs font-medium text-blue-700 dark:text-blue-300 mb-1 flex items-center gap-1">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                clip-rule="evenodd" />
            </svg>
            Résultats attendus
          </p>
          <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-3">{{ resultat.resultats_attendus }}</p>
        </div>
        <div class="bg-green-50 dark:bg-green-900/10 p-3 rounded-3">
          <p class="text-xs font-medium text-green-700 dark:text-green-300 mb-1 flex items-center gap-1">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clip-rule="evenodd" />
            </svg>
            Résultats obtenus
          </p>
          <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-3">{{ resultat.resultats_obtenus }}</p>
        </div>
      </div>

      <!-- Bouton détails -->
      <button @click="$emit('toggle')"
        class="w-full py-2 px-4 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-3 transition-colors flex items-center justify-center gap-2 border border-blue-200 dark:border-blue-800">
        <span>{{ expanded ? 'Masquer les détails' : 'Voir le rapport complet' }}</span>
        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': expanded }" fill="none" stroke="currentColor"
          viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </button>

      <!-- Contenu détaillé -->
      <div v-if="expanded" class="mt-4 space-y-4 pt-4 border-t border-gray-200 dark:border-gray-700">

        <!-- Documents avec prévisualisation -->
        <div v-if="resultat.documents && resultat.documents.length > 0">
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Documents justificatifs ({{ resultat.documents.length }})
          </p>
          <div class="space-y-2">
            <div v-for="doc in resultat.documents" :key="doc.id"
              class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group">
              <div class="flex items-center gap-3 flex-1 min-w-0">
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
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-1">
                <!-- Bouton Prévisualiser -->
                <button v-if="doc.can_view && (doc.is_image || doc.is_pdf)" type="button" @click="previewDocument(doc)"
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
                  <svg class="w-4 h-4 text-green-500 group-hover:text-green-600" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Difficultés rencontrées -->
        <div v-if="resultat.difficultes_rencontrees">
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
            <svg class="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                clip-rule="evenodd" />
            </svg>
            Difficultés rencontrées par le collaborateur
          </p>
          <p
            class="text-sm text-gray-600 dark:text-gray-400 p-3 bg-orange-50 dark:bg-orange-900/10 rounded-3 border border-orange-200 dark:border-orange-800">
            {{ resultat.difficultes_rencontrees }}
          </p>
        </div>

        <!-- Solutions proposées -->
        <div v-if="resultat.solutions_envisagees">
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"
                clip-rule="evenodd" />
            </svg>
            Solutions proposées
          </p>
          <p
            class="text-sm text-gray-600 dark:text-gray-400 p-3 bg-green-50 dark:bg-green-900/10 rounded-3 border border-green-200 dark:border-green-800">
            {{ resultat.solutions_envisagees }}
          </p>
        </div>

        <!-- Observations -->
        <div v-if="resultat.observations">
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
            <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                clip-rule="evenodd" />
            </svg>
            Observations du collaborateur
          </p>
          <p
            class="text-sm text-gray-600 dark:text-gray-400 p-3 bg-blue-50 dark:bg-blue-900/10 rounded-3 border border-blue-200 dark:border-blue-800">
            {{ resultat.observations }}
          </p>
        </div>
      </div>

      <!-- Actions de validation CONDITIONNELLES -->
      <div class="flex items-center gap-3 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
        <!-- Bouton Voir détails (toujours visible si can_view) -->
        <button v-if="availableActions.canView" @click="$emit('view-details', resultat)"
          class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-3 transition-colors flex items-center justify-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
          Consulter
        </button>

        <!-- Bouton Valider (N1 ou N2) -->
        <button v-if="availableActions.canValidateN1 || availableActions.canValidateN2" @click="handleValidate"
          dusk="resultat-validate-btn"
          class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-3 transition-colors flex items-center justify-center gap-2 shadow-green-500/30">
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
              clip-rule="evenodd" />
          </svg>
          Valider {{ availableActions.canValidateN1 ? 'N1' : 'N2' }}
        </button>

        <!-- Bouton Rejeter (N1 ou N2) -->
        <button v-if="availableActions.canRejectN1 || availableActions.canRejectN2" @click="handleReject"
          class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-3 transition-colors flex items-center justify-center gap-2 shadow-red-500/30">
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
              clip-rule="evenodd" />
          </svg>
          Demander modifications
        </button>
      </div>

      <!-- Message si pas de permissions -->
      <div v-if="!availableActions.canView && !availableActions.canValidateN1 && !availableActions.canValidateN2"
        class="mt-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-3 text-center">
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Vous n'avez pas les permissions nécessaires pour ce résultat
        </p>
      </div>


    </div>
  </div>

  <!-- Modal de prévisualisation -->
  <transition name="fade">
    <div v-if="showPreviewModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80">
      <div class="bg-white dark:bg-gray-800 rounded-3 max-w-4xl w-full max-h-[90vh] overflow-hidden ">
        <!-- Header du modal -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <div :class="getFileIconClass(previewDoc)" class="w-8 h-8 rounded flex items-center justify-center">
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
            <button v-if="previewDoc?.can_download" @click="downloadDocument(previewDoc)"
              class="p-2 hover:bg-green-100 dark:hover:bg-green-900/30 rounded transition-colors" title="Télécharger">
              <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
            </button>
            <button @click="closePreviewModal"
              class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors">
              <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Contenu de prévisualisation -->
        <div class="p-4 max-h-[calc(90vh-80px)] overflow-auto">
          <div v-if="previewDoc">
            <!-- Image -->
            <img v-if="previewDoc.is_image" :src="previewDoc.url" :alt="previewDoc.nom"
              class="max-w-full max-h-[70vh] mx-auto rounded-3 " @load="previewLoading = false"
              @error="previewError = true" />

            <!-- PDF -->
            <iframe v-else-if="previewDoc.is_pdf" :src="previewDoc.url" class="w-full h-[70vh] rounded-3 border-0"
              frameborder="0">
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
                class="px-4 py-2 bg-blue-500 text-white rounded-3 hover:bg-blue-600 transition-colors flex items-center gap-2 mx-auto">
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
</template>

<script setup>
import { computed, ref, onUnmounted } from 'vue'
import { useToast } from "vue-toastification"
import api from '@/api/axios'
import { useEvaluationPermissions } from '@/composables/useEvaluationPermissions'

const toast = useToast()

const props = defineProps({
  resultat: {
    type: Object,
    required: true
  },
  expanded: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['toggle', 'validate', 'reject', 'view-details'])

// Utiliser le composable de permissions
const {
  getAvailableActions,
  getCurrentValidationLevel,
  getProgressStatus,
  getUserRole,
  getRoleBadge
} = useEvaluationPermissions()


// Computed properties
const availableActions = computed(() => getAvailableActions(props.resultat))
const currentLevel = computed(() => getCurrentValidationLevel(props.resultat))
const progressStatus = computed(() => getProgressStatus(props.resultat))
const userRole = computed(() => getUserRole(props.resultat))
const userRoleBadge = computed(() => getRoleBadge(props.resultat))

// States pour la prévisualisation
const showPreviewModal = ref(false)
const previewDoc = ref(null)
const previewLoading = ref(false)
const previewError = ref(false)

// Computed
const isUrgent = computed(() => {
  if (!props.resultat.soumis_le) return false
  const soumisDate = new Date(props.resultat.soumis_le)
  const now = new Date()
  const diffHours = (now - soumisDate) / (1000 * 60 * 60)
  return diffHours > 48
})

const daysSinceSubmission = computed(() => {
  if (!props.resultat.soumis_le) return 0
  const soumisDate = new Date(props.resultat.soumis_le)
  const now = new Date()
  return Math.floor((now - soumisDate) / (1000 * 60 * 60 * 24))
})

// Methods
function handleValidate() {
  const level = availableActions.value.canValidateN1 ? 'n1' : 'n2'
  emit('validate', props.resultat, level)
}

function handleReject() {
  const level = availableActions.value.canRejectN1 ? 'n1' : 'n2'
  emit('reject', props.resultat, level)
}

// Methods
function getCardBorderClass() {
  if (availableActions.value.canValidateN1) {
    return 'border-orange-300 dark:border-orange-800'
  }
  if (availableActions.value.canValidateN2) {
    return 'border-blue-300 dark:border-blue-800'
  }
  return 'border-gray-200 dark:border-gray-700'
}

function getHeaderGradientClass() {
  if (availableActions.value.canValidateN1) {
    return 'from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 border-b border-orange-200 dark:border-orange-800'
  }
  if (availableActions.value.canValidateN2) {
    return 'from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-b border-blue-200 dark:border-blue-800'
  }
  return 'from-gray-50 to-gray-100 dark:from-gray-800/50 dark:to-gray-900/50 border-b border-gray-200 dark:border-gray-700'
}

function getProgressStrokeClass(taux) {
  if (taux >= 90) return 'text-green-500'
  if (taux >= 70) return 'text-blue-500'
  if (taux >= 50) return 'text-amber-500'
  return 'text-red-500'
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

function getPriorityClass(priorite) {
  const classes = {
    faible: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    moyenne: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
    elevee: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
    critique: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
  }
  return classes[priorite] || classes.moyenne
}

function getPriorityLabel(priorite) {
  const labels = {
    faible: 'Faible',
    moyenne: 'Moyenne',
    elevee: 'Élevée',
    critique: 'Critique'
  }
  return labels[priorite] || priorite
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

// Méthodes pour les documents
async function previewDocument(doc) {
  previewLoading.value = true
  previewError.value = false

  try {
    const response = await api.get(
      `/taches/${props.resultat.tache_id}/resultats/${props.resultat.id}/documents/${doc.id}/view`,
      {
        responseType: 'blob',
        headers: {
          'Accept': doc.is_pdf ? 'application/pdf' : (doc.is_image ? 'image/*' : '*/*')
        }
      }
    )

    const blobUrl = URL.createObjectURL(response.data)

    previewDoc.value = {
      ...doc,
      url: blobUrl,
      is_image: doc.is_image,
      is_pdf: doc.is_pdf,
      blobCreated: true
    }

    showPreviewModal.value = true
  } catch (error) {
    console.error('Erreur prévisualisation:', error)
    previewError.value = true

    if (error.response?.status === 401) {
      toast.error('Session expirée. Veuillez vous reconnecter.')
    } else if (error.response?.status === 403) {
      toast.error('Vous n\'avez pas la permission de voir ce document')
    } else {
      toast.error('Erreur lors de la prévisualisation du document')
    }
  } finally {
    previewLoading.value = false
  }
}

async function downloadDocument(doc) {
  try {
    const response = await api.get(
      `/taches/${props.resultat.tache_id}/resultats/${props.resultat.id}/documents/${doc.id}/download`,
      { responseType: 'blob' }
    )

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

function closePreviewModal() {
  if (previewDoc.value?.blobCreated && previewDoc.value?.url) {
    URL.revokeObjectURL(previewDoc.value.url)
  }
  showPreviewModal.value = false
  previewDoc.value = null
  previewLoading.value = false
  previewError.value = false
}

// Nettoyage
onUnmounted(() => {
  closePreviewModal()
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
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