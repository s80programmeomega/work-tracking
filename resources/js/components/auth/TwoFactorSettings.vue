<!-- resources/js/components/auth/TwoFactorSettings.vue -->
<template>
  <div class="rounded-3 border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03] space-y-6">
    <div>
      <h4 class="text-base font-semibold text-gray-900 dark:text-white">
        {{ $t('auth.mfa.settings_title') }}
      </h4>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ $t('auth.mfa.settings_subtitle') }}
      </p>
    </div>

    <!-- TOTP Section -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-3 p-4 space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium text-gray-900 dark:text-white">
            {{ $t('auth.mfa.totp_enabled') }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
            {{ $t('auth.mfa.totp_enabled_desc') }}
          </p>
        </div>
        <span
          :class="[
            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
            isTotpConfirmed
              ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
              : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
          ]"
        >
          {{ isTotpConfirmed ? $t('auth.mfa.totp_confirmed') : $t('auth.mfa.totp_not_configured') }}
        </span>
      </div>

      <!-- QR setup flow (shown after enable, before confirm) -->
      <div v-if="showSetup" class="space-y-4 border-t border-gray-100 dark:border-gray-700 pt-4">
        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $t('auth.mfa.setup_qr_desc') }}</p>

        <div
          v-if="qrSvg"
          class="flex justify-center p-4 bg-white rounded-3 border border-gray-200 dark:border-gray-700"
          v-html="qrSvg"
        />
        <div v-else class="flex justify-center py-6">
          <div class="h-8 w-8 animate-spin rounded-full border-2 border-blue-600 border-t-transparent" />
        </div>

        <div v-if="setupKey">
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t('auth.mfa.setup_manual_key') }}</p>
          <code class="block text-xs font-mono bg-gray-100 dark:bg-gray-800 rounded px-3 py-2 break-all text-gray-900 dark:text-white">
            {{ setupKey }}
          </code>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ $t('auth.mfa.confirm_code_label') }}
          </label>
          <div class="flex gap-2">
            <input
              v-model="confirmCode"
              type="text"
              inputmode="numeric"
              pattern="[0-9]*"
              maxlength="6"
              :placeholder="$t('auth.mfa.code_placeholder')"
              class="flex-1 rounded-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
              @keyup.enter="confirmTotp"
            />
            <button
              @click="confirmTotp"
              :disabled="confirmLoading || confirmCode.length < 6"
              class="rounded-3 bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <span v-if="confirmLoading" class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent mr-1" />
              {{ $t('auth.mfa.confirm_btn') }}
            </button>
          </div>
          <p v-if="confirmError" class="mt-1 text-xs text-red-600">{{ confirmError }}</p>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button
          v-if="!isTotpConfirmed && !showSetup"
          @click="startTotpSetup"
          :disabled="actionLoading"
          class="rounded-3 bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 transition-colors"
        >
          {{ $t('auth.mfa.enable_totp') }}
        </button>
        <button
          v-if="isTotpConfirmed"
          @click="disableTotp"
          :disabled="actionLoading"
          class="rounded-3 border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-700 dark:bg-transparent dark:hover:bg-red-900/10 disabled:opacity-50 transition-colors"
        >
          {{ $t('auth.mfa.disable_totp') }}
        </button>
      </div>
    </div>

    <!-- Recovery Codes Section (only shown when TOTP is confirmed) -->
    <div v-if="isTotpConfirmed" class="border border-gray-200 dark:border-gray-700 rounded-3 p-4 space-y-3">
      <div>
        <p class="text-sm font-medium text-gray-900 dark:text-white">
          {{ $t('auth.mfa.recovery_codes_title') }}
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
          {{ $t('auth.mfa.recovery_codes_desc') }}
        </p>
      </div>

      <div v-if="recoveryCodes.length > 0" ref="codesRef" class="grid grid-cols-2 gap-1.5">
        <code
          v-for="code in recoveryCodes"
          :key="code"
          class="stagger-item text-xs font-mono bg-gray-100 dark:bg-gray-800 rounded px-2 py-1 text-gray-900 dark:text-white text-center"
        >
          {{ code }}
        </code>
      </div>

      <div class="flex gap-2">
        <button
          @click="loadRecoveryCodes"
          :disabled="codesLoading"
          class="rounded-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-transparent px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 disabled:opacity-50 transition-colors"
        >
          {{ recoveryCodes.length > 0 ? $t('auth.mfa.recovery_codes_title') : $t('auth.mfa.recovery_codes_title') }}
        </button>
        <button
          @click="regenerateCodes"
          :disabled="codesLoading"
          class="rounded-3 border border-orange-300 bg-white dark:bg-transparent px-4 py-2 text-sm font-medium text-orange-600 hover:bg-orange-50 dark:border-orange-700 dark:hover:bg-orange-900/10 disabled:opacity-50 transition-colors"
        >
          {{ $t('auth.mfa.regenerate_codes') }}
        </button>
      </div>
    </div>

    <!-- Email OTP Section -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-3 p-4 space-y-3">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium text-gray-900 dark:text-white">
            {{ $t('auth.mfa.email_otp_title') }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
            {{ $t('auth.mfa.email_otp_desc') }}
          </p>
        </div>
        <span
          :class="[
            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
            emailOtpEnabled
              ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
              : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
          ]"
        >
          {{ emailOtpEnabled ? $t('auth.mfa.totp_confirmed') : $t('auth.mfa.totp_not_configured') }}
        </span>
      </div>

      <p v-if="emailOtpError" class="text-xs text-red-600">{{ emailOtpError }}</p>

      <button
        v-if="!emailOtpEnabled"
        @click="toggleEmailOtp(true)"
        :disabled="!isTotpConfirmed || emailOtpLoading"
        :title="!isTotpConfirmed ? $t('auth.mfa.email_otp_requires_totp') : ''"
        class="rounded-3 bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        {{ $t('auth.mfa.email_otp_enable') }}
      </button>
      <button
        v-else
        @click="toggleEmailOtp(false)"
        :disabled="emailOtpLoading"
        class="rounded-3 border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-700 dark:bg-transparent dark:hover:bg-red-900/10 disabled:opacity-50 transition-colors"
      >
        {{ $t('auth.mfa.email_otp_disable') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/authStore'
import { useStagger } from '@/composables/useAnimations'
import api from '@/api/axios'

const { t } = useI18n()
const authStore = useAuthStore()
const { staggerRef: codesRef, applyStagger: staggerCodes } = useStagger(40)

const isTotpConfirmed = computed(() => !!authStore.user?.two_factor_confirmed_at)
const emailOtpEnabled = ref(authStore.user?.email_otp_enabled ?? false)

const showSetup = ref(false)
const qrSvg = ref('')
const setupKey = ref('')
const confirmCode = ref('')
const confirmError = ref('')
const confirmLoading = ref(false)
const actionLoading = ref(false)
const codesLoading = ref(false)
const emailOtpLoading = ref(false)
const emailOtpError = ref('')
const recoveryCodes = ref([])

const startTotpSetup = async () => {
  actionLoading.value = true
  try {
    await api.post('/user/two-factor-authentication')
    showSetup.value = true
    const qrRes = await api.get('/user/two-factor-qr-code')
    qrSvg.value = qrRes.data.svg
    const secRes = await api.get('/user/two-factor-secret-key')
    setupKey.value = secRes.data.secretKey ?? ''
  } catch {
    // QR laden mislukt — gebruiker kan handmatig invoeren
  } finally {
    actionLoading.value = false
  }
}

const confirmTotp = async () => {
  confirmError.value = ''
  confirmLoading.value = true
  try {
    await api.post('/user/confirmed-two-factor-authentication', { code: confirmCode.value })
    showSetup.value = false
    confirmCode.value = ''
    await authStore.fetchUser()
    emailOtpEnabled.value = authStore.user?.email_otp_enabled ?? false
    await loadRecoveryCodes()
  } catch (err) {
    confirmError.value = err.response?.data?.message ?? t('auth.mfa.invalid_code')
  } finally {
    confirmLoading.value = false
  }
}

const disableTotp = async () => {
  if (!confirm(t('auth.mfa.disable_totp') + '?')) { return }
  actionLoading.value = true
  try {
    await api.delete('/user/two-factor-authentication')
    recoveryCodes.value = []
    await authStore.fetchUser()
  } catch {
    // Ignore
  } finally {
    actionLoading.value = false
  }
}

const loadRecoveryCodes = async () => {
  codesLoading.value = true
  try {
    const res = await api.get('/user/two-factor-recovery-codes')
    recoveryCodes.value = res.data
    await nextTick()
    staggerCodes()
  } catch {
    // Ignore
  } finally {
    codesLoading.value = false
  }
}

const regenerateCodes = async () => {
  if (!confirm(t('auth.mfa.regenerate_warning'))) { return }
  codesLoading.value = true
  try {
    await api.post('/user/two-factor-recovery-codes')
    await loadRecoveryCodes()
  } catch {
    // Ignore
  } finally {
    codesLoading.value = false
  }
}

const toggleEmailOtp = async (enabled) => {
  emailOtpError.value = ''
  emailOtpLoading.value = true
  try {
    const res = await api.post('/api/auth/email-otp-toggle', { enabled })
    emailOtpEnabled.value = res.data.email_otp_enabled
  } catch (err) {
    emailOtpError.value = err.response?.data?.message ?? t('auth.mfa.email_otp_requires_totp')
  } finally {
    emailOtpLoading.value = false
  }
}

onMounted(() => {
  if (isTotpConfirmed.value) {
    loadRecoveryCodes()
  }
})
</script>
