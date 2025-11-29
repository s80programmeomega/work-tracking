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
                <button @click="showWorkspaceMenu = !showWorkspaceMenu"
                  class="flex items-center gap-2 px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/>
                  </svg>
                  Menu
                </button>
              </div>
            </div>

            <!-- Workspace Menu -->
            <div v-if="showWorkspaceMenu" class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
              <div class="flex items-center gap-4">
                <router-link :to="{ name: 'workspaces.edit', params: { id: workspace.id } }"
                  class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-600 rounded transition-colors">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                  Modifier
                </router-link>

                <router-link :to="{ name: 'workspaces.settings', params: { id: workspace.id } }"
                  class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-600 rounded transition-colors">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  </svg>
                  Paramètres
                </router-link>
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
                    <button @click="activeTab = 'projects'"
                      class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                      Voir tous les projets
                    </button>
                  </div>
                  <ProjetList ref="recentProjetsList" :workspace-id="workspace.id" :limit="5"
                    :show-header="false" :show-filters="false" @view-projet="navigateToProject"/>
                </div>

                <!-- Recent Activity -->
                <div>
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Activité récente</h3>
                  <div class="space-y-3">
                    <div v-for="activity in recentActivities" :key="activity.id"
                      class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                      <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                      </div>
                      <div class="flex-1">
                        <p class="text-sm text-gray-900 dark:text-white">{{ activity.description }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ activity.time }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Projects Tab -->
              <div v-if="activeTab === 'projects'">
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
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useWorkspace } from '@/composables/useWorkspace';
import ProjetList from '@/components/projets/ProjetList.vue';
import WorkspaceMemberManagement from '@/components/workspaces/WorkspaceMemberManagement.vue';
import AdminLayout from '@/components/layout/AdminLayout.vue';

const route = useRoute();
const router = useRouter();
const { fetchWorkspace, fetchStatistics } = useWorkspace();

const workspace = ref<any>(null);
const statistics = ref<any>({});
const loading = ref(true);
const showWorkspaceMenu = ref(false);

const activeTab = ref('overview');
const recentProjetsList = ref<any>(null);
const allProjetsList = ref<any>(null);

const tabs = [
  { id: 'overview', label: 'Tableau de bord' },
  { id: 'projects', label: 'Projets' },
  { id: 'members', label: 'Membres' },
];

// Données d'exemple pour l'activité récente
const recentActivities = ref([
  { id: 1, description: 'Nouveau projet "Site E-commerce" créé', time: 'Il y a 2 heures' },
  { id: 2, description: 'Marie a rejoint le workspace', time: 'Il y a 4 heures' },
  { id: 3, description: 'Projet "Application Mobile" terminé', time: 'Il y a 1 jour' },
]);

const handleMemberUpdated = () => {
  loadWorkspaceData();
};

const getInitials = (name: string): string => {
  if (!name) return 'U';
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
};

const navigateToProject = (projectId: number) => {
  router.push({ name: 'projets.show', params: { id: projectId } });
};

const loadWorkspaceData = async () => {
  try {
    loading.value = true;
    const workspaceId = parseInt(route.params.id as string);

    workspace.value = await fetchWorkspace(workspaceId);
    statistics.value = await fetchStatistics(workspaceId);
  } catch (error) {
    console.error('Error loading workspace:', error);
    workspace.value = null;
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  await loadWorkspaceData();
});
</script>