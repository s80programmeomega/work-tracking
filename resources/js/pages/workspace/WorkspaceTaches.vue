<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1
            dusk="workspace-taches-title"
            class="text-2xl font-semibold text-gray-900 dark:text-white"
          >
            Toutes les tâches
          </h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Vue globale de toutes les tâches du workspace.
          </p>
        </div>

        <!-- Total + Export -->
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-500 dark:text-gray-400" dusk="task-total">
            {{ meta.total ?? '—' }} tâche(s)
          </span>
          <button
            dusk="export-excel-btn"
            @click="exportExcel"
            class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-3 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
          >
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Exporter Excel
          </button>
        </div>
      </div>

      <!-- Filtres -->
      <div
        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-3 p-4 flex flex-wrap gap-3"
        dusk="workspace-taches-filters"
      >
        <select
          v-model="filters.statut"
          @change="fetchTaches(1)"
          dusk="filter-statut"
          class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
        >
          <option value="">Tous les statuts</option>
          <option value="a_faire">À faire</option>
          <option value="en_cours">En cours</option>
          <option value="termine">Terminé</option>
          <option value="en_retard">En retard</option>
          <option value="a_refaire">À refaire</option>
        </select>

        <select
          v-model="filters.projetId"
          @change="filters.activiteId = ''; fetchTaches(1)"
          dusk="filter-projet"
          class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
        >
          <option value="">Tous les projets</option>
          <option v-for="p in projets" :key="p.id" :value="p.id">{{ p.nom }}</option>
        </select>

        <button
          v-if="hasActiveFilter"
          @click="clearFilters"
          dusk="clear-filters-btn"
          class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
        >
          <i class="fas fa-times mr-1"></i> Effacer
        </button>
      </div>

      <!-- Erreur -->
      <div
        v-if="error"
        class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-3"
      >
        {{ error }}
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-3 overflow-hidden">
        <div v-if="loading" class="text-center py-12 text-gray-500 dark:text-gray-400">
          <i class="fas fa-circle-notch fa-spin text-2xl mb-2"></i>
          <p class="text-sm">Chargement…</p>
        </div>

        <div
          v-else-if="!taches.length"
          class="text-center py-12 text-gray-500 dark:text-gray-400"
          dusk="no-tasks-message"
        >
          Aucune tâche trouvée pour ces critères.
        </div>

        <table v-else class="min-w-full text-sm" dusk="workspace-taches-table">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Titre</th>
              <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Projet / Activité</th>
              <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Statut</th>
              <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Priorité</th>
              <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Échéance</th>
              <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Assignés</th>
              <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">ST</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="tache in taches"
              :key="tache.id"
              dusk="tache-row"
              class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer"
              @click="goToTache(tache)"
            >
              <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100 max-w-xs truncate">
                {{ tache.titre }}
              </td>
              <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">
                <span class="block">{{ tache.activite?.projet?.nom ?? '—' }}</span>
                <span class="block text-gray-400 dark:text-gray-500">{{ tache.activite?.nom ?? '—' }}</span>
              </td>
              <td class="px-4 py-3">
                <span :class="statutBadge(tache.statut)" class="px-2 py-0.5 rounded-full text-xs font-medium">
                  {{ statutLabel(tache.statut) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span :class="prioriteBadge(tache.priorite)" class="px-2 py-0.5 rounded-full text-xs font-medium">
                  {{ tache.priorite ?? '—' }}
                </span>
              </td>
              <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">
                {{ formatDate(tache.echeance) }}
              </td>
              <td class="px-4 py-3">
                <div class="flex -space-x-1">
                  <div
                    v-for="a in (tache.assignees ?? []).slice(0, 4)"
                    :key="a.id"
                    :title="`${a.prenom} ${a.nom}`"
                    class="w-6 h-6 rounded-full bg-brand-100 dark:bg-brand-900 border-2 border-white dark:border-gray-800 flex items-center justify-center"
                  >
                    <span class="text-brand-600 dark:text-brand-300 text-xs font-medium">
                      {{ (a.prenom?.[0] ?? '') + (a.nom?.[0] ?? '') }}
                    </span>
                  </div>
                  <div
                    v-if="(tache.assignees?.length ?? 0) > 4"
                    class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 border-2 border-white dark:border-gray-800 flex items-center justify-center"
                  >
                    <span class="text-gray-600 dark:text-gray-300 text-xs">+{{ tache.assignees.length - 4 }}</span>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">
                {{ tache.sous_taches_count ?? 0 }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="flex items-center justify-between">
        <p class="text-sm text-gray-500 dark:text-gray-400">
          Page {{ meta.current_page }} / {{ meta.last_page }}
          ({{ meta.total }} tâche(s))
        </p>
        <div class="flex gap-2">
          <button
            :disabled="meta.current_page <= 1"
            @click="fetchTaches(meta.current_page - 1)"
            dusk="prev-page-btn"
            class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-3 disabled:opacity-40 hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            Précédent
          </button>
          <button
            :disabled="meta.current_page >= meta.last_page"
            @click="fetchTaches(meta.current_page + 1)"
            dusk="next-page-btn"
            class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-3 disabled:opacity-40 hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            Suivant
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import axios from 'axios'

const router = useRouter()

const filters = ref({
  statut: '',
  projetId: '',
  activiteId: '',
  assigneeId: '',
})

const loading = ref(false)
const error   = ref(null)
const taches  = ref([])
const meta    = ref({ current_page: 1, last_page: 1, total: 0, per_page: 25 })
const projets = ref([])

const hasActiveFilter = computed(() =>
  filters.value.statut || filters.value.projetId || filters.value.activiteId || filters.value.assigneeId
)

async function fetchTaches(page = 1) {
  loading.value = true
  error.value   = null
  try {
    const params = { page, per_page: 25 }
    if (filters.value.statut)     params.statut      = filters.value.statut
    if (filters.value.projetId)   params.projet_id   = filters.value.projetId
    if (filters.value.activiteId) params.activite_id = filters.value.activiteId
    if (filters.value.assigneeId) params.assignee_id = filters.value.assigneeId

    const res    = await axios.get('/api/workspace/taches', { params })
    taches.value = res.data.data
    meta.value   = res.data.meta
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Erreur lors du chargement des tâches.'
  } finally {
    loading.value = false
  }
}

function clearFilters() {
  filters.value = { statut: '', projetId: '', activiteId: '', assigneeId: '' }
  fetchTaches(1)
}

function exportExcel() {
  const params = new URLSearchParams()
  if (filters.value.statut) params.append('statut', filters.value.statut)
  if (filters.value.projetId) params.append('projet_id', filters.value.projetId)
  if (filters.value.activiteId) params.append('activite_id', filters.value.activiteId)
  if (filters.value.assigneeId) params.append('assignee_id', filters.value.assigneeId)
  window.open(`/api/workspace/taches/export-excel?${params}`, '_blank')
}

function goToTache(tache) {
  router.push({ name: 'taches.show', params: { id: tache.id } })
}

function formatDate(date) {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function statutLabel(statut) {
  const map = {
    a_faire: 'À faire',
    en_cours: 'En cours',
    termine: 'Terminé',
    en_retard: 'En retard',
    a_refaire: 'À refaire',
  }
  return map[statut] ?? statut
}

function statutBadge(statut) {
  const map = {
    a_faire: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    en_cours: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    termine: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    en_retard: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    a_refaire: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
  }
  return map[statut] ?? 'bg-gray-100 text-gray-700'
}

function prioriteBadge(priorite) {
  const map = {
    faible: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
    moyenne: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    elevee: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    critique: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
  }
  return map[priorite] ?? 'bg-gray-100 text-gray-600'
}

onMounted(fetchTaches)
</script>
