<!-- resources\js\components\settings\SessionSettings.vue -->
<template>
    <div class="space-y-6">
        <div class="mb-6">
            <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                {{ $t('session_settings.title') }}
            </h4>
        </div>

        <!-- Délai d'inactivité -->
        <div class="p-5 border border-gray-200 rounded-3 dark:border-gray-800">
            <div class="mb-4">
                <h5 class="font-medium text-gray-800 dark:text-white/90">
                    {{ $t('session_settings.timeout_label') }}
                </h5>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('session_settings.timeout_desc') }}
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                <button
                    v-for="option in timeoutOptions"
                    :key="option.value"
                    :class="[
                        'flex flex-col items-center p-3 border-2 rounded-3 transition-colors cursor-pointer text-sm font-medium',
                        selectedTimeout === option.value
                            ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-400'
                            : 'border-gray-200 text-gray-700 hover:border-blue-300 dark:border-gray-700 dark:text-gray-300 dark:hover:border-blue-500'
                    ]"
                    @click="setSessionTimeout(option.value)"
                >
                    {{ option.label }}
                </button>
            </div>

            <div v-if="showCustomInput" class="flex gap-3 mt-4">
                <input
                    type="number"
                    v-model="customTimeout"
                    min="5"
                    max="480"
                    class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-3 focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    :placeholder="$t('session_settings.timeout_label')"
                />
                <button
                    @click="applyCustomTimeout"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-3 hover:bg-blue-700"
                >
                    {{ $t('session_settings.btn_apply') }}
                </button>
            </div>
            <button
                v-if="!showCustomInput"
                @click="showCustomInput = true"
                class="mt-3 text-sm text-blue-600 hover:underline dark:text-blue-400"
            >
                {{ $t('session_settings.btn_customize') }}
            </button>
        </div>

        <!-- Informations de session courante -->
        <div class="p-5 border border-gray-200 rounded-3 dark:border-gray-800">
            <h5 class="mb-4 font-medium text-gray-800 dark:text-white/90">
                {{ $t('session_settings.info_title') }}
            </h5>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $t('session_settings.info_logout_in') }}
                    </p>
                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">
                        {{ timeUntilLogout }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $t('session_settings.info_last_activity') }}
                    </p>
                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">
                        {{ formatTime(lastActivity) }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $t('session_settings.info_active_since') }}
                    </p>
                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">
                        {{ formatDuration(sessionDuration) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Sessions actives -->
        <div class="p-5 border border-gray-200 rounded-3 dark:border-gray-800">
            <div class="mb-4">
                <h5 class="font-medium text-gray-800 dark:text-white/90">
                    {{ $t('session_settings.active_sessions_title') }}
                </h5>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('session_settings.active_sessions_desc') }}
                </p>
            </div>

            <div v-if="sessionsLoading" class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ $t('session_settings.sessions_loading') }}
            </div>

            <div v-else-if="sessions.length === 0" class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ $t('session_settings.sessions_empty') }}
            </div>

            <ul v-else ref="staggerRef" class="space-y-3">
                <li
                    v-for="session in sessions"
                    :key="session.id"
                    class="stagger-item flex items-center justify-between p-3 border border-gray-100 rounded-3 dark:border-gray-700"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="flex items-center justify-center w-9 h-9 rounded-full bg-blue-50 dark:bg-blue-900/30 shrink-0">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-2" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-medium text-gray-800 dark:text-white/90">
                                    {{ $t('session_settings.this_device') }}
                                </span>
                                <span
                                    v-if="session.is_current"
                                    class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 rounded-full dark:bg-green-900/30 dark:text-green-400"
                                >
                                    ● {{ $t('common.active') }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $t('session_settings.session_created') }}
                                {{ formatDate(session.created_at) }}
                                <span v-if="session.last_used_at">
                                    · {{ $t('session_settings.session_last_used') }}
                                    {{ formatDate(session.last_used_at) }}
                                </span>
                                <span v-else>
                                    · {{ $t('session_settings.session_never_used') }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <button
                        @click="revokeSession(session)"
                        class="ml-3 shrink-0 px-3 py-1.5 text-xs font-medium text-red-600 border border-red-200 rounded-3 hover:bg-red-50 dark:text-red-400 dark:border-red-800 dark:hover:bg-red-900/20"
                    >
                        {{ $t('session_settings.session_revoke') }}
                    </button>
                </li>
            </ul>
        </div>

        <!-- Actions -->
        <div class="flex flex-col gap-3 pt-4 border-t border-gray-200 sm:flex-row dark:border-gray-800">
            <button
                @click="refreshSession"
                class="flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-3 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800 dark:hover:bg-blue-900/40"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                {{ $t('session_settings.btn_refresh') }}
            </button>

            <button
                @click="logoutAll"
                class="flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-red-600 bg-red-50 border border-red-200 rounded-3 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800 dark:hover:bg-red-900/40"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                {{ $t('session_settings.btn_logout_all') }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/authStore'
import { useStagger } from '@/composables/useAnimations'

const { t } = useI18n()
const authStore = useAuthStore()
const { staggerRef, applyStagger } = useStagger()

const showCustomInput = ref(false)
const customTimeout = ref(60)
const timerInterval = ref(null)
const sessions = ref([])
const sessionsLoading = ref(true)

const timeoutOptions = computed(() => [
    { label: t('session_settings.opt_15min'), value: 15 },
    { label: t('session_settings.opt_30min'), value: 30 },
    { label: t('session_settings.opt_1h'), value: 60 },
    { label: t('session_settings.opt_2h'), value: 120 },
    { label: t('session_settings.opt_4h'), value: 240 },
    { label: t('session_settings.opt_never'), value: 0 },
])

const selectedTimeout = computed(() => authStore.getTimeoutDuration())
const lastActivity = computed(() => authStore.lastActivity)

const timeUntilLogout = computed(() => {
    const now = Date.now()
    const inactiveTime = now - authStore.lastActivity
    const timeLeft = authStore.inactivityTimeout - inactiveTime

    if (timeLeft <= 0) { return t('session_settings.now') }
    if (timeLeft < 60000) { return `${Math.floor(timeLeft / 1000)} sec` }

    return `${Math.floor(timeLeft / 60000)} min`
})

const sessionDuration = computed(() => {
    const loginTime = localStorage.getItem('login_time')

    return loginTime ? Date.now() - parseInt(loginTime) : 0
})

const setSessionTimeout = (minutes) => {
    authStore.setTimeoutDuration(minutes)
    showCustomInput.value = false
}

const applyCustomTimeout = () => {
    if (customTimeout.value >= 5 && customTimeout.value <= 480) {
        setSessionTimeout(customTimeout.value)
    }
}

const refreshSession = () => {
    authStore.resetInactivityTimer()
    authStore.refreshToken().catch(console.error)
}

const revokeSession = async (session) => {
    if (confirm(t('session_settings.confirm_logout_all'))) {
        await authStore.logoutAllDevices()
    }
}

const logoutAll = async () => {
    if (confirm(t('session_settings.confirm_logout_all'))) {
        await authStore.logoutAllDevices()
    }
}

const loadSessions = async () => {
    sessionsLoading.value = true
    sessions.value = await authStore.fetchSessions()
    sessionsLoading.value = false
    applyStagger()
}

const formatTime = (timestamp) => {
    return new Date(timestamp).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

const formatDate = (isoString) => {
    return new Date(isoString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const formatDuration = (ms) => {
    if (!ms) { return 'N/A' }

    const hours = Math.floor(ms / 3600000)
    const minutes = Math.floor((ms % 3600000) / 60000)

    return hours > 0 ? `${hours}h ${minutes}min` : `${minutes} min`
}

onMounted(() => {
    if (!localStorage.getItem('login_time')) {
        localStorage.setItem('login_time', Date.now().toString())
    }

    timerInterval.value = setInterval(() => {}, 1000)
    loadSessions()
})

onUnmounted(() => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value)
    }
})
</script>
