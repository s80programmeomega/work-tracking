<!-- resources/js/components/resultats/ResultatDetailModal.vue -->
<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

    <!-- Modal -->
    <div class="flex min-h-screen items-center justify-center p-4">
      <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden transform transition-all">
        <!-- Header -->
        <div class="sticky top-0 z-10 p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <span class="text-xs font-mono px-2 py-1 bg-white/50 dark:bg-black/20 rounded">
                  {{ resultat.tache?.code }}
                </span>
                <span class="text-xs font-medium px-2 py-1 rounded" :class="getValidationStatusClass()">
                  {{ getValidationStatusLabel() }}
                </span>
              </div>
              <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                {{ resultat.tache?.titre }}
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ resultat.tache?.activite?.nom }} • {{ resultat.tache?.activite?.projet?.nom }}
              </p>
            </div>

            <button 
              @click="$emit('close')"
              class="p-2 hover:bg-white/50 dark:hover:bg-black/20 rounded-lg transition-colors"
            >
              <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Body -->
        <div class="overflow-y-auto" style="max-height: calc(90vh - 140px);">
          <div class="p-6 space-y-6">
            <!-- User & Timeline -->
            <div class="flex items-start justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
              <div class="flex items-center gap-3">
                <img 
                  v-if="resultat.user?.avatar" 
                  :src="getImageUrl(resultat.user.avatar)" 
                  :alt="resultat.user.nom" 
                  class="w-12 h-12 rounded-full border-2 border-white dark:border-gray-700"
                />
                <div v-else class="w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold text-white border-2 border-white dark:border-gray-700"
                     :style="{ backgroundColor: stringToColor(resultat.user?.nom) }">
                  {{ getInitials(resultat.user?.nom) }}
                </div>
                <div>
                  <p class="font-medium text-gray-900 dark:text-white">{{ resultat.user?.nom }}</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">{{ resultat.user?.email }}</p>
                </div>
              </div>

              <div class="text-right">
                <p class="text-sm font-medium text-gray-900 dark:text-white">Soumis le</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDateTime(resultat.soumis_le) }}</p>
              </div>
            </div>

            <!-- Taux de réalisation -->
            <div class="p-6 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl">
              <div class="flex items-center justify-between mb-4">
                <div>
                  <p class="text-sm text-gray-600 dark:text-gray-400">Taux de réalisation</p>
                  <p class="text-4xl font-bold text-blue-600 dark:text-blue-400">{{ resultat.taux_realisation }}%</p>
                </div>
                <div class="w-24 h-24 rounded-full flex items-center justify-center" :class="getProgressRingClass()">
                  <svg class="w-20 h-20 transform -rotate-90">
                    <circle
                      cx="40"
                      cy="40"
                      r="36"
                      stroke="currentColor"
                      stroke-width="8"
                      fill="none"
                      class="text-gray-200 dark:text-gray-700"
                    />
                    <circle
                      cx="40"
                      cy="40"
                      r="36"
                      stroke="currentColor"
                      stroke-width="8"
                      fill="none"
                      :stroke-dasharray="`${2 * Math.PI * 36}`"
                      :stroke-dashoffset="`${2 * Math.PI * 36 * (1 - resultat.taux_realisation / 100)}`"
                      class="transition-all duration-500"
                      :class="getProgressColor()"
                    />
                  </svg>
                </div>
              </div>
            </div>

            <!-- Résultats -->
            <div class="grid grid-cols-2 gap-4">
              <div class="p-4 bg-blue-50 dark:bg-blue-900/10 rounded-xl border border-blue-200 dark:border-blue-800">
                <div class="flex items-center gap-2 mb-3">
                  <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                  <p class="font-medium text-blue-900 dark:text-blue-300">Résultats attendus</p>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                  {{ resultat.resultats_attendus }}
                </p>
              </div>

              <div class="p-4 bg-green-50 dark:bg-green-900/10 rounded-xl border border-green-200 dark:border-green-800">
                <div class="flex items-center gap-2 mb-3">
                  <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <p class="font-medium text-green-900 dark:text-green-300">Résultats obtenus</p>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                  {{ resultat.resultats_obtenus }}
                </p>
              </div>
            </div>

            <!-- Difficultés -->
            <div v-if="resultat.difficultes_rencontrees" class="p-4 bg-orange-50 dark:bg-orange-900/10 rounded-xl border border-orange-200 dark:border-orange-800">
              <div class="flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <p class="font-medium text-orange-900 dark:text-orange-300">Difficultés rencontrées</p>
              </div>
              <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                {{ resultat.difficultes_rencontrees }}
              </p>
            </div>

            <!-- Solutions -->
            <div v-if="resultat.solutions_envisagees" class="p-4 bg-purple-50 dark:bg-purple-900/10 rounded-xl border border-purple-200 dark:border-purple-800">
              <div class="flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
                </svg>
                <p class="font-medium text-purple-900 dark:text-purple-300">Solutions envisagées</p>
              </div>
              <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                {{ resultat.solutions_envisagees }}
              </p>
            </div>

            <!-- Observations -->
            <div v-if="resultat.observations" class="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
                <p class="font-medium text-gray-900 dark:text-gray-300">Observations</p>
              </div>
              <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                {{ resultat.observations }}
              </p>
            </div>

            <!-- Documents -->
            <div v-if="resultat.documents && resultat.documents.length > 0" class="p-4 bg-indigo-50 dark:bg-indigo-900/10 rounded-xl border border-indigo-200 dark:border-indigo-800">
              <div class="flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
                <p class="font-medium text-indigo-900 dark:text-indigo-300">
                  Documents joints ({{ resultat.documents.length }})
                </p>
              </div>
              <div class="space-y-2">
                <a 
                  v-for="doc in resultat.documents" 
                  :key="doc.id"
                  :href="doc.url"
                  target="_blank"
                  download
                  class="flex items-center gap-3 p-3 hover:bg-indigo-100 dark:hover:bg-indigo-900/20 rounded-lg transition-colors group"
                >
                  <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400">
                      {{ doc.nom }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      {{ formatFileSize(doc.taille_fichier) }} • {{ doc.extension?.toUpperCase() }}
                    </p>
                  </div>
                  <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                  </svg>
                </a>
              </div>
            </div>

            <!-- Validation History -->
            <div class="space-y-3">
              <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                Historique de validation
              </h4>

              <!-- Validation N1 -->
              <div class="p-4 rounded-xl" :class="getN1ValidationClass()">
                <div class="flex items-start gap-3">
                  <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0" :class="resultat.valide_par_n1 ? 'bg-green-600' : 'bg-gray-400'">
                    <svg v-if="resultat.valide_par_n1" class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span v-else class="text-white font-bold">1</span>
                  </div>
                  <div class="flex-1">
                    <p class="font-medium text-gray-900 dark:text-white">Validation Niveau 1</p>
                    <p v-if="resultat.valide_par_n1" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                      Validé par {{ resultat.validateurN1?.nom }} le {{ formatDateTime(resultat.valide_le_n1) }}
                    </p>
                    <p v-else class="text-sm text-gray-500 dark:text-gray-400 mt-1">En attente de validation</p>
                    <p v-if="resultat.commentaire_n1" class="text-sm text-gray-700 dark:text-gray-300 mt-2 p-2 bg-white dark:bg-gray-900 rounded">
                      💬 {{ resultat.commentaire_n1 }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Validation N2 -->
              <div v-if="resultat.tache?.validation_n2_required" class="p-4 rounded-xl" :class="getN2ValidationClass()">
                <div class="flex items-start gap-3">
                  <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0" :class="resultat.valide_par_n2 ? 'bg-blue-600' : 'bg-gray-400'">
                    <svg v-if="resultat.valide_par_n2" class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span v-else class="text-white font-bold">2</span>
                  </div>
                  <div class="flex-1">
                    <p class="font-medium text-gray-900 dark:text-white">Validation Niveau 2</p>
                    <p v-if="resultat.valide_par_n2" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                      Validé par {{ resultat.validateurN2?.nom }} le {{ formatDateTime(resultat.valide_le_n2) }}
                    </p>
                    <p v-else class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                      {{ resultat.valide_par_n1 ? 'En attente de validation' : 'Nécessite validation N1 d\'abord' }}
                    </p>
                    <p v-if="resultat.commentaire_n2" class="text-sm text-gray-700 dark:text-gray-300 mt-2 p-2 bg-white dark:bg-gray-900 rounded">
                      💬 {{ resultat.commentaire_n2 }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="sticky bottom-0 p-6 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 flex justify-end">
          <button
            @click="$emit('close')"
            class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  resultat: {
    type: Object,
    required: true
  }
})

defineEmits(['close'])

function getValidationStatusClass() {
  const status = getValidationStatusLabel()
  if (status.includes('Entièrement')) return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
  if (status.includes('N1')) return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300'
  if (status.includes('N2')) return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
  return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
}

function getValidationStatusLabel() {
  if (!this.resultat) return 'Non soumis'
  if (this.resultat.valide_par_n2) return '✅ Entièrement validé'
  if (this.resultat.valide_par_n1) return '⏳ En attente validation N2'
  return '⏳ En attente validation N1'
}

function getProgressRingClass() {
  const taux = this.resultat?.taux_realisation || 0
  if (taux >= 90) return 'text-green-500'
  if (taux >= 70) return 'text-blue-500'
  if (taux >= 50) return 'text-amber-500'
  return 'text-red-500'
}

function getProgressColor() {
  const taux = this.resultat?.taux_realisation || 0
  if (taux >= 90) return 'text-green-500'
  if (taux >= 70) return 'text-blue-500'
  if (taux >= 50) return 'text-amber-500'
  return 'text-red-500'
}

function getN1ValidationClass() {
  if (this.resultat?.valide_par_n1) {
    return 'bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-800'
  }
  return 'bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700'
}

function getN2ValidationClass() {
  if (this.resultat?.valide_par_n2) {
    return 'bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800'
  }
  return 'bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700'
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
</script>