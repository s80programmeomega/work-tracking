<!-- Onglet Journal d'audit admin-plateforme : actions opérateur (superadmin / directeur). -->
<template>
  <div class="space-y-4 p-4">

    <!-- Filtres -->
    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">

      <!-- Filtre action -->
      <select
        v-model="filters.action"
        class="rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
      >
        <option value="">{{ $t('admin_audit.filter_all_actions') }}</option>
        <option v-for="a in knownActions" :key="a.value" :value="a.value">{{ a.label }}</option>
      </select>

      <!-- Plage de dates -->
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
                :placeholder="$t('admin_audit.date_from')"
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
                :placeholder="$t('admin_audit.date_to')"
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
        {{ $t('admin_audit.no_logs') }}
      </p>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50">
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('admin_audit.col_date') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('admin_audit.col_actor') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('admin_audit.col_actor_type') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('admin_audit.col_action') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('admin_audit.col_target') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('admin_audit.col_ip') }}</th>
            </tr>
          </thead>
          <tbody ref="tableBodyRef" class="divide-y divide-gray-100 dark:divide-gray-800">
            <tr
              v-for="entry in entries"
              :key="entry.id"
              dusk="admin-audit-row"
              class="stagger-item transition-colors hover:bg-blue-50/60 dark:hover:bg-blue-900/10"
            >
              <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                {{ formatDate(entry.created_at) }}
              </td>
              <td class="px-4 py-3">
                <span v-if="entry.actor" class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ entry.actor.nom }}
                  <span class="block text-xs font-normal text-gray-400">{{ entry.actor.email }}</span>
                </span>
                <span v-else class="text-xs text-gray-400">—</span>
              </td>
              <td class="px-4 py-3">
                <span :class="actorTypeBadgeClass(entry.actor_type)" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold">
                  {{ $t(`admin_audit.actor_type_${entry.actor_type}`, entry.actor_type) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span :class="actionBadgeClass(entry.action)" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold">
                  {{ $t(`admin_audit.action_${entry.action.replace('.', '_')}`, entry.action) }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                <span v-if="entry.target_type">{{ entry.target_type }} #{{ entry.target_id }}</span>
                <span v-else class="text-gray-400">—</span>
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-400">
                {{ entry.ip_address || '—' }}
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
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue'
import { CalendarIcon } from '@heroicons/vue/24/outline'
import { useStagger } from '@/composables/useAnimations'
import { useI18n } from 'vue-i18n'
import api from '@/api/axios'
import DatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'

const props = defineProps({
  endpoint: { type: String, default: '/admin/audit-log' },
})

const { t } = useI18n()
const { staggerRef: tableBodyRef, applyStagger } = useStagger(30)

const isDark = computed(() => document.documentElement.classList.contains('dark'))

const entries = ref([])
const loading = ref(false)
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const filters = ref({ action: '', date_from: '', date_to: '' })

const knownActions = [
  { value: 'stats.read', label: t('admin_audit.action_stats_read') },
  { value: 'workspaces.list', label: t('admin_audit.action_workspaces_list') },
  { value: 'users.list', label: t('admin_audit.action_users_list') },
  { value: 'superadmins.list', label: t('admin_audit.action_superadmins_list') },
  { value: 'workspace.suspend', label: t('admin_audit.action_workspace_suspend') },
  { value: 'workspace.reactivate', label: t('admin_audit.action_workspace_reactivate') },
  { value: 'workspace.extend_trial', label: t('admin_audit.action_workspace_extend_trial') },
  { value: 'workspace.read', label: t('admin_audit.action_workspace_read') },
  { value: 'user.role_updated', label: t('admin_audit.action_user_role_updated') },
  { value: 'superadmin.terminated', label: t('admin_audit.action_superadmin_terminated') },
  { value: 'superadmin.created', label: t('admin_audit.action_superadmin_created') },
  { value: 'superadmin.credentials_sent', label: t('admin_audit.action_superadmin_credentials_sent') },
  { value: 'superadmin.reactivated', label: t('admin_audit.action_superadmin_reactivated') },
]

const loadEntries = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      per_page: 25,
      page,
      ...Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v !== '')),
    }
    const res = await api.get(props.endpoint, { params })
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
  filters.value = { action: '', date_from: '', date_to: '' }
}

watch(filters, () => loadEntries(1), { deep: true })
onMounted(() => loadEntries())

const actionBadgeClass = (action) => {
  if (['workspace.suspend', 'superadmin.terminated'].includes(action)) {
    return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
  }
  if (['workspace.reactivate', 'workspace.extend_trial'].includes(action)) {
    return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
  }
  if (['user.role_updated'].includes(action)) {
    return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400'
  }

  return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
}

const actorTypeBadgeClass = (type) => {
  if (type === 'system_owner') return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400'
  if (type === 'permanent_superadmin') return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
  if (type === 'temporary_superadmin') return 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400'

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

const formatDisplay = (dateStr) => {
  if (!dateStr) return ''
  const [y, m, d] = dateStr.split('-')
  return `${d}/${m}/${y}`
}
</script>
