<template>
  <AdminLayout>
    <!-- Header Asana-style -->
    <div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Workspaces</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Tous vos espaces de travail en un seul endroit</p>
          </div>
          <div class="flex items-center space-x-3">
            <!-- Search Bar -->
            <div class="relative">
              <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input v-model="searchQuery" type="text" placeholder="Rechercher des workspaces..."
                class="pl-10 pr-4 py-2 w-64 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
            </div>
            
            <!-- Create Button -->
            <button @click="$router.push({ name: 'workspaces.create' })"
              class="flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm font-medium">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Nouveau workspace
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Quick Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl p-5 border border-blue-100 dark:border-blue-800/30">
          <div class="flex items-center">
            <div class="p-3 bg-blue-100 dark:bg-blue-800/40 rounded-lg mr-4">
              <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
            </div>
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Workspaces</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
            </div>
          </div>
        </div>

        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl p-5 border border-green-100 dark:border-green-800/30">
          <div class="flex items-center">
            <div class="p-3 bg-green-100 dark:bg-green-800/40 rounded-lg mr-4">
              <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Projets</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.totalProjects }}</p>
            </div>
          </div>
        </div>

        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl p-5 border border-purple-100 dark:border-purple-800/30">
          <div class="flex items-center">
            <div class="p-3 bg-purple-100 dark:bg-purple-800/40 rounded-lg mr-4">
              <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.67 3.913a6 6 0 01-1.41 2.614M17.67 19.912A6 6 0 0015 15.197" />
              </svg>
            </div>
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Membres</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.totalMembers }}</p>
            </div>
          </div>
        </div>

        <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 rounded-xl p-5 border border-orange-100 dark:border-orange-800/30">
          <div class="flex items-center">
            <div class="p-3 bg-orange-100 dark:bg-orange-800/40 rounded-lg mr-4">
              <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Actifs</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.active }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-2">
          <button @click="viewMode = 'grid'" :class="[
            'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
            viewMode === 'grid' 
              ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
              : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'
          ]">
            Grille
          </button>
          <button @click="viewMode = 'list'" :class="[
            'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
            viewMode === 'list' 
              ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
              : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'
          ]">
            Liste
          </button>
        </div>
        
        <select v-model="filterActive"
          class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
          <option value="all">Tous les workspaces</option>
          <option value="active">Actifs seulement</option>
          <option value="inactive">Inactifs seulement</option>
        </select>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-20">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p class="text-gray-600 dark:text-gray-400">Chargement des workspaces...</p>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredWorkspaces.length === 0" class="text-center py-20">
        <div class="max-w-md mx-auto">
          <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-blue-100 to-blue-200 dark:from-blue-900/30 dark:to-blue-800/30 flex items-center justify-center">
            <svg class="w-12 h-12 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
          </div>
          <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
            {{ searchQuery ? 'Aucun workspace trouvé' : 'Aucun workspace pour le moment' }}
          </h3>
          <p class="text-gray-600 dark:text-gray-400 mb-6">
            {{ searchQuery ? 'Essayez une autre recherche' : 'Créez votre premier workspace pour commencer' }}
          </p>
          <button v-if="!searchQuery" @click="$router.push({ name: 'workspaces.create' })"
            class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Créer un workspace
          </button>
        </div>
      </div>

      <!-- Grid View (Asana-style) -->
      <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="workspace in filteredWorkspaces" :key="workspace.id"
          class="group relative bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 hover:shadow-lg transition-all duration-300 overflow-hidden cursor-pointer"
          @click="navigateToWorkspace(workspace.id)">
          
          <!-- Visual Identifier -->
          <div class="absolute top-0 left-0 w-full h-2" 
            :class="workspace.is_active ? 'bg-gradient-to-r from-blue-500 to-blue-600' : 'bg-gradient-to-r from-gray-400 to-gray-500'"></div>
          
          <!-- Hover Effect -->
          <div class="absolute inset-0 bg-gradient-to-br from-blue-50/0 to-blue-100/0 group-hover:from-blue-50/10 group-hover:to-blue-100/10 dark:from-blue-900/0 dark:to-blue-800/0 dark:group-hover:from-blue-900/10 dark:group-hover:to-blue-800/10 transition-all duration-300"></div>
          
          <div class="relative p-6">
            <!-- Logo and Title -->
            <div class="flex items-start space-x-4 mb-5">
              <div class="flex-shrink-0">
                <div v-if="workspace.logo_url" 
                  class="w-16 h-16 rounded-lg overflow-hidden border-2 border-white dark:border-gray-700 shadow-md">
                  <img :src="workspace.logo_url" :alt="workspace.nom" class="w-full h-full object-cover" />
                </div>
                <div v-else
                  class="w-16 h-16 rounded-lg bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center border-2 border-white dark:border-gray-700 shadow-md group-hover:shadow-lg transition-shadow">
                  <span class="text-white font-bold text-xl">
                    {{ getInitials(workspace.nom) }}
                  </span>
                </div>
              </div>
              
              <div class="flex-1 min-w-0">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                  {{ workspace.nom }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ workspace.code }}</p>
                
                <!-- Quick Actions -->
                <div class="flex items-center space-x-3 mt-3">
                  <span :class="[
                    'px-2 py-1 text-xs font-medium rounded-full',
                    workspace.is_active
                      ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                      : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400'
                  ]">
                    {{ workspace.is_active ? '● Actif' : '● Inactif' }}
                  </span>
                  <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ formatDate(workspace.created_at) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Description -->
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6 line-clamp-2">
              {{ workspace.description || 'Aucune description' }}
            </p>

            <!-- Stats -->
            <div class="flex items-center justify-between pt-5 border-t border-gray-100 dark:border-gray-700">
              <div class="flex items-center space-x-4">
                <!-- Projects -->
                <div class="flex items-center space-x-1.5">
                  <div class="w-8 h-8 rounded-md bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Projets</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ workspace.projets_count || 0 }}</p>
                  </div>
                </div>
                
                <!-- Members -->
                <div class="flex items-center space-x-1.5">
                  <div class="w-8 h-8 rounded-md bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.67 3.913a6 6 0 01-1.41 2.614M17.67 19.912A6 6 0 0015 15.197" />
                    </svg>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Membres</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ workspace.member_count || 0 }}</p>
                  </div>
                </div>
              </div>
              
              <!-- Hover Arrow -->
              <div class="opacity-0 group-hover:opacity-100 transform translate-x-2 group-hover:translate-x-0 transition-all duration-300">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
              </div>
            </div>
          </div>
          
          <!-- Context Menu -->
          <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
            <button @click.stop="openSettings(workspace)"
              class="w-8 h-8 rounded-md bg-white/80 dark:bg-gray-700/80 backdrop-blur-sm border border-gray-200 dark:border-gray-600 flex items-center justify-center hover:bg-white dark:hover:bg-gray-600 transition-colors shadow-sm">
              <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- List View (Asana-style) -->
      <div v-else class="space-y-3">
        <div v-for="workspace in filteredWorkspaces" :key="workspace.id"
          class="group bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 hover:shadow-md transition-all duration-200 cursor-pointer"
          @click="navigateToWorkspace(workspace.id)">
          
          <!-- Active Indicator -->
          <div class="flex items-center">
            <div class="w-1 h-16 rounded-l-lg" 
              :class="workspace.is_active ? 'bg-blue-600' : 'bg-gray-400'"></div>
            
            <div class="flex-1 p-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4 flex-1 min-w-0">
                  <!-- Logo -->
                  <div class="flex-shrink-0">
                    <div v-if="workspace.logo_url" 
                      class="w-12 h-12 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                      <img :src="workspace.logo_url" :alt="workspace.nom" class="w-full h-full object-cover" />
                    </div>
                    <div v-else
                      class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                      <span class="text-white font-bold text-sm">
                        {{ getInitials(workspace.nom) }}
                      </span>
                    </div>
                  </div>
                  
                  <!-- Info -->
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center space-x-3 mb-1">
                      <h3 class="text-base font-semibold text-gray-900 dark:text-white truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                        {{ workspace.nom }}
                      </h3>
                      <span class="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                        {{ workspace.code }}
                      </span>
                      <span :class="[
                        'text-xs px-2 py-1 rounded-full',
                        workspace.is_active
                          ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                          : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400'
                      ]">
                        {{ workspace.is_active ? 'Actif' : 'Inactif' }}
                      </span>
                    </div>
                    
                    <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                      {{ workspace.description || 'Aucune description' }}
                    </p>
                    
                    <div class="flex items-center space-x-4 mt-2">
                      <span class="text-xs text-gray-500 dark:text-gray-400">
                        Créé le {{ formatDate(workspace.created_at) }}
                      </span>
                    </div>
                  </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="hidden md:flex items-center space-x-6 mr-8">
                  <div class="text-center">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ workspace.projets_count || 0 }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Projets</p>
                  </div>
                  <div class="text-center">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ workspace.member_count || 0 }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Membres</p>
                  </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex items-center space-x-2">
                  <button @click.stop="openSettings(workspace)"
                    class="w-8 h-8 rounded-md bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 flex items-center justify-center transition-colors">
                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                    </svg>
                  </button>
                  
                  <!-- Hover Arrow -->
                  <div class="opacity-0 group-hover:opacity-100 transform translate-x-2 group-hover:translate-x-0 transition-all duration-300">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useWorkspace } from '@/composables/useWorkspace';
import AdminLayout from '@/components/layout/AdminLayout.vue';

const router = useRouter();
const { workspaces, loading, fetchWorkspaces, selectWorkspace } = useWorkspace();

const searchQuery = ref('');
const filterActive = ref('all');
const viewMode = ref<'grid' | 'list'>('grid');

const stats = computed(() => {
  const total = workspaces.value.length;
  const active = workspaces.value.filter(w => w.is_active).length;
  const totalProjects = workspaces.value.reduce((acc, w) => acc + (w.projets_count || 0), 0);
  const totalMembers = workspaces.value.reduce((acc, w) => acc + (w.member_count || 0), 0);

  return {
    total,
    active,
    totalProjects,
    totalMembers,
  };
});

const filteredWorkspaces = computed(() => {
  return workspaces.value.filter(workspace => {
    const matchesSearch =
      workspace.nom.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      workspace.code?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      workspace.description?.toLowerCase().includes(searchQuery.value.toLowerCase());

    const matchesFilter =
      filterActive.value === 'all' ||
      (filterActive.value === 'active' && workspace.is_active) ||
      (filterActive.value === 'inactive' && !workspace.is_active);

    return matchesSearch && matchesFilter;
  });
});

const getInitials = (name: string): string => {
  if (!name) return 'WS';
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
};

const formatDate = (dateString: string): string => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  });
};

const navigateToWorkspace = async (id: number) => {
  const workspace = workspaces.value.find(w => w.id === id);
  if (workspace) await selectWorkspace(workspace);
  router.push({ name: 'workspaces.show', params: { id } });
};

const openSettings = (workspace: any) => {
  router.push({ name: 'workspaces.settings', params: { id: workspace.id } });
};

onMounted(async () => {
  await fetchWorkspaces();
});
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

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 4px;
}

.dark ::-webkit-scrollbar-thumb {
  background: #4b5563;
}

::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

.dark ::-webkit-scrollbar-thumb:hover {
  background: #6b7280;
}
</style>