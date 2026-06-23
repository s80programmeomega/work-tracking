<!-- Onglet Logs applicatifs : visualiseur natif appelant l'API du package log-viewer. -->
<template>
  <div class="flex flex-col gap-4 p-4">

    <!-- Sélecteur de fichier + actions -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
        <!-- Sélecteur de fichier -->
        <div class="relative flex-1 max-w-xs">
          <DocumentTextIcon class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
          <select
            v-model="selectedFileId"
            @change="onFileChange"
            class="w-full rounded-3 border border-gray-300 bg-white py-2 pl-9 pr-8 text-sm text-gray-900 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          >
            <option value="">{{ $t('admin_logs.file_picker_label') }}</option>
            <option v-for="f in files" :key="f.identifier" :value="f.identifier">
              {{ f.name }} ({{ f.size_formatted }})
            </option>
          </select>
        </div>

        <!-- Recherche -->
        <div class="relative flex-1 min-w-48">
          <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="$t('admin_logs.search_logs')"
            class="w-full rounded-3 border border-gray-300 bg-white py-2 pl-9 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          />
        </div>
      </div>

      <!-- Actions fichier -->
      <div v-if="selectedFileId" class="flex items-center gap-2">
        <button
          v-if="selectedFile?.can_download"
          @click="download"
          :disabled="downloading"
          class="inline-flex items-center gap-1.5 rounded-3 border border-gray-300 bg-white px-3 py-2 text-xs text-gray-600 transition-colors hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-transparent dark:text-gray-400"
        >
          <ArrowDownTrayIcon class="h-3.5 w-3.5" />
          {{ $t('admin_logs.download') }}
        </button>
        <button
          v-if="selectedFile?.can_delete"
          @click="confirmDelete"
          class="inline-flex items-center gap-1.5 rounded-3 border border-red-200 bg-white px-3 py-2 text-xs text-red-600 transition-colors hover:bg-red-50 dark:border-red-800 dark:bg-transparent dark:text-red-400"
        >
          <TrashIcon class="h-3.5 w-3.5" />
          {{ $t('admin_logs.delete_file') }}
        </button>
      </div>
    </div>

    <!-- Chips de niveau -->
    <div v-if="levelCounts.length" class="flex flex-wrap gap-2">
      <button
        v-for="level in levelCounts"
        :key="level.level"
        @click="toggleLevel(level.level)"
        :class="[
          'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-all',
          isLevelExcluded(level.level)
            ? 'border-gray-200 bg-gray-100 text-gray-400 line-through opacity-60 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500'
            : levelChipClass(level.level),
        ]"
      >
        {{ $t(`admin_logs.log_level_${level.level}`, level.level_name) }}
        <span class="rounded-full bg-white/30 px-1.5 py-0.5 text-[10px] font-bold">{{ level.count }}</span>
      </button>
    </div>

    <!-- Indicateur de scan partiel -->
    <p v-if="percentScanned < 100 && percentScanned > 0" class="text-xs text-amber-600 dark:text-amber-400">
      {{ $t('admin_logs.scanned', { pct: Math.round(percentScanned) }) }}
    </p>

    <!-- Tableau des entrées -->
    <div class="overflow-hidden rounded-3 border border-gray-200 dark:border-gray-700">

      <!-- Chargement -->
      <div v-if="loading" class="space-y-2 p-4">
        <div v-for="i in 10" :key="i" class="h-10 animate-pulse rounded-3 bg-gray-200 dark:bg-gray-800" />
      </div>

      <!-- Aucun fichier sélectionné -->
      <p v-else-if="!selectedFileId" class="p-10 text-center text-sm text-gray-500 dark:text-gray-400">
        {{ $t('admin_logs.file_picker_label') }} …
      </p>

      <!-- Vide -->
      <p v-else-if="logs.length === 0" class="p-10 text-center text-sm text-gray-500 dark:text-gray-400">
        {{ $t('admin_logs.no_logs') }}
      </p>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50">
              <th class="w-28 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Niveau</th>
              <th class="w-40 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('admin_logs.col_date') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Message</th>
              <th class="w-8 px-4 py-3" />
            </tr>
          </thead>
          <tbody ref="logBodyRef" class="divide-y divide-gray-100 dark:divide-gray-800">
            <template v-for="log in logs" :key="log.index">
              <tr
                class="stagger-item cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40"
                :dusk="`log-row-${log.index}`"
                @click="toggleExpand(log.index)"
              >
                <td class="px-4 py-3">
                  <span :class="levelBadgeClass(log.level)" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold">
                    {{ log.level_name }}
                  </span>
                  <!-- Badge mail si contexte contient des clés mail -->
                  <span
                    v-if="isMailLog(log)"
                    class="ml-1 inline-flex items-center rounded-full bg-indigo-100 px-1.5 py-0.5 text-[10px] font-medium text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400"
                  >
                    {{ $t('admin_logs.mail_preview') }}
                  </span>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                  {{ log.datetime ?? log.time ?? '—' }}
                </td>
                <td class="max-w-xl truncate px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                  {{ log.message }}
                </td>
                <td class="px-4 py-3 text-right">
                  <ChevronDownIcon
                    :class="['h-4 w-4 text-gray-400 transition-transform', expandedIndex === log.index ? 'rotate-180' : '']"
                  />
                </td>
              </tr>
              <!-- Ligne étendue : stack trace -->
              <tr v-if="expandedIndex === log.index" :key="`${log.index}-expand`">
                <td colspan="4" class="bg-gray-50 px-4 py-3 dark:bg-gray-800/60">
                  <pre class="max-h-80 overflow-auto whitespace-pre-wrap break-all rounded bg-gray-900 p-3 text-xs leading-relaxed text-green-300">{{ log.full_text || log.message }}</pre>
                </td>
              </tr>
            </template>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="flex items-center justify-between border-t border-gray-200 px-4 py-3 dark:border-gray-700">
          <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ $t('admin_logs.total', { total: pagination.total }) }}
          </p>
          <div class="flex gap-2">
            <button
              :disabled="pagination.current_page <= 1"
              @click="changePage(pagination.current_page - 1)"
              class="rounded-3 border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-600 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-700 dark:bg-transparent dark:text-gray-400"
            >
              {{ $t('common.previous') }}
            </button>
            <span class="flex items-center px-2 text-xs text-gray-500 dark:text-gray-400">
              {{ pagination.current_page }} / {{ pagination.last_page }}
            </span>
            <button
              :disabled="pagination.current_page >= pagination.last_page"
              @click="changePage(pagination.current_page + 1)"
              class="rounded-3 border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-600 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-700 dark:bg-transparent dark:text-gray-400"
            >
              {{ $t('common.next') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modale de confirmation de suppression -->
    <div v-if="deleteConfirmOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
      <div class="mx-4 w-full max-w-sm rounded-3 bg-white p-6 shadow-xl dark:bg-gray-800">
        <h3 class="mb-2 text-base font-semibold text-gray-900 dark:text-white">{{ $t('admin_logs.delete_file') }}</h3>
        <p class="mb-5 text-sm text-gray-600 dark:text-gray-400">{{ $t('admin_logs.confirm_delete_file') }}</p>
        <div class="flex justify-end gap-3">
          <button
            @click="deleteConfirmOpen = false"
            class="rounded-3 border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400"
          >
            {{ $t('common.cancel') }}
          </button>
          <button
            @click="doDelete"
            :disabled="deleting"
            class="rounded-3 bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
          >
            {{ $t('admin_logs.delete_file') }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, watch, nextTick, onMounted, computed } from 'vue'
import {
  DocumentTextIcon,
  MagnifyingGlassIcon,
  ArrowDownTrayIcon,
  TrashIcon,
  ChevronDownIcon,
} from '@heroicons/vue/24/outline'
import { useStagger } from '@/composables/useAnimations'
import { useLogViewer } from '@/composables/useLogViewer'

const { staggerRef: logBodyRef, applyStagger } = useStagger(30)
const { fetchFiles, fetchLogs, requestDownload, deleteFile } = useLogViewer()

// ── Fichiers ──────────────────────────────────────────────────────────────────

const files = ref([])
const selectedFileId = ref('')
const loading = ref(false)

const selectedFile = computed(() => files.value.find((f) => f.identifier === selectedFileId.value) ?? null)

const loadFiles = async () => {
  try {
    const data = await fetchFiles()
    files.value = Array.isArray(data) ? data : (data.files ?? [])
    // Sélectionner le premier fichier si aucun n'est choisi
    if (!selectedFileId.value && files.value.length) {
      selectedFileId.value = files.value[0].identifier
      await loadLogs()
    }
  } catch {
    files.value = []
  }
}

const onFileChange = () => {
  excludedLevels.value = []
  searchQuery.value = ''
  expandedIndex.value = null
  loadLogs()
}

// ── Logs ──────────────────────────────────────────────────────────────────────

const logs = ref([])
const levelCounts = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const percentScanned = ref(100)
const searchQuery = ref('')
const excludedLevels = ref([])
const expandedIndex = ref(null)

const loadLogs = async (page = 1) => {
  if (!selectedFileId.value) return
  loading.value = true
  try {
    const params = {
      file: selectedFileId.value,
      page,
      per_page: 25,
    }
    if (searchQuery.value) params.query = searchQuery.value
    if (excludedLevels.value.length) params['exclude_levels[]'] = excludedLevels.value

    const data = await fetchLogs(params)
    logs.value = data.logs ?? []
    levelCounts.value = data.levelCounts ?? []
    pagination.value = data.pagination ?? pagination.value
    percentScanned.value = data.percentScanned ?? 100
    expandedIndex.value = null
    await nextTick()
    applyStagger()
  } catch {
    logs.value = []
  } finally {
    loading.value = false
  }
}

const changePage = (page) => loadLogs(page)

// Filtre par niveau : bascule l'exclusion
const toggleLevel = (level) => {
  const idx = excludedLevels.value.indexOf(level)
  if (idx === -1) {
    excludedLevels.value.push(level)
  } else {
    excludedLevels.value.splice(idx, 1)
  }
  loadLogs(1)
}

const isLevelExcluded = (level) => excludedLevels.value.includes(level)

// Recherche avec debounce
let searchDebounce = null
watch(searchQuery, () => {
  clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => loadLogs(1), 400)
})

// ── Expand ────────────────────────────────────────────────────────────────────

const toggleExpand = (index) => {
  expandedIndex.value = expandedIndex.value === index ? null : index
}

// ── Téléchargement ────────────────────────────────────────────────────────────

const downloading = ref(false)

const download = async () => {
  if (!selectedFileId.value) return
  downloading.value = true
  try {
    const url = await requestDownload(selectedFileId.value)
    window.location.href = url
  } catch {
    /* ignore */
  } finally {
    downloading.value = false
  }
}

// ── Suppression ───────────────────────────────────────────────────────────────

const deleteConfirmOpen = ref(false)
const deleting = ref(false)

const confirmDelete = () => (deleteConfirmOpen.value = true)

const doDelete = async () => {
  if (!selectedFileId.value) return
  deleting.value = true
  try {
    await deleteFile(selectedFileId.value)
    deleteConfirmOpen.value = false
    selectedFileId.value = ''
    logs.value = []
    levelCounts.value = []
    await loadFiles()
  } catch {
    /* ignore */
  } finally {
    deleting.value = false
  }
}

// ── Helpers d'affichage ───────────────────────────────────────────────────────

const levelBadgeClass = (level) => {
  switch (level) {
    case 'error': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
    case 'critical':
    case 'alert':
    case 'emergency': return 'bg-red-200 text-red-900 dark:bg-red-800/40 dark:text-red-300'
    case 'warning': return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400'
    case 'notice':
    case 'info': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
    case 'debug': return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
    default: return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
  }
}

const levelChipClass = (level) => {
  switch (level) {
    case 'error':
    case 'critical':
    case 'alert':
    case 'emergency': return 'border-red-200 bg-red-100 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400'
    case 'warning': return 'border-amber-200 bg-amber-100 text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-400'
    case 'notice':
    case 'info': return 'border-blue-200 bg-blue-100 text-blue-700 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-400'
    case 'debug': return 'border-gray-200 bg-gray-100 text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400'
    default: return 'border-gray-200 bg-gray-100 text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400'
  }
}

// Détecter les entrées de log d'email (contexte contenant to/subject)
const isMailLog = (log) => {
  if (!log.context) return false
  const ctx = typeof log.context === 'string' ? JSON.parse(log.context) : log.context

  return ctx && (ctx.to !== undefined || ctx.subject !== undefined)
}

onMounted(loadFiles)
</script>
