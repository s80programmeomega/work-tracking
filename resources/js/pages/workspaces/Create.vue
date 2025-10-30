<template>
  <AdminLayout>
    <!-- <PageBreadcrumb :pageTitle="'Mes Workspaces'" /> -->

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
      <!-- Header -->
      <div class="bg-white dark:bg-gray-800 shadow">
        <div class="container mx-auto px-4 py-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
              <router-link to="/workspaces"
                class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
              </router-link>
              <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                  Créer un nouveau workspace
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                  Organisez vos projets dans un espace de travail dédié
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Form Container -->
      <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
          <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Informations de base -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
              <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Informations de base
              </h2>

              <div class="space-y-6">
                <!-- Nom du workspace -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Nom du workspace <span class="text-red-500">*</span>
                  </label>
                  <input v-model="form.nom" type="text" required placeholder="Ex: Entreprise XYZ - Projets 2024"
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    :class="{ 'border-red-500': errors.nom }" />
                  <p v-if="errors.nom" class="mt-1 text-sm text-red-500">{{ errors.nom }}</p>
                </div>

                <!-- Description -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Description
                  </label>
                  <textarea v-model="form.description" rows="4" placeholder="Décrivez l'objectif de ce workspace..."
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent dark:bg-gray-700 dark:text-white resize-none"
                    :class="{ 'border-red-500': errors.description }"></textarea>
                  <p v-if="errors.description" class="mt-1 text-sm text-red-500">{{ errors.description }}</p>
                  <p class="mt-1 text-sm text-gray-500">Optionnel - Expliquez à quoi servira ce workspace</p>
                </div>

                <!-- Logo Upload -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Logo du workspace
                  </label>
                  <div class="flex items-center gap-4">
                    <div v-if="logoPreview"
                      class="w-24 h-24 rounded-lg overflow-hidden border-2 border-gray-300 dark:border-gray-600">
                      <img :src="logoPreview" alt="Logo preview" class="w-full h-full object-cover" />
                    </div>
                    <div v-else
                      class="w-24 h-24 rounded-lg bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white text-3xl font-bold">
                      {{ workspaceInitials }}
                    </div>
                    <div class="flex-1">
                      <input ref="logoInput" type="file" accept="image/*" class="hidden" @change="handleLogoChange" />
                      <button type="button" @click="$refs.logoInput.click()"
                        class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        Choisir un logo
                      </button>
                      <button v-if="logoPreview" type="button" @click="removeLogo"
                        class="ml-2 px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors">
                        Supprimer
                      </button>
                      <p class="mt-2 text-sm text-gray-500">
                        Format recommandé: PNG ou JPG, max 2 MB
                      </p>
                    </div>
                  </div>
                  <p v-if="errors.logo" class="mt-1 text-sm text-red-500">{{ errors.logo }}</p>
                </div>
              </div>
            </div>

            <!-- Paramètres -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
              <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    <option value="public">Public - Visible par tous les membres</option>
                    <option value="team">Équipe - Visible par les membres du workspace</option>
                    <option value="private">Privé - Visible uniquement par les membres du projet</option>
                  </select>
                </div>

                <!-- Permissions -->
                <div class="space-y-4">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Permissions des membres
                  </label>

                  <div class="space-y-3">
                    <label
                      class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                      <input v-model="form.settings.members_can_create_projects" type="checkbox"
                        class="w-5 h-5 text-brand-600 border-gray-300 rounded focus:ring-brand-500" />
                      <div class="flex-1">
                        <p class="font-medium text-gray-900 dark:text-white">Créer des projets</p>
                        <p class="text-sm text-gray-500">Les membres peuvent créer de nouveaux projets</p>
                      </div>
                    </label>

                    <label
                      class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                      <input v-model="form.settings.members_can_invite" type="checkbox"
                        class="w-5 h-5 text-brand-600 border-gray-300 rounded focus:ring-brand-500" />
                      <div class="flex-1">
                        <p class="font-medium text-gray-900 dark:text-white">Inviter des membres</p>
                        <p class="text-sm text-gray-500">Les membres peuvent inviter d'autres utilisateurs</p>
                      </div>
                    </label>

                    <label
                      class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                      <input v-model="form.settings.require_task_validation" type="checkbox"
                        class="w-5 h-5 text-brand-600 border-gray-300 rounded focus:ring-brand-500" />
                      <div class="flex-1">
                        <p class="font-medium text-gray-900 dark:text-white">Validation des tâches requise</p>
                        <p class="text-sm text-gray-500">Les nouvelles tâches nécessitent une validation</p>
                      </div>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-4 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
              <router-link to="/workspaces"
                class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                Annuler
              </router-link>
              <button type="submit" :disabled="loading"
                class="px-6 py-3 bg-brand-600 text-white rounded-lg hover:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2">
                <svg v-if="loading" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                  </path>
                </svg>
                <span v-if="loading">Création en cours...</span>
                <span v-else>Créer le workspace</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useWorkspace } from '@/composables/useWorkspace';
import axios from 'axios';
import AdminLayout from '@/components/layout/AdminLayout.vue'; // ← Importez le layout
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'; // ← Optionnel

const router = useRouter();
const { createWorkspace, fetchWorkspaces } = useWorkspace();

const form = ref({
  nom: '',
  description: '',
  logo: null as File | null,
  settings: {
    default_project_visibility: 'team',
    members_can_create_projects: true,
    members_can_invite: false,
    require_task_validation: true,
  },
});

const errors = ref<Record<string, string>>({});
const loading = ref(false);
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

const handleLogoChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];

  if (file) {
    // Validate file size (2MB)
    if (file.size > 2 * 1024 * 1024) {
      errors.value.logo = 'Le fichier est trop volumineux (max 2 MB)';
      return;
    }

    // Validate file type
    if (!file.type.startsWith('image/')) {
      errors.value.logo = 'Le fichier doit être une image';
      return;
    }

    form.value.logo = file;
    errors.value.logo = '';

    // Create preview
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
    if (form.value.description) {
      formData.append('description', form.value.description);
    }

    //Envoyer les settings comme JSON string
    formData.append('settings', JSON.stringify(form.value.settings));

        // Vérifier que c'est bien un fichier avant d'ajouter
    if (form.value.logo && form.value.logo instanceof File) {
      formData.append('logo', form.value.logo);
    }
    const response = await createWorkspace(formData);

    // Success notification
    // TODO: Add toast notification
    console.log('Workspace créé avec succès', response);

    // Redirect to workspace details
    router.push({ name: 'workspaces.show', params: { id: response.id } });
  } catch (error: any) {
    console.error('Erreur lors de la création du workspace:', error);

    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    } else {
      errors.value.general = error.response?.data?.message || 'Une erreur est survenue';
    }
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
/* Additional custom styles if needed */
</style>