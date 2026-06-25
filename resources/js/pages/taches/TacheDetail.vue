<!-- resources\js\pages\taches\TacheDetail.vue -->
<template>
  <AdminLayout>
  <div class="bg-gray-50 dark:bg-gray-950">
    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center min-h-screen">
      <i class="fas fa-spinner fa-spin text-4xl text-brand-600"></i>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="flex items-center justify-center min-h-screen">
      <div class="text-center">
        <i class="fas fa-exclamation-triangle text-6xl text-red-500 mb-4"></i>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ $t('tache_detail.error_title') }}</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-4">{{ error }}</p>
        <button @click="$router.back()" class="px-6 py-2 bg-brand-600 text-white rounded-3 hover:bg-brand-700">
          Retour
        </button>
      </div>
    </div>

    <!-- Content -->
    <div v-else-if="tache" class="py-6">
      <!-- Breadcrumb -->
      <nav class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-6">
        <router-link to="/" class="hover:text-brand-600">
          <i class="fas fa-home"></i>
        </router-link>
        <i class="fas fa-chevron-right text-xs"></i>
        <router-link :to="{ name: 'workspaces.select' }" class="hover:text-brand-600">
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
      <div class="bg-white dark:bg-gray-900 rounded-3 p-6 mb-6">
        <div class="flex items-start justify-between gap-4 mb-4">
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-2 flex-wrap">
              <!-- Titre inline editable -->
              <input
                v-if="headerEditing.field === 'titre'"
                v-model="headerEditing.value"
                @blur="saveHeaderEdit"
                @keydown.enter="saveHeaderEdit"
                @keydown.escape="cancelHeaderEdit"
                autofocus
                class="text-3xl font-bold text-gray-900 dark:text-white bg-transparent border-b-2 border-brand-500 focus:outline-none flex-1 min-w-0"
              />
              <h1
                v-else
                @click="startHeaderEdit('titre', tache.titre)"
                class="text-3xl font-bold text-gray-900 dark:text-white"
                :class="permissions.can_inline_edit ? 'cursor-pointer hover:text-brand-600 dark:hover:text-brand-400' : ''"
                :title="permissions.can_inline_edit ? $t('tache_detail.click_to_edit') : ''"
              >{{ tache.titre }}</h1>

              <!-- Statut inline editable -->
              <select
                v-if="headerEditing.field === 'statut'"
                v-model="headerEditing.value"
                @change="saveHeaderEdit"
                @blur="cancelHeaderEdit"
                @keydown.escape="cancelHeaderEdit"
                autofocus
                class="px-3 py-1 text-xs font-bold rounded-full border border-brand-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500"
              >
                <option value="a_faire">{{ $t('statuts.a_faire') }}</option>
                <option value="en_cours">{{ $t('statuts.en_cours') }}</option>
                <option value="termine">{{ $t('statuts.termine') }}</option>
                <option value="en_retard">{{ $t('statuts.en_retard') }}</option>
                <option value="a_refaire">{{ $t('statuts.a_refaire') }}</option>
              </select>
              <span
                v-else
                @click="startHeaderEdit('statut', tache.statut)"
                class="px-3 py-1 rounded-full text-xs font-bold"
                :class="[getStatutClass(tache.statut), permissions.can_inline_edit ? 'cursor-pointer hover:opacity-80' : '']"
                :title="permissions.can_inline_edit ? $t('tache_detail.click_to_edit') : ''"
              >
                {{ getStatutLabel(tache.statut) }}
              </span>

              <!-- Priorité inline editable -->
              <select
                v-if="headerEditing.field === 'priorite'"
                v-model="headerEditing.value"
                @change="saveHeaderEdit"
                @blur="cancelHeaderEdit"
                @keydown.escape="cancelHeaderEdit"
                autofocus
                class="px-3 py-1 text-xs font-bold rounded-full border border-brand-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500"
              >
                <option value="faible">{{ $t('priorites.faible') }}</option>
                <option value="moyenne">{{ $t('priorites.moyenne') }}</option>
                <option value="elevee">{{ $t('priorites.elevee') }}</option>
                <option value="critique">{{ $t('priorites.critique') }}</option>
              </select>
              <span
                v-else-if="tache.priorite"
                @click="startHeaderEdit('priorite', tache.priorite)"
                class="px-3 py-1 rounded-full text-xs font-bold"
                :class="[getPrioriteClass(tache.priorite), permissions.can_inline_edit ? 'cursor-pointer hover:opacity-80' : '']"
                :title="permissions.can_inline_edit ? $t('tache_detail.click_to_edit') : ''"
              >
                {{ tache.priorite_label || tache.priorite }}
              </span>
            </div>

            <!-- Quick Stats -->
            <div class="flex items-center gap-6 text-sm flex-wrap">
              <div class="flex items-center gap-2">
                <i class="fas fa-users text-gray-400"></i>
                <span class="text-gray-600 dark:text-gray-400">{{ $t('tache_detail.assignees_count', { count: stats.assignees_count }) }}</span>
              </div>
              <div class="flex items-center gap-2">
                <i class="fas fa-paperclip text-gray-400"></i>
                <span class="text-gray-600 dark:text-gray-400">{{ $t('tache_detail.attachments_count', { count: stats.attachments_count }) }}</span>
              </div>
              <div class="flex items-center gap-2">
                <i class="fas fa-link text-gray-400"></i>
                <span class="text-gray-600 dark:text-gray-400">{{ $t('tache_detail.links_count', { count: stats.links_count }) }}</span>
              </div>
              <div class="flex items-center gap-2">
                <i class="fas fa-comment text-gray-400"></i>
                <span class="text-gray-600 dark:text-gray-400">{{ $t('tache_detail.comments_count', { count: stats.comments_count }) }}</span>
              </div>
              <!-- Écheance inline editable -->
              <div class="flex items-center gap-2" :class="{ 'text-red-600': stats.is_overdue }">
                <i class="fas fa-calendar-alt"></i>
                <DatePicker
                  v-if="headerEditing.field === 'echeance'"
                  v-model="headerEditing.value"
                  :enable-time-picker="false"
                  auto-apply
                  :format="'dd-MM-yyyy'"
                  :locale="'fr'"
                  :dark="isDark"
                  :placeholder="$t('tache_detail.select_date')"
                  @update:model-value="saveHeaderEdit"
                  @keydown.escape="cancelHeaderEdit"
                  inline
                />
                <span
                  v-else-if="tache.echeance"
                  @click="startHeaderEdit('echeance', tache.echeance)"
                  :class="permissions.can_inline_edit ? 'cursor-pointer hover:opacity-80' : ''"
                  :title="permissions.can_inline_edit ? $t('tache_detail.click_to_edit') : ''"
                >{{ formatDate(tache.echeance) }}</span>
                <span
                  v-else-if="permissions.can_inline_edit"
                  @click="startHeaderEdit('echeance', null)"
                  class="cursor-pointer text-gray-400 hover:text-brand-600 dark:hover:text-brand-400"
                >{{ $t('tache_detail.add_deadline') }}</span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-2">
            <button @click="$router.back()"
              class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-3 hover:bg-gray-300 dark:hover:bg-gray-700 transition-colors">
              <i class="fas fa-arrow-left mr-2"></i>{{ $t('tache_detail.back') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="bg-white dark:bg-gray-900 rounded-3 mb-6">
        <div class="border-b border-gray-200 dark:border-gray-800 overflow-x-auto">
          <nav class="flex gap-1 px-6 min-w-max">
            <button v-for="tab in tabs" :key="tab.id"
              :dusk="`tab-${tab.id}`"
              @click="activeTab = tab.id"
              class="px-6 py-4 font-semibold text-sm border-b-2 transition-colors"
              :class="activeTab === tab.id
                ? 'border-brand-600 text-brand-600'
                : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'">
              <i :class="['fas', tab.icon, 'mr-2']"></i>
              {{ tab.label }}
              <span v-if="tab.count !== undefined" :dusk="`tab-${tab.id}-count`" class="ml-2 px-2 py-0.5 rounded-full text-xs bg-gray-200 dark:bg-gray-800">
                {{ tab.count }}
              </span>
            </button>
          </nav>
        </div>

        <div class="p-6">
          <!-- Tab: Détails -->
          <div v-show="activeTab === 'details'">
            <TacheDetailsTab :tache="tache" :permissions="permissions" @refresh="fetchTache" />
          </div>

          <!-- Tab: Sous-tâches -->
          <div v-show="activeTab === 'sous-taches'">
            <SousTacheList
              :tache-id="tache.id"
              :parent-echeance="tache.echeance"
              :can-create="permissions.can_create_subtask ?? false"
              :can-edit="permissions.can_inline_edit ?? false"
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

          <!-- Tab: Documents -->
          <div v-show="activeTab === 'documents'">
            <DocumentManager
              :documentable-type="'App\\Models\\Tache'"
              :documentable-id="tache.id"
              :entity-label="tache.titre"
              :can-upload="permissions.can_edit ?? false"
            />
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
import { ref, reactive, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import api from '@/api/axios';
import DatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
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
import DocumentManager from '@/components/documents/DocumentManager.vue';

const { t } = useI18n();
const route = useRoute();
const tache = ref(null);
const loading = ref(true);
const error = ref(null);
const activeTab = ref('details');
const stats = ref({});
const permissions = ref({});
const breadcrumb = ref({});

const headerEditing = reactive({ field: null, value: null })
const isDark = computed(() => document.documentElement.classList.contains('dark'))

const formatDateForApi = (date) => {
  if (!date) return null
  const d = date instanceof Date ? date : new Date(date)
  if (isNaN(d.getTime())) return null
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const startHeaderEdit = (field, value) => {
  if (!permissions.value.can_inline_edit) return
  headerEditing.field = field
  headerEditing.value = field === 'echeance' ? (value ? new Date(value) : null) : value
}

const cancelHeaderEdit = () => {
  headerEditing.field = null
  headerEditing.value = null
}

const saveHeaderEdit = async () => {
  const { field, value } = headerEditing
  if (!field) return
  const apiValue = field === 'echeance' ? formatDateForApi(value) : value
  const oldValue = tache.value[field]
  if ((apiValue ?? '') === (oldValue ?? '')) { cancelHeaderEdit(); return }
  cancelHeaderEdit()
  try {
    await api.patch(`/taches/${route.params.id}`, { [field]: apiValue })
    await fetchTache()
  } catch (err) {
    console.error('Erreur mise à jour:', err)
  }
}

const tabs = computed(() => [
  { id: 'details', label: t('tache_detail.tabs.details'), icon: 'fa-info-circle' },
  { id: 'sous-taches', label: t('tache_detail.tabs.subtasks'), icon: 'fa-list-check', count: stats.value.sous_taches_count },
  { id: 'assignees', label: t('tache_detail.tabs.assignees'), icon: 'fa-users', count: stats.value.assignees_count },
  { id: 'attachments', label: t('tache_detail.tabs.attachments'), icon: 'fa-paperclip', count: stats.value.attachments_count },
  { id: 'documents', label: t('tache_detail.tabs.documents'), icon: 'fa-folder-open' },
  { id: 'links', label: t('tache_detail.tabs.links'), icon: 'fa-link', count: stats.value.links_count },
  { id: 'results', label: t('tache_detail.tabs.results'), icon: 'fa-check-circle', count: stats.value.resultats_count },
  { id: 'comments', label: t('tache_detail.tabs.comments'), icon: 'fa-comment', count: stats.value.comments_count },
  { id: 'activity', label: t('tache_detail.tabs.activity'), icon: 'fa-history' },
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
  const key = `statuts.${statut}`
  const label = t(key)
  return label !== key ? label : statut
};

const getPrioriteClass = (priorite) => {
  const classes = {
    'faible': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    'moyenne': 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    'elevee': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    'critique': 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
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

// Gestion de l'onglet depuis l'URL
watch(() => route.query.tab, (newTab) => {
  if (newTab && tabs.value.some(t => t.id === newTab)) {
    activeTab.value = newTab;
  }
}, { immediate: true });

const handleKeydown = (e) => {
  if (e.key === 'Escape' && headerEditing.field) cancelHeaderEdit()
}

onMounted(() => {
  fetchTache();
  window.addEventListener('keydown', handleKeydown)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKeydown)
})
</script>