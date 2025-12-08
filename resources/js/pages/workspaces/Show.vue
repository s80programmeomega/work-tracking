<!-- resources/js/pages/workspaces/Show.vue -->
<template>
  <AdminLayout>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center h-screen">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>

      <template v-else-if="workspace">
        <!-- Header Trello-like -->
        <div class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
          <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-4">
                <router-link to="/workspaces"
                  class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                  <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                  </svg>
                </router-link>

                <div class="flex items-center gap-3">
                  <div v-if="workspace.logo_url" class="w-10 h-10 rounded-lg overflow-hidden">
                    <img :src="workspace.logo_url" :alt="workspace.nom" class="w-full h-full object-cover"/>
                  </div>
                  <div v-else class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                    <span class="text-white font-bold text-lg">
                      {{ getInitials(workspace.nom) }}
                    </span>
                  </div>

                  <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                      {{ workspace.nom }}
                    </h1>
                    <div class="flex items-center gap-2 mt-1">
                      <span class="text-sm text-gray-600 dark:text-gray-400">
                        {{ workspace.code }}
                      </span>
                      <span :class="[
                        'px-2 py-1 text-xs font-medium rounded-full',
                        workspace.is_active
                          ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                          : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                      ]">
                        {{ workspace.is_active ? 'Actif' : 'Inactif' }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="flex items-center gap-2">
                <!-- Bouton Modifier avec vérification de permission -->
                <button v-if="canEditWorkspace"
                  @click="navigateToEdit"
                  class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                  :title="canEditWorkspace ? 'Modifier le workspace' : 'Seul le propriétaire peut modifier'"
                  :disabled="!canEditWorkspace">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                  Modifier
                </button>

                <button v-else
                  @click="showPermissionDenied('edit')"
                  class="flex items-center gap-2 px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-lg cursor-not-allowed"
                  title="Seul le propriétaire peut modifier">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                  Modifier
                </button>

                <!-- Bouton Paramètres avec vérification de permission -->
                <button v-if="canManageSettings"
                  @click="navigateToSettings"
                  class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                  :title="canManageSettings ? 'Gérer les paramètres' : 'Permission requise'">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  </svg>
                  Paramètres
                </button>

                <!-- Menu déroulant pour plus d'options -->
                <div class="relative">
                  <button @click="showWorkspaceMenu = !showWorkspaceMenu"
                    class="flex items-center gap-2 px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/>
                    </svg>
                    Plus d'options
                  </button>

                  <!-- Menu déroulant -->
                  <div v-if="showWorkspaceMenu" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-10">
                    <div class="py-1">
                      <!-- Option pour créer un projet -->
                      <button v-if="canCreateProjects"
                        @click="showCreateProjectModal = true"
                        class="flex items-center gap-2 w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nouveau projet
                      </button>
                      
                      <button v-else
                        @click="showPermissionDenied('create_projects')"
                        class="flex items-center gap-2 w-full px-4 py-2 text-sm text-gray-500 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nouveau projet 
                      </button>

                      <!-- Séparateur -->
                      <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>

                      <!-- Option pour gérer les membres -->
                      <button v-if="canManageMembers"
                        @click="activeTab = 'members'"
                        class="flex items-center gap-2 w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                        </svg>
                        Gérer les membres
                      </button>

                      <!-- Option pour archiver le workspace (seulement propriétaire) -->
                      <button v-if="isOwner"
                        @click="confirmArchiveWorkspace"
                        class="flex items-center gap-2 w-full px-4 py-2 text-sm text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                        Archiver le workspace
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <p v-if="workspace.description" class="text-gray-600 dark:text-gray-400 mt-3 text-sm">
              {{ workspace.description }}
            </p>
          </div>
        </div>

        <!-- Main Content -->
        <div class="container mx-auto px-6 py-6">
          <!-- Statistics Cards -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Projets</p>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ statistics.total_projets || 0 }}</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                  <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                  </svg>
                </div>
              </div>
              <div class="mt-2">
                <span class="text-xs text-green-600 dark:text-green-400 font-medium">
                  {{ statistics.projets_actifs || 0 }} actifs
                </span>
              </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Tâches</p>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ statistics.total_taches || 0 }}</p>
                </div>
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                  <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                  </svg>
                </div>
              </div>
              <div class="mt-2">
                <span class="text-xs text-green-600 dark:text-green-400 font-medium">
                  {{ statistics.taux_completion || 0 }}% complétées
                </span>
              </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Activités</p>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ statistics.total_activites || 0 }}</p>
                </div>
                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                  <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                  </svg>
                </div>
              </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Membres</p>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ workspace.member_count || 0 }}</p>
                </div>
                <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center">
                  <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                  </svg>
                </div>
              </div>
            </div>
          </div>

          <!-- Content Tabs -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
            <!-- Tab Navigation -->
            <div class="border-b border-gray-200 dark:border-gray-700">
              <nav class="flex space-x-8 px-6">
                <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" :class="[
                  'py-4 px-1 border-b-2 font-medium text-sm transition-colors whitespace-nowrap',
                  activeTab === tab.id
                    ? 'border-blue-600 text-blue-600 dark:text-blue-400'
                    : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                ]">
                  {{ tab.label }}
                </button>
              </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
              <!-- Overview Tab -->
              <div v-if="activeTab === 'overview'" class="space-y-6">
                <!-- Recent Projects -->
                <div>
                  <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Projets récents</h3>
                    <div class="flex gap-2">
                      <button v-if="canCreateProjects"
                        @click="showCreateProjectModal = true"
                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nouveau projet 
                      </button>
                      <button @click="activeTab = 'projects'"
                        class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                        Voir tous les projets
                      </button>
                    </div>
                  </div>
                  <ProjetList ref="recentProjetsList" :workspace-id="workspace.id" :limit="5"
                    :show-header="false" :show-filters="false" @view-projet="navigateToProject"/>
                </div>
              </div>

              <!-- Projects Tab -->
              <div v-if="activeTab === 'projects'">
                <div class="flex items-center justify-between mb-4">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tous les projets</h3>
                  <button v-if="canCreateProjects"
                    @click="showCreateProjectModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nouveau projet  
                  </button>
                </div>
                <ProjetList ref="allProjetsList" :workspace-id="workspace.id" @view-projet="navigateToProject"/>
              </div>

              <!-- Members Tab -->
              <div v-if="activeTab === 'members'">
                <WorkspaceMemberManagement :workspace-id="workspace.id" @member-updated="handleMemberUpdated"/>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Error State -->
      <div v-else class="flex items-center justify-center h-screen">
        <div class="text-center">
          <div class="w-16 h-16 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Workspace introuvable</h3>
          <p class="text-gray-500 dark:text-gray-400 mb-6">
            Le workspace demandé n'existe pas ou vous n'y avez pas accès.
          </p>
          <router-link to="/workspaces"
            class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Retour aux workspaces
          </router-link>
        </div>
      </div>
    </div>

    <!-- Modals -->
    
    <!-- Modal de création de projet -->
    <CreateProjectModal
      v-if="showCreateProjectModal"
      :workspace-id="workspace.id"
      @close="showCreateProjectModal = false"
      @created="handleProjectCreated"
    />

    <!-- Modal de confirmation d'archivage -->
    <ConfirmModal
      v-if="showArchiveModal"
      title="Archiver le workspace"
      message="Êtes-vous sûr de vouloir archiver ce workspace ? Tous les projets seront également archivés. Cette action peut être annulée."
      confirm-text="Archiver"
      type="warning"
      @confirm="archiveWorkspace"
      @cancel="showArchiveModal = false"
    />

    <!-- Notification Toast -->
    <div v-if="notification.show" :class="[
      'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300',
      notification.type === 'success' ? 'bg-green-50 border border-green-200 dark:bg-green-900/20 dark:border-green-800' :
      notification.type === 'error' ? 'bg-red-50 border border-red-200 dark:bg-red-900/20 dark:border-red-800' :
      notification.type === 'info' ? 'bg-blue-50 border border-blue-200 dark:bg-blue-900/20 dark:border-blue-800' :
      'bg-yellow-50 border border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800'
    ]">
      <div class="flex items-start gap-3">
        <div :class="[
          'rounded-full p-2',
          notification.type === 'success' ? 'bg-green-100 dark:bg-green-800' :
          notification.type === 'error' ? 'bg-red-100 dark:bg-red-800' :
          notification.type === 'info' ? 'bg-blue-100 dark:bg-blue-800' :
          'bg-yellow-100 dark:bg-yellow-800'
        ]">
          <svg class="w-5 h-5" :class="[
            notification.type === 'success' ? 'text-green-600 dark:text-green-300' :
            notification.type === 'error' ? 'text-red-600 dark:text-red-300' :
            notification.type === 'info' ? 'text-blue-600 dark:text-blue-300' :
            'text-yellow-600 dark:text-yellow-300'
          ]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path v-if="notification.type === 'success'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            <path v-if="notification.type === 'error'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            <path v-if="notification.type === 'info'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            <path v-if="notification.type === 'warning'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"/>
          </svg>
        </div>
        <div>
          <h3 class="font-medium text-gray-900 dark:text-white">{{ notification.title }}</h3>
          <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ notification.message }}</p>
        </div>
        <button @click="notification.show = false" class="ml-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, nextTick, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useWorkspace } from '@/composables/useWorkspace'
import { useWorkspacePermissions } from '@/composables/useWorkspacePermissions'
import ProjetList from '@/components/projets/ProjetList.vue'
import WorkspaceMemberManagement from '@/components/workspaces/WorkspaceMemberManagement.vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import CreateProjectModal from '@/components/projets/ProjetFormModal.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const route = useRoute()
const router = useRouter()
const { fetchWorkspace, fetchStatistics, archiveWorkspace: archiveWorkspaceService } = useWorkspace()

const workspace = ref(null)
const statistics = ref({})
const loading = ref(true)
const showWorkspaceMenu = ref(false)
const showCreateProjectModal = ref(false)
const showArchiveModal = ref(false)

const activeTab = ref('overview')
const recentProjetsList = ref(null)
const allProjetsList = ref(null)

// Notification system
const notification = ref({
  show: false,
  type: 'info', // success, error, warning, info
  title: '',
  message: '',
  timeout: null
})

// Tabs
const tabs = [
  { id: 'overview', label: 'Tableau de bord' },
  { id: 'projects', label: 'Projets' },
  { id: 'members', label: 'Membres' },
]

// Initialize permissions
const permissions = computed(() => {
  return useWorkspacePermissions(workspace)
})

// Computed properties for permissions
const isOwner = computed(() => permissions.value?.isOwner?.value || false)
const canEditWorkspace = computed(() => permissions.value?.canEditWorkspace?.value || false)
const canManageSettings = computed(() => permissions.value?.canManageSettings?.value || false)
const canManageMembers = computed(() => permissions.value?.canManageMembers?.value || false)
const canCreateProjects = computed(() => permissions.value?.canCreateProjects?.value || false)

// Methods
const showNotification = (type, title, message, duration = 5000) => {
  // Clear existing timeout
  if (notification.value.timeout) {
    clearTimeout(notification.value.timeout)
  }

  notification.value = {
    show: true,
    type,
    title,
    message
  }

  // Auto hide after duration
  notification.value.timeout = setTimeout(() => {
    notification.value.show = false
  }, duration)
}

const handleMemberUpdated = () => {
  loadWorkspaceData()
  showNotification('success', 'Membre mis à jour', 'Les modifications ont été enregistrées avec succès.')
}

const handleProjectCreated = () => {
  showCreateProjectModal.value = false
  showNotification('success', 'Projet créé', 'Le nouveau projet a été créé avec succès.')
  
  // Refresh project lists
  if (recentProjetsList.value?.loadProjects) {
    recentProjetsList.value.loadProjects()
  }
  if (allProjetsList.value?.loadProjects) {
    allProjetsList.value.loadProjects()
  }
}

const getInitials = (name) => {
  if (!name) return 'U'
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const navigateToProject = (projectId) => {
  router.push({ name: 'projets.show', params: { id: projectId } })
}

const navigateToEdit = () => {
  if (!canEditWorkspace.value) {
    showPermissionDenied('edit')
    return
  }
  router.push({ name: 'workspaces.edit', params: { id: workspace.value.id } })
}

const navigateToSettings = () => {
  if (!canManageSettings.value) {
    showPermissionDenied('manage_settings')
    return
  }
  router.push({ name: 'workspaces.settings', params: { id: workspace.value.id } })
}

const showPermissionDenied = (action) => {
  const messages = {
    edit: 'Seul le propriétaire du workspace peut le modifier.',
    manage_settings: 'Vous n\'avez pas la permission de gérer les paramètres.',
    create_projects: 'Vous n\'avez pas la permission de créer des projets.',
    manage_members: 'Vous n\'avez pas la permission de gérer les membres.'
  }
  
  showNotification('error', 'Permission refusée', messages[action] || 'Action non autorisée.')
}

const confirmArchiveWorkspace = () => {
  if (!isOwner.value) {
    showNotification('error', 'Permission refusée', 'Seul le propriétaire peut archiver le workspace.')
    return
  }
  showArchiveModal.value = true
}

const archiveWorkspace = async () => {
  try {
    await archiveWorkspaceService(workspace.value.id)
    showArchiveModal.value = false
    showNotification('success', 'Workspace archivé', 'Le workspace a été archivé avec succès.')
    
    // Rediriger vers la liste des workspaces après un délai
    setTimeout(() => {
      router.push('/workspaces')
    }, 2000)
  } catch (error) {
    console.error('Erreur archivage:', error)
    showNotification('error', 'Erreur', error.response?.data?.message || 'Erreur lors de l\'archivage.')
  }
}

const loadWorkspaceData = async () => {
  try {
    loading.value = true
    const workspaceId = parseInt(route.params.id)

    workspace.value = await fetchWorkspace(workspaceId)
    statistics.value = await fetchStatistics(workspaceId)
    
    // Show success notification
    showNotification('success', 'Chargement réussi', `Workspace "${workspace.value.nom}" chargé.`)
  } catch (error) {
    console.error('Error loading workspace:', error)
    workspace.value = null
    showNotification('error', 'Erreur de chargement', 'Impossible de charger le workspace.')
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadWorkspaceData()
})
</script>