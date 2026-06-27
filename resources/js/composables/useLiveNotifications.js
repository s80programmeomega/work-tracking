// resources/js/composables/useLiveNotifications.js
//
// Task 8 — Real-time notification subscription via Laravel Echo + Reverb.
//
// Subscribes to the authenticated user's private channel and increments the
// unread badge whenever Laravel broadcasts a notification.
//
// Also listens for session revocation events and logs the user out immediately
// when their specific token (or all tokens) are revoked by another session.

import { ref, onUnmounted } from 'vue'
import { useEcho } from '@/composables/useEcho'
import { useAuthStore } from '@/stores/authStore'

const subscribed = ref(false)
const listeners = []

export function useLiveNotifications() {
    const { echo } = useEcho()
    const authStore = useAuthStore()

    /** Register a callback that fires for each incoming notification. */
    const onNotification = (cb) => {
        listeners.push(cb)
        return () => {
            const i = listeners.indexOf(cb)
            if (i !== -1) listeners.splice(i, 1)
        }
    }

    const handleSessionRevoked = (data) => {
        const currentToken = localStorage.getItem('auth_token')
        if (!currentToken) return

        // Extraire l'ID du token courant depuis le JWT Sanctum (format: id|hash)
        const currentTokenId = parseInt(currentToken.split('|')[0], 10)
        if (currentTokenId === data.token_id) {
            authStore.logout()
        }
    }

    const handleAllSessionsRevoked = () => {
        authStore.logout()
    }

    const start = () => {
        const userId = authStore.user?.id
        if (!userId || subscribed.value || !echo) return

        echo.private(`App.Models.User.${userId}`)
            .notification((data) => {
                listeners.forEach((cb) => {
                    try { cb(data) } catch (e) { console.error('Live notification listener error:', e) }
                })
            })
            .listen('.session.revoked', handleSessionRevoked)
            .listen('.sessions.all.revoked', handleAllSessionsRevoked)

        subscribed.value = true
    }

    const stop = () => {
        const userId = authStore.user?.id
        if (!userId || !echo) return
        echo.leave(`App.Models.User.${userId}`)
        subscribed.value = false
    }

    onUnmounted(() => {
        // intentionally don't auto-stop — le canal est partagé sur toute la session
    })

    return {
        subscribed,
        start,
        stop,
        onNotification,
    }
}
