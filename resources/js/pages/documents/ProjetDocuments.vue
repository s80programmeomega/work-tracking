<template>
  <div class="space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm">
      <router-link
        :to="{ name: 'Projects' }"
        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
      >
        Projets
      </router-link>
      <ChevronRightIcon class="h-4 w-4 text-gray-400" />
      <router-link
        :to="{ name: 'projets.show', params: { id: projetId } }"
        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
      >
        {{ projet?.nom || 'Projet' }}
      </router-link>
      <ChevronRightIcon class="h-4 w-4 text-gray-400" />
      <span class="font-medium text-gray-900 dark:text-white">Documents</span>
    </nav>

    <!-- Project Info Card -->
    <div v-if="projet" class="rounded-3 border border-gray-200 p-6 dark:border-gray-800">
          <div class="flex items-start gap-4">
        <div class="flex h-14 w-14 items-center justify-center rounded-3 bg-blue-600 text-white">
          <FolderIcon class="h-7 w-7" />
        </div>
        <div class="flex-1">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">
            {{ projet.nom }}
          </h2>
          <p v-if="projet.description" class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ projet.description }}
          </p>
          <div class="mt-3 flex flex-wrap gap-3 text-sm">
            <span class="inline-flex items-center gap-1 text-gray-600 dark:text-gray-400">
              <UserIcon class="h-4 w-4" />
              {{ projet.responsable?.name }}
            </span>
            <span class="inline-flex items-center gap-1 text-gray-600 dark:text-gray-400">
              <CalendarIcon class="h-4 w-4" />
              {{ formatDate(projet.date_debut) }} - {{ formatDate(projet.date_fin) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Documents Section -->
    <document-manager
      :documentable-type="'App\\Models\\Projet'"
      :documentable-id="projetId"
      :entity-label="projet?.nom || 'ce projet'"
      :can-upload="canUpload"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import {
  ChevronRightIcon,
  FolderIcon,
  UserIcon,
  CalendarIcon
} from '@heroicons/vue/24/outline'
import DocumentManager from '@/components/documents/DocumentManager.vue'
import api from '@/api/axios'
import { useAuthStore } from '@/stores/authStore'

const route = useRoute()
const authStore = useAuthStore()

const projetId = computed(() => route.params.projetId)
const projet = ref(null)
const loading = ref(false)

// Check if user can upload documents
const canUpload = computed(() => {
  if (!projet.value) return false

  const user = authStore.user

  // Project manager can always upload
  if (projet.value.responsable_id === user.id) return true

  // Workspace admins can upload
  if (projet.value.workspace?.admins?.some(admin => admin.id === user.id)) return true

  // Members with specific permissions can upload
  const member = projet.value.membres?.find(m => m.id === user.id)
  return member?.pivot?.can_upload_documents || false
})

const loadProjet = async () => {
  loading.value = true
  try {
    const response = await api.get(`/projets/${projetId.value}`)
    projet.value = response.data.data
  } catch (error) {
    console.error('Error loading project:', error)
  } finally {
    loading.value = false
  }
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

onMounted(() => {
  loadProjet()
})
</script>