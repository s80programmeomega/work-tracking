<!-- resources\js\components\profile\ActivityLog.vue -->
<template>
  <div class="activity-log">
    <div class="mb-6">
      <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">
        {{ $t('activity_log.title') }}
      </h4>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ $t('activity_log.subtitle') }}
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="p-8 text-center">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-500 dark:text-gray-400">{{ $t('activity_log.loading') }}</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="p-8 text-center">
      <p class="text-red-500 dark:text-red-400">{{ error }}</p>
      <button
        @click="loadActivities"
        class="mt-3 px-4 py-2 text-sm font-medium text-blue-600 transition-colors bg-blue-50 rounded-3 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400"
      >
        {{ $t('activity_log.btn_refresh') }}
      </button>
    </div>

    <!-- Activity Content -->
    <div v-else class="space-y-6">
      <!-- Filters -->
      <div class="flex flex-col gap-4 p-4 bg-gray-50 border border-gray-200 rounded-3 dark:bg-gray-800 dark:border-gray-700 sm:flex-row sm:items-center">
        <div class="flex items-center space-x-4">
          <button
            v-for="filter in timeFilters"
            :key="filter.value"
            @click="selectTimeFilter(filter.value)"
            :class="[
              'px-3 py-1.5 text-sm font-medium rounded-3 transition-colors',
              selectedTimeFilter === filter.value
                ? 'bg-blue-600 text-white'
                : 'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
            ]"
          >
            {{ filter.label }}
          </button>
        </div>

        <div class="flex items-center space-x-4">
          <select
            v-model="selectedType"
            @change="currentPage = 1"
            class="bg-white border border-gray-300 text-gray-700 text-sm rounded-3 focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
          >
            <option value="all">{{ $t('activity_log.type_all') }}</option>
            <option value="task">{{ $t('activity_log.type_task') }}</option>
            <option value="project">{{ $t('activity_log.type_project') }}</option>
            <option value="profile">{{ $t('activity_log.type_profile') }}</option>
            <option value="system">{{ $t('activity_log.type_system') }}</option>
          </select>
        </div>

        <div class="flex items-center ml-auto space-x-2">
          <span class="text-sm text-gray-600 dark:text-gray-400">
            {{ $t('activity_log.count', { count: filteredActivities.length }) }}
          </span>
          <button
            @click="loadActivities"
            class="p-2 text-gray-500 transition-colors rounded-3 hover:bg-gray-200 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300"
            :title="$t('activity_log.btn_refresh')"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </button>
          <button
            @click="exportActivities"
            class="p-2 text-gray-500 transition-colors rounded-3 hover:bg-gray-200 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300"
            :title="$t('activity_log.btn_export')"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Activity Timeline -->
      <div class="relative">
        <!-- Timeline line -->
        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

        <!-- Activities -->
        <div ref="staggerRef" class="space-y-6">
          <div
            v-for="(activity, index) in paginatedActivities"
            :key="activity.id"
            class="stagger-item relative flex gap-4"
          >
            <!-- Timeline dot -->
            <div class="relative z-10 flex-shrink-0">
              <div
                :class="[
                  'w-8 h-8 rounded-full flex items-center justify-center',
                  getActivityTypeClass(activity.type)
                ]"
              >
                <component :is="getActivityIcon(activity.type)" class="w-4 h-4 text-white" />
              </div>
            </div>

            <!-- Activity content -->
            <div class="flex-1 pb-6">
              <div class="p-4 bg-white border border-gray-200 rounded-3 dark:bg-gray-800 dark:border-gray-700">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                  <div>
                    <h5 class="font-medium text-gray-800 dark:text-white/90">
                      {{ activity.title }}
                    </h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                      {{ activity.description }}
                    </p>
                  </div>
                  <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                      {{ formatTime(activity.timestamp) }}
                    </span>
                    <span
                      :class="[
                        'px-2 py-1 text-xs font-medium rounded-full',
                        getActivityTypeBadgeClass(activity.type)
                      ]"
                    >
                      {{ getActivityTypeLabel(activity.type) }}
                    </span>
                  </div>
                </div>

                <!-- Activity details: diff lines -->
                <div v-if="activity.details" class="mt-3">
                  <div class="p-3 bg-gray-50 rounded-3 dark:bg-gray-700 space-y-1">
                    <div
                      v-for="(line, i) in activity.details.split('\n')"
                      :key="i"
                      class="flex items-start gap-2 text-xs text-gray-600 dark:text-gray-300"
                    >
                      <span class="mt-px text-gray-400 dark:text-gray-500 shrink-0">▸</span>
                      <span>{{ line }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Separator (except for last item) -->
              <div v-if="index < paginatedActivities.length - 1" class="absolute left-4 -bottom-3 w-0.5 h-6 bg-gray-200 dark:bg-gray-700"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="filteredActivities.length === 0" class="p-8 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 mb-4 text-gray-400 bg-gray-100 rounded-full dark:bg-gray-800">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <h5 class="mb-2 text-lg font-medium text-gray-800 dark:text-white/90">
          {{ $t('activity_log.empty_title') }}
        </h5>
        <p class="text-gray-500 dark:text-gray-400">
          {{ $t('activity_log.empty_desc') }}
        </p>
      </div>

      <!-- Pagination -->
      <div v-if="filteredActivities.length > itemsPerPage" class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-800">
        <div class="text-sm text-gray-500 dark:text-gray-400">
          {{ $t('activity_log.pagination_showing', { start: startIndex + 1, end: endIndex, total: filteredActivities.length }) }}
        </div>
        <div class="flex gap-2">
          <button
            @click="currentPage--"
            :disabled="currentPage === 1"
            class="px-3 py-1.5 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-3 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            {{ $t('activity_log.btn_prev') }}
          </button>
          <div class="flex items-center space-x-1">
            <button
              v-for="page in totalPages"
              :key="page"
              @click="currentPage = page"
              :class="[
                'px-3 py-1.5 text-sm font-medium rounded-3',
                currentPage === page
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
              ]"
            >
              {{ page }}
            </button>
          </div>
          <button
            @click="currentPage++"
            :disabled="currentPage === totalPages"
            class="px-3 py-1.5 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-3 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            {{ $t('activity_log.btn_next') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  CheckCircleIcon,
  DocumentTextIcon,
  UserIcon,
  CogIcon,
  CalendarIcon,
  ClockIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'
import { useStagger } from '@/composables/useAnimations'
import api from '@/api/axios'

const props = defineProps({
  user: {
    type: Object,
    default: () => ({})
  }
})

const { t } = useI18n()
const loading = ref(true)
const error = ref(null)
const activities = ref([])
const selectedTimeFilter = ref('all')
const selectedType = ref('all')
const currentPage = ref(1)
const itemsPerPage = ref(10)

const { staggerRef, applyStagger } = useStagger(50)

const timeFilters = computed(() => [
  { label: t('activity_log.filter_today'), value: 'today' },
  { label: t('activity_log.filter_week'), value: 'week' },
  { label: t('activity_log.filter_month'), value: 'month' },
  { label: t('activity_log.filter_all'), value: 'all' }
])

const activityTypes = computed(() => ({
  task: {
    label: t('activity_log.type_label_task'),
    icon: CheckCircleIcon,
    bgColor: 'bg-green-500',
    badgeColor: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
  },
  project: {
    label: t('activity_log.type_label_project'),
    icon: DocumentTextIcon,
    bgColor: 'bg-blue-500',
    badgeColor: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
  },
  profile: {
    label: t('activity_log.type_label_profile'),
    icon: UserIcon,
    bgColor: 'bg-purple-500',
    badgeColor: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300'
  },
  system: {
    label: t('activity_log.type_label_system'),
    icon: CogIcon,
    bgColor: 'bg-gray-500',
    badgeColor: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
  },
  deadline: {
    label: t('activity_log.type_label_deadline'),
    icon: CalendarIcon,
    bgColor: 'bg-orange-500',
    badgeColor: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300'
  },
  login: {
    label: t('activity_log.type_label_login'),
    icon: ClockIcon,
    bgColor: 'bg-indigo-500',
    badgeColor: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300'
  },
  error: {
    label: t('activity_log.type_label_error'),
    icon: ExclamationTriangleIcon,
    bgColor: 'bg-red-500',
    badgeColor: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
  }
}))

// Map spatie subject_type to UI type categories
const mapSubjectType = (subjectType) => {
  if (!subjectType) { return 'system' }
  const short = subjectType.replace(/^App\\Models\\/, '').replace(/^App\/Models\//, '')
  if (['Tache', 'SousTache', 'TacheResultat'].includes(short)) { return 'task' }
  if (['Projet', 'Activite'].includes(short)) { return 'project' }
  if (['User'].includes(short)) { return 'profile' }
  if (['PersonalAccessToken'].includes(short)) { return 'login' }
  return 'system'
}

const mapEvent = (event) => {
  if (!event) { return 'system' }
  const e = event.toLowerCase()
  if (e.includes('login') || e.includes('logout') || e.includes('session') || e.includes('connexion')) { return 'login' }
  return 'system'
}

// Human-readable event verb
const humanizeEvent = (event) => {
  if (!event) { return t('activity_log.event_updated') }
  const e = event.toLowerCase()
  if (e.includes('created') || e.includes('créé')) { return t('activity_log.event_created') }
  if (e.includes('updated') || e.includes('mis à jour') || e.includes('modifié')) { return t('activity_log.event_updated') }
  if (e.includes('deleted') || e.includes('supprimé')) { return t('activity_log.event_deleted') }
  if (e.includes('login') || e.includes('connexion')) { return t('activity_log.event_logged_in') }
  if (e.includes('logout') || e.includes('déconnexion')) { return t('activity_log.event_logged_out') }
  if (e.includes('registered') || e.includes('inscription')) { return t('activity_log.event_registered') }
  if (e.includes('assigned') || e.includes('assigné')) { return t('activity_log.event_assigned') }
  if (e.includes('completed') || e.includes('terminé')) { return t('activity_log.event_completed') }
  if (e.includes('comment')) { return t('activity_log.event_commented') }
  if (e.includes('upload') || e.includes('document')) { return t('activity_log.event_uploaded') }
  // Capitalise and return as-is
  return event.charAt(0).toUpperCase() + event.slice(1)
}

// Human-readable subject label
const humanizeSubject = (subjectType, props) => {
  if (!subjectType) { return '' }
  const short = subjectType.replace(/^App\\Models\\/, '').replace(/^App\/Models\//, '')
  const nameFromProps = props?.attributes?.titre
    ?? props?.attributes?.nom
    ?? props?.attributes?.name
    ?? props?.subject_name
    ?? props?.name
    ?? null
  const labels = {
    Tache: t('activity_log.subject_task'),
    SousTache: t('activity_log.subject_subtask'),
    TacheResultat: t('activity_log.subject_result'),
    Projet: t('activity_log.subject_project'),
    Activite: t('activity_log.subject_activity'),
    User: t('activity_log.subject_user'),
    PersonalAccessToken: t('activity_log.subject_session'),
    Document: t('activity_log.subject_document'),
  }
  const typeLabel = labels[short] ?? short
  return nameFromProps ? `${typeLabel} « ${nameFromProps} »` : typeLabel
}

// Build readable diff lines from spatie properties.old / properties.attributes
const buildDiffLines = (properties) => {
  if (!properties) { return [] }
  const oldVals = properties.old ?? {}
  const newVals = properties.attributes ?? {}
  const fieldLabels = {
    titre: t('activity_log.field_title'),
    nom: t('activity_log.field_name'),
    statut: t('activity_log.field_status'),
    priorite: t('activity_log.field_priority'),
    taux_realisation: t('activity_log.field_progress'),
    echeance: t('activity_log.field_deadline'),
    description: t('activity_log.field_description'),
    responsable_id: t('activity_log.field_owner'),
    email: 'Email',
    timezone: t('activity_log.field_timezone'),
    language: t('activity_log.field_language'),
  }
  const SKIP = new Set(['updated_at', 'created_at', 'id', 'password', 'remember_token'])
  const allKeys = new Set([...Object.keys(oldVals), ...Object.keys(newVals)])
  const lines = []
  allKeys.forEach(k => {
    if (SKIP.has(k)) { return }
    const o = oldVals[k]
    const n = newVals[k]
    if (o === n) { return }
    const label = fieldLabels[k] ?? k
    if (o !== undefined && n !== undefined) {
      lines.push(`${label}: ${o} → ${n}`)
    } else if (n !== undefined) {
      lines.push(`${label}: ${n}`)
    }
  })
  return lines
}

// Normalize a spatie Activity record into the shape the template expects
const normalizeActivity = (item) => {
  const type = item.subject_type
    ? mapSubjectType(item.subject_type)
    : mapEvent(item.event)

  const verb = humanizeEvent(item.event)
  const subject = humanizeSubject(item.subject_type, item.properties)
  const title = subject ? `${verb} — ${subject}` : verb

  const diffLines = buildDiffLines(item.properties)
  const description = item.human_readable
    ?? (diffLines.length ? diffLines.join('\n') : (item.description ?? ''))

  return {
    id: item.id,
    type,
    title,
    description,
    timestamp: new Date(item.created_at),
    details: diffLines.length > 1 ? diffLines.join('\n') : null,
  }
}

const loadActivities = async () => {
  if (!props.user?.id) { return }
  loading.value = true
  error.value = null
  try {
    const { data } = await api.get(`/users/${props.user.id}/activity`)
    activities.value = (data.data ?? []).map(normalizeActivity)
    await nextTick()
    applyStagger()
  } catch (err) {
    error.value = err.response?.data?.message ?? t('activity_log.loading_error')
  } finally {
    loading.value = false
  }
}

const selectTimeFilter = (value) => {
  selectedTimeFilter.value = value
  currentPage.value = 1
}

const filteredActivities = computed(() => {
  let filtered = [...activities.value]

  // Filtre par période
  const now = new Date()
  if (selectedTimeFilter.value === 'today') {
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
    filtered = filtered.filter(a => a.timestamp >= today)
  } else if (selectedTimeFilter.value === 'week') {
    const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000)
    filtered = filtered.filter(a => a.timestamp >= weekAgo)
  } else if (selectedTimeFilter.value === 'month') {
    const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000)
    filtered = filtered.filter(a => a.timestamp >= monthAgo)
  }

  // Filtre par type
  if (selectedType.value !== 'all') {
    filtered = filtered.filter(a => a.type === selectedType.value)
  }

  return filtered
})

const paginatedActivities = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredActivities.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(filteredActivities.value.length / itemsPerPage.value)
})

const startIndex = computed(() => {
  return (currentPage.value - 1) * itemsPerPage.value
})

const endIndex = computed(() => {
  return Math.min(startIndex.value + itemsPerPage.value, filteredActivities.value.length)
})

const getActivityIcon = (type) => {
  return activityTypes.value[type]?.icon || CogIcon
}

const getActivityTypeClass = (type) => {
  return activityTypes.value[type]?.bgColor || 'bg-gray-500'
}

const getActivityTypeBadgeClass = (type) => {
  return activityTypes.value[type]?.badgeColor || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
}

const getActivityTypeLabel = (type) => {
  return activityTypes.value[type]?.label || t('activity_log.type_label_system')
}

const formatTime = (timestamp) => {
  const now = new Date()
  const diffMs = now - timestamp
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMins / 60)
  const diffDays = Math.floor(diffHours / 24)

  if (diffMins < 1) { return t('activity_log.time_now') }
  if (diffMins < 60) { return t('activity_log.time_mins_ago', { n: diffMins }) }
  if (diffHours < 24) { return t('activity_log.time_hours_ago', { n: diffHours }) }
  if (diffDays < 7) { return t('activity_log.time_days_ago', { n: diffDays }) }

  return timestamp.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const exportActivities = () => {
  const dataStr = JSON.stringify(filteredActivities.value, null, 2)
  const dataUri = 'data:application/json;charset=utf-8,' + encodeURIComponent(dataStr)
  const exportFileDefaultName = `activites-${new Date().toISOString().split('T')[0]}.json`
  const linkElement = document.createElement('a')
  linkElement.setAttribute('href', dataUri)
  linkElement.setAttribute('download', exportFileDefaultName)
  linkElement.click()
}

onMounted(() => {
  loadActivities()
})
</script>
