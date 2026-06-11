<!-- Onglet Journal d'activité : filtre + tableau paginé + panneau de détail. -->
<template>
  <div class="space-y-4 p-4">

    <!-- Filtres -->
    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">

      <!-- Recherche texte -->
      <div class="relative flex-1 min-w-48">
        <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
        <input
          v-model="filters.search"
          type="text"
          :placeholder="$t('admin_logs.search_placeholder')"
          class="w-full rounded-3 border border-gray-300 bg-white py-2 pl-9 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
        />
      </div>

      <!-- Filtre auteur (autocomplete) — masqué quand fixedCauserId est défini -->
      <div v-if="!fixedCauserId" class="relative min-w-48">
        <UserIcon class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
        <input
          v-model="causerSearch"
          type="text"
          :placeholder="$t('admin_logs.filter_causer')"
          autocomplete="off"
          class="w-full rounded-3 border border-gray-300 bg-white py-2 pl-9 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          @input="onCauserInput"
          @blur="hideSuggestionsDelayed"
        />
        <!-- Suggestions -->
        <ul
          v-if="causerSuggestions.length && showSuggestions"
          class="absolute left-0 right-0 top-full z-20 mt-1 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
        >
          <li
            v-for="user in causerSuggestions"
            :key="user.id"
            @mousedown.prevent="selectCauser(user)"
            class="cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            {{ user.nom }} <span class="text-gray-400 text-xs">{{ user.email }}</span>
          </li>
        </ul>
      </div>
      <!-- Indicateur auteur verrouillé (quand fixedCauserId est défini) -->
      <div v-else class="flex items-center gap-2 rounded-3 border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-700 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
        <UserIcon class="h-4 w-4 shrink-0" />
        <span>{{ fixedCauserLabel || $t('admin_logs.filter_causer_locked') }}</span>
      </div>

      <!-- Filtre événement -->
      <select
        v-model="filters.event"
        class="rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
      >
        <option value="">{{ $t('admin_logs.all_events') }}</option>
        <option v-for="ev in knownEvents" :key="ev" :value="ev">{{ ev }}</option>
      </select>

      <!-- Filtre sujet -->
      <select
        v-model="filters.subject_type"
        class="rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
      >
        <option value="">{{ $t('admin_logs.all_subjects') }}</option>
        <option v-for="st in knownSubjects" :key="st.value" :value="st.value">{{ st.label }}</option>
      </select>

      <!-- Plage de dates — groupés sur la même ligne, style identique aux autres filtres -->
      <div class="flex gap-2">
        <DatePicker
          v-model="filters.date_from"
          :enable-time-picker="false"
          auto-apply
          :model-type="'yyyy-MM-dd'"
          :locale="'fr'"
          :dark="isDark"
        >
          <template #trigger>
            <div class="relative cursor-pointer">
              <CalendarIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
              <input
                :value="formatDisplay(filters.date_from)"
                readonly
                placeholder="Date début"
                class="w-36 cursor-pointer rounded-3 border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
              />
            </div>
          </template>
        </DatePicker>

        <DatePicker
          v-model="filters.date_to"
          :enable-time-picker="false"
          auto-apply
          :model-type="'yyyy-MM-dd'"
          :locale="'fr'"
          :dark="isDark"
          :min-date="filters.date_from || undefined"
        >
          <template #trigger>
            <div class="relative cursor-pointer">
              <CalendarIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
              <input
                :value="formatDisplay(filters.date_to)"
                readonly
                placeholder="Date fin"
                class="w-36 cursor-pointer rounded-3 border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
              />
            </div>
          </template>
        </DatePicker>
      </div>

      <button
        @click="resetFilters"
        class="rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-600 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-transparent dark:text-gray-400 dark:hover:bg-gray-800"
      >
        {{ $t('admin_logs.reset') }}
      </button>
    </div>

    <!-- Tableau -->
    <div class="overflow-hidden rounded-3 border border-gray-200 dark:border-gray-700">

      <!-- Chargement -->
      <div v-if="loading" class="space-y-2 p-4">
        <div v-for="i in 8" :key="i" class="h-10 animate-pulse rounded-3 bg-gray-200 dark:bg-gray-800" />
      </div>

      <!-- Vide -->
      <p v-else-if="activities.length === 0" class="p-10 text-center text-sm text-gray-500 dark:text-gray-400">
        {{ $t('admin_logs.empty') }}
      </p>

      <div v-else>
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50">
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('admin_logs.col_date') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('admin_logs.col_causer') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('admin_logs.col_event') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('admin_logs.col_subject') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('admin_logs.col_description') }}</th>
            </tr>
          </thead>
          <tbody ref="tableBodyRef" class="divide-y divide-gray-100 dark:divide-gray-800">
            <tr
              v-for="entry in activities"
              :key="entry.id"
              dusk="activity-row"
              class="stagger-item cursor-pointer transition-colors hover:bg-blue-50/60 dark:hover:bg-blue-900/10"
              @click="openDrawer(entry)"
            >
              <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                {{ formatDate(entry.created_at) }}
              </td>
              <td class="px-4 py-3">
                <span v-if="entry.causer" class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ entry.causer.name || entry.causer.nom || entry.causer.email || `#${entry.causer_id}` }}
                </span>
                <span v-else class="text-xs text-gray-400">—</span>
              </td>
              <td class="px-4 py-3">
                <span :class="eventBadgeClass(entry.event || entry.description)"
                  class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium">
                  {{ entry.event || entry.description }}
                </span>
              </td>
              <td class="max-w-36 truncate px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                {{ subjectLabel(entry.subject_type) }}
                <span v-if="entry.subject_id" class="text-gray-400"> #{{ entry.subject_id }}</span>
              </td>
              <td class="max-w-xs truncate px-4 py-3 text-sm text-gray-700 dark:text-gray-300"
                :title="entry.human_readable || entry.description">
                {{ entry.human_readable || entry.description }}
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="flex items-center justify-between border-t border-gray-200 px-4 py-3 dark:border-gray-700">
          <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ $t('admin_logs.total', { total: meta.total }) }}
          </p>
          <div class="flex gap-2">
            <button
              :disabled="meta.current_page <= 1"
              @click="changePage(meta.current_page - 1)"
              class="rounded-3 border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-600 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-700 dark:bg-transparent dark:text-gray-400"
            >
              {{ $t('common.previous') }}
            </button>
            <span class="flex items-center px-2 text-xs text-gray-500 dark:text-gray-400">
              {{ meta.current_page }} / {{ meta.last_page }}
            </span>
            <button
              :disabled="meta.current_page >= meta.last_page"
              @click="changePage(meta.current_page + 1)"
              class="rounded-3 border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-600 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-700 dark:bg-transparent dark:text-gray-400"
            >
              {{ $t('common.next') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Panneau de détail -->
    <LogDetailDrawer v-model="drawerOpen" :entry="selectedEntry" type="activity" dusk="activity-drawer" />
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue'
import { CalendarIcon, MagnifyingGlassIcon, UserIcon } from '@heroicons/vue/24/outline'
import { useStagger } from '@/composables/useAnimations'
import api from '@/api/axios'
import DatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import LogDetailDrawer from './LogDetailDrawer.vue'

const props = defineProps({
  /** Quand défini, verrouille le filtre auteur sur cet ID et masque l'autocomplete. */
  fixedCauserId: {
    type: [Number, String],
    default: null,
  },
  /** Libellé affiché à côté de l'icône quand l'auteur est verrouillé. */
  fixedCauserLabel: {
    type: String,
    default: '',
  },
})

const { staggerRef: tableBodyRef, applyStagger } = useStagger(30)

const isDark = computed(() => document.documentElement.classList.contains('dark'))

// Affichage lisible de la date sélectionnée dans le trigger du date picker
const formatDisplay = (dateStr) => {
  if (!dateStr) { return '' }
  const [y, m, d] = dateStr.split('-')
  return `${d}/${m}/${y}`
}

// ── État ──────────────────────────────────────────────────────────────────────

const activities = ref([])
const loading = ref(false)
const meta = ref({ current_page: 1, last_page: 1, per_page: 25, total: 0 })

const filters = ref({
  search: '',
  event: '',
  subject_type: '',
  date_from: '',
  date_to: '',
  causer_id: props.fixedCauserId ? String(props.fixedCauserId) : '',
})

// ── Autocomplete auteur ────────────────────────────────────────────────────────

const causerSearch = ref('')
const causerSuggestions = ref([])
const showSuggestions = ref(false)
let causerDebounce = null

const onCauserInput = () => {
  clearTimeout(causerDebounce)
  filters.value.causer_id = ''
  if (!causerSearch.value.trim()) {
    causerSuggestions.value = []
    showSuggestions.value = false

    return
  }
  causerDebounce = setTimeout(async () => {
    try {
      const { data } = await api.get('/admin/users', { params: { search: causerSearch.value, per_page: 8 } })
      causerSuggestions.value = data.data ?? []
      showSuggestions.value = causerSuggestions.value.length > 0
    } catch {
      causerSuggestions.value = []
    }
  }, 300)
}

const selectCauser = (user) => {
  causerSearch.value = `${user.nom} (${user.email})`
  filters.value.causer_id = user.id
  causerSuggestions.value = []
  showSuggestions.value = false
}

const hideSuggestionsDelayed = () => {
  setTimeout(() => (showSuggestions.value = false), 150)
}

// ── Données statiques de filtres ──────────────────────────────────────────────

const knownEvents = ['created', 'updated', 'deleted', 'restored', 'logged in', 'logged out']

const knownSubjects = [
  { value: 'Workspace', label: 'Workspace' },
  { value: 'Projet', label: 'Projet' },
  { value: 'Activite', label: 'Activité' },
  { value: 'Tache', label: 'Tâche' },
  { value: 'SousTache', label: 'Sous-tâche' },
  { value: 'User', label: 'Utilisateur' },
  { value: 'Document', label: 'Document' },
  { value: 'TacheResultat', label: 'Résultat' },
]

// ── Chargement ────────────────────────────────────────────────────────────────

const loadActivities = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      per_page: 25,
      page,
      ...Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v !== '')),
    }
    const res = await api.get('/admin/activity-log', { params })
    activities.value = res.data.data ?? []
    meta.value = res.data.meta ?? meta.value
    await nextTick()
    applyStagger()
  } catch {
    /* ignore */
  } finally {
    loading.value = false
  }
}

const changePage = (page) => loadActivities(page)

const resetFilters = () => {
  causerSearch.value = ''
  causerSuggestions.value = []
  filters.value = {
    search: '',
    event: '',
    subject_type: '',
    date_from: '',
    date_to: '',
    causer_id: props.fixedCauserId ? String(props.fixedCauserId) : '',
  }
}

let filterDebounce = null
watch(
  filters,
  () => {
    clearTimeout(filterDebounce)
    filterDebounce = setTimeout(() => loadActivities(1), 350)
  },
  { deep: true }
)

onMounted(() => loadActivities())

// ── Panneau de détail ─────────────────────────────────────────────────────────

const drawerOpen = ref(false)
const selectedEntry = ref(null)

const openDrawer = (entry) => {
  selectedEntry.value = entry
  drawerOpen.value = true
}

// ── Helpers d'affichage ───────────────────────────────────────────────────────

const eventBadgeClass = (event) => {
  if (!event) return 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
  const e = event.toLowerCase()
  if (e.includes('created') || e.includes('registered')) return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
  if (e.includes('updated') || e.includes('modified')) return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
  if (e.includes('deleted') || e.includes('removed') || e.includes('logout')) return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
  if (e.includes('login') || e.includes('connected')) return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400'

  return 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
}

const subjectLabel = (type) => {
  if (!type) return '—'
  return type.replace(/^App\\Models\\/, '').replace(/\\/g, '')
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString(undefined, {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>
