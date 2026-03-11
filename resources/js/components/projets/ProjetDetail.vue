<!-- resources/js/components/projets/ProjetDetail.vue - -->
<template>
  <div class="space-y-6">
    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-600"></div>
    </div>

    <template v-else-if="projet">
      <!-- Header du projet et tabs -->
      <div>
        <!-- Header existant du projet -->
        <div
          class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
          <!-- Banner avec couleur du projet -->
          <div class="h-32" :style="{ backgroundColor: projet.couleur || '#3B82F6' }"></div>

          <div class="px-6 py-4">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                  <button @click="$emit('back')"
                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <ChevronLeftIcon class="w-5 h-5" />
                  </button>
                  <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ projet.code }}
                  </span>
                  <StarIcon v-if="projet.is_favorite" class="w-5 h-5 fill-yellow-400 text-yellow-400" />
                </div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                  {{ projet.nom }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                  {{ projet.description }}
                </p>
              </div>

              <div class="flex items-center gap-2 ml-4">
                <button v-if="canEditProjet" @click.stop="editProjet(projet)"
                  class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-t-lg">
                  <EditIcon class="w-4 h-4" />
                  Modifier
                </button>

                <button
                  class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors">
                  <SettingsIcon class="w-4 h-4" />
                  Paramètres
                </button>
                <span v-if="isWorkspaceOwner"
                  class="ml-2 inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                  </svg>
                  Propriétaire du workspace
                </span>
              </div>
            </div>

            <!-- Metadata -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
              <div class="flex items-center gap-3">
                <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                  <CalendarIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                </div>
                <div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">Début</div>
                  <div class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ formatDate(projet.date_debut) }}
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                  <CalendarIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                </div>
                <div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">Fin</div>
                  <div class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ formatDate(projet.date_fin) }}
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                  <UsersIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                </div>
                <div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">Responsable</div>
                  <div class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ projet.responsable?.nom }}
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                  <component :is="getStatusIcon(projet.status)" class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                </div>
                <div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">Statut</div>
                  <div>
                    <span :class="[
                      'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                      getStatusColor(projet.status)
                    ]">
                      {{ getStatusLabel(projet.status) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
          <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
              <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" :class="[
                activeTab === tab.id
                  ? 'border-brand-500 text-brand-600 dark:text-brand-400'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2'
              ]">
                <component :is="tab.icon" class="w-5 h-5" />
                {{ tab.label }}
                <span v-if="tab.count" :class="[
                  'ml-2 py-0.5 px-2 rounded-full text-xs font-medium',
                  activeTab === tab.id
                    ? 'bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400'
                    : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                ]">
                  {{ tab.count }}
                </span>
              </button>
            </nav>
          </div>

          <!-- Tab Content -->
          <div class="p-6">

            <!-- Overview Tab -->
            <div v-if="activeTab === 'overview'" class="space-y-6">
              <!-- Stats -->
              <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                  <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ projectStats.activites_count || 0 }}
                  </div>
                  <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Activités totales
                  </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                  <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ displayedActivities.length }}
                  </div>
                  <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Mes activités
                  </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                  <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ projectStats.taches_terminees || 0 }}
                  </div>
                  <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Complétées
                  </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                  <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ projet.member_count || 0 }}
                  </div>
                  <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Membres du projet disponibles
                  </div>
                </div>
              </div>

              <!-- Progress -->
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Progression globale
                  </span>
                  <span class="text-sm font-bold text-gray-900 dark:text-white">
                    {{ projet.progression || 0 }}%
                  </span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                  <div class="bg-brand-600 h-3 rounded-full transition-all duration-500"
                    :style="{ width: `${projet.progression || 0}%` }"></div>
                </div>
              </div>

              <!-- Objectifs -->
              <div v-if="projet.objectifs">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                  Objectifs
                </h3>
                <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">
                  {{ projet.objectifs }}
                </p>
              </div>

              <!-- Budget -->
              <div v-if="projet.budget"
                class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium text-blue-900 dark:text-blue-300">
                    Budget alloué
                  </span>
                  <span class="text-lg font-bold text-blue-900 dark:text-blue-300">
                    {{ formatCurrency(projet.budget) }} XAF
                  </span>
                </div>
              </div>
            </div>

            <!-- Activities Tab -->
            <div v-if="activeTab === 'activities'" class="space-y-4">
              <!-- Header avec bouton de création -->
              <div class="flex items-center justify-between mb-6">
                <div>
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Mes activités dans ce projet
                  </h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Activités où vous êtes responsable ou membre
                  </p>
                </div>

                <!-- Bouton Nouvelle Activité -->
                <button v-if="canCreateActivities" @click="showCreateActivityModal = true"
                  class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors shadow-sm">
                  <PlusIcon class="w-4 h-4" />
                  Nouvelle activité
                </button>
              </div>

              <!-- Filtre d'accès -->
              <div v-if="showAccessFilter"
                class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                      viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                      <p class="text-sm font-medium text-blue-900 dark:text-blue-300">
                        Filtrage des activités
                      </p>
                      <p class="text-xs text-blue-700 dark:text-blue-400">
                        {{ showAllActivities ? 'Affichage de toutes les activités du projet' : 'Affichage uniquement des activités où vous êtes responsable ou membre' }}
                      </p>
                    </div>
                  </div>
                  <button v-if="canViewAllActivities" @click="toggleViewAllActivities"
                    class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline">
                    {{ showAllActivities ? 'Voir mes activités seulement' : 'Voir toutes les activités' }}
                  </button>
                </div>
              </div>

              <!-- État vide -->
              <div v-if="displayedActivities.length === 0"
                class="text-center py-12 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                <ListIcon class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                  {{ showAllActivities ? 'Aucune activité dans ce projet' : 'Aucune activité accessible' }}
                </h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                  {{ showAllActivities
                    ? 'Ce projet ne contient aucune activité.'
                    : 'Vous n\'êtes pas membre ou responsable d\'activités dans ce projet.'
                  }}
                </p>
                <button v-if="!showAllActivities && canViewAllActivities" @click="toggleViewAllActivities"
                  class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                  Voir toutes les activités du projet
                </button>
                <button v-else-if="canCreateActivities" @click="showCreateActivityModal = true"
                  class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors">
                  <PlusIcon class="w-4 h-4" />
                  Créer une activité
                </button>
              </div>

              <!-- Liste des activités - STYLE SIMILAIRE À MES ACTIVITÉS -->
              <div v-else class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="overflow-x-auto">
                  <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700">
                      <tr>
                        <th class="px-6 py-3">Activité</th>
                        <th class="px-6 py-3">Responsable</th>
                        <th class="px-6 py-3">Équipe</th>
                        <th class="px-6 py-3">Statut</th>
                        <th class="px-6 py-3">Progression</th>
                        <th class="px-6 py-3">Date fin</th>
                        <th class="px-6 py-3">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="activity in displayedActivities" :key="activity.id"
                        class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <!-- Activité -->
                        <td class="px-6 py-4">
                          <div class="cursor-pointer" @click="navigateToActivityDetail(activity)">
                            <div class="font-medium text-gray-900 dark:text-white hover:text-brand-600">
                              {{ activity.nom }}
                            </div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs">{{ activity.code }}</div>
                          </div>
                        </td>

                        <!-- Responsable -->
                        <td class="px-6 py-4">
                          <div v-if="activity.responsable" class="flex items-center gap-2">
                            <div
                              class="w-6 h-6 rounded-full bg-brand-500 flex items-center justify-center text-white text-xs font-medium">
                              {{ getInitials(activity.responsable.nom) }}
                            </div>
                            <span class="text-gray-700 dark:text-gray-300">{{ activity.responsable.nom }}</span>
                          </div>
                          <span v-else class="text-gray-500 text-sm">Non assigné</span>
                        </td>

                        <!-- Équipe -->
                        <td class="px-6 py-4">
                          <button v-if="getActivityPermissions(activity).canManageMembers"
                            @click.stop="openActivityMembersModal(activity)"
                            class="flex items-center gap-2 px-3 py-1.5 text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded-lg hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors">
                            <UsersIcon class="w-4 h-4" />
                            {{ activity.membres_count || 0 }} membre(s)
                          </button>
                          <div v-else class="flex items-center gap-2 px-3 py-1.5 text-xs text-gray-500">
                            <UsersIcon class="w-4 h-4" />
                            {{ activity.membres_count || 0 }} membre(s)
                          </div>
                        </td>

                        <!-- Statut -->
                        <td class="px-6 py-4">
                          <span
                            :class="['px-2 py-1 text-xs font-medium rounded-full', getActivityStatusClass(activity.status)]">
                            {{ getActivityStatusLabel(activity.status) }}
                          </span>
                        </td>

                        <!-- Progression -->
                        <td class="px-6 py-4">
                          <div class="flex items-center gap-2 min-w-32">
                            <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                              <div class="h-full bg-brand-500 transition-all duration-500"
                                :style="{ width: `${activity.progression || 0}%` }"></div>
                            </div>
                            <span class="text-xs text-gray-600 dark:text-gray-400 min-w-8">
                              {{ activity.progression || 0 }}%
                            </span>
                          </div>
                        </td>

                        <!-- Date fin -->
                        <td class="px-6 py-4">
                          <span :class="activity.is_overdue ? 'text-red-600 font-medium' : 'text-gray-600'">
                            {{ activity.date_fin ? formatDate(activity.date_fin) : '-' }}
                          </span>
                          <div v-if="activity.is_overdue" class="text-xs text-red-500 mt-1">
                            En retard
                          </div>
                        </td>

                        <!-- Actions -->
                        <td class="px-6 py-4">
                          <div class="flex items-center gap-2">
                            <button @click="navigateToActivityDetail(activity)"
                              class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg"
                              title="Voir">
                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                              </svg>
                            </button>

                            <button v-if="getActivityPermissions(activity).canEdit" @click.stop="editActivity(activity)"
                              class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg"
                              title="Modifier">
                              <EditIcon class="w-4 h-4" />
                            </button>

                            <button v-if="getActivityPermissions(activity).canDelete"
                              @click.stop="deleteActivity(activity)"
                              class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"
                              title="Supprimer">
                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                              </svg>
                            </button>

                            <!-- Indicateur si aucune action disponible -->
                            <span
                              v-if="!getActivityPermissions(activity).canEdit && !getActivityPermissions(activity).canDelete"
                              class="text-xs text-gray-400 px-2">
                              Lecture seule
                            </span>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Members Tab - VERSION CORRIGÉE -->
            <div v-if="activeTab === 'members'" class="space-y-4">
              <div class="flex items-center justify-between mb-4">
                <div>
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Membres du projet ({{ members.length }})
                  </h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Gestion des membres et de leurs permissions
                  </p>
                </div>

                <button v-if="canManageMembers" @click="showInviteModal = true"
                  class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors">
                  <PlusIcon class="w-4 h-4" /> Inviter des membres
                </button>
              </div>

              <!-- Debug des permissions -->
              <!-- <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h4 class="font-medium text-blue-800">Structure des données membres:</h4>
                <pre class="text-xs mt-2">{{ JSON.stringify(members, null, 2) }}</pre>
              </div> -->

              <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="overflow-x-auto">
                  <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700">
                      <tr>
                        <th class="px-6 py-3">Membre</th>
                        <th class="px-6 py-3">Rôle</th>
                        <th class="px-6 py-3">Permissions</th>
                        <th class="px-6 py-3">Statut</th>
                        <th v-if="canManageMembers" class="px-6 py-3 text-right">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="member in members" :key="member.id"
                        class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="flex items-center gap-3">
                            <div v-if="member.avatar" class="w-8 h-8 rounded-full overflow-hidden">
                              <img :src="member.avatar" :alt="member.nom" class="w-full h-full object-cover" />
                            </div>
                            <div v-else
                              class="w-8 h-8 rounded-full bg-brand-600 flex items-center justify-center text-white text-xs font-medium">
                              {{ getInitials(member.nom) }}
                            </div>
                            <div>
                              <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ member.nom }}
                                <span v-if="member.id === projet.responsable_id" class="ml-1 text-purple-600">★</span>
                              </div>
                              <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ member.email }}
                              </div>
                            </div>
                          </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <!-- ✅ CORRECTION: Utiliser member.role au lieu de member.pivot.role -->
                          <span :class="[
                            'px-2 py-1 rounded-full text-xs font-medium',
                            getRoleColor(member.role)
                          ]">
                            {{ getRoleLabel(member.role) }}
                          </span>
                          <div v-if="member.id === projet.responsable_id" class="text-xs text-purple-600 mt-1">
                            Responsable projet
                          </div>
                        </td>
                        <td class="px-6 py-4">
                          <div class="flex flex-wrap gap-1">
                            <!-- Permissions projet -->
                            <span v-if="member.can_edit === true || member.can_edit === 1"
                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                              Éditer projet
                            </span>
                            <span v-if="member.can_delete === true || member.can_delete === 1"
                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                              Supprimer projet
                            </span>
                            <span v-if="member.can_invite === true || member.can_invite === 1"
                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                              Inviter membres
                            </span>
                            <span v-if="member.can_delete_member === true || member.can_delete_member === 1"
                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">
                              Retirer membres
                            </span>

                            <!-- ✅ Permissions activités -->
                            <span v-if="member.can_create_activity === true || member.can_create_activity === 1"
                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                              <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 4v16m8-8H4" />
                              </svg>
                              Créer activités
                            </span>
                            <span v-if="member.can_edit_activity === true || member.can_edit_activity === 1"
                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400">
                              <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                              </svg>
                              Éditer activités
                            </span>
                            <span v-if="member.can_delete_activity === true || member.can_delete_activity === 1"
                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                              <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                              </svg>
                              Supprimer activités
                            </span>

                            <!-- Aucune permission -->
                            <span v-if="!hasAnyPermission(member)"
                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                              Lecture seule
                            </span>
                          </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <span class="text-xs text-gray-500 dark:text-gray-400">
                            Ajouté le {{ formatDateTime(member.joined_at) }}
                          </span>
                        </td>
                        <td v-if="canManageMembers" class="px-6 py-4 whitespace-nowrap text-right">
                          <div class="flex items-center justify-end gap-2">
                            <button v-if="canEditMember(member)" @click="editMember(member)"
                              class="text-brand-600 hover:text-brand-900 dark:text-brand-400 dark:hover:text-brand-300 text-sm font-medium">
                              Modifier
                            </button>

                            <button v-if="canRemoveMember(member)" @click="removeMember(member)"
                              class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium">
                              Retirer
                            </button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Invitations Tab -->
            <div v-if="activeTab === 'invitations'" class="space-y-4">
              <div class="flex items-center justify-between mb-4">
                <div>
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Invitations en attente ({{ pendingInvitations.length }})
                  </h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Gestion des invitations envoyées aux membres
                  </p>
                </div>
              </div>

              <!-- Loading -->
              <div v-if="loadingInvitations" class="flex justify-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-600"></div>
              </div>

              <!-- État vide -->
              <div v-else-if="pendingInvitations.length === 0"
                class="text-center py-12 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                  Aucune invitation en attente
                </h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                  Toutes les invitations ont été acceptées, refusées ou ont expiré.
                </p>
              </div>

              <!-- Liste des invitations -->
              <div v-else class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="overflow-x-auto">
                  <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700">
                      <tr>
                        <th class="px-6 py-3">Invité</th>
                        <th class="px-6 py-3">Rôle</th>
                        <th class="px-6 py-3">Permissions</th>
                        <th class="px-6 py-3">Invité par</th>
                        <th class="px-6 py-3">Date d'envoi</th>
                        <th class="px-6 py-3">Expire le</th>
                        <th class="px-6 py-3">Statut</th>
                        <th v-if="canManageMembers" class="px-6 py-3 text-right">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="invitation in pendingInvitations" :key="invitation.id"
                        class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <!-- Invité -->
                        <td class="px-6 py-4">
                          <div class="flex items-center gap-3">
                            <div
                              class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300 text-xs font-medium">
                              {{ getInitials(invitation.email) }}
                            </div>
                            <div>
                              <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ invitation.email }}
                              </div>
                              <div v-if="invitation.user_id" class="text-xs text-gray-500 dark:text-gray-400">
                                Utilisateur existant
                              </div>
                              <div v-else class="text-xs text-orange-600 dark:text-orange-400">
                                Nouveau compte requis
                              </div>
                            </div>
                          </div>
                        </td>

                        <!-- Rôle -->
                        <td class="px-6 py-4">
                          <span :class="[
                            'px-2 py-1 rounded-full text-xs font-medium',
                            getRoleColor(invitation.role)
                          ]">
                            {{ getRoleLabel(invitation.role) }}
                          </span>
                        </td>

                        <!-- Permissions -->
                        <td class="px-6 py-4">
                          <div class="flex flex-wrap gap-1">
                            <span v-if="invitation.can_edit"
                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                              Éditer
                            </span>
                            <span v-if="invitation.can_delete"
                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                              Supprimer
                            </span>
                            <span v-if="invitation.can_invite"
                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                              Inviter
                            </span>
                            <span v-if="invitation.can_delete_member"
                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">
                              Retirer membres
                            </span>
                            <span
                              v-if="!invitation.can_edit && !invitation.can_delete && !invitation.can_invite && !invitation.can_delete_member"
                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                              Lecture seule
                            </span>
                          </div>
                        </td>

                        <!-- Invité par -->
                        <td class="px-6 py-4">
                          <div class="flex items-center gap-2">
                            <div
                              class="w-6 h-6 rounded-full bg-brand-500 flex items-center justify-center text-white text-xs font-medium">
                              {{ getInitials(invitation.invited_by?.nom || 'U') }}
                            </div>
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                              {{ invitation.invited_by?.nom || 'Inconnu' }}
                            </span>
                          </div>
                        </td>

                        <!-- Date d'envoi -->
                        <td class="px-6 py-4">
                          <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ formatDateTime(invitation.created_at) }}
                          </span>
                        </td>

                        <!-- Expiration -->
                        <td class="px-6 py-4">
                          <div>
                            <span :class="[
                              'text-xs',
                              isExpiringSoon(invitation.expires_at)
                                ? 'text-red-600 dark:text-red-400 font-medium'
                                : 'text-gray-500 dark:text-gray-400'
                            ]">
                              {{ formatDateTime(invitation.expires_at) }}
                            </span>
                            <div v-if="isExpiringSoon(invitation.expires_at)" class="text-xs text-red-500 mt-1">
                              ⚠️ Expire bientôt
                            </div>
                          </div>
                        </td>

                        <!-- Statut -->
                        <td class="px-6 py-4">
                          <span
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            En attente
                          </span>
                        </td>

                        <!-- Actions -->
                        <td v-if="canManageMembers" class="px-6 py-4 text-right">
                          <div class="flex items-center justify-end gap-2">
                            <button @click="resendInvitation(invitation)"
                              :disabled="resendingInvitation === invitation.id"
                              class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium disabled:opacity-50"
                              title="Renvoyer l'invitation">
                              <svg v-if="resendingInvitation === invitation.id" class="animate-spin h-4 w-4" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                  stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                              </svg>
                              <span v-else>Renvoyer</span>
                            </button>

                            <button @click="cancelInvitation(invitation)"
                              :disabled="cancelingInvitation === invitation.id"
                              class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium disabled:opacity-50"
                              title="Annuler l'invitation">
                              <span v-if="cancelingInvitation === invitation.id">Annulation...</span>
                              <span v-else>Annuler</span>
                            </button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Modaux - SECTION CORRIGÉE -->
    <ActiviteForm v-if="showCreateActivityModal" :activite="null" :projet-id="projetId"
      @close="showCreateActivityModal = false" @saved="handleActivityCreated" />

    <ActiviteForm v-if="showEditActivityModal" :activite="selectedActivity" :projet-id="projetId"
      @close="showEditActivityModal = false" @saved="handleActivityUpdated" />

    <InviteExternalMemberModal v-if="showInviteModal" :projet-id="projetId" @close="showInviteModal = false"
      @invited="handleInvited" />

    <EditProjetMemberModal v-if="showEditProjetMemberModal" :membre="selectedMember" :projet-id="projetId"
      @close="showEditProjetMemberModal = false" @updated="handleMemberUpdated" />

    <RemoveMemberWithTransferModal v-if="showRemoveMemberModal" :member="memberToRemove"
      :workspace-id="projet.workspace_id" :projet-id="projetId" context="projet" @close="showRemoveMemberModal = false"
      @removed="handleMemberRemoved" />

    <ProjetFormModal v-if="showFormModal" :projet="selectedProjet" :workspace-id="workspaceId" @close="closeFormModal"
      @saved="handleProjetSaved" />

    <!-- ✅ CORRECTION 1: Modal pour gérer les membres d'activité -->
    <ManageMembersModal v-if="showActivityMembersModal" :activite="selectedActivityForMembers"
      :workspace-owner-id="projet?.workspace?.owner_id" :projet-responsable-id="projet?.responsable_id"
      @close="showActivityMembersModal = false" @updated="handleActivityMembersUpdated"
      @add-member="openAddMemberModalForActivity" @edit-member="onEditActivityMember" />

    <!-- ✅ CORRECTION 2: Modal pour ajouter des membres à une activité -->
    <AddMemberModal v-if="showAddMemberModal" :show="showAddMemberModal" :activite-id="selectedActivityForMembers?.id"
      :projet-id="selectedActivityForMembers?.projet_id" @close="showAddMemberModal = false"
      @members-added="handleMembersAddedToActivity" />
    <!-- Modal d’édition permissions membre (activité) -->
    <EditMemberPermissionsModal v-if="showEditActivityMemberModal" :member="editingActivityMember"
      :activite-id="selectedActivityForMembers?.id"
      @close="showEditActivityMemberModal = false; editingActivityMember = null"
      @updated="handleActivityMembersUpdated" />


  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useProjets } from '@/composables/useProjets'
import { useActivites } from '@/composables/useActivites'
import { useActivityPermissions } from '@/composables/useActivityPermissions'
import { useAuthStore } from '@/stores/auth'
import { useProjetInvitations } from '@/composables/useProjetInvitations'
import EditMemberPermissionsModal from '@/components/activites/EditMemberPermissionsModal.vue'

import {
  ChevronLeftIcon, EditIcon,
  SettingsIcon, CalendarIcon,
  UsersIcon, StarIcon,
  ListIcon, PlusIcon,
  CheckCircleIcon, TrendingUpIcon,
  ArchiveIcon, ClockIcon, MailIcon
} from '@/icons'

import ActiviteForm from '@/components/activites/ActiviteForm.vue'
import EditProjetMemberModal from './EditProjetMemberModal.vue'
import ProjetFormModal from './ProjetFormModal.vue'
import InviteExternalMemberModal from '@/components/projets/InviteExternalMemberModal.vue'
import RemoveMemberWithTransferModal from '@/components/projets/RemoveMemberWithTransferModal.vue'
import ManageMembersModal from '@/components/activites/ManageMembersModal.vue'
// ✅ CORRECTION 3: Import du composant AddMemberModal
import AddMemberModal from '@/components/activites/AddMemberModal.vue'
import { useToast } from "vue-toastification"


const props = defineProps({
  projetId: {
    type: Number,
    required: true
  },
  workspaceId: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['back', 'create-activity', 'view-activity'])

const router = useRouter()
const authStore = useAuthStore()
const { fetchProjet, removeMember: removeMemberService, fetchProjets } = useProjets()
const { deleteActivite: deleteActiviteService } = useActivites()

const loading = ref(false)
const projet = ref({})
const projectStats = ref({})
const activities = ref([])
const members = ref([])
const showAllActivities = ref(false)

// ✅ CORRECTION 4: États pour la gestion des activités
const selectedActivity = ref(null)
const showEditActivityModal = ref(false)
const showCreateActivityModal = ref(false)
const showActivityMembersModal = ref(false)
const showAddMemberModal = ref(false) // ← AJOUTÉ
const selectedActivityForMembers = ref(null)

// États pour la gestion des membres
const showEditProjetMemberModal = ref(false)
const showRemoveMemberModal = ref(false)
const showInviteModal = ref(false)
const selectedMember = ref(null)
const memberToRemove = ref(null)

const showEditActivityMemberModal = ref(false)
const editingActivityMember = ref(null)

// États pour la gestion du projet
const showFormModal = ref(false)
const selectedProjet = ref(null)

const activeTab = ref('overview')

// États pour la gestion des invitations
const {
  fetchInvitations,
  resendInvitation: resendInvitationService,
  cancelInvitation: cancelInvitationService
} = useProjetInvitations()

const toast = useToast()

const pendingInvitations = ref([])
const loadingInvitations = ref(false)
const resendingInvitation = ref(null)
const cancelingInvitation = ref(null)

// Charger les invitations en attente
const loadInvitations = async () => {
  try {
    loadingInvitations.value = true
    const invitations = await fetchInvitations(props.projetId)
    pendingInvitations.value = invitations || []
  } catch (error) {
    console.error('Error loading invitations:', error)
    pendingInvitations.value = []
        toast.error('Erreur lors du chargement des invitations')

  } finally {
    loadingInvitations.value = false
  }
}

// Renvoyer une invitation
const resendInvitation = async (invitation) => {
  if (!confirm(`Renvoyer l'invitation à ${invitation.email} ?`)) {
    return
  }

  try {
    resendingInvitation.value = invitation.id
    await resendInvitationService(props.projetId, invitation.id)

    toast.success(
      `L'invitation a été renvoyée avec succès`,
      'Invitation renvoyée',
      [`Email envoyé à ${invitation.email}`]
    )

    await loadInvitations()
  } catch (error) {
    console.error('Error resending invitation:', error)
    toast.error(
      error.response?.data?.message || 'Une erreur est survenue lors du renvoi',
      'Erreur'
    )
  } finally {
    resendingInvitation.value = null
  }
}

// Annuler une invitation
const cancelInvitation = async (invitation) => {
  if (!confirm(`Êtes-vous sûr de vouloir annuler l'invitation de ${invitation.email} ?`)) {
    return
  }

  try {
    cancelingInvitation.value = invitation.id
    await cancelInvitationService(props.projetId, invitation.id)

    toast.warning(
      `L'invitation a été annulée`,
      'Invitation annulée',
      [`${invitation.email} ne pourra plus accepter cette invitation`]
    )

    await loadInvitations()
  } catch (error) {
    console.error('Error canceling invitation:', error)
    toast.error(
      error.response?.data?.message || 'Une erreur est survenue lors de l\'annulation',
      'Erreur'
    )
  } finally {
    cancelingInvitation.value = null
  }
}

// Vérifier si une invitation expire bientôt (dans moins de 24h)
const isExpiringSoon = (expiresAt) => {
  if (!expiresAt) return false
  const now = new Date()
  const expiration = new Date(expiresAt)
  const hoursUntilExpiration = (expiration - now) / (1000 * 60 * 60)
  return hoursUntilExpiration < 24 && hoursUntilExpiration > 0
}





// ==================== COMPUTED PROPERTIES ====================

const tabs = computed(() => [
  { id: 'overview', label: 'Vue d\'ensemble', icon: TrendingUpIcon },
  { id: 'activities', label: 'Activités', icon: ListIcon, count: displayedActivities.value.length },
  { id: 'members', label: 'Membres', icon: UsersIcon, count: members.value.length },
  { id: 'invitations', label: 'Invitations', icon: MailIcon, count: pendingInvitations.value.length }
])

/**
 * ✅ Activités accessibles à l'utilisateur (membre ou responsable)
 */
const accessibleActivities = computed(() => {
  if (!activities.value.length || !authStore.user) return []

  return activities.value.filter(activity => {
    return isUserInActivity(activity)
  })
})

/**
 * ✅ Activités affichées (selon le filtre)
 */
const displayedActivities = computed(() => {
  if (isWorkspaceOwner.value) return activities.value
  return showAllActivities.value ? activities.value : accessibleActivities.value
})

/**
 * ✅ Vérifie si l'utilisateur peut voir toutes les activités
 */
const canViewAllActivities = computed(() => {
  const user = authStore.user
  if (!user || !projet.value) return false

  // Super admin peut tout voir
  if (user.is_super_admin) return true
  if (isWorkspaceOwner.value) return true

  // Responsable du projet peut tout voir
  if (projet.value.responsable_id === user.id) return true

  // Membre avec permissions étendues
  const userMember = projet.value.members?.find(m => m.id === user.id)
  return userMember?.pivot?.can_edit || false
})

/**
 * ✅ Vérifie si le filtre d'accès doit être affiché
 */
const showAccessFilter = computed(() => {
  return accessibleActivities.value.length !== activities.value.length && activities.value.length > 0
})

/**
 * ✅ Vérifie si l'utilisateur peut créer des activités
 */
const canCreateActivities = computed(() => {
  const user = authStore.user
  if (!user || !projet.value) return false

  // Super admin peut tout
  if (user.is_super_admin) return true

  // Responsable du projet peut tout
  if (projet.value.responsable_id === user.id) return true

  // Chercher l'utilisateur dans les membres
  const userMember = members.value.find(m => m.id === user.id)

  if (!userMember) return false

  // Admin du projet peut créer
  if (userMember.role === 'admin') return true

  // Vérifier la permission spécifique
  return userMember.can_create_activity === true || userMember.can_create_activity === 1
})


// ==================== MÉTHODES D'ACCÈS ====================

/**
 * ✅ Vérifie si l'utilisateur est dans l'activité (membre ou responsable)
 */
const isUserInActivity = (activity) => {
  const user = authStore.user
  if (!user) return false

  // Super admin a accès à tout
  if (user.is_super_admin) return true

  // Responsable de l'activité
  if (activity.responsable_id === user.id) return true

  // Membre de l'activité
  if (activity.membres?.some(membre => membre.id === user.id)) return true

  // Responsable du projet parent
  if (projet.value.responsable_id === user.id) return true

  return false
}


/**
 * ✅ CORRECTION: Obtient les permissions pour une activité spécifique
 * Utilise d'abord user_permissions, sinon vérifie dans les membres
 */
const getActivityPermissions = (activity) => {
  const user = authStore.user
  if (!user) {
    return getDefaultActivityPermissions()
  }

  if (isWorkspaceOwner.value) {
    return getFullActivityPermissions()
  }

  // ✅ PRIORITÉ: Utiliser user_permissions s'il existe (permissions calculées côté serveur)
  if (activity.user_permissions) {
    return {
      canEdit: activity.user_permissions.can_edit_activity || false,
      canDelete: activity.user_permissions.can_delete_activity || false,
      canManageMembers: activity.user_permissions.can_manage_members || false,
      canCreateTasks: activity.user_permissions.can_create_tasks || false,
      canEditTasks: activity.user_permissions.can_edit_tasks || false,
      canDeleteTasks: activity.user_permissions.can_delete_tasks || false,
      canValidateResults: activity.user_permissions.can_validate_results || false,
      canAssignUsers: activity.user_permissions.can_assign_users || false
    }
  }

  // ✅ Fallback: Super admin a tous les droits
  if (user.is_super_admin) {
    return getFullActivityPermissions()
  }

  // ✅ Fallback: Vérifier si l'utilisateur est dans les membres de l'activité
  const userMember = activity.membres?.find(m => m.id === user.id)

  if (userMember) {
    return {
      canEdit: userMember.permissions?.can_edit_activity || false,
      canDelete: userMember.permissions?.can_delete_activity || false,
      canManageMembers: userMember.permissions?.can_assign_users || false,
      canCreateTasks: userMember.permissions?.can_create_tasks || false,
      canEditTasks: userMember.permissions?.can_edit_tasks || false,
      canDeleteTasks: userMember.permissions?.can_delete_tasks || false,
      canValidateResults: userMember.permissions?.can_validate_results || false,
      canAssignUsers: userMember.permissions?.can_assign_users || false
    }
  }

  // ✅ Fallback: Responsable de l'activité a tous les droits
  if (activity.responsable_id === user.id) {
    return getFullActivityPermissions()
  }

  // ✅ Fallback: Responsable du projet a tous les droits sur les activités
  if (projet.value.responsable_id === user.id) {
    return getFullActivityPermissions()
  }

  return getDefaultActivityPermissions()
}

/**
 * ✅ Permissions complètes pour une activité
 */
const getFullActivityPermissions = () => {
  return {
    canEdit: true,
    canDelete: true,
    canManageMembers: true,
    canCreateTasks: true,
    canEditTasks: true,
    canDeleteTasks: true,
    canValidateResults: true,
    canAssignUsers: true
  }
}

/**
 * ✅ Permissions par défaut (aucun accès)
 */
const getDefaultActivityPermissions = () => {
  return {
    canEdit: false,
    canDelete: false,
    canManageMembers: false,
    canCreateTasks: false,
    canEditTasks: false,
    canDeleteTasks: false,
    canValidateResults: false,
    canAssignUsers: false
  }
}


/**
 * ✅ Bascule entre la vue "mes activités" et "toutes les activités"
 */
const toggleViewAllActivities = () => {
  showAllActivities.value = !showAllActivities.value
}

// ==================== MÉTHODES DE NAVIGATION ====================

const navigateToActivityDetail = (activity) => {
  console.log('Navigating to activity detail:', activity.id)
  router.push(`/activites/${activity.id}`)
}

const navigateToActivityTasks = (activity) => {
  console.log('Navigating to activity tasks:', activity.id)
  router.push(`/activites/${activity.id}/taches`)
}

// ==================== MÉTHODES DE GESTION DES ACTIVITÉS ====================

const editActivity = (activity) => {
  selectedActivity.value = activity
  showEditActivityModal.value = true
}

const deleteActivity = async (activity) => {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer "${activity.nom}" ?`)) {
    return
  }

  try {
    await deleteActiviteService(activity.id)
    await loadProjet()
    toast.success(`Activité "${activity.nom}" supprimée avec succès`)

  } catch (error) {
    console.error('Error deleting activity:', error)
    toast.error('Erreur lors de la suppression de l\'activité')
  }
}

const onMembersActivityUpdated = () => {
  showActivityMembersModal.value = false
  loadActivite()
}

const onAddActivityMember = () => {
  showAddMemberModal.value = true
}

const onEditActivityMember = (member) => {
  editingActivityMember.value = member
  showEditActivityMemberModal.value = true
}

// ✅ CORRECTION 5: Gestion du modal ManageMembersModal
const openActivityMembersModal = (activity) => {
  selectedActivityForMembers.value = activity
  showActivityMembersModal.value = true
}

const handleActivityMembersUpdated = () => {
  showActivityMembersModal.value = false
  loadProjet()
  toast.success('Membres de l\'activité mis à jour')

}

// ✅ CORRECTION 6: Nouvelle méthode pour ouvrir AddMemberModal depuis ManageMembersModal
const openAddMemberModalForActivity = () => {
  console.log('📝 Ouverture du modal AddMember pour:', selectedActivityForMembers.value)
  showAddMemberModal.value = true
}

// ✅ CORRECTION 7: Nouvelle méthode pour gérer l'ajout de membres
const handleMembersAddedToActivity = async () => {
  console.log('✅ Membres ajoutés avec succès')
  showAddMemberModal.value = false
  toast.success('Membres ajoutés à l\'activité')

  // Recharger le projet pour mettre à jour les données
  await loadProjet()

  // Si le modal ManageMembersModal est encore ouvert, il se rechargera automatiquement
  // car loadProjet() met à jour selectedActivityForMembers via la référence
}

const onMemberAdded = () => {
  showAddMemberModal.value = false
  loadActivite()
}

// ==================== MÉTHODES DE GESTION DES MEMBRES ====================

const editMember = (member) => {
  selectedMember.value = member
  showEditProjetMemberModal.value = true
}

const removeMember = (member) => {
  memberToRemove.value = member
  showRemoveMemberModal.value = true
}

// ==================== MÉTHODES EXISTANTES ====================

const loadProjet = async () => {
  try {
    loading.value = true
    const response = await fetchProjet(props.projetId)

    console.log('🔍 Données du projet chargées:', {
      workspace: response?.data?.workspace,
      workspaceOwnerId: response?.data?.workspace?.owner_id,
      currentUserId: authStore.user?.id,
      isWorkspaceOwner: response?.data?.workspace?.owner_id === authStore.user?.id
    })

    if (response && response.data) {
      projet.value = response.data
      projectStats.value = response.stats || {}
      activities.value = Array.isArray(response.data.activites) ? response.data.activites : []
      members.value = Array.isArray(response.data.members) ? response.data.members : []

      console.log('📋 Activités chargées:', activities.value)
      console.log('👥 Membres chargés:', members.value.length)

      // Réinitialiser le filtre à "mes activités" par défaut
      showAllActivities.value = false
    } else {
      projet.value = response || {}
      projectStats.value = {}
      activities.value = []
      members.value = []
    }

  } catch (error) {
    console.error('Error loading projet:', error)
    projet.value = null
    activities.value = []
    members.value = []
    toast.error('Erreur lors du chargement du projet')

  } finally {
    loading.value = false
  }
}

const handleActivityCreated = () => {
  showCreateActivityModal.value = false
  loadProjet()
  toast.success('Activité créée avec succès')
}

const handleActivityUpdated = () => {
  showEditActivityModal.value = false
  selectedActivity.value = null
  loadProjet()
  toast.success('Activité mise à jour avec succès')

}

const handleInvited = async () => {
  showInviteModal.value = false
  await loadProjet()
  await loadInvitations()
  toast.success('Invitation(s) traitée(s) avec succès')
}

const handleMemberUpdated = () => {
  showEditProjetMemberModal.value = false
  selectedMember.value = null
  loadProjet()
  toast.success('Permissions du membre mises à jour')

}

const handleMemberRemoved = async () => {
  showRemoveMemberModal.value = false
  memberToRemove.value = null
  await loadProjet()
  toast.success('Membre retiré du projet')
}

const editProjet = (projet) => {
  selectedProjet.value = projet
  showFormModal.value = true
}

const closeFormModal = () => {
  showFormModal.value = false
  selectedProjet.value = null
}

const handleProjetSaved = () => {
  fetchProjets()
  closeFormModal()
  toast.success('Projet sauvegardé avec succès')

}

// ==================== MÉTHODES D'AFFICHAGE ====================

const getActivityStatusClass = (status) => {
  const classes = {
    active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    archived: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    completed: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
  }
  return classes[status] || classes.active
}

const getActivityStatusLabel = (status) => {
  const labels = {
    active: 'Active',
    archived: 'Archivée',
    completed: 'Terminée'
  }
  return labels[status] || status
}

const getStatusColor = (status) => {
  const colors = {
    active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    completed: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    archived: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
  }
  return colors[status] || colors.pending
}

const getStatusLabel = (status) => {
  const labels = {
    active: 'Actif',
    completed: 'Terminé',
    archived: 'Archivé',
    pending: 'En attente'
  }
  return labels[status] || status
}

const getStatusIcon = (status) => {
  const icons = {
    active: TrendingUpIcon,
    completed: CheckCircleIcon,
    archived: ArchiveIcon,
    pending: ClockIcon
  }
  return icons[status] || ClockIcon
}
// ✅ Méthodes pour vérifier les permissions - VERSION CORRIGÉE
const hasAnyPermission = (member) => {
  // ✅ CORRECTION: Utiliser member.can_edit directement, pas member.pivot.can_edit
  return member.can_edit || member.can_delete || member.can_invite || member.can_delete_member ||
    member.can_create_activity ||
    member.can_edit_activity ||
    member.can_delete_activity
}

/**
 * ✅ CORRECTION: Vérifie si l'utilisateur peut éditer le projet
 */
const canEditProjet = computed(() => {
  const user = authStore.user
  if (!user || !projet.value) return false

  // Super admin peut tout éditer
  if (user.is_super_admin) return true

  // ✅ Workspace owner peut tout éditer
  if (isWorkspaceOwner.value) {
    console.log('✅ Workspace owner peut éditer le projet')
    return true
  }

  // Responsable du projet peut tout éditer
  if (projet.value.responsable_id === user.id) return true

  // Chercher l'utilisateur courant dans les membres
  const currentUserMember = members.value.find(m => m.id === user.id)

  // Vérifier si l'utilisateur a la permission d'éditer
  return currentUserMember?.can_edit === true || currentUserMember?.can_edit === 1
})

const isWorkspaceOwner = computed(() => {
  const user = authStore.user;
  if (!user || !projet.value?.workspace) return false;

  console.log('Vérification workspace owner:', {
    userId: user.id,
    workspaceOwnerId: projet.value.workspace.owner_id,
    isOwner: Number(projet.value.workspace.owner_id) === Number(user.id)
  });

  return Number(projet.value.workspace.owner_id) === Number(user.id);
});

const canEditMember = (member) => {
  const currentUser = authStore.user
  if (!currentUser) return false


  // Le propriétaire de l'espace de travail peut tout faire
  if (isWorkspaceOwner.value) return true

  // Ne pas permettre de modifier soi-même (l'utilisateur modifie ses propres permissions via un autre écran)
  if (member.id === currentUser.id) return false

  // Vérifier si l'utilisateur courant a la permission de gérer les membres
  if (!canManageMembers.value) return false

  // Ne pas permettre de retirer le responsable du projet
  if (member.id === projet.value.responsable_id) return false


  return true
}

const canRemoveMember = (member) => {
  const currentUser = authStore.user
  if (!currentUser || !projet.value) return false

  if (member.id === currentUser.id) return false

  if (currentUser.is_super_admin) return true
  if (isWorkspaceOwner.value) return true
  if (projet.value.responsable_id === currentUser.id) return true

  const currentUserMember = members.value.find(m => m.id === currentUser.id)

  return currentUserMember?.can_delete_member === true || currentUserMember?.can_delete_member === 1
}


// ✅ CORRECTION: Méthodes pour déterminer si l'utilisateur peut gérer les membres
const canManageMembers = computed(() => {
  const user = authStore.user;
  if (!user || !projet.value) return false;

  // Super admin peut tout gérer
  if (user.is_super_admin) return true;

  // Propriétaire du workspace peut tout gérer
  if (isWorkspaceOwner.value) return true;

  // Responsable du projet peut tout gérer
  if (projet.value.responsable_id === user.id) return true;

  // Chercher l'utilisateur courant dans les membres
  const currentUserMember = members.value.find(m => m.id === user.id);

  // Vérifier si l'utilisateur a la permission d'inviter
  return currentUserMember?.can_invite === true || currentUserMember?.can_invite === 1;
});

// ✅ CORRECTION: Méthodes de formatage des rôles
const getRoleColor = (role) => {
  const colors = {
    owner: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    admin: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    member: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    viewer: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
  }
  return colors[role] || colors.viewer
}

const getRoleLabel = (role) => {
  const labels = {
    owner: 'Propriétaire',
    admin: 'Administrateur',
    member: 'Membre',
    viewer: 'Observateur'
  }
  return labels[role] || role
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatDateTime = (dateTime) => {
  if (!dateTime) return '-'
  return new Date(dateTime).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR').format(amount)
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

onMounted(() => {
  loadProjet()
})

watch(() => props.projetId, () => {
  if (props.projetId) {
    loadProjet()
  }
})

watch(activeTab, async (tab) => {
  if (tab === 'invitations') {
    await loadInvitations()
  }
})


</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
