<template>
  <div class="space-y-4">
    <!-- Filtres -->
    <div class="flex gap-2 flex-wrap">
      <button 
        @click="filterType = 'all'"
        class="px-3 py-1 rounded-3 text-sm transition-colors"
        :class="filterType === 'all' ? 'bg-brand-600 text-white' : 'bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
      >
        Tout
      </button>
      <button 
        @click="filterType = 'status'"
        class="px-3 py-1 rounded-3 text-sm transition-colors"
        :class="filterType === 'status' ? 'bg-brand-600 text-white' : 'bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
      >
        Statuts
      </button>
      <button 
        @click="filterType = 'assignment'"
        class="px-3 py-1 rounded-3 text-sm transition-colors"
        :class="filterType === 'assignment' ? 'bg-brand-600 text-white' : 'bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
      >
        Assignations
      </button>
      <button 
        @click="filterType = 'files'"
        class="px-3 py-1 rounded-3 text-sm transition-colors"
        :class="filterType === 'files' ? 'bg-brand-600 text-white' : 'bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
      >
        Fichiers
      </button>
      <button 
        @click="filterType = 'comments'"
        class="px-3 py-1 rounded-3 text-sm transition-colors"
        :class="filterType === 'comments' ? 'bg-brand-600 text-white' : 'bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
      >
        Commentaires
      </button>
    </div>

    <!-- Timeline -->
    <div v-if="filteredActivities.length > 0" ref="staggerRef" class="relative">
      <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

      <div
        v-for="activity in filteredActivities"
        :key="activity.id"
        class="stagger-item relative flex gap-4 pb-8"
      >
        <!-- Icon -->
        <div class="relative z-10 flex-shrink-0">
          <div 
            class="w-12 h-12 rounded-full flex items-center justify-center "
            :class="getActivityColor(activity.type)"
          >
            <i :class="['fas', getActivityIcon(activity.type), 'text-white']"></i>
          </div>
        </div>

        <!-- Content -->
        <div class="flex-1 bg-white dark:bg-gray-800 rounded-3 p-4 border-2 border-gray-200 dark:border-gray-700 hover:border-brand-500 transition-colors">
          <div class="flex items-start justify-between gap-4 mb-2">
            <div class="flex items-center gap-2">
              <img 
                v-if="activity.user?.avatar" 
                :src="activity.user.avatar" 
                :alt="activity.user.nom"
                class="w-8 h-8 rounded-full"
              />
              <div v-else class="w-8 h-8 rounded-full bg-brand-600 flex items-center justify-center text-white text-xs font-semibold">
                {{ getInitials(activity.user?.nom) }}
              </div>
              <span class="font-semibold text-gray-900 dark:text-white">{{ activity.user?.nom }}</span>
            </div>
            <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ formatDate(activity.created_at) }}</span>
          </div>

          <!-- Description -->
          <p class="text-gray-700 dark:text-gray-300 mb-2">
            {{ getActivityDescription(activity) }}
          </p>

          <!-- Détails supplémentaires -->
          <div v-if="activity.details" class="mt-3 p-3 bg-gray-50 dark:bg-gray-900 rounded-3 text-sm">
            <!-- Changement de statut -->
            <div v-if="activity.type === 'status_changed'" class="flex items-center gap-2">
              <span class="px-2 py-1 rounded text-xs font-semibold" :class="getStatutClass(activity.details.from)">
                {{ getStatutLabel(activity.details.from) }}
              </span>
              <i class="fas fa-arrow-right text-gray-400"></i>
              <span class="px-2 py-1 rounded text-xs font-semibold" :class="getStatutClass(activity.details.to)">
                {{ getStatutLabel(activity.details.to) }}
              </span>
            </div>

            <!-- Modification de champs -->
            <div v-if="activity.type === 'field_updated'" class="space-y-1">
              <div v-for="(change, field) in activity.details.changes" :key="field" class="flex items-center gap-2 text-xs">
                <span class="font-medium text-gray-600 dark:text-gray-400">{{ field }}:</span>
                <span class="text-gray-500 dark:text-gray-500 line-through">{{ change.old }}</span>
                <i class="fas fa-arrow-right text-gray-400 text-xs"></i>
                <span class="text-gray-700 dark:text-gray-300">{{ change.new }}</span>
              </div>
            </div>

            <!-- Assignation -->
            <div v-if="activity.type === 'user_assigned' || activity.type === 'user_unassigned'" class="flex items-center gap-2">
              <img 
                v-if="activity.details.assigned_user?.avatar" 
                :src="activity.details.assigned_user.avatar" 
                :alt="activity.details.assigned_user.nom"
                class="w-6 h-6 rounded-full"
              />
              <span class="text-gray-700 dark:text-gray-300">{{ activity.details.assigned_user?.nom }}</span>
            </div>

            <!-- Fichier -->
            <div v-if="activity.type === 'file_added'" class="flex items-center gap-2">
              <i class="fas fa-file text-gray-400"></i>
              <span class="text-gray-700 dark:text-gray-300">{{ activity.details.file_name }}</span>
              <span class="text-xs text-gray-500 dark:text-gray-500">({{ formatFileSize(activity.details.file_size) }})</span>
            </div>

            <!-- Commentaire -->
            <div v-if="activity.type === 'comment_added' && activity.details.comment" class="text-gray-700 dark:text-gray-300 italic">
              "{{ truncate(activity.details.comment, 100) }}"
            </div>

            <!-- Validation -->
            <div v-if="activity.type === 'validated_n1' || activity.type === 'validated_n2'" class="space-y-1">
              <div v-if="activity.details.commentaire" class="text-gray-700 dark:text-gray-300 italic">
                "{{ activity.details.commentaire }}"
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- État vide -->
    <div v-else class="text-center py-12">
      <i class="fas fa-history text-6xl text-gray-300 dark:text-gray-700 mb-4"></i>
      <p class="text-gray-600 dark:text-gray-400">Aucune activité à afficher</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { useStagger } from '@/composables/useAnimations';
import api from '@/api/axios';

const props = defineProps({
  tache: {
    type: Object,
    required: true
  }
});

const { staggerRef, applyStagger } = useStagger(40);

const activities = ref([]);
const filterType = ref('all');

const filteredActivities = computed(() => {
  if (filterType.value === 'all') return activities.value;
  
  const typeMap = {
    'status': ['status_changed', 'completed', 'started'],
    'assignment': ['user_assigned', 'user_unassigned'],
    'files': ['file_added', 'file_deleted', 'link_added'],
    'comments': ['comment_added', 'comment_updated', 'comment_deleted']
  };
  
  const types = typeMap[filterType.value] || [];
  return activities.value.filter(a => types.includes(a.type));
});

const fetchActivities = async () => {
  try {
    const response = await api.get(`/taches/${props.tache.id}/activities`);
    activities.value = response.data?.data ?? [];
    await nextTick();
    applyStagger();
  } catch (error) {
    console.error('Erreur lors du chargement des activités:', error);
  }
};

const getActivityIcon = (type) => {
  const icons = {
    'created': 'fa-plus-circle',
    'status_changed': 'fa-exchange-alt',
    'completed': 'fa-check-circle',
    'started': 'fa-play-circle',
    'field_updated': 'fa-edit',
    'user_assigned': 'fa-user-plus',
    'user_unassigned': 'fa-user-minus',
    'file_added': 'fa-paperclip',
    'file_deleted': 'fa-trash-alt',
    'link_added': 'fa-link',
    'comment_added': 'fa-comment',
    'comment_updated': 'fa-comment-dots',
    'comment_deleted': 'fa-comment-slash',
    'validated_n1': 'fa-check',
    'validated_n2': 'fa-check-double',
    'archived': 'fa-archive',
  };
  return icons[type] || 'fa-info-circle';
};

const getActivityColor = (type) => {
  const colors = {
    'created': 'bg-blue-500',
    'status_changed': 'bg-purple-500',
    'completed': 'bg-green-500',
    'started': 'bg-cyan-500',
    'field_updated': 'bg-orange-500',
    'user_assigned': 'bg-indigo-500',
    'user_unassigned': 'bg-gray-500',
    'file_added': 'bg-blue-600',
    'file_deleted': 'bg-red-500',
    'link_added': 'bg-teal-500',
    'comment_added': 'bg-pink-500',
    'comment_updated': 'bg-pink-600',
    'comment_deleted': 'bg-red-600',
    'validated_n1': 'bg-green-600',
    'validated_n2': 'bg-green-700',
    'archived': 'bg-gray-600',
  };
  return colors[type] || 'bg-gray-500';
};

const getActivityDescription = (activity) => {
  const descriptions = {
    'created': 'a créé cette tâche',
    'status_changed': 'a changé le statut',
    'completed': 'a marqué la tâche comme terminée',
    'started': 'a commencé à travailler sur la tâche',
    'field_updated': 'a modifié des informations',
    'user_assigned': `a assigné ${activity.details?.assigned_user?.nom}`,
    'user_unassigned': `a retiré ${activity.details?.assigned_user?.nom}`,
    'file_added': 'a ajouté un fichier',
    'file_deleted': 'a supprimé un fichier',
    'link_added': 'a ajouté un lien',
    'comment_added': 'a ajouté un commentaire',
    'comment_updated': 'a modifié un commentaire',
    'comment_deleted': 'a supprimé un commentaire',
    'validated_n1': 'a validé (N+1)',
    'validated_n2': 'a validé (N+2)',
    'archived': 'a archivé la tâche',
  };
  return descriptions[activity.type] || 'a effectué une action';
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
  const date = new Date(dateString);
  const now = new Date();
  const diff = now - date;
  const minutes = Math.floor(diff / 60000);
  const hours = Math.floor(diff / 3600000);
  const days = Math.floor(diff / 86400000);

  if (minutes < 1) return 'À l\'instant';
  if (minutes < 60) return `Il y a ${minutes} min`;
  if (hours < 24) return `Il y a ${hours}h`;
  if (days < 7) return `Il y a ${days}j`;
  
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const truncate = (text, length) => {
  if (!text) return '';
  return text.length > length ? text.substring(0, length) + '...' : text;
};

onMounted(() => {
  fetchActivities();
});
</script>