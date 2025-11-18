<!-- resources/js/pages/ActiviteDetail.vue - VERSION CORRIGÉE AVEC PERMISSIONS -->
<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="'Détail de l\'activité'" />
    <div class="min-h-screen bg-gray-50/30 dark:bg-gray-900/50 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="space-y-6" v-if="!loading && activite">
          <!-- Header -->
          <div class="space-y-6">
            <nav class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
              <button @click="$router.back()"
                class="flex items-center space-x-1 hover:text-gray-700 dark:hover:text-gray-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Activités</span>
              </button>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
              <router-link :to="`/projets/${activite.projet?.id}`" class="hover:text-gray-700 dark:hover:text-gray-300">
                {{ activite.projet?.nom }}
              </router-link>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
              <span class="text-gray-900 dark:text-white font-medium">{{ activite.nom }}</span>
            </nav>

            <div class="flex flex-col space-y-4 sm:flex-row sm:items-start sm:justify-between sm:space-y-0">
              <div class="space-y-3">
                <div class="flex items-center space-x-3">
                  <div class="w-3 h-8 rounded-full" :style="{ backgroundColor: activite.couleur || '#3b82f6' }"></div>
                  <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ activite.nom }}
                  </h1>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                    :class="getStatusClasses(activite.status)">
                    <span class="w-2 h-2 rounded-full mr-2" :class="getStatusDotClass(activite.status)"></span>
                    {{ getStatusLabel(activite.status) }}
                  </span>

                  <span class="text-sm text-gray-500 dark:text-gray-400 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    {{ formatDate(activite.created_at) }}
                  </span>
                </div>
              </div>

              <!-- Actions dans le header -->
              <div class="flex items-center space-x-3">
                <button 
                  v-if="canEdit" 
                  @click="editActivite(activite)" 
                  class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800"
                >
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                  Modifier
                </button>
               
                <button 
                  v-if="canManageMembers" 
                  @click="openMembersModal"
                  class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
                >
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                  Gérer l'équipe
                </button>
              </div> 
            </div>
          </div>

          <!-- Stats Cards -->
          <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Tâches totales</p>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ stats.totalTaches }}</p>
                </div>
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                  <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                </div>
              </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Terminées</p>
                  <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ stats.tachesTerminees }}</p>
                </div>
                <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                  <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
              </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Progression</p>
                  <p class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-1">
                    {{ stats.progression }}%
                  </p>
                </div>
                <div class="p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                  <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                  </svg>
                </div>
              </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Membres</p>
                  <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">
                    {{ activite.membres?.length || 0 }}
                  </p>
                </div>
                <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                  <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                </div>
              </div>
            </div>
          </div>

          <div class="grid gap-8 lg:grid-cols-3">
            <!-- Contenu principal -->
            <div class="lg:col-span-2 space-y-8">
              <!-- Description -->
              <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                  <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  Description
                </h3>
                <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                  {{ activite.description || 'Aucune description fournie.' }}
                </p>
              </div>

              <!-- Progression détaillée -->
              <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                  <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                  </svg>
                  Progression globale
                </h3>

                <div class="space-y-6">
                  <!-- Barre de progression principale -->
                  <div class="space-y-2">
                    <div class="flex items-center justify-between">
                      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Avancement</span>
                      <span class="text-2xl font-bold text-blue-600">{{ stats.progression }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                      <div
                        class="bg-gradient-to-r from-blue-500 to-blue-600 h-4 rounded-full transition-all duration-500 flex items-center justify-end pr-2"
                        :style="{ width: `${stats.progression}%` }">
                        <span v-if="stats.progression > 10" class="text-xs font-medium text-white">
                          {{ stats.progression }}%
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Statistiques détaillées -->
                  <div class="grid gap-4 sm:grid-cols-3">
                    <div
                      class="text-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-red-50 dark:bg-red-900/10">
                      <div class="text-3xl font-bold text-red-600">{{ stats.tachesAFaire }}</div>
                      <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">À faire</div>
                    </div>

                    <div
                      class="text-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-blue-50 dark:bg-blue-900/10">
                      <div class="text-3xl font-bold text-blue-600">{{ stats.tachesEnCours }}</div>
                      <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">En cours</div>
                    </div>

                    <div
                      class="text-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-green-50 dark:bg-green-900/10">
                      <div class="text-3xl font-bold text-green-600">{{ stats.tachesTerminees }}</div>
                      <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Terminées</div>
                    </div>
                  </div>

                  <!-- Taux de complétion -->
                  <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Taux de complétion
                    </span>
                    <span class="text-lg font-bold"
                      :class="stats.progression >= 80 ? 'text-green-600' : stats.progression >= 50 ? 'text-blue-600' : 'text-orange-600'">
                      {{ stats.tachesTerminees }}/{{ stats.totalTaches }} tâches
                    </span>
                  </div>
                </div>
              </div>

              <!-- Tâches récentes -->
              <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-6">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Tâches récentes
                  </h3>
                  <button @click="viewAllTasks"
                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                    Voir tout
                  </button>
                </div>

                <div v-if="taches.length > 0" class="space-y-3">
                  <div v-for="tache in recentTaches" :key="tache.id"
                    class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer"
                    @click="viewTask(tache.id)">
                    <div class="space-y-1 flex-1">
                      <div class="font-medium text-gray-900 dark:text-white">
                        {{ tache.titre }}
                      </div>
                      <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ tache.assignee?.nom || 'Non assignée' }}
                      </div>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="getTaskStatusClasses(tache.statut)">
                      {{ getTaskStatusLabel(tache.statut) }}
                    </span>
                  </div>
                </div>

                <div v-else class="text-center py-12 text-gray-500 dark:text-gray-400">
                  <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                  <p class="font-medium text-lg">Aucune tâche</p>
                  <p class="text-sm mt-1">Créez votre première tâche pour commencer</p>
                  
                  <!-- ✅ Message si viewer -->
                  <div v-if="userRole === 'viewer'" class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <p class="text-sm text-blue-700 dark:text-blue-300">
                      Vous êtes observateur : accès en lecture seule
                    </p>
                  </div>
                  
                  <button 
                    v-if="canCreateTasks" 
                    @click="createTask" 
                    class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                  >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Créer une tâche
                  </button>
                </div>
              </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-8">
              <!-- Informations -->
              <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                  <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Informations
                </h3>

                <div class="space-y-4">
                  <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Projet</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ activite.projet?.nom }}
                    </span>
                  </div>

                  <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Responsable</span>
                    <div class="flex items-center space-x-2">
                      <div
                        class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white text-xs font-bold">
                        {{ getInitials(activite.responsable?.nom) }}
                      </div>
                      <span class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ activite.responsable?.nom }}
                      </span>
                    </div>
                  </div>

                  <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Date début</span>
                    <span class="text-sm text-gray-900 dark:text-white">
                      {{ formatDate(activite.date_debut) }}
                    </span>
                  </div>

                  <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Date fin</span>
                    <span class="text-sm font-medium"
                      :class="isOverdue ? 'text-red-600' : 'text-gray-900 dark:text-white'">
                      {{ formatDate(activite.date_fin) }}
                    </span>
                  </div>

                  <div v-if="isOverdue"
                    class="flex items-center space-x-2 text-red-600 bg-red-50 dark:bg-red-900/20 p-3 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <span class="text-sm font-medium">En retard</span>
                  </div>
                </div>
              </div>

              <!-- Équipe AMÉLIORÉE -->
              <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-6">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Équipe ({{ activite.membres?.length || 0 }})
                  </h3>
                  <button 
                    v-if="canManageMembers" 
                    @click="openMembersModal"
                    class="inline-flex items-center gap-2 px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Gérer
                  </button>
                </div>

                <div v-if="activite.membres && activite.membres.length > 0" class="space-y-3">
                  <!-- Responsable -->
                  <div
                    class="flex items-center space-x-3 p-3 rounded-lg bg-gradient-to-r from-orange-50 to-yellow-50 dark:from-orange-900/10 dark:to-yellow-900/10 border border-orange-200 dark:border-orange-800">
                    <div
                      class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-500 to-yellow-500 flex items-center justify-center text-white text-sm font-bold">
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path
                          d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                      </svg>
                    </div>
                    <div class="flex-1">
                      <div class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ activite.responsable?.nom }}
                      </div>
                      <div class="text-xs text-orange-600 dark:text-orange-400 font-medium">
                        Responsable
                      </div>
                    </div>
                  </div>

                  <!-- Membres avec permissions détaillées -->
                  <div v-for="member in otherMembers" :key="member.id"
                    class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group">
                    <div class="flex items-center space-x-3 flex-1">
                      <div
                        class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white text-sm font-bold">
                        {{ getInitials(member.nom) }}
                      </div>
                      <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                          {{ member.nom }}
                        </div>
                        <div class="flex items-center space-x-2 mt-1">
                          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                            :class="getRoleBadgeClass(member.role)">
                            {{ getRoleLabel(member.role) }}
                          </span>

                          <!-- Badges des permissions -->
                          <div class="flex items-center space-x-1">
                            <span v-if="member.permissions.can_create_tasks"
                              class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300"
                              title="Peut créer des tâches">
                              Créer
                            </span>
                            <span v-if="member.permissions.can_edit_tasks"
                              class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300"
                              title="Peut modifier des tâches">
                              Éditer
                            </span>
                            <span v-if="member.permissions.can_validate_results"
                              class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-300"
                              title="Peut valider les résultats N1">
                              Valider
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- ✅ Bouton retirer seulement si permission -->
                    <button 
                      v-if="canManageMembers" 
                      @click="removeMember(member)"
                      class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-600 transition-all duration-200"
                      title="Retirer de l'activité"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>
                </div>

                <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                  <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                  <p class="text-sm font-medium mb-2">Aucun membre assigné</p>
                  <button 
                    v-if="canManageMembers" 
                    @click="openMembersModal"
                    class="inline-flex items-center px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                  >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Ajouter des membres
                  </button>
                </div>
              </div>

              <!-- Actions rapides -->
              <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Actions rapides</h3>

                <div class="space-y-3">
                  <button 
                    v-if="canCreateTasks"
                    @click="createTask"
                    class="w-full flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
                  >
                    <div class="flex items-center">
                      <div class="p-2 bg-green-100 dark:bg-green-900/20 rounded-lg mr-3 group-hover:bg-green-200 dark:group-hover:bg-green-900/30">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                      </div>
                      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Créer une tâche</span>
                    </div>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                  </button>

                  <button 
                    @click="viewAllTasks"
                    class="w-full flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
                  >
                    <div class="flex items-center">
                      <div class="p-2 bg-blue-100 dark:bg-blue-900/20 rounded-lg mr-3 group-hover:bg-blue-200 dark:group-hover:bg-blue-900/30">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                      </div>
                      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Voir toutes les tâches</span>
                    </div>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                  </button>

                  <button 
                    v-if="canManageMembers" 
                    @click="openMembersModal"
                    class="w-full flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
                  >
                    <div class="flex items-center">
                      <div
                        class="p-2 bg-purple-100 dark:bg-purple-900/20 rounded-lg mr-3 group-hover:bg-purple-200 dark:group-hover:bg-purple-900/30">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                      </div>
                      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Gérer les membres</span>
                    </div>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                  </button>

                  <!-- Message si viewer -->
                  <div v-if="userRole === 'viewer'" class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <p class="text-sm text-blue-700 dark:text-blue-300 flex items-center">
                      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Vous êtes observateur : accès en lecture seule
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-else-if="loading" class="flex items-center justify-center min-h-[400px]">
          <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-4 text-gray-500 dark:text-gray-400">Chargement de l'activité...</p>
          </div>
        </div>

        <!-- Error State -->
        <div v-else class="flex items-center justify-center min-h-[400px]">
          <div class="text-center">
            <svg class="w-16 h-16 mx-auto text-red-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8v4m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <p class="text-gray-900 dark:text-white font-medium text-lg">Activité introuvable</p>
            <button @click="$router.back()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
              Retour
            </button>
          </div>
        </div>
      </div>

      <!-- Modals -->
      <ActiviteForm
        v-if="showEditForm"
        :activite="editingActivite"
        @close="showEditForm = false; editingActivite = null"
        @saved="onActiviteUpdated"
      />

      <!-- ✅ Modaux avec gestion correcte -->
      <ManageMembersModal
        v-if="showMembersModal"
        :activite="activite"
        @close="showMembersModal = false"
        @updated="onMembersUpdated"
        @edit-member="onEditMember"
        @add-member="onAddMember"
      />

      <EditMemberPermissionsModal
        v-if="showEditMemberModal"
        :member="editingMember"
        :activite-id="activite?.id"
        @close="showEditMemberModal = false"
        @updated="onMemberPermissionsUpdated"
      />

      <AddMemberModal
        v-if="showAddMemberModal"
        :show="showAddMemberModal"
        :activite-id="activite?.id"
        :projet-id="activite?.projet_id"
        @close="showAddMemberModal = false"
        @member-added="onMemberAdded"
      />
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/api/axios'
import { useAuthStore } from '@/stores/auth'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ActiviteForm from '@/components/activites/ActiviteForm.vue'
import ManageMembersModal from '@/components/activites/ManageMembersModal.vue'
import EditMemberPermissionsModal from '@/components/activites/EditMemberPermissionsModal.vue'
import AddMemberModal from '@/components/activites/AddMemberModal.vue'
import { useActivityPermissions } from '@/composables/useActivityPermissions'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const loading = ref(true)
const activite = ref(null)
const taches = ref([])
const showMembersModal = ref(false)
const showEditMemberModal = ref(false)
const showAddMemberModal = ref(false)
const editingActivite = ref(null)
const showEditForm = ref(false)
const editingMember = ref(null)

// ✅ Utiliser le composable pour les permissions
const {
  canEdit,
  canDelete,
  canManageMembers,
  canCreateTasks,
  canEditTasks,
  canDeleteTasks,
  canValidateResults,
  canAssignUsers,
  userRole,
  isSuperAdmin,
  isActivityResponsable,
  isProjectResponsable,
  getPermissionDeniedMessage
} = useActivityPermissions(activite)

// Computed properties
const recentTaches = computed(() => {
  return taches.value
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    .slice(0, 5)
})

const otherMembers = computed(() => {
  if (!activite.value?.membres) return []
  return activite.value.membres.filter(member =>
    member.id !== activite.value.responsable_id
  )
})

const stats = computed(() => {
  if (!taches.value.length) {
    return {
      totalTaches: 0,
      tachesTerminees: 0,
      tachesEnCours: 0,
      tachesAFaire: 0,
      progression: activite.value?.progression || 0
    }
  }

  const total = taches.value.length
  const terminees = taches.value.filter(t => t.statut === 'termine').length
  const enCours = taches.value.filter(t => t.statut === 'en_cours').length
  const aFaire = taches.value.filter(t => t.statut === 'a_faire' || t.statut === 'en_attente').length

  return {
    totalTaches: total,
    tachesTerminees: terminees,
    tachesEnCours: enCours,
    tachesAFaire: aFaire,
    progression: total > 0 ? Math.round((terminees / total) * 100) : 0
  }
})

const isOverdue = computed(() => {
  if (!activite.value?.date_fin) return false
  return new Date(activite.value.date_fin) < new Date() &&
    !['terminee', 'annulee'].includes(activite.value.status)
})

// Helper functions
const getInitials = (name) => {
  if (!name) return '??'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const getStatusClasses = (status) => {
  const classes = {
    'active': 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300',
    'archived': 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-300',
    'planifiee': 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300',
    'en_cours': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300',
    'terminee': 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-300'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusDotClass = (status) => {
  const classes = {
    'active': 'bg-green-500',
    'archived': 'bg-gray-500',
    'planifiee': 'bg-blue-500',
    'en_cours': 'bg-yellow-500',
    'terminee': 'bg-purple-500'
  }
  return classes[status] || 'bg-gray-500'
}

const getStatusLabel = (status) => {
  const labels = {
    'active': 'Active',
    'archived': 'Archivée',
    'planifiee': 'Planifiée',
    'en_cours': 'En cours',
    'terminee': 'Terminée'
  }
  return labels[status] || status
}

const getTaskStatusClasses = (status) => {
  const classes = {
    'a_faire': 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-300',
    'en_attente': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300',
    'en_cours': 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300',
    'termine': 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getTaskStatusLabel = (status) => {
  const labels = {
    'a_faire': 'À faire',
    'en_attente': 'En attente',
    'en_cours': 'En cours',
    'termine': 'Terminé'
  }
  return labels[status] || status
}

const getRoleBadgeClass = (role) => {
  const classes = {
    'responsable': 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-300',
    'collaborator': 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300',
    'viewer': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
  }
  return classes[role] || 'bg-gray-100 text-gray-800'
}

const getRoleLabel = (role) => {
  const labels = {
    'responsable': 'Responsable',
    'collaborator': 'Collaborateur',
    'viewer': 'Observateur'
  }
  return labels[role] || role
}

const formatDate = (dateString) => {
  if (!dateString) return 'Non définie'
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

// Actions
const loadActivite = async () => {
  loading.value = true
  try {
    const response = await api.get(`/activites/${route.params.id}`)
    activite.value = response.data.data

    // Charger les tâches
    await loadTaches()
  } catch (error) {
    console.error('Erreur lors du chargement de l\'activité:', error)
  } finally {
    loading.value = false
  }
}

const loadTaches = async () => {
  try {
    const response = await api.get(`/activites/${activite.value.id}/taches`)
    taches.value = response.data.data || []
  } catch (error) {
    console.error('Erreur lors du chargement des tâches:', error)
    taches.value = []
  }
}

const editActivite = (activite) => {
  if (!canEdit.value) {
    alert(getPermissionDeniedMessage('edit'))
    return
  }

  editingActivite.value = activite
  showEditForm.value = true
}

const onActiviteUpdated = () => {
  showEditForm.value = false
  editingActivite.value = null
  loadActivite()
}

// ✅ Actions avec vérification
const createTask = () => {
  if (!canCreateTasks.value) {
    alert(getPermissionDeniedMessage('create_tasks'))
    return
  }
  router.push(`/activites/${activite.value.id}/taches/create`)
}

const viewAllTasks = () => {
  router.push(`/activites/${activite.value.id}/taches`)
}

const viewTask = (taskId) => {
  router.push(`/taches/${taskId}`)
}

// ✅ Gestion des membres
const openMembersModal = () => {
  if (!canManageMembers.value) {
    alert(getPermissionDeniedMessage('manage_members'))
    return
  }
  showMembersModal.value = true
}

const onEditMember = (member) => {
  editingMember.value = member
  showEditMemberModal.value = true
}

const onAddMember = () => {
  showAddMemberModal.value = true
}

const onMemberPermissionsUpdated = () => {
  showEditMemberModal.value = false
  editingMember.value = null
  loadActivite()
}

const onMemberAdded = () => {
  showAddMemberModal.value = false
  loadActivite()
}

const onMembersUpdated = () => {
  showMembersModal.value = false
  loadActivite()
}

const removeMember = async (member) => {
  if (!canManageMembers.value) {
    alert(getPermissionDeniedMessage('manage_members'))
    return
  }

  if (!confirm(`Êtes-vous sûr de vouloir retirer ${member.nom} de cette activité ?`)) {
    return
  }

  try {
    await api.delete(`/activites/${activite.value.id}/members/${member.id}`)
    await loadActivite()
  } catch (error) {
    console.error('Erreur lors du retrait du membre:', error)
    alert('Erreur lors du retrait du membre')
  }
}

onMounted(() => {
  loadActivite()
})
</script>