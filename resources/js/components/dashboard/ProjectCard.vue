<!-- resources\js\components\dashboard\ProjectCard.vue -->
<template>
  <div class="group bg-white dark:bg-gray-800 rounded-3 p-4 border border-gray-200 dark:border-gray-700 transition-all duration-300 cursor-pointer">
    <div class="flex items-start justify-between mb-3">
      <div class="flex-1">
        <h4 class="font-semibold text-gray-900 dark:text-white group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors line-clamp-1">
          {{ project.name }}
        </h4>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ project.code }}</p>
      </div>
      <span class="text-xs font-medium px-2 py-1 rounded-full" :class="statusClass">
        {{ statusText }}
      </span>
    </div>

    <div class="flex items-center gap-2 mb-3 text-xs text-gray-500 dark:text-gray-400">
      <UsersIcon class="w-3 h-3" />
      <span>{{ project.team }} membres</span>
    </div>

    <div class="space-y-2">
      <div class="flex justify-between text-xs">
        <span class="font-medium">Progression</span>
        <span class="font-bold">{{ project.progress }}%</span>
      </div>
      <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
        <div class="h-2 rounded-full transition-all duration-300" :class="progressColor" :style="{ width: `${project.progress}%` }"></div>
      </div>
    </div>

    <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 dark:border-gray-600">
      <div class="flex -space-x-2">
        <div v-for="member in project.preview_members" :key="member.id" 
             class="w-6 h-6 rounded-full border-2 border-white dark:border-gray-800 bg-gray-300 flex items-center justify-center text-xs font-semibold text-gray-700">
          {{ member.initials }}
        </div>
      </div>
      <span class="text-xs text-gray-500 dark:text-gray-400">{{ project.due_date }}</span>
    </div>
  </div>
</template>

<script setup>
import { UsersIcon } from '@/icons'
import { computed } from 'vue'

const props = defineProps({
  project: {
    type: Object,
    required: true
  }
})

const statusClass = computed(() => {
  const classes = {
    active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    completed: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
  }
  return classes[props.project.status] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
})

const statusText = computed(() => {
  const texts = {
    active: 'En cours',
    pending: 'En attente',
    completed: 'Terminé'
  }
  return texts[props.project.status] || props.project.status
})

const progressColor = computed(() => {
  if (props.project.progress >= 100) return 'bg-green-500'
  if (props.project.progress >= 75) return 'bg-blue-500'
  if (props.project.progress >= 50) return 'bg-yellow-500'
  return 'bg-red-500'
})
</script>