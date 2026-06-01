<!-- resources\js\components\settings\SessionSettings.vue -->
<template>
    <div class="session-settings">
        <h3 class="settings-title">{{ $t('session_settings.title') }}</h3>

        <div class="settings-group">
            <label class="setting-label">
                <span class="label-text">{{ $t('session_settings.timeout_label') }}</span>
                <span class="label-description">
                    {{ $t('session_settings.timeout_desc') }}
                </span>
            </label>
            
            <div class="timeout-options">
                <button 
                    v-for="option in timeoutOptions"
                    :key="option.value"
                    :class="['timeout-btn', { active: selectedTimeout === option.value }]"
                    @click="setTimeout(option.value)"
                >
                    <span class="timeout-label">{{ option.label }}</span>
                    <span class="timeout-value">{{ option.value }} min</span>
                </button>
            </div>
            
            <div class="custom-timeout" v-if="showCustomInput">
                <input
                    type="number"
                    v-model="customTimeout"
                    min="5"
                    max="480"
                    placeholder="Durée en minutes"
                    class="custom-input"
                />
                <button @click="setCustomTimeout" class="custom-btn">
                    {{ $t('session_settings.btn_apply') }}
                </button>
            </div>

            <button
                v-if="!showCustomInput"
                @click="showCustomInput = true"
                class="custom-toggle"
            >
                {{ $t('session_settings.btn_customize') }}
            </button>
        </div>
        
        <div class="session-info">
            <h4>{{ $t('session_settings.info_title') }}</h4>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">{{ $t('session_settings.info_logout_in') }}</span>
                    <span class="info-value">{{ timeUntilLogout }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">{{ $t('session_settings.info_last_activity') }}</span>
                    <span class="info-value">{{ formatTime(lastActivity) }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">{{ $t('session_settings.info_active_since') }}</span>
                    <span class="info-value">{{ formatDuration(sessionDuration) }}</span>
                </div>
            </div>
        </div>

        <div class="settings-actions">
            <button @click="refreshSession" class="action-btn refresh">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                {{ $t('session_settings.btn_refresh') }}
            </button>

            <button @click="logoutAll" class="action-btn logout-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

const { t } = useI18n()
const authStore = useAuthStore()
const showCustomInput = ref(false)
const customTimeout = ref(60)
const timerInterval = ref(null)

const timeoutOptions = computed(() => [
    { label: t('session_settings.opt_15min'), value: 15 },
    { label: t('session_settings.opt_30min'), value: 30 },
    { label: t('session_settings.opt_1h'), value: 60 },
    { label: t('session_settings.opt_2h'), value: 120 },
    { label: t('session_settings.opt_4h'), value: 240 },
    { label: t('session_settings.opt_never'), value: 0 }
])

const selectedTimeout = computed(() => {
    return authStore.getTimeoutDuration()
})

const lastActivity = computed(() => {
    return authStore.lastActivity
})

const timeUntilLogout = computed(() => {
    const now = Date.now()
    const inactiveTime = now - authStore.lastActivity
    const timeLeft = authStore.inactivityTimeout - inactiveTime
    
    if (timeLeft <= 0) return t('session_settings.now')
    if (timeLeft < 60000) return `${Math.floor(timeLeft / 1000)} sec`
    
    const minutes = Math.floor(timeLeft / 60000)
    return `${minutes} min`
})

const sessionDuration = computed(() => {
    // Vous pouvez stocker le loginTime dans le store
    const loginTime = localStorage.getItem('login_time')
    return loginTime ? Date.now() - parseInt(loginTime) : 0
})

const setTimeout = (minutes) => {
    authStore.setTimeoutDuration(minutes)
    showCustomInput.value = false
}

const setCustomTimeout = () => {
    if (customTimeout.value >= 5 && customTimeout.value <= 480) {
        setTimeout(customTimeout.value)
    }
}

const refreshSession = () => {
    authStore.resetInactivityTimer()
    // Optionnel: rafraîchir le token
    authStore.refreshToken().catch(console.error)
}

const logoutAll = async () => {
    if (confirm(t('session_settings.confirm_logout_all'))) {
        await authStore.logoutAllDevices()
    }
}

const formatTime = (timestamp) => {
    const date = new Date(timestamp)
    return date.toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit'
    })
}

const formatDuration = (ms) => {
    if (!ms) return 'N/A'
    
    const hours = Math.floor(ms / 3600000)
    const minutes = Math.floor((ms % 3600000) / 60000)
    
    if (hours > 0) {
        return `${hours}h ${minutes}min`
    }
    return `${minutes} min`
}

onMounted(() => {
    // Stocker le temps de connexion
    if (!localStorage.getItem('login_time')) {
        localStorage.setItem('login_time', Date.now().toString())
    }
    
    // Mettre à jour le timer toutes les secondes
    timerInterval.value = setInterval(() => {}, 1000)
})

onUnmounted(() => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value)
    }
})
</script>

<style scoped>
.session-settings {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.dark .session-settings {
    background: #1e293b;
    color: #f1f5f9;
}

.settings-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 24px;
    color: #0f172a;
}

.dark .settings-title {
    color: #f1f5f9;
}

.settings-group {
    margin-bottom: 32px;
}

.setting-label {
    display: flex;
    flex-direction: column;
    margin-bottom: 16px;
}

.label-text {
    font-weight: 500;
    color: #0f172a;
}

.dark .label-text {
    color: #f1f5f9;
}

.label-description {
    font-size: 14px;
    color: #64748b;
    margin-top: 4px;
}

.timeout-options {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 10px;
    margin-bottom: 16px;
}

.timeout-btn {
    padding: 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.dark .timeout-btn {
    background: #334155;
    border-color: #475569;
    color: #cbd5e1;
}

.timeout-btn:hover {
    border-color: #3b82f6;
    transform: translateY(-1px);
}

.timeout-btn.active {
    border-color: #3b82f6;
    background: #eff6ff;
    color: #1d4ed8;
}

.dark .timeout-btn.active {
    background: #1e3a8a;
    color: #93c5fd;
}

.timeout-label {
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 4px;
}

.timeout-value {
    font-size: 14px;
    font-weight: 600;
}

.custom-timeout {
    display: flex;
    gap: 10px;
    margin-top: 16px;
}

.custom-input {
    flex: 1;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
}

.dark .custom-input {
    background: #334155;
    border-color: #475569;
    color: #f1f5f9;
}

.custom-btn {
    padding: 10px 20px;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.custom-btn:hover {
    background: #2563eb;
}

.custom-toggle {
    color: #3b82f6;
    background: none;
    border: none;
    font-size: 14px;
    cursor: pointer;
    padding: 8px 0;
}

.session-info {
    border-top: 1px solid #e2e8f0;
    padding-top: 24px;
    margin-bottom: 24px;
}

.dark .session-info {
    border-color: #475569;
}

.session-info h4 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 16px;
    color: #0f172a;
}

.dark .session-info h4 {
    color: #f1f5f9;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
}

.info-item {
    display: flex;
    flex-direction: column;
}

.info-label {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 4px;
}

.info-value {
    font-size: 14px;
    font-weight: 500;
    color: #0f172a;
}

.dark .info-value {
    color: #f1f5f9;
}

.settings-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
    border-top: 1px solid #e2e8f0;
    padding-top: 24px;
}

.dark .settings-actions {
    border-color: #475569;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    font-size: 14px;
}

.action-btn svg {
    width: 20px;
    height: 20px;
}

.action-btn.refresh {
    background: #f0f9ff;
    color: #0369a1;
    border: 1px solid #bae6fd;
}

.dark .action-btn.refresh {
    background: #0c4a6e;
    color: #7dd3fc;
    border-color: #0ea5e9;
}

.action-btn.refresh:hover {
    background: #e0f2fe;
}

.dark .action-btn.refresh:hover {
    background: #075985;
}

.action-btn.logout-all {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.dark .action-btn.logout-all {
    background: #7f1d1d;
    color: #fca5a5;
    border-color: #f87171;
}

.action-btn.logout-all:hover {
    background: #fee2e2;
}

.dark .action-btn.logout-all:hover {
    background: #991b1b;
}
</style>