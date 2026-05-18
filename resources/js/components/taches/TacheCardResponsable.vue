<!-- resources/js/components/taches/TacheCardResponsable.vue -->
<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 hover:border-purple-400 transition-all shadow-sm hover:shadow-md">
    <!-- Header -->
    <div class="p-3 border-b border-gray-100 dark:border-gray-700">
      <div class="flex items-start justify-between gap-2 mb-2">
        <h4 class="font-semibold text-sm text-gray-900 dark:text-white line-clamp-2 flex-1">
          {{ tache.titre }}
        </h4>
        <span class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-xs font-bold rounded flex items-center gap-1">
          👑
        </span>
      </div>
      <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
        </svg>
        <span class="truncate">{{ tache.activite?.nom }}</span>
      </div>
    </div>

    <!-- Intervenants -->
    <div v-if="tache.assignees?.length" class="px-3 py-2 bg-gray-50 dark:bg-gray-900/50">
      <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
          Intervenants ({{ tache.assignees.length }})
        </span>
        <button 
          @click="showAll = !showAll"
          class="text-xs text-purple-600 hover:underline"
        >
          {{ showAll ? 'Masquer' : 'Voir' }}
        </button>
      </div>
      
      <div class="space-y-1">
        <div 
          v-for="assignee in displayed" 
          :key="assignee.id"
          class="flex items-center justify-between text-xs p-1.5 rounded bg-white dark:bg-gray-800"
        >
          <div class="flex items-center gap-2 flex-1 min-w-0">
            <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-xs font-bold">
              {{ assignee.nom.charAt(0) }}
            </div>
            <span class="truncate">{{ assignee.nom }}</span>
          </div>
          <span :class="getStatusClass(assignee)" class="px-2 py-0.5 rounded text-xs">
            {{ getStatusLabel(assignee) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="px-3 py-2 flex items-center justify-between text-xs border-t border-gray-100 dark:border-gray-700">
      <div class="flex items-center gap-3">
        <span v-if="tache.priorite">{{ getPriorityIcon(tache.priorite) }}</span>
        <span v-if="tache.echeance" :class="tache.is_overdue ? 'text-red-600 font-semibold' : 'text-gray-500'">
          {{ formatDate(tache.echeance) }}
        </span>
        <span
          v-if="tache.sous_taches_count > 0"
          class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium"
          :title="`${tache.sous_taches_count} sous-tâche(s)`"
        >
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
          </svg>
          {{ tache.sous_taches_count }} ST
        </span>
      </div>
      <div class="flex gap-1">
        <button @click.stop="$emit('view', tache)" class="p-1 hover:bg-gray-100 rounded">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
        </button>
        <button @click.stop="$emit('edit', tache)" class="p-1 hover:bg-gray-100 rounded">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Progress -->
    <div v-if="tache.taux_realisation != null" class="px-3 pb-3">
      <div class="flex justify-between text-xs mb-1">
        <span class="text-gray-600">Progression</span>
        <span class="font-semibold">{{ tache.taux_realisation }}%</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-1.5">
        <div :class="getProgressClass(tache.taux_realisation)" :style="{width: `${tache.taux_realisation}%`}" class="h-1.5 rounded-full"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  tache: { type: Object, required: true }
})

defineEmits(['view', 'edit'])

const showAll = ref(false)

const displayed = computed(() => {
  if (showAll.value || !props.tache.assignees) return props.tache.assignees || []
  return props.tache.assignees.slice(0, 3)
})

function getStatusLabel(assignee) {
  const s = assignee.pivot?.statut_individuel || 'a_faire'
  return { a_faire: 'À faire', en_cours: 'En cours', termine: 'OK' }[s] || s
}

function getStatusClass(assignee) {
  const s = assignee.pivot?.statut_individuel || 'a_faire'
  return {
    a_faire: 'bg-gray-100 text-gray-700',
    en_cours: 'bg-blue-100 text-blue-700',
    termine: 'bg-green-100 text-green-700'
  }[s] || 'bg-gray-100'
}

function getPriorityIcon(p) {
  return { faible: '🟢', moyenne: '🟡', elevee: '🟠', critique: '🔴' }[p] || '⚪'
}

function getProgressClass(p) {
  return p < 30 ? 'bg-red-500' : p < 70 ? 'bg-yellow-500' : 'bg-green-500'
}

function formatDate(d) {
  return d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' }) : ''
}
</script>