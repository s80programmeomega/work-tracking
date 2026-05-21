// resources/js/composables/useLiveNotifications.js
//
// Task 8 — Real-time notification subscription via Laravel Echo + Reverb.
//
// Subscribes to the authenticated user's private channel and increments the
// unread badge whenever Laravel broadcasts a notification.
//
// Usage in App.vue (or any high-level layout):
//   import { useLiveNotifications } from '@/composables/useLiveNotifications'
//   const { start, stop, onNotification } = useLiveNotifications()
//   onMounted(start)
//   onUnmounted(stop)
//
// Laravel automatically broadcasts every Notification with channel 'broadcast'
// on the user's private channel 'App.Models.User.{id}' as the event
// 'Illuminate\\Notifications\\Events\\BroadcastNotificationCreated'.
// Echo's .notification() listener picks this up out of the box.

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

    const start = () => {
        const userId = authStore.user?.id
        if (!userId || subscribed.value || !echo) return

        echo.private(`App.Models.User.${userId}`)
            .notification((data) => {
                // data is the notification's toArray() payload + a 'type' field
                // containing the FQCN of the Notification class.
                listeners.forEach((cb) => {
                    try { cb(data) } catch (e) { console.error('Live notification listener error:', e) }
                })
            })

        subscribed.value = true
    }

    const stop = () => {
        const userId = authStore.user?.id
        if (!userId || !echo) return
        echo.leave(`App.Models.User.${userId}`)
        subscribed.value = false
    }

    // Cleanup if a consumer never explicitly calls stop()
    onUnmounted(() => {
        // intentionally don't auto-stop — the channel is shared across the app session
    })

    return {
        subscribed,
        start,
        stop,
        onNotification,
    }
}
