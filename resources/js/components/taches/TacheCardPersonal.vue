<!-- resources/js/components/taches/TacheCardPersonal.vue -->
<template>
  <div 
    class="relative group bg-white dark:bg-gray-800 rounded-lg border transition-all hover:shadow-lg"
    :class="[
      cardBorderClass,
      compact ? 'p-2' : 'p-4',
      { 'cursor-pointer': !compact }
    ]"
    @click="!compact && $emit('view', tache)"
  >
    <!-- En-tête -->
    <div class="flex items-start justify-between gap-2 mb-2">
      <div class="flex-1 min-w-0">
        <!-- Code + Priorité -->
        <div class="flex items-center gap-2 mb-1 flex-wrap">
          <span v-if="tache.code" class="text-xs font-mono text-gray-500 dark:text-gray-400">
            {{ tache.code }}
          </span>
          <span 
            class="text-xs font-medium px-2 py-0.5 rounded"
            :class="getPriorityClass(tache.priorite)"
          >
            {{ tache.priorite_icon }} {{ compact ? '' : tache.priorite_label }}
          </span>
          <span v-if="tache.is_overdue && myStatus !== 'termine'" class="text-xs font-medium px-2 py-0.5 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 rounded animate-pulse">
            ⏰ En retard
          </span>
        </div>

        <!-- Titre -->
        <h4 
          class="font-semibold text-gray-900 dark:text-white mb-1"
          :class="compact ? 'text-xs line-clamp-2' : 'text-sm line-clamp-2'"
        >
          {{ tache.titre }}
        </h4>

        <!-- Activité -->
        <p v-if="!compact" class="text-xs text-gray-500 dark:text-gray-400 truncate">
          {{ tache.activite.nom }}
        </p>
      </div>

      <!-- Actions rapides -->
      <div v-if="!compact" class="flex-shrink-0">
        <button
          @click.stop="toggleMenu"
          class="p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors opacity-0 group-hover:opacity-100"
        >
          <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Double Statut : Mon statut + Statut global -->
    <div class="space-y-2 mb-3">
      <!-- Mon statut individuel -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div 
            class="w-2 h-2 rounded-full"
            :style="{ backgroundColor: getStatusColor(myStatus) }"
          ></div>
          <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
            Mon statut : {{ getStatusLabel(myStatus) }}
          </span>
        </div>
        <span class="text-xs font-bold" :style="{ color: getStatusColor(myStatus) }">
          {{ myProgression }}%
        </span>
      </div>

      <!-- Barre de progression personnelle -->
      <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
        <div 
          class="h-full rounded-full transition-all duration-300"
          :style="{ 
            width: `${myProgression}%`,
            backgroundColor: getStatusColor(myStatus)
          }"
        ></div>
      </div>

      <!-- Statut global (si tâche collaborative) -->
      <div v-if="isCollaborative" class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
        <div class="flex items-center gap-1">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          <span>Équipe : {{ getStatusLabel(tache.statut) }}</span>
        </div>
        <span class="font-medium">{{ teamCompletionRate }}% globalement</span>
      </div>
    </div>

    <!-- Slider de progression (apparaît au hover) -->
    <div v-if="!compact && myStatus === 'en_cours'" class="mb-3 opacity-0 group-hover:opacity-100 transition-opacity">
      <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">
        Ajuster ma progression :
      </label>
      <input
        type="range"
        min="0"
        max="100"
        :value="myProgression"
        @input="handleProgressionChange"
        @click.stop
        class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
        :style="progressionSliderStyle"
      />
    </div>

    <!-- Statistiques équipe (si collaborative) -->
    <div v-if="!compact && isCollaborative && teamStats" class="mb-3 p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
      <div class="flex items-center gap-2 text-xs">
        <div class="flex -space-x-2">
          <div
            v-for="assignee in visibleAssignees"
            :key="assignee.id"
            :title="`${assignee.nom} - ${getStatusLabel(assignee.pivot.statut_individuel)}`"
            class="w-6 h-6 rounded-full border-2 border-white dark:border-gray-800 flex items-center justify-center text-[10px] font-bold text-white"
            :style="{ 
              backgroundColor: stringToColor(assignee.nom),
              opacity: assignee.pivot.statut_individuel === 'termine' ? 1 : 0.6
            }"
          >
            {{ getInitials(assignee.nom) }}
            <span 
              v-if="assignee.pivot.statut_individuel === 'termine'"
              class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full border border-white dark:border-gray-800 flex items-center justify-center"
            >
              <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
            </span>
          </div>
          <div 
            v-if="remainingAssignees > 0"
            class="w-6 h-6 rounded-full bg-gray-300 dark:bg-gray-600 border-2 border-white dark:border-gray-800 flex items-center justify-center text-[9px] font-medium text-gray-700 dark:text-gray-300"
          >
            +{{ remainingAssignees }}
          </div>
        </div>
        <span class="text-gray-600 dark:text-gray-400">
          {{ teamStats.termine_count }}/{{ teamStats.total }} terminé{{ teamStats.termine_count > 1 ? 's' : '' }}
        </span>
      </div>
    </div>

    <!-- Mon résultat -->
    <div v-if="hasMyResult" class="mb-3 p-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
      <div class="flex items-center justify-between text-xs">
        <div class="flex items-center gap-2 text-green-700 dark:text-green-300">
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
          <span class="font-medium">Résultat soumis</span>
        </div>
        <div class="flex items-center gap-1">
          <span v-if="myResult.valide_par_n1" class="text-green-600 dark:text-green-400" title="Validé N1">✓ N1</span>
          <span v-if="myResult.valide_par_n2" class="text-green-600 dark:text-green-400" title="Validé N2">✓ N2</span>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div v-if="!compact" class="flex items-center gap-2 pt-2 border-t border-gray-200 dark:border-gray-700">
      <!-- Boutons de déplacement rapide -->
      <button
        v-if="myStatus !== 'en_cours'"
        @click.stop="quickMove('en_cours', 50)"
        class="flex-1 px-3 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
      >
        ▶ Démarrer
      </button>
      
      <button
        v-if="myStatus !== 'termine'"
        @click.stop="quickMove('termine', 100)"
        class="flex-1 px-3 py-1.5 text-xs font-medium text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition-colors"
      >
        ✓ Terminer
      </button>

      <!-- Bouton soumettre résultat -->
      <button
        v-if="canSubmitResult"
        @click.stop="$emit('submit-result', tache)"
        class="flex-1 px-3 py-1.5 text-xs font-medium text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/30 rounded-lg transition-colors flex items-center justify-center gap-1"
      >
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        {{ hasMyResult ? 'Modifier' : 'Résultat' }}
      </button>
    </div>

    <!-- Échéance -->
    <div v-if="tache.echeance && !compact" class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400 mt-2">
      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
      <span>{{ formatDate(tache.echeance) }}</span>
    </div>

    <!-- Menu contextuel -->
    <div
      v-if="showMenu"
      class="absolute right-2 top-12 z-50 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1"
      @click.stop
    >
      <button
        @click="handleViewDetails"
        class="w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
      >
        👁️ Voir détails
      </button>
      <button
        v-if="myStatus !== 'a_faire'"
        @click="quickMove('a_faire', 0)"
        class="w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
      >
        ⏪ Remettre à faire
      </button>
      <button
        v-if="canSubmitResult"
        @click="handleSubmitResult"
        class="w-full px-4 py-2 text-left text-sm text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/30 transition-colors"
      >
        📄 {{ hasMyResult ? 'Modifier résultat' : 'Soumettre résultat' }}
      </button>
    </div>

    <!-- Overlay pour fermer le menu -->
    <div
      v-if="showMenu"
      class="fixed inset-0 z-40"
      @click="showMenu = false"
    ></div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  tache: {
    type: Object,
    required: true
  },
  compact: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['view', 'move', 'submit-result', 'update-progression'])

// State
const showMenu = ref(false)

// Computed
const myStatus = computed(() => props.tache.my_status?.statut || props.tache.statut)
const myProgression = computed(() => props.tache.my_status?.progression || 0)
const myResult = computed(() => props.tache.my_result)
const hasMyResult = computed(() => !!myResult.value)
const teamStats = computed(() => props.tache.team_stats)
const isCollaborative = computed(() => teamStats.value && teamStats.value.total > 1)

const canSubmitResult = computed(() => {
  // ✅ CORRECTION: Permet soumission si en_cours OU termine
  return (myStatus.value === 'en_cours' || myStatus.value === 'termine')
})

const teamCompletionRate = computed(() => {
  if (!teamStats.value) return 0
  return teamStats.value.completion_percentage || 0
})

const cardBorderClass = computed(() => {
  if (props.tache.is_overdue && myStatus.value !== 'termine') {
    return 'border-red-300 dark:border-red-800 bg-red-50/50 dark:bg-red-900/10'
  }
  if (myStatus.value === 'termine') {
    return 'border-green-300 dark:border-green-800 bg-green-50/50 dark:bg-green-900/10'
  }
  if (myStatus.value === 'en_cours') {
    return 'border-blue-300 dark:border-blue-800 bg-blue-50/50 dark:bg-blue-900/10'
  }
  return 'border-gray-200 dark:border-gray-700'
})

const visibleAssignees = computed(() => {
  return props.tache.assignees?.slice(0, 4) || []
})

const remainingAssignees = computed(() => {
  const total = props.tache.assignees?.length || 0
  return Math.max(0, total - 4)
})

const progressionSliderStyle = computed(() => {
  return {
    background: `linear-gradient(to right, ${getStatusColor(myStatus.value)} 0%, ${getStatusColor(myStatus.value)} ${myProgression.value}%, #e5e7eb ${myProgression.value}%, #e5e7eb 100%)`
  }
})

// Methods
function toggleMenu() {
  showMenu.value = !showMenu.value
}

function handleViewDetails() {
  showMenu.value = false
  emit('view', props.tache)
}

function handleSubmitResult() {
  showMenu.value = false
  emit('submit-result', props.tache)
}

function quickMove(statut, progression) {
  showMenu.value = false
  emit('move', {
    tache: props.tache,
    statut,
    progression
  })
}

function handleProgressionChange(event) {
  const newProgression = parseInt(event.target.value)
  emit('update-progression', {
    tache: props.tache,
    progression: newProgression
  })
}

function getStatusColor(statut) {
  const colors = {
    'a_faire': '#6B7280',
    'en_cours': '#3B82F6',
    'termine': '#10B981'
  }
  return colors[statut] || '#6B7280'
}

function getStatusLabel(statut) {
  const labels = {
    'a_faire': 'À faire',
    'en_cours': 'En cours',
    'termine': 'Terminé'
  }
  return labels[statut] || statut
}

function getPriorityClass(priorite) {
  const classes = {
    'faible': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    'moyenne': 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
    'elevee': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
    'critique': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
  }
  return classes[priorite] || classes.moyenne
}

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', { 
    day: 'numeric', 
    month: 'short',
    year: new Date(date).getFullYear() !== new Date().getFullYear() ? 'numeric' : undefined
  })
}

function stringToColor(str) {
  let hash = 0
  for (let i = 0; i < str.length; i++) {
    hash = str.charCodeAt(i) + ((hash << 5) - hash)
  }
  return `hsl(${hash % 360}, 65%, 50%)`
}

function getInitials(name) {
  const parts = name.trim().split(' ')
  if (parts.length === 1) return parts[0].charAt(0).toUpperCase()
  return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase()
}
</script>

<style scoped>
/* Custom range slider */
input[type="range"]::-webkit-slider-thumb {
  appearance: none;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: white;
  border: 2px solid currentColor;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

input[type="range"]::-moz-range-thumb {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: white;
  border: 2px solid currentColor;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
</style>