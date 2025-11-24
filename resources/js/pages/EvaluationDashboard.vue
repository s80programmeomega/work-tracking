<!-- resources/js/views/evaluations/EvaluationDashboard.vue -->
<template>
  <AdminLayout>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
          <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>
          Tableau de bord des évaluations
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">
          Vue d'ensemble de vos performances et validations en attente
        </p>
      </div>
    </div>

    <!-- Contenu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
      
      <!-- Loading -->
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="animate-spin rounded-full h-16 w-16 border-4 border-purple-500 border-t-transparent"></div>
      </div>

      <template v-else>
        <!-- Statistiques principales -->
        <div>
          <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            Mes tâches - Semaine {{ weekInfo.week_number }}
          </h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <DashboardCard
              title="Total tâches"
              :value="myTasks.total"
              icon="clipboard-list"
              color="blue"
              :trend="{ value: '+12%', positive: true }"
            />
            <DashboardCard
              title="Complétées"
              :value="myTasks.completed"
              :subtitle="`${myTasks.completionRate}% de complétion`"
              icon="check-circle"
              color="green"
            />
            <DashboardCard
              title="En cours"
              :value="myTasks.in_progress"
              icon="clock"
              color="yellow"
            />
            <DashboardCard
              title="En retard"
              :value="myTasks.overdue"
              icon="exclamation"
              color="red"
              :urgent="myTasks.overdue > 0"
            />
          </div>
        </div>

        <!-- Validations en attente -->
        <div v-if="pendingValidations.total > 0">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Validations en attente
              <span class="inline-flex items-center px-3 py-1 text-sm font-semibold rounded-full bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300">
                {{ pendingValidations.total }}
              </span>
            </h2>
            <router-link 
              to="/evaluations/fiches"
              class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 flex items-center gap-1">
              Voir tout
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </router-link>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <ValidationCard
              title="Validation N1"
              subtitle="Responsable d'activité"
              :count="pendingValidations.n1"
              color="green"
              @click="goToValidations('n1')"
            />
            <ValidationCard
              title="Validation N2"
              subtitle="Responsable de projet"
              :count="pendingValidations.n2"
              color="purple"
              @click="goToValidations('n2')"
            />
          </div>
        </div>

        <!-- Graphiques -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Progression hebdomadaire -->
          <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              Progression hebdomadaire
            </h3>
            <div class="space-y-4">
              <ProgressBar
                label="Tâches complétées"
                :value="myTasks.completed"
                :max="myTasks.total"
                color="green"
              />
              <ProgressBar
                label="Tâches validées"
                :value="myTasks.validated"
                :max="myTasks.completed"
                color="purple"
              />
              <ProgressBar
                label="En cours"
                :value="myTasks.in_progress"
                :max="myTasks.total"
                color="blue"
              />
            </div>
          </div>

          <!-- Répartition par priorité -->
          <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              Répartition par priorité
            </h3>
            <div class="space-y-3">
              <PriorityBadge
                label="Critique"
                :count="priorityStats.critique"
                color="red"
              />
              <PriorityBadge
                label="Élevée"
                :count="priorityStats.elevee"
                color="orange"
              />
              <PriorityBadge
                label="Moyenne"
                :count="priorityStats.moyenne"
                color="yellow"
              />
              <PriorityBadge
                label="Faible"
                :count="priorityStats.faible"
                color="green"
              />
            </div>
          </div>
        </div>

        <!-- Actions rapides -->
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl p-8 text-white">
          <h2 class="text-2xl font-bold mb-4">Actions rapides</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <router-link 
              to="/evaluations/fiches"
              class="flex items-center gap-4 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-lg p-4 transition-all">
              <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
              </div>
              <div>
                <p class="font-semibold">Fiche d'évaluation</p>
                <p class="text-sm text-white/80">Remplir ma fiche</p>
              </div>
            </router-link>

            <router-link 
              to="/evaluations/rapport-hebdomadaire"
              class="flex items-center gap-4 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-lg p-4 transition-all">
              <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
              </div>
              <div>
                <p class="font-semibold">Rapport hebdomadaire</p>
                <p class="text-sm text-white/80">Voir mon rapport</p>
              </div>
            </router-link>

            <router-link 
              to="/evaluations/performance"
              class="flex items-center gap-4 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-lg p-4 transition-all">
              <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
              </div>
              <div>
                <p class="font-semibold">Performance d'équipe</p>
                <p class="text-sm text-white/80">Analyse collective</p>
              </div>
            </router-link>
          </div>
        </div>
      </template>
    </div>
  </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api/axios'
import AdminLayout from '@/components/layout/AdminLayout.vue'

// import DashboardCard from './components/DashboardCard.vue'
// import ValidationCard from './components/ValidationCard.vue'
// import ProgressBar from './components/ProgressBar.vue'
// import PriorityBadge from './components/PriorityBadge.vue'

const router = useRouter()
const loading = ref(true)
const dashboardData = ref(null)

const weekInfo = computed(() => dashboardData.value?.week_info || { week_number: 0, year: 2025 })
const myTasks = computed(() => dashboardData.value?.my_tasks || {
  total: 0,
  completed: 0,
  in_progress: 0,
  pending: 0,
  overdue: 0,
  validated: 0,
  completionRate: 0
})

const pendingValidations = computed(() => dashboardData.value?.pending_validations || {
  n1: 0,
  n2: 0,
  total: 0
})

const priorityStats = computed(() => ({
  critique: 0,
  elevee: 0,
  moyenne: 0,
  faible: 0
}))

async function loadDashboard() {
  loading.value = true
  try {
    const { data } = await api.get('/evaluations/dashboard')
    dashboardData.value = data
  } catch (error) {
    console.error('Error loading dashboard:', error)
  } finally {
    loading.value = false
  }
}

function goToValidations(level) {
  router.push(`/evaluations/fiches?filter=${level}`)
}

onMounted(() => {
  loadDashboard()
})
</script>