<template>
  <div 
    class="tache-card bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:shadow-lg transition-all cursor-pointer"
    :class="{ 'border-l-4': tache.priorite }"
    :style="{ borderLeftColor: tache.priorite_color }"
    @click="$emit('view', tache)"
  >
    <!-- Header -->
    <div class="flex items-start justify-between mb-3">
      <div class="flex-1">
        <div class="flex items-center gap-2 mb-1">
          <span class="text-xs font-mono text-gray-500">{{ tache.code }}</span>
          <span 
            class="px-2 py-0.5 text-xs rounded-full"
            :class="getStatusClass(tache.statut)"
          >
            {{ tache.statut_label }}
          </span>
          <span 
            class="px-2 py-0.5 text-xs rounded-full"
            :style="{ 
              backgroundColor: tache.priorite_color + '20', 
              color: tache.priorite_color 
            }"
          >
            {{ tache.priorite_icon }} {{ tache.priorite_label }}
          </span>
        </div>
        <h3 class="font-semibold text-gray-900 dark:text-white line-clamp-2">
          {{ tache.titre }}
        </h3>
      </div>

      <!-- Actions dropdown -->
      <div class="relative" @click.stop>
        <button
          @click="showActions = !showActions"
          class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
        >
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
          </svg>
        </button>
        
        <div
          v-if="showActions"
          v-click-outside="() => showActions = false"
          class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-10"
        >
          <button
            v-if="tache.permissions?.can_edit"
            @click="$emit('edit', tache)"
            class="w-full px-4 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Modifier
          </button>
          
          <button
            @click="$emit('duplicate', tache)"
            class="w-full px-4 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            Dupliquer
          </button>

          <button
            v-if="canValidate"
            @click="$emit('validate', tache)"
            class="w-full px-4 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2 text-green-600"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Valider
          </button>

          <hr class="my-1 border-gray-200 dark:border-gray-700">

          <button
            @click="$emit('archive', tache)"
            class="w-full px-4 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2 text-gray-600"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
            </svg>
            Archiver
          </button>

          <button
            v-if="tache.permissions?.can_delete"
            @click="$emit('delete', tache)"
            class="w-full px-4 py-2 text-left hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2 text-red-600"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Supprimer
          </button>
        </div>
      </div>
    </div>

    <!-- ✅ NOUVEAU : Mon statut vs Statut global (si multi-assignés) -->
    <div v-if="tache.my_status && tache.assignees?.length > 1" class="mb-3 p-2 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
      <div class="flex items-center justify-between text-xs">
        <div class="flex items-center gap-2">
          <span class="text-gray-500">Mon statut:</span>
          <span 
            class="px-2 py-0.5 rounded-full font-medium"
            :class="getStatusClass(tache.my_status.statut)"
          >
            {{ tache.my_status.statut_label }}
          </span>
          <span class="text-gray-400">{{ tache.my_status.progression }}%</span>
        </div>
        
        <div class="flex items-center gap-2">
          <span class="text-gray-500">Équipe:</span>
          <span 
            class="px-2 py-0.5 rounded-full font-medium"
            :class="getStatusClass(tache.statut)"
          >
            {{ tache.statut_label }}
          </span>
        </div>
      </div>
    </div>

    <!-- ✅ NOUVEAU : Indicateur de collaboration -->
    <div v-if="tache.collaboration" class="mb-3">
      <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
        <span>Progression équipe</span>
        <span class="font-medium">{{ Math.round(tache.collaboration.avg_progression) }}%</span>
      </div>
      <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
        <div 
          class="h-full bg-gradient-to-r from-blue-500 to-green-500 transition-all duration-300"
          :style="{ width: tache.collaboration.avg_progression + '%' }"
        ></div>
      </div>
      <div class="flex items-center gap-3 mt-2 text-xs">
        <span class="text-green-600">✓ {{ tache.collaboration.completed_count }}</span>
        <span class="text-blue-600">→ {{ tache.collaboration.in_progress_count }}</span>
        <span class="text-gray-400">○ {{ tache.collaboration.not_started_count }}</span>
      </div>
    </div>

    <!-- Description -->
    <p v-if="tache.description" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3">
      {{ tache.description }}
    </p>

    <!-- Labels -->
    <div v-if="tache.labels?.length" class="flex flex-wrap gap-1 mb-3">
      <span
        v-for="label in tache.labels.slice(0, 3)"
        :key="label.id"
        class="px-2 py-0.5 text-xs rounded-full"
        :style="{ 
          backgroundColor: label.couleur + '20', 
          color: label.couleur 
        }"
      >
        {{ label.nom }}
      </span>
      <span v-if="tache.labels.length > 3" class="px-2 py-0.5 text-xs text-gray-500">
        +{{ tache.labels.length - 3 }}
      </span>
    </div>

    <!-- Footer -->
    <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
      <!-- ✅ AMÉLIORÉ : Avatars avec indicateurs de statut -->
      <div class="flex -space-x-2">
        <div
          v-for="assignee in (tache.assignees_status || tache.assignees)?.slice(0, 3)"
          :key="assignee.id"
          class="relative group"
        >
          <img
            :src="assignee.avatar || '/default-avatar.png'"
            :alt="assignee.nom"
            class="w-8 h-8 rounded-full border-2 bg-white dark:bg-gray-800 transition-all"
            :class="getAssigneeBorderClass(assignee)"
            :title="getAssigneeTooltip(assignee)"
          />
          <!-- Indicateur de complétion -->
          <div
            v-if="assignee.is_completed || assignee.statut === 'termine'"
            class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white dark:border-gray-800 flex items-center justify-center"
          >
            <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
            </svg>
          </div>
          <!-- Indicateur en cours -->
          <div
            v-else-if="assignee.statut === 'en_cours'"
            class="absolute -bottom-1 -right-1 w-4 h-4 bg-blue-500 rounded-full border-2 border-white dark:border-gray-800"
          >
            <div class="w-full h-full rounded-full bg-blue-400 animate-ping"></div>
          </div>
        </div>
        <span
          v-if="(tache.assignees_status || tache.assignees)?.length > 3"
          class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 border-2 border-white dark:border-gray-800 flex items-center justify-center text-xs font-medium text-gray-600 dark:text-gray-400"
        >
          +{{ (tache.assignees_status || tache.assignees).length - 3 }}
        </span>
      </div>

      <!-- Date échéance -->
      <div class="flex items-center gap-2 text-xs">
        <svg 
          v-if="tache.echeance" 
          class="w-4 h-4"
          :class="tache.is_overdue ? 'text-red-500' : 'text-gray-400'"
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <span :class="tache.is_overdue ? 'text-red-500 font-medium' : 'text-gray-500'">
          {{ formatDate(tache.echeance) }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  tache: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['view', 'edit', 'duplicate', 'archive', 'delete', 'validate'])

const showActions = ref(false)

const canValidate = computed(() => {
  const validation = props.tache.validation
  if (!validation) return false

  const needsN1 = validation.n1_required && !validation.n1_validated_at
  const needsN2 = validation.n2_required && validation.n1_validated_at && !validation.n2_validated_at

  return (needsN1 && props.tache.permissions?.can_validate_n1) ||
         (needsN2 && props.tache.permissions?.can_validate_n2)
})

function getStatusClass(statut) {
  const classes = {
    'a_faire': 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    'en_cours': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    'termine': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
  }
  return classes[statut] || classes['a_faire']
}

function getAssigneeBorderClass(assignee) {
  if (assignee.is_completed || assignee.statut === 'termine') {
    return 'border-green-500 ring-2 ring-green-200'
  }
  if (assignee.statut === 'en_cours') {
    return 'border-blue-500 ring-2 ring-blue-200'
  }
  return 'border-gray-300 dark:border-gray-600'
}

function getAssigneeTooltip(assignee) {
  if (assignee.statut_label) {
    return `${assignee.nom} - ${assignee.statut_label} (${assignee.progression || 0}%)`
  }
  return assignee.nom
}

function formatDate(date) {
  if (!date) return 'Pas de date'
  const d = new Date(date)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
}

// Click outside directive
const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = (event) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value()
      }
    }
    document.addEventListener('click', el.clickOutsideEvent)
  },
  unmounted(el) {
    document.removeEventListener('click', el.clickOutsideEvent)
  }
}
</script>