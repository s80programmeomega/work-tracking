import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import tachesApi from '@/api/taches'

export const useTacheStore = defineStore('tache', () => {
  // State
  const taches = ref([])
  const kanban = ref({
    a_faire: [],
    en_cours: [],
    termine: []
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
      // Filter tasks assigned to current user (implement based on auth)
      return true
    })
  })

  // Actions
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

  async function fetchKanbanForActivite(activiteId) {
    loading.value = true
    error.value = null
    try {
      const { data } = await tachesApi.getForActivite(activiteId)
      kanban.value = {
        a_faire: data.a_faire || [],
        en_cours: data.en_cours || [],
        termine: data.termine || []
      }
      // Debug: Check if labels are in the data
      console.log('Kanban data loaded, first task labels:', kanban.value.a_faire[0]?.labels)
      return kanban.value
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch kanban'
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

      // Add to local state
      taches.value.push(newTache)

      // Add to kanban if it exists
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

      // Update in local state
      const index = taches.value.findIndex(t => t.id === id)
      if (index !== -1) {
        taches.value[index] = updatedTache
      }

      // Update in kanban
      updateTacheInKanban(updatedTache)

      // Update current if it's the same
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

      // Remove from local state
      taches.value = taches.value.filter(t => t.id !== id)

      // Remove from kanban
      for (const statut in kanban.value) {
        kanban.value[statut] = kanban.value[statut].filter(t => t.id !== id)
      }

      // Clear current if it's the same
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

      // Update in local state
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

      // Add to local state
      taches.value.push(duplicatedTache)

      // Add to kanban
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

      // Remove from local state
      taches.value = taches.value.filter(t => t.id !== id)

      // Remove from kanban
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

  async function validateTache(id) {
    loading.value = true
    error.value = null
    try {
      const { data } = await tachesApi.validate(id)
      const validatedTache = data.data

      // Update in local state
      const index = taches.value.findIndex(t => t.id === id)
      if (index !== -1) {
        taches.value[index] = validatedTache
      }

      // Update in kanban
      updateTacheInKanban(validatedTache)

      return validatedTache
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to validate task'
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

      // Update in local state
      const index = taches.value.findIndex(t => t.id === tacheId)
      if (index !== -1) {
        taches.value[index] = updatedTache
      }

      // Update in kanban
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

      // Update in local state
      const index = taches.value.findIndex(t => t.id === tacheId)
      if (index !== -1) {
        taches.value[index] = updatedTache
      }

      // Update in kanban
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

      // Update in local state
      const index = taches.value.findIndex(t => t.id === id)
      if (index !== -1) {
        taches.value[index] = updatedTache
      }

      // Update in kanban
      updateTacheInKanban(updatedTache)

      return updatedTache
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to update progress'
      throw err
    }
  }

  // Helper function to update task in kanban
  function updateTacheInKanban(tache) {
    // Remove from all statuts
    for (const statut in kanban.value) {
      kanban.value[statut] = kanban.value[statut].filter(t => t.id !== tache.id)
    }

    // Add to correct statut
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
    currentTache.value = null
    loading.value = false
    error.value = null
  }

  return {
    // State
    taches,
    kanban,
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
    validateTache,
    assignUser,
    unassignUser,
    updateProgress,
    clearError,
    $reset
  }
})
