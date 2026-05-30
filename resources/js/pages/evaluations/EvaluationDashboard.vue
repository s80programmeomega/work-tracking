<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1
            dusk="evaluation-dashboard-title"
            class="text-2xl font-semibold text-gray-900 dark:text-white"
          >
            Tableau de bord évaluations
          </h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Scores équipe, top performers et alertes pour la période sélectionnée.
          </p>
        </div>

        <!-- Filtres période -->
        <div class="flex items-center gap-2">
          <input
            v-model="filters.periodeStart"
            type="date"
            dusk="periode-start"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
          />
          <span class="text-gray-500 dark:text-gray-400 text-sm">→</span>
          <input
            v-model="filters.periodeEnd"
            type="date"
            dusk="periode-end"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
          />
          <button
            @click="fetchDashboard"
            :disabled="loading"
            dusk="refresh-dashboard-btn"
            class="px-4 py-2 bg-brand-600 text-white rounded-3 text-sm hover:bg-brand-700 disabled:opacity-50"
          >
            <i class="fas fa-sync-alt mr-1" :class="{ 'animate-spin': loading }"></i>
            Actualiser
          </button>
        </div>
      </div>

      <!-- Erreur -->
      <div
        v-if="error"
        class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-3"
      >
        {{ error }}
      </div>

      <template v-if="!loading && data">
        <!-- Top performers -->
        <section dusk="top-performers-section">
          <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-3">
            <i class="fas fa-trophy text-yellow-500 mr-2"></i>
            Meilleurs performers
          </h2>
          <div v-if="!data.top_performers.length" class="text-center py-6 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 rounded-3">
            Aucun score enregistré pour cette période.
          </div>
          <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
            <div
              v-for="(entry, idx) in data.top_performers"
              :key="entry.user_id"
              dusk="top-performer-card"
              class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-3 p-4 flex flex-col items-center text-center "
            >
              <!-- Rang -->
              <div
                class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold mb-2"
                :class="rankClass(idx)"
              >
                {{ idx + 1 }}
              </div>
              <!-- Avatar -->
              <img
                v-if="entry.user?.avatar"
                :src="entry.user.avatar"
                class="w-12 h-12 rounded-full object-cover mb-2"
                alt=""
              />
              <div v-else class="w-12 h-12 rounded-full bg-brand-100 dark:bg-brand-900 flex items-center justify-center mb-2">
                <span class="text-brand-600 dark:text-brand-300 font-medium text-lg">
                  {{ initials(entry.user) }}
                </span>
              </div>
              <p class="font-medium text-sm text-gray-900 dark:text-white">
                {{ entry.user?.nom }} {{ entry.user?.prenom }}
              </p>
              <p class="text-2xl font-bold mt-1" :class="scoreColor(entry.total_score)">
                {{ entry.total_score > 0 ? '+' : '' }}{{ entry.total_score }}
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                {{ entry.decision_count }} décision(s)
              </p>
            </div>
          </div>
        </section>

        <!-- Scores complets -->
        <section dusk="all-scores-section">
          <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-3">
            <i class="fas fa-chart-bar text-blue-500 mr-2"></i>
            Scores de l'équipe
          </h2>
          <div v-if="!data.scores.length" class="text-center py-6 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 rounded-3">
            Aucun score enregistré pour cette période.
          </div>
          <div v-else class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-3 overflow-hidden">
            <table class="min-w-full text-sm">
              <thead class="bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                <tr>
                  <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Membre</th>
                  <th class="px-4 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Score total</th>
                  <th class="px-4 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Décisions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr
                  v-for="entry in sortedScores"
                  :key="entry.user_id"
                  dusk="score-row"
                  class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                >
                  <td class="px-4 py-3 text-gray-900 dark:text-gray-100">
                    {{ entry.user?.nom }} {{ entry.user?.prenom }}
                  </td>
                  <td class="px-4 py-3 text-right font-semibold" :class="scoreColor(entry.total_score)">
                    {{ entry.total_score > 0 ? '+' : '' }}{{ entry.total_score }}
                  </td>
                  <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">
                    {{ entry.decision_count }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Alertes -->
        <section dusk="alerts-section">
          <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-3">
            <i class="fas fa-exclamation-triangle text-orange-500 mr-2"></i>
            Alertes
          </h2>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Escalades abusives -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-3 p-4">
              <h3 class="font-medium text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                Escalades abusives
              </h3>
              <div
                v-if="!data.alerts.escalades_abusives.length"
                class="text-sm text-gray-500 dark:text-gray-400 text-center py-4"
                dusk="no-escalade-alerts"
              >
                Aucune alerte active.
              </div>
              <ul v-else class="space-y-2" dusk="escalade-alerts-list">
                <li
                  v-for="alert in data.alerts.escalades_abusives"
                  :key="alert.user_id"
                  class="text-sm flex items-start gap-2"
                >
                  <i class="fas fa-flag text-red-500 mt-0.5 shrink-0"></i>
                  <span class="text-gray-700 dark:text-gray-300">
                    <span class="font-medium">{{ alert.user?.nom }} {{ alert.user?.prenom }}</span>
                    — tâche « {{ alert.tache_titre }} »
                  </span>
                </li>
              </ul>
            </div>

            <!-- Taux d'inaction élevé -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-3 p-4">
              <h3 class="font-medium text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                Taux d'inaction N0 élevé (> 33 %)
              </h3>
              <div
                v-if="!data.alerts.high_inaction_rate.length"
                class="text-sm text-gray-500 dark:text-gray-400 text-center py-4"
                dusk="no-inaction-alerts"
              >
                Aucune alerte active.
              </div>
              <ul v-else class="space-y-2" dusk="inaction-alerts-list">
                <li
                  v-for="alert in data.alerts.high_inaction_rate"
                  :key="alert.user_id"
                  class="text-sm flex items-start gap-2"
                >
                  <i class="fas fa-hourglass-half text-orange-500 mt-0.5 shrink-0"></i>
                  <span class="text-gray-700 dark:text-gray-300">
                    <span class="font-medium">Responsable #{{ alert.user_id }}</span>
                    — {{ Math.round(alert.inaction_rate * 100) }}% ({{ alert.inaction_count }}/{{ alert.total_resultats }})
                  </span>
                </li>
              </ul>
            </div>
          </div>
        </section>
      </template>

      <!-- Chargement initial -->
      <div v-if="loading && !data" class="text-center py-16 text-gray-500 dark:text-gray-400">
        <i class="fas fa-circle-notch fa-spin text-3xl mb-3"></i>
        <p>Chargement du tableau de bord…</p>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import api from '@/api/axios'

// Période par défaut : mois courant
const today = new Date()
const firstOfMonth = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().slice(0, 10)
const lastOfMonth  = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().slice(0, 10)

const filters = ref({
  periodeStart: firstOfMonth,
  periodeEnd: lastOfMonth,
})

const loading = ref(false)
const error   = ref(null)
const data    = ref(null)

const sortedScores = computed(() =>
  data.value
    ? [...data.value.scores].sort((a, b) => b.total_score - a.total_score)
    : []
)

async function fetchDashboard() {
  loading.value = true
  error.value   = null
  try {
    const params = {
      periode_start: filters.value.periodeStart,
      periode_end:   filters.value.periodeEnd,
    }
    const res = await api.get('/evaluations/tableau-de-bord', { params })
    data.value = res.data.data
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Erreur lors du chargement du tableau de bord.'
  } finally {
    loading.value = false
  }
}

function initials(user) {
  if (!user) return '?'
  return `${(user.prenom ?? '')[0] ?? ''}${(user.nom ?? '')[0] ?? ''}`.toUpperCase()
}

function rankClass(idx) {
  if (idx === 0) return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
  if (idx === 1) return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'
  if (idx === 2) return 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400'
  return 'bg-brand-50 text-brand-600 dark:bg-brand-900/20 dark:text-brand-400'
}

function scoreColor(score) {
  if (score > 0) return 'text-green-600 dark:text-green-400'
  if (score < 0) return 'text-red-600 dark:text-red-400'
  return 'text-gray-500 dark:text-gray-400'
}

onMounted(fetchDashboard)
</script>
