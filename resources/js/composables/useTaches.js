import { useTacheStore } from '@/stores/tacheStore'
import { storeToRefs } from 'pinia'

export function useTaches() {
  const tacheStore = useTacheStore()

  // Reactive state from store
  const {
    taches,
    kanban,
    currentTache,
    loading,
    error,
    tachesByStatut,
    overdueTaches,
    myTaches
  } = storeToRefs(tacheStore)

  // Actions
  const {
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
    clearError
  } = tacheStore

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
    clearError
  }
}
