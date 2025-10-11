<template>
  <div class="comment-section">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="fas fa-comments mr-2"></i>
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
          <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
          <p class="mt-3 text-muted">Chargement des commentaires...</p>
        </div>

        <!-- Error state -->
        <div v-else-if="error" class="alert alert-danger">
          <i class="fas fa-exclamation-triangle mr-2"></i>
          {{ error }}
        </div>

        <!-- Empty state -->
        <div v-else-if="comments.length === 0" class="text-center py-5">
          <i class="far fa-comments fa-3x text-muted mb-3"></i>
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
              <i v-if="loading" class="fas fa-spinner fa-spin mr-2"></i>
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
