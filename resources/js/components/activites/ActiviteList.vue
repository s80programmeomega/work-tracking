<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
      <div>
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Activités</h1>
        <p class="text-gray-500 dark:text-gray-400">Gérer toutes vos activités</p>
      </div>

      <button
        @click="showCreateForm = true"
        class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nouvelle activité
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
      <div class="flex flex-col space-y-4 md:flex-row md:items-center md:space-y-0 md:space-x-4">
        <div class="flex-1">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Rechercher par nom ou code..."
            class="w-full max-w-sm px-4 py-2 text-sm border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            @input="debouncedSearch"
          />
        </div>

        <select
          v-model="filters.status"
          class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
          @change="loadActivites"
        >
          <option value="">Tous les statuts</option>
          <option value="active">Actif</option>
          <option value="archived">Archivé</option>
        </select>

        <button
          @click="resetFilters"
          class="inline-flex items-center gap-2 px-4 py-2 text-sm border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
          Réinitialiser
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-500"></div>
      <span class="ml-2">Chargement...</span>
    </div>

    <!-- Activities Table -->
    <div v-else class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3">Activité</th>
              <th class="px-6 py-3">Projet</th>
              <th class="px-6 py-3">Responsable</th>
              <th class="px-6 py-3">Statut</th>
              <th class="px-6 py-3">Progression</th>
              <th class="px-6 py-3">Date fin</th>
              <th class="px-6 py-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="activite in activites"
              :key="activite.id"
              class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <td class="px-6 py-4">
                <div>
                  <div class="font-medium text-gray-900 dark:text-white">{{ activite.nom }}</div>
                  <div class="text-gray-500 dark:text-gray-400 text-xs">{{ activite.code }}</div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span v-if="activite.projet">{{ activite.projet.nom }}</span>
              </td>
              <td class="px-6 py-4">
                <span v-if="activite.responsable">{{ activite.responsable.nom }}</span>
              </td>
              <td class="px-6 py-4">
                <span
                  :class="[
                    'px-2 py-1 text-xs font-medium rounded-full',
                    getStatusClass(activite.status)
                  ]"
                >
                  {{ getStatusLabel(activite.status) }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div
                      class="h-full bg-brand-500"
                      :style="{ width: `${activite.progression}%` }"
                    ></div>
                  </div>
                  <span class="text-xs">{{ activite.progression }}%</span>
                </div>
              </td>
              <td class="px-6 py-4">{{ activite.date_fin || '-' }}</td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <button
                    @click="editActivite(activite)"
                    class="px-3 py-1 text-sm text-white bg-blue-500 rounded hover:bg-blue-600"
                    title="Modifier"
                  >
                    <i class="fas fa-edit"></i> Modifier
                  </button>
                  <button
                    @click="deleteActivite(activite)"
                    class="px-3 py-1 text-sm text-white bg-red-500 rounded hover:bg-red-600"
                    title="Supprimer"
                  >
                    <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="!loading && activites.length === 0" class="text-center py-12">
        <p class="text-gray-500 dark:text-gray-400">Aucune activité trouvée</p>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        <div class="text-sm text-gray-500 dark:text-gray-400">
          Affichage de {{ (pagination.current_page - 1) * pagination.per_page + 1 }}
          à {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}
          sur {{ pagination.total }} activités
        </div>
        <div class="flex gap-2">
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="px-3 py-1 border border-gray-300 dark:border-gray-700 rounded hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Précédent
          </button>
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="changePage(page)"
            :class="[
              'px-3 py-1 border rounded',
              page === pagination.current_page
                ? 'bg-brand-500 text-white border-brand-500'
                : 'border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
            ]"
          >
            {{ page }}
          </button>
          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="px-3 py-1 border border-gray-300 dark:border-gray-700 rounded hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Suivant
          </button>
        </div>
      </div>
    </div>

    <!-- Create Form Modal -->
    <ActiviteForm
      v-if="showCreateForm"
      @close="showCreateForm = false"
      @saved="onActiviteCreated"
    />

    <!-- Edit Form Modal -->
    <ActiviteForm
      v-if="showEditForm"
      :activite="editingActivite"
      @close="showEditForm = false; editingActivite = null"
      @saved="onActiviteUpdated"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useActivites } from '@/composables/useActivites'
import ActiviteForm from './ActiviteForm.vue'

const { fetchActivites, activites, loading, deleteActivite: deleteAct, pagination, getStatusLabel } = useActivites()

const filters = ref({
  search: '',
  status: '',
  page: 1
})

const showCreateForm = ref(false)
const showEditForm = ref(false)
const editingActivite = ref(null)

const loadActivites = async () => {
  await fetchActivites(filters.value)
}

const editActivite = (activite) => {
  editingActivite.value = activite
  showEditForm.value = true
}

let searchTimeout
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    loadActivites()
  }, 500)
}

const resetFilters = () => {
  filters.value = {
    search: '',
    status: '',
    page: 1
  }
  loadActivites()
}

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    filters.value.page = page
    loadActivites()
  }
}

const visiblePages = computed(() => {
  const pages = []
  const current = pagination.value.current_page
  const total = pagination.value.last_page

  let start = Math.max(1, current - 2)
  let end = Math.min(total, start + 4)

  if (end - start < 4) {
    start = Math.max(1, end - 4)
  }

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }

  return pages
})

const deleteActivite = async (activite) => {
  if (confirm(`Êtes-vous sûr de vouloir supprimer l'activité "${activite.nom}" ?`)) {
    try {
      await deleteAct(activite.id)
      loadActivites()
    } catch (error) {
      alert('Erreur lors de la suppression de l\'activité')
    }
  }
}

const onActiviteCreated = () => {
  showCreateForm.value = false
  loadActivites()
}

const onActiviteUpdated = () => {
  showEditForm.value = false
  editingActivite.value = null
  loadActivites()
}

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    archived: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

onMounted(() => {
  loadActivites()
})
</script>
