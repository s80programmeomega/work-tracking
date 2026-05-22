<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- ── Header: identité de l'agent + score global + indicateurs ───── -->
      <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-800">
        <div v-if="loading && !sheet" class="text-center py-8 text-gray-500">
          <i class="fas fa-spinner fa-spin mr-2"></i>Chargement de la fiche…
        </div>

        <div v-else-if="error" dusk="sheet-error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded">
          {{ error }}
        </div>

        <div v-else-if="sheet" class="flex items-start justify-between gap-6 flex-wrap">
          <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-brand-600 text-white flex items-center justify-center text-2xl font-semibold">
              {{ initials }}
            </div>
            <div>
              <h1 dusk="sheet-user-nom" class="text-2xl font-semibold text-gray-900 dark:text-white">
                {{ sheet.user.nom_complet || sheet.user.nom }}
              </h1>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ sheet.user.email }}</p>
              <p class="text-xs text-gray-400 mt-1">
                Période: {{ sheet.periode_start }} → {{ sheet.periode_end }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-6">
            <!-- Score global -->
            <div class="text-center">
              <div dusk="sheet-score-global" class="text-4xl font-bold" :class="scoreColor(sheet.score_global)">
                {{ Math.round(sheet.score_global * 100) }}%
              </div>
              <p class="text-xs text-gray-500 uppercase tracking-wider mt-1">Score global</p>
            </div>

            <!-- Badge escalades abusives -->
            <div
              v-if="sheet.indicators.escalades_abusives"
              dusk="sheet-escalades-badge"
              class="px-3 py-2 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-sm font-medium"
            >
              <i class="fas fa-exclamation-triangle mr-1"></i>
              Escalades abusives
            </div>

            <!-- Alerte renvois injustifiés -->
            <div
              v-if="sheet.indicators.unjustified_alert"
              dusk="sheet-unjustified-alert"
              class="px-3 py-2 rounded-lg bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 text-sm font-medium"
            >
              <i class="fas fa-flag mr-1"></i>
              Renvois injustifiés: {{ Math.round(sheet.indicators.unjustified_return_rate * 100) }}%
            </div>

            <button
              v-if="sheet.meta.can_export"
              dusk="sheet-export-btn"
              @click="exportSheet"
              class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 text-sm"
            >
              <i class="fas fa-download mr-2"></i>Exporter
            </button>
          </div>
        </div>
      </div>

      <!-- ── Filtres période ──────────────────────────────────────────── -->
      <div v-if="sheet" class="bg-white dark:bg-gray-900 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-800">
        <div class="flex items-end gap-4 flex-wrap">
          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Du</label>
            <input
              v-model="filters.start"
              type="date"
              dusk="sheet-filter-start"
              class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Au</label>
            <input
              v-model="filters.end"
              type="date"
              dusk="sheet-filter-end"
              class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Statut</label>
            <select
              v-model="filters.statut"
              dusk="sheet-filter-statut"
              class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm"
            >
              <option value="">Tous</option>
              <option value="a_faire">À faire</option>
              <option value="en_cours">En cours</option>
              <option value="termine">Terminé</option>
            </select>
          </div>
          <button
            @click="applyFilters"
            dusk="sheet-apply-filters"
            class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 text-sm"
          >
            <i class="fas fa-filter mr-2"></i>Appliquer
          </button>
          <button
            @click="resetFilters"
            class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg text-sm"
          >
            Réinitialiser
          </button>
        </div>
      </div>

      <!-- ── Indicateur qualité des renvois (donut justifié vs injustifié) ─ -->
      <div v-if="sheet" class="bg-white dark:bg-gray-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-800">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Qualité des renvois</h2>
          <span
            v-if="sheet.indicators.unjustified_alert"
            dusk="return-quality-alert"
            class="text-xs px-2 py-1 rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 font-medium"
          >
            <i class="fas fa-exclamation-triangle mr-1"></i>
            Au-dessus du seuil (40%)
          </span>
        </div>

        <div class="flex items-center gap-8 flex-wrap">
          <!-- Donut SVG inline — pas de dépendance externe.
               Chaque secteur est un cercle stroked avec stroke-dasharray
               calculé à partir de la circonférence (2πr=100) et du %. -->
          <svg
            dusk="return-quality-donut"
            viewBox="0 0 36 36"
            class="w-32 h-32"
          >
            <!-- Fond gris (100% du cercle) -->
            <circle
              cx="18" cy="18" r="15.915"
              fill="none" stroke="currentColor"
              class="text-gray-200 dark:text-gray-800"
              stroke-width="3.5"
            />
            <!-- Secteur "justifiés" en vert -->
            <circle
              cx="18" cy="18" r="15.915"
              fill="none" stroke="currentColor"
              class="text-green-500"
              stroke-width="3.5"
              :stroke-dasharray="`${donutJustifiedPercent} ${100 - donutJustifiedPercent}`"
              stroke-dashoffset="25"
              transform="rotate(-90 18 18)"
            />
            <text
              x="18" y="20"
              text-anchor="middle"
              class="fill-gray-900 dark:fill-white"
              font-size="6"
              font-weight="600"
            >
              {{ Math.round(donutJustifiedPercent) }}%
            </text>
          </svg>

          <div class="flex-1 space-y-2 text-sm min-w-[180px]">
            <div class="flex items-center gap-2">
              <span class="w-3 h-3 rounded-full bg-green-500"></span>
              <span class="text-gray-700 dark:text-gray-300">Renvois justifiés</span>
              <strong class="ml-auto text-gray-900 dark:text-white">{{ Math.round(donutJustifiedPercent) }}%</strong>
            </div>
            <div class="flex items-center gap-2">
              <span class="w-3 h-3 rounded-full bg-gray-300 dark:bg-gray-700"></span>
              <span class="text-gray-700 dark:text-gray-300">Renvois injustifiés</span>
              <strong class="ml-auto text-gray-900 dark:text-white">{{ Math.round(sheet.indicators.unjustified_return_rate * 100) }}%</strong>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-200 dark:border-gray-800">
              Un taux d'injustifiés supérieur à 40% déclenche une alerte automatique au manager.
            </p>
          </div>
        </div>
      </div>

      <!-- ── 8 critères ───────────────────────────────────────────────── -->
      <div v-if="sheet" class="bg-white dark:bg-gray-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-800">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Détail des 8 critères</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="(payload, key) in sheet.criteria"
            :key="key"
            :dusk="`criterion-${key}`"
            class="border border-gray-200 dark:border-gray-800 rounded-lg p-4"
          >
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium text-gray-900 dark:text-white">{{ criterionLabel(key) }}</span>
              <span class="text-xs text-gray-500">Poids: {{ Math.round(payload.weight * 100) }}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-2 mb-2">
              <div
                class="h-2 rounded-full transition-all"
                :class="scoreBg(payload.raw)"
                :style="{ width: `${Math.round(payload.raw * 100)}%` }"
              ></div>
            </div>
            <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
              <span>Valeur: <strong class="text-gray-900 dark:text-white">{{ Math.round(payload.raw * 100) }}%</strong></span>
              <span>Pondéré: <strong class="text-gray-900 dark:text-white">{{ Math.round(payload.weighted * 100) }}%</strong></span>
            </div>
          </div>
        </div>
      </div>

      <!-- ── 4 sections paginées ──────────────────────────────────────── -->
      <div v-if="sheet" class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
        <div class="border-b border-gray-200 dark:border-gray-800 flex gap-1 px-4">
          <button
            v-for="s in sections"
            :key="s.id"
            :dusk="`section-tab-${s.id}`"
            @click="switchSection(s.id)"
            class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
            :class="activeSection === s.id ? 'border-brand-600 text-brand-600' : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900'"
          >
            <i :class="['fas', s.icon, 'mr-1']"></i>
            {{ s.label }}
          </button>
        </div>

        <div class="p-4">
          <div v-if="sectionLoading" class="text-center py-8 text-gray-500">
            <i class="fas fa-spinner fa-spin mr-2"></i>Chargement…
          </div>
          <div v-else-if="!sectionItems.length" dusk="section-empty" class="text-center py-8 text-gray-500">
            <i class="fas fa-clipboard text-4xl mb-2 opacity-30"></i>
            <p class="text-sm">Aucun élément dans cette section sur la période.</p>
          </div>
          <ul v-else class="divide-y divide-gray-200 dark:divide-gray-800" dusk="section-items">
            <li
              v-for="item in sectionItems"
              :key="`${activeSection}-${item.id}`"
              class="py-3 flex items-center justify-between"
            >
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                  {{ item.titre }}
                </p>
                <p class="text-xs text-gray-500 truncate">
                  <template v-if="activeSection.startsWith('directed_subtask') || activeSection === 'assignee_subtasks'">
                    {{ item.tache?.activite?.projet?.nom || '—' }} · {{ item.tache?.activite?.nom || '—' }} · Tâche {{ item.tache?.titre }}
                  </template>
                  <template v-else>
                    {{ item.activite?.projet?.nom || '—' }} · {{ item.activite?.nom || '—' }}
                  </template>
                </p>
              </div>
              <div class="flex items-center gap-3">
                <span class="px-2 py-1 rounded text-xs font-semibold" :class="statutBadge(item.statut)">
                  {{ item.statut }}
                </span>
                <button
                  @click="openDrillDown(item)"
                  :dusk="`drilldown-btn-${item.id}`"
                  class="text-brand-600 hover:text-brand-700 text-xs font-medium"
                >
                  <i class="fas fa-info-circle mr-1"></i>Détails
                </button>
              </div>
            </li>
          </ul>

          <!-- Pagination -->
          <div v-if="sectionMeta.last_page > 1" class="mt-4 flex items-center justify-between text-sm">
            <span class="text-gray-600 dark:text-gray-400">
              Page {{ sectionMeta.current_page }} / {{ sectionMeta.last_page }} ({{ sectionMeta.total }} éléments)
            </span>
            <div class="flex gap-2">
              <button
                @click="loadSection(sectionMeta.current_page - 1)"
                :disabled="sectionMeta.current_page <= 1"
                dusk="section-prev"
                class="px-3 py-1 rounded border border-gray-300 dark:border-gray-700 disabled:opacity-50"
              >
                Précédent
              </button>
              <button
                @click="loadSection(sectionMeta.current_page + 1)"
                :disabled="sectionMeta.current_page >= sectionMeta.last_page"
                dusk="section-next"
                class="px-3 py-1 rounded border border-gray-300 dark:border-gray-700 disabled:opacity-50"
              >
                Suivant
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Modal drill-down ────────────────────────────────────────── -->
    <div
      v-if="drillItem"
      dusk="drilldown-modal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      @click.self="drillItem = null"
    >
      <div class="bg-white dark:bg-gray-900 rounded-xl max-w-2xl w-full max-h-[80vh] overflow-y-auto shadow-2xl">
        <div class="p-6 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ drillItem.titre }}</h3>
          <button
            @click="drillItem = null"
            dusk="drilldown-close"
            class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="p-6 space-y-4 text-sm">
          <div>
            <span class="text-gray-500">Statut:</span>
            <strong class="ml-2 text-gray-900 dark:text-white">{{ drillItem.statut }}</strong>
          </div>
          <div v-if="drillItem.taux_realisation !== undefined">
            <span class="text-gray-500">Taux de réalisation:</span>
            <strong class="ml-2">{{ drillItem.taux_realisation }}%</strong>
          </div>
          <div v-if="drillItem.echeance">
            <span class="text-gray-500">Échéance:</span>
            <strong class="ml-2">{{ drillItem.echeance }}</strong>
          </div>
          <!-- Note coefficient 0.5 sur sous-tâches: rappel UX. -->
          <p v-if="isSubtaskSection" class="text-xs text-gray-500 italic">
            <i class="fas fa-info-circle mr-1"></i>
            Les sous-tâches contribuent au score à coefficient 0.5.
          </p>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import api from '@/api/axios'

const route = useRoute()
const userId = computed(() => route.params.id)

const sheet = ref(null)
const loading = ref(false)
const error = ref(null)

// Période — défaut: 30 derniers jours (même défaut que le backend).
const filters = ref({
  start: defaultStart(),
  end: defaultEnd(),
  statut: '',
})

function defaultStart() {
  const d = new Date()
  d.setDate(d.getDate() - 30)
  return d.toISOString().slice(0, 10)
}
function defaultEnd() {
  return new Date().toISOString().slice(0, 10)
}

// Sections
const sections = [
  { id: 'directed_tasks', label: 'Tâches dirigées', icon: 'fa-user-tie' },
  { id: 'directed_subtasks', label: 'Sous-tâches dirigées', icon: 'fa-list-check' },
  { id: 'assignee_tasks', label: 'Tâches assignées', icon: 'fa-tasks' },
  { id: 'assignee_subtasks', label: 'Sous-tâches assignées', icon: 'fa-clipboard-list' },
]
const activeSection = ref('directed_tasks')
const sectionItems = ref([])
const sectionMeta = ref({ current_page: 1, last_page: 1, total: 0 })
const sectionLoading = ref(false)

const drillItem = ref(null)

const initials = computed(() => {
  const nom = sheet.value?.user?.nom || ''
  const prenom = sheet.value?.user?.prenom || ''
  return ((prenom[0] || '') + (nom[0] || '')).toUpperCase() || '??'
})

const isSubtaskSection = computed(() =>
  activeSection.value === 'directed_subtasks' || activeSection.value === 'assignee_subtasks',
)

// Pourcentage de renvois justifiés pour le donut.
// Le backend renvoie unjustified_return_rate ∈ [0..1]; on calcule le
// complémentaire pour afficher la part "justifiés".
const donutJustifiedPercent = computed(() => {
  const r = sheet.value?.indicators?.unjustified_return_rate ?? 0
  return Math.max(0, Math.min(100, (1 - r) * 100))
})

// Libellés FR pour les 8 critères — alignés avec lang/fr/evaluation.php
// (les clés exhaustives arrivent à l'étape 8 mais on prévoit ici).
function criterionLabel(key) {
  return {
    completion_rate: 'Taux de complétion',
    deadline_respect: 'Respect des échéances',
    result_quality: 'Qualité des résultats',
    first_pass_validation: 'Validation premier passage',
    justified_returns: 'Renvois justifiés',
    inactions: 'Inactions (timeouts)',
    work_volume: 'Volume de travail',
    team_coordination: 'Coordination d\'équipe',
  }[key] || key
}

function scoreColor(score) {
  if (score >= 0.75) return 'text-green-600 dark:text-green-400'
  if (score >= 0.5) return 'text-amber-600 dark:text-amber-400'
  return 'text-red-600 dark:text-red-400'
}
function scoreBg(score) {
  if (score >= 0.75) return 'bg-green-500'
  if (score >= 0.5) return 'bg-amber-500'
  return 'bg-red-500'
}
function statutBadge(statut) {
  const map = {
    a_faire: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    en_cours: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    termine: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
  }
  return map[statut] || 'bg-gray-100 text-gray-700'
}

async function loadSheet() {
  loading.value = true
  error.value = null
  try {
    const params = { start: filters.value.start, end: filters.value.end }
    const { data } = await api.get(`/evaluations/personnel/${userId.value}/score`, { params })
    sheet.value = data.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur lors du chargement de la fiche.'
  } finally {
    loading.value = false
  }
}

async function loadSection(page = 1) {
  sectionLoading.value = true
  try {
    const params = {
      section: activeSection.value,
      start: filters.value.start,
      end: filters.value.end,
      page,
      per_page: 20,
    }
    if (filters.value.statut) params.statut = filters.value.statut
    const { data } = await api.get(`/evaluations/personnel/${userId.value}/historique`, { params })
    sectionItems.value = data.data
    sectionMeta.value = data.meta
  } catch (e) {
    sectionItems.value = []
    sectionMeta.value = { current_page: 1, last_page: 1, total: 0 }
    error.value = e.response?.data?.message || error.value
  } finally {
    sectionLoading.value = false
  }
}

function switchSection(id) {
  activeSection.value = id
  loadSection(1)
}

function applyFilters() {
  loadSheet()
  loadSection(1)
}
function resetFilters() {
  filters.value = { start: defaultStart(), end: defaultEnd(), statut: '' }
  applyFilters()
}

function openDrillDown(item) {
  drillItem.value = item
}

function exportSheet() {
  // Stub — l'export PDF/Excel arrive plus tard (Task 9 step suivant ou Task 10).
  // Sécurise au moins le bouton: pas de crash si cliqué.
  window.alert('Export à venir — endpoint dédié à brancher.')
}

onMounted(() => {
  loadSheet()
  loadSection(1)
})

watch(userId, () => {
  loadSheet()
  loadSection(1)
})
</script>
