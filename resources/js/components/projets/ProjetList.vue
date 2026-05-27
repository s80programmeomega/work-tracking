<!-- resources/js/components/projets/ProjetList.vue -->
<template>
  <div class="space-y-6">
    <!-- Header avec actions - Conditionnel -->
    <!-- <div v-if="showHeader" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
          Projets
        </h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ filteredProjets.length }} projet(s) trouvé(s)
        </p>
      </div>
      
      <button
        @click="openCreateModal" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white rounded-3 hover:bg-brand-700 transition-colors" >
        <PlusIcon class="w-5 h-5" />
        Nouveau projet 
      </button>

    </div> -->

   <!-- Filtres et recherche - Conditionnel -->
    <div v-if="showFilters" class="bg-gray-50 dark:bg-gray-800/50 rounded-3 p-4 space-y-4">
      <!-- Barre de recherche -->
      <div class="relative">
        <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
        <input
          v-model="searchTerm"
          type="text"
          placeholder="Rechercher un projet par nom, code ou description..."
          class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
        />
      </div>

      <!-- Filtres -->
      <div class="flex flex-wrap gap-3">
        <!-- Filtre Status -->
        <select
          v-model="filters.status"
          class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-brand-500"
        >
          <option value="all">Tous les statuts</option>
          <option value="active">Actifs</option>
          <option value="completed">Terminés</option>
          <option value="archived">Archivés</option>
        </select>

        <!-- Filtre Visibilité -->
        <select
          v-model="filters.visibility"
          class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-brand-500"
        >
          <option value="all">Toutes visibilités</option>
          <option value="public">Public</option>
          <option value="team">Équipe</option>
          <option value="private">Privé</option>
        </select>

        <!-- Filtre Favoris -->
        <button
          @click="filters.favorites = !filters.favorites"
          :class="[
            'inline-flex items-center gap-2 px-3 py-2 rounded-3 text-sm font-medium transition-colors',
            filters.favorites
              ? 'bg-brand-600 text-white'
              : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'
          ]"
        >
          <StarIcon :class="filters.favorites ? 'fill-current' : ''" class="w-4 h-4" />
          Favoris
        </button>

        <!-- Filtre En retard -->
        <button
          @click="filters.overdue = !filters.overdue"
          :class="[
            'inline-flex items-center gap-2 px-3 py-2 rounded-3 text-sm font-medium transition-colors',
            filters.overdue
              ? 'bg-red-600 text-white'
              : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'
          ]"
        >
          <AlertCircleIcon class="w-4 h-4" />
          En retard
        </button>

        <!-- Reset filtres -->
        <button
          v-if="hasActiveFilters"
          @click="resetFilters"
          class="inline-flex items-center gap-2 px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
        >
          <XIcon class="w-4 h-4" />
          Réinitialiser
        </button>
      </div>

      <!-- Vue (Grid/List) -->
      <div class="flex items-center gap-2 border-t border-gray-200 dark:border-gray-700 pt-4">
        <span class="text-sm text-gray-600 dark:text-gray-400">Vue :</span>
        <button
          @click="viewMode = 'grid'"
          :class="[
            'p-2 rounded-3 transition-colors',
            viewMode === 'grid'
              ? 'bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400'
              : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
          ]"
        >
          <GridIcon class="w-5 h-5" />
        </button>
        <button
          @click="viewMode = 'list'"
          :class="[
            'p-2 rounded-3 transition-colors',
            viewMode === 'list'
              ? 'bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400'
              : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
          ]"
        >
          <ListIcon class="w-5 h-5" />
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-600"></div>
    </div>

    <!-- Empty state -->
    <div v-else-if="filteredProjets.length === 0"
      class="text-center py-12 bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700" >
      <FolderOpenIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
        Aucun projet trouvé
      </h3>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        {{ searchTerm || hasActiveFilters ? 'Essayez de modifier vos critères de recherche' : 'Commencez par créer votre premier projet' }}
      </p>
      <button v-if="!searchTerm && !hasActiveFilters"
        @click="openCreateModal"
        class="mt-6 inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white rounded-3 hover:bg-brand-700 transition-colors" >
        <PlusIcon class="w-5 h-5" />
        Créer un projet
      </button>
    </div>

    <!-- Grid View -->
    <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="projet in filteredProjets"
        :key="projet.id"
        class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 transition-all cursor-pointer group"
      >
        <!-- Card Header -->
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <div
                  class="w-3 h-3 rounded-full"
                  :style="{ backgroundColor: projet.couleur || '#3B82F6' }"
                ></div>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                  {{ projet.code }}
                </span>
              </div>
              <h3
                @click="$emit('view-projet', projet.id)"
                class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors"
              >
                {{ projet.nom }}
              </h3>
            </div>
            
            <div class="flex items-center gap-2">
              <button
                @click.stop="toggleFavorite(projet)"
                class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
              >
                <StarIcon
                  :class="projet.is_favorite ? 'fill-yellow-400 text-yellow-400' : 'text-gray-400'"
                  class="w-5 h-5"
                />
              </button>
              
              <div class="relative">
                <button
                  @click.stop="toggleMenu(projet.id)"
                  class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700" >
                  <MoreVerticalIcon class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                </button>
                
                <!-- Dropdown Menu -->
                <div
                  v-if="activeMenuId === projet.id"
                  v-click-outside="() => activeMenuId = null"
                  class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-700 rounded-3 border border-gray-200 dark:border-gray-600 z-10" >
                  <button
                    @click.stop="editProjet(projet)"
                    class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-t-lg" >
                    <EditIcon class="w-4 h-4" />
                    Modifier
                  </button>
                  <button
                    @click.stop="duplicateProjet(projet)"
                    class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600"
                  >
                    <CopyIcon class="w-4 h-4" />
                    Dupliquer
                  </button>
                  <button
                    v-if="projet.status === 'active'"
                    @click.stop="archiveProjet(projet)"
                    class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600"
                  >
                    <ArchiveIcon class="w-4 h-4" />
                    Archiver
                  </button>
                  <button
                    v-if="projet.status === 'archived'"
                    @click.stop="unarchiveProjet(projet)"
                    class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600"
                  >
                    <ArchiveIcon class="w-4 h-4" />
                    Désarchiver
                  </button>
                  <button
                    @click.stop="deleteProjet(projet)"
                    class="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-b-lg" >
                    <TrashIcon class="w-4 h-4" />
                    Supprimer
                  </button>
                </div>
              </div>
            </div>
          </div>

          <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-4">
            {{ projet.description || 'Aucune description' }}
          </p>

          <!-- Status Badge -->
          <div class="flex items-center gap-2">
            <span
              :class="[
                'inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium',
                getStatusColor(projet.status)
              ]"
            >
              <component :is="getStatusIcon(projet.status)" class="w-3 h-3" />
              {{ getStatusLabel(projet.status) }}
            </span>
            
            <span
              v-if="projet.is_overdue && projet.status === 'active'"
              class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400"
            >
              <AlertCircleIcon class="w-3 h-3" />
              En retard
            </span>
          </div>
        </div>

        <!-- Card Body -->
        <div class="p-6 space-y-4">
          <!-- Progress Bar -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                Progression
              </span>
              <span class="text-xs font-bold text-gray-900 dark:text-white">
                {{ projet.progression || 0 }}%
              </span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
              <div
                class="bg-brand-600 h-2 rounded-full transition-all duration-300"
                :style="{ width: `${projet.progression || 0}%` }"
              ></div>
            </div>
          </div>

          <!-- Stats -->
          <div class="grid grid-cols-3 gap-4 text-center">
            <div>
              <div class="text-lg font-bold text-gray-900 dark:text-white">
                {{ projet.activites_count || 0 }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400">
                Activités
              </div>
            </div>
            <div>
              <div class="text-lg font-bold text-gray-900 dark:text-white">
                {{ projet.taches_count || 0 }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400">
                Tâches
              </div>
            </div>
            <div>
              <div class="text-lg font-bold text-gray-900 dark:text-white">
                {{ projet.member_count || 0 }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400">
                Membres
              </div>
            </div>
          </div>

          <!-- Dates -->
          <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-1">
              <CalendarIcon class="w-4 h-4" />
              {{ formatDate(projet.date_debut) }}
            </div>
            <div class="flex items-center gap-1">
              <CalendarIcon class="w-4 h-4" />
              {{ formatDate(projet.date_fin) }}
            </div>
          </div>

          <!-- Responsable -->
          <div class="flex items-center gap-2 pt-2">
            <div
              v-if="projet.responsable?.avatar"
              class="w-6 h-6 rounded-full overflow-hidden"
            >
              <img :src="projet.responsable.avatar" :alt="projet.responsable.nom" class="w-full h-full object-cover" />
            </div>
            <div
              v-else
              class="w-6 h-6 rounded-full bg-brand-600 flex items-center justify-center text-white text-xs font-medium"
            >
              {{ getInitials(projet.responsable?.nom) }}
            </div>
            <span class="text-xs text-gray-600 dark:text-gray-400">
              {{ projet.responsable?.nom || 'Non assigné' }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- List View -->
    <div v-else class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
              Projet
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
              Responsable
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
              Progression
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
              Dates
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
              Statut
            </th>
            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          <tr
            v-for="projet in filteredProjets"
            :key="projet.id"
            @click="$emit('view-projet', projet.id)"
            class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer"
          >
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center gap-3">
                <div
                  class="w-3 h-3 rounded-full flex-shrink-0"
                  :style="{ backgroundColor: projet.couleur || '#3B82F6' }"
                ></div>
                <div>
                  <div class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ projet.nom }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ projet.code }}
                  </div>
                </div>
                <StarIcon
                  v-if="projet.is_favorite"
                  class="w-4 h-4 fill-yellow-400 text-yellow-400"
                />
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center gap-2">
                <div
                  v-if="projet.responsable?.avatar"
                  class="w-8 h-8 rounded-full overflow-hidden"
                >
                  <img :src="projet.responsable.avatar" :alt="projet.responsable.nom" class="w-full h-full object-cover" />
                </div>
                <div
                  v-else
                  class="w-8 h-8 rounded-full bg-brand-600 flex items-center justify-center text-white text-xs font-medium"
                >
                  {{ getInitials(projet.responsable?.nom) }}
                </div>
                <div class="text-sm text-gray-900 dark:text-white">
                  {{ projet.responsable?.nom || 'Non assigné' }}
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center gap-3">
                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 max-w-[120px]">
                  <div
                    class="bg-brand-600 h-2 rounded-full transition-all"
                    :style="{ width: `${projet.progression || 0}%` }"
                  ></div>
                </div>
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ projet.progression || 0 }}%
                </span>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
              <div>{{ formatDate(projet.date_debut) }}</div>
              <div>{{ formatDate(projet.date_fin) }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="[
                  'inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium',
                  getStatusColor(projet.status)
                ]"
              >
                <component :is="getStatusIcon(projet.status)" class="w-3 h-3" />
                {{ getStatusLabel(projet.status) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <div class="flex items-center justify-end gap-2">
                <button
                  @click.stop="toggleFavorite(projet)"
                  class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-600"
                >
                  <StarIcon
                    :class="projet.is_favorite ? 'fill-yellow-400 text-yellow-400' : 'text-gray-400'"
                    class="w-4 h-4"
                  />
                </button>
                <button
                  @click.stop="editProjet(projet)"
                  class="text-brand-600 hover:text-brand-900 dark:text-brand-400"
                >
                  Modifier
                </button>
                <button @click.stop="deleteProjet(projet)"
                  class="text-red-600 hover:text-red-900 dark:text-red-400" >
                  Supprimer
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Project Form Modal -->
     <ProjetFormModal
      v-if="showFormModal"
      :projet="selectedProjet"
      :workspace-id="workspaceId"  
      @close="closeFormModal"
      @saved="handleProjetSaved"
    />
        
    <!-- Modal Confirmation Delete -->
    <ConfirmModal
      v-if="showDeleteModal"
      title="Supprimer le projet"
      :message="`Êtes-vous sûr de vouloir supprimer le projet ${projetToDelete?.nom} ?`"
      confirm-text="Supprimer"
      confirm-class="bg-red-600 hover:bg-red-700"
      @confirm="confirmDelete"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useProjets } from '@/composables/useProjets'
import {
  PlusIcon,
  SearchIcon,
  StarIcon,
  AlertCircleIcon,
  XIcon,
  GridIcon,
  ListIcon,
  FolderOpenIcon,
  MoreVerticalIcon,
  EditIcon,
  CopyIcon,
  ArchiveIcon,
  TrashIcon,
  CalendarIcon,
  CheckCircleIcon,
  ClockIcon,
  TrendingUpIcon
} from '@/icons'
import ProjetFormModal from './ProjetFormModal.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const emit = defineEmits(['view-projet'])

// Props
const props = defineProps({
  workspaceId: {
    type: [Number, String],
    default: null
  },
  limit: {
    type: Number,
    default: null
  },
  showHeader: {
    type: Boolean,
    default: true
  },
  showFilters: {
    type: Boolean,
    default: true
  }
})

const {
  loading,
  projets,
  fetchProjets,
  deleteProjet: deleteProjetService,
  toggleFavorite: toggleFavoriteService,
  archiveProjet: archiveProjetService,
  unarchiveProjet: unarchiveProjetService,
  cloneProjet: cloneProjetService
} = useProjets()

// State
const searchTerm = ref('')
const viewMode = ref('grid')
const activeMenuId = ref(null)
const showFormModal = ref(false)
const showDeleteModal = ref(false)
const selectedProjet = ref(null)
const projetToDelete = ref(null)

const filters = ref({
  status: 'all',
  visibility: 'all',
  favorites: false,
  overdue: false
})

// Computed
const hasActiveFilters = computed(() => {
  return filters.value.status !== 'all' ||
    filters.value.visibility !== 'all' ||
    filters.value.favorites ||
    filters.value.overdue
})

const filteredProjets = computed(() => {
  let result = projets.value

  // Search
  if (searchTerm.value) {
    const term = searchTerm.value.toLowerCase()
    result = result.filter(p =>
      p.nom.toLowerCase().includes(term) ||
      p.code.toLowerCase().includes(term) ||
      p.description?.toLowerCase().includes(term)
    )
  }

  // Status filter
  if (filters.value.status !== 'all') {
    result = result.filter(p => p.status === filters.value.status)
  }

  // Visibility filter
  if (filters.value.visibility !== 'all') {
    result = result.filter(p => p.visibility === filters.value.visibility)
  }

  // Favorites filter
  if (filters.value.favorites) {
    result = result.filter(p => p.is_favorite)
  }

  // Overdue filter
  if (filters.value.overdue) {
    result = result.filter(p => p.is_overdue)
  }

  return result
})

// Méthode fetchProjets exposée
const fetchProjetsList = async () => {
  const filters = {
    workspace_id: props.workspaceId
  }
  await fetchProjets(filters)
}

// Watch workspaceId prop changes (when passed explicitly)
watch(() => props.workspaceId, (newWorkspaceId) => {
  if (newWorkspaceId) {
    fetchProjetsList()
  }
})

// Methods
const openCreateModal = () => {
  selectedProjet.value = null
  showFormModal.value = true
}

const editProjet = (projet) => {
  selectedProjet.value = projet
  showFormModal.value = true
  activeMenuId.value = null
}

const closeFormModal = () => {
  showFormModal.value = false
  selectedProjet.value = null
}

const handleProjetSaved = () => {
  fetchProjets()
  closeFormModal()
}

const deleteProjet = (projet) => {
  projetToDelete.value = projet
  showDeleteModal.value = true
  activeMenuId.value = null
}

const confirmDelete = async () => {
  try {
    await deleteProjetService(projetToDelete.value.id)
    showDeleteModal.value = false
    projetToDelete.value = null
    await fetchProjets()
  } catch (error) {
    console.error('Error deleting projet:', error)
  }
}

const toggleFavorite = async (projet) => {
  try {
    await toggleFavoriteService(projet.id)
    await fetchProjets()
  } catch (error) {
    console.error('Error toggling favorite:', error)
  }
}

const archiveProjet = async (projet) => {
  try {
    await archiveProjetService(projet.id)
    activeMenuId.value = null
    await fetchProjets()
  } catch (error) {
    console.error('Error archiving projet:', error)
  }
}

const unarchiveProjet = async (projet) => {
  try {
    await unarchiveProjetService(projet.id)
    activeMenuId.value = null
    await fetchProjets()
  } catch (error) {
    console.error('Error unarchiving projet:', error)
  }
}

const duplicateProjet = async (projet) => {
  try {
    await cloneProjetService(projet.id, {
      nom: `${projet.nom} (Copie)`
    })
    activeMenuId.value = null
    await fetchProjets()
  } catch (error) {
    console.error('Error duplicating projet:', error)
  }
}

const toggleMenu = (projetId) => {
  activeMenuId.value = activeMenuId.value === projetId ? null : projetId
}

const resetFilters = () => {
  filters.value = {
    status: 'all',
    visibility: 'all',
    favorites: false,
    overdue: false
  }
  searchTerm.value = ''
}

const getStatusColor = (status) => {
  const colors = {
    active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    completed: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    archived: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
  }
  return colors[status] || colors.pending
}

const getStatusLabel = (status) => {
  const labels = {
    active: 'Actif',
    completed: 'Terminé',
    archived: 'Archivé',
    pending: 'En attente'
  }
  return labels[status] || status
}

const getStatusIcon = (status) => {
  const icons = {
    active: TrendingUpIcon,
    completed: CheckCircleIcon,
    archived: ArchiveIcon,
    pending: ClockIcon
  }
  return icons[status] || ClockIcon
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getInitials = (name) => {
  if (!name) return 'U'
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

// Lifecycle
onMounted(() => {
  fetchProjetsList()
})

// Exposer la méthode fetchProjets
defineExpose({
  fetchProjets: fetchProjetsList
})

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

 