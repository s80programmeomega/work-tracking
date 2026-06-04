<!-- Page de recherche globale — résultats paginés, lecture seule, export sélectif/global. -->
<template>
  <AdminLayout>
    <div class="space-y-5">

      <!-- En-tête + barre de recherche -->
      <div class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/3">
        <h1 class="mb-4 text-2xl font-bold text-gray-900 dark:text-white">{{ $t('search_page.title') }}</h1>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
          <!-- Champ de recherche principal -->
          <div class="relative flex-1">
            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
            <input
              v-model="queryInput"
              type="text"
              dusk="search-page-input"
              :placeholder="$t('search_page.placeholder')"
              class="w-full rounded-3 border border-gray-300 bg-white py-3 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
              @keydown.enter="doSearch"
            />
          </div>

          <!-- Sélecteur de workspace (super-admin uniquement) -->
          <select
            v-if="isSuperAdmin"
            v-model="selectedWorkspaceId"
            class="rounded-3 border border-gray-300 bg-white px-3 py-3 text-sm text-gray-900 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          >
            <option value="">{{ $t('search_page.all_workspaces') }}</option>
            <option v-for="ws in workspaces" :key="ws.id" :value="ws.id">{{ ws.nom }}</option>
          </select>

          <button
            @click="doSearch"
            class="rounded-3 bg-blue-600 px-5 py-3 text-sm font-medium text-white transition-colors hover:bg-blue-700 disabled:opacity-50"
            :disabled="queryInput.trim().length < 2"
          >
            {{ $t('search_page.search_btn') }}
          </button>
        </div>

        <!-- Filtres de type -->
        <div class="mt-3 flex flex-wrap gap-2">
          <label
            v-for="type in allTypes"
            :key="type.key"
            class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-colors"
            :class="activeTypes.includes(type.key)
              ? 'border-blue-500 bg-blue-50 text-blue-700 dark:border-blue-700 dark:bg-blue-900/20 dark:text-blue-400'
              : 'border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400'"
          >
            <input v-model="activeTypes" :value="type.key" type="checkbox" class="sr-only" />
            {{ type.emoji }} {{ $t(`search_page.type_${type.key}`, type.label) }}
          </label>
        </div>
      </div>

      <!-- Barre d'export (apparaît si résultats présents) -->
      <div
        v-if="totalResults > 0"
        class="flex items-center justify-between rounded-3 border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-white/3"
      >
        <p class="text-sm text-gray-600 dark:text-gray-400">
          <span v-if="selectedIds.length" class="font-medium text-gray-900 dark:text-white">
            {{ selectedIds.length }} {{ $t('search_page.selected') }}
          </span>
          <span v-else>{{ totalResults }} {{ $t('search_page.total_results') }}</span>
        </p>
        <div class="flex gap-2">
          <button
            v-if="selectedIds.length"
            @click="exportSelected"
            class="rounded-3 border border-blue-500 px-3 py-1.5 text-xs font-medium text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20"
          >
            {{ $t('search_page.export_selected') }}
          </button>
          <button
            @click="showExportCapDialog = true"
            class="rounded-3 bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700"
          >
            {{ $t('search_page.export_all', { n: totalResults }) }}
          </button>
        </div>
      </div>

      <!-- Onglets par type -->
      <div v-if="searched" class="rounded-3 border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/3">
        <nav class="flex overflow-x-auto border-b border-gray-200 px-4 dark:border-gray-800">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="activeTab = tab.key"
            :class="[
              'flex shrink-0 items-center gap-1.5 px-4 py-3.5 text-sm font-medium transition-all whitespace-nowrap',
              activeTab === tab.key
                ? 'border-b-2 border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
            ]"
          >
            {{ tab.emoji }} {{ $t(`search_page.type_${tab.key}`, tab.label) }}
            <span
              v-if="totals[tab.key]"
              class="rounded-full bg-gray-100 px-1.5 py-0.5 text-[10px] font-bold text-gray-600 dark:bg-gray-700 dark:text-gray-300"
            >{{ totals[tab.key] }}</span>
          </button>
        </nav>

        <!-- État de chargement -->
        <div v-if="loading" class="space-y-3 p-4">
          <div v-for="i in 6" :key="i" class="h-16 animate-pulse rounded-3 bg-gray-100 dark:bg-gray-800" />
        </div>

        <!-- Résultats de l'onglet actif -->
        <div v-else>
          <div v-if="currentResults.length === 0" class="py-16 text-center text-sm text-gray-500 dark:text-gray-400">
            {{ $t('search_page.no_results') }}
          </div>

          <div ref="resultListRef">
            <div
              v-for="result in currentResults"
              :key="`${result.type}-${result.id}`"
              class="stagger-item flex items-start gap-3 border-b border-gray-100 px-4 py-4 last:border-0 dark:border-gray-800"
              dusk="search-result-row"
            >
              <!-- Checkbox sélection -->
              <input
                type="checkbox"
                class="mt-1 h-4 w-4 shrink-0 cursor-pointer rounded border-gray-300"
                :value="`${result.type}:${result.id}`"
                v-model="selectedIds"
              />

              <!-- Contenu -->
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <!-- Type badge -->
                  <span :class="typeBadgeClass(result.type)" class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">
                    {{ typeLabel(result.type) }}
                  </span>
                  <!-- Workspace badge (super-admin) -->
                  <span
                    v-if="isSuperAdmin && result.meta?.workspace_name"
                    class="rounded-full bg-purple-100 px-2 py-0.5 text-[10px] font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-400"
                  >
                    {{ result.meta.workspace_name }}
                  </span>
                  <!-- Statut / priorité -->
                  <span v-if="result.meta?.statut" class="text-xs text-gray-400">{{ result.meta.statut }}</span>
                </div>

                <!-- Titre avec highlights -->
                <!-- eslint-disable-next-line vue/no-v-html -->
                <p
                  class="mt-1 font-medium text-gray-900 dark:text-white [&_mark]:rounded [&_mark]:bg-yellow-200 [&_mark]:px-0.5 dark:[&_mark]:bg-yellow-800"
                  v-html="sanitize(result.label)"
                />

                <!-- Extrait avec highlights -->
                <!-- eslint-disable-next-line vue/no-v-html -->
                <p
                  v-if="result.excerpt"
                  class="mt-0.5 truncate text-sm text-gray-500 dark:text-gray-400 [&_mark]:rounded [&_mark]:bg-yellow-200 [&_mark]:px-0.5 dark:[&_mark]:bg-yellow-800"
                  v-html="sanitize(result.excerpt)"
                />

                <!-- Meta chips -->
                <div class="mt-1.5 flex flex-wrap gap-2 text-xs text-gray-400">
                  <span v-if="result.meta?.projet_nom">📁 {{ result.meta.projet_nom }}</span>
                  <span v-if="result.meta?.user_nom">👤 {{ result.meta.user_nom }}</span>
                  <span v-if="result.meta?.responsable_nom">👤 {{ result.meta.responsable_nom }}</span>
                  <span v-if="result.meta?.fonction">{{ result.meta.fonction }}</span>
                </div>
              </div>

              <!-- Actions lecture seule uniquement -->
              <div class="flex shrink-0 items-center gap-1.5">
                <button
                  dusk="result-preview-btn"
                  @click="openPreview(result)"
                  class="rounded-3 border border-gray-200 px-3 py-1.5 text-xs text-gray-600 transition-colors hover:border-blue-400 hover:text-blue-600 dark:border-gray-700 dark:text-gray-400 dark:hover:text-blue-400"
                >
                  👁 {{ $t('search_page.preview') }}
                </button>
                <a
                  :href="result.url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="rounded-3 bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-blue-700"
                >
                  ↗ {{ $t('search_page.open_tab') }}
                </a>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="totals[activeTab] > perPage" class="flex items-center justify-between border-t border-gray-100 px-4 py-3 dark:border-gray-800">
            <p class="text-xs text-gray-500 dark:text-gray-400">Page {{ page }} / {{ lastPage }}</p>
            <div class="flex gap-2">
              <button :disabled="page <= 1" @click="changePage(page - 1)"
                class="rounded-3 border border-gray-300 px-3 py-1.5 text-xs text-gray-600 disabled:opacity-40 dark:border-gray-700 dark:text-gray-400">
                {{ $t('common.previous') }}
              </button>
              <button :disabled="page >= lastPage" @click="changePage(page + 1)"
                class="rounded-3 border border-gray-300 px-3 py-1.5 text-xs text-gray-600 disabled:opacity-40 dark:border-gray-700 dark:text-gray-400">
                {{ $t('common.next') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- État initial (pas encore de recherche) -->
      <div v-else class="py-24 text-center text-sm text-gray-400 dark:text-gray-500">
        {{ $t('search_page.hint') }}
      </div>
    </div>

    <!-- Modale de prévisualisation (lecture seule) -->
    <div v-if="previewResult" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="previewResult = null">
      <div class="w-full max-w-2xl overflow-hidden rounded-3 bg-white shadow-2xl dark:bg-gray-900">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <span :class="typeBadgeClass(previewResult.type)" class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase">
              {{ typeLabel(previewResult.type) }}
            </span>
            <span class="font-semibold text-gray-900 dark:text-white">{{ stripTags(previewResult.label) }}</span>
          </div>
          <button @click="previewResult = null" class="text-gray-400 transition-colors hover:text-gray-600 dark:hover:text-gray-300">✕</button>
        </div>
        <div class="max-h-[60vh] overflow-y-auto p-6 space-y-4">
          <!-- Extrait -->
          <div v-if="previewResult.excerpt">
            <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $t('search_page.excerpt') }}</p>
            <!-- eslint-disable-next-line vue/no-v-html -->
            <p class="text-sm text-gray-700 dark:text-gray-300 [&_mark]:bg-yellow-200 [&_mark]:px-0.5" v-html="sanitize(previewResult.excerpt)" />
          </div>
          <!-- Métadonnées -->
          <div class="grid grid-cols-2 gap-3 text-sm">
            <template v-for="(val, key) in previewResult.meta" :key="key">
              <div v-if="val">
                <p class="text-xs text-gray-400">{{ metaLabel(key) }}</p>
                <p class="font-medium text-gray-700 dark:text-gray-300">{{ val }}</p>
              </div>
            </template>
          </div>
        </div>
        <div class="flex justify-end gap-2 border-t border-gray-100 px-6 py-3 dark:border-gray-800">
          <a :href="previewResult.url" target="_blank" rel="noopener"
            class="rounded-3 bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            ↗ {{ $t('search_page.open_tab') }}
          </a>
        </div>
      </div>
    </div>

    <!-- Modale de choix du plafond d'export -->
    <div v-if="showExportCapDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showExportCapDialog = false">
      <div class="mx-4 w-full max-w-sm rounded-3 bg-white p-6 shadow-xl dark:bg-gray-800">
        <h3 class="mb-3 font-semibold text-gray-900 dark:text-white">{{ $t('search_page.export_cap_title') }}</h3>
        <div class="space-y-2">
          <label v-for="cap in exportCaps" :key="cap.value" class="flex cursor-pointer items-center gap-3 rounded-3 border border-gray-200 p-3 transition-colors hover:border-blue-400 dark:border-gray-700">
            <input v-model="selectedCap" :value="cap.value" type="radio" class="h-4 w-4" />
            <div>
              <p class="text-sm font-medium text-gray-900 dark:text-white">{{ cap.label }}</p>
              <p v-if="cap.note" class="text-xs text-gray-400">{{ cap.note }}</p>
            </div>
          </label>
        </div>
        <div class="mt-4 flex justify-end gap-2">
          <button @click="showExportCapDialog = false" class="rounded-3 border border-gray-300 px-4 py-2 text-sm text-gray-600 dark:border-gray-600 dark:text-gray-400">
            {{ $t('common.cancel') }}
          </button>
          <button @click="exportAll" class="rounded-3 bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            {{ $t('search_page.export_btn') }}
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import { useStagger } from '@/composables/useAnimations'
import { useAuthStore } from '@/stores/authStore'
import { useWorkspace } from '@/composables/useWorkspace'
import api from '@/api/axios'
import AdminLayout from '@/components/layout/AdminLayout.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { workspaces, fetchWorkspaces } = useWorkspace()
const { staggerRef: resultListRef, applyStagger } = useStagger(40)

const isSuperAdmin = computed(() => authStore.isSuperAdmin)

// ── État de la recherche ──────────────────────────────────────────────────────

const queryInput = ref(route.query.q ?? '')
const selectedWorkspaceId = ref('')
const activeTypes = ref(['projets', 'activites', 'taches', 'documents', 'users', 'messages'])
const activeTab = ref('projets')
const page = ref(1)
const perPage = 10
const loading = ref(false)
const searched = ref(false)
const results = ref({})
const totals = ref({})

const allTypes = [
  { key: 'projets', label: 'Projets', emoji: '📁' },
  { key: 'activites', label: 'Activités', emoji: '🗂️' },
  { key: 'taches', label: 'Tâches', emoji: '✅' },
  { key: 'documents', label: 'Documents', emoji: '📄' },
  { key: 'users', label: 'Membres', emoji: '👤' },
  { key: 'messages', label: 'Messages', emoji: '💬' },
]

const tabs = computed(() => allTypes.filter((t) => activeTypes.value.includes(t.key)))

const currentResults = computed(() => results.value[activeTab.value] ?? [])

const totalResults = computed(() => Object.values(totals.value).reduce((s, n) => s + n, 0))

const lastPage = computed(() => Math.ceil((totals.value[activeTab.value] ?? 0) / perPage))

// ── Sélection et export ───────────────────────────────────────────────────────

const selectedIds = ref([])
const showExportCapDialog = ref(false)
const selectedCap = ref('500')
const previewResult = ref(null)

const exportCaps = computed(() => [
  { value: '500', label: t('search_page.cap_500') },
  { value: '1000', label: t('search_page.cap_1000') },
  { value: '2000', label: t('search_page.cap_2000') },
  { value: 'all', label: t('search_page.cap_all'), note: t('search_page.cap_all_note') },
])

// ── Recherche ─────────────────────────────────────────────────────────────────

const doSearch = async () => {
  const q = queryInput.value.trim()
  if (q.length < 2) return

  // Mettre à jour l'URL pour partageabilité
  router.replace({ query: { q } })

  await runSearch(q, 1)
}

const runSearch = async (q, p) => {
  loading.value = true
  page.value = p

  try {
    const params = {
      q,
      page: p,
      per_page: perPage,
      types: activeTypes.value,
    }

    if (isSuperAdmin.value && selectedWorkspaceId.value) {
      params.workspace_id = selectedWorkspaceId.value
    } else if (!isSuperAdmin.value) {
      params.workspace_id = authStore.currentWorkspace?.id
    }

    const { data } = await api.get('/search', { params })
    results.value = data.results ?? {}
    totals.value = data.totals ?? {}
    searched.value = true

    // Basculer sur le premier onglet ayant des résultats
    const firstWithResults = tabs.value.find((tab) => (totals.value[tab.key] ?? 0) > 0)
    if (firstWithResults) activeTab.value = firstWithResults.key

    await nextTick()
    applyStagger()
  } catch {
    /* ignore */
  } finally {
    loading.value = false
  }
}

const changePage = (p) => runSearch(queryInput.value.trim(), p)

watch(activeTab, async () => {
  await nextTick()
  applyStagger()
})

// Lancer la recherche si q est dans l'URL au chargement
onMounted(async () => {
  if (isSuperAdmin.value) await fetchWorkspaces()
  if (queryInput.value.trim().length >= 2) await doSearch()
})

// ── Export ────────────────────────────────────────────────────────────────────

const exportAll = async () => {
  showExportCapDialog.value = false
  const q = queryInput.value.trim()
  if (!q) return

  if (selectedCap.value === 'all') {
    // Export asynchrone — email envoyé
    await api.get('/search/export', {
      params: { q, cap: 'all', types: activeTypes.value },
    })
    alert(t('search_page.export_all_queued'))
    return
  }

  // Téléchargement direct
  window.location.href = `/api/search/export?q=${encodeURIComponent(q)}&cap=${selectedCap.value}&types[]=${activeTypes.value.join('&types[]=')}`
}

const exportSelected = async () => {
  if (!selectedIds.value.length) return

  // Grouper les IDs sélectionnés par type
  const byType = {}
  for (const entry of selectedIds.value) {
    const [type, id] = entry.split(':')
    if (!byType[type]) byType[type] = []
    byType[type].push(parseInt(id))
  }

  // Premier type avec IDs (l'export sélectif est mono-type dans cette version)
  const type = Object.keys(byType)[0]
  const ids = byType[type]

  window.location.href = `/api/search/export-selected?type=${type}&ids=${ids.join(',')}&q=${encodeURIComponent(queryInput.value)}`
}

// ── Prévisualisation ──────────────────────────────────────────────────────────

const openPreview = (result) => {
  previewResult.value = result
}

// ── Helpers d'affichage ───────────────────────────────────────────────────────

const sanitize = (html) => {
  if (!html) return ''
  // Conserver uniquement les balises <mark> — retirer tout le reste
  return html.replace(/<(?!\/?mark\b)[^>]+>/gi, '')
}

const stripTags = (html) => sanitize(html).replace(/<\/?mark>/gi, '')

const typeBadgeClass = (type) => {
  const map = {
    projet: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    activite: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
    tache: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    document: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    user: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    message: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
  }
  return map[type] ?? 'bg-gray-100 text-gray-600'
}

const typeLabel = (type) => {
  const map = {
    projet: 'Projet',
    activite: 'Activité',
    tache: 'Tâche',
    document: 'Document',
    user: 'Membre',
    message: 'Message',
  }
  return map[type] ?? type
}

const metaLabel = (key) => {
  const map = {
    statut: 'Statut',
    priorite: 'Priorité',
    workspace_name: 'Workspace',
    projet_nom: 'Projet',
    activite_nom: 'Activité',
    responsable_nom: 'Responsable',
    assignees_noms: 'Assignés',
    user_nom: 'Auteur',
    fonction: 'Fonction',
    team_uuid: 'Équipe UUID',
    mime_type: 'Type de fichier',
    uploader_nom: 'Uploadé par',
  }
  return map[key] ?? key
}
</script>
