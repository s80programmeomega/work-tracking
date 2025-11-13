<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="'Mes Activités'" />

    <div class="rounded-2xl border border-gray-200 bg-white p-7.5 shadow-default dark:border-gray-700 dark:bg-gray-800 xl:p-12.5">
      <!-- Stats Cards -->
      <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-lg bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
            </div>
            <div class="rounded-full bg-blue-500 p-3">
              <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
          </div>
        </div>

        <div class="rounded-lg bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 p-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Actives</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.active }}</p>
            </div>
            <div class="rounded-full bg-green-500 p-3">
              <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
          </div>
        </div>

        <div class="rounded-lg bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 p-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">En retard</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.overdue }}</p>
            </div>
            <div class="rounded-full bg-yellow-500 p-3">
              <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="rounded-lg bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 p-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Progression moy.</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.avgProgress }}%</p>
            </div>
            <div class="rounded-full bg-purple-500 p-3">
              <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="flex-1">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Rechercher une activité..."
            class="w-full max-w-md rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-4 py-2 text-sm"
            @input="debouncedSearch"
          />
        </div>

        <div class="flex gap-2">
          <select
            v-model="filters.status"
            class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-4 py-2 text-sm"
            @change="loadActivites"
          >
            <option value="">Tous les statuts</option>
            <option value="active">Actives</option>
            <option value="archived">Archivées</option>
          </select>

          <button
            @click="resetFilters"
            class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            Réinitialiser
          </button>
        </div>
      </div>

      <!-- Activities List -->
      <ActiviteList :filters="filters" endpoint="/activites/my-activites" />
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ActiviteList from '@/components/activites/ActiviteList.vue'
import { useActivites } from '@/composables/useActivites'

const { activites, fetchActivites } = useActivites()

const filters = ref({
  search: '',
  status: '',
  page: 1
})

const stats = computed(() => {
  const total = activites.value.length
  const active = activites.value.filter(a => a.status === 'active').length
  const overdue = activites.value.filter(a => a.is_overdue).length
  const avgProgress = total > 0 
    ? Math.round(activites.value.reduce((sum, a) => sum + (a.progression || 0), 0) / total)
    : 0

  return { total, active, overdue, avgProgress }
})

const loadActivites = async () => {
  await fetchActivites(filters.value)
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

onMounted(() => {
  loadActivites()
})
</script>