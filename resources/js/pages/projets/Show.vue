<!-- resources/js/pages/projets/Show.vue -->
<template>
  <AdminLayout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center h-screen">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-600"></div>
      </div>

      <template v-else-if="projet">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 shadow">
          <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-4">
                <!-- Back Button -->
                <button @click="goBack"
                  class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                  </svg>
                </button>

                <!-- Project Color Indicator -->
                <div class="w-16 h-16 rounded-lg flex items-center justify-center"
                  :style="{ backgroundColor: projet.couleur || '#3B82F6' }">
                  <FolderIcon class="w-8 h-8 text-white" />
                </div>

                <!-- Project Info -->
                <div>
                  <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                      {{ projet.code }}
                    </span>
                    <span :class="[
                      'px-2 py-1 text-xs font-medium rounded-full',
                      getStatusColor(projet.status)
                    ]">
                      {{ getStatusLabel(projet.status) }}
                    </span>
                  </div>
                  <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ projet.nom }}
                  </h1>
                  <p v-if="projet.description" class="text-gray-600 dark:text-gray-400 mt-2 max-w-2xl">
                    {{ projet.description }}
                  </p>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex items-center gap-2">
                <button @click="toggleFavorite"
                  class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                  <StarIcon :class="projet.is_favorite ? 'fill-yellow-400 text-yellow-400' : 'text-gray-400'"
                    class="w-6 h-6" />
                </button>

                <button v-if="canEdit" @click="editProjet"
                  class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors flex items-center gap-2">
                  <EditIcon class="w-5 h-5" />
                  Modifier
                </button>

                <button
                  class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors flex items-center gap-2">
                  <PlusIcon class="w-5 h-5" />
                  Nouvelle activité
                </button>
              </div>
            </div>

            <!-- Project Meta Info -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
              <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <CalendarIcon class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                <div>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Début</p>
                  <p class="font-medium text-gray-900 dark:text-white">{{ formatDate(projet.date_debut) }}</p>
                </div>
              </div>
              <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <CalendarIcon class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                <div>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Fin</p>
                  <p class="font-medium text-gray-900 dark:text-white">{{ formatDate(projet.date_fin) }}</p>
                </div>
              </div>
              <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <!-- <UserIcon class="w-5 h-5 text-gray-500 dark:text-gray-400" /> -->
                <div>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Responsable</p>
                  <p class="font-medium text-gray-900 dark:text-white">{{ projet.responsable?.nom || 'Non assigné' }}</p>
                </div>
              </div>
              <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <UsersIcon class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                <div>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Membres</p>
                  <p class="font-medium text-gray-900 dark:text-white">{{ projet.member_count || 0 }}</p>
                </div>
              </div>
            </div>

            <!-- Progress Bar -->
            <div class="mt-6">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Progression globale</span>
                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ projet.progression || 0 }}%</span>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                <div class="h-3 rounded-full transition-all duration-300"
                  :class="projet.progression >= 100 ? 'bg-green-500' : 'bg-brand-600'"
                  :style="{ width: `${projet.progression || 0}%` }"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="container mx-auto px-4 py-6">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <!-- Activities Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-gray-600 dark:text-gray-400">Activités</p>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                    {{ stats.total_activites || 0 }}
                  </p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                  <ListIcon class="w-6 h-6 text-blue-600 dark:text-blue-300" />
                </div>
              </div>
            </div>

            <!-- Tasks Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-gray-600 dark:text-gray-400">Tâches</p>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                    {{ stats.total_taches || 0 }}
                  </p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                  <CheckCircleIcon class="w-6 h-6 text-green-600 dark:text-green-300" />
                </div>
              </div>
              <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ stats.taches_terminees || 0 }} terminées
              </div>
            </div>

            <!-- Completed Tasks -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-gray-600 dark:text-gray-400">Taux de complétion</p>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                    {{ stats.taux_completion || 0 }}%
                  </p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                  <TrendingUpIcon class="w-6 h-6 text-purple-600 dark:text-purple-300" />
                </div>
              </div>
            </div>

            <!-- Overdue Tasks -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-gray-600 dark:text-gray-400">Tâches en retard</p>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                    {{ stats.taches_en_retard || 0 }}
                  </p>
                </div>
                <div class="p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                  <AlertCircleIcon class="w-6 h-6 text-red-600 dark:text-red-300" />
                </div>
              </div>
            </div>
          </div>

          <!-- Activities List -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Activités</h2>
                <button
                  class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors flex items-center gap-2">
                  <PlusIcon class="w-5 h-5" />
                  Ajouter une activité
                </button>
              </div>
            </div>

            <div class="p-6">
              <div v-if="!projet.activites || projet.activites.length === 0" class="text-center py-12">
                <ListIcon class="mx-auto h-12 w-12 text-gray-400" />
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                  Aucune activité pour le moment
                </p>
                <button
                  class="mt-4 px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors">
                  Créer la première activité
                </button>
              </div>

              <div v-else class="space-y-4">
                <div v-for="activite in projet.activites" :key="activite.id"
                  class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-all cursor-pointer"
                  @click="viewActivite(activite.id)">
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <h3 class="font-semibold text-gray-900 dark:text-white">
                        {{ activite.nom }}
                      </h3>
                      <p v-if="activite.description" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ activite.description }}
                      </p>
                      <div class="flex items-center gap-4 mt-3 text-sm text-gray-500 dark:text-gray-400">
                        <span>{{ activite.taches_count || 0 }} tâches</span>
                        <span v-if="activite.date_debut && activite.date_fin">
                          {{ formatDate(activite.date_debut) }} - {{ formatDate(activite.date_fin) }}
                        </span>
                      </div>
                    </div>
                    <div class="flex items-center gap-3">
                      <div v-if="activite.responsable" class="flex items-center gap-2">
                        <img v-if="activite.responsable.avatar" :src="activite.responsable.avatar"
                          :alt="activite.responsable.nom" class="w-8 h-8 rounded-full" />
                        <div v-else
                          class="w-8 h-8 rounded-full bg-brand-600 flex items-center justify-center text-white text-xs font-semibold">
                          {{ getInitials(activite.responsable.nom) }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Error State -->
      <div v-else class="flex items-center justify-center h-screen">
        <div class="text-center">
          <AlertCircleIcon class="mx-auto h-12 w-12 text-red-500" />
          <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-white">Projet introuvable</h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Le projet demandé n'existe pas ou vous n'y avez pas accès.
          </p>
          <button @click="goBack"
            class="mt-4 px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors">
            Retour
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProjets } from '@/composables/useProjets'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import {
  FolderIcon, StarIcon, EditIcon, PlusIcon, CalendarIcon,
  UsersIcon, ListIcon, CheckCircleIcon, TrendingUpIcon, AlertCircleIcon
} from '@/icons'

const route = useRoute()
const router = useRouter()
const { fetchProjet, toggleFavorite: toggleFavoriteService } = useProjets()

const loading = ref(true)
const projet = ref(null)
const stats = ref({})

// Computed
const canEdit = computed(() => {
  // TODO: Check user permissions
  return true
})

// Methods
const loadProjet = async () => {
  try {
    loading.value = true
    const projetId = parseInt(route.params.id)
    const response = await fetchProjet(projetId)
    
    projet.value = response.data
    stats.value = response.stats || {}
    
    console.log('Projet chargé:', projet.value)
  } catch (error) {
    console.error('Error loading projet:', error)
    projet.value = null
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.back()
}

const editProjet = () => {
  // TODO: Open edit modal
  console.log('Edit projet')
}

const toggleFavorite = async () => {
  try {
    await toggleFavoriteService(projet.value.id)
    await loadProjet()
  } catch (error) {
    console.error('Error toggling favorite:', error)
  }
}

const viewActivite = (activiteId) => {
  router.push({ name: 'activites.show', params: { id: activiteId } })
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

// Lifecycle
onMounted(() => {
  loadProjet()
})
</script>

<style scoped>
/* Add any custom styles here */
</style>