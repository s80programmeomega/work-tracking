<template>
  <div
    class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow cursor-move border border-gray-200 dark:border-gray-700"
    :style="{ borderLeftColor: tache.couleur, borderLeftWidth: '4px' }"
  >
    <!-- Cover Image -->
    <div v-if="tache.cover_image" class="mb-3 -mx-4 -mt-4">
      <img
        :src="tache.cover_image"
        alt="Cover"
        class="w-full h-32 object-cover rounded-t-lg"
      />
    </div>

    <!-- Header with priority and menu -->
    <div class="flex items-start justify-between mb-2">
      <div class="flex items-center gap-2 flex-wrap">
        <!-- Task Code -->
        <span
          v-if="tache.code"
          class="px-2 py-1 text-xs font-mono font-medium rounded bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300"
        >
          {{ tache.code }}
        </span>

        <!-- Priority badge -->
        <span
          class="px-2 py-1 text-xs font-medium rounded"
          :class="getPriorityClass(tache.priorite)"
        >
          {{ tache.priorite_label }}
        </span>

        <!-- Overdue indicator -->
        <span
          v-if="tache.is_overdue"
          class="px-2 py-1 text-xs font-medium rounded bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300"
        >
          En retard
        </span>

        <!-- Archived indicator -->
        <span
          v-if="tache.archive_status === 'archived'"
          class="px-2 py-1 text-xs font-medium rounded bg-gray-500 text-white"
        >
          Archivée
        </span>
      </div>

      <!-- Actions menu -->
      <div class="relative">
        <button
          @click.stop="showMenu = !showMenu"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
          </svg>
        </button>

        <!-- Dropdown menu -->
        <div
          v-if="showMenu"
          v-click-outside="() => showMenu = false"
          class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-700 rounded-lg shadow-lg z-10 py-1"
        >
          <button
            @click.stop="$emit('edit', tache)"
            class="w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Modifier
          </button>
          <button
            @click.stop="$emit('duplicate', tache)"
            class="w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            Dupliquer
          </button>
          <button
            v-if="!tache.validation_superieur"
            @click.stop="$emit('validate', tache)"
            class="w-full px-4 py-2 text-left text-sm text-green-600 hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Valider
          </button>
          <button
            @click.stop="$emit('archive', tache)"
            class="w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2"
            :class="{ 'text-blue-600': tache.archive_status === 'archived' }"
          >
            <svg v-if="tache.archive_status === 'archived'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
            </svg>
            {{ tache.archive_status === 'archived' ? 'Désarchiver' : 'Archiver' }}
          </button>
          <button
            @click.stop="$emit('delete', tache)"
            class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Supprimer
          </button>
        </div>
      </div>
    </div>

    <!-- Title -->
    <h3
      class="font-semibold text-gray-900 dark:text-white mb-2 cursor-pointer hover:text-brand-500"
      @click="$emit('view', tache)"
    >
      {{ tache.titre }}
    </h3>

    <!-- Description (truncated) -->
    <p
      v-if="tache.description"
      class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2"
    >
      {{ tache.description }}
    </p>

    <!-- Labels -->
    <div v-if="tache.labels?.length > 0" class="mb-3 flex flex-wrap gap-1">
      <span
        v-for="label in tache.labels"
        :key="label.id"
        class="px-2 py-1 text-xs font-medium rounded-md"
        :style="{
          backgroundColor: label.couleur + '20',
          color: label.couleur,
          border: `1px solid ${label.couleur}`
        }"
        :title="label.description"
      >
        {{ label.nom }}
      </span>
    </div>

    <!-- Progress bar -->
    <div v-if="tache.taux_realisation > 0" class="mb-3">
      <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
        <span>Progression</span>
        <span>{{ tache.taux_realisation }}%</span>
      </div>
      <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
        <div
          class="bg-brand-500 h-2 rounded-full transition-all"
          :style="{ width: `${tache.taux_realisation}%` }"
        ></div>
      </div>
    </div>

    <!-- Time Tracking -->
    <div
      v-if="tache.estimated_hours || tache.actual_hours"
      class="mb-3 flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <span v-if="tache.estimated_hours">
        Est: {{ tache.estimated_hours }}h
      </span>
      <span v-if="tache.actual_hours" :class="getTimeVarianceClass()">
        / Réel: {{ tache.actual_hours }}h
      </span>
    </div>

    <!-- Footer with assignees and due date -->
    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
      <!-- Assignees -->
      <div class="flex -space-x-2">
        <div
          v-for="assignee in tache.assignees.slice(0, 3)"
          :key="assignee.id"
          class="relative"
          :title="assignee.nom"
        >
          <div
            v-if="assignee.avatar"
            class="w-6 h-6 rounded-full border-2 border-white dark:border-gray-800 overflow-hidden"
          >
            <img :src="assignee.avatar" :alt="assignee.nom" class="w-full h-full object-cover" />
          </div>
          <div
            v-else
            class="w-6 h-6 rounded-full border-2 border-white dark:border-gray-800 bg-brand-500 text-white flex items-center justify-center text-xs font-medium"
          >
            {{ assignee.nom.charAt(0).toUpperCase() }}
          </div>
        </div>
        <div
          v-if="tache.assignees.length > 3"
          class="w-6 h-6 rounded-full border-2 border-white dark:border-gray-800 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center text-xs font-medium"
        >
          +{{ tache.assignees.length - 3 }}
        </div>
      </div>

      <!-- Due date -->
      <div v-if="tache.echeance" class="flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <span :class="{ 'text-red-600 font-medium': tache.is_overdue }">
          {{ formatDate(tache.echeance) }}
        </span>
      </div>
    </div>

    <!-- Validated indicator -->
    <div
      v-if="tache.validation_superieur"
      class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700 flex items-center gap-1 text-xs text-green-600 dark:text-green-400"
    >
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
      </svg>
      <span>Validée</span>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  tache: {
    type: Object,
    required: true
  }
})

defineEmits(['view', 'edit', 'duplicate', 'archive', 'delete', 'validate'])

// Debug: Log labels
console.log('TacheCard - tache:', props.tache.titre, 'labels:', JSON.parse(JSON.stringify(props.tache.labels)))

const showMenu = ref(false)

const getPriorityClass = (priorite) => {
  const classes = {
    faible: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    moyenne: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
    elevee: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
    critique: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
  }
  return classes[priorite] || classes.moyenne
}

const formatDate = (date) => {
  if (!date) return ''
  const d = new Date(date)
  const today = new Date()
  const diffDays = Math.ceil((d - today) / (1000 * 60 * 60 * 24))

  if (diffDays === 0) return "Aujourd'hui"
  if (diffDays === 1) return 'Demain'
  if (diffDays === -1) return 'Hier'

  return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}

const getTimeVarianceClass = () => {
  if (!props.tache.estimated_hours || !props.tache.actual_hours) return ''

  const variance = props.tache.actual_hours - props.tache.estimated_hours
  if (variance > 0) return 'text-red-600 dark:text-red-400 font-medium' // Over budget
  if (variance < 0) return 'text-green-600 dark:text-green-400 font-medium' // Under budget
  return '' // On budget
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

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
