<!-- resources/js/pages/admin/AdminSupport.vue -->
<template>
  <admin-layout>
    <page-breadcrumb :page-title="$t('support.admin.page_title')" />

    <div class="space-y-6">
      <!-- Filters -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <div class="relative flex-1">
          <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
          <input
            v-model="search"
            type="text"
            :placeholder="$t('support.admin.search_placeholder')"
            class="w-full rounded-3 border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          />
        </div>
        <select
          v-model="statusFilter"
          class="rounded-3 border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
        >
          <option value="">{{ $t('support.admin.filter_all_statuses') }}</option>
          <option v-for="(label, key) in statusOptions" :key="key" :value="key">{{ label }}</option>
        </select>
        <select
          v-model="categoryFilter"
          class="rounded-3 border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
        >
          <option value="">{{ $t('support.admin.filter_all_categories') }}</option>
          <option v-for="(label, key) in categoryOptions" :key="key" :value="key">{{ label }}</option>
        </select>
      </div>

      <!-- Table -->
      <div class="rounded-3 border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div v-if="loading" class="space-y-3 p-6">
          <div v-for="i in 5" :key="i" class="h-12 animate-pulse rounded-3 bg-gray-200 dark:bg-gray-800" />
        </div>

        <div v-else-if="tickets.length === 0" class="p-12 text-center text-sm text-gray-500 dark:text-gray-400">
          {{ $t('support.admin.empty') }}
        </div>

        <table v-else class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-200 dark:border-gray-700">
              <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400">{{ $t('support.admin.col_id') }}</th>
              <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400">{{ $t('support.admin.col_user') }}</th>
              <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400">{{ $t('support.admin.col_category') }}</th>
              <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400">{{ $t('support.admin.col_subject') }}</th>
              <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400">{{ $t('support.admin.col_status') }}</th>
              <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400">{{ $t('support.admin.col_date') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="ticket in tickets"
              :key="ticket.id"
              class="border-b border-gray-100 dark:border-gray-800 last:border-0"
            >
              <td class="px-4 py-3 text-gray-500 dark:text-gray-400">#{{ ticket.id }}</td>
              <td class="px-4 py-3 text-gray-900 dark:text-white">{{ ticket.user?.nom_complet }}</td>
              <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $t(`support.categories.${ticket.category}`) }}</td>
              <td class="max-w-xs px-4 py-3">
                <p class="truncate text-gray-900 dark:text-white">{{ ticket.subject }}</p>
              </td>
              <td class="px-4 py-3">
                <select
                  :value="ticket.status"
                  @change="updateStatus(ticket, $event.target.value)"
                  class="rounded-3 border border-gray-300 bg-white px-2 py-1 text-xs focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                  <option v-for="(label, key) in statusOptions" :key="key" :value="key">{{ label }}</option>
                </select>
              </td>
              <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ formatDate(ticket.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </admin-layout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import api from '@/api/axios'

const { t } = useI18n()

const tickets = ref([])
const loading = ref(false)
const search = ref('')
const statusFilter = ref('')
const categoryFilter = ref('')

const statusOptions = computed(() => ({
  open: t('support.statuses.open'),
  in_progress: t('support.statuses.in_progress'),
  resolved: t('support.statuses.resolved'),
}))

const categoryOptions = computed(() => ({
  bug: t('support.categories.bug'),
  feature: t('support.categories.feature'),
  billing: t('support.categories.billing'),
  account: t('support.categories.account'),
  other: t('support.categories.other'),
}))

let debounceTimer = null
watch([search, statusFilter, categoryFilter], () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(loadTickets, 300)
})

const loadTickets = async () => {
  loading.value = true
  try {
    const res = await api.get('/admin/support', {
      params: {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        category: categoryFilter.value || undefined,
      },
    })
    tickets.value = res.data.data?.data ?? res.data.data ?? []
  } catch {
    // Ignore
  } finally {
    loading.value = false
  }
}

const updateStatus = async (ticket, newStatus) => {
  try {
    await api.patch(`/admin/support/${ticket.id}/status`, { status: newStatus })
    ticket.status = newStatus
  } catch {
    // Ignore
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })
}

onMounted(loadTickets)
</script>
