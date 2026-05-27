<!-- resources/js/components/taches/KanbanColumnPersonal.vue -->
<template>
  <div class="flex flex-col h-full bg-gray-50 dark:bg-gray-900/50 rounded-3 border border-gray-200 dark:border-gray-700">
    <!-- Header -->
    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
      <div class="flex items-center gap-3">
        <span class="text-2xl">{{ statusIcon }}</span>
        <div>
          <h3 class="font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400">Mon statut individuel</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <span class="px-2.5 py-1 text-sm font-bold rounded-full" :style="{ backgroundColor: statusColor, color: 'white' }">
          {{ taches.length }}
        </span>
      </div>
    </div>

    <!-- Tasks Container -->
    <div 
      class="flex-1 p-3 space-y-3 overflow-y-auto min-h-[400px]"
      @drop="handleDrop"
      @dragover.prevent
      @dragenter.prevent="isDragOver = true"
      @dragleave="isDragOver = false"
      :class="{ 'bg-blue-50 dark:bg-blue-900/20 border-2 border-dashed border-blue-400': isDragOver }"
    >
      <!-- Empty State -->
      <div v-if="taches.length === 0" class="flex flex-col items-center justify-center h-32 text-gray-400 dark:text-gray-600">
        <svg class="w-12 h-12 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
        <p class="text-sm">Aucune tâche</p>
      </div>

      <!-- Task Cards -->
      <div
        v-for="tache in taches"
        :key="tache.id"
        :draggable="true"
        @dragstart="handleDragStart($event, tache)"
        @dragend="isDragging = false"
        class="cursor-move transition-all"
        :class="{ 'opacity-50': isDragging && draggedTask?.id === tache.id }"
      >
       <TacheCardPersonal
        :tache="tache"
        @view="$emit('view-task', tache)"
        @move="handleQuickMove"
        @submit-result="$emit('submit-result', tache)"
        @update-progression="handleUpdateProgression"
        @edit="$emit('edit-task', tache)"   
      />
      </div>
    </div>

    <!-- Footer Stats -->
    <div v-if="taches.length > 0" class="p-3 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
      <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
        <span>{{ taches.length }} tâche{{ taches.length > 1 ? 's' : '' }}</span>
        <span v-if="overdueCount > 0" class="text-red-600 dark:text-red-400 font-medium flex items-center gap-1">
          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
          </svg>
          {{ overdueCount }} en retard
        </span>
        <span v-if="averageProgress > 0">{{ averageProgress }}% moy.</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import TacheCardPersonal from './TacheCardPersonal.vue'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  statut: {
    type: String,
    required: true
  },
  taches: {
    type: Array,
    default: () => []
  },
  statusColor: {
    type: String,
    default: '#6B7280'
  },
  statusIcon: {
    type: String,
    default: '📋'
  }
})

const emit = defineEmits(['move-card', 'view-task', 'submit-result', 'update-progression', 'edit-task'])

// State
const isDragOver = ref(false)
const isDragging = ref(false)
const draggedTask = ref(null)

// Computed
const overdueCount = computed(() => {
  return props.taches.filter(t => t.is_overdue && props.statut !== 'termine').length
})

const averageProgress = computed(() => {
  if (props.taches.length === 0) return 0
  const total = props.taches.reduce((sum, t) => {
    return sum + (t.my_status?.progression || 0)
  }, 0)
  return Math.round(total / props.taches.length)
})

// Methods
function handleDragStart(event, tache) {
  isDragging.value = true
  draggedTask.value = tache
  event.dataTransfer.effectAllowed = 'move'
  event.dataTransfer.setData('tache', JSON.stringify(tache))
}

function handleDrop(event) {
  event.preventDefault()
  isDragOver.value = false
  
  try {
    const tacheData = JSON.parse(event.dataTransfer.getData('tache'))
    const currentStatus = tacheData.my_status?.statut || tacheData.statut
    
    // Ne rien faire si déjà dans cette colonne
    if (currentStatus === props.statut) {
      return
    }

    // Calculer la progression suggérée
    let suggestedProgression = 0
    if (props.statut === 'en_cours') {
      suggestedProgression = 50
    } else if (props.statut === 'termine') {
      suggestedProgression = 100
    }

    // Émettre l'événement de déplacement
    emit('move-card', {
      tache: tacheData,
      newStatut: props.statut,
      progression: suggestedProgression,
      notes: null
    })

    console.log('✅ Carte déplacée:', tacheData.titre, '→', props.statut)
  } catch (err) {
    console.error('❌ Erreur drop:', err)
  }
}

function handleQuickMove({ tache, statut, progression }) {
  emit('move-card', {
    tache,
    newStatut: statut,
    progression,
    notes: null
  })
}

function handleUpdateProgression({ tache, progression }) {
  emit('move-card', {
    tache,
    newStatut: tache.my_status?.statut || tache.statut,
    progression,
    notes: null
  })
}
</script>

<style scoped>
/* Custom scrollbar */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #CBD5E0;
  border-radius: 3px;
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb {
  background: #4A5568;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #A0AEC0;
}
</style>