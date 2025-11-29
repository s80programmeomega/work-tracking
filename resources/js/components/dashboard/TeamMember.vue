<!-- resources/js/components/dashboard/TeamMember.vue -->
<template>
  <div class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200 group">
    <!-- Avatar -->
    <div class="flex-shrink-0">
      <div v-if="member.avatar" class="w-10 h-10 rounded-full overflow-hidden ring-2 ring-white dark:ring-gray-700">
        <img :src="member.avatar" :alt="memberName" class="w-full h-full object-cover">
      </div>
      <div v-else class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-semibold text-sm ring-2 ring-white dark:ring-gray-700">
        {{ memberInitials }}
      </div>
    </div>

    <!-- Info -->
    <div class="flex-1 min-w-0">
      <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">
        {{ memberName }}
      </h4>
      <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
        {{ member.email }}
      </p>
    </div>

    <!-- Stats -->
    <div class="flex-shrink-0 text-right">
      <div class="text-sm font-bold text-gray-900 dark:text-white">
        {{ member.taches_count || 0 }}
      </div>
      <div class="text-xs text-gray-500 dark:text-gray-400">
        tâches
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  member: {
    type: Object,
    required: true
  }
})

const memberName = computed(() => {
  return `${props.member.prenom} ${props.member.nom}`.trim()
})

const memberInitials = computed(() => {
  return `${props.member.prenom?.[0] || ''}${props.member.nom?.[0] || ''}`.toUpperCase() || 'U'
})
</script>