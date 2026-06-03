<!-- resources/js/pages/admin/AdminLogs.vue -->
<!-- Journal applicatif unifié : onglet Activité (Spatie) + onglet Logs fichier (log-viewer). -->
<template>
  <admin-layout>
    <page-breadcrumb :page-title="$t('admin_logs.page_title')" />

    <div class="space-y-4">
      <!-- Tabs -->
      <div class="rounded-3 border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <nav class="flex space-x-1 overflow-x-auto border-b border-gray-200 px-4 dark:border-gray-800">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'flex items-center gap-2 px-4 py-3.5 text-sm font-medium transition-all whitespace-nowrap',
              activeTab === tab.id
                ? 'border-b-2 border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
            ]"
          >
            <component :is="tab.icon" class="h-4 w-4" />
            {{ tab.label }}
          </button>
        </nav>

        <!-- ════ TAB 1 : ACTIVITY LOG ════ -->
        <div v-if="activeTab === 'activity'" class="p-4 space-y-4">
          <!-- Filtres -->
          <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
            <div class="relative flex-1 min-w-48">
              <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
              <input v-model="filters.search" type="text" :placeholder="$t('admin_logs.search_placeholder')"
                class="w-full rounded-3 border border-gray-300 bg-white py-2 pl-9 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>

            <select v-model="filters.event"
              class="rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white">
              <option value="">{{ $t('admin_logs.all_events') }}</option>
              <option v-for="ev in knownEvents" :key="ev" :value="ev">{{ ev }}</option>
            </select>

            <select v-model="filters.subject_type"
              class="rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white">
              <option value="">{{ $t('admin_logs.all_subjects') }}</option>
              <option v-for="st in knownSubjects" :key="st.value" :value="st.value">{{ st.label }}</option>
            </select>

            <div class="flex gap-2">
              <input v-model="filters.date_from" type="date"
                class="rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
              <input v-model="filters.date_to" type="date"
                class="rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>

            <button @click="resetFilters"
              class="rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-transparent dark:text-gray-400 dark:hover:bg-gray-800 transition-colors">
              {{ $t('admin_logs.reset') }}
            </button>
          </div>

          <!-- Table -->
          <div class="overflow-hidden rounded-3 border border-gray-200 dark:border-gray-700">
            <div v-if="loading" class="space-y-2 p-4">
              <div v-for="i in 8" :key="i" class="h-10 animate-pulse rounded-3 bg-gray-200 dark:bg-gray-800" />
            </div>

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
                  <tr v-for="entry in activities" :key="entry.id"
                    class="stagger-item hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                    <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                      {{ formatDate(entry.created_at) }}
                    </td>
                    <td class="px-4 py-3">
                      <span v-if="entry.causer" class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ entry.causer.name || entry.causer.email || `#${entry.causer_id}` }}
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
                  <button :disabled="meta.current_page <= 1" @click="changePage(meta.current_page - 1)"
                    class="rounded-3 border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed dark:border-gray-700 dark:bg-transparent dark:text-gray-400 transition-colors">
                    {{ $t('common.previous') }}
                  </button>
                  <span class="flex items-center px-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ meta.current_page }} / {{ meta.last_page }}
                  </span>
                  <button :disabled="meta.current_page >= meta.last_page" @click="changePage(meta.current_page + 1)"
                    class="rounded-3 border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed dark:border-gray-700 dark:bg-transparent dark:text-gray-400 transition-colors">
                    {{ $t('common.next') }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ════ TAB 2 : APPLICATION LOGS (log-viewer iframe) ════ -->
        <div v-else-if="activeTab === 'app_logs'" class="p-0">
          <div class="relative">
            <!-- Barre de chargement / info -->
            <div v-if="!iframeLoaded" class="flex items-center justify-center py-20">
              <div class="flex flex-col items-center gap-3 text-gray-400 dark:text-gray-500">
                <div class="h-8 w-8 animate-spin rounded-full border-2 border-blue-500 border-t-transparent" />
                <p class="text-sm">{{ $t('admin_logs.loading_viewer') }}</p>
              </div>
            </div>
            <iframe
              :src="logViewerUrl"
              :class="['w-full rounded-b-3 border-0 transition-opacity', iframeLoaded ? 'opacity-100' : 'opacity-0 h-0']"
              style="height: 75vh;"
              @load="iframeLoaded = true"
              title="Log Viewer"
              sandbox="allow-same-origin allow-scripts allow-forms allow-popups"
            />
          </div>
        </div>
      </div>
    </div>
  </admin-layout>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { MagnifyingGlassIcon, ClipboardDocumentListIcon, DocumentTextIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/authStore'
import { useStagger } from '@/composables/useAnimations'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import api from '@/api/axios'

const { t } = useI18n()
const authStore = useAuthStore()
const { staggerRef: tableBodyRef, applyStagger } = useStagger(30)

// ── Tabs ──────────────────────────────────────────────────────────────────────

const activeTab = ref('activity')

const tabs = computed(() => [
  { id: 'activity',  label: t('admin_logs.tab_activity'),  icon: ClipboardDocumentListIcon },
  { id: 'app_logs',  label: t('admin_logs.tab_app_logs'),  icon: DocumentTextIcon },
])

// ── Log-viewer iframe ─────────────────────────────────────────────────────────

const iframeLoaded = ref(false)

const logViewerUrl = computed(() =>
  `/log-viewer?token=${authStore.token ?? ''}`
)

// Réinitialiser l'état de chargement quand on revient sur l'onglet
watch(activeTab, (val) => {
  if (val === 'app_logs') iframeLoaded.value = false
})

// ── Activity log ──────────────────────────────────────────────────────────────

const activities = ref([])
const loading = ref(false)
const meta = ref({ current_page: 1, last_page: 1, per_page: 25, total: 0 })

const filters = ref({
  search: '',
  event: '',
  subject_type: '',
  date_from: '',
  date_to: '',
})

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
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

let debounce = null
watch(filters, () => {
  clearTimeout(debounce)
  debounce = setTimeout(() => loadActivities(1), 350)
}, { deep: true })

const loadActivities = async (page = 1) => {
  loading.value = true
  try {
    const params = { per_page: 25, page, ...Object.fromEntries(
      Object.entries(filters.value).filter(([, v]) => v !== '')
    )}
    const res = await api.get('/admin/activity-log', { params })
    activities.value = res.data.data ?? []
    meta.value = res.data.meta ?? meta.value
    await nextTick()
    applyStagger()
  } catch { /* ignore */ } finally {
    loading.value = false
  }
}

const changePage = (page) => loadActivities(page)

const resetFilters = () => {
  filters.value = { search: '', event: '', subject_type: '', date_from: '', date_to: '' }
}

onMounted(() => loadActivities())
</script>
