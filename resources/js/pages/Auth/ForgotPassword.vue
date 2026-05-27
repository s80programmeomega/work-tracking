<template>
  <FullScreenLayout>
    <div class="relative p-6 bg-white z-1 dark:bg-gray-900 sm:p-0">
      <div class="relative flex flex-col justify-center w-full h-screen lg:flex-row dark:bg-gray-900">
        <div class="flex flex-col flex-1 w-full lg:w-1/2">
          <div class="w-full max-w-md pt-10 mx-auto">
            <router-link to="/signin"
              class="inline-flex items-center text-sm text-gray-500 transition-colors hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
              <svg class="stroke-current" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"
                fill="none">
                <path d="M12.7083 5L7.5 10.2083L12.7083 15.4167" stroke="" stroke-width="1.5" stroke-linecap="round"
                  stroke-linejoin="round" />
              </svg>
              Back to sign in
            </router-link>
          </div>
          <div class="flex flex-col justify-center flex-1 w-full max-w-md mx-auto">
            <div>
              <div class="mb-5 sm:mb-8">
                <h1 class="mb-2 font-semibold text-gray-800 text-title-sm dark:text-white/90 sm:text-title-md">
                  Forgot Password?
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Enter your email and we'll send you a reset link
                </p>
              </div>

              <!-- Success Message -->
              <div v-if="success" class="mb-5 rounded-3 bg-success-50 p-4 dark:bg-success-500/10">
                <p class="text-sm text-success-700 dark:text-success-400">
                  Password reset link sent! Check your email.
                </p>
              </div>

              <!-- Error Alert -->
              <div v-if="error" class="mb-5 rounded-3 bg-error-50 p-4 dark:bg-error-500/10">
                <p class="text-sm text-error-700 dark:text-error-400">{{ error }}</p>
              </div>

              <form @submit.prevent="handleSubmit">
                <div class="space-y-5">
                  <!-- Email -->
                  <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                      Email<span class="text-error-500">*</span>
                    </label>
                    <input v-model="email" type="email" id="email" name="email" placeholder="info@gmail.com"
                      :class="validationErrors.email ? 'border-error-500' : 'border-gray-300'"
                      class="dark:bg-dark-900 h-11 w-full rounded-3 border bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" />
                    <p v-if="validationErrors.email" class="mt-1 text-sm text-error-500">
                      {{ validationErrors.email[0] }}
                    </p>
                  </div>

                  <!-- Button -->
                  <div>
                    <button type="submit" :disabled="loading || success"
                      class="flex items-center justify-center w-full px-4 py-3 text-sm font-medium text-white transition rounded-3 bg-brand-500 hover:bg-brand-600 disabled:opacity-50 disabled:cursor-not-allowed">
                      <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                      </svg>
                      {{ loading ? 'Sending...' : 'Send Reset Link' }}
                    </button>
                  </div>
                </div>
              </form>

              <div class="mt-5">
                <p class="text-sm font-normal text-center text-gray-700 dark:text-gray-400 sm:text-start">
                  Remember your password?
                  <router-link to="/signin" class="text-brand-500 hover:text-brand-600 dark:text-brand-400">Sign
                    In</router-link>
                </p>
              </div>
            </div>
          </div>
        </div>
        <div class="relative items-center hidden w-full h-full lg:w-1/2 bg-brand-950 dark:bg-white/5 lg:grid">
          <div class="flex items-center justify-center z-1">
            <common-grid-shape />
            <div class="flex flex-col items-center max-w-xs">
              <router-link to="/" class="block mb-4">
                <img width="200" height="180" :src="LogoDark" alt="Uptiimum Work Tracking" class="mx-auto rounded-3" />
              </router-link>
              <p class="text-center text-gray-400 dark:text-white/60">
                Free and Open-Source Tailwind CSS Admin Dashboard Template
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </FullScreenLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import CommonGridShape from '@/components/common/CommonGridShape.vue'
import FullScreenLayout from '@/components/layout/FullScreenLayout.vue'
import api from '@/api/axios'
// import LogoDark from '@/assets/images/logo/Logo-dark.jpg'

const LogoDark = new URL('@/assets/images/logo/Logo-dark.jpg', import.meta.url).href
const email = ref('')
const loading = ref(false)
const error = ref(null)
const success = ref(false)
const validationErrors = ref({})

const handleSubmit = async () => {
  validationErrors.value = {}
  error.value = null
  success.value = false
  loading.value = true

  try {
    await api.post('/auth/forgot-password', { email: email.value })
    success.value = true
  } catch (err) {
    if (err.response?.data?.errors) {
      validationErrors.value = err.response.data.errors
    } else {
      error.value = err.response?.data?.message || 'Failed to send reset link'
    }
  } finally {
    loading.value = false
  }
}
</script>
