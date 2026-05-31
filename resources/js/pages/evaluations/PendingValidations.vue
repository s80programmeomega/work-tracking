<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
            Validations en attente
          </h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Résultats en attente d'action N1 ou N2, triés par échéance.
          </p>
        </div>
        <div class="flex items-center gap-3">
          <button
            @click="showStats = !showStats"
            class="inline-flex items-center gap-2 rounded-3 border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Statistiques
          </button>
          <button
            @click="refresh"
            :disabled="loading"
            dusk="refresh-pending-btn"
            class="px-4 py-2 bg-brand-600 text-white rounded-3 hover:bg-brand-700 disabled:opacity-50"
          >
            <i class="fas fa-sync-alt mr-2" :class="{ 'animate-spin': loading }"></i>
            Rafraîchir
          </button>
        </div>
      </div>

      <!-- Stats panel -->
      <transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 -translate-y-4"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-4"
      >
        <PendingValidationsStats
          v-if="showStats"
          :counts="counts"
          :loading="loading"
          @close="showStats = false"
        />
      </transition>

      <!-- Error -->
      <div v-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded">
        {{ error }}
      </div>

      <!-- N1 list -->
      <section dusk="pending-n1-section">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-3">
          En attente N1 ({{ counts.n1 }})
        </h2>
        <div v-if="loading && !rows.pending_n1.length" class="text-center py-8 text-gray-500 dark:text-gray-400">
          Chargement…
        </div>
        <div v-else-if="!rows.pending_n1.length" class="text-center py-8 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 rounded">
          Aucun résultat en attente N1
        </div>
        <div v-else ref="n1Ref" class="space-y-2">
          <PendingRow
            v-for="row in rows.pending_n1"
            :key="`n1-${row.id}`"
            :row="row"
            level="n1"
            class="stagger-item"
            @open="openTask"
          />
        </div>
      </section>

      <!-- N2 list -->
      <section dusk="pending-n2-section">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-3">
          En attente N2 ({{ counts.n2 }})
        </h2>
        <div v-if="loading && !rows.pending_n2.length" class="text-center py-8 text-gray-500 dark:text-gray-400">
          Chargement…
        </div>
        <div v-else-if="!rows.pending_n2.length" class="text-center py-8 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 rounded">
          Aucun résultat en attente N2
        </div>
        <div v-else ref="n2Ref" class="space-y-2">
          <PendingRow
            v-for="row in rows.pending_n2"
            :key="`n2-${row.id}`"
            :row="row"
            level="n2"
            class="stagger-item"
            @open="openTask"
          />
        </div>
      </section>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PendingRow from './PendingRow.vue'
import PendingValidationsStats from '@/components/evaluations/PendingValidationsStats.vue'
import { useStagger } from '@/composables/useAnimations'
import api from '@/api/axios'

const router = useRouter()
const loading = ref(false)
const showStats = ref(false)
const error = ref(null)
const rows = ref({ pending_n1: [], pending_n2: [] })
const counts = ref({ n1: 0, n2: 0, urgent: 0, total: 0 })
const { staggerRef: n1Ref, applyStagger: applyN1Stagger } = useStagger(40)
const { staggerRef: n2Ref, applyStagger: applyN2Stagger } = useStagger(40)

const fetchData = async () => {
  loading.value = true
  error.value = null
  try {
    const res = await api.get('/evaluations/validations/en-attente')
    rows.value.pending_n1 = res.data?.data?.pending_n1 ?? []
    rows.value.pending_n2 = res.data?.data?.pending_n2 ?? []
    counts.value = res.data?.data?.counts ?? { n1: 0, n2: 0, urgent: 0, total: 0 }
    applyN1Stagger()
    applyN2Stagger()
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur de chargement'
  } finally {
    loading.value = false
  }
}

const refresh = () => fetchData()

const openTask = (row) => {
  router.push(`/taches/${row.tache.id}`)
}

onMounted(fetchData)
</script>
