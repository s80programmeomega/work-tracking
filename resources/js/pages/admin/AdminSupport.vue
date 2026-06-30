<!-- resources/js/pages/admin/AdminSupport.vue -->
<!-- Vue de gestion des tickets de support — super-admin uniquement. -->
<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="$t('support.admin.page_title')" />
  <div class="space-y-6">
    <!-- Filtres -->
    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
      <div class="relative flex-1 min-w-48">
        <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
        <input v-model="search" type="text" :placeholder="$t('support.admin.search_placeholder')"
          class="w-full rounded-3 border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
      </div>
      <select v-model="statusFilter"
        class="rounded-3 border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white">
        <option value="">{{ $t('support.admin.filter_all_statuses') }}</option>
        <option v-for="(label, key) in statusOptions" :key="key" :value="key">{{ label }}</option>
      </select>
      <select v-model="categoryFilter"
        class="rounded-3 border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white">
        <option value="">{{ $t('support.admin.filter_all_categories') }}</option>
        <option v-for="(label, key) in categoryOptions" :key="key" :value="key">{{ label }}</option>
      </select>
      <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
        <input v-model="slaFilter" type="checkbox" class="rounded" />
        {{ $t('support.admin.filter_sla_breached') }}
      </label>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <!-- Liste des tickets -->
      <div class="rounded-3 border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div v-if="loading" class="space-y-3 p-6">
          <div v-for="i in 5" :key="i" class="h-16 animate-pulse rounded-3 bg-gray-200 dark:bg-gray-800" />
        </div>

        <p v-else-if="tickets.length === 0" class="p-12 text-center text-sm text-gray-500 dark:text-gray-400">
          {{ $t('support.admin.empty') }}
        </p>

        <div v-else ref="ticketsRef" class="divide-y divide-gray-100 dark:divide-gray-800">
          <div
            v-for="ticket in tickets"
            :key="ticket.id"
            dusk="admin-ticket-row"
            @click="selectTicket(ticket)"
            :class="[
              'stagger-item cursor-pointer p-4 transition-all hover:bg-gray-50 dark:hover:bg-gray-800/50',
              activeTicket?.id === ticket.id ? 'bg-blue-50 dark:bg-blue-900/10' : ''
            ]"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                  <span class="shrink-0 font-mono text-xs text-gray-400">#{{ ticket.ticket_number }}</span>
                  <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ ticket.subject }}</p>
                </div>
                <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">
                  {{ ticket.user?.nom_complet }} · {{ $t(`support.categories.${ticket.category}`) }}
                </p>
                <!-- SLA -->
                <div class="mt-1 flex items-center gap-2">
                  <span v-if="ticket.sla_breached"
                    class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                    ⚠ {{ $t('support.sla_breached') }}
                  </span>
                  <span v-else-if="ticket.sla_deadline" class="text-xs text-gray-400">
                    {{ $t('support.admin.col_sla') }}: {{ formatDate(ticket.sla_deadline) }}
                  </span>
                  <span v-if="ticket.replies?.length" class="text-xs text-blue-600 dark:text-blue-400">
                    {{ ticket.replies.length }} {{ $t('support.replies_title').toLowerCase() }}
                  </span>
                </div>
              </div>
              <div class="flex shrink-0 flex-col items-end gap-1">
                <span :class="statusBadgeClass(ticket.status)"
                  class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                  {{ $t(`support.statuses.${ticket.status}`) }}
                </span>
                <span class="text-xs text-gray-400">{{ formatDate(ticket.created_at) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Panneau de détail + réponse -->
      <div v-if="activeTicket" class="space-y-4">
        <!-- Détails du ticket -->
        <div class="rounded-3 border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
          <div class="mb-3 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
              #{{ activeTicket.ticket_number }} — {{ activeTicket.subject }}
            </h3>
            <button @click="activeTicket = null" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>
          <div class="space-y-2 text-sm">
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ activeTicket.message }}</p>
            <div v-if="activeTicket.steps_to_reproduce" class="rounded-3 bg-gray-50 p-3 dark:bg-gray-800">
              <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ $t('support.steps_label') }}</p>
              <p class="whitespace-pre-line text-gray-700 dark:text-gray-300">{{ activeTicket.steps_to_reproduce }}</p>
            </div>
            <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
              <span v-if="activeTicket.reproducibility">
                {{ $t('support.reproducibility_label') }}: {{ $t(`support.reproducibilities.${activeTicket.reproducibility}`) }}
              </span>
              <span v-if="activeTicket.first_responded_at">
                {{ $t('support.first_response') }}: {{ formatDate(activeTicket.first_responded_at) }}
              </span>
              <span v-if="activeTicket.resolved_at">
                {{ $t('support.resolved_at') }}: {{ formatDate(activeTicket.resolved_at) }}
              </span>
            </div>
          </div>

          <!-- Pièces jointes -->
          <div v-if="activeTicket.attachments?.length" class="mt-3 flex flex-wrap gap-2">
            <a v-for="att in activeTicket.attachments" :key="att.id"
              :href="`/api/support/attachments/${att.id}/download`"
              class="inline-flex items-center gap-1 rounded-3 border border-gray-200 bg-white px-2.5 py-1 text-xs text-blue-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-blue-400">
              <PaperClipIcon class="h-3 w-3" />{{ att.original_name }}
            </a>
          </div>
        </div>

        <!-- Fil de réponses -->
        <div v-if="activeTicket.replies?.length" class="rounded-3 border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
          <p class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('support.replies_title') }}</p>
          <div ref="repliesRef" class="space-y-3">
            <div v-for="reply in activeTicket.replies" :key="reply.id" :class="[
              'stagger-item rounded-3 p-3 text-sm',
              reply.is_admin_reply
                ? 'bg-blue-50 dark:bg-blue-900/20'
                : 'bg-gray-50 dark:bg-gray-800'
            ]">
              <p class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">
                {{ reply.author?.nom_complet }}
                <span v-if="reply.is_admin_reply" class="ml-1 text-blue-600 dark:text-blue-400">(Admin)</span>
                · {{ formatDate(reply.created_at) }}
              </p>
              <p class="whitespace-pre-line text-gray-900 dark:text-white">{{ reply.body }}</p>
            </div>
          </div>
        </div>

        <!-- Formulaire de réponse obligatoire -->
        <div class="rounded-3 border border-blue-200 bg-blue-50 p-5 dark:border-blue-800 dark:bg-blue-900/10">
          <h4 class="mb-3 text-sm font-semibold text-blue-900 dark:text-blue-300">{{ $t('support.admin.reply_title') }}</h4>
          <form @submit.prevent="submitReply" class="space-y-3">
            <textarea v-model="replyForm.body" rows="4" required dusk="admin-reply-body"
              :placeholder="$t('support.admin.reply_placeholder')"
              class="w-full rounded-3 border border-blue-200 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-blue-700 dark:bg-gray-800 dark:text-white" />
            <p v-if="replyErrors.body" class="text-xs text-red-600">{{ replyErrors.body[0] }}</p>

            <div class="flex items-center gap-3">
              <label class="text-xs font-medium text-blue-900 dark:text-blue-300 shrink-0">
                {{ $t('support.admin.reply_status_label') }}
              </label>
              <select v-model="replyForm.status" required dusk="admin-reply-status"
                class="flex-1 rounded-3 border border-blue-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none dark:border-blue-700 dark:bg-gray-800 dark:text-white">
                <option v-for="(label, key) in statusOptions" :key="key" :value="key">{{ label }}</option>
              </select>
            </div>
            <p v-if="replyErrors.status" class="text-xs text-red-600">{{ replyErrors.status[0] }}</p>

            <button type="submit" :disabled="replying || !replyForm.body.trim()" dusk="admin-reply-submit"
              class="w-full rounded-3 bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50 transition-colors">
              <span v-if="replying" class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
              {{ replying ? $t('support.admin.reply_submitting') : $t('support.admin.reply_submit') }}
            </button>
          </form>
        </div>
      </div>

      <!-- Placeholder quand aucun ticket sélectionné -->
      <div v-else class="flex items-center justify-center rounded-3 border-2 border-dashed border-gray-200 p-12 text-center dark:border-gray-700">
        <p class="text-sm text-gray-400 dark:text-gray-500">Sélectionnez un ticket pour le traiter.</p>
      </div>
    </div>
  </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { MagnifyingGlassIcon, PaperClipIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useStagger } from '@/composables/useAnimations'
import api from '@/api/axios'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'

const { t } = useI18n()
const { staggerRef: ticketsRef, applyStagger: staggerTickets } = useStagger(40)
const { staggerRef: repliesRef, applyStagger: staggerReplies } = useStagger(30)

const tickets = ref([])
const loading = ref(false)
const activeTicket = ref(null)
const search = ref('')
const statusFilter = ref('')
const categoryFilter = ref('')
const slaFilter = ref(false)

const replyForm = ref({ body: '', status: 'in_progress' })
const replyErrors = ref({})
const replying = ref(false)

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

const statusBadgeClass = (status) => ({
  open: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
  in_progress: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
  resolved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
}[status] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400')

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })
}

let debounce = null
watch([search, statusFilter, categoryFilter, slaFilter], () => {
  clearTimeout(debounce)
  debounce = setTimeout(loadTickets, 300)
})

const loadTickets = async () => {
  loading.value = true
  try {
    const res = await api.get('/admin/support', {
      params: {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        category: categoryFilter.value || undefined,
        sla_breached: slaFilter.value || undefined,
      },
    })
    tickets.value = res.data.data?.data ?? []
    await nextTick()
    staggerTickets()
  } catch { /* ignore */ } finally {
    loading.value = false
  }
}

const selectTicket = async (ticket) => {
  activeTicket.value = ticket
  replyForm.value = { body: '', status: ticket.status === 'open' ? 'in_progress' : ticket.status }
  replyErrors.value = {}
  await nextTick()
  staggerReplies()
}

const submitReply = async () => {
  replyErrors.value = {}
  replying.value = true
  try {
    const res = await api.post(`/admin/support/${activeTicket.value.id}/reply`, replyForm.value)
    // Mise à jour locale du ticket actif
    activeTicket.value.replies = [...(activeTicket.value.replies ?? []), res.data.data.reply]
    activeTicket.value.status = res.data.data.status
    // Mise à jour dans la liste
    const idx = tickets.value.findIndex((t) => t.id === activeTicket.value.id)
    if (idx !== -1) tickets.value[idx].status = res.data.data.status
    replyForm.value.body = ''
    await nextTick()
    staggerReplies()
  } catch (err) {
    if (err.response?.status === 422) replyErrors.value = err.response.data.errors ?? {}
  } finally {
    replying.value = false
  }
}

onMounted(loadTickets)
</script>
