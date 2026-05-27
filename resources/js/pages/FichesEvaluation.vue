<!-- resources/js/pages/FicheEvaluation.vue -->
<template>
  <AdminLayout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
      
      <!-- Header -->
      <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-30 ">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <!-- Titre et info semaine -->
            <div>
              <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <div class="w-12 h-12 rounded-3 flex items-center justify-center">
                  <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                  </svg>
                </div>
                Ma Fiche d'évaluation hebdomadaire
              </h1>
              <p class="text-gray-600 dark:text-gray-400 mt-2 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Semaine {{ selectedWeek }} - {{ selectedYear }} 
                <span class="text-sm">({{ formatDateRange(weekDates.start, weekDates.end) }})</span>
              </p>
            </div>

            <!-- Sélecteur de semaine et filtres -->
            <div class="flex flex-wrap items-center gap-3">
              <!-- Sélecteur de semaine -->
              <div class="flex items-center gap-2">
                <button
                  @click="previousWeek"
                  class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                  </svg>
                </button>
                
                <select
                  v-model="selectedWeek"
                  @change="loadWeekData"
                  class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                  <option v-for="week in 53" :key="week" :value="week">
                    Semaine {{ week }}
                  </option>
                </select>

                <select
                  v-model="selectedYear"
                  @change="loadWeekData"
                  class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                  <option v-for="year in years" :key="year" :value="year">
                    {{ year }}
                  </option>
                </select>

                <button
                  @click="nextWeek"
                  class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </button>

                <button
                  @click="goToCurrentWeek"
                  class="px-4 py-2 bg-blue-500 text-white rounded-3 hover:bg-blue-600 transition-colors font-medium">
                  Aujourd'hui
                </button>
              </div>

              <!-- Filtre activité -->
              <select
                v-model="selectedActivite"
                @change="filterByActivite"
                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                <option :value="null">Toutes les activités</option>
                <option v-for="activite in activites" :key="activite.id" :value="activite.id">
                  {{ activite.nom }}
                </option>
              </select>

              <!-- Export -->
              <button
                @click="exportToPDF"
                class="px-4 py-2 text-white rounded-3 font-medium transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Exporter PDF
              </button>
            </div>
          </div>

          <!-- Statistiques rapides -->
          <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-6">
            <StatCard
              title="Total tâches"
              :value="stats.total"
              icon="clipboard-list"
              color="gray"
            />
            <StatCard
              title="À faire"
              :value="stats.a_faire"
              icon="clock"
              color="slate"
            />
            <StatCard
              title="En cours"
              :value="stats.en_cours"
              :progress="stats.total > 0 ? Math.round((stats.en_cours / stats.total) * 100) : 0"
              icon="play"
              color="blue"
            />
            <StatCard
              title="Terminées"
              :value="stats.termine"
              :progress="stats.total > 0 ? Math.round((stats.termine / stats.total) * 100) : 0"
              icon="check-circle"
              color="green"
            />
            <StatCard
              title="En retard"
              :value="stats.en_retard"
              :alert="stats.en_retard > 0"
              icon="exclamation"
              color="red"
            />
          </div>
        </div>
      </div>

      <!-- Contenu principal -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Loading -->
        <div v-if="loading" class="flex flex-col items-center justify-center py-16">
          <div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-500 border-t-transparent mb-4"></div>
          <p class="text-gray-600 dark:text-gray-400">Chargement de votre fiche d'évaluation...</p>
        </div>

        <!-- Empty state -->
        <div v-else-if="filteredTasks.length === 0" class="text-center py-16">
          <div class="w-24 h-24 mx-auto mb-6 rounded-full flex items-center justify-center">
            <svg class="w-12 h-12 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
          <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
            Aucune tâche en cours pour cette semaine
          </h3>
          <p class="text-gray-600 dark:text-gray-400 mb-6">
            {{ selectedActivite 
              ? 'Aucune tâche en cours pour cette activité.' 
              : 'Toutes vos tâches sont terminées et validées !' }}
          </p>
          <button
            @click="goToCurrentWeek"
            class="inline-flex items-center px-6 py-3 bg-blue-500 text-white rounded-3 hover:bg-blue-600 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Voir la semaine actuelle
          </button>
        </div>

        <!-- Table d'évaluation -->
        <div v-else class="bg-white dark:bg-gray-800 rounded-3 overflow-hidden">
          
          <!-- Message informatif -->
          <div class="bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200 dark:border-blue-800 px-6 py-3">
            <p class="text-sm text-blue-700 dark:text-blue-300 flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Cette fiche affiche vos tâches en cours et terminées en attente de validation. Les tâches complètement validées disparaissent automatiquement.
            </p>
          </div>

          <!-- Table header -->
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="border-b-2 border-gray-200 dark:border-gray-700">
                <tr>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    N°
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider min-w-[200px]">
                    Tâche
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider min-w-[180px]">
                    Résultats attendus
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    Échéance
                  </th>
                  <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    Mon Statut
                  </th>
                  <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    Ma Progression
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider min-w-[180px]">
                    Résultats obtenus
                  </th>
                  <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    Taux
                  </th>
                  <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    Validation
                  </th>
                  <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <EvaluationRow
                  v-for="(task, index) in filteredTasks"
                  :key="task.id"
                  :task="task"
                  :index="index + 1"
                  @add-result="openResultForm"
                  @edit-result="editResult"
                  @view-result="viewResult"
                  @refresh="loadWeekData"
                />
              </tbody>
            </table>
          </div>
        </div>

        <!-- Résumé de la semaine -->
        <div v-if="filteredTasks.length > 0" class="mt-8 rounded-3 border border-blue-200 dark:border-blue-800 p-6">
          <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Résumé de ma semaine
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-3 p-4">
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Mon taux de complétion</p>
              <div class="flex items-end gap-2">
                <p class="text-3xl font-bold text-blue-600">{{ stats.completionRate }}%</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ stats.termine }}/{{ stats.total }}</p>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
                <div class="bg-blue-500 h-2 rounded-full transition-all" :style="{ width: stats.completionRate + '%' }"></div>
              </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-3 p-4">
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Résultats validés</p>
              <div class="flex items-end gap-2">
                <p class="text-3xl font-bold text-purple-600">{{ stats.validationRate }}%</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ stats.valide_n1 }}/{{ stats.avec_resultat }}</p>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
                <div class="bg-purple-500 h-2 rounded-full transition-all" :style="{ width: stats.validationRate + '%' }"></div>
              </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-3 p-4">
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Heures travaillées</p>
              <div class="flex items-end gap-2">
                <p class="text-3xl font-bold text-green-600">{{ stats.actual_hours }}h</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">/ {{ stats.estimated_hours }}h</p>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
                <div 
                  class="h-2 rounded-full transition-all"
                  :class="stats.actual_hours > stats.estimated_hours ? 'bg-red-500' : 'bg-green-500'"
                  :style="{ width: Math.min((stats.actual_hours / stats.estimated_hours) * 100, 100) + '%' }">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Résultat -->
      <ResultatFormModal
        v-if="showResultModal"
        :tache="selectedTask"
        :resultat="selectedResult"
        @close="closeResultModal"
        @saved="handleResultSaved"
      />

      <!-- Modal Détail Résultat -->
      <ResultatDetailModal
        v-if="showResultDetailModal"
        :resultat="selectedResult"
        :tache="selectedTask"
        @close="showResultDetailModal = false"
        @edit="editFromDetail"
      />
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api/axios'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import StatCard from '@/components/common/StatCard.vue'
import EvaluationRow from '@/components/taches/EvaluationRow.vue'
import ResultatFormModal from '@/components/taches/ResultatForm.vue'
import ResultatDetailModal from '@/components/taches/resultats/ResultatDetailModal.vue'

// État réactif
const router = useRouter()
const loading = ref(false)
const tasks = ref([])
const activites = ref([])
const selectedWeek = ref(getCurrentWeek())
const selectedYear = ref(new Date().getFullYear())
const selectedActivite = ref(null)
const showResultModal = ref(false)
const showResultDetailModal = ref(false)
const selectedTask = ref(null)
const selectedResult = ref(null)

// Plage d'années de 1990 à aujourd'hui
const years = computed(() => {
  const currentYear = new Date().getFullYear()
  const startYear = 1990
  const yearsList = []
  
  for (let year = startYear; year <= currentYear; year++) {
    yearsList.push(year)
  }
  
  return yearsList.reverse()
})

const weekDates = computed(() => {
  return getWeekDateRange(selectedYear.value, selectedWeek.value)
})

const filteredTasks = computed(() => {
  let filtered = tasks.value

  if (selectedActivite.value) {
    filtered = filtered.filter(task => task.activite.id === selectedActivite.value)
  }

  return filtered
})

const stats = computed(() => {
  const total = filteredTasks.value.length
  
  // ✅ Utiliser my_status pour les statistiques
  const a_faire = filteredTasks.value.filter(t => t.my_status?.statut === 'a_faire').length
  const en_cours = filteredTasks.value.filter(t => t.my_status?.statut === 'en_cours').length
  const termine = filteredTasks.value.filter(t => t.my_status?.statut === 'termine').length
  
  const avec_resultat = filteredTasks.value.filter(t => t.my_result?.soumis_le).length
  const valide_n1 = filteredTasks.value.filter(t => t.my_result?.valide_par_n1).length
  const valide_n2 = filteredTasks.value.filter(t => t.my_result?.valide_par_n2).length
  
  // En retard = échéance passée ET pas terminé individuellement
  const en_retard = filteredTasks.value.filter(t => 
    t.is_overdue && t.my_status?.statut !== 'termine'
  ).length
  
  const estimated_hours = filteredTasks.value.reduce((sum, t) => {
    const hours = parseFloat(t.estimated_hours) || 0
    return sum + hours
  }, 0)

  const actual_hours = filteredTasks.value.reduce((sum, t) => {
    const hours = parseFloat(t.actual_hours) || 0
    return sum + hours
  }, 0)
  
  return {
    total,
    a_faire,
    en_cours,
    termine,
    avec_resultat,
    valide_n1,
    valide_n2,
    en_retard,
    completionRate: total > 0 ? Math.round((termine / total) * 100) : 0,
    validationRate: avec_resultat > 0 ? Math.round((valide_n1 / avec_resultat) * 100) : 0,
    estimated_hours: estimated_hours.toFixed(1),
    actual_hours: actual_hours.toFixed(1)
  }
})

// Méthodes
function getCurrentWeek() {
  const now = new Date()
  const start = new Date(now.getFullYear(), 0, 1)
  const diff = now - start
  const oneWeek = 1000 * 60 * 60 * 24 * 7
  return Math.ceil(diff / oneWeek)
}

function getWeekDateRange(year, week) {
  const simple = new Date(year, 0, 1 + (week - 1) * 7)
  const dow = simple.getDay()
  const ISOweekStart = simple
  if (dow <= 4)
    ISOweekStart.setDate(simple.getDate() - simple.getDay() + 1)
  else
    ISOweekStart.setDate(simple.getDate() + 8 - simple.getDay())
  
  const end = new Date(ISOweekStart)
  end.setDate(end.getDate() + 6)
  
  return {
    start: ISOweekStart,
    end: end
  }
}

function formatDateRange(start, end) {
  const options = { day: 'numeric', month: 'short' }
  return `${start.toLocaleDateString('fr-FR', options)} - ${end.toLocaleDateString('fr-FR', options)}`
}

async function loadWeekData() {
  loading.value = true
  try {
    const { data } = await api.get('/evaluations/mon-rapport-hebdomadaire', {
      params: {
        week_number: selectedWeek.value,
        year: selectedYear.value
      }
    })
    
    tasks.value = data.all_tasks || []
    
    // Extraire les activités uniques
    const uniqueActivites = [...new Map(
      tasks.value.map(task => [task.activite.id, task.activite])
    ).values()]
    activites.value = uniqueActivites
  } catch (error) {
    console.error('Error loading week data:', error)
  } finally {
    loading.value = false
  }
}

function previousWeek() {
  if (selectedWeek.value === 1) {
    selectedWeek.value = 52
    selectedYear.value--
  } else {
    selectedWeek.value--
  }
  loadWeekData()
}

function nextWeek() {
  if (selectedWeek.value === 52) {
    selectedWeek.value = 1
    selectedYear.value++
  } else {
    selectedWeek.value++
  }
  loadWeekData()
}

function goToCurrentWeek() {
  selectedWeek.value = getCurrentWeek()
  selectedYear.value = new Date().getFullYear()
  loadWeekData()
}

function filterByActivite() {
  // Le computed filteredTasks gère déjà le filtrage
}

function openResultForm(task) {
  selectedTask.value = task
  selectedResult.value = null
  showResultModal.value = true
}

function editResult(task, result) {
  selectedTask.value = task
  selectedResult.value = result
  showResultModal.value = true
}

function viewResult(task, result) {
  selectedTask.value = task
  selectedResult.value = result
  showResultDetailModal.value = true
}

function editFromDetail(result) {
  showResultDetailModal.value = false
  selectedResult.value = result
  showResultModal.value = true
}

function closeResultModal() {
  showResultModal.value = false
  selectedTask.value = null
  selectedResult.value = null
}

function handleResultSaved() {
  closeResultModal()
  loadWeekData()
}

async function exportToPDF() {
  try {
    const response = await api.post('/evaluations/export-pdf', {
      week_number: selectedWeek.value,
      year: selectedYear.value
    }, {
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `fiche-evaluation-S${selectedWeek.value}-${selectedYear.value}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    console.error('Error exporting PDF:', error)
    alert('Erreur lors de l\'export PDF')
  }
}

onMounted(() => {
  loadWeekData()
})

</script>