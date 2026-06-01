<template>
  <div class="space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm">
      <router-link
        :to="{ name: 'Activites' }"
        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
      >
        {{ $t('document_pages.activities') }}
      </router-link>
      <ChevronRightIcon class="h-4 w-4 text-gray-400" />
      <router-link
        :to="{ name: 'activites.show', params: { id: activiteId } }"
        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
      >
        {{ activite?.nom || $t('document_pages.activities') }}
      </router-link>
      <ChevronRightIcon class="h-4 w-4 text-gray-400" />
      <span class="font-medium text-gray-900 dark:text-white">{{ $t('document_pages.documents') }}</span>
    </nav>

    <!-- Activite Info Card -->
    <div v-if="activite" class="rounded-3 border border-gray-200 p-6 dark:border-gray-800">
      <div class="flex items-start gap-4">
        <div class="flex h-14 w-14 items-center justify-center rounded-3 bg-green-600 text-white">
          <FolderIcon class="h-7 w-7" />
        </div>
        <div class="flex-1">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">
            {{ activite.nom }}
          </h2>
          <p v-if="activite.description" class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ activite.description }}
          </p>
          <div class="mt-3 flex flex-wrap gap-3 text-sm">
            <span class="inline-flex items-center gap-1 text-gray-600 dark:text-gray-400">
              <CalendarIcon class="h-4 w-4" />
              {{ formatDate(activite.date_debut) }} - {{ formatDate(activite.date_fin) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Documents Section -->
    <document-manager
      :documentable-type="'App\\Models\\Activite'"
      :documentable-id="activiteId"
      :entity-label="activite?.nom || 'cette activité'"
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

const activiteId = computed(() => route.params.activiteId)
const activite = ref(null)
const loading = ref(false)

const canUpload = computed(() => {
  if (!activite.value) return false
  const user = authStore.user
  if (activite.value.responsable_id === user.id) return true
  const member = activite.value.membres?.find(m => m.id === user.id)
  return member?.pivot?.can_upload_documents || false
})

const loadActivite = async () => {
  loading.value = true
  try {
    const response = await api.get(`/activites/${activiteId.value}`)
    activite.value = response.data.data
  } catch (error) {
    console.error('Erreur chargement activité:', error)
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
  loadActivite()
})
</script>
