<!-- Onglet Journal de validation : N0/N1/bypass decisions, filtre, tableau paginé + panneau de détail. -->
<template>
  <div class="space-y-4 p-4">

    <!-- Filtres -->
    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">

      <!-- Filtre acteur (autocomplete) -->
      <div class="relative min-w-48">
        <UserIcon class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
        <input
          v-model="actorSearch"
          type="text"
          :placeholder="$t('admin_logs.col_actor')"
          autocomplete="off"
          class="w-full rounded-3 border border-gray-300 bg-white py-2 pl-9 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          @input="onActorInput"
          @blur="hideSuggestionsDelayed"
        />
        <ul
          v-if="actorSuggestions.length && showSuggestions"
          class="absolute left-0 right-0 top-full z-20 mt-1 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
        >
          <li
            v-for="user in actorSuggestions"
            :key="user.id"
            @mousedown.prevent="selectActor(user)"
            class="cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            {{ user.nom }} <span class="text-gray-400 text-xs">{{ user.email }}</span>
          </li>
        </ul>
      </div>

      <!-- Filtre action -->
      <select
        v-model="filters.action"
        class="rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
      >
        <option value="">{{ $t('admin_logs.col_action') }} — tous</option>
        <option v-for="a in knownActions" :key="a.value" :value="a.value">{{ a.label }}</option>
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

      <div v-if="loading" class="space-y-2 p-4">
        <div v-for="i in 8" :key="i" class="h-10 animate-pulse rounded-3 bg-gray-200 dark:bg-gray-800" />
      </div>

      <p v-else-if="entries.length === 0" class="p-10 text-center text-sm text-gray-500 dark:text-gray-400">
        {{ $t('admin_logs.no_logs') }}
      </p>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50">
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('admin_logs.col_date') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('admin_logs.col_actor') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('admin_logs.col_action') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('admin_logs.col_task') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Résultat</th>
            </tr>
          </thead>
          <tbody ref="tableBodyRef" class="divide-y divide-gray-100 dark:divide-gray-800">
            <tr
              v-for="entry in entries"
              :key="entry.id"
              dusk="audit-row"
              class="stagger-item cursor-pointer transition-colors hover:bg-blue-50/60 dark:hover:bg-blue-900/10"
              @click="openDrawer(entry)"
            >
              <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                {{ formatDate(entry.created_at) }}
              </td>
              <td class="px-4 py-3">
                <span v-if="entry.actor" class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ entry.actor.nom }}
                </span>
                <span v-else class="text-xs text-gray-400">—</span>
              </td>
              <td class="px-4 py-3">
                <span :class="actionBadgeClass(entry.action)"
                  class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold">
                  {{ actionLabel(entry.action) }}
                </span>
              </td>
              <td class="max-w-xs truncate px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                {{ entry.resultat?.tache_titre ?? '—' }}
              </td>
              <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                #{{ entry.resultat?.id ?? '—' }}
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
    <LogDetailDrawer v-model="drawerOpen" :entry="selectedEntry" type="audit" />
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue'
import { CalendarIcon, UserIcon } from '@heroicons/vue/24/outline'
import { useStagger } from '@/composables/useAnimations'
import { useI18n } from 'vue-i18n'
import api from '@/api/axios'
import DatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import LogDetailDrawer from './LogDetailDrawer.vue'

const { t } = useI18n()
const { staggerRef: tableBodyRef, applyStagger } = useStagger(30)

const isDark = computed(() => document.documentElement.classList.contains('dark'))

// ── État ──────────────────────────────────────────────────────────────────────

const entries = ref([])
const loading = ref(false)
const meta = ref({ current_page: 1, last_page: 1, total: 0 })

const filters = ref({ actor_id: '', action: '', date_from: '', date_to: '' })

// ── Actions connues ───────────────────────────────────────────────────────────

const knownActions = [
  { value: 'approuve', label: t('admin_logs.audit_action_approuve') },
  { value: 'renvoye', label: t('admin_logs.audit_action_renvoye') },
  { value: 'timeout', label: t('admin_logs.audit_action_timeout') },
  { value: 'bypass', label: t('admin_logs.audit_action_bypass') },
  { value: 'n1_valide', label: t('admin_logs.audit_action_n1_valide') },
  { value: 'n1_rejete', label: t('admin_logs.audit_action_n1_rejete') },
  { value: 'n2_valide', label: t('admin_logs.audit_action_n2_valide') },
  { value: 'n2_rejete', label: t('admin_logs.audit_action_n2_rejete') },
]

// ── Autocomplete acteur ───────────────────────────────────────────────────────

const actorSearch = ref('')
const actorSuggestions = ref([])
const showSuggestions = ref(false)
let actorDebounce = null

const onActorInput = () => {
  clearTimeout(actorDebounce)
  filters.value.actor_id = ''
  if (!actorSearch.value.trim()) {
    actorSuggestions.value = []
    showSuggestions.value = false

    return
  }
  actorDebounce = setTimeout(async () => {
    try {
      const { data } = await api.get('/admin/users', { params: { search: actorSearch.value, per_page: 8 } })
      actorSuggestions.value = data.data ?? []
      showSuggestions.value = actorSuggestions.value.length > 0
    } catch {
      actorSuggestions.value = []
    }
  }, 300)
}

const selectActor = (user) => {
  actorSearch.value = `${user.nom} (${user.email})`
  filters.value.actor_id = user.id
  actorSuggestions.value = []
  showSuggestions.value = false
}

const hideSuggestionsDelayed = () => setTimeout(() => (showSuggestions.value = false), 150)

// ── Chargement ────────────────────────────────────────────────────────────────

const loadEntries = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      per_page: 25,
      page,
      ...Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v !== '')),
    }
    const res = await api.get('/admin/validation-audit-log', { params })
    entries.value = res.data.data ?? []
    meta.value = res.data.meta ?? meta.value
    await nextTick()
    applyStagger()
  } catch {
    /* ignore */
  } finally {
    loading.value = false
  }
}

const changePage = (page) => loadEntries(page)

const resetFilters = () => {
  actorSearch.value = ''
  actorSuggestions.value = []
  filters.value = { actor_id: '', action: '', date_from: '', date_to: '' }
}

watch(filters, () => loadEntries(1), { deep: true })

onMounted(() => loadEntries())

// ── Panneau de détail ─────────────────────────────────────────────────────────

const drawerOpen = ref(false)
const selectedEntry = ref(null)

const openDrawer = (entry) => {
  selectedEntry.value = entry
  drawerOpen.value = true
}

// ── Helpers d'affichage ───────────────────────────────────────────────────────

const actionLabel = (action) => {
  const key = `admin_logs.audit_action_${action}`
  return t(key, action)
}

const actionBadgeClass = (action) => {
  if (['approuve', 'n1_valide', 'n2_valide'].includes(action)) {
    return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
  }
  if (['renvoye', 'n1_rejete', 'n2_rejete'].includes(action)) {
    return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
  }
  if (action === 'bypass') {
    return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400'
  }
  if (action === 'timeout') {
    return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
  }

  return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
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

// Affichage lisible de la date sélectionnée dans le trigger du date picker
const formatDisplay = (dateStr) => {
  if (!dateStr) return ''
  const [y, m, d] = dateStr.split('-')
  return `${d}/${m}/${y}`
}
</script>
