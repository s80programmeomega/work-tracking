<template>
  <div class="comment-section">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2 inline-block"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg>
          Commentaires
          <span class="badge badge-primary ml-2">{{ totalComments }}</span>
        </h5>
      </div>

      <div class="card-body">
        <!-- New comment form -->
        <div class="mb-4">
          <CommentForm
            :commentable-type="commentableType"
            :commentable-id="commentableId"
            :loading="loading"
            @submit="handleCreateComment"
          />
        </div>

        <!-- Loading state -->
        <div v-if="loading && comments.length === 0" class="text-center py-5">
          <svg class="animate-spin h-8 w-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          <p class="mt-3 text-muted">Chargement des commentaires...</p>
        </div>

        <!-- Error state -->
        <div v-else-if="error" class="alert alert-danger">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2 inline-block"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
          {{ error }}
        </div>

        <!-- Empty state -->
        <div v-else-if="comments.length === 0" class="text-center py-5">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-muted mb-3"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg>
          <p class="text-muted">Aucun commentaire pour le moment.</p>
          <p class="text-muted">Soyez le premier à commenter!</p>
        </div>

        <!-- Comments list -->
        <div v-else class="comments-list">
          <CommentItem
            v-for="comment in comments"
            :key="comment.id"
            :comment="comment"
            :current-user-id="currentUserId"
            :loading="loading"
            @update="handleUpdateComment"
            @delete="handleDeleteComment"
            @reply="handleCreateComment"
            @reaction="handleToggleReaction"
          />

          <!-- Load more button -->
          <div v-if="hasMorePages" class="text-center mt-4">
            <button
              class="btn btn-outline-primary"
              :disabled="loading"
              @click="loadMore"
            >
              <svg v-if="loading" class="animate-spin h-4 w-4 mr-2 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
              Charger plus de commentaires
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import { useComments } from '../../composables/useComments';
import CommentForm from './CommentForm.vue';
import CommentItem from './CommentItem.vue';

export default {
  name: 'CommentSection',
  components: {
    CommentForm,
    CommentItem,
  },
  props: {
    commentableType: {
      type: String,
      required: true,
      validator: (value) => {
        return ['App\\Models\\Tache', 'App\\Models\\Projet', 'App\\Models\\Activite'].includes(value);
      },
    },
    commentableId: {
      type: [String, Number],
      required: true,
    },
    currentUserId: {
      type: [String, Number],
      required: true,
    },
    autoLoad: {
      type: Boolean,
      default: true,
    },
  },
  setup(props) {
    const {
      comments,
      loading,
      error,
      pagination,
      totalComments,
      hasMorePages,
      fetchComments,
      createComment,
      updateComment,
      deleteComment,
      toggleReaction,
    } = useComments();

    // Load comments on mount
    onMounted(() => {
      if (props.autoLoad) {
        loadComments();
      }
    });

    // Reload comments when commentableId changes
    watch(() => props.commentableId, () => {
      if (props.autoLoad) {
        loadComments();
      }
    });

    const loadComments = async () => {
      try {
        await fetchComments(props.commentableType, props.commentableId);
      } catch (err) {
        console.error('Error loading comments:', err);
      }
    };

    const loadMore = async () => {
      try {
        await fetchComments(
          props.commentableType,
          props.commentableId,
          pagination.value.current_page + 1,
          pagination.value.per_page
        );
      } catch (err) {
        console.error('Error loading more comments:', err);
      }
    };

    const handleCreateComment = async (data) => {
      try {
        await createComment(data);
        // Show success message (optional)
      } catch (err) {
        console.error('Error creating comment:', err);
        alert('Erreur lors de la création du commentaire');
      }
    };

    const handleUpdateComment = async ({ commentId, content }) => {
      try {
        await updateComment(commentId, content);
        // Show success message (optional)
      } catch (err) {
        console.error('Error updating comment:', err);
        alert('Erreur lors de la modification du commentaire');
      }
    };

    const handleDeleteComment = async (commentId) => {
      try {
        await deleteComment(commentId);
        // Show success message (optional)
      } catch (err) {
        console.error('Error deleting comment:', err);
        alert('Erreur lors de la suppression du commentaire');
      }
    };

    const handleToggleReaction = async ({ commentId, emoji }) => {
      try {
        await toggleReaction(commentId, emoji);
      } catch (err) {
        console.error('Error toggling reaction:', err);
        alert('Erreur lors de l\'ajout de la réaction');
      }
    };

    return {
      comments,
      loading,
      error,
      totalComments,
      hasMorePages,
      loadComments,
      loadMore,
      handleCreateComment,
      handleUpdateComment,
      handleDeleteComment,
      handleToggleReaction,
    };
  },
};
</script>

<style scoped>
.comment-section {
  margin-bottom: 2rem;
}

.comments-list {
  margin-top: 1rem;
}
</style>
