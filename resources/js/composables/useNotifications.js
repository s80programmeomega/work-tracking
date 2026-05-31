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
            // Tâches
            tache_assignee: 'fa-user-plus',
            tache_resources: 'fa-paperclip',
            tache_statut_auto_changed: 'fa-sync-alt',
            tache_partie_terminee: 'fa-check-circle',
            task_assigned: 'fa-user-plus',
            task_unassigned: 'fa-user-times',
            task_updated: 'fa-edit',
            task_file_added: 'fa-file-upload',
            task_file_removed: 'fa-trash',
            task_link_added: 'fa-link',
            task_link_removed: 'fa-unlink',
            task_due_soon: 'fa-clock',
            task_completed: 'fa-check-circle',
            sous_tache_assigned: 'fa-user-plus',
            sous_tache_overdue: 'fa-clock',

            // Résultats - Soumission
            resultat_soumis: 'fa-file-upload',
            resultat_soumis_n0: 'fa-file-upload',
            resultat_approuve_n0: 'fa-check',
            resultat_renvoye_n0: 'fa-undo',
            resultat_transmis_auto: 'fa-exchange-alt',

            // Résultats - Validations
            resultat_valide_n1: 'fa-check',
            resultat_valide_n2: 'fa-trophy',
            resultat_attente_n2: 'fa-exclamation-circle',
            resultat_validation_complete: 'fa-certificate',
            validation_n1_confirmee: 'fa-check-circle',
            validation_n2_confirmee: 'fa-trophy',

            // Résultats - Rejets
            resultat_rejete: 'fa-times-circle',
            rejet_confirme: 'fa-clipboard-check',
            resultat_rejete_n2_info: 'fa-info-circle',

            // Circuit / Bypass
            bypass_activated: 'fa-shield-alt',
            escalades_abusives: 'fa-exclamation-triangle',
            abusive_escalation_alert: 'fa-exclamation-triangle',
            unjustified_return_alert: 'fa-exclamation-triangle',
            high_inaction_rate_alert: 'fa-exclamation-triangle',

            // Scores / Évaluation
            score_updated: 'fa-chart-bar',
            evaluation_sheet_ready: 'fa-clipboard-list',

            // Projets / Invitations
            projet_invitation: 'fa-envelope',
            workspace_invitation: 'fa-envelope',
            workspace_member_added: 'fa-user-plus',

            // Activités
            activite_member_added: 'fa-user-plus',
            activite_member_removed: 'fa-user-times',
            activite_member_permissions_updated: 'fa-user-cog',
            responsable_changed: 'fa-user-check',

            // Documents
            document_uploaded: 'fa-file-upload',
            document_deleted: 'fa-trash',
            document_shared: 'fa-share-alt',
            document_permission_granted: 'fa-unlock',

            // Teams
            team_announcement: 'fa-bullhorn',
            team_event: 'fa-calendar-alt',
            team_member_added: 'fa-user-plus',
            team_member_joined: 'fa-user-check',
            team_resource: 'fa-file-alt',

            // Abonnements / Workspace
            trial_expiring: 'fa-clock',
            trial_expired: 'fa-times-circle',
            trial_extended: 'fa-calendar-plus',
            subscription_limit_reached: 'fa-exclamation-triangle',
            workspace_suspended: 'fa-ban',

            // Divers
            deadline_approaching: 'fa-exclamation-triangle',
            mentioned_in_comment: 'fa-at',
            comment_added: 'fa-comment',
            project_updated: 'fa-project-diagram',
        };

        return icons[type] || 'fa-bell';
    };

    const getNotificationColor = (type) => {
        const colors = {
            // Tâches
            tache_assignee: 'blue',
            tache_resources: 'blue',
            tache_statut_auto_changed: 'indigo',
            tache_partie_terminee: 'green',
            task_assigned: 'blue',
            task_unassigned: 'red',
            task_updated: 'indigo',
            task_file_added: 'green',
            task_file_removed: 'orange',
            task_link_added: 'cyan',
            task_link_removed: 'yellow',
            task_due_soon: 'orange',
            task_completed: 'green',
            sous_tache_assigned: 'blue',
            sous_tache_overdue: 'orange',

            // Résultats
            resultat_soumis: 'blue',
            resultat_soumis_n0: 'blue',
            resultat_approuve_n0: 'green',
            resultat_renvoye_n0: 'orange',
            resultat_transmis_auto: 'indigo',
            resultat_valide_n1: 'green',
            resultat_valide_n2: 'emerald',
            resultat_attente_n2: 'orange',
            resultat_validation_complete: 'emerald',
            resultat_rejete: 'red',
            rejet_confirme: 'orange',
            resultat_rejete_n2_info: 'yellow',
            validation_n1_confirmee: 'green',
            validation_n2_confirmee: 'emerald',

            // Circuit / Bypass
            bypass_activated: 'indigo',
            escalades_abusives: 'red',
            abusive_escalation_alert: 'red',
            unjustified_return_alert: 'red',
            high_inaction_rate_alert: 'orange',

            // Scores
            score_updated: 'purple',
            evaluation_sheet_ready: 'indigo',

            // Invitations / Workspace
            projet_invitation: 'purple',
            workspace_invitation: 'brand',
            workspace_member_added: 'brand',
            workspace_suspended: 'red',
            trial_expiring: 'orange',
            trial_expired: 'red',
            trial_extended: 'green',
            subscription_limit_reached: 'orange',

            // Activités
            activite_member_added: 'blue',
            activite_member_removed: 'red',
            activite_member_permissions_updated: 'indigo',
            responsable_changed: 'purple',

            // Documents
            document_uploaded: 'yellow',
            document_deleted: 'red',
            document_shared: 'cyan',
            document_permission_granted: 'green',

            // Teams
            team_announcement: 'purple',
            team_event: 'indigo',
            team_member_added: 'blue',
            team_member_joined: 'green',
            team_resource: 'cyan',

            // Divers
            deadline_approaching: 'red',
            mentioned_in_comment: 'purple',
            comment_added: 'cyan',
            project_updated: 'indigo',
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