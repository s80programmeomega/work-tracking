<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="'Mes Workspaces'" />
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
                            <router-link
                                :to="{ name: 'workspaces.show', params: { id: workspace.id } }"
                                class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-3 transition-colors"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </router-link>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                                    Paramètres du workspace
                                </h1>
                                <p class="text-gray-600 dark:text-gray-400 mt-1">
                                    {{ workspace.nom }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Navigation -->
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="container mx-auto px-4">
                    <div class="flex space-x-8">
                        <button
                            v-for="tab in settingsTabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                                activeTab === tab.id
                                    ? 'border-brand-600 text-brand-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="container mx-auto px-4 py-8">
                <div class="max-w-4xl mx-auto space-y-6">
                    <!-- General Settings -->
                    <div v-if="activeTab === 'general'" class="space-y-6">
                        <div class="bg-white dark:bg-gray-800 rounded-3 shadow p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                                Paramètres généraux
                            </h2>

                            <div class="space-y-6">
                                <!-- Workspace Visibility -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Visibilité du workspace
                                    </label>
                                    <select
                                        v-model="settings.visibility"
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-3 focus:ring-2 focus:ring-brand-500 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option value="private">Privé - Uniquement les membres invités</option>
                                        <option value="internal">Interne - Tous les utilisateurs de l'organisation</option>
                                        <option value="public">Public - Visible par tous</option>
                                    </select>
                                    <p class="mt-2 text-sm text-gray-500">
                                        Contrôlez qui peut voir et accéder à ce workspace
                                    </p>
                                </div>

                                <!-- Default Project Settings -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Visibilité par défaut des nouveaux projets
                                    </label>
                                    <select
                                        v-model="settings.default_project_visibility"
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-3 focus:ring-2 focus:ring-brand-500 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option value="public">Public</option>
                                        <option value="team">Équipe</option>
                                        <option value="private">Privé</option>
                                    </select>
                                </div>

                                <!-- Time Zone -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Fuseau horaire
                                    </label>
                                    <select
                                        v-model="settings.timezone"
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-3 focus:ring-2 focus:ring-brand-500 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option value="Africa/Douala">Afrique/Douala (GMT+1)</option>
                                        <option value="Europe/Paris">Europe/Paris (GMT+1)</option>
                                        <option value="UTC">UTC (GMT+0)</option>
                                    </select>
                                </div>

                                <!-- Language -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Langue par défaut
                                    </label>
                                    <select
                                        v-model="settings.language"
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-3 focus:ring-2 focus:ring-brand-500 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option value="fr">Français</option>
                                        <option value="en">English</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button
                                    @click="saveSettings"
                                    :disabled="saving"
                                    class="px-6 py-3 bg-brand-600 text-white rounded-3 hover:bg-brand-700 disabled:opacity-50 transition-colors"
                                >
                                    {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Permissions -->
                    <div v-if="activeTab === 'permissions'" class="space-y-6">
                        <div class="bg-white dark:bg-gray-800 rounded-3 shadow p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                                Permissions des membres
                            </h2>

                            <div class="space-y-4">
                                <label class="flex items-start gap-3 p-4 border border-gray-200 dark:border-gray-700 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                    <input
                                        v-model="settings.members_can_create_projects"
                                        type="checkbox"
                                        class="mt-1 w-5 h-5 text-brand-600 border-gray-300 rounded focus:ring-brand-500"
                                    />
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-white">Créer des projets</p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Permet aux membres de créer de nouveaux projets dans ce workspace
                                        </p>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 p-4 border border-gray-200 dark:border-gray-700 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                    <input
                                        v-model="settings.members_can_invite"
                                        type="checkbox"
                                        class="mt-1 w-5 h-5 text-brand-600 border-gray-300 rounded focus:ring-brand-500"
                                    />
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-white">Inviter des membres</p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Permet aux membres d'inviter d'autres utilisateurs au workspace
                                        </p>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 p-4 border border-gray-200 dark:border-gray-700 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                    <input
                                        v-model="settings.members_can_delete_projects"
                                        type="checkbox"
                                        class="mt-1 w-5 h-5 text-brand-600 border-gray-300 rounded focus:ring-brand-500"
                                    />
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-white">Supprimer des projets</p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Permet aux membres de supprimer des projets
                                        </p>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 p-4 border border-gray-200 dark:border-gray-700 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                    <input
                                        v-model="settings.require_task_validation"
                                        type="checkbox"
                                        class="mt-1 w-5 h-5 text-brand-600 border-gray-300 rounded focus:ring-brand-500"
                                    />
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-white">Validation des tâches requise</p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Les tâches créées nécessitent une validation avant d'être actives
                                        </p>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 p-4 border border-gray-200 dark:border-gray-700 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                    <input
                                        v-model="settings.require_approval_for_time_off"
                                        type="checkbox"
                                        class="mt-1 w-5 h-5 text-brand-600 border-gray-300 rounded focus:ring-brand-500"
                                    />
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-white">Approbation des congés</p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Les demandes de congés nécessitent une approbation
                                        </p>
                                    </div>
                                </label>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button
                                    @click="saveSettings"
                                    :disabled="saving"
                                    class="px-6 py-3 bg-brand-600 text-white rounded-3 hover:bg-brand-700 disabled:opacity-50 transition-colors"
                                >
                                    {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <div v-if="activeTab === 'notifications'" class="space-y-6">
                        <div class="bg-white dark:bg-gray-800 rounded-3 shadow p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                                Paramètres de notification
                            </h2>

                            <div class="space-y-4">
                                <label class="flex items-start gap-3 p-4 border border-gray-200 dark:border-gray-700 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                    <input
                                        v-model="settings.notify_on_new_member"
                                        type="checkbox"
                                        class="mt-1 w-5 h-5 text-brand-600 border-gray-300 rounded focus:ring-brand-500"
                                    />
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-white">Nouveau membre</p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Notifier tous les membres quand quelqu'un rejoint le workspace
                                        </p>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 p-4 border border-gray-200 dark:border-gray-700 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                    <input
                                        v-model="settings.notify_on_new_project"
                                        type="checkbox"
                                        class="mt-1 w-5 h-5 text-brand-600 border-gray-300 rounded focus:ring-brand-500"
                                    />
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-white">Nouveau projet</p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Notifier les membres quand un nouveau projet est créé
                                        </p>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 p-4 border border-gray-200 dark:border-gray-700 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                    <input
                                        v-model="settings.notify_on_task_assigned"
                                        type="checkbox"
                                        class="mt-1 w-5 h-5 text-brand-600 border-gray-300 rounded focus:ring-brand-500"
                                    />
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-white">Tâche assignée</p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Notifier quand une tâche est assignée
                                        </p>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 p-4 border border-gray-200 dark:border-gray-700 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                    <input
                                        v-model="settings.notify_on_deadline_approaching"
                                        type="checkbox"
                                        class="mt-1 w-5 h-5 text-brand-600 border-gray-300 rounded focus:ring-brand-500"
                                    />
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-white">Échéance proche</p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Notifier quand une échéance approche (24h avant)
                                        </p>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 p-4 border border-gray-200 dark:border-gray-700 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                    <input
                                        v-model="settings.weekly_digest"
                                        type="checkbox"
                                        class="mt-1 w-5 h-5 text-brand-600 border-gray-300 rounded focus:ring-brand-500"
                                    />
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-white">Résumé hebdomadaire</p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Envoyer un résumé hebdomadaire de l'activité du workspace
                                        </p>
                                    </div>
                                </label>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button
                                    @click="saveSettings"
                                    :disabled="saving"
                                    class="px-6 py-3 bg-brand-600 text-white rounded-3 hover:bg-brand-700 disabled:opacity-50 transition-colors"
                                >
                                    {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Danger Zone -->
                    <div v-if="activeTab === 'danger'" class="space-y-6">
                        <div class="bg-white dark:bg-gray-800 rounded-3 shadow border-2 border-red-200 dark:border-red-800 p-6">
                            <h2 class="text-xl font-semibold text-red-600 dark:text-red-400 mb-6">
                                Zone dangereuse
                            </h2>

                            <div class="space-y-6">
                                <!-- Archive Workspace -->
                                <div class="flex items-start justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-3">
                                    <div class="flex-1">
                                        <h3 class="font-medium text-gray-900 dark:text-white">
                                            Archiver le workspace
                                        </h3>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Le workspace ne sera plus accessible mais les données seront conservées
                                        </p>
                                    </div>
                                    <button
                                        @click="showArchiveModal = true"
                                        class="px-4 py-2 bg-yellow-100 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400 rounded-3 hover:bg-yellow-200 dark:hover:bg-yellow-900/40 transition-colors"
                                    >
                                        Archiver
                                    </button>
                                </div>

                                <!-- Transfer Ownership -->
                                <div class="flex items-start justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-3">
                                    <div class="flex-1">
                                        <h3 class="font-medium text-gray-900 dark:text-white">
                                            Transférer la propriété
                                        </h3>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Transférer la propriété du workspace à un autre membre
                                        </p>
                                    </div>
                                    <button
                                        @click="showTransferModal = true"
                                        class="px-4 py-2 bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400 rounded-3 hover:bg-blue-200 dark:hover:bg-blue-900/40 transition-colors"
                                    >
                                        Transférer
                                    </button>
                                </div>

                                <!-- Delete Workspace -->
                                <div class="flex items-start justify-between p-4 border border-red-200 dark:border-red-800 rounded-3 bg-red-50 dark:bg-red-900/10">
                                    <div class="flex-1">
                                        <h3 class="font-medium text-red-600 dark:text-red-400">
                                            Supprimer le workspace
                                        </h3>
                                        <p class="text-sm text-red-500 dark:text-red-400 mt-1">
                                            Cette action est irréversible. Tous les projets, activités et tâches seront définitivement supprimés.
                                        </p>
                                    </div>
                                    <button
                                        @click="showDeleteModal = true"
                                        class="px-4 py-2 bg-red-600 text-white rounded-3 hover:bg-red-700 transition-colors"
                                    >
                                        Supprimer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <div
                v-if="showDeleteModal"
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
                @click.self="showDeleteModal = false"
            >
                <div class="bg-white dark:bg-gray-800 rounded-3 max-w-md w-full mx-4 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-3 bg-red-100 dark:bg-red-900/20 rounded-full">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Supprimer le workspace
                            </h3>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Êtes-vous absolument sûr ? Cette action est irréversible.
                    </p>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Tapez <span class="font-mono text-red-600">{{ workspace?.nom }}</span> pour confirmer
                        </label>
                        <input
                            v-model="deleteConfirmation"
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-3 focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white"
                            placeholder="Nom du workspace"
                        />
                    </div>
                    <div class="flex justify-end gap-3">
                        <button
                            @click="showDeleteModal = false"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            Annuler
                        </button>
                        <button
                            @click="handleDelete"
                            :disabled="deleteConfirmation !== workspace?.nom || deleting"
                            class="px-4 py-2 bg-red-600 text-white rounded-3 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <svg v-if="deleting" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span v-if="deleting">Suppression...</span>
                            <span v-else>Supprimer définitivement</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Archive Modal (placeholder) -->
            <div
                v-if="showArchiveModal"
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
                @click.self="showArchiveModal = false"
            >
                <div class="bg-white dark:bg-gray-800 rounded-3 max-w-md w-full mx-4 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Archiver le workspace
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Le workspace sera archivé et ne sera plus accessible. Vous pourrez le restaurer plus tard.
                    </p>
                    <div class="flex justify-end gap-3">
                        <button
                            @click="showArchiveModal = false"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            Annuler
                        </button>
                        <button
                            @click="handleArchive"
                            class="px-4 py-2 bg-yellow-600 text-white rounded-3 hover:bg-yellow-700"
                        >
                            Archiver
                        </button>
                    </div>
                </div>
            </div>

            <!-- Transfer Modal (placeholder) -->
            <div
                v-if="showTransferModal"
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
                @click.self="showTransferModal = false"
            >
                <div class="bg-white dark:bg-gray-800 rounded-3 max-w-md w-full mx-4 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Transférer la propriété
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Fonctionnalité de transfert de propriété à implémenter
                    </p>
                    <div class="flex justify-end gap-3">
                        <button
                            @click="showTransferModal = false"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            Annuler
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useWorkspace } from '@/composables/useWorkspace';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';  
import { useToast } from "vue-toastification"

const toast = useToast()
const route = useRoute();
const router = useRouter();
const { fetchWorkspace, updateWorkspace, deleteWorkspace } = useWorkspace();

const workspaceId = parseInt(route.params.id as string);

const workspace = ref<any>(null);
const loading = ref(true);
const saving = ref(false);
const deleting = ref(false);
const activeTab = ref('general');

const showDeleteModal = ref(false);
const showArchiveModal = ref(false);
const showTransferModal = ref(false);
const deleteConfirmation = ref('');

const settingsTabs = [
    { id: 'general', label: 'Général' },
    { id: 'permissions', label: 'Permissions' },
    { id: 'notifications', label: 'Notifications' },
    { id: 'danger', label: 'Zone dangereuse' },
];

const settings = ref({
    // General
    visibility: 'private',
    default_project_visibility: 'team',
    timezone: 'Africa/Douala',
    language: 'fr',
    
    // Permissions
    members_can_create_projects: true,
    members_can_invite: false,
    members_can_delete_projects: false,
    require_task_validation: true,
    require_approval_for_time_off: true,
    
    // Notifications
    notify_on_new_member: true,
    notify_on_new_project: true,
    notify_on_task_assigned: true,
    notify_on_deadline_approaching: true,
    weekly_digest: false,
});

const loadWorkspace = async () => {
    try {
        loading.value = true;
        workspace.value = await fetchWorkspace(workspaceId);
        
        // Load settings from workspace
        if (workspace.value.settings) {
            settings.value = {
                ...settings.value,
                ...workspace.value.settings,
            };
        }
    } catch (error) {
        console.error('Error loading workspace:', error);
        toast.warning('Erreur lors du chargement du workspace')

        router.push({ name: 'workspaces.index' });
    } finally {
        loading.value = false;
    }
};

const saveSettings = async () => {
    saving.value = true;
    
    try {
        await updateWorkspace(workspaceId, {
            settings: settings.value,
        });
        
        console.log('Paramètres enregistrés avec succès');
        toast.success('Paramètres enregistrés avec succès')

        // TODO: Add success notification
    } catch (error) {
        console.error('Error saving settings:', error);
        // TODO: Add error notification
    } finally {
        saving.value = false;
    }
};

const handleDelete = async () => {
    if (deleteConfirmation.value !== workspace.value?.nom) {
        return;
    }

    deleting.value = true;

    try {
        await deleteWorkspace(workspaceId);
        toast.success(' workspace supprimé avec success'); 

        router.push({ name: 'workspaces.index' });
    } catch (error) {
        console.error('Error deleting workspace:', error);
        toast.error('Error deleting workspace');

        // TODO: Add error notification
    } finally {
        deleting.value = false;
        showDeleteModal.value = false;
    }
};

const handleArchive = async () => {
    try {
        // TODO: Implement archive functionality
        console.log('Archive workspace');
        showArchiveModal.value = false;
    } catch (error) {
        console.error('Error archiving workspace:', error);
        toast.error('Error archiving workspace')

    }
};

onMounted(() => {
    loadWorkspace();
});
</script>

<style scoped>
/* Additional custom styles if needed */
</style>