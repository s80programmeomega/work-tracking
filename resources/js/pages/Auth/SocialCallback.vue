<!-- resources/js/pages/Auth/SocialCallback.vue -->
<!-- Page intermédiaire : reçoit le token OAuth depuis le callback backend et finalise la session. -->
<template>
  <div class="flex h-screen items-center justify-center bg-gray-50 dark:bg-gray-900">
    <div class="text-center">
      <div
        v-if="error"
        class="space-y-4"
      >
        <p class="text-sm text-red-600 dark:text-red-400">{{ errorMessage }}</p>
        <router-link
          to="/signin"
          class="text-sm text-blue-600 hover:underline dark:text-blue-400"
        >
          {{ $t('auth.signin') }}
        </router-link>
      </div>
      <div
        v-else
        class="flex flex-col items-center gap-3"
      >
        <div class="h-8 w-8 animate-spin rounded-full border-2 border-blue-600 border-t-transparent" />
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('auth.signing_in') }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/authStore'
import api from '@/api/axios'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const error = ref(false)
const errorMessage = ref('')

onMounted(async () => {
  const errorParam = route.query.error as string | undefined
  if (errorParam) {
    error.value = true
    errorMessage.value = errorParam === 'account_inactive'
      ? t('auth.mfa.challenge_expired') // réutilise "session expirée" pour compte inactif
      : t('auth.login_failed')
    return
  }

  const token = route.query.token as string | undefined
  const expiresAt = route.query.expires_at as string | undefined

  if (!token) {
    error.value = true
    errorMessage.value = t('auth.login_failed')
    return
  }

  // Stocke le token et récupère le profil complet
  authStore.token = token
  authStore.isAuthenticated = true
  authStore.tokenExpiry = expiresAt ? new Date(expiresAt).getTime() : null
  localStorage.setItem('auth_token', token)
  authStore.setAxiosToken(token)

  try {
    await authStore.fetchUser()
    if (authStore.user) {
      localStorage.setItem('user', JSON.stringify(authStore.user))
      localStorage.setItem('user_language', authStore.user.language || 'fr')
      if (authStore.user.language) authStore.setLanguage(authStore.user.language)
    }
    if (authStore.tokenExpiry) authStore.startTokenAutoRefresh()

    const invitationToken = route.query.invitation as string | undefined
    router.replace(invitationToken ? `/accept-invitation/${invitationToken}` : '/')
  } catch {
    error.value = true
    errorMessage.value = t('auth.login_failed')
    authStore.token = null
    authStore.isAuthenticated = false
    localStorage.removeItem('auth_token')
  }
})
</script>
