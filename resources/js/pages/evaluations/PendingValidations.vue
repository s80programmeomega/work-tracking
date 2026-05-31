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

      <!-- Stats -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <StatCard title="N1 en attente" :value="counts.n1" color="blue" />
        <StatCard title="N2 en attente" :value="counts.n2" color="purple" />
        <StatCard title="Urgents (< 24h)" :value="counts.urgent" color="red" />
        <StatCard title="Total" :value="counts.total" color="gray" />
      </div>

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
import StatCard from '@/components/common/StatCard.vue'
import PendingRow from './PendingRow.vue'
import { useStagger } from '@/composables/useAnimations'
import api from '@/api/axios'

const router = useRouter()
const loading = ref(false)
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
