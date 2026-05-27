<!-- resources\js\components\taches\tacheDetail\TacheLinksTab.vue -->
<template>
  <div class="space-y-4">
    <!-- Add link form -->
    <div v-if="permissions.can_add_links" class="bg-gray-50 dark:bg-gray-800 rounded-3 p-4">
      <form @submit.prevent="addLink" class="flex gap-3">
        <input 
          v-model="newLink.url"
          type="url" 
          placeholder="URL du lien (https://...)"
          required
          class="flex-1 px-4 py-2 rounded-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent"
        />
        <input 
          v-model="newLink.title"
          type="text" 
          placeholder="Titre (optionnel)"
          class="flex-1 px-4 py-2 rounded-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent"
        />
        <button 
          type="submit"
          :disabled="!newLink.url"
          class="px-6 py-2 bg-brand-600 text-white rounded-3 hover:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <i class="fas fa-plus mr-2"></i>Ajouter
        </button>
      </form>
    </div>

    <!-- Links list -->
    <div v-if="tache.external_links && tache.external_links.length > 0" class="grid grid-cols-1 gap-4">
      <div 
        v-for="link in tache.external_links" 
        :key="link.id"
        :id="`link-${link.id}`"
        class="bg-white dark:bg-gray-800 rounded-3 p-4 border-2 border-gray-200 dark:border-gray-700 hover:border-brand-500 transition-colors"
        :class="{ 'ring-2 ring-brand-500': highlightedLink === link.id }"
      >
        <div class="flex items-start gap-4">
          <!-- Icon -->
          <div class="flex-shrink-0 w-12 h-12 rounded-3 flex items-center justify-center">
            <i :class="['fab', getLinkIcon(link.url), 'text-white text-xl']"></i>
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <h4 class="font-semibold text-gray-900 dark:text-white mb-1">
              {{ link.title }}
            </h4>
            <a 
              :href="link.url" 
              target="_blank"
              class="text-sm text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300 break-all"
            >
              {{ link.url }}
              <i class="fas fa-external-link-alt ml-1 text-xs"></i>
            </a>
            <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400 mt-2">
              <span>{{ formatDate(link.created_at) }}</span>
              <span v-if="link.created_by">
                • Par {{ link.created_by.nom }}
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex gap-2">
            <a 
              :href="link.url" 
              target="_blank"
              class="p-2 text-brand-600 dark:text-brand-400 hover:bg-brand-50 dark:hover:bg-brand-900/20 rounded-3 transition-colors"
              title="Ouvrir"
            >
              <i class="fas fa-external-link-alt"></i>
            </a>
            <button 
              v-if="permissions.can_update"
              @click="deleteLink(link)"
              class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-3 transition-colors"
              title="Supprimer"
            >
              <i class="fas fa-trash-alt"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="text-center py-12">
      <i class="fas fa-link text-6xl text-gray-300 dark:text-gray-700 mb-4"></i>
      <p class="text-gray-600 dark:text-gray-400">Aucun lien externe</p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRoute } from 'vue-router';
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

const route = useRoute();
const { showSuccess, showError } = useNotifications();
const highlightedLink = ref(null);

const newLink = reactive({
  url: '',
  title: ''
});

const addLink = async () => {
  try {
    await api.post(`/taches/${props.tache.id}/external-links`, {
      external_links: [{
        url: newLink.url,
        title: newLink.title || newLink.url
      }]
    });
    
    showSuccess('Lien ajouté avec succès');
    newLink.url = '';
    newLink.title = '';
    emit('refresh');
  } catch (error) {
    showError(error.response?.data?.message || 'Erreur lors de l\'ajout du lien');
  }
};

const deleteLink = async (link) => {
  if (!confirm(`Supprimer le lien "${link.title}" ?`)) return;

  try {
    await api.delete(`/taches/${props.tache.id}/external-links/${link.id}`);
    showSuccess('Lien supprimé');
    emit('refresh');
  } catch (error) {
    showError(error.response?.data?.message || 'Erreur lors de la suppression');
  }
};

const getLinkIcon = (url) => {
  const domain = new URL(url).hostname.toLowerCase();
  
  const icons = {
    'github.com': 'fa-github',
    'gitlab.com': 'fa-gitlab',
    'google.com': 'fa-google',
    'drive.google.com': 'fa-google-drive',
    'youtube.com': 'fa-youtube',
    'youtu.be': 'fa-youtube',
    'linkedin.com': 'fa-linkedin',
    'facebook.com': 'fa-facebook',
    'twitter.com': 'fa-twitter',
    'x.com': 'fa-x-twitter',
    'figma.com': 'fa-figma',
    'trello.com': 'fa-trello',
    'slack.com': 'fa-slack',
  };

  for (const [key, icon] of Object.entries(icons)) {
    if (domain.includes(key)) {
      return icon;
    }
  }

  return 'fa-link';
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

onMounted(() => {
  // Highlight link from URL
  if (route.query.highlight) {
    const match = route.query.highlight.match(/link-(\d+)/);
    if (match) {
      highlightedLink.value = parseInt(match[1]);
      setTimeout(() => {
        const element = document.getElementById(route.query.highlight);
        element?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }, 300);
    }
  }
});
</script>