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
            {{ $t('sidebar.workspace_tasks') }}
          </h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            {{ $t('sidebar.all_tasks') }}
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
        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-3 p-4 space-y-3"
        dusk="workspace-taches-filters"
      >
        <div class="flex flex-wrap gap-3">
          <select
            v-model="filters.statut"
            @change="fetchTaches(1)"
            dusk="filter-statut"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
          >
            <option value="">{{ $t('statuts.all') }}</option>
            <option value="a_faire">{{ $t('statuts.a_faire') }}</option>
            <option value="en_cours">{{ $t('statuts.en_cours') }}</option>
            <option value="termine">{{ $t('statuts.termine') }}</option>
            <option value="en_retard">{{ $t('statuts.en_retard') }}</option>
            <option value="a_refaire">{{ $t('statuts.a_refaire') }}</option>
          </select>

          <select
            v-model="filters.projetId"
            @change="filters.activiteId = ''; fetchTaches(1)"
            dusk="filter-projet"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
          >
            <option value="">{{ $t('projects.all_projects') }}</option>
            <option v-for="p in projets" :key="p.id" :value="p.id">{{ p.nom }}</option>
          </select>

          <button
            v-if="hasActiveFilter"
            @click="clearFilters"
            dusk="clear-filters-btn"
            class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
          >
            <i class="fas fa-times mr-1"></i> {{ $t('common.reset') }}
          </button>
        </div>

        <DateRangeFilter @change="onDateRangeChange" />
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
        <div v-if="loading" class="p-4">
          <SkeletonLoader type="table" :rows="8" :cols="7" :col-widths="[3,2,1,1,1,1,1]" />
        </div>

        <div
          v-else-if="!taches.length"
          class="text-center py-12 text-gray-500 dark:text-gray-400"
          dusk="no-tasks-message"
        >
          {{ $t('projects.empty.description_with_filter') }}
        </div>

        <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm" dusk="workspace-taches-table">
          <thead class="bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
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
          <tbody ref="tbodyRef" class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="tache in taches"
              :key="tache.id"
              dusk="tache-row"
              class="stagger-item hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer"
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
            {{ $t('common.previous') }}
          </button>
          <button
            :disabled="meta.current_page >= meta.last_page"
            @click="fetchTaches(meta.current_page + 1)"
            dusk="next-page-btn"
            class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-3 disabled:opacity-40 hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            {{ $t('common.next') }}
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import DateRangeFilter from '@/components/common/DateRangeFilter.vue'
import { useStagger } from '@/composables/useAnimations'
import SkeletonLoader from '@/components/common/SkeletonLoader.vue'
import api from '@/api/axios'

const { t } = useI18n()
const router = useRouter()
const { staggerRef: tbodyRef, applyStagger } = useStagger(40)

const filters = ref({
  statut: '',
  projetId: '',
  activiteId: '',
  assigneeId: '',
})

const dateRange = ref({ from: null, to: null })

function onDateRangeChange({ from, to }) {
  dateRange.value = { from, to }
  fetchTaches(1)
}

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
    if (dateRange.value.from)     params.date_from   = dateRange.value.from
    if (dateRange.value.to)       params.date_to     = dateRange.value.to

    const res    = await api.get('/workspace/taches', { params })
    taches.value = res.data.data
    meta.value   = res.data.meta
    applyStagger()
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

async function exportExcel() {
  // window.open ne transporte pas l'en-tête Authorization Bearer du localStorage —
  // on télécharge via api (qui ajoute le token via son intercepteur) et on déclenche
  // une ancre synthétique avec un object-URL.
  const params = {}
  if (filters.value.statut) params.statut = filters.value.statut
  if (filters.value.projetId) params.projet_id = filters.value.projetId
  if (filters.value.activiteId) params.activite_id = filters.value.activiteId
  if (filters.value.assigneeId) params.assignee_id = filters.value.assigneeId
  try {
    const res = await api.get('/workspace/taches/export-excel', { params, responseType: 'blob' })
    const blob = new Blob([res.data], { type: res.headers['content-type'] || 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `taches-workspace-${new Date().toISOString().slice(0, 10)}.xlsx`
    document.body.appendChild(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(url)
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Échec de l\'export Excel.'
  }
}

function goToTache(tache) {
  router.push({ name: 'taches.show', params: { id: tache.id } })
}

function formatDate(date) {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function statutLabel(statut) {
  const key = `statuts.${statut}`
  const label = t(key)
  return label !== key ? label : statut
}

function statutBadge(statut) {
  const map = {
    a_faire: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    en_cours: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    en_attente: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    termine: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    en_retard: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    a_refaire: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    annule: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
  }
  return map[statut] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200'
}

function prioriteBadge(priorite) {
  const map = {
    faible: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
    moyenne: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    elevee: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    critique: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
  }
  return map[priorite] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'
}

async function loadProjets() {
  // Liste tous les projets pour alimenter le filtre du dropdown (owner uniquement).
  try {
    const res = await api.get('/projets/list/all')
    projets.value = res.data?.data ?? res.data ?? []
  } catch {
    // non-bloquant — le dropdown restera vide si l'appel échoue.
  }
}

onMounted(async () => {
  await Promise.all([loadProjets(), fetchTaches()])
})

</script>
