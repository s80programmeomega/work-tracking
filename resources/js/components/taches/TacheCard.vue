<!-- resources/js/components/taches/TacheCard.vue -->
<template>
 <div
    class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow cursor-move border border-gray-200 dark:border-gray-700 group"
    :style="{ borderLeftColor: tache.couleur, borderLeftWidth: '4px' }"
    :data-tache-id="tache.id"
  >
    <!-- Cover Image -->
     <div v-if="tache.cover_image" class="mb-3 -mx-4 -mt-4">
      <img
        :src="getImageUrl(tache.cover_image)"
        :alt="`Couverture de la tâche: ${tache.titre}`"
        class="w-full h-32 object-cover rounded-t-lg"
        @error="handleImageError"
        loading="lazy"
      />
    </div>

    <!-- Header with badges and menu -->
    <div class="flex items-start justify-between mb-2 gap-2">
      <div class="flex items-center gap-2 flex-wrap flex-1">
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
          {{ tache.priorite_icon }} {{ tache.priorite_label }}
        </span>

        <!-- Validation status badges -->
        <span
          v-if="tache.validation?.n2_validated_at"
          class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 flex items-center gap-1"
          title="Validation complète (N1 + N2)"
        >
          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
          Validé N2
        </span>
        <span
          v-else-if="tache.validation?.n1_validated_at"
          class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 flex items-center gap-1"
          title="Validation niveau 1" >
          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
          Validé N1
        </span>
        <span
          v-else-if="tache.statut === 'termine' && (tache.validation?.n1_required || tache.validation?.n2_required)"
          class="px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300 flex items-center gap-1"
          title="En attente de validation" >
          <svg class="w-3 h-3 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
          </svg>
          En attente
        </span>

        <!-- Overdue indicator -->
        <span
          v-if="tache.is_overdue"
          class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 flex items-center gap-1"
        >
          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
          En retard
        </span>

        <!-- Archived indicator -->
        <span
          v-if="tache.archive_status === 'archived'"
          class="px-2 py-1 text-xs font-medium rounded-full bg-gray-500 text-white"
        >
          Archivée
        </span>
      </div>

      <!-- Actions menu -->
      <div class="relative">
        <button
          @click.stop="showMenu = !showMenu"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity"
        >
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
          </svg>
        </button>

        <!-- Dropdown menu -->
        <div
          v-if="showMenu"
          v-click-outside="() => showMenu = false"
          class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-700 rounded-lg shadow-lg z-20 py-1 border border-gray-200 dark:border-gray-600"
        >
          <button
            @click.stop="$emit('view', tache)"
            class="w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2 text-gray-700 dark:text-gray-300"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            Voir les détails
          </button>

          <button
            v-if="tache.permissions?.can_edit"
            @click.stop="$emit('edit', tache)"
            class="w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2 text-gray-700 dark:text-gray-300"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Modifier
          </button>

          <button
            @click.stop="$emit('duplicate', tache)"
            class="w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2 text-gray-700 dark:text-gray-300"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            Dupliquer
          </button>

          <!-- Validation buttons -->
          <div v-if="canValidate" class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
          
          <button
            v-if="tache.permissions?.can_validate_n1 && tache.statut === 'termine' && !tache.validation?.n1_validated_at"
            @click.stop="$emit('validate', tache)"
            class="w-full px-4 py-2 text-left text-sm text-green-600 dark:text-green-400 hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2 font-medium"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Valider N1
          </button>

          <button
            v-if="tache.permissions?.can_validate_n2 && tache.validation?.n1_validated_at && !tache.validation?.n2_validated_at"
            @click.stop="$emit('validate', tache)"
            class="w-full px-4 py-2 text-left text-sm text-purple-600 dark:text-purple-400 hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2 font-medium"
          >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            Valider N2 (Final)
          </button>

          <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>

          <button
            @click.stop="$emit('archive', tache)"
            class="w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2 text-gray-700 dark:text-gray-300"
            :class="{ 'text-blue-600 dark:text-blue-400': tache.archive_status === 'archived' }"
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
            v-if="tache.permissions?.can_edit"
            @click.stop="handleDelete"
            class="w-full px-4 py-2 text-left text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2 font-medium"
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
      class="font-semibold text-gray-900 dark:text-white mb-2 cursor-pointer hover:text-brand-500 transition-colors"
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
        <span class="font-medium">{{ tache.taux_realisation }}%</span>
      </div>
      <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
        <div
          class="h-2 rounded-full transition-all duration-300"
          :class="getProgressColor(tache.taux_realisation)"
          :style="{ width: `${tache.taux_realisation}%` }"
        ></div>
      </div>
    </div>

    <!-- Time Tracking -->
    <div
      v-if="tache.estimated_hours || tache.actual_hours"
      class="mb-3 flex items-center gap-2 text-xs"
    >
      <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <span class="text-gray-600 dark:text-gray-400">
        <span v-if="tache.estimated_hours">Est: {{ tache.estimated_hours }}h</span>
        <span v-if="tache.actual_hours" :class="getTimeVarianceClass()" class="ml-1">
          / Réel: {{ tache.actual_hours }}h
        </span>
      </span>
    </div>

    <!-- Footer with assignees and due date -->
     <div class="flex items-center justify-between text-xs">
      <!-- Assignees -->
      <div class="flex -space-x-2">
        <div
          v-for="assignee in tache.assignees.slice(0, 3)"
          :key="assignee.id"
          class="relative group/avatar"
          :title="assignee.nom"
        >
          <div
            v-if="assignee.avatar"
            class="w-7 h-7 rounded-full border-2 border-white dark:border-gray-800 overflow-hidden ring-1 ring-gray-200 dark:ring-gray-700"
          >
            <img 
              :src="getImageUrl(assignee.avatar)" 
              :alt="assignee.nom" 
              class="w-full h-full object-cover"
              @error="handleAvatarError(assignee)"
              loading="lazy"
            />
          </div>
          <div
            v-else
            class="w-7 h-7 rounded-full border-2 border-white dark:border-gray-800 flex items-center justify-center text-xs font-medium ring-1 ring-gray-200 dark:ring-gray-700"
            :style="{ backgroundColor: stringToColor(assignee.nom), color: '#fff' }"
          >
            {{ getInitials(assignee.nom) }}
          </div>
        </div>
        <div
          v-if="tache.assignees.length > 3"
          class="w-7 h-7 rounded-full border-2 border-white dark:border-gray-800 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center text-xs font-medium ring-1 ring-gray-200 dark:ring-gray-700"
          :title="`+${tache.assignees.length - 3} autres`"
        >
          +{{ tache.assignees.length - 3 }}
        </div>
      </div>

      <!-- Due date -->
      <div v-if="tache.echeance" class="flex items-center gap-1">
        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <span
          class="text-gray-600 dark:text-gray-400"
          :class="{ 'text-red-600 dark:text-red-400 font-medium': tache.is_overdue }"
        >
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

const showMenu = ref(false)

// ✅ NOUVEAU : Méthodes pour gérer les URLs d'images
const getImageUrl = (path) => {
  if (!path) return ''
  
  // Si c'est déjà une URL complète
  if (path.startsWith('http://') || path.startsWith('https://')) {
    return path
  }
  
  // Si c'est un chemin de stockage Laravel
  if (path.startsWith('storage/')) {
    return `${import.meta.env.VITE_APP_URL || window.location.origin}/${path}`
  }
  
  // Si c'est un chemin relatif sans storage/
  if (path.startsWith('task-covers/') || path.startsWith('avatars/')) {
    return `${import.meta.env.VITE_APP_URL || window.location.origin}/storage/${path}`
  }
  
  // Pour les chemins absolus depuis la racine
  return `${import.meta.env.VITE_APP_URL || window.location.origin}${path.startsWith('/') ? path : '/' + path}`
}
const handleImageError = (event) => {
  event.target.src = '/images/default-cover.png' // Image de couverture par défaut
}
// Computed
const canValidate = computed(() => {
  return props.tache.permissions?.can_validate_n1 || props.tache.permissions?.can_validate_n2
})

const handleAvatarError = (assignee) => {
  console.warn('❌ Erreur chargement avatar:', assignee.nom)
  // L'avatar sera remplacé par les initiales automatiquement
}
const getInitials = (name) => {
  if (!name) return ''
  const parts = name.trim().split(' ')
  if (parts.length === 1) return parts[0].charAt(0).toUpperCase()
  return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase()
}
// Methods
const getPriorityClass = (priorite) => {
  const classes = {
    faible: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    moyenne: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
    elevee: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
    critique: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
  }
  return classes[priorite] || classes.moyenne
}

const getProgressColor = (progress) => {
  if (progress < 30) return 'bg-red-500'
  if (progress < 70) return 'bg-amber-500'
  return 'bg-green-500'
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
  return ''
}

const stringToColor = (str) => {
  let hash = 0
  for (let i = 0; i < str.length; i++) {
    hash = str.charCodeAt(i) + ((hash << 5) - hash)
  }
  const hue = hash % 360
  return `hsl(${hue}, 65%, 50%)`
}

const handleDelete = () => {
  showMenu.value = false
  emit('delete', props.tache)
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