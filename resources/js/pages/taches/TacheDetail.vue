<!-- resources\js\pages\taches\TacheDetail.vue -->
<template>
  <AdminLayout>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-950">
    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center min-h-screen">
      <i class="fas fa-spinner fa-spin text-4xl text-brand-600"></i>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="flex items-center justify-center min-h-screen">
      <div class="text-center">
        <i class="fas fa-exclamation-triangle text-6xl text-red-500 mb-4"></i>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Erreur</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-4">{{ error }}</p>
        <button @click="$router.back()" class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700">
          Retour
        </button>
      </div>
    </div>

    <!-- Content -->
    <div v-else-if="tache" class="container mx-auto px-4 py-6 max-w-7xl">
      <!-- Breadcrumb -->
      <nav class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-6">
        <router-link to="/" class="hover:text-brand-600">
          <i class="fas fa-home"></i>
        </router-link>
        <i class="fas fa-chevron-right text-xs"></i>
        <router-link :to="`/workspaces/${breadcrumb.workspace.id}`" class="hover:text-brand-600">
          {{ breadcrumb.workspace.nom }}
        </router-link>
        <i class="fas fa-chevron-right text-xs"></i>
        <router-link :to="`/projets/${breadcrumb.projet.id}`" class="hover:text-brand-600">
          {{ breadcrumb.projet.nom }}
        </router-link>
        <i class="fas fa-chevron-right text-xs"></i>
        <router-link :to="`/activites/${breadcrumb.activite.id}`" class="hover:text-brand-600">
          {{ breadcrumb.activite.nom }}
        </router-link>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-900 dark:text-white font-semibold">{{ tache.titre }}</span>
      </nav>

      <!-- Header -->
      <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-lg p-6 mb-6">
        <div class="flex items-start justify-between gap-4 mb-4">
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
              <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ tache.titre }}</h1>
              <span class="px-3 py-1 rounded-full text-xs font-bold" :class="getStatutClass(tache.statut)">
                {{ getStatutLabel(tache.statut) }}
              </span>
              <span v-if="tache.priorite" class="px-3 py-1 rounded-full text-xs font-bold" :class="getPrioriteClass(tache.priorite)">
                {{ tache.priorite }}
              </span>
            </div>
            
            <p v-if="tache.description" class="text-gray-600 dark:text-gray-400 mb-4">
              {{ tache.description }}
            </p>

            <!-- Quick Stats -->
            <div class="flex items-center gap-6 text-sm">
              <div class="flex items-center gap-2">
                <i class="fas fa-users text-gray-400"></i>
                <span class="text-gray-600 dark:text-gray-400">{{ stats.assignees_count }} assigné(s)</span>
              </div>
              <div class="flex items-center gap-2">
                <i class="fas fa-paperclip text-gray-400"></i>
                <span class="text-gray-600 dark:text-gray-400">{{ stats.attachments_count }} fichier(s)</span>
              </div>
              <div class="flex items-center gap-2">
                <i class="fas fa-link text-gray-400"></i>
                <span class="text-gray-600 dark:text-gray-400">{{ stats.links_count }} lien(s)</span>
              </div>
              <div class="flex items-center gap-2">
                <i class="fas fa-comment text-gray-400"></i>
                <span class="text-gray-600 dark:text-gray-400">{{ stats.comments_count }} commentaire(s)</span>
              </div>
              <div v-if="tache.echeance" class="flex items-center gap-2" :class="{ 'text-red-600': stats.is_overdue }">
                <i class="fas fa-calendar-alt"></i>
                <span>{{ formatDate(tache.echeance) }}</span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-2">
            <!-- <button v-if="permissions.can_update" @click="openEditModal" 
              class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors">
              <i class="fas fa-edit mr-2"></i>Modifier
            </button> -->
            <button @click="$router.back()" 
              class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-700 transition-colors">
              <i class="fas fa-arrow-left mr-2"></i>Retour
            </button>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-lg mb-6">
        <div class="border-b border-gray-200 dark:border-gray-800">
          <nav class="flex gap-1 px-6">
            <button v-for="tab in tabs" :key="tab.id"
              @click="activeTab = tab.id"
              class="px-6 py-4 font-semibold text-sm border-b-2 transition-colors"
              :class="activeTab === tab.id 
                ? 'border-brand-600 text-brand-600' 
                : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'">
              <i :class="['fas', tab.icon, 'mr-2']"></i>
              {{ tab.label }}
              <span v-if="tab.count !== undefined" class="ml-2 px-2 py-0.5 rounded-full text-xs bg-gray-200 dark:bg-gray-800">
                {{ tab.count }}
              </span>
            </button>
          </nav>
        </div>

        <div class="p-6">
          <!-- Tab: Détails -->
          <div v-show="activeTab === 'details'">
            <TacheDetailsTab :tache="tache" />
          </div>

          <!-- Tab: Sous-tâches -->
          <div v-show="activeTab === 'sous-taches'">
            <SousTacheList
              :tache-id="tache.id"
              :parent-echeance="tache.echeance"
              :can-create="permissions.can_create_subtask ?? false"
              :can-edit="permissions.can_update ?? false"
              :can-delete="permissions.can_delete ?? false"
              @updated="fetchTache"
            />
          </div>

          <!-- Tab: Assignés -->
          <div v-show="activeTab === 'assignees'">
            <TacheAssigneesTab :tache="tache" :permissions="permissions" @refresh="fetchTache" />
          </div>

          <!-- Tab: Fichiers -->
          <div v-show="activeTab === 'attachments'">
            <TacheAttachmentsTab :tache="tache" :permissions="permissions" @refresh="fetchTache" />
          </div>

          <!-- Tab: Liens -->
          <div v-show="activeTab === 'links'">
            <TacheLinksTab :tache="tache" :permissions="permissions" @refresh="fetchTache" />
          </div>

          <!-- Tab: Résultats -->
          <div v-show="activeTab === 'results'">
            <TacheResultsTab :tache="tache" :permissions="permissions" @refresh="fetchTache" />
          </div>

          <!-- Tab: Commentaires -->
          <div v-show="activeTab === 'comments'">
            <TacheCommentsTab :tache="tache" :permissions="permissions" @refresh="fetchTache" />
          </div>

          <!-- Tab: Activité -->
          <div v-show="activeTab === 'activity'">
            <TacheActivityTab :tache="tache" />
          </div>
        </div>
      </div>
    </div>
  </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import api from '@/api/axios';
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'

import TacheDetailsTab from '@/components/taches/tacheDetail/TacheDetailsTab.vue';
import TacheAssigneesTab from '@/components/taches/tacheDetail/TacheAssigneesTab.vue';
import TacheAttachmentsTab from '@/components/taches/tacheDetail/TacheAttachmentsTab.vue';
import TacheLinksTab from '@/components/taches/tacheDetail/TacheLinksTab.vue';
import TacheResultsTab from '@/components/taches/tacheDetail/TacheResultsTab.vue';
import TacheCommentsTab from '@/components/taches/tacheDetail/TacheCommentsTab.vue';
import TacheActivityTab from '@/components/taches/tacheDetail/TacheActivityTab.vue';
import SousTacheList from '@/components/taches/SousTacheList.vue';

const route = useRoute();
const tache = ref(null);
const loading = ref(true);
const error = ref(null);
const activeTab = ref('details');
const stats = ref({});
const permissions = ref({});
const breadcrumb = ref({});

const tabs = computed(() => [
  { id: 'details', label: 'Détails', icon: 'fa-info-circle' },
  { id: 'sous-taches', label: 'Sous-tâches', icon: 'fa-list-check' },
  { id: 'assignees', label: 'Assignés', icon: 'fa-users', count: stats.value.assignees_count },
  { id: 'attachments', label: 'Fichiers', icon: 'fa-paperclip', count: stats.value.attachments_count },
  { id: 'links', label: 'Liens', icon: 'fa-link', count: stats.value.links_count },
  { id: 'results', label: 'Résultats', icon: 'fa-check-circle', count: stats.value.resultats_count },
  { id: 'comments', label: 'Commentaires', icon: 'fa-comment', count: stats.value.comments_count },
  { id: 'activity', label: 'Activité', icon: 'fa-history' },
]);

const fetchTache = async () => {
  loading.value = true;
  error.value = null;

  try {
    const response = await api.get(`/taches/${route.params.id}`);
    tache.value = response.data.data;
    stats.value = response.data.additional_info.stats;
    permissions.value = response.data.additional_info.permissions;
    breadcrumb.value = response.data.additional_info.breadcrumb;

  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors du chargement de la tâche';
    console.error('Erreur:', err);
  } finally {
    loading.value = false;
  }
};

const getStatutClass = (statut) => {
  const classes = {
    'a_faire': 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    'en_cours': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    'termine': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  };
  return classes[statut] || classes['a_faire'];
};

const getStatutLabel = (statut) => {
  const labels = {
    'a_faire': 'À faire',
    'en_cours': 'En cours',
    'termine': 'Terminé',
  };
  return labels[statut] || statut;
};

const getPrioriteClass = (priorite) => {
  const classes = {
    'haute': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    'moyenne': 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    'basse': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  };
  return classes[priorite] || '';
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const openEditModal = () => {
  // À implémenter
  console.log('Open edit modal');
};

// Gestion de l'onglet depuis l'URL
watch(() => route.query.tab, (newTab) => {
  if (newTab && tabs.value.some(t => t.id === newTab)) {
    activeTab.value = newTab;
  }
}, { immediate: true });

onMounted(() => {
  fetchTache();
});
</script>