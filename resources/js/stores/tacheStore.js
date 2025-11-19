// resources/js/stores/tacheStore.js - VERSION CORRIGÉE
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import tachesApi from '@/api/taches'

export const useTacheStore = defineStore('tache', () => {
  // ✅ State avec initialisation garantie
  const taches = ref([])
  const kanban = ref({
    a_faire: [],
    en_cours: [],
    termine: []
  })

  const stats = ref({
    total: 0,
    a_faire: 0,
    en_cours: 0,
    termine: 0
  })

  const currentTache = ref(null)
  const loading = ref(false)
  const error = ref(null)

  // Getters
  const tachesByStatut = computed(() => {
    return {
      a_faire: taches.value.filter(t => t.statut === 'a_faire'),
      en_cours: taches.value.filter(t => t.statut === 'en_cours'),
      termine: taches.value.filter(t => t.statut === 'termine')
    }
  })

  const overdueTaches = computed(() => {
    return taches.value.filter(t => t.is_overdue)
  })

  const myTaches = computed(() => {
    return taches.value.filter(t => {
      return true // À adapter selon votre système d'auth
    })
  })

  // ✅ CORRIGÉ : Fetch Kanban avec gestion d'erreurs robuste
  async function fetchKanbanForActivite(activiteId) {
    loading.value = true
    error.value = null
    
    try {
      console.log('🔄 Store: Chargement kanban pour activité:', activiteId)
      
      const { data } = await tachesApi.getForActivite(activiteId)
      
      console.log('📦 Store: Données reçues:', data)
      
      // ✅ Initialisation GARANTIE avec structure complète
      kanban.value = {
        a_faire: Array.isArray(data.a_faire) ? data.a_faire : [],
        en_cours: Array.isArray(data.en_cours) ? data.en_cours : [],
        termine: Array.isArray(data.termine) ? data.termine : []
      }
      
      // ✅ Stats avec fallback
      stats.value = data.stats || {
        total: kanban.value.a_faire.length + kanban.value.en_cours.length + kanban.value.termine.length,
        a_faire: kanban.value.a_faire.length,
        en_cours: kanban.value.en_cours.length,
        termine: kanban.value.termine.length
      }
      
      console.log('✅ Store: Kanban mis à jour:', {
        a_faire: kanban.value.a_faire.length,
        en_cours: kanban.value.en_cours.length,
        termine: kanban.value.termine.length,
        stats: stats.value
      })
      
      return kanban.value
      
    } catch (err) {
      console.error('❌ Store: Erreur chargement kanban:', err)
      console.error('Response:', err.response?.data)
      
      error.value = err.response?.data?.message || 'Failed to fetch kanban'
      
      // ✅ Réinitialiser avec structure vide en cas d'erreur
      kanban.value = {
        a_faire: [],
        en_cours: [],
        termine: []
      }
      stats.value = {
        total: 0,
        a_faire: 0,
        en_cours: 0,
        termine: 0
      }
      
      throw err
    } finally {
      loading.value = false
    }
  }

  // Actions de base
  async function fetchTaches(filters = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await tachesApi.getAll(filters)
      taches.value = data.data || []
      return taches.value
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch tasks'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchMyTaches() {
    loading.value = true
    error.value = null
    try {
      const { data } = await tachesApi.getMyTaches()
      taches.value = data.data || []
      return taches.value
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch my tasks'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchTache(id) {
    loading.value = true
    error.value = null
    try {
      const { data } = await tachesApi.get(id)
      currentTache.value = data.data
      return currentTache.value
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch task'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createTache(tacheData) {
    loading.value = true
    error.value = null
    try {
      const { data } = await tachesApi.create(tacheData)
      const newTache = data.data

      taches.value.push(newTache)

      if (kanban.value[newTache.statut]) {
        kanban.value[newTache.statut].push(newTache)
      }

      return newTache
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to create task'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateTache(id, tacheData) {
    loading.value = true
    error.value = null
    try {
      const { data } = await tachesApi.update(id, tacheData)
      const updatedTache = data.data

      const index = taches.value.findIndex(t => t.id === id)
      if (index !== -1) {
        taches.value[index] = updatedTache
      }

      updateTacheInKanban(updatedTache)

      if (currentTache.value?.id === id) {
        currentTache.value = updatedTache
      }

      return updatedTache
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to update task'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteTache(id) {
    loading.value = true
    error.value = null
    try {
      await tachesApi.delete(id)

      taches.value = taches.value.filter(t => t.id !== id)

      for (const statut in kanban.value) {
        kanban.value[statut] = kanban.value[statut].filter(t => t.id !== id)
      }

      if (currentTache.value?.id === id) {
        currentTache.value = null
      }

      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to delete task'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function moveTache(id, statut, ordre) {
    error.value = null
    try {
      const { data } = await tachesApi.move(id, statut, ordre)
      const movedTache = data.data

      const index = taches.value.findIndex(t => t.id === id)
      if (index !== -1) {
        taches.value[index] = movedTache
      }

      return movedTache
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to move task'
      throw err
    }
  }

  async function duplicateTache(id) {
    loading.value = true
    error.value = null
    try {
      const { data } = await tachesApi.duplicate(id)
      const duplicatedTache = data.data

      taches.value.push(duplicatedTache)

      if (kanban.value[duplicatedTache.statut]) {
        kanban.value[duplicatedTache.statut].push(duplicatedTache)
      }

      return duplicatedTache
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to duplicate task'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function archiveTache(id) {
    loading.value = true
    error.value = null
    try {
      await tachesApi.archive(id)

      taches.value = taches.value.filter(t => t.id !== id)

      for (const statut in kanban.value) {
        kanban.value[statut] = kanban.value[statut].filter(t => t.id !== id)
      }

      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to archive task'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function unarchiveTache(id) {
    loading.value = true
    error.value = null
    try {
      const { data } = await tachesApi.unarchive(id)
      const unarchivedTache = data.data

      taches.value.push(unarchivedTache)

      if (kanban.value[unarchivedTache.statut]) {
        kanban.value[unarchivedTache.statut].push(unarchivedTache)
      }

      return unarchivedTache
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to unarchive task'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function assignUser(tacheId, userId) {
    error.value = null
    try {
      const { data } = await tachesApi.assignUser(tacheId, userId)
      const updatedTache = data.data

      const index = taches.value.findIndex(t => t.id === tacheId)
      if (index !== -1) {
        taches.value[index] = updatedTache
      }

      updateTacheInKanban(updatedTache)

      return updatedTache
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to assign user'
      throw err
    }
  }

  async function unassignUser(tacheId, userId) {
    error.value = null
    try {
      const { data } = await tachesApi.unassignUser(tacheId, userId)
      const updatedTache = data.data

      const index = taches.value.findIndex(t => t.id === tacheId)
      if (index !== -1) {
        taches.value[index] = updatedTache
      }

      updateTacheInKanban(updatedTache)

      return updatedTache
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to unassign user'
      throw err
    }
  }

  async function updateProgress(id, tauxRealisation) {
    error.value = null
    try {
      const { data } = await tachesApi.updateProgress(id, tauxRealisation)
      const updatedTache = data.data

      const index = taches.value.findIndex(t => t.id === id)
      if (index !== -1) {
        taches.value[index] = updatedTache
      }

      updateTacheInKanban(updatedTache)

      return updatedTache
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to update progress'
      throw err
    }
  }

  // Helper function to update task in kanban
  function updateTacheInKanban(tache) {
    for (const statut in kanban.value) {
      kanban.value[statut] = kanban.value[statut].filter(t => t.id !== tache.id)
    }

    if (kanban.value[tache.statut]) {
      const index = kanban.value[tache.statut].findIndex(t => t.id === tache.id)
      if (index !== -1) {
        kanban.value[tache.statut][index] = tache
      } else {
        kanban.value[tache.statut].push(tache)
      }
    }
  }

  function clearError() {
    error.value = null
  }

  function $reset() {
    taches.value = []
    kanban.value = { a_faire: [], en_cours: [], termine: [] }
    stats.value = { total: 0, a_faire: 0, en_cours: 0, termine: 0 }
    currentTache.value = null
    loading.value = false
    error.value = null
  }

  return {
    // State
    taches,
    kanban,
    stats,
    currentTache,
    loading,
    error,

    // Getters
    tachesByStatut,
    overdueTaches,
    myTaches,

    // Actions
    fetchTaches,
    fetchKanbanForActivite,
    fetchMyTaches,
    fetchTache,
    createTache,
    updateTache,
    deleteTache,
    moveTache,
    duplicateTache,
    archiveTache,
    unarchiveTache,
    assignUser,
    unassignUser,
    updateProgress,
    clearError,
    $reset
  }
})