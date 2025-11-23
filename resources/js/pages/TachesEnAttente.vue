<!-- resources/js/pages/TachesEnAttente.vue -->
<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="'Tâches en Attente de Validation'" />

    <div class="space-y-6">
      <!-- Header Premium -->
      <div class="rounded-2xl border border-gray-200 bg-gradient-to-br from-amber-50 to-orange-50 dark:from-gray-900 dark:to-gray-800 dark:border-gray-800 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Validations en Attente
              </h1>
              <p class="text-gray-500 dark:text-gray-400">
                Tâches nécessitant votre validation (N1 ou N2)
              </p>
            </div>
          </div>

          <button
            @click="loadPendingTasks"
            :disabled="loading"
            class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-2"
          >
            <svg 
              class="w-5 h-5 text-gray-600 dark:text-gray-400" 
              :class="{ 'animate-spin': loading }"
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Actualiser
          </button>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Total -->
          <div class="rounded-xl bg-white/80 dark:bg-gray-800/80 backdrop-blur p-4 border border-white/50 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total en attente</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ totalPending }}</p>
              </div>
              <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
          </div>

          <!-- N1 -->
          <div class="rounded-xl bg-white/80 dark:bg-gray-800/80 backdrop-blur p-4 border border-white/50 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Validation N1</p>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1">{{ pendingN1.length }}</p>
                <p class="text-xs text-gray-400 mt-1">Responsable activité</p>
              </div>
              <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
          </div>

          <!-- N2 -->
          <div class="rounded-xl bg-white/80 dark:bg-gray-800/80 backdrop-blur p-4 border border-white/50 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Validation N2</p>
                <p class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ pendingN2.length }}</p>
                <p class="text-xs text-gray-400 mt-1">Responsable projet</p>
              </div>
              <div class="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-amber-500 mx-auto mb-4"></div>
          <p class="text-gray-600 dark:text-gray-400">Chargement des validations...</p>
        </div>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="rounded-2xl border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-6">
        <div class="flex items-center gap-3 text-red-700 dark:text-red-300">
          <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
          <span class="font-medium">{{ error }}</span>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="totalPending === 0" class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-12 text-center">
        <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 rounded-3xl flex items-center justify-center">
          <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">
          Aucune validation en attente
        </h3>
        <p class="text-gray-500 dark:text-gray-400">
          Toutes les tâches ont été validées. Bravo ! 🎉
        </p>
      </div>

      <!-- Content -->
      <div v-else class="space-y-6">
        <!-- Section N1 -->
        <div v-if="pendingN1.length > 0" class="rounded-2xl border border-green-200 dark:border-green-800 overflow-hidden">
          <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-b border-green-200 dark:border-green-800">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-green-500 flex items-center justify-center">
                <span class="text-white font-bold">N1</span>
              </div>
              <div>
                <h2 class="font-semibold text-gray-900 dark:text-white">Validation Niveau 1</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">En tant que responsable d'activité</p>
              </div>
              <span class="ml-auto px-3 py-1 bg-green-500 text-white text-sm font-bold rounded-full">
                {{ pendingN1.length }}
              </span>
            </div>
          </div>
          
          <div class="p-4 bg-white dark:bg-gray-900 space-y-3">
            <div v-for="tache in pendingN1" :key="tache.id">
              <ValidationTaskCard
                :tache="tache"
                validation-level="n1"
                @view="handleViewTask"
                @validate="handleValidateN1"
              />
            </div>
          </div>
        </div>

        <!-- Section N2 -->
        <div v-if="pendingN2.length > 0" class="rounded-2xl border border-purple-200 dark:border-purple-800 overflow-hidden">
          <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-violet-50 dark:from-purple-900/20 dark:to-violet-900/20 border-b border-purple-200 dark:border-purple-800">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-purple-500 flex items-center justify-center">
                <span class="text-white font-bold">N2</span>
              </div>
              <div>
                <h2 class="font-semibold text-gray-900 dark:text-white">Validation Niveau 2</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">En tant que responsable de projet</p>
              </div>
              <span class="ml-auto px-3 py-1 bg-purple-500 text-white text-sm font-bold rounded-full">
                {{ pendingN2.length }}
              </span>
            </div>
          </div>
          
          <div class="p-4 bg-white dark:bg-gray-900 space-y-3">
            <div v-for="tache in pendingN2" :key="tache.id">
              <ValidationTaskCard
                :tache="tache"
                validation-level="n2"
                @view="handleViewTask"
                @validate="handleValidateN2"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Task Detail Modal -->
    <TacheDetailModal
      v-if="showViewModal"
      :tache="currentTache"
      @close="showViewModal = false"
      @validate-n1="handleValidateN1"
      @validate-n2="handleValidateN2"
    />
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ValidationTaskCard from '@/components/taches/ValidationTaskCard.vue'
import TacheDetailModal from '@/components/taches/TacheDetailModal.vue'
import api from '@/api/axios'

// State
const pendingN1 = ref([])
const pendingN2 = ref([])
const loading = ref(false)
const error = ref(null)
const showViewModal = ref(false)
const currentTache = ref(null)

// Computed
const totalPending = computed(() => pendingN1.value.length + pendingN2.value.length)

// Methods
async function loadPendingTasks() {
  loading.value = true
  error.value = null

  try {
    const { data } = await api.get('/taches/en-attente')
    pendingN1.value = data.pending_n1 || []
    pendingN2.value = data.pending_n2 || []
    console.log('✅ Validations chargées:', { n1: pendingN1.value.length, n2: pendingN2.value.length })
  } catch (err) {
    console.error('❌ Erreur:', err)
    error.value = err.response?.data?.message || 'Impossible de charger les validations'
  } finally {
    loading.value = false
  }
}

async function handleViewTask(tache) {
  try {
    const { data } = await api.get(`/taches/${tache.id}`)
    currentTache.value = data.data
    showViewModal.value = true
  } catch (err) {
    console.error('Erreur:', err)
    alert('Erreur lors du chargement')
  }
}

async function handleValidateN1(tache) {
  const commentaire = prompt('Commentaire de validation N1 (optionnel):')
  if (commentaire === null) return

  try {
    await api.post(`/taches/${tache.id}/validate-n1`, { commentaire })
    await loadPendingTasks()
    alert('Tâche validée (N1) avec succès !')
  } catch (err) {
    console.error('Erreur validation N1:', err)
    alert(err.response?.data?.message || 'Erreur lors de la validation')
  }
}


async function handleValidateN2(tache) {
  const commentaire = prompt('Commentaire de validation finale N2 (optionnel):')
  if (commentaire === null) return

  try {
    await api.post(`/taches/${tache.id}/validate-n2`, { commentaire })
    await loadPendingTasks()
    alert('Tâche validée (N2) avec succès ! Validation complète.')
  } catch (err) {
    console.error('Erreur validation N2:', err)
    alert(err.response?.data?.message || 'Erreur lors de la validation')
  }
}

// Lifecycle
onMounted(() => {
  loadPendingTasks()
})
</script>