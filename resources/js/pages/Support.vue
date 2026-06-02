<!-- resources/js/pages/Support.vue -->
<template>
  <admin-layout>
    <page-breadcrumb :page-title="$t('support.page_title')" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('support.page_title') }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('support.page_subtitle') }}</p>
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Contact Form -->
        <div class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
          <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ $t('support.form_title') }}</h2>

          <form @submit.prevent="submitTicket" class="space-y-4">
            <!-- Category -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('support.category_label') }}
              </label>
              <select
                v-model="form.category"
                dusk="support-category"
                class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
              >
                <option value="">{{ $t('support.category_placeholder') }}</option>
                <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
              </select>
              <p v-if="errors.category" class="mt-1 text-xs text-red-600">{{ errors.category[0] }}</p>
            </div>

            <!-- Subject -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('support.subject_label') }}
              </label>
              <input
                v-model="form.subject"
                type="text"
                dusk="support-subject"
                :placeholder="$t('support.subject_placeholder')"
                class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
              />
              <p v-if="errors.subject" class="mt-1 text-xs text-red-600">{{ errors.subject[0] }}</p>
            </div>

            <!-- Message -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('support.message_label') }}
              </label>
              <textarea
                v-model="form.message"
                rows="5"
                dusk="support-message"
                :placeholder="$t('support.message_placeholder')"
                class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
              />
              <p v-if="errors.message" class="mt-1 text-xs text-red-600">{{ errors.message[0] }}</p>
            </div>

            <!-- Attachment -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('support.attachment_label') }}
              </label>
              <input
                ref="fileInput"
                type="file"
                @change="handleFile"
                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.zip"
                class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:rounded-3 file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-blue-600 hover:file:bg-blue-100 dark:file:bg-blue-900/20 dark:file:text-blue-400"
              />
              <p class="mt-1 text-xs text-gray-400">{{ $t('support.attachment_hint') }}</p>
              <p v-if="errors.attachment" class="mt-1 text-xs text-red-600">{{ errors.attachment[0] }}</p>
            </div>

            <!-- Success -->
            <p v-if="successMessage" class="rounded-3 bg-green-50 px-4 py-3 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
              {{ successMessage }}
            </p>

            <button
              type="submit"
              :disabled="submitting"
              dusk="support-submit"
              class="w-full rounded-3 bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <span v-if="submitting" class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent mr-2" />
              {{ submitting ? $t('support.submitting') : $t('support.submit') }}
            </button>
          </form>
        </div>

        <!-- My Tickets -->
        <div class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
          <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ $t('support.my_tickets_title') }}</h2>

          <div v-if="loadingTickets" class="space-y-3">
            <div v-for="i in 3" :key="i" class="h-16 animate-pulse rounded-3 bg-gray-200 dark:bg-gray-800" />
          </div>

          <div v-else-if="tickets.length === 0" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
            {{ $t('support.no_tickets') }}
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="ticket in tickets"
              :key="ticket.id"
              class="rounded-3 border border-gray-200 p-4 dark:border-gray-700"
            >
              <div class="flex items-start justify-between gap-2">
                <div class="min-w-0 flex-1">
                  <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ ticket.subject }}</p>
                  <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    {{ $t(`support.categories.${ticket.category}`) }} · #{{ ticket.id }}
                  </p>
                </div>
                <span :class="statusBadgeClass(ticket.status)" class="shrink-0 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                  {{ $t(`support.statuses.${ticket.status}`) }}
                </span>
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
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import api from '@/api/axios'

const { t } = useI18n()

const form = ref({ category: '', subject: '', message: '' })
const attachment = ref(null)
const fileInput = ref(null)
const submitting = ref(false)
const successMessage = ref('')
const errors = ref({})
const tickets = ref([])
const loadingTickets = ref(false)

const categories = computed(() => ({
  bug: t('support.categories.bug'),
  feature: t('support.categories.feature'),
  billing: t('support.categories.billing'),
  account: t('support.categories.account'),
  other: t('support.categories.other'),
}))

const handleFile = (e) => {
  attachment.value = e.target.files[0] ?? null
}

const statusBadgeClass = (status) => ({
  open: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
  in_progress: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
  resolved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
}[status] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400')

const submitTicket = async () => {
  errors.value = {}
  successMessage.value = ''
  submitting.value = true

  try {
    const payload = new FormData()
    payload.append('category', form.value.category)
    payload.append('subject', form.value.subject)
    payload.append('message', form.value.message)
    if (attachment.value) {
      payload.append('attachment', attachment.value)
    }

    await api.post('/support', payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    successMessage.value = t('support.success')
    form.value = { category: '', subject: '', message: '' }
    attachment.value = null
    if (fileInput.value) { fileInput.value.value = '' }
    await loadTickets()
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors ?? {}
    }
  } finally {
    submitting.value = false
  }
}

const loadTickets = async () => {
  loadingTickets.value = true
  try {
    const res = await api.get('/support')
    tickets.value = res.data.data?.data ?? res.data.data ?? []
  } catch {
    // Ignore
  } finally {
    loadingTickets.value = false
  }
}

onMounted(loadTickets)
</script>
