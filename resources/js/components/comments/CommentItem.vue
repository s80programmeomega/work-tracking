<template>
  <div class="comment-item" :class="{ 'comment-reply': isReply }">
    <div class="card mb-3">
      <div class="card-body">
        <!-- Comment Header -->
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div class="d-flex align-items-center">
            <div class="avatar mr-2">
              <div class="avatar-circle">
                {{ comment.user.nom.charAt(0).toUpperCase() }}
              </div>
            </div>
            <div>
              <strong>{{ comment.user.nom }}</strong>
              <small class="text-muted ml-2">
                {{ formatDate(comment.created_at) }}
                <span v-if="comment.is_edited" class="badge badge-sm badge-secondary ml-1">
                  modifié
                </span>
              </small>
            </div>
          </div>

          <!-- Actions dropdown -->
          <div v-if="canEdit || canDelete" class="dropdown">
            <button
              class="btn btn-sm btn-link text-muted"
              type="button"
              data-toggle="dropdown"
            >
              <i class="fas fa-ellipsis-v"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-right">
              <button
                v-if="canEdit"
                class="dropdown-item"
                @click="startEdit"
              >
                <i class="fas fa-edit mr-2"></i>Modifier
              </button>
              <button
                v-if="canDelete"
                class="dropdown-item text-danger"
                @click="handleDelete"
              >
                <i class="fas fa-trash mr-2"></i>Supprimer
              </button>
            </div>
          </div>
        </div>

        <!-- Edit mode -->
        <div v-if="isEditing">
          <CommentForm
            :commentable-type="comment.commentable_type"
            :commentable-id="comment.commentable_id"
            :initial-content="comment.content"
            :is-editing="true"
            :loading="loading"
            @submit="handleUpdate"
            @cancel="cancelEdit"
          />
        </div>

        <!-- View mode -->
        <div v-else>
          <!-- Comment content -->
          <div class="comment-content mb-3" v-html="comment.content_html || comment.content"></div>

          <!-- Attachments -->
          <div v-if="comment.attachments && comment.attachments.length > 0" class="mb-3">
            <div class="attachments">
              <a
                v-for="attachment in comment.attachments"
                :key="attachment.id"
                :href="attachment.url"
                target="_blank"
                class="attachment-badge badge badge-light mr-2 mb-2"
              >
                <i :class="getAttachmentIcon(attachment)" class="mr-1"></i>
                {{ attachment.nom }}
                <small class="text-muted">({{ attachment.human_size }})</small>
              </a>
            </div>
          </div>

          <!-- Reactions -->
          <div class="d-flex align-items-center mb-2">
            <div class="reactions mr-3">
              <button
                v-for="(count, emoji) in comment.reactions_grouped"
                :key="emoji"
                class="btn btn-sm btn-outline-secondary mr-1"
                :class="{ 'active': hasUserReacted(emoji) }"
                @click="toggleReaction(emoji)"
              >
                {{ emoji }} {{ count }}
              </button>
              <button
                class="btn btn-sm btn-outline-secondary"
                @click="showReactionPicker = !showReactionPicker"
              >
                <i class="far fa-smile"></i>
              </button>
            </div>

            <!-- Reaction picker -->
            <div v-if="showReactionPicker" class="reaction-picker">
              <button
                v-for="emoji in availableEmojis"
                :key="emoji"
                class="btn btn-sm"
                @click="addReaction(emoji)"
              >
                {{ emoji }}
              </button>
            </div>
          </div>

          <!-- Action buttons -->
          <div class="comment-actions">
            <button
              class="btn btn-sm btn-link text-muted"
              @click="toggleReply"
            >
              <i class="fas fa-reply mr-1"></i>Répondre
            </button>
          </div>

          <!-- Reply form -->
          <div v-if="showReplyForm" class="mt-3">
            <CommentForm
              :commentable-type="comment.commentable_type"
              :commentable-id="comment.commentable_id"
              :parent-id="comment.id"
              :is-reply="true"
              :loading="loading"
              @submit="handleReply"
              @cancel="toggleReply"
            />
          </div>

          <!-- Nested replies -->
          <div v-if="comment.replies && comment.replies.length > 0" class="replies mt-3">
            <CommentItem
              v-for="reply in comment.replies"
              :key="reply.id"
              :comment="reply"
              :current-user-id="currentUserId"
              :is-reply="true"
              @update="$emit('update', $event)"
              @delete="$emit('delete', $event)"
              @reply="$emit('reply', $event)"
              @reaction="$emit('reaction', $event)"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue';
import CommentForm from './CommentForm.vue';

export default {
  name: 'CommentItem',
  components: {
    CommentForm,
  },
  props: {
    comment: {
      type: Object,
      required: true,
    },
    currentUserId: {
      type: [String, Number],
      required: true,
    },
    isReply: {
      type: Boolean,
      default: false,
    },
    loading: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['update', 'delete', 'reply', 'reaction'],
  setup(props, { emit }) {
    const isEditing = ref(false);
    const showReplyForm = ref(false);
    const showReactionPicker = ref(false);

    const availableEmojis = ['👍', '❤️', '😊', '🎉', '🚀', '👀'];

    const canEdit = computed(() => {
      return props.comment.user_id === props.currentUserId;
    });

    const canDelete = computed(() => {
      return props.comment.user_id === props.currentUserId;
    });

    const hasUserReacted = (emoji) => {
      if (!props.comment.reactions) return false;
      return props.comment.reactions.some(
        (r) => r.user.id === props.currentUserId && r.emoji === emoji
      );
    };

    const formatDate = (dateString) => {
      const date = new Date(dateString);
      const now = new Date();
      const diff = (now - date) / 1000; // seconds

      if (diff < 60) return 'à l\'instant';
      if (diff < 3600) return `il y a ${Math.floor(diff / 60)} min`;
      if (diff < 86400) return `il y a ${Math.floor(diff / 3600)} h`;
      if (diff < 604800) return `il y a ${Math.floor(diff / 86400)} j`;

      return date.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined,
      });
    };

    const getAttachmentIcon = (attachment) => {
      if (attachment.is_image) return 'fas fa-image';
      if (attachment.is_document) return 'fas fa-file-alt';
      return 'fas fa-file';
    };

    const startEdit = () => {
      isEditing.value = true;
    };

    const cancelEdit = () => {
      isEditing.value = false;
    };

    const handleUpdate = (data) => {
      emit('update', { commentId: props.comment.id, content: data.content });
      isEditing.value = false;
    };

    const handleDelete = () => {
      if (confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ?')) {
        emit('delete', props.comment.id);
      }
    };

    const toggleReply = () => {
      showReplyForm.value = !showReplyForm.value;
    };

    const handleReply = (data) => {
      emit('reply', data);
      showReplyForm.value = false;
    };

    const toggleReaction = (emoji) => {
      emit('reaction', { commentId: props.comment.id, emoji });
      showReactionPicker.value = false;
    };

    const addReaction = (emoji) => {
      emit('reaction', { commentId: props.comment.id, emoji });
      showReactionPicker.value = false;
    };

    return {
      isEditing,
      showReplyForm,
      showReactionPicker,
      availableEmojis,
      canEdit,
      canDelete,
      hasUserReacted,
      formatDate,
      getAttachmentIcon,
      startEdit,
      cancelEdit,
      handleUpdate,
      handleDelete,
      toggleReply,
      handleReply,
      toggleReaction,
      addReaction,
    };
  },
};
</script>

<style scoped>
.comment-item {
  position: relative;
}

.comment-reply {
  margin-left: 40px;
}

.avatar-circle {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 16px;
}

.comment-content {
  line-height: 1.6;
  white-space: pre-wrap;
  word-break: break-word;
}

.attachment-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1rem;
  text-decoration: none;
  border: 1px solid #dee2e6;
}

.attachment-badge:hover {
  background-color: #e9ecef;
  text-decoration: none;
}

.reactions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
}

.reactions .btn.active {
  background-color: #007bff;
  color: white;
}

.reaction-picker {
  display: inline-flex;
  gap: 0.25rem;
  padding: 0.5rem;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 0.25rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.comment-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.replies {
  border-left: 2px solid #e9ecef;
  padding-left: 1rem;
}
</style>
