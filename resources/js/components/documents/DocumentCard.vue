<template>
  <div
    class="group relative rounded-3 border border-gray-200 bg-white p-5 transition-all dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-gray-700"
  >
    <!-- Preview Area -->
    <div
      @click="$emit('view')"
      class="relative mb-4 aspect-square cursor-pointer overflow-hidden rounded-3 bg-gray-50 dark:bg-gray-800/50">
      <!-- Image Preview -->
      <img
        v-if="document.is_image && document.thumbnail_url"
        :src="document.thumbnail_url"
        :alt="document.nom"
        class="h-full w-full object-cover transition-transform "
      />

      <!-- File Icon -->
      <div v-else class="flex h-full w-full items-center justify-center">
        <component
          :is="fileIcon"
          class="h-20 w-20 text-gray-400 transition-transform "
        />
      </div>

      <!-- Overlay on Hover -->
      <div class="absolute inset-0 bg-black/0 transition-colors group-hover:bg-black/10" />

      <!-- Quick Actions Overlay -->
      <div class="absolute inset-0 flex items-center justify-center gap-2 opacity-0 transition-opacity group-hover:opacity-100">
        <button
          @click.stop="$emit('view')"
          class="rounded-3 bg-white/90 p-2 transition-transform dark:bg-gray-800/90"
          title="Voir"
        >
          <EyeIcon class="h-5 w-5 text-gray-700 dark:text-gray-300" />
        </button>
        <button
          @click.stop="$emit('download')"
          class="rounded-3 bg-white/90 p-2 transition-transform dark:bg-gray-800/90"
          title="Télécharger"
        >
          <ArrowDownTrayIcon class="h-5 w-5 text-gray-700 dark:text-gray-300" />
        </button>
      </div>

      <!-- Version Badge -->
      <div
        v-if="document.version > 1"
        class="absolute right-2 top-2 rounded-full bg-blue-600 px-2 py-1 text-xs font-medium text-white "
      >
        v{{ document.version }}
      </div>

    </div>

    <!-- Document Info -->
    <div class="space-y-3">
      <!-- Title -->
      <div>
        <h3
          @click="$emit('view')"
          class="cursor-pointer truncate text-sm font-semibold text-gray-900 hover:text-blue-600 dark:text-white dark:hover:text-blue-400"
          :title="document.nom"
        >
          {{ document.nom }}
        </h3>
        <p
          v-if="document.description"
          class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400"
          :title="document.description"
        >
          {{ document.description }}
        </p>
      </div>

      <!-- Metadata -->
      <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
        <span class="font-medium">{{ document.formatted_size }}</span>
        <div class="flex items-center gap-1">
          <ArrowDownTrayIcon class="h-3.5 w-3.5" />
          {{ document.download_count }}
        </div>
      </div>

      <!-- User & Date -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div
            class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-medium text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"
          >
            {{ getInitials(document.user.nom) }}
          </div>
          <span class="truncate text-xs text-gray-600 dark:text-gray-400">
            {{ document.user.nom }}
          </span>
        </div>
        <span class="text-xs text-gray-500 dark:text-gray-400">
          {{ formatDate(document.created_at) }}
        </span>
      </div>

      <!-- Actions -->
      <div class="flex gap-2 border-t border-gray-200 pt-3 dark:border-gray-800">
        <button
          @click="$emit('edit')"
          class="flex-1 rounded-3 border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
        >
          Modifier
        </button>
        <button
          @click="$emit('share')"
          class="flex-1 rounded-3 border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
        >
          Partager
        </button>
        <button
          @click="showMenu = !showMenu"
          class="rounded-3 border border-gray-300 bg-white px-2 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
        >
          <EllipsisVerticalIcon class="h-4 w-4" />
        </button>
      </div>

      <!-- Dropdown Menu -->
      <transition
        enter-active-class="transition ease-out duration-100"
        enter-from-class="transform opacity-0 scale-95"
        enter-to-class="transform opacity-100 scale-100"
        leave-active-class="transition ease-in duration-75"
        leave-from-class="transform opacity-100 scale-100"
        leave-to-class="transform opacity-0 scale-95"
      >
        <div
          v-if="showMenu"
          v-click-outside="() => showMenu = false"
          class="absolute right-5 bottom-20 z-10 mt-2 w-48 origin-bottom-right rounded-3 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-800 dark:ring-gray-700"
        >
          <div class="py-1">
            <button
              @click="handleVersion"
              class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
            >
              <DocumentDuplicateIcon class="h-4 w-4" />
              Gérer les versions
            </button>
            <button
              @click="handleDelete"
              class="flex w-full items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
            >
              <TrashIcon class="h-4 w-4" />
              Supprimer
            </button>
          </div>
        </div>
      </transition>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import {
  EyeIcon,
  ArrowDownTrayIcon,
  EllipsisVerticalIcon,
  DocumentDuplicateIcon,
  TrashIcon,
  DocumentTextIcon,
  PhotoIcon,
  FilmIcon,
  MusicalNoteIcon,
  ArchiveBoxIcon,
  DocumentIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  document: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['view', 'download', 'edit', 'delete', 'share', 'version'])

const showMenu = ref(false)

const fileIcon = computed(() => {
  const mimeType = props.document.mime_type
  if (mimeType.startsWith('image/')) return PhotoIcon
  if (mimeType === 'application/pdf') return DocumentTextIcon
  if (mimeType.startsWith('video/')) return FilmIcon
  if (mimeType.startsWith('audio/')) return MusicalNoteIcon
  if (mimeType.includes('zip') || mimeType.includes('compressed')) return ArchiveBoxIcon
  return DocumentIcon
})

const getInitials = (name) => {
  if (!name) return '?'
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .substring(0, 2)
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short'
  })
}

const handleVersion = () => {
  showMenu.value = false
  emit('version')
}

const handleDelete = () => {
  showMenu.value = false
  emit('delete')
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