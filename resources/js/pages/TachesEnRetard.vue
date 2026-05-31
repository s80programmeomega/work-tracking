<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="rounded-3 border border-red-200 dark:border-red-800 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
          <div class="flex min-w-0 items-center gap-4">
            <div class="w-14 h-14 rounded-3 flex items-center justify-center bg-red-100 dark:bg-red-900/30">
              <svg class="w-7 h-7 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tâches en Retard</h1>
              <p class="text-gray-500 dark:text-gray-400">Tâches dont l'échéance est dépassée</p>
            </div>
          </div>

          <button
            @click="loadTaches"
            :disabled="loading"
            class="p-2 rounded-3 border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            title="Actualiser"
          >
            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </button>
        </div>

        <!-- Stat -->
        <div class="inline-flex items-center gap-2 bg-red-50 dark:bg-red-900/20 px-4 py-2 rounded-3 border border-red-200 dark:border-red-800">
          <span class="text-sm font-semibold text-red-700 dark:text-red-300">{{ taches.length }} tâche(s) en retard</span>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-red-500 mx-auto"></div>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="rounded-3 border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-6">
        <div class="flex items-center gap-3 text-red-700 dark:text-red-300">
          <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
          <span>{{ error }}</span>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="taches.length === 0" class="rounded-3 border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-12 text-center">
        <div class="w-20 h-20 mx-auto mb-6 bg-green-100 dark:bg-green-900/30 rounded-3 flex items-center justify-center">
          <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Aucune tâche en retard</h3>
        <p class="text-gray-500 dark:text-gray-400">Toutes vos tâches sont dans les délais.</p>
      </div>

      <!-- Task List -->
      <div v-else ref="listRef" class="space-y-3">
        <div
          v-for="tache in taches"
          :key="tache.id"
          class="stagger-item rounded-3 border border-red-100 dark:border-red-900/30 bg-white dark:bg-white/3 p-4 hover:border-red-300 dark:hover:border-red-700 transition-colors cursor-pointer"
          @click="$router.push({ name: 'taches.show', params: { id: tache.id } })"
        >
          <div class="flex flex-wrap items-start justify-between gap-2">
            <div class="min-w-0 flex-1">
              <h3 class="font-semibold text-gray-900 dark:text-white truncate">{{ tache.titre }}</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ tache.activite?.nom }} · {{ tache.activite?.projet?.nom }}
              </p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
              <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                Échéance : {{ formatDate(tache.echeance) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import { useStagger } from '@/composables/useAnimations'
import api from '@/api/axios'

const taches = ref([])
const loading = ref(false)
const error = ref(null)
const { staggerRef: listRef, applyStagger } = useStagger(50)

const loadTaches = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get('/taches/en-retard')
    taches.value = response.data.data ?? response.data
    applyStagger()
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Erreur lors du chargement des tâches.'
  } finally {
    loading.value = false
  }
}

const formatDate = (date) => {
  if (!date) { return '—' }
  return new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

onMounted(loadTaches)
</script>
