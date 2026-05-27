<!-- resources/js/pages/TachesParUtilisateur.vue -->
<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="rounded-2xl border border-gray-200 bg-gradient-to-br from-white to-gray-50 dark:from-gray-900 dark:to-gray-800 dark:border-gray-800 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center shadow-lg">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Vue Coordination</h1>
              <p class="text-gray-500 dark:text-gray-400">
                {{ activite?.nom }} · Suivi et validation par utilisateur
              </p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <!-- Sélecteur d'activité -->
            <select v-model="selectedActiviteId" @change="handleActiviteChange" class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
              <option value="">Sélectionner une activité</option>
              <option v-for="act in activites" :key="act.id" :value="act.id">
                {{ act.nom }} ({{ act.projet?.nom }})
              </option>
            </select>

            <button @click="loadData" :disabled="loading || !selectedActiviteId" class="p-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors disabled:opacity-50" title="Actualiser">
              <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Filtres -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
          <!-- Filtre par membre -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Filtrer par membre</label>
            <select v-model="filters.user_id" @change="loadData" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
              <option value="">Tous les membres</option>
              <option v-for="member in activityMembers" :key="member.id" :value="member.id">
                {{ member.nom }} ({{ member.email }})
              </option>
            </select>
          </div>

          <!-- Filtre par période -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Période</label>
            <select v-model="filters.period" @change="handlePeriodChange" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
              <option value="current_week">Semaine en cours</option>
              <option value="last_week">Semaine dernière</option>
              <option value="current_month">Mois en cours</option>
              <option value="last_month">Mois dernier</option>
              <option value="custom">Personnalisée</option>
            </select>
          </div>

          <!-- Dates personnalisées -->
          <div v-if="filters.period === 'custom'" class="flex items-end gap-2">
            <div class="flex-1">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Du</label>
              <DatePicker
                v-model="filters.start_date"
                :enable-time-picker="false"
                auto-apply
                :format="'dd/MM/yyyy'"
                :locale="'fr'"
                :dark="isDark"
                placeholder="Date de début"
                class="w-full date-input"
              >
                <template #input-icon>
                  <CalendarIcon class="w-4 h-4 text-gray-400" />
                </template>
              </DatePicker>
            </div>
            <div class="flex-1">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Au</label>
              <DatePicker
                v-model="filters.end_date"
                :enable-time-picker="false"
                auto-apply
                :format="'dd/MM/yyyy'"
                :locale="'fr'"
                :dark="isDark"
                :min-date="filters.start_date"
                placeholder="Date de fin"
                class="w-full date-input"
              >
                <template #input-icon>
                  <CalendarIcon class="w-4 h-4 text-gray-400" />
                </template>
              </DatePicker>
            </div>
          </div>
        </div>

        <!-- Statistiques globales -->
        <div v-if="usersData.length > 0" class="grid grid-cols-4 gap-4">
          <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">Membres</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ totalUsers }}</p>
          </div>
          <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">Tâches totales</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ totalTasks }}</p>
          </div>
          <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">Progression moyenne</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ averageProgress }}%</p>
          </div>
          <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">En attente validation</p>
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-1">{{ totalPendingValidation }}</p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between mt-4">
          <div class="flex items-center gap-3">
            <button
              @click="generateReport"
              :disabled="!selectedActiviteId"
              class="px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 disabled:opacity-50 transition-colors flex items-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Générer Rapport
            </button>

            <button
              @click="exportToExcel"
              :disabled="!selectedActiviteId || usersData.length === 0"
              class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors flex items-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Exporter Excel
            </button>
          </div>

          <div class="text-sm text-gray-500 dark:text-gray-400">
            Données mises à jour: {{ lastUpdate }}
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500 mx-auto mb-4"></div>
          <p class="text-gray-600 dark:text-gray-400">Chargement...</p>
        </div>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="rounded-2xl border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-6">
        <p class="text-red-700 dark:text-red-300">{{ error }}</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="!selectedActiviteId" class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-12 text-center">
        <p class="text-gray-500 dark:text-gray-400">Sélectionnez une activité pour voir la répartition des tâches</p>
      </div>

      <div v-else-if="filteredUsersData.length === 0" class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-12 text-center">
        <p class="text-gray-500 dark:text-gray-400">Aucune tâche ne correspond aux critères sélectionnés</p>
      </div>

      <!-- Grille des utilisateurs -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div v-for="userData in filteredUsersData" :key="userData.user.id" class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] overflow-hidden">
          <!-- User Header -->
          <div class="p-6 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-900 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4">
              <img v-if="userData.user.avatar" :src="getImageUrl(userData.user.avatar)" :alt="userData.user.nom" class="w-16 h-16 rounded-full" />
              <div v-else class="w-16 h-16 rounded-full flex items-center justify-center text-xl font-bold text-white" :style="{ backgroundColor: stringToColor(userData.user.nom) }">
                {{ getInitials(userData.user.nom) }}
              </div>

              <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ userData.user.nom }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ userData.user.email }}</p>
                <div class="flex items-center gap-2 mt-1">
                  <span class="text-xs px-2 py-1 rounded-full" :class="getRoleBadgeClass(userData.user.role)">
                    {{ getUserRoleLabel(userData.user.role) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Statistiques utilisateur -->
            <div class="grid grid-cols-4 gap-2 mt-4">
              <div class="text-center">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ userData.stats.total }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Total</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ userData.stats.termine }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Terminé</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ userData.stats.en_cours }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">En cours</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold" :class="userData.stats.en_retard > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-400'">
                  {{ userData.stats.en_retard }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Retard</p>
              </div>
            </div>

            <!-- Barre de progression -->
            <div class="mt-4">
              <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                <span>Progression globale</span>
                <span class="font-medium">{{ userData.stats.progression_moyenne }}%</span>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="h-2 rounded-full transition-all" :class="getProgressColor(userData.stats.progression_moyenne)" :style="{ width: `${userData.stats.progression_moyenne}%` }"></div>
              </div>
            </div>

            <!-- Indicateurs de performance -->
            <div class="grid grid-cols-3 gap-2 mt-3 text-center">
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Taux réalisation</p>
                <p class="text-sm font-bold" :class="getPerformanceColor(userData.stats.taux_realisation_moyen)">
                  {{ userData.stats.taux_realisation_moyen }}%
                </p>
              </div>
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Heures estimées</p>
                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ userData.stats.heures_estimees }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Heures réelles</p>
                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ userData.stats.heures_reelles }}</p>
              </div>
            </div>
          </div>

          <!-- Liste des tâches -->
          <div class="p-4 space-y-2 max-h-96 overflow-y-auto">
            <div v-for="tache in userData.taches" :key="tache.tache_id" class="p-3 rounded-lg border transition-all hover:shadow-md cursor-pointer" :class="getTacheCardClass(tache)" @click="viewTacheDetails(tache.tache_id)">
              <div class="flex items-start justify-between gap-2">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-1">
                    <span v-if="tache.code" class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ tache.code }}</span>
                    <span class="text-xs font-medium px-2 py-0.5 rounded" :class="getPriorityClass(tache.priorite)">
                      {{ getPriorityIcon(tache.priorite) }}
                    </span>
                    <span v-if="tache.is_overdue && tache.statut_individuel !== 'termine'" class="text-xs font-medium px-2 py-0.5 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 rounded">
                      En retard
                    </span>
                  </div>
                  <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ tache.titre }}</p>
                  <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs px-2 py-0.5 rounded" :class="getStatusBadgeClass(tache.statut_individuel)">
                      {{ getStatusLabel(tache.statut_individuel) }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ tache.progression_individuelle }}%</span>

                    <!-- Badge résultat -->
                    <span v-if="tache.has_result" class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1">
                      <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                      </svg>
                      Résultat
                    </span>

                    <!-- Badge validation -->
                    <span v-if="tache.validation_status === 'pending'" class="text-xs px-2 py-0.5 bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300 rounded flex items-center gap-1 animate-pulse">
                      <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                      </svg>
                      En attente
                    </span>
                  </div>
                  <p v-if="tache.echeance" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Échéance: {{ formatDate(tache.echeance) }}
                  </p>
                </div>

                <!-- Actions de validation -->
                <div v-if="canValidate && tache.has_result && tache.validation_status === 'pending'" class="flex flex-col gap-1">
                  <button @click.stop="handleValidate(tache, userData.user)" class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors">
                    ✓ Valider
                  </button>
                  <button @click.stop="handleReject(tache, userData.user)" class="px-2 py-1 text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors">
                    ✗ Refuser
                  </button>
                </div>

                <!-- Statut global vs individuel -->
                <div class="flex flex-col items-end gap-1">
                  <span class="text-xs text-gray-400">Global:</span>
                  <span class="text-xs px-2 py-0.5 rounded" :class="getStatusBadgeClass(tache.statut_global)">
                    {{ getStatusLabel(tache.statut_global) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal détails tâche -->
    <TacheDetailModal v-if="showDetailModal" :tache="currentTache" @close="showDetailModal=false" @edit="handleEditTask" />

    <!-- Modal validation -->
    <ValidationModal v-if="showValidationModal" :tache="currentTacheForValidation" :user="currentUserForValidation" :action="validationAction" @close="showValidationModal=false" @validated="handleValidationComplete" />

    <!-- Modal rapport -->
    <ReportModal v-if="showReportModal" :activite="activite" :usersData="filteredUsersData" :filters="filters" @close="showReportModal=false" />

  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { CalendarIcon } from '@heroicons/vue/24/outline'
import DatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import TacheDetailModal from '@/components/taches/TacheDetailModal.vue'
import ValidationModal from '@/components/taches/resultats/ValidationModal.vue'
import ReportModal from '@/components/reports/ReportModal.vue'
import api from '@/api/axios'

// State
const activites = ref([])
const selectedActiviteId = ref(null)
const usersData = ref([])
const activityMembers = ref([])
const activite = ref(null)
const loading = ref(false)
const error = ref(null)
const router = useRouter()
const showDetailModal = ref(false)
const showValidationModal = ref(false)
const showReportModal = ref(false)
const currentTache = ref(null)
const currentTacheForValidation = ref(null)
const currentUserForValidation = ref(null)
const validationAction = ref('validate')
const lastUpdate = ref(null)

const filters = ref({
  period: 'current_week',
  start_date: null,
  end_date: null,
  user_id: ''
})

const isDark = computed(() => document.documentElement.classList.contains('dark'))

// Computed
const totalUsers = computed(() => filteredUsersData.value.length)
const totalTasks = computed(() => filteredUsersData.value.reduce((sum, u) => sum + u.stats.total, 0))
const totalPendingValidation = computed(() => {
  return filteredUsersData.value.reduce((sum, u) => {
    return sum + u.taches.filter(t => t.has_result && t.validation_status === 'pending').length
  }, 0)
})
const averageProgress = computed(() => {
  if (filteredUsersData.value.length === 0) return 0
  const sum = filteredUsersData.value.reduce((acc, u) => acc + u.stats.progression_moyenne, 0)
  return Math.round(sum / filteredUsersData.value.length)
})

const filteredUsersData = computed(() => {
  if (!filters.value.user_id) {
    return usersData.value
  }
  return usersData.value.filter(userData => userData.user.id.toString() === filters.value.user_id.toString())
})

const canValidate = computed(() => {
  // Vérifier si l'utilisateur connecté peut valider
  return true // TODO: Implémenter logique de permissions
})

// Methods
async function loadActivites() {
  try {
    const { data } = await api.get('/activites/mes-activites')
    activites.value = data.data || data || []
  } catch (err) {
    console.error('Erreur chargement activités:', err)
  }
}

async function loadActivityMembers() {
  if (!selectedActiviteId.value) {
    activityMembers.value = []
    return
  }

  try {
    const { data } = await api.get(`/activites/${selectedActiviteId.value}/membres`)
    activityMembers.value = data.data || data || []
  } catch (err) {
    console.error('Erreur chargement membres:', err)
    activityMembers.value = []
  }
}

async function loadData() {
  if (!selectedActiviteId.value) return

  loading.value = true
  error.value = null

  try {
    const params = {
      period: filters.value.period,
      user_id: filters.value.user_id
    }

    if (filters.value.period === 'custom' && filters.value.start_date && filters.value.end_date) {
      params.start_date = formatDateForApi(filters.value.start_date)
      params.end_date = formatDateForApi(filters.value.end_date)
    }

    const { data } = await api.get(`/activites/${selectedActiviteId.value}/taches-by-user`, { params })
    usersData.value = data.data || []
    activite.value = data.activite
    lastUpdate.value = new Date().toLocaleString('fr-FR')
    
    console.log('✅ Données chargées:', usersData.value.length, 'utilisateurs')
  } catch (err) {
    console.error('❌ Erreur:', err)
    error.value = err.response?.data?.message || 'Erreur de chargement'
  } finally {
    loading.value = false
  }
}

function handleEditTask(tache) {
  showDetailModal.value = false
  router.push(`/taches/${tache.id}`)
}

function handleActiviteChange() {
  filters.value.user_id = ''
  loadActivityMembers()
  loadData()
}

function handlePeriodChange() {
  if (filters.value.period !== 'custom') {
    initializeDates()
  }
  loadData()
}

function initializeDates() {
  const today = new Date()
  let startDate, endDate

  switch (filters.value.period) {
    case 'current_week':
      startDate = new Date(today.setDate(today.getDate() - today.getDay() + 1))
      endDate = new Date(today.setDate(today.getDate() - today.getDay() + 7))
      break
    case 'last_week':
      startDate = new Date(today.setDate(today.getDate() - today.getDay() - 6))
      endDate = new Date(today.setDate(today.getDate() - today.getDay() + 0))
      break
    case 'current_month':
      startDate = new Date(today.getFullYear(), today.getMonth(), 1)
      endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0)
      break
    case 'last_month':
      startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1)
      endDate = new Date(today.getFullYear(), today.getMonth(), 0)
      break
    default:
      startDate = new Date(today.setDate(today.getDate() - today.getDay() + 1))
      endDate = new Date(today.setDate(today.getDate() - today.getDay() + 7))
  }

  filters.value.start_date = startDate
  filters.value.end_date = endDate
}

function formatDateForApi(date) {
  if (!date) return null
  const d = new Date(date)
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function generateReport() {
  if (!selectedActiviteId.value) {
    alert('Veuillez sélectionner une activité')
    return
  }
  showReportModal.value = true
}

async function exportToExcel() {
  if (!selectedActiviteId.value || filteredUsersData.value.length === 0) {
    alert('Aucune donnée à exporter')
    return
  }

  try {
    const params = {
      period: filters.value.period,
      user_id: filters.value.user_id,
      export: 'excel'
    }

    if (filters.value.period === 'custom' && filters.value.start_date && filters.value.end_date) {
      params.start_date = formatDateForApi(filters.value.start_date)
      params.end_date = formatDateForApi(filters.value.end_date)
    }

    const response = await api.get(`/reports/activite/${selectedActiviteId.value}/user-tasks`, {
      params,
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `rapport-taches-${activite.value.nom}-${new Date().toISOString().split('T')[0]}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Erreur export Excel:', err)
    alert('Erreur lors de l\'export Excel')
  }
}

async function viewTacheDetails(tacheId) {
  try {
    const { data } = await api.get(`/taches/${tacheId}`)
    currentTache.value = data.data
    showDetailModal.value = true
  } catch (err) {
    console.error('Erreur:', err)
    alert('Erreur lors du chargement')
  }
}

function handleValidate(tache, user) {
  currentTacheForValidation.value = tache
  currentUserForValidation.value = user
  validationAction.value = 'validate'
  showValidationModal.value = true
}

function handleReject(tache, user) {
  currentTacheForValidation.value = tache
  currentUserForValidation.value = user
  validationAction.value = 'reject'
  showValidationModal.value = true
}

async function handleValidationComplete() {
  showValidationModal.value = false
  await loadData()
  alert('✅ Validation effectuée avec succès !')
}

// Helpers
const getProgressColor = (progress) => {
  if (progress < 30) return 'bg-red-500'
  if (progress < 70) return 'bg-amber-500'
  return 'bg-green-500'
}

const getPerformanceColor = (value) => {
  if (value >= 90) return 'text-green-600 dark:text-green-400'
  if (value >= 70) return 'text-amber-600 dark:text-amber-400'
  return 'text-red-600 dark:text-red-400'
}

const getTacheCardClass = (tache) => {
  const base = 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800'
  if (tache.is_overdue && tache.statut_individuel !== 'termine') {
    return 'border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-900/20'
  }
  if (tache.statut_individuel === 'termine') {
    return 'border-green-300 dark:border-green-800 bg-green-50 dark:bg-green-900/20'
  }
  return base
}

const getStatusBadgeClass = (statut) => {
  const classes = {
    'termine': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    'en_cours': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    'a_faire': 'bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-300'
  }
  return classes[statut] || classes.a_faire
}

const getStatusLabel = (statut) => {
  const labels = {
    'termine': 'Terminé',
    'en_cours': 'En cours',
    'a_faire': 'À faire'
  }
  return labels[statut] || statut
}

const getPriorityClass = (priorite) => {
  const classes = {
    faible: 'bg-green-100 text-green-800',
    moyenne: 'bg-amber-100 text-amber-800',
    elevee: 'bg-orange-100 text-orange-800',
    critique: 'bg-red-100 text-red-800'
  }
  return classes[priorite] || classes.moyenne
}

const getPriorityIcon = (priorite) => {
  const icons = {
    faible: '🟢',
    moyenne: '🟡',
    elevee: '🟠',
    critique: '🔴'
  }
  return icons[priorite] || '⚪'
}

const getRoleBadgeClass = (role) => {
  const classes = {
    responsable: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
    membre: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    observateur: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
  }
  return classes[role] || classes.membre
}

const getUserRoleLabel = (role) => {
  const labels = {
    responsable: 'Responsable',
    membre: 'Membre',
    observateur: 'Observateur'
  }
  return labels[role] || 'Membre'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}

const getImageUrl = (path) => {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${import.meta.env.VITE_APP_URL}/storage/${path}`
}

const stringToColor = (str) => {
  let hash = 0
  for (let i = 0; i < str.length; i++) {
    hash = str.charCodeAt(i) + ((hash << 5) - hash)
  }
  return `hsl(${hash % 360}, 65%, 50%)`
}

const getInitials = (name) => {
  const parts = name.trim().split(' ')
  if (parts.length === 1) return parts[0].charAt(0).toUpperCase()
  return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase()
}

// Lifecycle
onMounted(async () => {
  await loadActivites()
  initializeDates()
})
</script>

<style scoped>
.date-input :deep(.dp__input) {
  width: 100%;
  padding: 0.5rem 0.75rem 0.5rem 2rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  background-color: white;
  color: #1f2937;
  font-size: 0.875rem;
}

.dark .date-input :deep(.dp__input) {
  border-color: #4b5563;
  background-color: #1f2937;
  color: white;
}

.date-input :deep(.dp__input_icon) {
  left: 0.5rem;
  padding: 0;
}
</style>