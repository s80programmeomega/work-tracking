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
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5zM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5zM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5z"/></svg>
            </button>
            <div class="dropdown-menu dropdown-menu-right">
              <button
                v-if="canEdit"
                class="dropdown-item"
                @click="startEdit"
              >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2 inline-block"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>Modifier
              </button>
              <button
                v-if="canDelete"
                class="dropdown-item text-danger"
                @click="handleDelete"
              >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2 inline-block"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>Supprimer
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
                <span class="mr-1" v-html="getAttachmentIcon(attachment)"></span>
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
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z"/></svg>
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
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1 inline-block"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/></svg>Répondre
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
      if (attachment.is_image) return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 inline-block"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0z"/></svg>';
      if (attachment.is_document) return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 inline-block"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9z"/></svg>';
      return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 inline-block"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9z"/></svg>';
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
