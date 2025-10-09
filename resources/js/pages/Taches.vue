<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="'Gestion des Tâches'" />

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <!-- Activity Filter and Actions -->
      <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-4">
        <!-- Activity Selector -->
        <select
          v-model="selectedActiviteId"
          @change="loadKanban"
          class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
        >
          <option value="">Toutes les activités</option>
          <option v-for="activite in activites" :key="activite.id" :value="activite.id">
            {{ activite.nom }}
          </option>
        </select>

        <!-- Stats -->
        <div v-if="selectedActiviteId" class="flex gap-4 text-sm">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-gray-500"></div>
            <span class="text-gray-600 dark:text-gray-400">À faire: {{ kanban.a_faire.length }}</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-blue-500"></div>
            <span class="text-gray-600 dark:text-gray-400">En cours: {{ kanban.en_cours.length }}</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-green-500"></div>
            <span class="text-gray-600 dark:text-gray-400">Terminé: {{ kanban.termine.length }}</span>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-3">
        <button
          @click="showForm = true"
          class="px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 flex items-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Nouvelle tâche
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-500"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
      {{ error }}
    </div>

    <!-- Empty State - No Activity Selected -->
    <div v-if="!selectedActiviteId" class="text-center py-16">
      <svg class="w-24 h-24 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
      </svg>
      <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">
        Sélectionnez une activité
      </h3>
      <p class="text-gray-500 dark:text-gray-400">
        Choisissez une activité pour voir et gérer ses tâches
      </p>
    </div>

    <!-- Kanban Content -->
    <div v-else>
      <!-- Kanban Board -->
      <div class="mt-6">
        <KanbanBoard
          :kanban="localKanban"
          @add-task="handleAddTask"
          @view-task="handleViewTask"
          @edit-task="handleEditTask"
          @duplicate-task="handleDuplicateTask"
          @archive-task="handleArchiveTask"
          @delete-task="handleDeleteTask"
          @validate-task="handleValidateTask"
          @task-moved="handleTaskMoved"
        />
      </div>
    </div>

    <!-- Task Form Modal -->
    <TacheForm
      v-if="showForm"
      :tache="currentTache"
      :activite-id="selectedActiviteId"
      :initial-statut="currentStatut"
      @close="closeForm"
      @saved="handleTaskSaved"
    />

    <!-- Task View Modal -->
    <div v-if="showViewModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="showViewModal = false">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-start mb-6">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ currentTache?.titre }}</h2>
          <button @click="showViewModal = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="space-y-4">
          <div v-if="currentTache?.description">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</h3>
            <p class="text-gray-900 dark:text-white">{{ currentTache.description }}</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Statut</h3>
              <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full"
                :class="{
                  'bg-gray-100 text-gray-800': currentTache?.statut === 'a_faire',
                  'bg-blue-100 text-blue-800': currentTache?.statut === 'en_cours',
                  'bg-green-100 text-green-800': currentTache?.statut === 'termine'
                }">
                {{ currentTache?.statut_label }}
              </span>
            </div>

            <div>
              <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priorité</h3>
              <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full"
                :class="{
                  'bg-green-100 text-green-800': currentTache?.priorite === 'faible',
                  'bg-amber-100 text-amber-800': currentTache?.priorite === 'moyenne',
                  'bg-orange-100 text-orange-800': currentTache?.priorite === 'elevee',
                  'bg-red-100 text-red-800': currentTache?.priorite === 'critique'
                }">
                {{ currentTache?.priorite_label }}
              </span>
            </div>
          </div>

          <div v-if="currentTache?.echeance">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Échéance</h3>
            <p class="text-gray-900 dark:text-white">{{ new Date(currentTache.echeance).toLocaleDateString('fr-FR') }}</p>
          </div>

          <div v-if="currentTache?.taux_realisation > 0">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Progression</h3>
            <div class="flex items-center gap-3">
              <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="bg-brand-500 h-2 rounded-full" :style="{ width: `${currentTache.taux_realisation}%` }"></div>
              </div>
              <span class="text-sm font-medium text-gray-900 dark:text-white">{{ currentTache.taux_realisation }}%</span>
            </div>
          </div>

          <div v-if="currentTache?.assignees?.length > 0">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Assigné à</h3>
            <div class="flex flex-wrap gap-2">
              <div v-for="assignee in currentTache.assignees" :key="assignee.id"
                class="flex items-center gap-2 px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full">
                <div v-if="assignee.avatar" class="w-6 h-6 rounded-full overflow-hidden">
                  <img :src="assignee.avatar" :alt="assignee.nom" class="w-full h-full object-cover" />
                </div>
                <div v-else class="w-6 h-6 rounded-full bg-brand-500 text-white flex items-center justify-center text-xs font-medium">
                  {{ assignee.nom.charAt(0).toUpperCase() }}
                </div>
                <span class="text-sm text-gray-900 dark:text-white">{{ assignee.nom }}</span>
              </div>
            </div>
          </div>

          <div v-if="currentTache?.objectif">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Objectif</h3>
            <p class="text-gray-900 dark:text-white">{{ currentTache.objectif }}</p>
          </div>

          <div v-if="currentTache?.indicateurs_resultats">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Indicateurs de résultats</h3>
            <p class="text-gray-900 dark:text-white">{{ currentTache.indicateurs_resultats }}</p>
          </div>

          <div v-if="currentTache?.commentaire">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Commentaire</h3>
            <p class="text-gray-900 dark:text-white">{{ currentTache.commentaire }}</p>
          </div>
        </div>

        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
          <button
            @click="showViewModal = false"
            class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            Fermer
          </button>
          <button
            @click="showViewModal = false; handleEditTask(currentTache)"
            class="px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600"
          >
            Modifier
          </button>
        </div>
      </div>
    </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useTaches } from '@/composables/useTaches'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import KanbanBoard from '@/components/taches/KanbanBoardSimple.vue'
import TacheForm from '@/components/taches/TacheForm.vue'
import api from '@/api/axios'

const {
  kanban,
  loading,
  error,
  fetchKanbanForActivite,
  moveTache,
  duplicateTache,
  archiveTache,
  deleteTache,
  validateTache,
  clearError
} = useTaches()

const activites = ref([])
const selectedActiviteId = ref(null)
const showForm = ref(false)
const showViewModal = ref(false)
const currentTache = ref(null)
const currentStatut = ref('a_faire')

// Local mutable kanban for drag & drop
const localKanban = ref({
  a_faire: [],
  en_cours: [],
  termine: []
})

const loadActivites = async () => {
  try {
    const { data } = await api.get('/activites')
    activites.value = data.data || []

    // Auto-select first activity if available
    if (activites.value.length > 0 && !selectedActiviteId.value) {
      selectedActiviteId.value = activites.value[0].id
      await loadKanban()
    }
  } catch (err) {
    console.error('Error loading activities:', err)
  }
}

const loadKanban = async () => {
  if (!selectedActiviteId.value) {
    return
  }

  try {
    clearError()
    await fetchKanbanForActivite(selectedActiviteId.value)

    // Copy to local mutable kanban
    localKanban.value = {
      a_faire: [...(kanban.value.a_faire || [])],
      en_cours: [...(kanban.value.en_cours || [])],
      termine: [...(kanban.value.termine || [])]
    }
  } catch (err) {
    console.error('Error loading kanban:', err)
  }
}

const handleAddTask = (statut) => {
  console.log('handleAddTask called with statut:', statut)
  currentTache.value = null
  currentStatut.value = statut // Store the statut to pass to form
  showForm.value = true
  console.log('showForm set to:', showForm.value)
}

const handleViewTask = (tache) => {
  currentTache.value = tache
  showViewModal.value = true
}

const handleEditTask = (tache) => {
  currentTache.value = tache
  showForm.value = true
}

const handleDuplicateTask = async (tache) => {
  if (!confirm('Voulez-vous dupliquer cette tâche ?')) {
    return
  }

  try {
    await duplicateTache(tache.id)
    await loadKanban()
  } catch (err) {
    console.error('Error duplicating task:', err)
    alert('Erreur lors de la duplication de la tâche')
  }
}

const handleArchiveTask = async (tache) => {
  if (!confirm('Voulez-vous archiver cette tâche ?')) {
    return
  }

  try {
    await archiveTache(tache.id)
    await loadKanban()
  } catch (err) {
    console.error('Error archiving task:', err)
    alert('Erreur lors de l\'archivage de la tâche')
  }
}

const handleDeleteTask = async (tache) => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) {
    return
  }

  try {
    await deleteTache(tache.id)
    await loadKanban()
  } catch (err) {
    console.error('Error deleting task:', err)
    alert('Erreur lors de la suppression de la tâche')
  }
}

const handleValidateTask = async (tache) => {
  if (!confirm('Voulez-vous valider cette tâche ?')) {
    return
  }

  try {
    await validateTache(tache.id)
    await loadKanban()
  } catch (err) {
    console.error('Error validating task:', err)
    alert('Erreur lors de la validation de la tâche')
  }
}

const handleTaskMoved = async ({ tache, newStatut, newOrdre }) => {
  try {
    await moveTache(tache.id, newStatut, newOrdre)
    // Reload to ensure consistency
    await loadKanban()
  } catch (err) {
    console.error('Error moving task:', err)
    alert('Erreur lors du déplacement de la tâche')
    // Reload to restore previous state
    await loadKanban()
  }
}

const handleTaskSaved = async () => {
  closeForm()
  await loadKanban()
}

const closeForm = () => {
  showForm.value = false
  currentTache.value = null
}

onMounted(async () => {
  await loadActivites()
})
</script>
