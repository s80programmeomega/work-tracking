import { ref, computed } from 'vue';
import api from '../api/axios';

export function useNotifications() {
    const notifications = ref([]);
    const unreadCount = ref(0);
    const loading = ref(false);
    const error = ref(null);

    const showNotification = (message, type = 'info', duration = 5000) => {
        const id = Date.now()
        const notification = {
            id,
            message,
            type,
            duration
        }

        notifications.value.push(notification)

        setTimeout(() => {
            removeNotification(id)
        }, duration)

        return id
    }

    const showSuccess = (message, duration = 5000) => {
        return showNotification(message, 'success', duration)
    }

    const showError = (message, duration = 5000) => {
        return showNotification(message, 'error', duration)
    }

    const showWarning = (message, duration = 5000) => {
        return showNotification(message, 'warning', duration)
    }

    const showInfo = (message, duration = 5000) => {
        return showNotification(message, 'info', duration)
    }

    const removeNotification = (id) => {
        const index = notifications.value.findIndex(n => n.id === id)
        if (index !== -1) {
            notifications.value.splice(index, 1)
        }
    }

    const clearAll = () => {
        notifications.value = []
    }

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

    const markAsRead = async (notificationId) => {
        try {
            await api.post(`/notifications/${notificationId}/mark-read`);

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

    const markAllAsRead = async () => {
        try {
            const response = await api.post('/notifications/mark-all-read');

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

    const deleteNotification = async (notificationId) => {
        try {
            await api.delete(`/notifications/${notificationId}`);

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

    const deleteAllRead = async () => {
        try {
            const response = await api.delete('/notifications/delete-all-read');

            notifications.value = notifications.value.filter((n) => !n.read_at);

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de la suppression';
            throw err;
        }
    };

    const fetchStatistics = async () => {
        try {
            const response = await api.get('/notifications/statistics');
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement des statistiques';
            throw err;
        }
    };

    const getNotificationIcon = (type) => {
        const icons = {
            // Tâches - Assignation
            task_assigned: 'fa-user-plus',
            task_unassigned: 'fa-user-times',
            task_updated: 'fa-edit',

            // Tâches - Fichiers
            task_file_added: 'fa-file-upload',
            task_file_removed: 'fa-file-circle-minus',

            // Tâches - Liens
            task_link_added: 'fa-link',
            task_link_removed: 'fa-unlink',

            // Tâches - États
            task_due_soon: 'fa-clock',
            task_completed: 'fa-check-circle',

            // Résultats - Soumission
            resultat_soumis: 'fa-file-upload',

            // Résultats - Validations
            resultat_valide_n1: 'fa-check',
            resultat_valide_n2: 'fa-trophy',
            resultat_attente_n2: 'fa-exclamation-circle',
            validation_n1_confirmee: 'fa-check-circle',
            validation_n2_confirmee: 'fa-trophy',
            resultat_validation_complete: 'fa-certificate',

            // Résultats - Rejets
            resultat_rejete: 'fa-times-circle',
            rejet_confirme: 'fa-clipboard-check',
            resultat_rejete_n2_info: 'fa-info-circle',

            // Commentaires
            mentioned_in_comment: 'fa-at',
            comment_added: 'fa-comment',

            // Projets
            project_updated: 'fa-project-diagram',
            projet_invitation: 'fa-envelope',

            // Workspace
            workspace_invitation: 'fa-envelope',

            // Responsable activité changé
            responsable_changed: 'fa-user-check',

            // Autres
            deadline_approaching: 'fa-exclamation-triangle',
            document_uploaded: 'fa-file-upload',
        };

        return icons[type] || 'fa-bell';
    };

    const getNotificationColor = (type) => {
        const colors = {
            // Tâches
            task_assigned: 'blue',
            task_unassigned: 'red',
            task_updated: 'indigo',
            task_file_added: 'green',
            task_file_removed: 'orange',
            task_link_added: 'cyan',
            task_link_removed: 'yellow',
            task_due_soon: 'orange',
            task_completed: 'green',

            // Résultats - Soumission
            resultat_soumis: 'blue',

            // Résultats - Validations
            resultat_valide_n1: 'green',
            resultat_valide_n2: 'emerald',
            resultat_attente_n2: 'orange',
            validation_n1_confirmee: 'green',
            validation_n2_confirmee: 'emerald',
            resultat_validation_complete: 'cyan',

            // Résultats - Rejets
            resultat_rejete: 'red',
            rejet_confirme: 'orange',
            resultat_rejete_n2_info: 'yellow',

            // Commentaires
            mentioned_in_comment: 'purple',
            comment_added: 'cyan',

            // Projets
            project_updated: 'indigo',
            projet_invitation: 'purple',

            // Workspace
            workspace_invitation: 'brand',

            // Responsable activité changé
            responsable_changed: 'purple',

            // Autres
            deadline_approaching: 'red',
            document_uploaded: 'yellow',
        };

        return colors[type] || 'gray';
    };

    const unreadNotifications = computed(() => {
        return notifications.value.filter((n) => !n.read_at);
    });

    const recentNotifications = computed(() => {
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);

        return notifications.value.filter((n) => {
            return new Date(n.created_at) > yesterday;
        });
    });

    const isInvitationNotification = (notification) => {
        return ['workspace_invitation', 'projet_invitation'].includes(notification.type);
    };

    const isResultatNotification = (notification) => {
        return [
            'resultat_soumis',
            'resultat_attente_n2',
            'resultat_valide_n1',
            'resultat_valide_n2',
            'resultat_rejete',
            'validation_n1_confirmee',
            'validation_n2_confirmee',
            'rejet_confirme',
            'resultat_validation_complete',
            'resultat_rejete_n2_info'
        ].includes(notification.type);
    };

    const isTaskNotification = (notification) => {
        return [
            'task_assigned',
            'task_unassigned',
            'task_updated',
            'task_file_added',
            'task_file_removed',
            'task_link_added',
            'task_link_removed',
            'task_due_soon',
            'task_completed'
        ].includes(notification.type);
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
        isInvitationNotification,
        isResultatNotification,
        isTaskNotification,
        
        showNotification,
        showSuccess,
        showError,
        showWarning,
        showInfo,
        removeNotification,
        clearAll
    };
}