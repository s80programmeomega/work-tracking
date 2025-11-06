<!-- resources\js\pages\workspaces\Show.vue -->
<template>
    <AdminLayout>
        <!-- <PageBreadcrumb :pageTitle="'Mes Workspaces'" /> -->
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

                        <!-- Recent Projects -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                                        Projets récents
                                    </h2>
                                    <router-link to="/projets/list/all"
                                        class="text-brand-600 hover:text-brand-700 text-sm font-medium">
                                        Voir tout
                                    </router-link>
                                </div>
                            </div>
                            <div class="p-6">
                                <div v-if="projects.length === 0" class="text-center py-8">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        Aucun projet pour le moment
                                    </p>
                                </div>
                                <div v-else class="space-y-3">
                                    <div v-for="projet in projects.slice(0, 5)" :key="projet.id"
                                        class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer"
                                        @click="navigateToProject(projet.id)">
                                        <div class="flex-1">
                                            <h3 class="font-medium text-gray-900 dark:text-white">
                                                {{ projet.nom }}
                                            </h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                {{ projet.code }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ projet.activites_count || 0 }} activités
                                            </div>
                                            <span :class="[
                                                'px-2 py-1 text-xs font-medium rounded-full',
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

                    <!-- Projects Tab -->
                    <div v-if="activeTab === 'projects'" class="space-y-6">
                        <div class="flex items-center justify-between">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                                Tous les projets
                            </h2>
                            <button
                                class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors">
                                Nouveau projet
                            </button>
                        </div>

                        <div v-if="loadingProjects" class="flex justify-center py-12">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-600"></div>
                        </div>

                        <div v-else-if="projects.length === 0"
                            class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucun projet</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Commencez par créer un nouveau projet
                            </p>
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="projet in projects" :key="projet.id"
                                class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-all cursor-pointer border border-gray-200 dark:border-gray-700 p-6"
                                @click="navigateToProject(projet.id)">
                                <div class="flex items-start justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ projet.nom }}
                                    </h3>
                                    <span :class="[
                                        'px-2 py-1 text-xs font-medium rounded-full',
                                        getStatusColor(projet.status)
                                    ]">
                                        {{ getStatusLabel(projet.status) }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                                    {{ projet.description || 'Aucune description' }}
                                </p>
                                <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                                    <span>{{ projet.activites_count || 0 }} activités</span>
                                    <span>{{ projet.members_count || 0 }} membres</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Members Tab -->
                    <div v-if="activeTab === 'members'" class="space-y-6">
                        

                        <div v-if="activeTab === 'members'" class="space-y-6">
                            <WorkspaceMemberManagement :workspace-id="workspace.id"
                                @member-updated="handleMemberUpdated" />
                        </div>

                        <div v-else class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Membre
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Rôle
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Ajouté le
                                        </th>
                                        <th
                                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="member in members" :key="member.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <img v-if="member.avatar" :src="member.avatar" :alt="member.nom"
                                                        class="h-10 w-10 rounded-full" />
                                                    <div v-else
                                                        class="h-10 w-10 rounded-full bg-brand-600 flex items-center justify-center text-white font-medium">
                                                        {{ getInitials(member.nom) }}
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ member.nom }}
                                                    </div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                                        {{ member.email }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="[
                                                'px-2 py-1 text-xs font-medium rounded-full',
                                                getRoleColor(member.pivot?.role)
                                            ]">
                                                {{ getRoleLabel(member.pivot?.role) }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ formatDate(member.pivot?.invited_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button v-if="member.pivot?.role !== 'owner'" @click="editMember(member)"
                                                class="text-brand-600 hover:text-brand-900 dark:text-brand-400 dark:hover:text-brand-300 mr-3">
                                                Modifier
                                            </button>
                                            <button v-if="member.pivot?.role !== 'owner'"
                                                @click="removeMemberConfirm(member)"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                Retirer
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useWorkspace } from '@/composables/useWorkspace';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import WorkspaceMemberManagement from '@/components/workspaces/WorkspaceMemberManagement.vue';

const route = useRoute();
const router = useRouter();
const { fetchWorkspace, fetchProjects, fetchMembers, fetchStatistics } = useWorkspace();

const workspace = ref<any>(null);
const projects = ref<any[]>([]);
const members = ref<any[]>([]);
const statistics = ref<any>({});

const loading = ref(true);
const loadingProjects = ref(false);
const loadingMembers = ref(false);

const activeTab = ref('overview');
// const showAddMemberModal = ref(false);

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

const getStatusColor = (status: string): string => {
    const colors: Record<string, string> = {
        active: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        completed: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        archived: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    };
    return colors[status] || colors.pending;
};

const getStatusLabel = (status: string): string => {
    const labels: Record<string, string> = {
        active: 'Actif',
        completed: 'Terminé',
        archived: 'Archivé',
        pending: 'En attente',
    };
    return labels[status] || status;
};

const getRoleColor = (role: string): string => {
    const colors: Record<string, string> = {
        owner: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        admin: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        member: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        viewer: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    };
    return colors[role] || colors.viewer;
};

const getRoleLabel = (role: string): string => {
    const labels: Record<string, string> = {
        owner: 'Propriétaire',
        admin: 'Administrateur',
        member: 'Membre',
        viewer: 'Observateur',
    };
    return labels[role] || role;
};

const formatDate = (date: string): string => {
    if (!date) return '-';
    const d = new Date(date);
    return d.toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const navigateToProject = (projectId: number) => {
    router.push({ name: 'projets.show', params: { id: projectId } });
};

const editMember = (member: any) => {
    console.log('Edit member:', member);
    // TODO: Implement edit member modal
};

const removeMemberConfirm = (member: any) => {
    console.log('Remove member:', member);
    // TODO: Implement remove member confirmation
};

const loadWorkspaceData = async () => {
    try {
        loading.value = true;
        const workspaceId = parseInt(route.params.id as string);

        workspace.value = await fetchWorkspace(workspaceId);

        // Load statistics
        statistics.value = await fetchStatistics(workspaceId);
    } catch (error) {
        console.error('Error loading workspace:', error);
    } finally {
        loading.value = false;
    }
};

const loadProjects = async () => {
    try {
        loadingProjects.value = true;
        const workspaceId = parseInt(route.params.id as string);
        const response = await fetchProjects(workspaceId);
        projects.value = response.data || [];
    } catch (error) {
        console.error('Error loading projects:', error);
    } finally {
        loadingProjects.value = false;
    }
};

const loadMembers = async () => {
    try {
        loadingMembers.value = true;
        const workspaceId = parseInt(route.params.id as string);
        members.value = await fetchMembers(workspaceId);
    } catch (error) {
        console.error('Error loading members:', error);
    } finally {
        loadingMembers.value = false;
    }
};

onMounted(async () => {
    await loadWorkspaceData();
    await loadProjects();
    await loadMembers();
});
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>