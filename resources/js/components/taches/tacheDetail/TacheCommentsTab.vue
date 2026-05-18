<template>
  <div class="space-y-6">
    <!-- Formulaire d'ajout de commentaire -->
    <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
      <form @submit.prevent="addComment">
        <div class="flex gap-3">
          <div class="flex-shrink-0">
            <div class="w-10 h-10 rounded-full bg-brand-600 flex items-center justify-center text-white font-semibold">
              {{ currentUserInitials }}
            </div>
          </div>
          <div class="flex-1">
            <textarea 
              v-model="newComment"
              placeholder="Ajouter un commentaire..."
              rows="3"
              class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent resize-none"
            ></textarea>
            <div class="flex items-center justify-between mt-2">
              <div class="flex gap-2">
                <button 
                  type="button"
                  class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors"
                  title="Ajouter une pièce jointe"
                >
                  <i class="fas fa-paperclip"></i>
                </button>
                <button 
                  type="button"
                  class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors"
                  title="Mentionner"
                >
                  <i class="fas fa-at"></i>
                </button>
              </div>
              <button 
                type="submit"
                :disabled="!newComment.trim() || submitting"
                class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                <i v-if="submitting" class="fas fa-spinner fa-spin mr-2"></i>
                <i v-else class="fas fa-paper-plane mr-2"></i>
                Envoyer
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- Liste des commentaires -->
    <div v-if="comments.length > 0" class="space-y-4">
      <div 
        v-for="comment in comments" 
        :key="comment.id"
        :id="`comment-${comment.id}`"
        class="bg-white dark:bg-gray-800 rounded-xl p-4 border-2 border-gray-200 dark:border-gray-700 transition-colors"
        :class="{ 'ring-2 ring-brand-500 border-brand-500': highlightedComment === comment.id }"
      >
        <div class="flex gap-3">
          <!-- Avatar -->
          <div class="flex-shrink-0">
            <img 
              v-if="comment.user?.avatar" 
              :src="comment.user.avatar" 
              :alt="comment.user.nom"
              class="w-10 h-10 rounded-full"
            />
            <div v-else class="w-10 h-10 rounded-full bg-brand-600 flex items-center justify-center text-white font-semibold">
              {{ getInitials(comment.user?.nom) }}
            </div>
          </div>

          <!-- Contenu -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1 flex-wrap">
              <span class="font-semibold text-gray-900 dark:text-white">{{ comment.user?.nom }}</span>
              <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(comment.created_at) }}</span>
              <span v-if="comment.edited" class="text-xs text-gray-400 dark:text-gray-500 italic">(modifié)</span>
            </div>

            <!-- Texte du commentaire -->
            <div v-if="editingComment !== comment.id" class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap break-words">
              {{ comment.content }}
            </div>

            <!-- Édition -->
            <div v-else>
              <textarea 
                v-model="editedContent"
                class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 resize-none"
                rows="3"
              ></textarea>
              <div class="flex gap-2 mt-2">
                <button 
                  @click="saveEdit(comment)"
                  :disabled="submitting"
                  class="px-3 py-1 bg-brand-600 text-white rounded text-sm hover:bg-brand-700 disabled:opacity-50 transition-colors"
                >
                  <i v-if="submitting" class="fas fa-spinner fa-spin mr-1"></i>
                  Enregistrer
                </button>
                <button 
                  @click="cancelEdit"
                  :disabled="submitting"
                  class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded text-sm hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                >
                  Annuler
                </button>
              </div>
            </div>

            <!-- Pièces jointes -->
            <div v-if="comment.attachments && comment.attachments.length > 0" class="mt-3 space-y-2">
              <div 
                v-for="attachment in comment.attachments" 
                :key="attachment.id"
                class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900 rounded p-2 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
              >
                <i class="fas fa-paperclip"></i>
                <a :href="attachment.url" target="_blank" class="hover:text-brand-600 dark:hover:text-brand-400 truncate">
                  {{ attachment.name }}
                </a>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 mt-3 text-sm">
              <button 
                @click="likeComment(comment)"
                :disabled="submitting"
                class="text-gray-600 dark:text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors disabled:opacity-50"
              >
                <i :class="[comment.liked_by_me ? 'fas' : 'far', 'fa-heart', comment.liked_by_me ? 'text-red-500' : '']"></i>
                <span v-if="comment.likes_count > 0" class="ml-1">{{ comment.likes_count }}</span>
              </button>
              <button 
                @click="replyTo(comment)"
                class="text-gray-600 dark:text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors"
              >
                <i class="fas fa-reply mr-1"></i>
                Répondre
              </button>
              <button 
                v-if="comment.can_edit"
                @click="startEdit(comment)"
                :disabled="submitting"
                class="text-gray-600 dark:text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors disabled:opacity-50"
              >
                <i class="fas fa-edit mr-1"></i>
                Modifier
              </button>
              <button 
                v-if="comment.can_delete"
                @click="deleteComment(comment)"
                :disabled="submitting"
                class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition-colors disabled:opacity-50"
              >
                <i class="fas fa-trash-alt mr-1"></i>
                Supprimer
              </button>
            </div>

            <!-- Réponses -->
            <div v-if="comment.replies && comment.replies.length > 0" class="mt-4 space-y-3 pl-4 border-l-2 border-gray-200 dark:border-gray-700">
              <div 
                v-for="reply in comment.replies" 
                :key="reply.id"
                class="flex gap-3"
              >
                <div class="flex-shrink-0">
                  <img 
                    v-if="reply.user?.avatar" 
                    :src="reply.user.avatar" 
                    :alt="reply.user.nom"
                    class="w-8 h-8 rounded-full"
                  />
                  <div v-else class="w-8 h-8 rounded-full bg-brand-600 flex items-center justify-center text-white text-xs font-semibold">
                    {{ getInitials(reply.user?.nom) }}
                  </div>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ reply.user?.nom }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(reply.created_at) }}</span>
                  </div>
                  <p class="text-sm text-gray-700 dark:text-gray-300 break-words">{{ reply.content }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- État vide -->
    <div v-else-if="!loading" class="text-center py-12">
      <i class="fas fa-comments text-6xl text-gray-300 dark:text-gray-700 mb-4"></i>
      <p class="text-gray-600 dark:text-gray-400">Aucun commentaire pour le moment</p>
      <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">Soyez le premier à commenter cette tâche</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <i class="fas fa-spinner fa-spin text-4xl text-brand-600"></i>
      <p class="text-gray-600 dark:text-gray-400 mt-4">Chargement des commentaires...</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '@/api/axios';
import { useNotifications } from '@/composables/useNotifications';
import { useAuthStore } from '@/stores/authStore';

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
const authStore = useAuthStore();
const { showSuccess, showError } = useNotifications();

const newComment = ref('');
const comments = ref([]);
const editingComment = ref(null);
const editedContent = ref('');
const highlightedComment = ref(null);
const loading = ref(false);
const submitting = ref(false);

const currentUserInitials = computed(() => {
  const user = authStore.user;
  if (!user?.nom) return '?';
  return user.nom
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .substring(0, 2);
});

const addComment = async () => {
  if (!newComment.value.trim() || submitting.value) return;

  submitting.value = true;

  try {
    await api.post(`/taches/${props.tache.id}/comments`, {
      content: newComment.value
    });
    
    showSuccess('Commentaire ajouté');
    newComment.value = '';
    await fetchComments();
  } catch (error) {
    showError(error.response?.data?.message || 'Erreur lors de l\'ajout du commentaire');
  } finally {
    submitting.value = false;
  }
};

const fetchComments = async () => {
  loading.value = true;
  try {
    const response = await api.get(`/taches/${props.tache.id}/comments`);
    comments.value = response.data?.data ?? [];
  } catch (error) {
    console.error('Erreur lors du chargement des commentaires:', error);
    showError('Erreur lors du chargement des commentaires');
  } finally {
    loading.value = false;
  }
};

const startEdit = (comment) => {
  editingComment.value = comment.id;
  editedContent.value = comment.content;
};

const cancelEdit = () => {
  editingComment.value = null;
  editedContent.value = '';
};

const saveEdit = async (comment) => {
  if (!editedContent.value.trim() || submitting.value) return;

  submitting.value = true;

  try {
    await api.put(`/taches/${props.tache.id}/comments/${comment.id}`, {
      content: editedContent.value
    });
    
    showSuccess('Commentaire modifié');
    editingComment.value = null;
    await fetchComments();
  } catch (error) {
    showError(error.response?.data?.message || 'Erreur lors de la modification');
  } finally {
    submitting.value = false;
  }
};

const deleteComment = async (comment) => {
  if (!confirm('Supprimer ce commentaire ?')) return;

  submitting.value = true;

  try {
    await api.delete(`/taches/${props.tache.id}/comments/${comment.id}`);
    showSuccess('Commentaire supprimé');
    await fetchComments();
  } catch (error) {
    showError(error.response?.data?.message || 'Erreur lors de la suppression');
  } finally {
    submitting.value = false;
  }
};

const likeComment = async (comment) => {
  if (submitting.value) return;

  submitting.value = true;

  try {
    await api.post(`/taches/${props.tache.id}/comments/${comment.id}/like`);
    await fetchComments();
  } catch (error) {
    showError('Erreur lors du like');
  } finally {
    submitting.value = false;
  }
};

const replyTo = (comment) => {
  // Focus sur le champ de commentaire et mentionner l'utilisateur
  newComment.value = `@${comment.user?.nom} `;
  // Scroll vers le haut
  window.scrollTo({ top: 0, behavior: 'smooth' });
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
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

onMounted(async () => {
  await fetchComments();
  
  // Highlight comment from URL
  if (route.query.highlight) {
    const match = route.query.highlight.match(/comment-(\d+)/);
    if (match) {
      highlightedComment.value = parseInt(match[1]);
      setTimeout(() => {
        const element = document.getElementById(route.query.highlight);
        element?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Retirer le highlight après 3 secondes
        setTimeout(() => {
          highlightedComment.value = null;
        }, 3000);
      }, 300);
    }
  }
});
</script>