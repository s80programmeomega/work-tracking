<!-- resources/js/components/taches/ValidationTaskCard.vue -->
<template>
  <div 
    class="rounded-3 border p-4 transition-all group"
    :class="cardClass"
  >
    <div class="flex items-start justify-between gap-4">
      <!-- Info principale -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 mb-2">
          <!-- Code -->
          <span 
            v-if="tache.code"
            class="px-2 py-0.5 text-xs font-mono font-medium rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400"
          >
            {{ tache.code }}
          </span>
          
          <!-- Priorité -->
          <span 
            class="px-2 py-0.5 text-xs font-medium rounded"
            :class="getPriorityClass(tache.priorite)"
          >
            {{ getPriorityIcon(tache.priorite) }} {{ tache.priorite_label }}
          </span>
          
          <!-- Badge validation en cours -->
          <span 
            class="px-2 py-0.5 text-xs font-medium rounded-full animate-pulse"
            :class="validationLevel === 'n1' 
              ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' 
              : 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'"
          >
            {{ validationLevel === 'n1' ? 'En attente N1' : 'En attente N2' }}
          </span>
        </div>

        <!-- Titre -->
        <h3 
          class="font-semibold text-gray-900 dark:text-white mb-1 cursor-pointer hover:text-brand-500 transition-colors"
          @click="$emit('view', tache)"
        >
          {{ tache.titre }}
        </h3>

        <!-- Activité & Projet -->
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
          <span class="font-medium">{{ tache.activite?.nom }}</span>
          <span v-if="tache.activite?.projet_nom" class="mx-1">•</span>
          <span v-if="tache.activite?.projet_nom">{{ tache.activite.projet_nom }}</span>
        </p>

        <!-- Description courte -->
        <p v-if="tache.description" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3">
          {{ tache.description }}
        </p>

        <!-- Infos complémentaires -->
        <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
          <!-- Assignés -->
          <div v-if="tache.assignees?.length" class="flex items-center gap-2">
            <div class="flex -space-x-2">
              <div 
                v-for="user in tache.assignees.slice(0, 3)" 
                :key="user.id"
                class="w-6 h-6 rounded-full border-2 border-white dark:border-gray-800 flex items-center justify-center text-[10px] font-medium"
                :style="{ backgroundColor: stringToColor(user.nom), color: '#fff' }"
                :title="user.nom"
              >
                {{ getInitials(user.nom) }}
              </div>
            </div>
            <span>{{ tache.assignees.map(u => u.nom).join(', ') }}</span>
          </div>

          <!-- Date échéance -->
          <div v-if="tache.echeance" class="flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span :class="{ 'text-red-600 dark:text-red-400 font-medium': tache.is_overdue }">
              {{ formatDate(tache.echeance) }}
            </span>
          </div>

          <!-- Sous-tâches count -->
          <div v-if="tache.sous_taches_count > 0" class="flex items-center gap-1" :title="`${tache.sous_taches_count} sous-tâche(s)`">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            <span>{{ tache.sous_taches_count }} ST</span>
          </div>

          <!-- Terminé le -->
          <div v-if="tache.date_fin_reelle" class="flex items-center gap-1">
            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>Terminé le {{ formatDate(tache.date_fin_reelle) }}</span>
          </div>
        </div>

        <!-- Info validation N1 (pour N2) -->
        <div 
          v-if="validationLevel === 'n2' && tache.validation?.n1_validated_at" 
          class="mt-3 p-2 rounded-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800"
        >
          <div class="flex items-center gap-2 text-sm text-green-700 dark:text-green-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>
              N1 validé par <strong>{{ tache.validation.n1_validated_by?.nom }}</strong>
              le {{ formatDateTime(tache.validation.n1_validated_at) }}
            </span>
          </div>
          <p v-if="tache.validation.n1_commentaire" class="text-xs text-green-600 dark:text-green-500 mt-1 italic">
            "{{ tache.validation.n1_commentaire }}"
          </p>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex flex-col gap-2">
        <!-- Bouton voir -->
        <button
          @click="$emit('view', tache)"
          class="p-2 rounded-3 border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
          title="Voir les détails"
        >
          <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
        </button>

        <!-- Bouton valider -->
        <button
          @click="$emit('validate', tache)"
          class="px-4 py-2 rounded-3 font-medium transition-all flex items-center gap-2 "
          :class="validationLevel === 'n1' 
            ? 'bg-green-500 hover:bg-green-600 text-white' 
            : 'bg-purple-500 hover:bg-purple-600 text-white'"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Valider {{ validationLevel.toUpperCase() }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  tache: {
    type: Object,
    required: true
  },
  validationLevel: {
    type: String,
    required: true,
    validator: v => ['n1', 'n2'].includes(v)
  }
})

defineEmits(['view', 'validate'])

const cardClass = computed(() => {
  return props.validationLevel === 'n1'
    ? 'border-green-200 dark:border-green-800 bg-white dark:bg-gray-800'
    : 'border-purple-200 dark:border-purple-800 bg-white dark:bg-gray-800'
})

function getPriorityClass(priorite) {
  const classes = {
    critique: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    elevee: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    moyenne: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    faible: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
  }
  return classes[priorite] || classes.moyenne
}

function getPriorityIcon(priorite) {
  const icons = { critique: '🔴', elevee: '🟠', moyenne: '🟡', faible: '🟢' }
  return icons[priorite] || '⚪'
}

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })
}

function formatDateTime(dateTime) {
  if (!dateTime) return ''
  return new Date(dateTime).toLocaleDateString('fr-FR', { 
    day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' 
  })
}

function getInitials(name) {
  if (!name) return ''
  const parts = name.trim().split(' ')
  return parts.length > 1 
    ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
    : parts[0][0].toUpperCase()
}

function stringToColor(str) {
  let hash = 0
  for (let i = 0; i < str.length; i++) {
    hash = str.charCodeAt(i) + ((hash << 5) - hash)
  }
  return `hsl(${hash % 360}, 65%, 50%)`
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