<!-- resources/js/pages/projets/ArchivedProjects.vue -->
<template>
  <AdminLayout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
      <!-- Header Section - Trello Style -->
      <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-full mx-auto px-4 py-4">
          <div class="flex items-center justify-between">
            <!-- Left Side -->
            <div class="flex items-center gap-4">
              <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                  {{ pageTitle }}
                </h1>
                <p class="text-gray-500 dark:text-gray-400 flex items-center gap-2 text-sm mt-1">
                  <ArchiveIcon class="w-4 h-4" />
                  {{ filteredProjets.length }} projet(s) archivé(s) dans {{ currentWorkspaceName || 'le workspace actuel' }}
                </p>
              </div>
            </div>

            <!-- Right Side -->
            <div class="flex items-center gap-3">
              <!-- Workspace Selector -->
              <div v-if="hasWorkspaces" class="relative">
                <select v-model="selectedWorkspaceId" @change="onWorkspaceChange"
                  class="pl-10 pr-8 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 appearance-none cursor-pointer">
                  <option v-for="workspace in workspaces" :key="workspace.id" :value="workspace.id"
                    class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    {{ workspace.nom }}
                  </option>
                </select>
                <BuildingOfficeIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 dark:text-gray-400" />
                <ChevronDownIcon
                  class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 dark:text-gray-400 pointer-events-none" />
              </div>

              <!-- Display Mode Toggle for Super Admin -->
              <button v-if="isSuperAdmin" @click="toggleDisplayMode" :class="[
                'inline-flex items-center gap-2 px-4 py-2 rounded-3 text-sm font-medium transition-colors',
                displayMode === 'all-archived'
                  ? 'bg-blue-600 text-white hover:bg-blue-700'
                  : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'
              ]">
                <ArchiveIcon class="w-4 h-4" />
                {{ displayMode === 'all-archived' ? 'Tous les projets archivés' : 'Mes projets archivés' }}
              </button>

              <!-- Bulk Actions -->
              <div class="relative" v-if="selectedProjets.length > 0">
                <button @click="showBulkActions = !showBulkActions"
                  class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-3 hover:bg-blue-700 transition-colors font-medium">
                  <ArchiveIcon class="w-4 h-4" />
                  Actions groupées ({{ selectedProjets.length }})
                  <ChevronDownIcon class="w-4 h-4" />
                </button>

                <!-- Bulk Actions Dropdown -->
                <div v-if="showBulkActions" v-click-outside="() => showBulkActions = false"
                  class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-700 rounded-3 border border-gray-200 dark:border-gray-600 z-10">
                  <button @click="bulkUnarchive"
                    class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-t-lg transition-colors">
                    <ArchiveBoxArrowUpIcon class="w-4 h-4" />
                    Désarchiver
                  </button>
                  <button @click="bulkDelete"
                    class="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-b-lg transition-colors">
                    <TrashIcon class="w-4 h-4" />
                    Supprimer
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Statistics Cards - Spécial Archives -->
      <div v-if="!loading && hasStats" class="px-4 py-4">
        <div class="max-w-full mx-auto">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Archives -->
            <div class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ stats.total_archives || 0 }}
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    Projets archivés
                  </p>
                </div>
                <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-3">
                  <ArchiveIcon class="w-6 h-6 text-gray-600 dark:text-gray-400" />
                </div>
              </div>
              <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 dark:border-gray-600">
                <span class="text-xs font-medium text-blue-600 dark:text-blue-400">
                  {{ stats.archives_termines || 0 }} terminés
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                  {{ stats.archives_actifs || 0 }} actifs avant archivage
                </span>
              </div>
            </div>

            <!-- Durée moyenne d'archivage -->
            <div class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ stats.duree_moyenne_archivage || 0 }}
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    Jours d'archivage
                  </p>
                </div>
                <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-3">
                  <ClockIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                </div>
              </div>
              <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-600">
                <span class="text-xs text-gray-500 dark:text-gray-400">
                  Durée moyenne
                </span>
              </div>
            </div>

            <!-- Taux de complétion -->
            <div class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ stats.taux_completion_archives || 0 }}%
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    Complétion moyenne
                  </p>
                </div>
                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-3">
                  <CheckCircleIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
              </div>
              <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 dark:border-gray-600">
                <span class="text-xs font-medium text-green-600 dark:text-green-400">
                  {{ stats.archives_100_percent || 0 }} à 100%
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                  {{ stats.archives_incomplets || 0 }} incomplets
                </span>
              </div>
            </div>

            <!-- Ancienneté -->
            <div class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ stats.archives_recentes || 0 }}
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    Archivés récemment
                  </p>
                </div>
                <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-3">
                  <CalendarIcon class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                </div>
              </div>
              <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-600">
                <span class="text-xs font-medium" :class="
                  (stats.archives_anciennes || 0) > 10 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'
                ">
                  {{ stats.archives_anciennes || 0 }} > 1 an
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="px-4 py-4">
        <div class="max-w-full mx-auto">
          <!-- Filters and Search - Spécial Archives -->
          <div class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-4 mb-4">
            <!-- Search Bar -->
            <div class="relative mb-4">
              <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input v-model="searchTerm" type="text" placeholder="Rechercher dans les archives..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" />
            </div>

            <!-- Filter Buttons -->
            <div class="flex flex-wrap gap-2">
              <!-- Date d'archivage Filter -->
              <select v-model="filters.archived_period"
                class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 transition-all">
                <option value="all">Toutes périodes</option>
                <option value="today">Aujourd'hui</option>
                <option value="week">Cette semaine</option>
                <option value="month">Ce mois</option>
                <option value="year">Cette année</option>
                <option value="old">Plus d'un an</option>
              </select>

              <!-- Statut avant archivage -->
              <select v-model="filters.pre_archive_status"
                class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 transition-all">
                <option value="all">Tous les statuts</option>
                <option value="completed">Terminés avant archivage</option>
                <option value="active">Actifs avant archivage</option>
              </select>

              <!-- Complétion Filter -->
              <select v-model="filters.completion"
                class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 transition-all">
                <option value="all">Tous les taux</option>
                <option value="100">100% complétés</option>
                <option value="75-99">75-99% complétés</option>
                <option value="50-74">50-74% complétés</option>
                <option value="0-49">0-49% complétés</option>
              </select>

              <!-- Reset Filters -->
              <button v-if="hasActiveFilters" @click="resetFilters"
                class="inline-flex items-center gap-2 px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                <XIcon class="w-4 h-4" />
                Réinitialiser
              </button>
            </div>
          </div>

          <!-- Selection Info -->
          <div v-if="selectedProjets.length > 0" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-3 p-4 mb-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <CheckCircleIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                <span class="text-sm font-medium text-blue-800 dark:text-blue-300">
                  {{ selectedProjets.length }} projet(s) sélectionné(s)
                </span>
              </div>
              <button @click="clearSelection" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200">
                Tout désélectionner
              </button>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="flex justify-center py-16">
            <div class="relative">
              <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
              <div class="absolute inset-0 flex items-center justify-center">
                <ArchiveIcon class="w-6 h-6 text-blue-600" />
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else-if="filteredProjets.length === 0"
            class="text-center py-16 bg-white dark:bg-gray-800 rounded-3 border-2 border-dashed border-gray-300 dark:border-gray-700">
            <div
              class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
              <ArchiveBoxIcon class="w-8 h-8 text-gray-400" />
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
              Aucun projet archivé
            </h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
              {{ searchTerm || hasActiveFilters
                ? 'Aucun projet ne correspond à vos critères de recherche'
                : 'Les projets archivés apparaîtront ici. Archivez des projets pour les conserver sans les afficher dans la liste principale.'
              }}
            </p>
            <button v-if="!searchTerm && !hasActiveFilters" @click="goToActiveProjects"
              class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-3 hover:bg-blue-700 transition-colors font-medium">
              <ArrowLeftIcon class="w-5 h-5" />
              Voir les projets actifs
            </button>
          </div>

          <!-- Archived Projects Grid -->
          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div v-for="projet in filteredProjets" :key="projet.id"
              class="bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 transition-shadow overflow-hidden"
              :class="{
                'ring-2 ring-blue-500': isSelected(projet.id),
                'opacity-75': projet.status === 'archived'
              }">
              
              <!-- Selection Checkbox -->
              <div class="absolute top-3 left-3 z-10">
                <input type="checkbox" :checked="isSelected(projet.id)" @change="toggleSelection(projet.id)"
                  class="w-4 h-4 text-blue-600 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500">
              </div>

              <!-- Card Header with Gray Band for Archives -->
              <div class="h-2 bg-gray-400"></div>

              <div class="p-4">
                <!-- Top Section -->
                <div class="flex items-start justify-between mb-3">
                  <div class="flex-1 ml-6">
                    <div class="flex items-center gap-2 mb-2">
                      <span
                        class="text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                        {{ projet.code }}
                      </span>
                      <span v-if="projet.responsable_id === currentUserId"
                        class="text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-2 py-1 rounded">
                        Propriétaire
                      </span>
                    </div>
                    <h3 @click="viewProjet(projet.id)"
                      class="text-lg font-bold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors cursor-pointer line-clamp-1">
                      {{ projet.nom }}
                    </h3>
                  </div>

                  <div class="flex items-center gap-1">
                    <button @click.stop="toggleFavorite(projet)"
                      class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                      <StarIcon :class="projet.is_favorite ? 'fill-yellow-400 text-yellow-400' : 'text-gray-400'"
                        class="w-4 h-4" />
                    </button>

                    <div class="relative">
                      <button @click.stop="toggleMenu(projet.id)"
                        class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <HorizontalDots class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                      </button>

                      <!-- Dropdown Menu -->
                      <div v-if="activeMenuId === projet.id" v-click-outside="() => activeMenuId = null"
                        class="absolute right-0 mt-1 w-48 bg-white dark:bg-gray-700 rounded-3 border border-gray-200 dark:border-gray-600 z-10">
                        <button @click.stop="viewProjet(projet.id)"
                          class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-t-lg transition-colors">
                          <EyeIcon class="w-4 h-4" />
                          Voir détails
                        </button>
                        <button @click.stop="unarchiveProjet(projet)"
                          class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                          <ArchiveBoxArrowUpIcon class="w-4 h-4" />
                          Désarchiver
                        </button>
                        <button @click.stop="duplicateProjet(projet)"
                          class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                          <CopyIcon class="w-4 h-4" />
                          Dupliquer
                        </button>
                        <button v-if="projet.responsable_id === currentUserId" @click.stop="deleteProjet(projet)"
                          class="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-b-lg transition-colors">
                          <TrashIcon class="w-4 h-4" />
                          Supprimer définitivement
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Description -->
                <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3 min-h-[2.5rem]">
                  {{ projet.description || 'Aucune description' }}
                </p>

                <!-- Archive Info -->
                <div class="flex items-center gap-2 mb-3">
                  <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                    <ArchiveIcon class="w-3 h-3" />
                    Archivé le {{ formatArchiveDate(projet.archived_at) }}
                  </span>
                  
                  <span v-if="getArchiveDuration(projet) > 365"
                    class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">
                    <ClockIcon class="w-3 h-3" />
                    Ancien
                  </span>
                </div>

                <!-- Progress Bar -->
                <div class="mb-3">
                  <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                      Progression finale
                    </span>
                    <span class="text-xs font-bold text-gray-900 dark:text-white">
                      {{ projet.progression }}%
                    </span>
                  </div>
                  <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all duration-300"
                      :class="projet.progression >= 100 ? 'bg-green-500' : 'bg-gray-500'"
                      :style="{ width: `${projet.progression}%` }">
                    </div>
                  </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-3 gap-2 mb-3 pb-3 border-b border-gray-200 dark:border-gray-700">
                  <div class="text-center">
                    <div class="text-sm font-bold text-gray-900 dark:text-white">
                      {{ getProjectActivitiesCount(projet) }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                      Activités
                    </div>
                  </div>
                  <div class="text-center">
                    <div class="text-sm font-bold text-gray-900 dark:text-white">
                      {{ getProjectTasksCount(projet) }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                      Tâches
                    </div>
                  </div>
                  <div class="text-center">
                    <div class="text-sm font-bold text-gray-900 dark:text-white">
                      {{ projet.member_count || 0 }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                      Membres
                    </div>
                  </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between">
                  <!-- Dates -->
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ formatDate(projet.date_debut) }} - {{ formatDate(projet.date_fin) }}
                  </div>

                  <!-- Durée d'archivage -->
                  <div class="text-xs text-gray-400 dark:text-gray-500">
                    {{ getArchiveDuration(projet) }}j
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="pagination.last_page > 1"
            class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-3 border border-gray-200 dark:border-gray-700 p-4 mt-4">
            <div class="text-sm text-gray-700 dark:text-gray-400">
              Affichage de <span class="font-medium">{{ (pagination.current_page - 1) * pagination.per_page + 1
                }}</span> à
              <span class="font-medium">{{ Math.min(pagination.current_page * pagination.per_page, pagination.total)
                }}</span>
              sur <span class="font-medium">{{ pagination.total }}</span> projets archivés
            </div>
            <div class="flex gap-1">
              <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
                class="px-3 py-2 rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm">
                Précédent
              </button>
              <button v-for="page in paginationButtons" :key="page" @click="changePage(page)" :disabled="page === '...'"
                :class="[
                  'px-3 py-2 rounded border text-sm font-medium transition-colors',
                  page === pagination.current_page
                    ? 'bg-blue-600 text-white border-blue-600'
                    : page === '...'
                      ? 'border-gray-300 dark:border-gray-600 text-gray-400 cursor-default'
                      : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                ]">
                {{ page }}
              </button>
              <button @click="changePage(pagination.current_page + 1)"
                :disabled="pagination.current_page === pagination.last_page"
                class="px-3 py-2 rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm">
                Suivant
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <ConfirmModal v-if="showDeleteModal" title="Supprimer le projet archivé"
      :message="`Êtes-vous sûr de vouloir supprimer définitivement le projet ${projetToDelete?.nom} ? Cette action est irréversible.`"
      confirm-text="Supprimer définitivement" confirm-class="bg-red-600 hover:bg-red-700" @confirm="confirmDelete"
      @cancel="showDeleteModal = false" />

    <ConfirmModal v-if="showBulkDeleteModal" title="Supprimer les projets archivés"
      :message="`Êtes-vous sûr de vouloir supprimer définitivement ${selectedProjets.length} projet(s) archivé(s) ? Cette action est irréversible.`"
      confirm-text="Supprimer définitivement" confirm-class="bg-red-600 hover:bg-red-700" @confirm="confirmBulkDelete"
      @cancel="showBulkDeleteModal = false" />
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useProjets } from '@/composables/useProjets'
import { useWorkspace } from '@/composables/useWorkspace'
import { useAuthStore } from '@/stores/authStore'
import AdminLayout from "@/components/layout/AdminLayout.vue"
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import {
  PlusIcon, SearchIcon, StarIcon, XIcon,
  ListIcon, HorizontalDots, PencilIcon, CopyIcon,
  ArchiveIcon, TrashIcon, CheckCircleIcon, ClockIcon,
  FolderIcon, ChevronDownIcon, BuildingOfficeIcon, EyeIcon,
  ArchiveBoxIcon, ArchiveBoxArrowUpIcon, CalendarIcon, ArrowLeftIcon
} from '@/icons'

const router = useRouter()
const authStore = useAuthStore()

const {
  loading,
  projets,
  stats,
  pagination,
  errors,
  isSuperAdmin,
  fetchArchivedProjets,
  fetchAllArchivedProjets,
  deleteProjet: deleteProjetService,
  toggleFavorite: toggleFavoriteService,
  unarchiveProjet: unarchiveProjetService,
  cloneProjet: cloneProjetService
} = useProjets()

const {
  currentWorkspace,
  workspaces,
  hasWorkspaces,
  currentWorkspaceId,
  currentWorkspaceName,
  selectWorkspace,
  fetchWorkspaces
} = useWorkspace()

// State
const searchTerm = ref('')
const activeMenuId = ref(null)
const showDeleteModal = ref(false)
const showBulkDeleteModal = ref(false)
const showBulkActions = ref(false)
const projetToDelete = ref(null)
const selectedWorkspaceId = ref(null)
const displayMode = ref('my-archived')
const selectedProjets = ref([])

const filters = ref({
  archived_period: 'all',
  pre_archive_status: 'all',
  completion: 'all'
})

// Current User ID
const currentUserId = computed(() => authStore.user?.id)

// Computed
const pageTitle = computed(() => {
  if (isSuperAdmin.value && displayMode.value === 'all-archived') {
    return selectedWorkspaceId.value
      ? `Tous les projets archivés - ${currentWorkspaceName.value}`
      : 'Tous les projets archivés (Global)'
  }
  return 'Projets Archivés'
})

const hasActiveFilters = computed(() => {
  return filters.value.archived_period !== 'all' ||
    filters.value.pre_archive_status !== 'all' ||
    filters.value.completion !== 'all'
})

const filteredProjets = computed(() => {
  let result = projets.value

  // Search
  if (searchTerm.value) {
    const term = searchTerm.value.toLowerCase()
    result = result.filter(p =>
      p.nom.toLowerCase().includes(term) ||
      p.code.toLowerCase().includes(term) ||
      p.description?.toLowerCase().includes(term)
    )
  }

  // Archive period filter
  if (filters.value.archived_period !== 'all') {
    const now = new Date()
    result = result.filter(p => {
      if (!p.archived_at) return false
      const archivedDate = new Date(p.archived_at)
      
      switch (filters.value.archived_period) {
        case 'today':
          return archivedDate.toDateString() === now.toDateString()
        case 'week':
          const startOfWeek = new Date(now.setDate(now.getDate() - now.getDay()))
          return archivedDate >= startOfWeek
        case 'month':
          return archivedDate.getMonth() === now.getMonth() && archivedDate.getFullYear() === now.getFullYear()
        case 'year':
          return archivedDate.getFullYear() === now.getFullYear()
        case 'old':
          const oneYearAgo = new Date(now.setFullYear(now.getFullYear() - 1))
          return archivedDate < oneYearAgo
        default:
          return true
      }
    })
  }

  // Completion filter
  if (filters.value.completion !== 'all') {
    result = result.filter(p => {
      const progression = p.progression || 0
      switch (filters.value.completion) {
        case '100':
          return progression === 100
        case '75-99':
          return progression >= 75 && progression < 100
        case '50-74':
          return progression >= 50 && progression < 75
        case '0-49':
          return progression < 50
        default:
          return true
      }
    })
  }

  return result
})

const hasStats = computed(() => {
  return stats.value && stats.value.total_archives > 0
})

const paginationButtons = computed(() => {
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const delta = 2
  const range = []
  const rangeWithDots = []

  for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
    range.push(i)
  }

  if (current - delta > 2) {
    rangeWithDots.push(1, '...')
  } else {
    rangeWithDots.push(1)
  }

  rangeWithDots.push(...range)

  if (current + delta < last - 1) {
    rangeWithDots.push('...', last)
  } else if (last > 1) {
    rangeWithDots.push(last)
  }

  return rangeWithDots
})

// Methods
const loadData = async () => {
  const workspaceId = selectedWorkspaceId.value || currentWorkspaceId.value

  // Charger les projets archivés selon le mode
  if (isSuperAdmin.value && displayMode.value === 'all-archived') {
    await fetchAllArchivedProjets({
      per_page: 12,
      workspace_id: workspaceId
    })
  } else {
    await fetchArchivedProjets({
      per_page: 12,
      workspace_id: workspaceId
    })
  }

  // Charger les workspaces
  await fetchWorkspaces()

  // Set selected workspace to current workspace
  if (currentWorkspaceId.value && !selectedWorkspaceId.value) {
    selectedWorkspaceId.value = currentWorkspaceId.value
  }
}

const toggleDisplayMode = () => {
  displayMode.value = displayMode.value === 'my-archived' ? 'all-archived' : 'my-archived'
  loadData()
}

const onWorkspaceChange = async () => {
  if (selectedWorkspaceId.value && selectedWorkspaceId.value !== currentWorkspaceId.value) {
    const workspace = workspaces.value.find(w => w.id === selectedWorkspaceId.value)
    if (workspace) {
      selectWorkspace(workspace)
      await loadData()
    }
  }
}

const viewProjet = (projetId) => {
  router.push({ name: 'projets.show', params: { id: projetId } })
}

const deleteProjet = (projet) => {
  projetToDelete.value = projet
  showDeleteModal.value = true
  activeMenuId.value = null
}

const confirmDelete = async () => {
  try {
    await deleteProjetService(projetToDelete.value.id)
    showDeleteModal.value = false
    projetToDelete.value = null
    await loadData()
  } catch (error) {
    console.error('Error deleting projet:', error)
  }
}

const toggleFavorite = async (projet) => {
  try {
    await toggleFavoriteService(projet.id)
    await loadData()
  } catch (error) {
    console.error('Error toggling favorite:', error)
  }
}

const unarchiveProjet = async (projet) => {
  try {
    await unarchiveProjetService(projet.id)
    activeMenuId.value = null
    await loadData()
  } catch (error) {
    console.error('Error unarchiving projet:', error)
  }
}

const duplicateProjet = async (projet) => {
  try {
    await cloneProjetService(projet.id, {
      nom: `${projet.nom} (Copie)`
    })
    activeMenuId.value = null
    await loadData()
  } catch (error) {
    console.error('Error duplicating projet:', error)
  }
}

const toggleMenu = (projetId) => {
  activeMenuId.value = activeMenuId.value === projetId ? null : projetId
}

const resetFilters = () => {
  filters.value = {
    archived_period: 'all',
    pre_archive_status: 'all',
    completion: 'all'
  }
  searchTerm.value = ''
}

const changePage = async (page) => {
  if (page === '...' || page < 1 || page > pagination.value.last_page) return

  const workspaceId = selectedWorkspaceId.value || currentWorkspaceId.value

  if (isSuperAdmin.value && displayMode.value === 'all-archived') {
    await fetchAllArchivedProjets({
      page,
      per_page: pagination.value.per_page,
      workspace_id: workspaceId
    })
  } else {
    await fetchArchivedProjets({
      page,
      per_page: pagination.value.per_page,
      workspace_id: workspaceId
    })
  }
}

// Selection methods
const toggleSelection = (projetId) => {
  const index = selectedProjets.value.indexOf(projetId)
  if (index > -1) {
    selectedProjets.value.splice(index, 1)
  } else {
    selectedProjets.value.push(projetId)
  }
}

const isSelected = (projetId) => {
  return selectedProjets.value.includes(projetId)
}

const clearSelection = () => {
  selectedProjets.value = []
}

const bulkUnarchive = async () => {
  try {
    for (const projetId of selectedProjets.value) {
      await unarchiveProjetService(projetId)
    }
    clearSelection()
    showBulkActions.value = false
    await loadData()
  } catch (error) {
    console.error('Error bulk unarchiving:', error)
  }
}

const bulkDelete = () => {
  showBulkDeleteModal.value = true
  showBulkActions.value = false
}

const confirmBulkDelete = async () => {
  try {
    for (const projetId of selectedProjets.value) {
      await deleteProjetService(projetId)
    }
    clearSelection()
    showBulkDeleteModal.value = false
    await loadData()
  } catch (error) {
    console.error('Error bulk deleting:', error)
  }
}

const goToActiveProjects = () => {
  router.push({ name: 'projets.mes-projets' })
}

// Utility methods
const getProjectProgression = (projet) => {
  if (projet.progression !== undefined && projet.progression !== null) {
    return Math.round(projet.progression)
  }
  return 0
}

const getProjectActivitiesCount = (projet) => {
  if (projet.activites_count !== undefined && projet.activites_count !== null) {
    return projet.activites_count
  }
  if (projet.activites && Array.isArray(projet.activites)) {
    return projet.activites.length
  }
  return 0
}

const getProjectTasksCount = (projet) => {
  if (projet.taches_count !== undefined && projet.taches_count !== null) {
    return projet.taches_count
  }
  if (projet.activites && Array.isArray(projet.activites)) {
    return projet.activites.reduce((sum, activite) => sum + (activite.tache_count || 0), 0)
  }
  return 0
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatArchiveDate = (date) => {
  if (!date) return 'date inconnue'
  return new Date(date).toLocaleDateString('fr-FR')
}

const getArchiveDuration = (projet) => {
  if (!projet.archived_at) return 0
  const archivedDate = new Date(projet.archived_at)
  const now = new Date()
  const diffTime = Math.abs(now - archivedDate)
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
}

// Lifecycle
onMounted(() => {
  loadData()
})

// Watchers
watch(currentWorkspaceId, (newWorkspaceId) => {
  if (newWorkspaceId) {
    selectedWorkspaceId.value = newWorkspaceId
    loadData()
  }
})

watch(displayMode, () => {
  loadData()
})

// Click outside directive
const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = (event) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value()
      }
    }
    document.addEventListener('click', el.clickOutsideEvent)
  },
  unmounted(el) {
    document.removeEventListener('click', el.clickOutsideEvent)
  }
}
</script>

<style scoped>
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>