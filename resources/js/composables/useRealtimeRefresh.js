// resources/js/composables/useRealtimeRefresh.js
//
// Debounced Echo listener for workspace-scoped domain events.
// Pages call useRealtimeRefresh({ onTacheChanged, onResultatChanged, onSousTacheChanged, onPendingChanged })
// and provide callbacks that trigger their own data refetch.
//
// All callbacks are debounced (300 ms) so rapid-fire events don't cause N fetches.

import { onMounted, onUnmounted } from 'vue'
import { useEcho } from '@/composables/useEcho'
import { useAuthStore } from '@/stores/authStore'

function debounce(fn, ms = 300) {
    let timer = null
    return (...args) => {
        clearTimeout(timer)
        timer = setTimeout(() => fn(...args), ms)
    }
}

/**
 * @param {object} callbacks
 * @param {function} [callbacks.onTacheChanged]         payload: { tache_id, statut, taux_realisation }
 * @param {function} [callbacks.onResultatChanged]      payload: { resultat_id, tache_id, user_id, statut }
 * @param {function} [callbacks.onSousTacheChanged]     payload: { sous_tache_id, tache_id, action, statut }
 * @param {function} [callbacks.onPendingChanged]       payload: { workspace_id, notify_user_ids }
 */
export function useRealtimeRefresh(callbacks = {}) {
    const { echo } = useEcho()
    const authStore = useAuthStore()

    let channel = null

    const debouncedCallbacks = {
        onTacheChanged: callbacks.onTacheChanged ? debounce(callbacks.onTacheChanged) : null,
        onResultatChanged: callbacks.onResultatChanged ? debounce(callbacks.onResultatChanged) : null,
        onSousTacheChanged: callbacks.onSousTacheChanged ? debounce(callbacks.onSousTacheChanged) : null,
        onPendingChanged: callbacks.onPendingChanged ? debounce(callbacks.onPendingChanged) : null,
    }

    const subscribe = () => {
        const workspaceId = authStore.currentWorkspace?.id
        if (!workspaceId || !echo) { return }

        channel = echo.private(`workspace.${workspaceId}`)

        if (debouncedCallbacks.onTacheChanged) {
            channel.listen('.tache.statut.changed', debouncedCallbacks.onTacheChanged)
        }
        if (debouncedCallbacks.onResultatChanged) {
            channel.listen('.resultat.statut.changed', debouncedCallbacks.onResultatChanged)
        }
        if (debouncedCallbacks.onSousTacheChanged) {
            channel.listen('.sous-tache.changed', debouncedCallbacks.onSousTacheChanged)
        }
        if (debouncedCallbacks.onPendingChanged) {
            channel.listen('.pending-validation.count.changed', debouncedCallbacks.onPendingChanged)
        }
    }

    const unsubscribe = () => {
        const workspaceId = authStore.currentWorkspace?.id
        if (!workspaceId || !echo) { return }
        echo.leave(`workspace.${workspaceId}`)
        channel = null
    }

    onMounted(subscribe)
    onUnmounted(unsubscribe)

    return { subscribe, unsubscribe }
}
