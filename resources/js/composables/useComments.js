import { ref, computed } from 'vue';
import api from '@/api/axios';

export function useComments() {
    const comments = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
    });

    /**
     * Fetch comments for a commentable entity
     */
    const fetchComments = async (commentableType, commentableId, page = 1, perPage = 15) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get('/comments', {
                params: {
                    commentable_type: commentableType,
                    commentable_id: commentableId,
                    per_page: perPage,
                    page,
                },
            });

            comments.value = response.data.data;
            pagination.value = response.data.meta;

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement des commentaires';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Create a new comment
     */
    const createComment = async (data) => {
        loading.value = true;
        error.value = null;

        try {
            const formData = new FormData();
            formData.append('commentable_type', data.commentable_type);
            formData.append('commentable_id', data.commentable_id);
            formData.append('content', data.content);

            if (data.parent_id) {
                formData.append('parent_id', data.parent_id);
            }

            if (data.attachments && data.attachments.length > 0) {
                data.attachments.forEach((file, index) => {
                    formData.append(`attachments[${index}]`, file);
                });
            }

            const response = await api.post('/comments', formData, {
                headers: {
                    
                },
            });

            // Add new comment to the list
            if (data.parent_id) {
                // It's a reply, find parent and add to its replies
                const parent = findCommentById(comments.value, data.parent_id);
                if (parent) {
                    if (!parent.replies) parent.replies = [];
                    parent.replies.push(response.data.data);
                }
            } else {
                // It's a root comment, add to the top
                comments.value.unshift(response.data.data);
                pagination.value.total += 1;
            }

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de la création du commentaire';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Update a comment
     */
    const updateComment = async (commentId, content) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.put(`/comments/${commentId}`, { content });

            // Update comment in the list
            const comment = findCommentById(comments.value, commentId);
            if (comment) {
                Object.assign(comment, response.data.data);
            }

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de la modification du commentaire';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Delete a comment
     */
    const deleteComment = async (commentId) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.delete(`/comments/${commentId}`);

            // Remove comment from the list
            removeCommentById(comments.value, commentId);
            pagination.value.total -= 1;

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de la suppression du commentaire';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Toggle reaction on a comment
     */
    const toggleReaction = async (commentId, emoji) => {
        try {
            const response = await api.post(`/comments/${commentId}/reactions`, { emoji });

            // Update reactions in the comment
            const comment = findCommentById(comments.value, commentId);
            if (comment) {
                comment.reactions_grouped = response.data.data.reactions;
            }

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de la réaction';
            throw err;
        }
    };

    /**
     * Add attachment to a comment
     */
    const addAttachment = async (commentId, file) => {
        loading.value = true;
        error.value = null;

        try {
            const formData = new FormData();
            formData.append('file', file);

            const response = await api.post(`/comments/${commentId}/attachments`, formData, {
                headers: {
                    
                },
            });

            // Add attachment to comment
            const comment = findCommentById(comments.value, commentId);
            if (comment) {
                if (!comment.attachments) comment.attachments = [];
                comment.attachments.push(response.data.data);
            }

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de l\'ajout de la pièce jointe';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Delete attachment from a comment
     */
    const deleteAttachment = async (commentId, attachmentId) => {
        try {
            const response = await api.delete(`/comments/${commentId}/attachments/${attachmentId}`);

            // Remove attachment from comment
            const comment = findCommentById(comments.value, commentId);
            if (comment && comment.attachments) {
                comment.attachments = comment.attachments.filter((a) => a.id !== attachmentId);
            }

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de la suppression de la pièce jointe';
            throw err;
        }
    };

    /**
     * Get unread mentions
     */
    const fetchUnreadMentions = async () => {
        try {
            const response = await api.get('/comments/mentions/unread');
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement des mentions';
            throw err;
        }
    };

    /**
     * Mark mentions as read
     */
    const markMentionsAsRead = async (commentId = null) => {
        try {
            const response = await api.post('/comments/mentions/mark-read', {
                comment_id: commentId,
            });
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de la mise à jour des mentions';
            throw err;
        }
    };

    /**
     * Helper: Find comment by ID recursively
     */
    const findCommentById = (commentsList, id) => {
        for (const comment of commentsList) {
            if (comment.id === id) return comment;
            if (comment.replies && comment.replies.length > 0) {
                const found = findCommentById(comment.replies, id);
                if (found) return found;
            }
        }
        return null;
    };

    /**
     * Helper: Remove comment by ID recursively
     */
    const removeCommentById = (commentsList, id) => {
        for (let i = 0; i < commentsList.length; i++) {
            if (commentsList[i].id === id) {
                commentsList.splice(i, 1);
                return true;
            }
            if (commentsList[i].replies && commentsList[i].replies.length > 0) {
                if (removeCommentById(commentsList[i].replies, id)) return true;
            }
        }
        return false;
    };

    /**
     * Computed: Total comments count
     */
    const totalComments = computed(() => pagination.value.total);

    /**
     * Computed: Has more pages
     */
    const hasMorePages = computed(() => pagination.value.current_page < pagination.value.last_page);

    return {
        // State
        comments,
        loading,
        error,
        pagination,

        // Computed
        totalComments,
        hasMorePages,

        // Methods
        fetchComments,
        createComment,
        updateComment,
        deleteComment,
        toggleReaction,
        addAttachment,
        deleteAttachment,
        fetchUnreadMentions,
        markMentionsAsRead,
    };
}
