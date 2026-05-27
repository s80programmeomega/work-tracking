<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="'Gestion des Activités'" />
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          Tous les documents
        </h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
          Gérez tous vos documents à travers vos workspaces
        </p>
      </div>

      <div class="flex items-center gap-3">
        <button
          @click="showFilters = !showFilters"
          class="inline-flex items-center gap-2 rounded-3 border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
        >
          <FunnelIcon class="h-5 w-5" />
          Filtres
        </button>
      </div>
    </div>

    <!-- Filters Panel -->
    <transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 -translate-y-4"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-4"
    >
      <div v-if="showFilters" class="rounded-3 border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-4">
          <!-- Search -->
          <div class="lg:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Rechercher
            </label>
            <div class="relative">
              <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
              <input
                v-model="filters.search"
                type="text"
                placeholder="Nom ou description..."
                class="w-full rounded-3 border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
              />
            </div>
          </div>

          <!-- Type Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Type
            </label>
            <select
              v-model="filters.type"
              class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            >
              <option value="">Tous les types</option>
              <option value="image">Images</option>
              <option value="pdf">PDF</option>
              <option value="document">Documents</option>
              <option value="video">Vidéos</option>
              <option value="audio">Audio</option>
            </select>
          </div>

          <!-- User Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Uploadé par
            </label>
            <select
              v-model="filters.user_id"
              class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            >
              <option value="">Tous les utilisateurs</option>
              <option
                v-for="user in users"
                :key="user.id"
                :value="user.id"
              >
                {{ user.nom }}
              </option>
            </select>
          </div>
        </div>

        <div class="mt-4 flex justify-end gap-3">
          <button
            @click="resetFilters"
            class="rounded-3 border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            Réinitialiser
          </button>
          <button
            @click="applyFilters"
            class="rounded-3 bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
          >
            Appliquer
          </button>
        </div>
      </div>
    </transition>

    <!-- Documents List -->
    <div class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
      <document-list
        :documents="documents"
        :loading="loading"
        view-mode="list"
        @view="handleViewDocument"
        @download="handleDownloadDocument"
        @edit="handleEditDocument"
        @delete="handleDeleteDocument"
        @share="handleShareDocument"
        @version="handleCreateVersion"
      />

      <!-- Pagination -->
      <div v-if="pagination.total > pagination.per_page" class="mt-6 flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-800">
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Affichage de {{ pagination.from }} à {{ pagination.to }} sur {{ pagination.total }} documents
        </p>
        <div class="flex gap-2">
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            Précédent
          </button>
          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            Suivant
          </button>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <document-viewer-modal
      v-if="selectedDocument"
      :document="selectedDocument"
      :is-open="!!selectedDocument"
      @close="selectedDocument = null"
      @download="handleDownloadDocument"
    />
  </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useDocuments } from '@/composables/useDocuments'
import {
  FunnelIcon,
  MagnifyingGlassIcon
} from '@heroicons/vue/24/outline'

import DocumentList from '@/components/documents/DocumentList.vue'
// import DocumentViewerModal from '@/components/documents/DocumentViewer.vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import api from '@/api/axios'

const { documents, loading, downloadDocument } = useDocuments()

const showFilters = ref(false)
const selectedDocument = ref(null)
const users = ref([])

const filters = ref({
  search: '',
  type: '',
  user_id: ''
})

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0
})

const loadDocuments = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page,
      per_page: pagination.value.per_page,
      ...filters.value
    }

    const response = await api.get('/documents/search', { params })

    documents.value = response.data.data
    pagination.value = {
      current_page: response.data.meta.current_page,
      last_page: response.data.meta.last_page,
      per_page: response.data.meta.per_page,
      total: response.data.meta.total,
      from: (response.data.meta.current_page - 1) * response.data.meta.per_page + 1,
      to: Math.min(response.data.meta.current_page * response.data.meta.per_page, response.data.meta.total)
    }
  } catch (error) {
    console.error('Error loading documents:', error)
  } finally {
    loading.value = false
  }
}

const loadUsers = async () => {
  try {
    const response = await api.get('/users')
    users.value = response.data.data
  } catch (error) {
    console.error('Error loading users:', error)
  }
}

const applyFilters = () => {
  loadDocuments()
}

const resetFilters = () => {
  filters.value = {
    search: '',
    type: '',
    user_id: ''
  }
  loadDocuments()
}

const changePage = (page) => {
  loadDocuments(page)
}

const handleViewDocument = (document) => {
  selectedDocument.value = document
}

const handleDownloadDocument = async (document) => {
  try {
    await downloadDocument(document.id, document.nom)
  } catch (error) {
    console.error('Error downloading document:', error)
  }
}

const handleEditDocument = (document) => {
  // Implement edit logic
}

const handleDeleteDocument = async (document) => {
  // Implement delete logic
}

const handleShareDocument = (document) => {
  // Implement share logic
}

const handleCreateVersion = (document) => {
  // Implement version logic
}

onMounted(() => {
  loadDocuments()
  loadUsers()
})

watch(() => filters.value.search, (newValue) => {
  if (newValue.length === 0 || newValue.length >= 3) {
    loadDocuments()
  }
})
</script>