<!-- resources\js\pages\workspaces\Edit.vue -->
<template>
    <AdminLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Loading state -->
            <div v-if="loadingWorkspace" class="flex items-center justify-center min-h-screen">
                <div class="text-center">
                    <svg class="animate-spin h-12 w-12 text-brand-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <p class="text-gray-600 dark:text-gray-400">Chargement du workspace...</p>
                </div>
            </div>

            <!-- Content -->
            <template v-else-if="workspace">
                <!-- Header -->
                <div class="bg-white dark:bg-gray-800 shadow">
                    <div class="container mx-auto px-4 py-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <router-link :to="{ name: 'workspaces.show', params: { id: workspace.id } }"
                                    class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-3 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 19l-7-7 7-7" />
                                    </svg>
                                </router-link>
                                <div>
                                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                                        Modifier le workspace
                                    </h1>
                                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                                        {{ workspace.nom }}
                                    </p>
                                </div>
                            </div>

                            <!-- Delete Button -->
                            <button v-if="canDelete" @click="showDeleteModal = true"
                                class="px-4 py-2 bg-red-600 text-white rounded-3 hover:bg-red-700 transition-colors flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Supprimer
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Form Container -->
                <div class="container mx-auto px-4 py-8">
                    <div class="max-w-4xl mx-auto">
                        <form @submit.prevent="handleSubmit" class="space-y-6">
                            <!-- Informations de base -->
                            <div class="bg-white dark:bg-gray-800 rounded-3 p-6">
                                <h2
                                    class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                    <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Informations de base
                                </h2>

                                <div class="space-y-6">
                                    <!-- Code (Read-only) -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Code du workspace
                                        </label>
                                        <input :value="workspace.code" type="text" disabled
                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-3 bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 cursor-not-allowed" />
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Le code ne peut pas être modifié</p>
                                    </div>

                                    <!-- Nom du workspace -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Nom du workspace <span class="text-red-500">*</span>
                                        </label>
                                        <input v-model="form.nom" type="text" required
                                            placeholder="Ex: Entreprise XYZ - Projets 2024"
                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-3 focus:ring-2 focus:ring-brand-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                            :class="{ 'border-red-500': errors.nom }" />
                                        <p v-if="errors.nom" class="mt-1 text-sm text-red-500">{{ errors.nom }}</p>
                                    </div>

                                    <!-- Description -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Description
                                        </label>
                                        <textarea v-model="form.description" rows="4"
                                            placeholder="Décrivez l'objectif de ce workspace..."
                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-3 focus:ring-2 focus:ring-brand-500 focus:border-transparent dark:bg-gray-700 dark:text-white resize-none"
                                            :class="{ 'border-red-500': errors.description }"></textarea>
                                        <p v-if="errors.description" class="mt-1 text-sm text-red-500">{{
                                            errors.description }}</p>
                                    </div>

                                    <!-- Logo Upload -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Logo du workspace
                                        </label>
                                        <div class="flex items-center gap-4">
                                            <div v-if="logoPreview || workspace.logo_url"
                                                class="w-24 h-24 rounded-3 overflow-hidden border-2 border-gray-300 dark:border-gray-600">
                                                <img :src="logoPreview || workspace.logo_url" alt="Logo preview"
                                                    class="w-full h-full object-cover" />
                                            </div>
                                            <div v-else
                                                class="w-24 h-24 rounded-3 flex items-center justify-center text-white text-3xl font-bold">
                                                {{ workspaceInitials }}
                                            </div>
                                            <div class="flex-1">
                                                <input ref="logoInput" type="file" accept="image/*" class="hidden"
                                                    @change="handleLogoChange" />
                                                <button type="button" @click="$refs.logoInput.click()"
                                                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-3 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                                    {{ workspace.logo_url ? 'Changer le logo' : 'Ajouter un logo' }}
                                                </button>
                                                <button v-if="logoPreview || workspace.logo_url" type="button"
                                                    @click="removeLogo"
                                                    class="ml-2 px-4 py-2 bg-red-100 text-red-700 rounded-3 hover:bg-red-200 transition-colors">
                                                    Supprimer
                                                </button>
                                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                                    Format recommandé: PNG ou JPG, max 2 MB
                                                </p>
                                            </div>
                                        </div>
                                        <p v-if="errors.logo" class="mt-1 text-sm text-red-500">{{ errors.logo }}</p>
                                    </div>

                                    <!-- Status -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Statut
                                        </label>
                                        <div class="flex items-center gap-4">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="form.is_active" :value="true" type="radio"
                                                    class="w-4 h-4 text-brand-600 border-gray-300 focus:ring-brand-500" />
                                                <span class="text-gray-900 dark:text-white">Actif</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="form.is_active" :value="false" type="radio"
                                                    class="w-4 h-4 text-brand-600 border-gray-300 focus:ring-brand-500" />
                                                <span class="text-gray-900 dark:text-white">Archivé</span>
                                            </label>
                                        </div>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            Les workspaces archivés ne sont plus accessibles aux membres
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Paramètres -->
                            <div class="bg-white dark:bg-gray-800 rounded-3 p-6">
                                <h2
                                    class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                                    <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Paramètres
                                </h2>

                                <div class="space-y-6">
                                    <!-- Visibilité par défaut des projets -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Visibilité par défaut des projets
                                        </label>
                                        <select v-model="form.settings.default_project_visibility"
                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-3 focus:ring-2 focus:ring-brand-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                                            <option value="public">Public - Visible par tous les membres</option>
                                            <option value="team">Équipe - Visible par les membres du workspace</option>
                                            <option value="private">Privé - Visible uniquement par les membres du projet
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Permissions -->
                                    <div class="space-y-4">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Permissions des membres
                                        </label>

                                        <div class="space-y-3">
                                            <label
                                                class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-700 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                                <input v-model="form.settings.members_can_create_projects"
                                                    type="checkbox"
                                                    class="w-5 h-5 text-brand-600 border-gray-300 rounded focus:ring-brand-500" />
                                                <div class="flex-1">
                                                    <p class="font-medium text-gray-900 dark:text-white">Créer des
                                                        projets</p>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">Les membres peuvent créer de
                                                        nouveaux projets</p>
                                                </div>
                                            </label>

                                            <label
                                                class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-700 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                                <input v-model="form.settings.members_can_invite" type="checkbox"
                                                    class="w-5 h-5 text-brand-600 border-gray-300 rounded focus:ring-brand-500" />
                                                <div class="flex-1">
                                                    <p class="font-medium text-gray-900 dark:text-white">Inviter des
                                                        membres</p>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">Les membres peuvent inviter
                                                        d'autres utilisateurs</p>
                                                </div>
                                            </label>

                                            <label
                                                class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-700 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                                <input v-model="form.settings.require_task_validation" type="checkbox"
                                                    class="w-5 h-5 text-brand-600 border-gray-300 rounded focus:ring-brand-500" />
                                                <div class="flex-1">
                                                    <p class="font-medium text-gray-900 dark:text-white">Validation des
                                                        tâches requise</p>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">Les nouvelles tâches nécessitent
                                                        une validation</p>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Info supplémentaires -->
                            <div class="bg-white dark:bg-gray-800 rounded-3 p-6">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                                    Informations
                                </h2>

                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-400">Créé le</p>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ formatDate(workspace.created_at) }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-400">Dernière modification</p>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ formatDate(workspace.updated_at) }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-400">Propriétaire</p>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ workspace.owner?.nom || 'Non défini' }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-400">Nombre de membres</p>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ workspace.member_count || 0 }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div
                                class="flex items-center justify-end gap-4 bg-white dark:bg-gray-800 rounded-3 p-6">
                                <router-link :to="{ name: 'workspaces.show', params: { id: workspace.id } }"
                                    class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    Annuler
                                </router-link>
                                <button type="submit" :disabled="loading"
                                    class="px-6 py-3 bg-brand-600 text-white rounded-3 hover:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2">
                                    <svg v-if="loading" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    <span v-if="loading">Enregistrement...</span>
                                    <span v-else>Enregistrer les modifications</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </template>

            <!-- Delete Confirmation Modal -->
            <teleport to="body">
                <div v-if="showDeleteModal"
                    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
                    @click.self="showDeleteModal = false">
                    <div class="bg-white dark:bg-gray-800 rounded-3 max-w-md w-full p-6">
                        <div class="flex items-center gap-4 mb-4">
                            <div
                                class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    Supprimer le workspace
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Cette action est irréversible
                                </p>
                            </div>
                        </div>

                        <p class="text-gray-700 dark:text-gray-300 mb-6">
                            Êtes-vous sûr de vouloir supprimer ce workspace ?
                            Tous les projets, activités et tâches associés seront également supprimés.
                        </p>

                        <div class="flex items-center justify-end gap-3">
                            <button @click="showDeleteModal = false"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Annuler
                            </button>
                            <button @click="handleDelete" :disabled="deleting"
                                class="px-4 py-2 bg-red-600 text-white rounded-3 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                {{ deleting ? 'Suppression...' : 'Supprimer définitivement' }}
                            </button>
                        </div>
                    </div>
                </div>
            </teleport>
        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useWorkspace } from '@/composables/useWorkspace';
import { useAuthStore } from '@/stores/authStore';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import { useToast } from "vue-toastification"

const toast = useToast()
const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const { fetchWorkspace, updateWorkspace, deleteWorkspace } = useWorkspace();

const workspace = ref<any>(null);
const loadingWorkspace = ref(true);
const loading = ref(false);
const deleting = ref(false);
const showDeleteModal = ref(false);
const logoRemoved = ref(false);

const form = ref({
    nom: '',
    description: '',
    logo: null as File | null,
    is_active: true,
    settings: {
        default_project_visibility: 'team',
        members_can_create_projects: true,
        members_can_invite: false,
        require_task_validation: true,
    },
});

const errors = ref<Record<string, string>>({});
const logoPreview = ref<string | null>(null);
const logoInput = ref<HTMLInputElement | null>(null);

const workspaceInitials = computed(() => {
    if (!form.value.nom) return 'WS';
    return form.value.nom
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
});

const canDelete = computed(() => {
    return workspace.value?.owner_id === authStore.user?.id;
});

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const loadWorkspace = async () => {
    try {
        loadingWorkspace.value = true;
        const id = route.params.id as string;
        workspace.value = await fetchWorkspace(parseInt(id));

        // Populate form
        form.value.nom = workspace.value.nom;
        form.value.description = workspace.value.description || '';
        form.value.is_active = workspace.value.is_active;
        logoRemoved.value = false; // ✅ Réinitialiser

        // ✅ Fusion des settings avec les valeurs par défaut
        const defaultSettings = {
            default_project_visibility: 'team',
            members_can_create_projects: true,
            members_can_invite: false,
            require_task_validation: true,
        };

        form.value.settings = {
            ...defaultSettings,
            ...(workspace.value.settings || {}),
        };
    } catch (error) {
        console.error('Erreur lors du chargement du workspace:', error);
        toast.warning('Erreur lors du chargement du workspace')

        router.push({ name: 'workspaces.index' });
    } finally {
        loadingWorkspace.value = false;
    }
};

const handleLogoChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        if (file.size > 2 * 1024 * 1024) {
            errors.value.logo = 'Le fichier est trop volumineux (max 2 MB)';
            toast.error('Le fichier est trop volumineux (max 2 MB)')

            return;
        }

        if (!file.type.startsWith('image/')) {
            errors.value.logo = 'Le fichier doit être une image';
            toast.error('Le fichier doit être une image')

            return;
        }

        form.value.logo = file;
        errors.value.logo = '';
        logoRemoved.value = false; // ✅ Réinitialiser si un nouveau fichier est sélectionné

        const reader = new FileReader();
        reader.onload = (e) => {
            logoPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};


const removeLogo = () => {
    form.value.logo = null;
    logoPreview.value = null;
    logoRemoved.value = true; // ✅ Marquer que le logo a été supprimé

    // Si le workspace avait un logo, on le marque pour suppression
    if (workspace.value?.logo_url) {
        form.value.logo = null; // Cela indiquera au backend de supprimer le logo
    }

    if (logoInput.value) {
        logoInput.value.value = '';
    }
};

const validateForm = (): boolean => {
    errors.value = {};

    if (!form.value.nom.trim()) {
        errors.value.nom = 'Le nom du workspace est requis';
    }

    if (form.value.nom.length < 3) {
        errors.value.nom = 'Le nom doit contenir au moins 3 caractères';
    }

    if (form.value.description && form.value.description.length > 500) {
        errors.value.description = 'La description ne peut pas dépasser 500 caractères';
    }

    return Object.keys(errors.value).length === 0;
};

const handleSubmit = async () => {
    if (!validateForm()) {
        return;
    }

    loading.value = true;

    try {
        const formData = new FormData();
        formData.append('nom', form.value.nom);
        formData.append('description', form.value.description || '');
        formData.append('is_active', form.value.is_active ? '1' : '0');

        formData.append('settings', JSON.stringify(form.value.settings));

        // ✅ Gestion améliorée du logo
        if (form.value.logo instanceof File) {
            // Nouveau fichier sélectionné
            formData.append('logo', form.value.logo);
            console.log('Nouveau logo ajouté au FormData');
            toast.info('Nouveau logo ajouté')

        } else if (logoRemoved.value && workspace.value?.logo_url) {
            // L'utilisateur a explicitement supprimé le logo existant
            formData.append('remove_logo', 'true');
            console.log('Logo marqué pour suppression');
            toast.info('Logo marqué pour suppression')

        }
        // Sinon, ne rien faire (garder le logo existant)
        // ✅ Debug: Afficher le contenu du FormData
        console.log('=== FormData envoyé ===');
        for (let [key, value] of formData.entries()) {
            if (value instanceof File) {
                console.log(`${key}:`, value.name, value.type, value.size);
            } else {
                console.log(`${key}:`, value);
            }
        }

        await updateWorkspace(workspace.value.id, formData);
        toast.success('workspace mise à jour avec success');

        // Success - redirect to workspace details
        router.push({ name: 'workspaces.show', params: { id: workspace.value.id } });
    } catch (error: any) {
        console.error('Erreur lors de la mise à jour du workspace:', error);
        toast.warning('Erreur lors de la mise à jour du workspace')

        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            errors.value.general = error.response?.data?.message || 'Une erreur est survenue';
        }
    } finally {
        loading.value = false;
    }
};


const handleDelete = async () => {
    deleting.value = true;

    try {
        await deleteWorkspace(workspace.value.id);
        toast.success(' workspace supprimé avec success');

        router.push({ name: 'workspaces.index' });
    } catch (error: any) {
        console.error('Erreur lors de la suppression du workspace:', error);
        toast.warning('Erreur lors de la suppression du workspace')

        alert(error.response?.data?.message || 'Erreur lors de la suppression');
    } finally {
        deleting.value = false;
        showDeleteModal.value = false;
    }
};

onMounted(() => {
    loadWorkspace();
});
</script>

<style scoped>
/* Additional custom styles if needed */
</style>