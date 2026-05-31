<!-- resources/js/components/activites/ActiviteList.vue -->
<template>
  <div class="space-y-6">
    <!-- Vue détail d'activité -->
    <div v-if="selectedActivity" class="mb-6">
      <ActiviteDetail
        :activite="selectedActivity"
        :taches="selectedActivityTaches"
        @go-back="selectedActivity = null"
        @edit-activite="editActivite"
        @view-tasks="viewActivityTasks"
        @create-task="createActivityTask"
      />
    </div>

    <!-- Liste des activités (masquée quand une activité est sélectionnée) -->
    <div v-else>
      <!-- Header -->
      <div class="flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
        <div>
          <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Activités</h1>
          <p class="text-gray-500 dark:text-gray-400">Gérer toutes vos activités</p>
        </div>

        <button
          @click="showCreateForm = true"
          class="inline-flex items-center justify-center gap-2 rounded-3 bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 transition-colors"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Nouvelle activité
        </button>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 p-4 rounded-3 border border-gray-200 dark:border-gray-700 space-y-3">
        <div class="flex flex-wrap items-center gap-4">
          <div class="flex-1">
            <input
              v-model="filters.search"
              type="text"
              placeholder="Rechercher par nom ou code..."
              class="w-full max-w-sm px-4 py-2 text-sm border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent"
              @input="debouncedSearch"
            />
          </div>

          <select
            v-model="filters.status"
            class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-700 rounded-3 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent"
            @change="loadActivites"
          >
            <option value="">Tous les statuts</option>
            <option value="active">Actif</option>
            <option value="archived">Archivé</option>
          </select>

          <button
            @click="resetFilters"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm border border-gray-300 dark:border-gray-700 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Réinitialiser
          </button>
        </div>

        <!-- Date range filter -->
        <DateRangeFilter @change="onDateRangeChange" />
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-500"></div>
        <span class="ml-2 text-gray-500 dark:text-gray-400">Chargement...</span>
      </div>

      <!-- Activities Table -->
      <div v-else class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 ">
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
              <tr>
                <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Activité</th>
                <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Projet</th>
                <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Responsable</th>
                <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Statut</th>
                <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Progression</th>
                <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Date fin</th>
                <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Actions</th>
              </tr>
            </thead>
            <tbody ref="tbodyRef">
              <tr
                v-for="activite in activites"
                :key="activite.id"
                class="stagger-item border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              >
                <td class="px-6 py-4">
                  <div class="cursor-pointer group" @click="viewActivityDetail(activite)">
                    <div class="font-medium text-gray-900 dark:text-white group-hover:text-brand-600 transition-colors">
                      {{ activite.nom }}
                    </div>
                    <div class="text-gray-500 dark:text-gray-400 text-xs">{{ activite.code }}</div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span v-if="activite.projet" class="text-gray-700 dark:text-gray-300">
                    {{ activite.projet.nom }}
                  </span>
                  <span v-else class="text-gray-400 dark:text-gray-500">-</span>
                </td>
                <td class="px-6 py-4">
                  <div v-if="activite.responsable" class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-brand-500 flex items-center justify-center text-white text-xs font-medium">
                      {{ getInitials(activite.responsable.nom) }}
                    </div>
                    <span class="text-gray-700 dark:text-gray-300">{{ activite.responsable.nom }}</span>
                  </div>
                  <span v-else class="text-gray-400 dark:text-gray-500">-</span>
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
                  <div class="flex items-center gap-2 min-w-32">
                    <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                      <div
                        class="h-full bg-brand-500 transition-all duration-500"
                        :style="{ width: `${activite.progression || 0}%` }"
                      ></div>
                    </div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 min-w-8">
                      {{ activite.progression || 0 }}%
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                  {{ activite.date_fin ? formatDate(activite.date_fin) : '-' }}
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <!-- Bouton Voir détails -->
                    <button
                      @click="viewActivityDetail(activite)"
                      class="inline-flex items-center gap-1 px-3 py-1.5 text-xs bg-blue-500 text-white rounded-3 hover:bg-blue-600 transition-colors"
                      title="Voir les détails"
                    >
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      Détails
                    </button>

                    <!-- Bouton Modifier -->
                    <button
                      @click="editActivite(activite)"
                      class="inline-flex items-center gap-1 px-3 py-1.5 text-xs bg-green-500 text-white rounded-3 hover:bg-green-600 transition-colors"
                      title="Modifier"
                    >
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                      Modifier
                    </button>

                    <!-- Bouton Supprimer -->
                    <button
                      @click="deleteActivite(activite)"
                      class="inline-flex items-center gap-1 px-3 py-1.5 text-xs bg-red-500 text-white rounded-3 hover:bg-red-600 transition-colors"
                      title="Supprimer"
                    >
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                      Supprimer
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && activites.length === 0" class="text-center py-12">
          <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
          <p class="text-gray-500 dark:text-gray-400 mb-2">Aucune activité trouvée</p>
          <p class="text-sm text-gray-400 dark:text-gray-500">
            {{ filters.search || filters.status ? 'Essayez de modifier vos critères de recherche' : 'Créez votre première activité pour commencer' }}
          </p>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700">
          <div class="text-sm text-gray-500 dark:text-gray-400">
            Affichage de {{ (pagination.current_page - 1) * pagination.per_page + 1 }}
            à {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}
            sur {{ pagination.total }} activités
          </div>
          <div class="flex gap-1">
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-700 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Précédent
            </button>
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="changePage(page)"
              :class="[
                'px-3 py-1.5 text-sm border rounded-3 transition-colors',
                page === pagination.current_page
                  ? 'bg-brand-500 text-white border-brand-500'
                  : 'border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300'
              ]"
            >
              {{ page }}
            </button>
            <button
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-700 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Suivant
            </button>
          </div>
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
import { useStagger } from '@/composables/useAnimations'
import ActiviteDetail from '@/pages/ActiviteDetail.vue'
import ActiviteForm from './ActiviteForm.vue'
import DateRangeFilter from '@/components/common/DateRangeFilter.vue'

const { fetchActivites, activites, loading, deleteActivite: deleteAct, pagination, getStatusLabel,getStatusClass ,fetchActiviteTaches } = useActivites()
const { staggerRef: tbodyRef, applyStagger } = useStagger(40)

const filters = ref({
  search: '',
  status: '',
  page: 1,
  date_from: null,
  date_to: null,
})

const showCreateForm = ref(false)
const showEditForm = ref(false)
const editingActivite = ref(null)

// États pour la gestion des détails d'activité
const selectedActivity = ref(null)
const selectedActivityTaches = ref([])

// ✅ NOUVEAU : Afficher les détails d'une activité
const viewActivityDetail = async (activite) => {
  try {
    console.log('Viewing activity details:', activite)
    selectedActivity.value = activite
    
    // Charger les tâches de l'activité
    if (activite.id) {
      const taches = await fetchActiviteTaches(activite.id)
      selectedActivityTaches.value = taches || []
      console.log('Loaded tasks:', selectedActivityTaches.value)
    }
  } catch (error) {
    console.error('Error loading activity details:', error)
    selectedActivityTaches.value = []
  }
}

// ✅ NOUVEAU : Voir les tâches d'une activité
const viewActivityTasks = (activityId) => {
  console.log('View tasks for activity:', activityId)
  // Vous pouvez naviguer vers une vue de tâches ou ouvrir un modal
  // Pour l'instant, on log simplement
  alert(`Voir les tâches de l'activité ${activityId}`)
}

// ✅ NOUVEAU : Créer une tâche pour une activité
const createActivityTask = (activityId) => {
  console.log('Create task for activity:', activityId)
  // Ouvrir un modal de création de tâche
  alert(`Créer une tâche pour l'activité ${activityId}`)
}

// ✅ NOUVEAU : Obtenir les initiales d'un nom
const getInitials = (name) => {
  if (!name) return '??'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

// ✅ NOUVEAU : Formater une date
const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

// Méthodes existantes (conservées)
const loadActivites = async () => {
  await fetchActivites(filters.value)
  applyStagger()
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

const onDateRangeChange = ({ from, to }) => {
  filters.value.date_from = from
  filters.value.date_to = to
  filters.value.page = 1
  loadActivites()
}

const resetFilters = () => {
  filters.value = {
    search: '',
    status: '',
    page: 1,
    date_from: null,
    date_to: null,
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
  if (confirm(`Êtes-vous sûr de vouloir supprimer l'activité "${activite.nom}" ? Cette action est irréversible.`)) {
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

 

onMounted(() => {
  loadActivites()
})

</script>