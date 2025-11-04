<!-- resources/js/pages/projets/MyProjects.vue -->
<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header Section -->
      <div class="bg-gradient-to-r from-brand-600 to-brand-700 rounded-xl p-6 text-white shadow-lg">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
          <div>
            <h1 class="text-3xl font-bold mb-2">Mes Projets</h1>
            <p class="text-brand-100 flex items-center gap-2">
              <FolderIcon class="w-5 h-5" />
              {{ filteredProjets.length }} projet(s) dans {{ currentWorkspaceName || 'le workspace actuel' }}
            </p>
          </div>

          <div class="flex items-center gap-3">
            <!-- Workspace Selector -->
            <div v-if="hasWorkspaces" class="relative">
              <select v-model="selectedWorkspaceId" @change="onWorkspaceChange"
                class="pl-10 pr-8 py-2.5 border-2 border-white/20 rounded-lg bg-white/10 backdrop-blur-sm text-white text-sm focus:ring-2 focus:ring-white/50 appearance-none cursor-pointer hover:bg-white/20 transition-all">
                <option v-for="workspace in workspaces" :key="workspace.id" :value="workspace.id"
                  class="bg-gray-800 text-white">
                  {{ workspace.nom }}
                </option>
              </select>
              <BuildingOfficeIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-white/80" />
              <ChevronDownIcon
                class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-white/80 pointer-events-none" />
            </div>
            <!-- Dans le header, après le workspace selector -->
            <button v-if="isSuperAdmin" @click="toggleDisplayMode" :class="[
              'inline-flex items-center gap-2 px-5 py-2.5 rounded-lg transition-all shadow-lg hover:shadow-xl font-medium',
              displayMode === 'all-projects'
                ? 'bg-purple-600 text-white hover:bg-purple-700'
                : 'bg-white text-purple-600 hover:bg-purple-50'
            ]">
              <component :is="displayMode === 'all-projects' ? FolderIcon : UserIcon" class="w-5 h-5" />
              {{ displayMode === 'all-projects' ? 'Tous les projets' : 'Mes projets' }}
            </button>

            <button @click="openCreateModal"
              class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-brand-600 rounded-lg hover:bg-brand-50 transition-all shadow-lg hover:shadow-xl font-medium">
              <PlusIcon class="w-5 h-5" />
              Nouveau projet
            </button>
          </div>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div v-if="!loading && hasStats" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Projects Card -->
        <div
          class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 p-6 hover:shadow-xl transition-all hover:-translate-y-1">
          <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
              <FolderIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div class="text-right">
              <p class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ stats.total_projets }}
              </p>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Total Projets
              </p>
            </div>
          </div>
          <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
            <span class="text-sm font-medium text-green-600 dark:text-green-400">
              {{ stats.projets_actifs }} actifs
            </span>
            <span class="text-xs text-gray-500 dark:text-gray-400">
              {{ stats.projets_termines }} terminés
            </span>
          </div>
        </div>

        <!-- Activities Card -->
        <div
          class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 p-6 hover:shadow-xl transition-all hover:-translate-y-1">
          <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
              <ListIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div class="text-right">
              <p class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ stats.total_activites }}
              </p>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Activités
              </p>
            </div>
          </div>
          <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
            <span class="text-sm text-gray-600 dark:text-gray-400">
              Réparties sur tous vos projets
            </span>
          </div>
        </div>

        <!-- Tasks Card -->
        <div
          class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 p-6 hover:shadow-xl transition-all hover:-translate-y-1">
          <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
              <CheckCircleIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div class="text-right">
              <p class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ stats.total_taches }}
              </p>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Tâches
              </p>
            </div>
          </div>
          <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
            <span class="text-sm font-medium text-green-600 dark:text-green-400">
              {{ stats.taux_completion }}% complétées
            </span>
            <span class="text-xs text-gray-500 dark:text-gray-400">
              {{ stats.taches_terminees }}/{{ stats.total_taches }}
            </span>
          </div>
        </div>

        <!-- Overdue Card -->
        <div
          class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 p-6 hover:shadow-xl transition-all hover:-translate-y-1">
          <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
              <AlertCircleIcon class="w-6 h-6 text-red-600 dark:text-red-400" />
            </div>
            <div class="text-right">
              <p class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ stats.projets_en_retard }}
              </p>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                En Retard
              </p>
            </div>
          </div>
          <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
            <span class="text-sm font-medium"
              :class="stats.projets_en_retard > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
              {{ stats.projets_en_retard > 0 ? 'Nécessite attention' : 'Aucun retard' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Filters and Search -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 p-6">
        <!-- Search Bar -->
        <div class="relative mb-4">
          <SearchIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input v-model="searchTerm" type="text" placeholder="Rechercher par nom, code ou description..."
            class="w-full pl-12 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all" />
        </div>

        <!-- Filter Buttons -->
        <div class="flex flex-wrap gap-3 mb-4">
          <!-- Status Filter -->
          <select v-model="filters.status"
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-brand-500 transition-all">
            <option value="all">Tous les statuts</option>
            <option value="active">Actifs</option>
            <option value="completed">Terminés</option>
            <option value="archived">Archivés</option>
          </select>

          <!-- Visibility Filter -->
          <select v-model="filters.visibility"
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-brand-500 transition-all">
            <option value="all">Toutes visibilités</option>
            <option value="public">Public</option>
            <option value="team">Équipe</option>
            <option value="private">Privé</option>
          </select>

          <!-- Favorites Button -->
          <button @click="filters.favorites = !filters.favorites" :class="[
            'inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all',
            filters.favorites
              ? 'bg-brand-600 text-white shadow-lg'
              : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600'
          ]">
            <StarIcon :class="filters.favorites ? 'fill-current' : ''" class="w-4 h-4" />
            Favoris
          </button>

          <!-- Overdue Button -->
          <button @click="filters.overdue = !filters.overdue" :class="[
            'inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all',
            filters.overdue
              ? 'bg-red-600 text-white shadow-lg'
              : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600'
          ]">
            <AlertCircleIcon class="w-4 h-4" />
            En retard
          </button>

          <!-- Reset Filters -->
          <button v-if="hasActiveFilters" @click="resetFilters"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-all">
            <XIcon class="w-4 h-4" />
            Réinitialiser
          </button>
        </div>

        <!-- View Toggle -->
        <div class="flex items-center gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
          <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Vue :</span>
          <button @click="viewMode = 'grid'" :class="[
            'p-2 rounded-lg transition-all',
            viewMode === 'grid'
              ? 'bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400'
              : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700'
          ]">
            <GridIcon class="w-5 h-5" />
          </button>
          <button @click="viewMode = 'list'" :class="[
            'p-2 rounded-lg transition-all',
            viewMode === 'list'
              ? 'bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400'
              : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700'
          ]">
            <ListIcon class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-16">
        <div class="relative">
          <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-brand-600"></div>
          <div class="absolute inset-0 flex items-center justify-center">
            <FolderIcon class="w-6 h-6 text-brand-600" />
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredProjets.length === 0"
        class="text-center py-16 bg-white dark:bg-gray-800 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-700">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
          <FolderOpenIcon class="w-8 h-8 text-gray-400" />
        </div>
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
          Aucun projet trouvé
        </h3>
        <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
          {{ searchTerm || hasActiveFilters
            ? 'Essayez de modifier vos critères de recherche ou de filtrage'
            : 'Commencez par créer votre premier projet ou attendez d\'être invité à collaborer'
          }}
        </p>
        <button v-if="!searchTerm && !hasActiveFilters" @click="openCreateModal"
          class="inline-flex items-center gap-2 px-6 py-3 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-all shadow-lg hover:shadow-xl font-medium">
          <PlusIcon class="w-5 h-5" />
          Créer mon premier projet
        </button>
      </div>

      <!-- Grid View -->
      <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="projet in filteredProjets" :key="projet.id"
          class="group bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:shadow-2xl transition-all hover:-translate-y-1 overflow-hidden">
          <!-- Card Header with Color Band -->
          <div class="h-2" :style="{ backgroundColor: projet.couleur || '#3B82F6' }"></div>

          <div class="p-6">
            <!-- Top Section -->
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                  <span
                    class="text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                    {{ projet.code }}
                  </span>
                  <span v-if="projet.responsable_id === currentUserId"
                    class="text-xs font-medium text-brand-600 dark:text-brand-400 bg-brand-50 dark:bg-brand-900/20 px-2 py-1 rounded">
                    Propriétaire
                  </span>
                  <span v-else
                    class="text-xs font-medium text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 px-2 py-1 rounded">
                    Collaborateur
                  </span>
                </div>
                <h3 @click="viewProjet(projet.id)"
                  class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors cursor-pointer line-clamp-1">
                  {{ projet.nom }}
                </h3>
              </div>

              <div class="flex items-center gap-2">
                <button @click.stop="toggleFavorite(projet)"
                  class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                  <StarIcon :class="projet.is_favorite ? 'fill-yellow-400 text-yellow-400' : 'text-gray-400'"
                    class="w-5 h-5" />
                </button>

                <div class="relative">
                  <button @click.stop="toggleMenu(projet.id)"
                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <MoreVerticalIcon class="w-5 h-5 text-gray-500" />
                  </button>

                  <!-- Dropdown Menu -->
                  <div v-if="activeMenuId === projet.id" v-click-outside="() => activeMenuId = null"
                    class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-700 rounded-lg shadow-xl border border-gray-200 dark:border-gray-600 z-10">
                    <button @click.stop="viewProjet(projet.id)"
                      class="w-full flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-t-lg transition-colors">
                      <EyeIcon class="w-4 h-4" />
                      Voir détails
                    </button>
                    <button v-if="projet.responsable_id === currentUserId" @click.stop="editProjet(projet)"
                      class="w-full flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                      <EditIcon class="w-4 h-4" />
                      Modifier
                    </button>
                    <button @click.stop="duplicateProjet(projet)"
                      class="w-full flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                      <CopyIcon class="w-4 h-4" />
                      Dupliquer
                    </button>
                    <button v-if="projet.status === 'active' && projet.responsable_id === currentUserId"
                      @click.stop="archiveProjet(projet)"
                      class="w-full flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                      <ArchiveIcon class="w-4 h-4" />
                      Archiver
                    </button>
                    <button v-if="projet.responsable_id === currentUserId" @click.stop="deleteProjet(projet)"
                      class="w-full flex items-center gap-3 px-4 py-3 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-b-lg transition-colors">
                      <TrashIcon class="w-4 h-4" />
                      Supprimer
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Description -->
            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-4 min-h-[2.5rem]">
              {{ projet.description || 'Aucune description' }}
            </p>

            <!-- Status Badges -->
            <div class="flex items-center gap-2 mb-4">
              <span :class="[
                'inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium',
                getStatusColor(projet.status)
              ]">
                <component :is="getStatusIcon(projet.status)" class="w-3 h-3" />
                {{ getStatusLabel(projet.status) }}
              </span>

              <span v-if="projet.is_overdue && projet.status === 'active'"
                class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                <AlertCircleIcon class="w-3 h-3" />
                En retard
              </span>
            </div>

            <!-- Progress Bar -->
            <div class="mb-4">
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                  Progression
                </span>
                <span class="text-xs font-bold text-gray-900 dark:text-white">
                  {{ projet.progression || 0 }}%
                </span>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                <div class="h-2.5 rounded-full transition-all duration-300"
                  :class="projet.progression >= 100 ? 'bg-green-500' : 'bg-brand-600'"
                  :style="{ width: `${projet.progression || 0}%` }"></div>
              </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-3 gap-4 mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
              <div class="text-center">
                <div class="text-lg font-bold text-gray-900 dark:text-white">
                  {{ projet.activites_count || 0 }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  Activités
                </div>
              </div>
              <div class="text-center">
                <div class="text-lg font-bold text-gray-900 dark:text-white">
                  {{ projet.taches_count || 0 }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  Tâches
                </div>
              </div>
              <div class="text-center">
                <div class="text-lg font-bold text-gray-900 dark:text-white">
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
              <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                <div class="flex items-center gap-1">
                  <CalendarIcon class="w-3.5 h-3.5" />
                  {{ formatDate(projet.date_debut) }}
                </div>
                <span>→</span>
                <div class="flex items-center gap-1">
                  <CalendarIcon class="w-3.5 h-3.5" />
                  {{ formatDate(projet.date_fin) }}
                </div>
              </div>
            </div>

            <!-- Responsable -->
            <div class="flex items-center gap-2 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
              <div v-if="projet.responsable?.avatar"
                class="w-7 h-7 rounded-full overflow-hidden ring-2 ring-gray-200 dark:ring-gray-700">
                <img :src="projet.responsable.avatar" :alt="projet.responsable.nom"
                  class="w-full h-full object-cover" />
              </div>
              <div v-else
                class="w-7 h-7 rounded-full bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center text-white text-xs font-semibold ring-2 ring-gray-200 dark:ring-gray-700">
                {{ getInitials(projet.responsable?.nom) }}
              </div>
              <span class="text-xs text-gray-600 dark:text-gray-400">
                {{ projet.responsable?.nom || 'Non assigné' }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- List View -->
      <div v-else
        class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
              <tr>
                <th scope="col"
                  class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                  Projet
                </th>
                <th scope="col"
                  class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                  Responsable
                </th>
                <th scope="col"
                  class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                  Progression
                </th>
                <th scope="col"
                  class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                  Dates
                </th>
                <th scope="col"
                  class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                  Statut
                </th>
                <th scope="col"
                  class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="projet in filteredProjets" :key="projet.id" @click="viewProjet(projet.id)"
                class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-3">
                    <div class="w-3 h-3 rounded-full flex-shrink-0"
                      :style="{ backgroundColor: projet.couleur || '#3B82F6' }"></div>
                    <div>
                      <div class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ projet.nom }}
                      </div>
                      <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                          {{ projet.code }}
                        </span>
                        <span v-if="projet.responsable_id === currentUserId"
                          class="text-xs font-medium text-brand-600 dark:text-brand-400">
                          Propriétaire
                        </span>
                        <span v-else class="text-xs font-medium text-purple-600 dark:text-purple-400">
                          Collaborateur
                        </span>
                      </div>
                    </div>
                    <StarIcon v-if="projet.is_favorite" class="w-4 h-4 fill-yellow-400 text-yellow-400 ml-2" />
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-3">
                    <div v-if="projet.responsable?.avatar"
                      class="w-9 h-9 rounded-full overflow-hidden ring-2 ring-gray-200 dark:ring-gray-700">
                      <img :src="projet.responsable.avatar" :alt="projet.responsable.nom"
                        class="w-full h-full object-cover" />
                    </div>
                    <div v-else
                      class="w-9 h-9 rounded-full bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center text-white text-xs font-semibold ring-2 ring-gray-200 dark:ring-gray-700">
                      {{ getInitials(projet.responsable?.nom) }}
                    </div>
                    <div>
                      <div class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ projet.responsable?.nom || 'Non assigné' }}
                      </div>
                      <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ projet.member_count || 0 }} membre(s)
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-3">
                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 max-w-[140px]">
                      <div :class="projet.progression >= 100 ? 'bg-green-500' : 'bg-brand-600'"
                        class="h-2.5 rounded-full transition-all" :style="{ width: `${projet.progression || 0}%` }">
                      </div>
                    </div>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white min-w-[3rem] text-right">
                      {{ projet.progression || 0 }}%
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                  <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-1">
                      <CalendarIcon class="w-3.5 h-3.5" />
                      <span>{{ formatDate(projet.date_debut) }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                      <CalendarIcon class="w-3.5 h-3.5" />
                      <span>{{ formatDate(projet.date_fin) }}</span>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex flex-col gap-2">
                    <span :class="[
                      'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium w-fit',
                      getStatusColor(projet.status)
                    ]">
                      <component :is="getStatusIcon(projet.status)" class="w-3 h-3" />
                      {{ getStatusLabel(projet.status) }}
                    </span>
                    <span v-if="projet.is_overdue && projet.status === 'active'"
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 w-fit">
                      <AlertCircleIcon class="w-3 h-3" />
                      En retard
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex items-center justify-end gap-2">
                    <button @click.stop="toggleFavorite(projet)"
                      class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                      <StarIcon :class="projet.is_favorite ? 'fill-yellow-400 text-yellow-400' : 'text-gray-400'"
                        class="w-4 h-4" />
                    </button>
                    <button v-if="projet.responsable_id === currentUserId" @click.stop="editProjet(projet)"
                      class="px-3 py-1.5 text-brand-600 hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-900/20 rounded-lg transition-colors font-medium">
                      Modifier
                    </button>
                    <button v-if="projet.responsable_id === currentUserId" @click.stop="deleteProjet(projet)"
                      class="px-3 py-1.5 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 rounded-lg transition-colors font-medium">
                      Supprimer
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1"
        class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 p-4">
        <div class="text-sm text-gray-700 dark:text-gray-400">
          Affichage de <span class="font-medium">{{ (pagination.current_page - 1) * pagination.per_page + 1 }}</span> à
          <span class="font-medium">{{ Math.min(pagination.current_page * pagination.per_page, pagination.total)
          }}</span>
          sur <span class="font-medium">{{ pagination.total }}</span> projets
        </div>
        <div class="flex gap-2">
          <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-medium">
            Précédent
          </button>
          <button v-for="page in paginationButtons" :key="page" @click="changePage(page)" :disabled="page === '...'"
            :class="[
              'px-4 py-2 rounded-lg border font-medium transition-colors',
              page === pagination.current_page
                ? 'bg-brand-600 text-white border-brand-600'
                : page === '...'
                  ? 'border-gray-300 dark:border-gray-600 text-gray-400 cursor-default'
                  : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
            ]">
            {{ page }}
          </button>
          <button @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-medium">
            Suivant
          </button>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <ProjetFormModal v-if="showFormModal" :projet="selectedProjet" @close="closeFormModal" @saved="handleProjetSaved" />

    <ConfirmModal v-if="showDeleteModal" title="Supprimer le projet"
      :message="`Êtes-vous sûr de vouloir supprimer le projet ${projetToDelete?.nom} ? Cette action est irréversible.`"
      confirm-text="Supprimer" confirm-class="bg-red-600 hover:bg-red-700" @confirm="confirmDelete"
      @cancel="showDeleteModal = false" />
  </AdminLayout>
</template>

<script setup>
// Script section pour MyProjects.vue
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useProjets } from '@/composables/useProjets'
import { useWorkspace } from '@/composables/useWorkspace'
import { useAuthStore } from '@/stores/auth'
import AdminLayout from "@/components/layout/AdminLayout.vue"
import ProjetFormModal from '@/components/projets/ProjetFormModal.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import {
  PlusIcon, SearchIcon, StarIcon, AlertCircleIcon, XIcon, GridIcon,
  ListIcon, FolderOpenIcon, MoreVerticalIcon, EditIcon, CopyIcon,
  ArchiveIcon, TrashIcon, CalendarIcon, CheckCircleIcon, ClockIcon,
  TrendingUpIcon, FolderIcon, ChevronDownIcon, BuildingOfficeIcon, EyeIcon
} from '@/icons'

const router = useRouter()
const authStore = useAuthStore()

const {
  loading,
  projets,
  stats,
  pagination,
  errors,
  isSuperAdmin, // ✅ Récupéré du composable
  fetchDashboardStats,
  fetchProjets,
  fetchAllProjets, // ✅ NOUVEAU
  fetchProjetsByWorkspace, // ✅ NOUVEAU
  deleteProjet: deleteProjetService,
  toggleFavorite: toggleFavoriteService,
  archiveProjet: archiveProjetService,
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
const viewMode = ref('grid')
const activeMenuId = ref(null)
const showFormModal = ref(false)
const showDeleteModal = ref(false)
const selectedProjet = ref(null)
const projetToDelete = ref(null)
const selectedWorkspaceId = ref(null)

// ✅ NOUVEAU : Mode d'affichage pour super admin
const displayMode = ref('my-projects') // 'my-projects' | 'all-projects'

const filters = ref({
  status: 'all',
  visibility: 'all',
  favorites: false,
  overdue: false
})

// Current User ID
const currentUserId = computed(() => authStore.user?.id)

// ✅ NOUVEAU : Titre dynamique selon le mode
const pageTitle = computed(() => {
  if (isSuperAdmin.value && displayMode.value === 'all-projects') {
    return selectedWorkspaceId.value
      ? `Tous les projets - ${currentWorkspaceName.value}`
      : 'Tous les projets (Global)'
  }
  return 'Mes Projets'
})

// Computed
const hasActiveFilters = computed(() => {
  return filters.value.status !== 'all' ||
    filters.value.visibility !== 'all' ||
    filters.value.favorites ||
    filters.value.overdue
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

  // Status filter
  if (filters.value.status !== 'all') {
    result = result.filter(p => p.status === filters.value.status)
  }

  // Visibility filter
  if (filters.value.visibility !== 'all') {
    result = result.filter(p => p.visibility === filters.value.visibility)
  }

  // Favorites filter
  if (filters.value.favorites) {
    result = result.filter(p => p.is_favorite)
  }

  // Overdue filter
  if (filters.value.overdue) {
    result = result.filter(p => p.is_overdue)
  }

  return result
})

const hasStats = computed(() => {
  return stats.value.total_projets > 0
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

// ✅ NOUVEAU : Charger les données selon le mode d'affichage
const loadData = async () => {
  const workspaceId = selectedWorkspaceId.value || currentWorkspaceId.value

  // Charger les statistiques
  await fetchDashboardStats(workspaceId)

  // Charger les projets selon le mode
  if (isSuperAdmin.value && displayMode.value === 'all-projects') {
    // Super admin : tous les projets (filtrés par workspace si sélectionné)
    await fetchAllProjets({
      per_page: 12,
      workspace_id: workspaceId
    })
  } else {
    // Utilisateur normal ou super admin en mode "mes projets"
    await fetchProjetsByWorkspace(workspaceId, { per_page: 12 })
  }

  // Charger les workspaces
  await fetchWorkspaces()

  // Set selected workspace to current workspace
  if (currentWorkspaceId.value && !selectedWorkspaceId.value) {
    selectedWorkspaceId.value = currentWorkspaceId.value
  }
}

// ✅ NOUVEAU : Changer le mode d'affichage
const toggleDisplayMode = () => {
  displayMode.value = displayMode.value === 'my-projects' ? 'all-projects' : 'my-projects'
  loadData()
}

// ✅ Modification : Gestion du changement de workspace
const onWorkspaceChange = async () => {
  if (selectedWorkspaceId.value && selectedWorkspaceId.value !== currentWorkspaceId.value) {
    const workspace = workspaces.value.find(w => w.id === selectedWorkspaceId.value)
    if (workspace) {
      selectWorkspace(workspace)
      await loadData()
    }
  }
}

const openCreateModal = () => {
  selectedProjet.value = null
  showFormModal.value = true
}

const editProjet = (projet) => {
  selectedProjet.value = projet
  showFormModal.value = true
  activeMenuId.value = null
}

const closeFormModal = () => {
  showFormModal.value = false
  selectedProjet.value = null
}

const handleProjetSaved = () => {
  loadData()
  closeFormModal()
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

const archiveProjet = async (projet) => {
  try {
    await archiveProjetService(projet.id)
    activeMenuId.value = null
    await loadData()
  } catch (error) {
    console.error('Error archiving projet:', error)
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
    status: 'all',
    visibility: 'all',
    favorites: false,
    overdue: false
  }
  searchTerm.value = ''
}

const changePage = async (page) => {
  if (page === '...' || page < 1 || page > pagination.value.last_page) return

  const workspaceId = selectedWorkspaceId.value || currentWorkspaceId.value

  if (isSuperAdmin.value && displayMode.value === 'all-projects') {
    await fetchAllProjets({
      page,
      per_page: pagination.value.per_page,
      workspace_id: workspaceId
    })
  } else {
    await fetchProjetsByWorkspace(workspaceId, {
      page,
      per_page: pagination.value.per_page
    })
  }
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

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
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

// Lifecycle
onMounted(() => {
  loadData()
})

// ✅ Watch for workspace changes
watch(currentWorkspaceId, (newWorkspaceId) => {
  if (newWorkspaceId) {
    selectedWorkspaceId.value = newWorkspaceId
    loadData()
  }
})

// ✅ Watch for display mode changes
watch(displayMode, () => {
  loadData()
})

// Listen for workspace change event from sidebar
onMounted(() => {
  window.addEventListener('workspace-changed', async () => {
    await loadData()
  })
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