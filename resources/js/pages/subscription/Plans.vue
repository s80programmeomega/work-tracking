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

          <!-- Limites effectives du plan (source de vérité : colonnes du plan). -->
          <ul class="mt-4 flex-1 space-y-2 text-sm text-gray-700 dark:text-gray-200">
            <li class="flex items-start gap-2"><span class="mt-0.5 text-green-500">✓</span>{{ lim(plan.max_members) }} {{ $t('subscription_plans.limit_members') }}</li>
            <li class="flex items-start gap-2"><span class="mt-0.5 text-green-500">✓</span>{{ lim(plan.max_storage_mb) }} {{ $t('subscription_plans.limit_storage') }}</li>
            <li class="flex items-start gap-2"><span class="mt-0.5 text-green-500">✓</span>{{ lim(plan.max_file_size_mb) }} {{ $t('subscription_plans.limit_file') }}</li>
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

      <!-- Modale de paiement (plan payant) -->
      <div v-if="pay.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="closePay">
        <div class="w-full max-w-md rounded-3 border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-900">
          <h2 class="mb-1 text-lg font-semibold text-gray-900 dark:text-white">
            {{ $t('subscription_plans.payment.title') }} — {{ localized(pay.plan, 'nom') }}
          </h2>
          <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">{{ formatPrice(pay.plan) }}</p>

          <!-- En attente de validation (flux MTN) -->
          <div v-if="pay.waiting" class="space-y-3 text-center">
            <p class="text-sm text-gray-700 dark:text-gray-200">{{ $t('subscription_plans.payment.waiting') }}</p>
            <p v-if="pay.error" class="text-sm text-red-600">{{ pay.error }}</p>
            <button @click="closePay" class="rounded-3 border border-gray-300 px-4 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">{{ $t('common.cancel') }}</button>
          </div>

          <!-- Formulaire fournisseur + téléphone -->
          <form v-else class="space-y-3" @submit.prevent="startPayment">
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ $t('subscription_plans.payment.provider') }}</label>
              <select v-model="pay.provider" class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="mtn_momo">MTN MoMo</option>
                <option value="orange_money">Orange Money</option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ $t('subscription_plans.payment.phone') }}</label>
              <input v-model="pay.phone" required placeholder="2376XXXXXXXX" class="w-full rounded-3 border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>
            <p v-if="pay.error" class="text-sm text-red-600">{{ pay.error }}</p>
            <div class="flex items-center justify-end gap-3 pt-1">
              <button type="button" @click="closePay" class="rounded-3 border border-gray-300 px-4 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">{{ $t('common.cancel') }}</button>
              <button type="submit" :disabled="pay.loading" class="rounded-3 bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">{{ $t('subscription_plans.payment.pay') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { useStagger } from '@/composables/useAnimations'
import api from '@/api/axios'
import AdminLayout from '@/components/layout/AdminLayout.vue'

const { t, locale } = useI18n()
const route = useRoute()
const { staggerRef: plansRef, applyStagger } = useStagger(60)

const plans = ref([])
const current = ref(null)
const loading = ref(false)
const selecting = ref(false)
const message = ref('')

// Modale de paiement (plans payants).
const pay = ref({ open: false, plan: null, provider: 'mtn_momo', phone: '', loading: false, error: '', waiting: false })
let pollTimer = null

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

// -1 = illimité (sentinelle partagée avec le backend / la page admin).
const lim = (v) => v === -1 ? t('subscription_plans.unlimited') : v

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

// Plan gratuit → application immédiate ; plan payant → ouvre la modale de paiement.
const selectPlan = async (plan) => {
  if (plan.is_free) {
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
    return
  }
  pay.value = { open: true, plan, provider: 'mtn_momo', phone: '', loading: false, error: '', waiting: false }
}

// Lance le paiement : MTN = push + polling ; Orange = redirection web.
const startPayment = async () => {
  pay.value.loading = true
  pay.value.error = ''
  try {
    const { data } = await api.post('/payment/initiate', {
      plan_id: pay.value.plan.id,
      provider: pay.value.provider,
      payer_phone: pay.value.phone,
    })

    if (data.redirect_url) {
      // Orange : on quitte l'app vers la page de paiement Orange.
      window.location.href = data.redirect_url
      return
    }

    // MTN : la collecte est lancée, on attend la validation sur le téléphone.
    pay.value.waiting = true
    pollStatus(data.payment.reference)
  } catch (e) {
    pay.value.error = e.response?.data?.message ?? t('subscription_plans.payment.error')
  } finally {
    pay.value.loading = false
  }
}

// Interroge l'état du paiement toutes les 4 s jusqu'à résolution (max ~2 min).
const pollStatus = (reference) => {
  let tries = 0
  pollTimer = setInterval(async () => {
    tries += 1
    try {
      const { data } = await api.get(`/payment/${reference}/status`)
      const status = data.payment?.status
      if (status === 'succeeded') {
        clearInterval(pollTimer)
        pay.value.open = false
        message.value = t('subscription_plans.payment.success')
        await load()
      } else if (status === 'failed' || status === 'expired' || tries > 30) {
        clearInterval(pollTimer)
        pay.value.waiting = false
        pay.value.error = t('subscription_plans.payment.failed')
      }
    } catch {
      // on réessaiera au prochain tick
    }
  }, 4000)
}

const closePay = () => {
  if (pollTimer) { clearInterval(pollTimer) }
  pay.value.open = false
}

onBeforeUnmount(() => { if (pollTimer) { clearInterval(pollTimer) } })

onMounted(async () => {
  await load()
  // Retour depuis Orange : ?payment=return → on rafraîchit l'état d'abonnement.
  if (route.query.payment === 'return') {
    message.value = t('subscription_plans.payment.checking')
    await load()
  } else if (route.query.payment === 'cancel') {
    message.value = t('subscription_plans.payment.cancelled')
  }
})
</script>
