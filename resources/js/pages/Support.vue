<!-- resources/js/pages/Support.vue -->
<!-- Page unifiée Aide & Support — formulaire utilisateur OU gestion admin selon le rôle. -->
<template>
  <admin-layout>
    <page-breadcrumb :page-title="$t('support.page_title')" />

    <!-- ===== VUE ADMIN ===== -->
    <admin-support v-if="isSuperAdmin" />

    <!-- ===== VUE UTILISATEUR ===== -->
    <div v-else class="space-y-6">
      <div class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('support.page_title') }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('support.page_subtitle') }}</p>
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Formulaire de création -->
        <div class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
          <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ $t('support.form_title') }}</h2>

          <form @submit.prevent="submitTicket" class="space-y-4">
            <!-- Catégorie -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('support.category_label') }}
              </label>
              <select v-model="form.category" dusk="support-category"
                class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">{{ $t('support.category_placeholder') }}</option>
                <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
              </select>
              <p v-if="errors.category" class="mt-1 text-xs text-red-600">{{ errors.category[0] }}</p>
            </div>

            <!-- Sujet -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('support.subject_label') }}
              </label>
              <input v-model="form.subject" type="text" dusk="support-subject"
                :placeholder="$t('support.subject_placeholder')"
                class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
              <p v-if="errors.subject" class="mt-1 text-xs text-red-600">{{ errors.subject[0] }}</p>
            </div>

            <!-- Message -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('support.message_label') }}
              </label>
              <textarea v-model="form.message" rows="4" dusk="support-message"
                :placeholder="$t('support.message_placeholder')"
                class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
              <p v-if="errors.message" class="mt-1 text-xs text-red-600">{{ errors.message[0] }}</p>
            </div>

            <!-- Reproductibilité -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('support.reproducibility_label') }}
              </label>
              <select v-model="form.reproducibility"
                class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">{{ $t('support.reproducibility_placeholder') }}</option>
                <option v-for="(label, key) in reproducibilities" :key="key" :value="key">{{ label }}</option>
              </select>
            </div>

            <!-- Étapes de reproduction -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('support.steps_label') }}
              </label>
              <textarea v-model="form.steps_to_reproduce" rows="3"
                :placeholder="$t('support.steps_placeholder')"
                class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>

            <!-- Pièces jointes -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('support.attachments_label') }}
              </label>
              <input ref="fileInput" type="file" multiple @change="handleFiles" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.zip,.webp"
                class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:rounded-3 file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-blue-600 hover:file:bg-blue-100 dark:file:bg-blue-900/20 dark:file:text-blue-400" />
              <p class="mt-1 text-xs text-gray-400">{{ $t('support.attachment_hint') }}</p>
              <p v-if="errors['attachments.0']" class="mt-1 text-xs text-red-600">{{ errors['attachments.0'][0] }}</p>
            </div>

            <p v-if="successMessage" dusk="support-success"
              class="rounded-3 bg-green-50 px-4 py-3 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
              {{ successMessage }}
            </p>

            <button type="submit" :disabled="submitting" dusk="support-submit"
              class="w-full rounded-3 bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50 transition-colors">
              <span v-if="submitting" class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
              {{ submitting ? $t('support.submitting') : $t('support.submit') }}
            </button>
          </form>
        </div>

        <!-- Mes tickets -->
        <div class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
          <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ $t('support.my_tickets_title') }}</h2>

          <div v-if="loadingTickets" class="space-y-3">
            <div v-for="i in 3" :key="i" class="h-20 animate-pulse rounded-3 bg-gray-200 dark:bg-gray-800" />
          </div>

          <p v-else-if="tickets.length === 0" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
            {{ $t('support.no_tickets') }}
          </p>

          <div v-else ref="ticketsRef" class="space-y-3">
            <div v-for="ticket in tickets" :key="ticket.id" dusk="ticket-row"
              class="stagger-item cursor-pointer rounded-3 border border-gray-200 p-4 transition-all hover:border-blue-300 dark:border-gray-700 dark:hover:border-blue-600"
              :class="{ 'border-blue-400 bg-blue-50 dark:bg-blue-900/10': selectedTicket?.id === ticket.id }"
              @click="selectedTicket = selectedTicket?.id === ticket.id ? null : ticket">
              <div class="flex items-start justify-between gap-2">
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-mono text-gray-400">#{{ ticket.ticket_number }}</span>
                    <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ ticket.subject }}</p>
                  </div>
                  <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    {{ $t(`support.categories.${ticket.category}`) }}
                    <span v-if="ticket.sla_deadline"> · {{ $t('support.sla_label') }}: {{ formatDate(ticket.sla_deadline) }}</span>
                    <span v-if="ticket.sla_breached" class="ml-1 text-red-600 font-medium">⚠ {{ $t('support.sla_breached') }}</span>
                  </p>
                </div>
                <span :class="statusBadgeClass(ticket.status)"
                  class="shrink-0 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                  {{ $t(`support.statuses.${ticket.status}`) }}
                </span>
              </div>

              <!-- Réponses (expandable) -->
              <transition name="fade">
                <div v-if="selectedTicket?.id === ticket.id && ticket.replies?.length" class="mt-3 space-y-2 border-t border-gray-100 pt-3 dark:border-gray-700">
                  <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $t('support.replies_title') }}</p>
                  <div v-for="reply in ticket.replies" :key="reply.id" :class="[
                    'rounded-3 p-3 text-sm',
                    reply.is_admin_reply
                      ? 'bg-blue-50 text-blue-900 dark:bg-blue-900/20 dark:text-blue-300'
                      : 'bg-gray-50 text-gray-900 dark:bg-gray-800 dark:text-gray-200'
                  ]">
                    <p class="font-medium text-xs mb-1">{{ reply.author?.nom_complet }} · {{ formatDate(reply.created_at) }}</p>
                    <p class="whitespace-pre-line">{{ reply.body }}</p>
                  </div>
                </div>
              </transition>

              <!-- Pièces jointes -->
              <div v-if="selectedTicket?.id === ticket.id && ticket.attachments?.length" class="mt-2 flex flex-wrap gap-2">
                <a v-for="att in ticket.attachments" :key="att.id"
                  :href="`/api/support/attachments/${att.id}/download`"
                  class="inline-flex items-center gap-1 rounded-3 border border-gray-200 bg-white px-2.5 py-1 text-xs text-blue-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-blue-400"
                  @click.stop>
                  <PaperClipIcon class="h-3 w-3" />
                  {{ att.original_name }}
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </admin-layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { PaperClipIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/authStore'
import { useStagger } from '@/composables/useAnimations'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import AdminSupport from '@/pages/admin/AdminSupport.vue'
import api from '@/api/axios'

const { t } = useI18n()
const authStore = useAuthStore()
const { staggerRef: ticketsRef, applyStagger } = useStagger(50)

const isSuperAdmin = computed(() => authStore.user?.is_super_admin ?? false)

const form = ref({ category: '', subject: '', message: '', reproducibility: '', steps_to_reproduce: '' })
const attachments = ref([])
const fileInput = ref(null)
const submitting = ref(false)
const successMessage = ref('')
const errors = ref({})
const tickets = ref([])
const loadingTickets = ref(false)
const selectedTicket = ref(null)

const categories = computed(() => ({
  bug: t('support.categories.bug'),
  feature: t('support.categories.feature'),
  billing: t('support.categories.billing'),
  account: t('support.categories.account'),
  other: t('support.categories.other'),
}))

const reproducibilities = computed(() => ({
  always: t('support.reproducibilities.always'),
  sometimes: t('support.reproducibilities.sometimes'),
  rarely: t('support.reproducibilities.rarely'),
  not_reproducible: t('support.reproducibilities.not_reproducible'),
  na: t('support.reproducibilities.na'),
}))

const handleFiles = (e) => { attachments.value = Array.from(e.target.files) }

const statusBadgeClass = (status) => ({
  open: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
  in_progress: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
  resolved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
}[status] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400')

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })
}

const submitTicket = async () => {
  errors.value = {}
  successMessage.value = ''
  submitting.value = true
  try {
    const payload = new FormData()
    Object.entries(form.value).forEach(([k, v]) => { if (v) payload.append(k, v) })
    attachments.value.forEach((f) => payload.append('attachments[]', f))

    await api.post('/support', payload, { headers: { 'Content-Type': 'multipart/form-data' } })

    successMessage.value = t('support.success')
    form.value = { category: '', subject: '', message: '', reproducibility: '', steps_to_reproduce: '' }
    attachments.value = []
    if (fileInput.value) fileInput.value.value = ''
    await loadTickets()
  } catch (err) {
    if (err.response?.status === 422) errors.value = err.response.data.errors ?? {}
  } finally {
    submitting.value = false
  }
}

const loadTickets = async () => {
  loadingTickets.value = true
  try {
    const res = await api.get('/support')
    tickets.value = res.data.data?.data ?? []
    await nextTick()
    applyStagger()
  } catch { /* ignore */ } finally {
    loadingTickets.value = false
  }
}

import { nextTick } from 'vue'
onMounted(loadTickets)
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
