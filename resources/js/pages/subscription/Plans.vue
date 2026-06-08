<!-- Abonnements (Phase 8) — catalogue des plans + sélection + état courant. -->
<template>
  <AdminLayout>
    <div class="space-y-6">

      <!-- En-tête -->
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('subscription_plans.title') }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('subscription_plans.subtitle') }}</p>
      </div>

      <!-- État courant -->
      <div v-if="current" class="rounded-3 border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/3">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('subscription_plans.current_plan') }}</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ current.plan ? localized(current.plan, 'nom') : '—' }}
              <span class="ml-2 align-middle text-xs font-medium" :class="statusClass(current.subscription_status)">
                {{ $t(`subscription_plans.status.${current.subscription_status}`, current.subscription_status) }}
              </span>
            </p>
          </div>
          <div v-if="current.subscription_ends_at" class="text-sm text-gray-500 dark:text-gray-400">
            {{ $t('subscription_plans.renews_on', { date: formatDate(current.subscription_ends_at) }) }}
          </div>
        </div>
      </div>

      <!-- Grille de plans -->
      <div v-if="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ $t('subscription_plans.loading') }}</div>
      <div v-else ref="plansRef" class="grid grid-cols-1 gap-5 md:grid-cols-3">
        <div
          v-for="plan in plans"
          :key="plan.id"
          class="stagger-item flex flex-col rounded-3 border bg-white p-6 dark:bg-white/3"
          :class="isCurrent(plan)
            ? 'border-blue-500 ring-1 ring-blue-500/30 dark:border-blue-600'
            : 'border-gray-200 dark:border-gray-800'"
        >
          <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ localized(plan, 'nom') }}</h3>
          <p class="mt-1 min-h-[2.5rem] text-sm text-gray-500 dark:text-gray-400">{{ localized(plan, 'description') }}</p>

          <div class="mt-4">
            <span class="text-3xl font-bold text-gray-900 dark:text-white">
              {{ plan.is_free ? $t('subscription_plans.free') : formatPrice(plan) }}
            </span>
            <span v-if="!plan.is_free" class="text-sm text-gray-500 dark:text-gray-400">
              / {{ $t(`subscription_plans.period.${plan.billing_period}`, plan.billing_period) }}
            </span>
          </div>

          <ul class="mt-4 flex-1 space-y-2 text-sm text-gray-600 dark:text-gray-300">
            <li v-for="(feat, i) in (plan.features || [])" :key="i" class="flex items-start gap-2">
              <span class="mt-0.5 text-green-500">✓</span>{{ feat }}
            </li>
          </ul>

          <button
            :disabled="isCurrent(plan) || selecting"
            @click="selectPlan(plan)"
            class="mt-6 rounded-3 px-4 py-2 text-sm font-medium transition-colors disabled:opacity-50"
            :class="isCurrent(plan)
              ? 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400'
              : 'bg-blue-600 text-white hover:bg-blue-700'"
          >
            {{ isCurrent(plan) ? $t('subscription_plans.current') : (plan.is_free ? $t('subscription_plans.choose_free') : $t('subscription_plans.choose')) }}
          </button>
        </div>
      </div>

      <p v-if="message" class="rounded-3 border border-green-200 bg-green-50 p-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
        {{ message }}
      </p>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useStagger } from '@/composables/useAnimations'
import api from '@/api/axios'
import AdminLayout from '@/components/layout/AdminLayout.vue'

const { t, locale } = useI18n()
const { staggerRef: plansRef, applyStagger } = useStagger(60)

const plans = ref([])
const current = ref(null)
const loading = ref(false)
const selecting = ref(false)
const message = ref('')

const localized = (obj, field) => {
  if (!obj) return ''
  return obj[`${field}_${locale.value}`] ?? obj[`${field}_fr`] ?? ''
}

const formatPrice = (plan) => {
  return new Intl.NumberFormat(locale.value, { style: 'currency', currency: plan.currency || 'XAF', maximumFractionDigits: 0 }).format(plan.price)
}

const formatDate = (iso) => {
  try { return new Date(iso).toLocaleDateString(locale.value, { day: 'numeric', month: 'long', year: 'numeric' }) }
  catch { return iso }
}

const isCurrent = (plan) => current.value?.plan?.id === plan.id

const statusClass = (status) => ({
  active: 'text-green-600 dark:text-green-400',
  trial: 'text-blue-600 dark:text-blue-400',
  free: 'text-gray-500 dark:text-gray-400',
  pending: 'text-amber-600 dark:text-amber-400',
  lapsed: 'text-orange-600 dark:text-orange-400',
  locked: 'text-red-600 dark:text-red-400',
}[status] ?? 'text-gray-500')

const load = async () => {
  loading.value = true
  try {
    const [plansRes, curRes] = await Promise.all([
      api.get('/subscription/plans'),
      api.get('/subscription/current'),
    ])
    plans.value = plansRes.data.plans ?? []
    current.value = curRes.data.subscription ?? null
    await applyStagger()
  } finally {
    loading.value = false
  }
}

const selectPlan = async (plan) => {
  selecting.value = true
  message.value = ''
  try {
    const { data } = await api.post('/subscription/select', { plan_id: plan.id })
    current.value = data.subscription ?? current.value
    message.value = data.message ?? t('subscription_plans.selected')
  } catch (e) {
    message.value = e.response?.data?.message ?? t('subscription_plans.error')
  } finally {
    selecting.value = false
  }
}

onMounted(load)
</script>
