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
            <select v-model="selectedActiviteId" @change="loadData" class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
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

      <div v-else-if="usersData.length === 0" class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-12 text-center">
        <p class="text-gray-500 dark:text-gray-400">Aucune tâche assignée dans cette activité</p>
      </div>

      <!-- Grille des utilisateurs -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div v-for="userData in usersData" :key="userData.user.id" class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] overflow-hidden">
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
    <TacheDetailModal
      v-if="showDetailModal"
      :tache="currentTache"
      @close="showDetailModal = false"
    />

    <!-- Modal validation -->
    <ValidationModal
      v-if="showValidationModal"
      :tache="currentTacheForValidation"
      :user="currentUserForValidation"
      :action="validationAction"
      @close="showValidationModal = false"
      @validated="handleValidationComplete"
    />
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import TacheDetailModal from '@/components/taches/TacheDetailModal.vue'
import ValidationModal from '@/components/taches/ValidationModal.vue'
import api from '@/api/axios'

// State
const activites = ref([])
const selectedActiviteId = ref(null)
const usersData = ref([])
const activite = ref(null)
const loading = ref(false)
const error = ref(null)
const showDetailModal = ref(false)
const showValidationModal = ref(false)
const currentTache = ref(null)
const currentTacheForValidation = ref(null)
const currentUserForValidation = ref(null)
const validationAction = ref('validate')

// Computed
const totalUsers = computed(() => usersData.value.length)
const totalTasks = computed(() => usersData.value.reduce((sum, u) => sum + u.stats.total, 0))
const totalPendingValidation = computed(() => {
  return usersData.value.reduce((sum, u) => {
    return sum + u.taches.filter(t => t.has_result && t.validation_status === 'pending').length
  }, 0)
})
const averageProgress = computed(() => {
  if (usersData.value.length === 0) return 0
  const sum = usersData.value.reduce((acc, u) => acc + u.stats.progression_moyenne, 0)
  return Math.round(sum / usersData.value.length)
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

async function loadData() {
  if (!selectedActiviteId.value) return

  loading.value = true
  error.value = null

  try {
    const { data } = await api.get(`/activites/${selectedActiviteId.value}/taches-by-user`)
    usersData.value = data.data || []
    activite.value = data.activite
    console.log('✅ Données chargées:', usersData.value.length, 'utilisateurs')
  } catch (err) {
    console.error('❌ Erreur:', err)
    error.value = err.response?.data?.message || 'Erreur de chargement'
  } finally {
    loading.value = false
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
})
</script>