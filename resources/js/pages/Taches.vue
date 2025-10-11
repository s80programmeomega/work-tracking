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
        <!-- Show Archived Toggle -->
        <button
          @click="showArchived = !showArchived"
          class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
          :class="{ 'bg-gray-100 dark:bg-gray-700': showArchived }"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
          </svg>
          {{ showArchived ? 'Masquer archivées' : 'Voir archivées' }}
        </button>
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
      <!-- Archived Tasks View -->
      <div v-if="showArchived" class="mt-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Tâches Archivées</h3>

        <div v-if="archivedTasks.length === 0" class="text-center py-12 text-gray-500">
          <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
          </svg>
          <p>Aucune tâche archivée</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="tache in archivedTasks" :key="tache.id" class="opacity-75">
            <TacheCard
              :tache="tache"
              @view="handleViewTask"
              @edit="handleEditTask"
              @duplicate="handleDuplicateTask"
              @archive="handleUnarchiveTask"
              @delete="handleDeleteTask"
              @validate="handleValidateTask"
            />
          </div>
        </div>
      </div>

      <!-- Kanban Board (Active Tasks) -->
      <div v-else class="mt-6">
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

    <!-- Task View Modal - Design Moderne 2 Colonnes -->
    <div v-if="showViewModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="showViewModal = false">
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-7xl max-h-[95vh] overflow-hidden flex flex-col">

        <!-- Header -->
        <div class="px-8 py-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-start bg-gradient-to-r from-gray-50 to-white dark:from-gray-900 dark:to-gray-800">
          <div class="flex-1">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ currentTache?.titre }}</h2>
            <div class="flex items-center gap-3">
              <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full shadow-sm"
                :class="{
                  'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300': currentTache?.statut === 'a_faire',
                  'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300': currentTache?.statut === 'en_cours',
                  'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300': currentTache?.statut === 'termine'
                }">
                <span class="w-2 h-2 rounded-full mr-2"
                  :class="{
                    'bg-gray-500': currentTache?.statut === 'a_faire',
                    'bg-blue-500': currentTache?.statut === 'en_cours',
                    'bg-green-500': currentTache?.statut === 'termine'
                  }"></span>
                {{ currentTache?.statut_label }}
              </span>
              <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full shadow-sm"
                :class="{
                  'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300': currentTache?.priorite === 'faible',
                  'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300': currentTache?.priorite === 'moyenne',
                  'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300': currentTache?.priorite === 'elevee',
                  'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300': currentTache?.priorite === 'critique'
                }">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" />
                </svg>
                {{ currentTache?.priorite_label }}
              </span>
            </div>
          </div>
          <button @click="showViewModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors ml-4">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Body avec 2 colonnes -->
        <div class="flex-1 overflow-hidden flex">

          <!-- Colonne Gauche - Détails (60%) -->
          <div class="w-3/5 overflow-y-auto px-8 py-6 border-r border-gray-200 dark:border-gray-700">
            <div class="space-y-6">

              <!-- Description -->
              <div v-if="currentTache?.description" class="bg-gray-50 dark:bg-gray-900 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2 mb-3">
                  <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                  </svg>
                  <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Description</h3>
                </div>
                <p class="text-gray-700 dark:text-gray-300 leading-relaxed">{{ currentTache.description }}</p>
              </div>

              <!-- Progression -->
              <div v-if="currentTache?.taux_realisation >= 0" class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-5 border border-blue-200 dark:border-blue-800">
                <div class="flex items-center justify-between mb-3">
                  <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-300 uppercase tracking-wide">Progression</h3>
                  </div>
                  <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ currentTache.taux_realisation }}%</span>
                </div>
                <div class="w-full bg-blue-200 dark:bg-blue-900 rounded-full h-3 overflow-hidden shadow-inner">
                  <div class="bg-gradient-to-r from-blue-500 to-indigo-500 h-3 rounded-full transition-all duration-500 ease-out shadow-lg"
                    :style="{ width: `${currentTache.taux_realisation}%` }">
                  </div>
                </div>
              </div>

              <!-- Échéance & Assignés -->
              <div class="grid grid-cols-2 gap-4">
                <!-- Échéance -->
                <div v-if="currentTache?.echeance" class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-5 border border-amber-200 dark:border-amber-800">
                  <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="text-xs font-semibold text-amber-900 dark:text-amber-300 uppercase tracking-wide">Échéance</h3>
                  </div>
                  <p class="text-lg font-semibold text-amber-900 dark:text-amber-300">{{ new Date(currentTache.echeance).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
                </div>

                <!-- Assignés -->
                <div v-if="currentTache?.assignees?.length > 0" class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-5 border border-purple-200 dark:border-purple-800">
                  <div class="flex items-center gap-2 mb-3">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="text-xs font-semibold text-purple-900 dark:text-purple-300 uppercase tracking-wide">Assignés</h3>
                  </div>
                  <div class="flex flex-wrap gap-2">
                    <div v-for="assignee in currentTache.assignees.slice(0, 3)" :key="assignee.id"
                      class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-purple-900/30 rounded-full shadow-sm border border-purple-200 dark:border-purple-700">
                      <div v-if="assignee.avatar" class="w-6 h-6 rounded-full overflow-hidden ring-2 ring-purple-300">
                        <img :src="assignee.avatar" :alt="assignee.nom" class="w-full h-full object-cover" />
                      </div>
                      <div v-else class="w-6 h-6 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 text-white flex items-center justify-center text-xs font-bold ring-2 ring-purple-300">
                        {{ assignee.nom.charAt(0).toUpperCase() }}
                      </div>
                      <span class="text-xs font-medium text-purple-900 dark:text-purple-300">{{ assignee.nom }}</span>
                    </div>
                    <span v-if="currentTache.assignees.length > 3" class="flex items-center px-3 py-1.5 bg-purple-100 dark:bg-purple-900/40 rounded-full text-xs font-medium text-purple-700 dark:text-purple-300">
                      +{{ currentTache.assignees.length - 3 }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Objectif -->
              <div v-if="currentTache?.objectif" class="bg-green-50 dark:bg-green-900/20 rounded-xl p-5 border border-green-200 dark:border-green-800">
                <div class="flex items-center gap-2 mb-3">
                  <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <h3 class="text-sm font-semibold text-green-900 dark:text-green-300 uppercase tracking-wide">Objectif</h3>
                </div>
                <p class="text-gray-700 dark:text-gray-300 leading-relaxed">{{ currentTache.objectif }}</p>
              </div>

              <!-- Indicateurs -->
              <div v-if="currentTache?.indicateurs_resultats" class="bg-cyan-50 dark:bg-cyan-900/20 rounded-xl p-5 border border-cyan-200 dark:border-cyan-800">
                <div class="flex items-center gap-2 mb-3">
                  <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                  </svg>
                  <h3 class="text-sm font-semibold text-cyan-900 dark:text-cyan-300 uppercase tracking-wide">Indicateurs</h3>
                </div>
                <p class="text-gray-700 dark:text-gray-300 leading-relaxed">{{ currentTache.indicateurs_resultats }}</p>
              </div>

              <!-- Commentaire -->
              <div v-if="currentTache?.commentaire" class="bg-rose-50 dark:bg-rose-900/20 rounded-xl p-5 border border-rose-200 dark:border-rose-800">
                <div class="flex items-center gap-2 mb-3">
                  <svg class="w-5 h-5 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                  </svg>
                  <h3 class="text-sm font-semibold text-rose-900 dark:text-rose-300 uppercase tracking-wide">Commentaire</h3>
                </div>
                <p class="text-gray-700 dark:text-gray-300 leading-relaxed">{{ currentTache.commentaire }}</p>
              </div>

              <!-- Documents Section -->
              <div class="mt-6">
                <DocumentSection
                  v-if="currentTache?.id"
                  documentable-type="App\Models\Tache"
                  :documentable-id="currentTache.id"
                  :current-user-id="currentUser?.id"
                />
              </div>

            </div>
          </div>

          <!-- Colonne Droite - Commentaires (40%) -->
          <div class="w-2/5 overflow-hidden flex flex-col bg-gray-50 dark:bg-gray-900">
            <!-- Header Commentaires -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center shadow-lg">
                  <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-lg font-bold text-gray-900 dark:text-white">Discussion</h3>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Collaborez avec votre équipe</p>
                </div>
              </div>
            </div>

            <!-- Zone Commentaires avec scroll -->
            <div class="flex-1 overflow-y-auto px-6 py-4">
              <CommentSection
                v-if="currentTache?.id"
                commentable-type="App\Models\Tache"
                :commentable-id="currentTache.id"
                :current-user-id="currentUser?.id"
              />
            </div>
          </div>

        </div>

        <!-- Footer Actions -->
        <div class="px-8 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-between items-center">
          <div class="text-sm text-gray-500 dark:text-gray-400">
            <span>Créée le {{ new Date(currentTache?.created_at).toLocaleDateString('fr-FR') }}</span>
          </div>
          <div class="flex gap-3">
            <button
              @click="showViewModal = false"
              class="px-5 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-700 dark:text-gray-300 transition-all"
            >
              Fermer
            </button>
            <button
              @click="showViewModal = false; handleEditTask(currentTache)"
              class="px-5 py-2.5 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-600 hover:to-brand-700 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all transform hover:scale-105"
            >
              <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              Modifier la tâche
            </button>
          </div>
        </div>

      </div>
    </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useTaches } from '@/composables/useTaches'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import KanbanBoard from '@/components/taches/KanbanBoardSimple.vue'
import TacheForm from '@/components/taches/TacheForm.vue'
import TacheCard from '@/components/taches/TacheCard.vue'
import CommentSection from '@/components/comments/CommentSection.vue'
import DocumentSection from '@/components/common/DocumentSection.vue'
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
const showArchived = ref(false)
const currentTache = ref(null)
const currentStatut = ref('a_faire')

// Local mutable kanban for drag & drop
const localKanban = ref({
  a_faire: [],
  en_cours: [],
  termine: []
})

// Archived tasks
const archivedTasks = ref([])

// Current user (for comments)
const currentUser = computed(() => {
  // Récupérer l'utilisateur depuis le localStorage ou store
  const userStr = localStorage.getItem('user')
  return userStr ? JSON.parse(userStr) : null
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

const loadArchivedTasks = async () => {
  if (!selectedActiviteId.value) return

  try {
    console.log('Loading archived tasks for activity:', selectedActiviteId.value)
    const { data } = await api.get(`/taches`, {
      params: {
        activite_id: selectedActiviteId.value,
        archive_status: 'archived'
      }
    })
    console.log('Archived tasks loaded:', data.data)
    archivedTasks.value = data.data || []
    console.log('archivedTasks.value set to:', archivedTasks.value)
  } catch (err) {
    console.error('Error loading archived tasks:', err)
  }
}

const handleArchiveTask = async (tache) => {
  if (!confirm('Voulez-vous archiver cette tâche ?')) {
    return
  }

  try {
    await archiveTache(tache.id)
    await loadKanban()
    await loadArchivedTasks()
  } catch (err) {
    console.error('Error archiving task:', err)
    alert('Erreur lors de l\'archivage de la tâche')
  }
}

const handleUnarchiveTask = async (tache) => {
  if (!confirm('Voulez-vous désarchiver cette tâche ?')) {
    return
  }

  try {
    await api.post(`/taches/${tache.id}/unarchive`)
    await loadKanban()
    await loadArchivedTasks()
  } catch (err) {
    console.error('Error unarchiving task:', err)
    alert('Erreur lors de la désarchivage de la tâche')
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

// Watch for showArchived toggle
watch(showArchived, async (newValue) => {
  if (newValue && selectedActiviteId.value) {
    await loadArchivedTasks()
  }
})

onMounted(async () => {
  await loadActivites()
})
</script>
