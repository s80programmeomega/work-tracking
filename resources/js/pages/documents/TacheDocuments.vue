<template>
  <div class="space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm">
      <router-link
        :to="{ name: 'Toutes les taches' }"
        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
      >
        Tâches
      </router-link>
      <ChevronRightIcon class="h-4 w-4 text-gray-400" />
      <router-link
        :to="{ name: 'taches.show', params: { id: tacheId } }"
        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
      >
        {{ tache?.titre || 'Tâche' }}
      </router-link>
      <ChevronRightIcon class="h-4 w-4 text-gray-400" />
      <span class="font-medium text-gray-900 dark:text-white">Documents</span>
    </nav>

    <!-- Tache Info Card -->
    <div v-if="tache" class="rounded-3 border border-gray-200 p-6 dark:border-gray-800">
      <div class="flex items-start gap-4">
        <div class="flex h-14 w-14 items-center justify-center rounded-3 bg-purple-600 text-white">
          <FolderIcon class="h-7 w-7" />
        </div>
        <div class="flex-1">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">
            {{ tache.titre }}
          </h2>
          <p v-if="tache.description" class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ tache.description }}
          </p>
          <div class="mt-3 flex flex-wrap gap-3 text-sm">
            <span
              v-if="tache.echeance"
              class="inline-flex items-center gap-1 text-gray-600 dark:text-gray-400"
            >
              <CalendarIcon class="h-4 w-4" />
              Échéance : {{ formatDate(tache.echeance) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Documents Section -->
    <document-manager
      :documentable-type="'App\\Models\\Tache'"
      :documentable-id="tacheId"
      :entity-label="tache?.titre || 'cette tâche'"
      :can-upload="canUpload"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ChevronRightIcon, FolderIcon, CalendarIcon } from '@heroicons/vue/24/outline'
import DocumentManager from '@/components/documents/DocumentManager.vue'
import api from '@/api/axios'
import { useAuthStore } from '@/stores/authStore'

const route = useRoute()
const authStore = useAuthStore()

const tacheId = computed(() => route.params.tacheId)
const tache = ref(null)
const loading = ref(false)

const canUpload = computed(() => {
  if (!tache.value) return false
  const perms = tache.value.permissions
  return perms?.can_edit ?? false
})

const loadTache = async () => {
  loading.value = true
  try {
    const response = await api.get(`/taches/${tacheId.value}`)
    tache.value = response.data.data
  } catch (error) {
    console.error('Erreur chargement tâche:', error)
  } finally {
    loading.value = false
  }
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

onMounted(() => {
  loadTache()
})
</script>
