import { ref, computed } from 'vue';
import api from '../api/axios';

export function useNotifications() {
    const notifications = ref([]);
    const unreadCount = ref(0);
    const loading = ref(false);
    const error = ref(null);

    /**
     * Fetch unread notifications
     */
    const fetchUnread = async (limit = 50) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get('/notifications', {
                params: { limit },
            });

            notifications.value = response.data.data;
            unreadCount.value = response.data.count;

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Fetch all notifications with pagination
     */
    const fetchAll = async (page = 1, perPage = 20) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get('/notifications/all', {
                params: { page, per_page: perPage },
            });

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Mark notification as read
     */
    const markAsRead = async (notificationId) => {
        try {
            await api.post(`/notifications/${notificationId}/mark-read`);

            // Update local state
            const notification = notifications.value.find((n) => n.id === notificationId);
            if (notification) {
                notification.read_at = new Date().toISOString();
                unreadCount.value = Math.max(0, unreadCount.value - 1);
            }

            return true;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de la mise à jour';
            throw err;
        }
    };

    /**
     * Mark all notifications as read
     */
    const markAllAsRead = async () => {
        try {
            const response = await api.post('/notifications/mark-all-read');

            // Update local state
            notifications.value.forEach((n) => {
                n.read_at = new Date().toISOString();
            });
            unreadCount.value = 0;

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de la mise à jour';
            throw err;
        }
    };

    /**
     * Delete notification
     */
    const deleteNotification = async (notificationId) => {
        try {
            await api.delete(`/notifications/${notificationId}`);

            // Remove from local state
            const index = notifications.value.findIndex((n) => n.id === notificationId);
            if (index !== -1) {
                const wasUnread = !notifications.value[index].read_at;
                notifications.value.splice(index, 1);

                if (wasUnread) {
                    unreadCount.value = Math.max(0, unreadCount.value - 1);
                }
            }

            return true;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de la suppression';
            throw err;
        }
    };

    /**
     * Delete all read notifications
     */
    const deleteAllRead = async () => {
        try {
            const response = await api.delete('/notifications/delete-all-read');

            // Remove read notifications from local state
            notifications.value = notifications.value.filter((n) => !n.read_at);

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de la suppression';
            throw err;
        }
    };

    /**
     * Get notification statistics
     */
    const fetchStatistics = async () => {
        try {
            const response = await api.get('/notifications/statistics');
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement des statistiques';
            throw err;
        }
    };

    /**
     * Get notification icon based on type
     */
    const getNotificationIcon = (type) => {
        const icons = {
            task_assigned: 'fa-user-plus',
            task_due_soon: 'fa-clock',
            task_completed: 'fa-check-circle',
            mentioned_in_comment: 'fa-at',
            comment_added: 'fa-comment',
            project_updated: 'fa-project-diagram',
            deadline_approaching: 'fa-exclamation-triangle',
            document_uploaded: 'fa-file-upload',
            workspace_invitation: 'fa-envelope',
        };

        return icons[type] || 'fa-bell';
    };

    /**
     * Get notification color based on type
     */
    const getNotificationColor = (type) => {
        const colors = {
            task_assigned: 'blue',
            task_due_soon: 'orange',
            task_completed: 'green',
            mentioned_in_comment: 'purple',
            comment_added: 'cyan',
            project_updated: 'indigo',
            deadline_approaching: 'red',
            document_uploaded: 'yellow',
            workspace_invitation: 'brand',
        };

        return colors[type] || 'gray';
    };

    /**
     * Computed: Unread notifications only
     */
    const unreadNotifications = computed(() => {
        return notifications.value.filter((n) => !n.read_at);
    });

    /**
     * Computed: Recent notifications (last 24h)
     */
    const recentNotifications = computed(() => {
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);

        return notifications.value.filter((n) => {
            return new Date(n.created_at) > yesterday;
        });
    });

    /**
     *  Vérifier si une notification est une invitation
     */
    const isInvitationNotification = (notification) => {
        return notification.type === 'workspace_invitation';
    };

    return {
        // State
        notifications,
        unreadCount,
        loading,
        error,

        // Computed
        unreadNotifications,
        recentNotifications,

        // Methods
        fetchUnread,
        fetchAll,
        markAsRead,
        markAllAsRead,
        deleteNotification,
        deleteAllRead,
        fetchStatistics,
        getNotificationIcon,
        getNotificationColor,
    };
}
