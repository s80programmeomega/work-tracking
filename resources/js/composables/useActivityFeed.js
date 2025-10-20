import { ref, computed } from 'vue';
import api from '@/api/axios';

export function useActivityFeed() {
    const activities = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: 0,
    });

    /**
     * Fetch activity feed for user's dashboard
     */
    const fetchFeed = async (filters = {}, page = 1, perPage = 20) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get('/activities/feed', {
                params: {
                    project_ids: filters.project_ids || [],
                    task_ids: filters.task_ids || [],
                    per_page: perPage,
                    page,
                },
            });

            activities.value = response.data.data;
            pagination.value = response.data.meta;

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement du fil d\'activité';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Fetch recent activities
     */
    const fetchRecent = async (page = 1, perPage = 20) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get('/activities/recent', {
                params: {
                    per_page: perPage,
                    page,
                },
            });

            activities.value = response.data.data;
            pagination.value = response.data.meta;

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement des activités récentes';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Fetch activities for a specific subject
     */
    const fetchForSubject = async (subjectType, subjectId, page = 1, perPage = 20) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get('/activities/subject', {
                params: {
                    subject_type: subjectType,
                    subject_id: subjectId,
                    per_page: perPage,
                    page,
                },
            });

            activities.value = response.data.data;
            pagination.value = response.data.meta;

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement des activités';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Fetch activities by a specific user
     */
    const fetchByUser = async (userId, page = 1, perPage = 20) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get('/activities/user', {
                params: {
                    user_id: userId,
                    per_page: perPage,
                    page,
                },
            });

            activities.value = response.data.data;
            pagination.value = response.data.meta;

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement des activités de l\'utilisateur';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Fetch activities by log name
     */
    const fetchByLogName = async (logName, page = 1, perPage = 20) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get('/activities/log-name', {
                params: {
                    log_name: logName,
                    per_page: perPage,
                    page,
                },
            });

            activities.value = response.data.data;
            pagination.value = response.data.meta;

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement des activités';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Fetch activities by date range
     */
    const fetchByDateRange = async (startDate, endDate, page = 1, perPage = 20) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get('/activities/date-range', {
                params: {
                    start_date: startDate,
                    end_date: endDate,
                    per_page: perPage,
                    page,
                },
            });

            activities.value = response.data.data;
            pagination.value = response.data.meta;

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement des activités';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Fetch activity statistics
     */
    const fetchStats = async (startDate, endDate) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get('/activities/stats', {
                params: {
                    start_date: startDate,
                    end_date: endDate,
                },
            });

            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement des statistiques';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Load more activities (append to existing list)
     */
    const loadMore = async () => {
        if (!hasMorePages.value) return;

        const nextPage = pagination.value.current_page + 1;
        loading.value = true;

        try {
            const response = await api.get('/activities/feed', {
                params: {
                    per_page: pagination.value.per_page,
                    page: nextPage,
                },
            });

            activities.value.push(...response.data.data);
            pagination.value = response.data.meta;

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement des activités';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Group activities by date
     */
    const groupByDate = () => {
        const grouped = {};

        activities.value.forEach((activity) => {
            const date = new Date(activity.created_at).toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            });

            if (!grouped[date]) {
                grouped[date] = [];
            }

            grouped[date].push(activity);
        });

        return grouped;
    };

    /**
     * Get icon class for activity
     */
    const getIconClass = (activity) => {
        const iconMap = {
            created: 'fa-plus-circle text-success',
            updated: 'fa-edit text-info',
            deleted: 'fa-trash text-danger',
            assigned: 'fa-user-plus text-primary',
            completed: 'fa-check-circle text-success',
            status_changed: 'fa-sync-alt text-warning',
            comment_added: 'fa-comment text-info',
        };

        return iconMap[activity.description] || 'fa-info-circle text-secondary';
    };

    /**
     * Format relative time
     */
    const formatRelativeTime = (dateString) => {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);

        if (seconds < 60) return 'à l\'instant';
        if (seconds < 3600) return `il y a ${Math.floor(seconds / 60)} min`;
        if (seconds < 86400) return `il y a ${Math.floor(seconds / 3600)} h`;
        if (seconds < 604800) return `il y a ${Math.floor(seconds / 86400)} j`;

        return date.toLocaleDateString('fr-FR');
    };

    /**
     * Computed: Total activities count
     */
    const totalActivities = computed(() => pagination.value.total);

    /**
     * Computed: Has more pages
     */
    const hasMorePages = computed(() => pagination.value.current_page < pagination.value.last_page);

    /**
     * Computed: Activities grouped by date
     */
    const activitiesByDate = computed(() => groupByDate());

    return {
        // State
        activities,
        loading,
        error,
        pagination,

        // Computed
        totalActivities,
        hasMorePages,
        activitiesByDate,

        // Methods
        fetchFeed,
        fetchRecent,
        fetchForSubject,
        fetchByUser,
        fetchByLogName,
        fetchByDateRange,
        fetchStats,
        loadMore,
        groupByDate,
        getIconClass,
        formatRelativeTime,
    };
}
