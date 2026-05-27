<!-- resources/js/components/taches/panels/QuickActionsPanel.vue -->
<template>
  <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
    <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
      <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
      </svg>
      Actions rapides
    </h3>
    
    <div class="space-y-2">
      <!-- Validation N1 -->
      <button
        v-if="showValidateN1"
        @click="handleValidateN1"
        :disabled="loading"
        class="w-full px-3 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700 flex items-center gap-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>Valider N1</span>
        <span v-if="loading" class="animate-spin">⟳</span>
      </button>

      <!-- Validation N2 -->
      <button
        v-if="showValidateN2"
        @click="handleValidateN2"
        :disabled="loading"
        class="w-full px-3 py-2 text-sm bg-purple-600 text-white rounded-md hover:bg-purple-700 flex items-center gap-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>Valider N2</span>
        <span v-if="loading" class="animate-spin">⟳</span>
      </button>

      <!-- Marquer comme terminé -->
      <button
        v-if="showCompleteButton"
        @click="handleComplete"
        :disabled="loading"
        class="w-full px-3 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center gap-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span>Marquer terminé</span>
        <span v-if="loading" class="animate-spin">⟳</span>
      </button>

      <!-- Réouvrir la tâche -->
      <button
        v-if="showReopenButton"
        @click="handleReopen"
        :disabled="loading"
        class="w-full px-3 py-2 text-sm bg-amber-600 text-white rounded-md hover:bg-amber-700 flex items-center gap-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        <span>Réouvrir</span>
        <span v-if="loading" class="animate-spin">⟳</span>
      </button>

      <!-- Actions d'archivage -->
      <div v-if="showArchiveActions" class="pt-2 border-t border-gray-200 dark:border-gray-700 space-y-2">
        <button
          v-if="!tache.archived_at && tache.permissions?.can_archive"
          @click="handleArchive"
          :disabled="loading"
          class="w-full px-3 py-2 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700 flex items-center gap-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
          </svg>
          <span>Archiver</span>
        </button>

        <button
          v-if="tache.archived_at && tache.permissions?.can_archive"
          @click="handleUnarchive"
          :disabled="loading"
          class="w-full px-3 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700 flex items-center gap-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
          </svg>
          <span>Désarchiver</span>
        </button>
      </div>

      <!-- Message d'information -->
      <div v-if="!hasAnyAction" class="text-center py-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">
          Aucune action disponible
        </p>
      </div>
    </div>

    <!-- État de chargement global -->
    <div v-if="loading" class="mt-3 p-2 bg-blue-50 dark:bg-blue-900/20 rounded-md">
      <div class="flex items-center gap-2">
        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
        <span class="text-xs text-blue-600 dark:text-blue-400">Traitement en cours...</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useTaches } from '@/composables/useTaches'

const props = defineProps({
  tache: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['validate-n1', 'validate-n2', 'complete', 'reopen', 'archive', 'unarchive'])

const { completeTache, reopenTache, archiveTache, unarchiveTache } = useTaches()
const loading = ref(false)

// Computed properties pour la logique conditionnelle
const showValidateN1 = computed(() => {
  return (
    props.tache.permissions?.can_validate_n1 &&
    props.tache.statut === 'termine' &&
    props.tache.validation?.n1_required &&
    !props.tache.validation?.n1_validated_at
  )
})

const showValidateN2 = computed(() => {
  return (
    props.tache.permissions?.can_validate_n2 &&
    props.tache.validation?.n1_validated_at &&
    props.tache.validation?.n2_required &&
    !props.tache.validation?.n2_validated_at
  )
})

const showCompleteButton = computed(() => {
  return (
    props.tache.permissions?.can_complete &&
    props.tache.statut !== 'termine'
  )
})

const showReopenButton = computed(() => {
  return (
    props.tache.permissions?.can_edit &&
    props.tache.statut === 'termine' &&
    !props.tache.validation?.is_fully_validated
  )
})

const showArchiveActions = computed(() => {
  return props.tache.permissions?.can_archive
})

const hasAnyAction = computed(() => {
  return showValidateN1.value ||
         showValidateN2.value ||
         showCompleteButton.value ||
         showReopenButton.value ||
         showArchiveActions.value
})

// Handlers
const handleValidateN1 = async () => {
  if (!props.tache.permissions?.can_validate_n1) {
    alert('Vous n\'avez pas la permission de valider cette tâche au niveau N1')
    return
  }

  const commentaire = prompt('Commentaire de validation (optionnel):')
  if (commentaire === null) return // User cancelled

  loading.value = true
  try {
    emit('validate-n1', props.tache, commentaire)
  } catch (error) {
    console.error('Error validating N1:', error)
    alert(error.response?.data?.message || 'Erreur lors de la validation N1')
  } finally {
    loading.value = false
  }
}

const handleValidateN2 = async () => {
  if (!props.tache.permissions?.can_validate_n2) {
    alert('Vous n\'avez pas la permission de valider cette tâche au niveau N2')
    return
  }

  const commentaire = prompt('Commentaire de validation finale (optionnel):')
  if (commentaire === null) return // User cancelled

  loading.value = true
  try {
    emit('validate-n2', props.tache, commentaire)
  } catch (error) {
    console.error('Error validating N2:', error)
    alert(error.response?.data?.message || 'Erreur lors de la validation N2')
  } finally {
    loading.value = false
  }
}

const handleComplete = async () => {
  if (!props.tache.permissions?.can_complete) {
    alert('Vous n\'avez pas la permission de marquer cette tâche comme terminée')
    return
  }

  if (!confirm('Êtes-vous sûr de vouloir marquer cette tâche comme terminée ?')) {
    return
  }

  loading.value = true
  try {
    await completeTache(props.tache.id)
    emit('complete', props.tache)
  } catch (error) {
    console.error('Error completing task:', error)
    alert(error.response?.data?.message || 'Erreur lors de la complétion de la tâche')
  } finally {
    loading.value = false
  }
}

const handleReopen = async () => {
  if (!props.tache.permissions?.can_edit) {
    alert('Vous n\'avez pas la permission de réouvrir cette tâche')
    return
  }

  if (!confirm('Êtes-vous sûr de vouloir réouvrir cette tâche ?')) {
    return
  }

  loading.value = true
  try {
    await reopenTache(props.tache.id)
    emit('reopen', props.tache)
  } catch (error) {
    console.error('Error reopening task:', error)
    alert(error.response?.data?.message || 'Erreur lors de la réouverture de la tâche')
  } finally {
    loading.value = false
  }
}

const handleArchive = async () => {
  if (!props.tache.permissions?.can_archive) {
    alert('Vous n\'avez pas la permission d\'archiver cette tâche')
    return
  }

  if (!confirm('Êtes-vous sûr de vouloir archiver cette tâche ?')) {
    return
  }

  loading.value = true
  try {
    await archiveTache(props.tache.id)
    emit('archive', props.tache)
  } catch (error) {
    console.error('Error archiving task:', error)
    alert(error.response?.data?.message || 'Erreur lors de l\'archivage de la tâche')
  } finally {
    loading.value = false
  }
}

const handleUnarchive = async () => {
  if (!props.tache.permissions?.can_archive) {
    alert('Vous n\'avez pas la permission de désarchiver cette tâche')
    return
  }

  if (!confirm('Êtes-vous sûr de vouloir désarchiver cette tâche ?')) {
    return
  }

  loading.value = true
  try {
    await unarchiveTache(props.tache.id)
    emit('unarchive', props.tache)
  } catch (error) {
    console.error('Error unarchiving task:', error)
    alert(error.response?.data?.message || 'Erreur lors du désarchivage de la tâche')
  } finally {
    loading.value = false
  }
}
</script>