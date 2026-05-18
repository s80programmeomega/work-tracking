// resources/js/composables/useSousTaches.js
import { ref, computed } from 'vue'
import api from '@/api/axios'

export function useSousTaches(tacheId) {
    const sousTaches = ref([])
    const loading = ref(false)
    const error = ref(null)

    const totalPoids = computed(() => sousTaches.value.reduce((sum, st) => sum + (st.poids ?? 0), 0))

    const hasBlockingSubtasks = computed(() =>
        sousTaches.value.some(st => st.statut !== 'termine' && st.statut !== 'annule')
    )

    const fetchSousTaches = async () => {
        loading.value = true
        error.value = null
        try {
            const { data } = await api.get(`/taches/${tacheId}/sous-taches`)
            sousTaches.value = data.data ?? data
        } catch (err) {
            console.error('[useSousTaches] error', err.response?.status, err.message)
            error.value = err.response?.data?.message ?? 'Erreur lors du chargement des sous-tâches.'
        } finally {
            loading.value = false
        }
    }

    const createSousTache = async (payload) => {
        const { data } = await api.post(`/taches/${tacheId}/sous-taches`, payload)
        sousTaches.value.push(data.data ?? data)
        return data
    }

    const updateSousTache = async (sousTacheId, payload) => {
        const { data } = await api.put(`/sous-taches/${sousTacheId}`, payload)
        const idx = sousTaches.value.findIndex(st => st.id === sousTacheId)
        if (idx !== -1) { sousTaches.value[idx] = data.data ?? data }
        return data
    }

    const deleteSousTache = async (sousTacheId) => {
        await api.delete(`/sous-taches/${sousTacheId}`)
        sousTaches.value = sousTaches.value.filter(st => st.id !== sousTacheId)
    }

    const assignIntervenant = async (sousTacheId, userId, options = {}) => {
        const { data } = await api.post(`/sous-taches/${sousTacheId}/intervenants`, {
            user_id: userId,
            ...options,
        })
        return data
    }

    return {
        sousTaches,
        loading,
        error,
        totalPoids,
        hasBlockingSubtasks,
        fetchSousTaches,
        createSousTache,
        updateSousTache,
        deleteSousTache,
        assignIntervenant,
    }
}
