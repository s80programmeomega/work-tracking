<template>
  <div class="comment-form">
    <div class="card">
      <div class="card-body">
        <form @submit.prevent="handleSubmit">
          <!-- Textarea for comment content -->
          <div class="form-group">
            <label v-if="!isReply" class="font-weight-bold">
              {{ isEditing ? 'Modifier le commentaire' : 'Ajouter un commentaire' }}
            </label>
            <textarea
              v-model="content"
              class="form-control"
              :rows="isReply ? 3 : 5"
              :placeholder="placeholder"
              @input="handleInput"
            ></textarea>
            <small v-if="showMarkdownHint" class="form-text text-muted">
              Supporte le markdown: **gras**, *italique*, `code`, @mention
            </small>
          </div>

          <!-- Attachments preview -->
          <div v-if="attachments.length > 0" class="mb-3">
            <div class="d-flex flex-wrap gap-2">
              <div
                v-for="(file, index) in attachments"
                :key="index"
                class="attachment-preview badge badge-secondary p-2"
              >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
                {{ file.name }}
                <button
                  type="button"
                  class="btn btn-sm btn-link text-white p-0 ml-2"
                  @click="removeAttachment(index)"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <!-- File upload -->
              <label class="btn btn-sm btn-outline-secondary mb-0 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
                Joindre un fichier
                <input
                  ref="fileInput"
                  type="file"
                  class="d-none"
                  multiple
                  @change="handleFileSelect"
                />
              </label>

              <!-- Mention button (optional) -->
              <button
                v-if="!isReply"
                type="button"
                class="btn btn-sm btn-outline-secondary ml-2"
                @click="insertMention"
              >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0zm0 0c0 1.657 1.007 3 2.25 3S21 13.657 21 12a9 9 0 1 0-2.636 6.364M16.5 12V8.25"/></svg>
                Mentionner
              </button>
            </div>

            <div>
              <button
                v-if="isEditing || isReply"
                type="button"
                class="btn btn-sm btn-secondary mr-2"
                @click="handleCancel"
              >
                Annuler
              </button>
              <button
                type="submit"
                class="btn btn-sm btn-primary"
                :disabled="!canSubmit || loading"
              >
                <svg v-if="loading" class="animate-spin h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                {{ submitLabel }}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue';

export default {
  name: 'CommentForm',
  props: {
    commentableType: {
      type: String,
      required: true,
    },
    commentableId: {
      type: [String, Number],
      required: true,
    },
    parentId: {
      type: [String, Number],
      default: null,
    },
    initialContent: {
      type: String,
      default: '',
    },
    isEditing: {
      type: Boolean,
      default: false,
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
  emits: ['submit', 'cancel'],
  setup(props, { emit }) {
    const content = ref(props.initialContent);
    const attachments = ref([]);
    const fileInput = ref(null);

    // Watch for changes to initialContent (for editing)
    watch(() => props.initialContent, (newVal) => {
      content.value = newVal;
    });

    const placeholder = computed(() => {
      if (props.isReply) return 'Écrire une réponse...';
      if (props.isEditing) return 'Modifier votre commentaire...';
      return 'Écrire un commentaire... (supporte @mentions et markdown)';
    });

    const submitLabel = computed(() => {
      if (props.isEditing) return 'Modifier';
      if (props.isReply) return 'Répondre';
      return 'Commenter';
    });

    const canSubmit = computed(() => {
      return content.value.trim().length > 0;
    });

    const showMarkdownHint = computed(() => {
      return !props.isReply && !props.isEditing;
    });

    const handleInput = () => {
      // Could add auto-save draft or character count here
    };

    const handleSubmit = () => {
      if (!canSubmit.value) return;

      const data = {
        content: content.value,
        commentable_type: props.commentableType,
        commentable_id: props.commentableId,
        parent_id: props.parentId,
        attachments: attachments.value,
      };

      emit('submit', data);

      // Reset form only if not editing
      if (!props.isEditing) {
        content.value = '';
        attachments.value = [];
        if (fileInput.value) {
          fileInput.value.value = '';
        }
      }
    };

    const handleCancel = () => {
      content.value = props.initialContent;
      attachments.value = [];
      if (fileInput.value) {
        fileInput.value.value = '';
      }
      emit('cancel');
    };

    const handleFileSelect = (event) => {
      const files = Array.from(event.target.files);
      attachments.value.push(...files);
    };

    const removeAttachment = (index) => {
      attachments.value.splice(index, 1);
    };

    const insertMention = () => {
      const textarea = document.querySelector('.comment-form textarea');
      if (textarea) {
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = content.value;
        content.value = text.substring(0, start) + '@' + text.substring(end);
        // Focus and move cursor after @
        setTimeout(() => {
          textarea.focus();
          textarea.setSelectionRange(start + 1, start + 1);
        }, 0);
      }
    };

    return {
      content,
      attachments,
      fileInput,
      placeholder,
      submitLabel,
      canSubmit,
      showMarkdownHint,
      handleInput,
      handleSubmit,
      handleCancel,
      handleFileSelect,
      removeAttachment,
      insertMention,
    };
  },
};
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}

.attachment-preview {
  display: inline-flex;
  align-items: center;
}

.gap-2 {
  gap: 0.5rem;
}
</style>
