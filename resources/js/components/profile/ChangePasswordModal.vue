<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    @click.self="closeModal"
  >
    <div class="w-full max-w-md rounded-3 bg-white p-6 shadow-xl dark:bg-gray-900">

      <!-- En-tête -->
      <div class="mb-5 flex items-center justify-between">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
          {{ $t('user_profile.change_password_title') }}
        </h3>
        <button
          @click="closeModal"
          class="rounded-full p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"
        >
          <XMarkIcon class="h-5 w-5" />
        </button>
      </div>

      <form @submit.prevent="changePassword" class="space-y-4">

        <!-- Mot de passe actuel -->
        <div>
          <label for="current_password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('user_profile.current_password') }} <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input
              id="current_password"
              v-model="form.current_password"
              :type="showCurrentPassword ? 'text' : 'password'"
              class="w-full rounded-3 border border-gray-300 bg-white px-4 py-2.5 pr-10 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
              :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': errors.current_password }"
              :placeholder="$t('user_profile.current_password_placeholder')"
              required
            />
            <button
              type="button"
              @click="showCurrentPassword = !showCurrentPassword"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <EyeSlashIcon v-if="showCurrentPassword" class="h-4 w-4" />
              <EyeIcon v-else class="h-4 w-4" />
            </button>
          </div>
          <p v-if="errors.current_password" class="mt-1 text-xs text-red-600">
            {{ errors.current_password[0] }}
          </p>
        </div>

        <!-- Nouveau mot de passe -->
        <div>
          <label for="new_password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('user_profile.new_password') }} <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input
              id="new_password"
              v-model="form.new_password"
              :type="showNewPassword ? 'text' : 'password'"
              class="w-full rounded-3 border border-gray-300 bg-white px-4 py-2.5 pr-10 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
              :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': errors.new_password }"
              :placeholder="$t('user_profile.new_password_placeholder')"
              minlength="8"
              required
            />
            <button
              type="button"
              @click="showNewPassword = !showNewPassword"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <EyeSlashIcon v-if="showNewPassword" class="h-4 w-4" />
              <EyeIcon v-else class="h-4 w-4" />
            </button>
          </div>
          <p v-if="errors.new_password" class="mt-1 text-xs text-red-600">
            {{ errors.new_password[0] }}
          </p>

          <!-- Force du mot de passe -->
          <div v-if="form.new_password" class="mt-2 space-y-1.5">
            <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
              <div
                class="h-full rounded-full transition-all duration-300"
                :class="strengthBarClass"
                :style="{ width: passwordStrength + '%' }"
              />
            </div>
            <p class="text-xs" :class="strengthTextClass">{{ strengthLabel }}</p>
            <ul class="grid grid-cols-2 gap-x-4 gap-y-0.5 text-xs">
              <li :class="passwordRules.length ? 'text-green-600 dark:text-green-400' : 'text-gray-400'">
                {{ passwordRules.length ? '✓' : '○' }} {{ $t('user_profile.rule_length') }}
              </li>
              <li :class="passwordRules.lower ? 'text-green-600 dark:text-green-400' : 'text-gray-400'">
                {{ passwordRules.lower ? '✓' : '○' }} {{ $t('user_profile.rule_lower') }}
              </li>
              <li :class="passwordRules.upper ? 'text-green-600 dark:text-green-400' : 'text-gray-400'">
                {{ passwordRules.upper ? '✓' : '○' }} {{ $t('user_profile.rule_upper') }}
              </li>
              <li :class="passwordRules.number ? 'text-green-600 dark:text-green-400' : 'text-gray-400'">
                {{ passwordRules.number ? '✓' : '○' }} {{ $t('user_profile.rule_number') }}
              </li>
              <li :class="passwordRules.symbol ? 'text-green-600 dark:text-green-400' : 'text-gray-400'">
                {{ passwordRules.symbol ? '✓' : '○' }} {{ $t('user_profile.rule_symbol') }}
              </li>
            </ul>
          </div>
        </div>

        <!-- Confirmation -->
        <div>
          <label for="new_password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('user_profile.confirm_new_password') }} <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input
              id="new_password_confirmation"
              v-model="form.new_password_confirmation"
              :type="showConfirmPassword ? 'text' : 'password'"
              class="w-full rounded-3 border border-gray-300 bg-white px-4 py-2.5 pr-10 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
              :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': passwordMismatch }"
              :placeholder="$t('user_profile.confirm_new_password_placeholder')"
              required
            />
            <button
              type="button"
              @click="showConfirmPassword = !showConfirmPassword"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <EyeSlashIcon v-if="showConfirmPassword" class="h-4 w-4" />
              <EyeIcon v-else class="h-4 w-4" />
            </button>
          </div>
          <p v-if="passwordMismatch" class="mt-1 text-xs text-red-600">
            {{ $t('user_profile.passwords_mismatch') }}
          </p>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-2">
          <button
            type="button"
            @click="closeModal"
            class="px-4 py-2 text-sm text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
          >
            {{ $t('user_profile.cancel') }}
          </button>
          <button
            type="submit"
            dusk="change-password-submit"
            :disabled="!canSubmit || loading"
            class="flex items-center gap-2 rounded-3 bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
          >
            <svg v-if="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            {{ $t('user_profile.change_password_btn') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { EyeIcon, EyeSlashIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'
import api from '@/api/axios'

export default {
  name: 'ChangePasswordModal',

  components: { EyeIcon, EyeSlashIcon, XMarkIcon },

  props: {
    show: { type: Boolean, default: false },
  },

  emits: ['close'],

  setup() {
    const toast = useToast()
    return { toast }
  },

  data() {
    return {
      form: { current_password: '', new_password: '', new_password_confirmation: '' },
      showCurrentPassword: false,
      showNewPassword: false,
      showConfirmPassword: false,
      loading: false,
      errors: {},
    }
  },

  computed: {
    passwordMismatch() {
      return this.form.new_password_confirmation &&
        this.form.new_password !== this.form.new_password_confirmation
    },
    passwordRules() {
      const p = this.form.new_password
      return {
        length:  p.length >= 8,
        lower:   /[a-z]/.test(p),
        upper:   /[A-Z]/.test(p),
        number:  /[0-9]/.test(p),
        symbol:  /[^A-Za-z0-9]/.test(p),
      }
    },
    passwordStrength() {
      if (!this.form.new_password) return 0
      const passed = Object.values(this.passwordRules).filter(Boolean).length
      return (passed / 5) * 100
    },
    strengthBarClass() {
      if (this.passwordStrength < 40) return 'bg-red-500'
      if (this.passwordStrength < 80) return 'bg-amber-500'
      if (this.passwordStrength < 100) return 'bg-blue-500'
      return 'bg-green-500'
    },
    strengthTextClass() {
      if (this.passwordStrength < 40) return 'text-red-600 dark:text-red-400'
      if (this.passwordStrength < 80) return 'text-amber-600 dark:text-amber-400'
      if (this.passwordStrength < 100) return 'text-blue-600 dark:text-blue-400'
      return 'text-green-600 dark:text-green-400'
    },
    strengthLabel() {
      if (this.passwordStrength < 40) return this.$t('user_profile.strength_weak')
      if (this.passwordStrength < 80) return this.$t('user_profile.strength_fair')
      if (this.passwordStrength < 100) return this.$t('user_profile.strength_good')
      return this.$t('user_profile.strength_strong')
    },
    canSubmit() {
      const r = this.passwordRules
      return this.form.current_password &&
        this.form.new_password &&
        this.form.new_password_confirmation &&
        !this.passwordMismatch &&
        r.length && r.lower && r.upper && r.number && r.symbol
    },
  },

  watch: {
    show(val) {
      if (val) this.resetForm()
    },
  },

  methods: {
    resetForm() {
      this.form = { current_password: '', new_password: '', new_password_confirmation: '' }
      this.showCurrentPassword = false
      this.showNewPassword = false
      this.showConfirmPassword = false
      this.errors = {}
    },
    closeModal() {
      this.resetForm()
      this.$emit('close')
    },
    async changePassword() {
      if (!this.canSubmit) return
      this.loading = true
      this.errors = {}
      try {
        const response = await api.post('/users/change-password', this.form)
        this.toast.success(response.data.message)
        this.closeModal()
      } catch (error) {
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors
        } else if (error.response?.status === 400) {
          this.toast.error(error.response.data.message)
        } else {
          this.toast.error(this.$t('user_profile.change_password_error'))
        }
      } finally {
        this.loading = false
      }
    },
  },
}
</script>
