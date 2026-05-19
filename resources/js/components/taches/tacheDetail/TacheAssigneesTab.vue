<!-- resources/js/components/taches/tacheDetail/TacheAssigneesTab.vue -->
<template>
  <div class="space-y-6">
    <!-- Ajouter un assigné -->
    <div v-if="permissions.can_update" class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
      <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Ajouter un membre</h3>
      <form @submit.prevent="assignUser" class="flex gap-3">
        <select 
          v-model="selectedUserId"
          class="flex-1 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent"
        >
          <option value="">Sélectionner un utilisateur...</option>
          <option v-for="user in availableUsers" :key="user.id" :value="user.id">
            {{ user.nom }}
          </option>
        </select>
        <button 
          type="submit"
          :disabled="!selectedUserId || loading"
          class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <i v-if="loading" class="fas fa-spinner fa-spin mr-2"></i>
          <i v-else class="fas fa-plus mr-2"></i>
          Ajouter
        </button>
      </form>
    </div>

    <!-- Statistiques d'équipe -->
    <div v-if="tache.team_stats" class="bg-gradient-to-br from-brand-50 to-blue-50 dark:from-brand-900/20 dark:to-blue-900/20 rounded-xl p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <i class="fas fa-chart-pie text-brand-600"></i>
        Progression de l'équipe
      </h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
        <div class="text-center">
          <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ tache.team_stats.termine_count }}</div>
          <div class="text-sm text-gray-600 dark:text-gray-400">Terminé</div>
        </div>
        <div class="text-center">
          <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ tache.team_stats.en_cours_count }}</div>
          <div class="text-sm text-gray-600 dark:text-gray-400">En cours</div>
        </div>
        <div class="text-center">
          <div class="text-3xl font-bold text-gray-600 dark:text-gray-400">{{ tache.team_stats.a_faire_count }}</div>
          <div class="text-sm text-gray-600 dark:text-gray-400">À faire</div>
        </div>
        <div class="text-center">
          <div class="text-3xl font-bold text-brand-600">{{ tache.team_stats.completion_percentage }}%</div>
          <div class="text-sm text-gray-600 dark:text-gray-400">Complété</div>
        </div>
      </div>
      <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
        <div 
          class="bg-gradient-to-r from-brand-500 to-brand-600 h-3 rounded-full transition-all duration-300"
          :style="{ width: tache.team_stats.completion_percentage + '%' }"
        ></div>
      </div>
      <p v-if="tache.team_stats.tous_ont_termine" class="text-center text-green-600 dark:text-green-400 font-medium mt-4">
        <i class="fas fa-check-circle mr-2"></i>Tous les membres ont terminé !
      </p>
    </div>

    <!-- Liste des assignés -->
    <div v-if="tache.assignees && tache.assignees.length > 0" class="space-y-3">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
        Membres assignés ({{ tache.assignees.length }})
      </h3>
      
      <div 
        v-for="assignee in tache.assignees" 
        :key="assignee.id"
        class="bg-white dark:bg-gray-800 rounded-xl p-4 border-2 border-gray-200 dark:border-gray-700 hover:border-brand-500 transition-colors"
      >
        <div class="flex items-center gap-4">
          <!-- Avatar -->
          <div class="flex-shrink-0">
            <img 
              v-if="assignee.avatar" 
              :src="assignee.avatar" 
              :alt="assignee.nom"
              class="w-12 h-12 rounded-full object-cover"
            />
            <div v-else class="w-12 h-12 rounded-full bg-brand-600 flex items-center justify-center text-white font-semibold">
              {{ getInitials(assignee.nom) }}
            </div>
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1 flex-wrap">
              <h4 class="font-semibold text-gray-900 dark:text-white">{{ assignee.nom }}</h4>
              <span 
                v-if="assignee.pivot.role === 'lead'"
                class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 rounded text-xs font-medium"
              >
                <i class="fas fa-star mr-1"></i>Lead
              </span>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ assignee.email }}</p>
            
            <!-- Statut et progression individuelle -->
            <div class="mt-3 space-y-2">
              <div class="flex items-center gap-3 flex-wrap">
                <span class="px-2 py-1 rounded text-xs font-semibold" :class="getStatutClass(assignee.pivot.statut_individuel)">
                  {{ getStatutLabel(assignee.pivot.statut_individuel) }}
                </span>
                <span class="text-sm text-gray-600 dark:text-gray-400">
                  {{ assignee.pivot.progression_individuelle }}% complété
                </span>
              </div>
              
              <!-- Barre de progression -->
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div 
                  class="h-2 rounded-full transition-all duration-300"
                  :class="assignee.pivot.statut_individuel === 'termine' ? 'bg-green-500' : 'bg-blue-500'"
                  :style="{ width: assignee.pivot.progression_individuelle + '%' }"
                ></div>
              </div>

              <!-- Dates -->
              <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400 flex-wrap">
                <span v-if="assignee.pivot.started_at">
                  <i class="fas fa-play mr-1"></i>Commencé: {{ formatDate(assignee.pivot.started_at) }}
                </span>
                <span v-if="assignee.pivot.completed_at">
                  <i class="fas fa-check mr-1"></i>Terminé: {{ formatDate(assignee.pivot.completed_at) }}
                </span>
              </div>

              <!-- Permissions -->
              <div class="flex flex-wrap gap-2 mt-2">
                <span v-if="assignee.pivot.can_edit" class="px-2 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 rounded text-xs">
                  <i class="fas fa-edit mr-1"></i>Peut éditer
                </span>
                <span v-if="assignee.pivot.can_validate" class="px-2 py-1 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 rounded text-xs">
                  <i class="fas fa-check-circle mr-1"></i>Peut valider
                </span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div v-if="permissions.can_update" class="flex-shrink-0">
            <button 
              @click="unassignUser(assignee)"
              :disabled="loading"
              class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors disabled:opacity-50"
              title="Retirer"
            >
              <i class="fas fa-user-minus"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- État vide -->
    <div v-else class="text-center py-12">
      <i class="fas fa-users text-6xl text-gray-300 dark:text-gray-700 mb-4"></i>
      <p class="text-gray-600 dark:text-gray-400">Aucun membre assigné</p>
      <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">Ajoutez des membres pour commencer à collaborer</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/api/axios';
import { useNotifications } from '@/composables/useNotifications';

const props = defineProps({
  tache: {
    type: Object,
    required: true
  },
  permissions: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['refresh']);

const { showSuccess, showError } = useNotifications();
const selectedUserId = ref('');
const availableUsers = ref([]);
const loading = ref(false);

const assignUser = async () => {
  if (!selectedUserId.value || loading.value) return;

  loading.value = true;

  try {
    await api.post(`/taches/${props.tache.id}/assignees`, {
      user_id: selectedUserId.value
    });
    
    showSuccess('Membre assigné avec succès');
    selectedUserId.value = '';
    emit('refresh');
    await fetchAvailableUsers();
  } catch (error) {
    showError(error.response?.data?.message || 'Erreur lors de l\'assignation');
  } finally {
    loading.value = false;
  }
};

const unassignUser = async (user) => {
  if (!confirm(`Retirer ${user.nom} de cette tâche ?`)) return;

  loading.value = true;

  try {
    await api.delete(`/taches/${props.tache.id}/assignees/${user.id}`);
    showSuccess('Membre retiré');
    emit('refresh');
    await fetchAvailableUsers();
  } catch (error) {
    showError(error.response?.data?.message || 'Erreur lors du retrait');
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

const getInitials = (name) => {
  if (!name) return '?';
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .substring(0, 2);
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const fetchAvailableUsers = async () => {
  if (!props.permissions.can_update || !props.tache.activite_id) return;

  try {
    const response = await api.get(`/activites/${props.tache.activite_id}/members`);

    // Filtrer les utilisateurs déjà assignés
    const assignedIds = props.tache.assignees?.map(a => a.id) || [];
    const members = response.data?.data ?? [];
    availableUsers.value = members.filter(u => !assignedIds.includes(u.id));
  } catch (error) {
    console.error('Erreur lors du chargement des utilisateurs:', error);
  }
};

onMounted(() => {
  fetchAvailableUsers();
});
</script>