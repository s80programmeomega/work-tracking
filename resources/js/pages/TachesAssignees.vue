<!-- resources/js/pages/TachesAssignees.vue -->
<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="'Tâches Assignées'" />

    <div class="space-y-6">
      <!-- Header Premium -->
      <div class="rounded-2xl border border-gray-200 bg-gradient-to-br from-white to-gray-50 dark:from-gray-900 dark:to-gray-800 dark:border-gray-800 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Tâches Assignées
              </h1>
              <p class="text-gray-500 dark:text-gray-400">
                Toutes les tâches qui vous ont été explicitement assignées
              </p>
            </div>
          </div>

          <!-- Actions rapides -->
          <div class="flex items-center gap-3">
            <!-- Bouton refresh -->
            <button
              @click="loadAssignedTasks"
              :disabled="loading"
              class="p-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
              title="Actualiser"
            >
              <svg 
                class="w-5 h-5 text-gray-600 dark:text-gray-400" 
                :class="{ 'animate-spin': loading }"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </button>

            <!-- Toggle vue -->
            <div class="flex gap-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg">
              <button
                @click="currentView = 'grouped'"
                :class="[
                  'px-3 py-2 rounded-md transition-all flex items-center gap-2 text-sm',
                  currentView === 'grouped' 
                    ? 'bg-white dark:bg-gray-700 shadow-sm text-brand-600 dark:text-brand-400' 
                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                ]"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Par Activité
              </button>
              <button
                @click="currentView = 'priority'"
                :class="[
                  'px-3 py-2 rounded-md transition-all flex items-center gap-2 text-sm',
                  currentView === 'priority' 
                    ? 'bg-white dark:bg-gray-700 shadow-sm text-brand-600 dark:text-brand-400' 
                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                ]"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
                </svg>
                Par Priorité
              </button>
              <button
                @click="currentView = 'list'"
                :class="[
                  'px-3 py-2 rounded-md transition-all flex items-center gap-2 text-sm',
                  currentView === 'list' 
                    ? 'bg-white dark:bg-gray-700 shadow-sm text-brand-600 dark:text-brand-400' 
                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                ]"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                Liste
              </button>
            </div>
          </div>
        </div>

        <!-- Statistiques avec design premium -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
          <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-700 shadow-sm group hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total assignées</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ stats.total }}</p>
              </div>
              <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
              </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-gray-400 to-gray-500"></div>
          </div>

          <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-700 shadow-sm group hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">À faire</p>
                <p class="text-2xl font-bold text-slate-600 dark:text-slate-400 mt-1">{{ stats.a_faire }}</p>
              </div>
              <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-slate-400 to-slate-500"></div>
          </div>

          <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-700 shadow-sm group hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">En cours</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ stats.en_cours }}</p>
              </div>
              <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-400 to-blue-500"></div>
          </div>

          <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-700 shadow-sm group hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Terminées</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ stats.termine }}</p>
              </div>
              <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-green-400 to-green-500"></div>
          </div>

          <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-700 shadow-sm group hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">En retard</p>
                <p class="text-2xl font-bold mt-1" :class="stats.overdue > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-400'">
                  {{ stats.overdue }}
                </p>
              </div>
              <div 
                class="w-12 h-12 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform"
                :class="stats.overdue > 0 ? 'bg-red-100 dark:bg-red-900/30' : 'bg-gray-100 dark:bg-gray-700'"
              >
                <svg 
                  class="w-6 h-6" 
                  :class="stats.overdue > 0 ? 'text-red-500 animate-pulse' : 'text-gray-400'"
                  fill="none" 
                  stroke="currentColor" 
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
              </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1" :class="stats.overdue > 0 ? 'bg-gradient-to-r from-red-400 to-red-500' : 'bg-gray-300'"></div>
          </div>
        </div>
      </div>

      <!-- Filtres -->
      <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-4">
        <div class="flex flex-wrap items-center gap-4">
          <!-- Recherche -->
          <div class="flex-1 min-w-[250px] relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher une tâche..."
              class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500"
            />
          </div>

          <!-- Filtre priorité -->
          <select
            v-model="filters.priorite"
            class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500"
          >
            <option value="">Toutes priorités</option>
            <option value="critique">🔴 Critique</option>
            <option value="elevee">🟠 Élevée</option>
            <option value="moyenne">🟡 Moyenne</option>
            <option value="faible">🟢 Faible</option>
          </select>

          <!-- Filtre statut -->
          <select
            v-model="filters.statut"
            class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500"
          >
            <option value="">Tous statuts</option>
            <option value="a_faire">À faire</option>
            <option value="en_cours">En cours</option>
            <option value="termine">Terminé</option>
          </select>

          <!-- Toggle en retard -->
          <label class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
            <input
              type="checkbox"
              v-model="filters.overdue"
              class="w-4 h-4 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500"
            />
            <span class="text-sm text-gray-700 dark:text-gray-300">En retard</span>
          </label>

          <!-- Reset -->
          <button
            v-if="hasActiveFilters"
            @click="resetFilters"
            class="px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Réinitialiser
          </button>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <div class="text-center">
          <div class="relative">
            <div class="animate-spin rounded-full h-16 w-16 border-4 border-brand-200 dark:border-brand-800 mx-auto"></div>
            <div class="animate-spin rounded-full h-16 w-16 border-4 border-brand-500 border-t-transparent absolute top-0 left-1/2 -translate-x-1/2"></div>
          </div>
          <p class="text-gray-600 dark:text-gray-400 mt-4">Chargement des tâches assignées...</p>
        </div>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="rounded-2xl border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-6">
        <div class="flex items-center gap-3 text-red-700 dark:text-red-300">
          <svg class="w-6 h-6 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
          <div>
            <p class="font-medium">{{ error }}</p>
            <button @click="loadAssignedTasks" class="text-sm underline mt-1 hover:no-underline">
              Réessayer
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredTaches.length === 0" class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-12 text-center">
        <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30 rounded-3xl flex items-center justify-center">
          <svg class="w-12 h-12 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">
          {{ hasActiveFilters ? 'Aucun résultat' : 'Aucune tâche assignée' }}
        </h3>
        <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
          {{ hasActiveFilters 
            ? 'Aucune tâche ne correspond à vos critères de recherche' 
            : 'Vous n\'avez actuellement aucune tâche qui vous a été assignée' 
          }}
        </p>
        <button
          v-if="hasActiveFilters"
          @click="resetFilters"
          class="px-6 py-2.5 bg-gradient-to-r from-brand-500 to-brand-600 text-white rounded-lg hover:from-brand-600 hover:to-brand-700 transition-all shadow-lg hover:shadow-xl"
        >
          Réinitialiser les filtres
        </button>
      </div>

      <!-- Content -->
      <div v-else>
        <!-- Vue groupée par activité -->
        <div v-if="currentView === 'grouped'" class="space-y-6">
          <div
            v-for="group in tasksByActivite"
            :key="group.activite.id"
            class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] overflow-hidden"
          >
            <!-- Header du groupe -->
            <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-900 border-b border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-lg bg-brand-100 dark:bg-brand-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                  </div>
                  <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ group.activite.nom }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ group.activite.projet_nom }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-4">
                  <span class="px-3 py-1 text-sm font-medium bg-brand-100 dark:bg-brand-900/30 text-brand-700 dark:text-brand-300 rounded-full">
                    {{ group.taches.length }} tâche{{ group.taches.length > 1 ? 's' : '' }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Liste des tâches -->
            <div class="p-4 space-y-3">
              <div v-for="tache in group.taches" :key="tache.id">
                <TacheCard
                  :tache="tache"
                  @view="handleViewTask"
                  @edit="handleEditTask"
                  @duplicate="handleDuplicateTask"
                  @archive="handleArchiveTask"
                  @delete="handleDeleteTask"
                  @validate="handleValidateTask"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Vue par priorité -->
        <div v-else-if="currentView === 'priority'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div
            v-for="priority in priorityGroups"
            :key="priority.value"
            class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] overflow-hidden"
          >
            <!-- Header priorité -->
            <div 
              class="px-4 py-3 border-b border-gray-200 dark:border-gray-700"
              :style="{ backgroundColor: priority.bgColor }"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="text-lg">{{ priority.icon }}</span>
                  <span class="font-semibold" :style="{ color: priority.textColor }">{{ priority.label }}</span>
                </div>
                <span 
                  class="px-2 py-0.5 text-xs font-bold rounded-full"
                  :style="{ backgroundColor: priority.textColor, color: '#fff' }"
                >
                  {{ getTasksByPriority(priority.value).length }}
                </span>
              </div>
            </div>

            <!-- Tâches -->
            <div class="p-3 space-y-3 max-h-[600px] overflow-y-auto">
              <div v-if="getTasksByPriority(priority.value).length === 0" class="text-center py-8 text-gray-400">
                <p class="text-sm">Aucune tâche</p>
              </div>
              <div v-else v-for="tache in getTasksByPriority(priority.value)" :key="tache.id">
                <TacheCard
                  :tache="tache"
                  @view="handleViewTask"
                  @edit="handleEditTask"
                  @duplicate="handleDuplicateTask"
                  @archive="handleArchiveTask"
                  @delete="handleDeleteTask"
                  @validate="handleValidateTask"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Vue liste simple -->
        <div v-else-if="currentView === 'list'" class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-4">
          <div class="space-y-3">
            <div v-for="tache in filteredTaches" :key="tache.id">
              <TacheCard
                :tache="tache"
                @view="handleViewTask"
                @edit="handleEditTask"
                @duplicate="handleDuplicateTask"
                @archive="handleArchiveTask"
                @delete="handleDeleteTask"
                @validate="handleValidateTask"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Task Detail Modal -->
    <TacheDetailModal
      v-if="showViewModal"
      :tache="currentTache"
      @close="showViewModal = false"
      @edit="handleEditFromDetail"
      @validate-n1="handleValidateN1"
      @validate-n2="handleValidateN2"
    />

    <!-- Task Form Modal -->
    <TacheForm
      v-if="showForm"
      :tache="currentTache"
      :activite-id="currentTache?.activite_id"
      @close="closeForm"
      @saved="handleTaskSaved"
    />
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import TacheCard from '@/components/taches/TacheCard.vue'
import TacheDetailModal from '@/components/taches/TacheDetailModal.vue'
import TacheForm from '@/components/taches/TacheForm.vue'
import api from '@/api/axios'

// State
const taches = ref([])
const loading = ref(false)
const error = ref(null)
const currentView = ref('grouped')
const showViewModal = ref(false)
const showForm = ref(false)
const currentTache = ref(null)
const searchQuery = ref('')

// Filtres
const filters = ref({
  priorite: '',
  statut: '',
  overdue: false
})

// Groupes de priorité
const priorityGroups = [
  { value: 'critique', label: 'Critique', icon: '🔴', bgColor: '#FEE2E2', textColor: '#DC2626' },
  { value: 'elevee', label: 'Élevée', icon: '🟠', bgColor: '#FFEDD5', textColor: '#EA580C' },
  { value: 'moyenne', label: 'Moyenne', icon: '🟡', bgColor: '#FEF3C7', textColor: '#D97706' },
  { value: 'faible', label: 'Faible', icon: '🟢', bgColor: '#DCFCE7', textColor: '#16A34A' }
]

// Computed
const filteredTaches = computed(() => {
  let result = [...taches.value]

  // Recherche
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(t => 
      t.titre.toLowerCase().includes(query) ||
      t.description?.toLowerCase().includes(query) ||
      t.code?.toLowerCase().includes(query)
    )
  }

  // Filtre priorité
  if (filters.value.priorite) {
    result = result.filter(t => t.priorite === filters.value.priorite)
  }

  // Filtre statut
  if (filters.value.statut) {
    result = result.filter(t => t.statut === filters.value.statut)
  }

  // Filtre en retard
  if (filters.value.overdue) {
    result = result.filter(t => t.is_overdue)
  }

  return result
})

const stats = computed(() => {
  const all = filteredTaches.value
  return {
    total: all.length,
    a_faire: all.filter(t => t.statut === 'a_faire').length,
    en_cours: all.filter(t => t.statut === 'en_cours').length,
    termine: all.filter(t => t.statut === 'termine').length,
    overdue: all.filter(t => t.is_overdue).length
  }
})

const hasActiveFilters = computed(() => {
  return searchQuery.value || 
         filters.value.priorite || 
         filters.value.statut || 
         filters.value.overdue
})

const tasksByActivite = computed(() => {
  const grouped = {}
  
  filteredTaches.value.forEach(tache => {
    const activiteId = tache.activite_id
    if (!grouped[activiteId]) {
      grouped[activiteId] = {
        activite: tache.activite,
        taches: []
      }
    }
    grouped[activiteId].taches.push(tache)
  })

  return Object.values(grouped).sort((a, b) => 
    a.activite.nom.localeCompare(b.activite.nom)
  )
})

// Methods
function getTasksByPriority(priorite) {
  return filteredTaches.value.filter(t => t.priorite === priorite)
}

async function loadAssignedTasks() {
  loading.value = true
  error.value = null

  try {
    const { data } = await api.get('/taches/assignees')
    taches.value = data.data || []
    console.log('✅ Tâches assignées chargées:', taches.value.length)
  } catch (err) {
    console.error('❌ Erreur chargement:', err)
    error.value = err.response?.data?.message || 'Impossible de charger les tâches assignées'
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  searchQuery.value = ''
  filters.value = {
    priorite: '',
    statut: '',
    overdue: false
  }
}

// Handlers
async function handleViewTask(tache) {
  try {
    const { data } = await api.get(`/taches/${tache.id}`)
    currentTache.value = data.data
    showViewModal.value = true
  } catch (err) {
    console.error('Erreur chargement détails:', err)
    alert('Erreur lors du chargement des détails')
  }
}

function handleEditTask(tache) {
  currentTache.value = tache
  showForm.value = true
}

function handleEditFromDetail(tache) {
  showViewModal.value = false
  currentTache.value = tache
  showForm.value = true
}

async function handleDuplicateTask(tache) {
  if (!confirm('Voulez-vous dupliquer cette tâche ?')) return

  try {
    await api.post(`/taches/${tache.id}/duplicate`)
    await loadAssignedTasks()
  } catch (err) {
    console.error('Erreur duplication:', err)
    alert('Erreur lors de la duplication')
  }
}

async function handleArchiveTask(tache) {
  if (!confirm('Voulez-vous archiver cette tâche ?')) return

  try {
    await api.post(`/taches/${tache.id}/archive`)
    await loadAssignedTasks()
  } catch (err) {
    console.error('Erreur archivage:', err)
    alert('Erreur lors de l\'archivage')
  }
}

async function handleDeleteTask(tache) {
  if (!confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) return

  try {
    await api.delete(`/taches/${tache.id}`)
    await loadAssignedTasks()
  } catch (err) {
    console.error('Erreur suppression:', err)
    alert('Erreur lors de la suppression')
  }
}

async function handleValidateTask(tache) {
  const needsN1 = tache.validation?.n1_required && !tache.validation?.n1_validated_at
  const needsN2 = tache.validation?.n2_required && tache.validation?.n1_validated_at && !tache.validation?.n2_validated_at

  if (needsN1) {
    await handleValidateN1(tache)
  } else if (needsN2) {
    await handleValidateN2(tache)
  }
}

async function handleValidateN1(tache) {
  const commentaire = prompt('Commentaire de validation (optionnel):')
  if (commentaire === null) return

  try {
    await api.post(`/taches/${tache.id}/validate-n1`, { commentaire })
    await loadAssignedTasks()
    if (showViewModal.value) {
      await handleViewTask(tache)
    }
  } catch (err) {
    console.error('Erreur validation N1:', err)
    alert(err.response?.data?.message || 'Erreur lors de la validation')
  }
}

async function handleValidateN2(tache) {
  const commentaire = prompt('Commentaire de validation finale (optionnel):')
  if (commentaire === null) return

  try {
    await api.post(`/taches/${tache.id}/validate-n2`, { commentaire })
    await loadAssignedTasks()
    if (showViewModal.value) {
      await handleViewTask(tache)
    }
  } catch (err) {
    console.error('Erreur validation N2:', err)
    alert(err.response?.data?.message || 'Erreur lors de la validation')
  }
}

async function handleTaskSaved() {
  closeForm()
  await loadAssignedTasks()
}

function closeForm() {
  showForm.value = false
  currentTache.value = null
}

// Lifecycle
onMounted(() => {
  loadAssignedTasks()
})
</script>