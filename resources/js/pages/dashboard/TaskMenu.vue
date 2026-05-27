<!-- resources/js/pages/dashboard/TaskMenu.vue -->
<template>
  <div class="relative">
    <button
      @click="toggleMenu"
      class="p-1 text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300 transition-colors rounded hover:bg-gray-100 dark:hover:bg-gray-600"
    >
      <HorizontalDots class="w-4 h-4" />
    </button>

    <!-- Dropdown Menu -->
    <div
      v-if="isOpen"
      v-click-outside="closeMenu"
      class="absolute right-0 top-full mt-1 w-48 bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 z-50 py-1"
    >
      <!-- View Details -->
      <button
        @click="viewTask"
        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
      >
        <EyeIcon class="w-4 h-4" />
        Voir détails
      </button>

      <!-- Edit Task -->
      <button
        @click="editTask"
        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
      >
        <PencilIcon class="w-4 h-4" />
        Modifier
      </button>

      <!-- Duplicate Task -->
      <button
        @click="duplicateTask"
        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
      >
        <CopyIcon class="w-4 h-4" />
        Dupliquer
      </button>

      <!-- Change Status -->
      <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
      
      <div class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400">
        Changer le statut
      </div>
      
      <button
        v-for="status in statusOptions"
        :key="status.value"
        @click="changeStatus(status.value)"
        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
      >
        <div class="w-2 h-2 rounded-full" :class="status.color"></div>
        {{ status.label }}
      </button>

      <!-- Delete Task -->
      <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
      
      <button
        @click="deleteTask"
        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
      >
        <TrashIcon class="w-4 h-4" />
        Supprimer
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  HorizontalDots,
  EyeIcon,
  PencilIcon,
  CopyIcon,
  TrashIcon
} from '@/icons'

const props = defineProps({
  task: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update'])

const router = useRouter()
const isOpen = ref(false)

const statusOptions = [
  { value: 'active', label: 'En cours', color: 'bg-green-500' },
  { value: 'pending', label: 'En attente', color: 'bg-yellow-500' },
  { value: 'completed', label: 'Terminé', color: 'bg-blue-500' },
  { value: 'archived', label: 'Archivé', color: 'bg-gray-500' }
]

const toggleMenu = () => {
  isOpen.value = !isOpen.value
}

const closeMenu = () => {
  isOpen.value = false
}

const viewTask = () => {
  closeMenu()
  router.push({ name: 'TaskDetail', params: { id: props.task.id } })
}

const editTask = () => {
  closeMenu()
  emit('update', { isEditing: true })
}

const duplicateTask = async () => {
  closeMenu()
  try {
    // Implémenter la logique de duplication
    console.log('Duplicating task:', props.task.id)
  } catch (error) {
    console.error('Error duplicating task:', error)
  }
}

const changeStatus = (newStatus) => {
  closeMenu()
  emit('update', { status: newStatus })
}

const deleteTask = () => {
  closeMenu()
  emit('update', { isDeleting: true })
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