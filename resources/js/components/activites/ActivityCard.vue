<!-- resources\js\components\activites\ActivityCard.vue -->
<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
    <div class="flex items-start justify-between">
      <div class="flex-1">
        <div class="flex items-center gap-3 mb-2">
          <div
            class="w-1 h-12 rounded-full"
            :style="{ backgroundColor: activite.couleur || '#3b82f6' }"
          ></div>
          
          <div>
            <h3
              class="text-lg font-semibold text-gray-900 dark:text-white cursor-pointer hover:text-blue-600 transition-colors"
              @click="$emit('view', activite)"
            >
              {{ activite.nom }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ activite.code }}</p>
          </div>
        </div>

        <p v-if="activite.description" class="text-sm text-gray-600 dark:text-gray-300 mb-4">
          {{ truncate(activite.description, 100) }}
        </p>

        <div class="flex flex-wrap items-center gap-4 mb-4">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span class="text-sm text-gray-600 dark:text-gray-300">
              {{ activite.responsable?.nom }}
            </span>
          </div>

          <div v-if="activite.membres_count > 0" class="flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span class="text-sm text-gray-600 dark:text-gray-300">
              {{ activite.membres_count }} membre{{ activite.membres_count > 1 ? 's' : '' }}
            </span>
          </div>

          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span class="text-sm text-gray-600 dark:text-gray-300">
              {{ formatDate(activite.date_fin) }}
            </span>
          </div>
        </div>

        <!-- Progression -->
        <div class="mb-4">
          <div class="flex items-center justify-between mb-1">
            <span class="text-xs text-gray-600 dark:text-gray-400">Progression</span>
            <span class="text-xs font-semibold text-gray-900 dark:text-white">
              {{ activite.progression || 0 }}%
            </span>
          </div>
          <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
            <div
              class="h-full bg-blue-600 transition-all duration-500"
              :style="{ width: `${activite.progression || 0}%` }"
            ></div>
          </div>
        </div>

        <!-- Status badge -->
        <div class="flex items-center gap-2">
          <span
            :class="getStatusClass(activite.status)"
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
          >
            {{ getStatusLabel(activite.status) }}
          </span>
          
          <span
            v-if="activite.is_overdue"
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300"
          >
            En retard
          </span>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-2 ml-4">
        <button
          v-if="activite.user_permissions?.can_manage_members"
          @click="$emit('manage-members', activite)"
          class="p-2 text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-colors"
          title="Gérer les membres"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
        </button>

        <button
          v-if="activite.user_permissions?.can_edit"
          @click="$emit('edit', activite)"
          class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors"
          title="Modifier"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
        </button>

        <button
          v-if="activite.user_permissions?.can_delete"
          @click="$emit('delete', activite)"
          class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
          title="Supprimer"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  activite: {
    type: Object,
    required: true
  }
})

defineEmits(['view', 'edit', 'delete', 'manage-members'])

const truncate = (text, length) => {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}

const formatDate = (date) => {
  if (!date) return 'Non défini'
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300',
    archived: 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-300'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status) => {
  const labels = {
    active: 'Active',
    archived: 'Archivée'
  }
  return labels[status] || status
}
</script>