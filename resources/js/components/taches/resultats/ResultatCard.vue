<!-- resources/js/components/resultats/ResultatCard.vue -->
<template>
  <div class="rounded-2xl border bg-white dark:bg-white/[0.03] overflow-hidden transition-all hover:shadow-lg"
       :class="getCardBorderClass()">
    <!-- Header -->
    <div class="p-6 bg-gradient-to-r" :class="getHeaderGradientClass()">
      <div class="flex items-start justify-between gap-4">
        <div class="flex-1">
          <!-- Tâche info -->
          <div class="flex items-center gap-2 mb-2">
            <span v-if="resultat.tache?.code" class="text-xs font-mono px-2 py-1 bg-white/20 dark:bg-black/20 rounded text-gray-900 dark:text-white">
              {{ resultat.tache.code }}
            </span>
            <span class="text-xs font-medium px-2 py-1 rounded" :class="getPriorityClass(resultat.tache?.priorite)">
              {{ getPriorityLabel(resultat.tache?.priorite) }}
            </span>
            <span v-if="isUrgent" class="text-xs font-medium px-2 py-1 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 rounded animate-pulse">
              🔥 Urgent
            </span>
          </div>

          <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">
            {{ resultat.tache?.titre }}
          </h3>

          <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
            {{ resultat.tache?.activite?.nom }} • {{ resultat.tache?.activite?.projet?.nom }}
          </p>

          <!-- User info -->
          <div class="flex items-center gap-3">
            <img 
              v-if="resultat.user?.avatar" 
              :src="getImageUrl(resultat.user.avatar)" 
              :alt="resultat.user.nom" 
              class="w-10 h-10 rounded-full border-2 border-white dark:border-gray-700"
            />
            <div v-else class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white border-2 border-white dark:border-gray-700"
                 :style="{ backgroundColor: stringToColor(resultat.user?.nom) }">
              {{ getInitials(resultat.user?.nom) }}
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900 dark:text-white">{{ resultat.user?.nom }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Soumis le {{ formatDate(resultat.soumis_le) }}</p>
            </div>
          </div>
        </div>

        <!-- Stats -->
        <div class="text-right">
          <div class="inline-flex items-center justify-center w-16 h-16 rounded-full" 
               :class="getProgressRingClass(resultat.taux_realisation)">
            <span class="text-lg font-bold text-gray-900 dark:text-white">
              {{ resultat.taux_realisation }}%
            </span>
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Réalisation</p>
        </div>
      </div>

      <!-- Validation status badges -->
      <div class="flex items-center gap-2 mt-4">
        <span v-if="level === 'n1'" class="text-xs px-3 py-1 bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300 rounded-full font-medium">
          ⏳ En attente validation N1
        </span>
        <span v-else-if="level === 'n2'" class="text-xs px-3 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 rounded-full font-medium flex items-center gap-1">
          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
          N1 validé • En attente N2
        </span>

        <span v-if="resultat.documents_count > 0" class="text-xs px-3 py-1 bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 rounded-full flex items-center gap-1">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
          </svg>
          {{ resultat.documents_count }} doc(s)
        </span>
      </div>
    </div>

    <!-- Content (collapsible) -->
    <div class="p-6 border-t border-gray-200 dark:border-gray-700">
      <!-- Quick summary -->
      <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Résultats attendus</p>
          <p class="text-sm text-gray-900 dark:text-white line-clamp-2">{{ resultat.resultats_attendus }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Résultats obtenus</p>
          <p class="text-sm text-gray-900 dark:text-white line-clamp-2">{{ resultat.resultats_obtenus }}</p>
        </div>
      </div>

      <!-- Toggle details button -->
      <button 
        @click="$emit('toggle')"
        class="w-full py-2 px-4 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors flex items-center justify-center gap-2"
      >
        <span>{{ expanded ? 'Masquer les détails' : 'Voir les détails complets' }}</span>
        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </button>

      <!-- Expanded content -->
      <div v-if="expanded" class="mt-4 space-y-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <!-- Difficultés -->
        <div v-if="resultat.difficultes_rencontrees">
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
            <svg class="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            Difficultés rencontrées
          </p>
          <p class="text-sm text-gray-600 dark:text-gray-400 p-3 bg-orange-50 dark:bg-orange-900/10 rounded-lg">
            {{ resultat.difficultes_rencontrees }}
          </p>
        </div>

        <!-- Solutions -->
        <div v-if="resultat.solutions_envisagees">
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
            </svg>
            Solutions envisagées
          </p>
          <p class="text-sm text-gray-600 dark:text-gray-400 p-3 bg-green-50 dark:bg-green-900/10 rounded-lg">
            {{ resultat.solutions_envisagees }}
          </p>
        </div>

        <!-- Observations -->
        <div v-if="resultat.observations">
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
            <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            Observations
          </p>
          <p class="text-sm text-gray-600 dark:text-gray-400 p-3 bg-blue-50 dark:bg-blue-900/10 rounded-lg">
            {{ resultat.observations }}
          </p>
        </div>

        <!-- Documents -->
        <div v-if="resultat.documents && resultat.documents.length > 0">
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
            </svg>
            Documents joints ({{ resultat.documents.length }})
          </p>
          <div class="space-y-2">
            <a 
              v-for="doc in resultat.documents" 
              :key="doc.id"
              :href="doc.url"
              target="_blank"
              class="flex items-center gap-2 p-2 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg transition-colors group"
            >
              <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd" />
              </svg>
              <span class="text-sm text-blue-600 dark:text-blue-400 group-hover:underline">{{ doc.nom }}</span>
              <span class="text-xs text-gray-400">({{ formatFileSize(doc.taille_fichier) }})</span>
            </a>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-3 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
        <button 
          @click="$emit('view-details', resultat)"
          class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors flex items-center justify-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
          Détails complets
        </button>

        <button 
          @click="$emit('validate', resultat, level)"
          class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-lg shadow-green-500/30"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
          Valider {{ level.toUpperCase() }}
        </button>

        <button 
          @click="$emit('reject', resultat, level)"
          class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-lg shadow-red-500/30"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
          Rejeter
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  resultat: {
    type: Object,
    required: true
  },
  level: {
    type: String,
    required: true,
    validator: (value) => ['n1', 'n2'].includes(value)
  },
  expanded: {
    type: Boolean,
    default: false
  }
})

defineEmits(['toggle', 'validate', 'reject', 'view-details'])

// Computed
const isUrgent = computed(() => {
  if (!props.resultat.soumis_le) return false
  const soumisDate = new Date(props.resultat.soumis_le)
  const now = new Date()
  const diffHours = (now - soumisDate) / (1000 * 60 * 60)
  return diffHours > 48 // Plus de 48h sans validation
})

// Methods
function getCardBorderClass() {
  if (props.level === 'n1') {
    return 'border-orange-300 dark:border-orange-800'
  }
  return 'border-blue-300 dark:border-blue-800'
}

function getHeaderGradientClass() {
  if (props.level === 'n1') {
    return 'from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 border-b border-orange-200 dark:border-orange-800'
  }
  return 'from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-b border-blue-200 dark:border-blue-800'
}

function getProgressRingClass(taux) {
  if (taux >= 90) return 'bg-green-100 dark:bg-green-900/30'
  if (taux >= 70) return 'bg-blue-100 dark:bg-blue-900/30'
  if (taux >= 50) return 'bg-amber-100 dark:bg-amber-900/30'
  return 'bg-red-100 dark:bg-red-900/30'
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
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>