<!-- resources/js/pages/workspaces/Show.vue -->
<template>
    <AdminLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center h-screen">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-600"></div>
            </div>

            <template v-else-if="workspace">
                <!-- Header -->
                <div class="bg-white dark:bg-gray-800 shadow">
                    <div class="container mx-auto px-4 py-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <router-link to="/workspaces"
                                    class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 19l-7-7 7-7" />
                                    </svg>
                                </router-link>

                                <div v-if="workspace.logo_url" class="w-16 h-16 rounded-lg overflow-hidden">
                                    <img :src="workspace.logo_url" :alt="workspace.nom"
                                        class="w-full h-full object-cover" />
                                </div>
                                <div v-else
                                    class="w-16 h-16 rounded-lg bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center">
                                    <span class="text-white font-bold text-2xl">
                                        {{ getInitials(workspace.nom) }}
                                    </span>
                                </div>

                                <div>
                                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                                        {{ workspace.nom }}
                                    </h1>
                                    <div class="flex items-center gap-3 mt-1">
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

                            <div class="flex items-center gap-2">
                                <router-link :to="{ name: 'workspaces.edit', params: { id: workspace.id } }"
                                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Modifier
                                </router-link>
                                <router-link :to="{ name: 'workspaces.settings', params: { id: workspace.id } }"
                                    class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Paramètres
                                </router-link>
                            </div>
                        </div>

                        <p v-if="workspace.description" class="text-gray-600 dark:text-gray-400 mt-4">
                            {{ workspace.description }}
                        </p>
                    </div>
                </div>

                <!-- Tabs Navigation -->
                <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <div class="container mx-auto px-4">
                        <div class="flex space-x-8">
                            <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" :class="[
                                'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                                activeTab === tab.id
                                    ? 'border-brand-600 text-brand-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                            ]">
                                {{ tab.label }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="container mx-auto px-4 py-8">
                    <!-- Overview Tab -->
                    <div v-if="activeTab === 'overview'" class="space-y-6">
                        <!-- Statistics -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Projets</p>
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                                            {{ statistics.total_projets || 0 }}
                                        </p>
                                    </div>
                                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="mt-4 flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <span class="text-green-600 dark:text-green-400 font-medium">
                                        {{ statistics.projets_actifs || 0 }} actifs
                                    </span>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Activités</p>
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                                            {{ statistics.total_activites || 0 }}
                                        </p>
                                    </div>
                                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                                        <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Tâches</p>
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                                            {{ statistics.total_taches || 0 }}
                                        </p>
                                    </div>
                                    <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="mt-4 flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <span class="text-green-600 dark:text-green-400 font-medium">
                                        {{ statistics.taux_completion || 0 }}% complétées
                                    </span>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Membres</p>
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                                            {{ workspace.member_count || 0 }}
                                        </p>
                                    </div>
                                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Projets récents avec ProjetList -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                                        Projets récents
                                    </h2>
                                    <button 
                                        @click="activeTab = 'projects'"
                                        class="text-brand-600 hover:text-brand-700 text-sm font-medium"
                                    >
                                        Voir tout
                                    </button>
                                </div>
                            </div>
                            <div class="p-6">
                                <ProjetList 
                                    ref="recentProjetsList"
                                    :workspace-id="workspace.id"
                                    :limit="5"
                                    :show-header="false"
                                    :show-filters="false"
                                    @view-projet="navigateToProject"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Projects Tab - Version complète avec ProjetList -->
                    <div v-if="activeTab === 'projects'" class="space-y-6">
                        <ProjetList 
                            ref="allProjetsList"
                            :workspace-id="workspace.id"
                            @view-projet="navigateToProject"
                        />
                    </div>

                    <!-- Members Tab -->
                    <div v-if="activeTab === 'members'" class="space-y-6">
                        <WorkspaceMemberManagement 
                            :workspace-id="workspace.id"
                            @member-updated="handleMemberUpdated" 
                        />
                    </div>
                </div>
            </template>

            <!-- Error State -->
            <div v-else class="flex items-center justify-center h-screen">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-white">Workspace introuvable</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Le workspace demandé n'existe pas ou vous n'y avez pas accès.
                    </p>
                    <router-link to="/workspaces"
                        class="mt-4 inline-flex items-center px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors">
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

const activeTab = ref('overview');
const recentProjetsList = ref<any>(null);
const allProjetsList = ref<any>(null);

const tabs = [
    { id: 'overview', label: 'Vue d\'ensemble' },
    { id: 'projects', label: 'Projets' },
    { id: 'members', label: 'Membres' },
];

// Nouvelle fonction pour gérer les mises à jour des membres
const handleMemberUpdated = () => {
    console.log('Membre mis à jour - recharger les données si nécessaire');
    loadWorkspaceData(); // Pour mettre à jour le compteur de membres
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

// Recharger les projets quand on change d'onglet
const handleTabChange = async (tabId: string) => {
    activeTab.value = tabId;
    
    // Attendre que le composant soit rendu
    await nextTick();
    
    if (tabId === 'projects' && allProjetsList.value) {
        // Recharger les projets complets
        allProjetsList.value.fetchProjets();
    } else if (tabId === 'overview' && recentProjetsList.value) {
        // Recharger les projets récents
        recentProjetsList.value.fetchProjets();
    }
};

onMounted(async () => {
    await loadWorkspaceData();
});
</script>