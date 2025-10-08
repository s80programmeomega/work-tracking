<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
      <div>
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Projets</h1>
        <p class="text-gray-500 dark:text-gray-400">Gérer tous vos projets</p>
      </div>

      <button
        @click="showCreateForm = true"
        class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nouveau projet
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
      <div class="flex flex-col space-y-4 md:flex-row md:items-center md:space-y-0 md:space-x-4">
        <div class="flex-1">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Rechercher par nom ou description..."
            class="w-full max-w-sm px-4 py-2 text-sm border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
            @input="debouncedSearch"
          />
        </div>

        <select
          v-model="filters.status"
          class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
          @change="loadProjets"
        >
          <option value="">Tous les statuts</option>
          <option value="active">Actif</option>
          <option value="archived">Archivé</option>
          <option value="completed">Terminé</option>
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

    <!-- Projects Table -->
    <div v-else class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700">
            <tr>
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
              v-for="projet in projets"
              :key="projet.id"
              class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <td class="px-6 py-4">
                <div>
                  <div class="font-medium text-gray-900 dark:text-white">{{ projet.nom }}</div>
                  <div class="text-gray-500 dark:text-gray-400 text-xs">{{ projet.code }}</div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span v-if="projet.responsable">{{ projet.responsable.nom }}</span>
              </td>
              <td class="px-6 py-4">
                <span
                  :class="[
                    'px-2 py-1 text-xs font-medium rounded-full',
                    getStatusClass(projet.status)
                  ]"
                >
                  {{ getStatusLabel(projet.status) }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div
                      class="h-full bg-brand-500"
                      :style="{ width: `${projet.progression}%` }"
                    ></div>
                  </div>
                  <span class="text-xs">{{ projet.progression }}%</span>
                </div>
              </td>
              <td class="px-6 py-4">{{ projet.date_fin || '-' }}</td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <button
                    @click="$emit('view-projet', projet.id)"
                    class="px-3 py-1 text-sm text-white bg-brand-500 rounded hover:bg-brand-600"
                    title="Voir les détails"
                  >
                    <i class="fas fa-eye"></i> Voir
                  </button>
                  <button
                    @click="editProjet(projet)"
                    class="px-3 py-1 text-sm text-white bg-blue-500 rounded hover:bg-blue-600"
                    title="Modifier"
                  >
                    <i class="fas fa-edit"></i> Modifier
                  </button>
                  <button
                    @click="deleteProjet(projet)"
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
      <div v-if="!loading && projets.length === 0" class="text-center py-12">
        <p class="text-gray-500 dark:text-gray-400">Aucun projet trouvé</p>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        <div class="text-sm text-gray-500 dark:text-gray-400">
          Affichage de {{ (pagination.current_page - 1) * pagination.per_page + 1 }}
          à {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}
          sur {{ pagination.total }} projets
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
    <ProjetForm
      v-if="showCreateForm"
      @close="showCreateForm = false"
      @saved="onProjetCreated"
    />

    <!-- Edit Form Modal -->
    <ProjetForm
      v-if="showEditForm"
      :projet="editingProjet"
      @close="showEditForm = false; editingProjet = null"
      @saved="onProjetUpdated"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useProjets } from '@/composables/useProjets'
import ProjetForm from './ProjetForm.vue'

const emit = defineEmits(['view-projet'])

const { fetchProjets, projets, loading, deleteProjet: deletePro, pagination } = useProjets()

const filters = ref({
  search: '',
  status: '',
  page: 1
})

const showCreateForm = ref(false)
const showEditForm = ref(false)
const editingProjet = ref(null)

const loadProjets = async () => {
  await fetchProjets(filters.value)
  console.log('📄 Pagination:', pagination.value)
}

const editProjet = (projet) => {
  editingProjet.value = projet
  showEditForm.value = true
}

let searchTimeout
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    loadProjets()
  }, 500)
}

const resetFilters = () => {
  filters.value = {
    search: '',
    status: '',
    page: 1
  }
  loadProjets()
}

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    filters.value.page = page
    loadProjets()
  }
}

const visiblePages = computed(() => {
  const pages = []
  const current = pagination.value.current_page
  const total = pagination.value.last_page

  // Show 5 pages at a time
  let start = Math.max(1, current - 2)
  let end = Math.min(total, start + 4)

  // Adjust start if we're near the end
  if (end - start < 4) {
    start = Math.max(1, end - 4)
  }

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }

  return pages
})

const deleteProjet = async (projet) => {
  if (confirm(`Êtes-vous sûr de vouloir supprimer le projet "${projet.nom}" ?`)) {
    try {
      await deletePro(projet.id)
      loadProjets()
    } catch (error) {
      alert('Erreur lors de la suppression du projet')
    }
  }
}

const onProjetCreated = () => {
  showCreateForm.value = false
  loadProjets()
}

const onProjetUpdated = () => {
  showEditForm.value = false
  editingProjet.value = null
  loadProjets()
}

const getStatusLabel = (status) => {
  const labels = {
    active: 'Actif',
    archived: 'Archivé',
    completed: 'Terminé'
  }
  return labels[status] || status
}

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    archived: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    completed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

onMounted(() => {
  loadProjets()
})
</script>
