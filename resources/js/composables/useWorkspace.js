// resources/js/composables/useWorkspace.js
//
// ARCHITECTURE NOTE:
// Previously, `currentWorkspace` and `workspaces` were module-level refs (singletons).
// This caused stale data to leak between user sessions in the same browser tab,
// because module-level state persists across logins until a full page reload.
//
// Fix: workspace state now lives in the Pinia authStore. Since authStore.clearAuth()
// is called on every logout, workspace data is automatically wiped when a user logs out.
// The public API of this composable is unchanged — all 23 consuming files work as before.

import { computed, ref } from 'vue';
import api from '@/api/axios';
import { useAuthStore } from '@/stores/authStore';
import { useToast } from 'vue-toastification';

const toast = useToast();

// Loading and error are still local refs — they are UI state, not user session data,
// so they don't need to survive across components or be cleared on logout.
const loading = ref(false);
const error = ref(null);

// Event listeners for workspace change notifications (UI-only, not session data)
const workspaceChangeListeners = new Set();

export function useWorkspace() {
    const authStore = useAuthStore();

    // Read workspace state from Pinia — reactive and automatically cleared on logout
    const workspaces = computed({
        get: () => authStore.workspaces,
        set: (val) => { authStore.workspaces = val; },
    });

    const currentWorkspace = computed({
        get: () => authStore.currentWorkspace,
        set: (val) => { authStore.currentWorkspace = val; },
    });

    /**
     * Fetch all workspaces for the current user and store them in Pinia.
     */
    const fetchWorkspaces = async (params = {}) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get('/workspaces', { params });
            const data = response.data.data && Array.isArray(response.data.data)
                ? response.data.data
                : (response.data.data || response.data);

            // Store in Pinia — will be cleared automatically on logout
            authStore.workspaces = data;

            // Set first workspace as current if none is selected yet
            if (!authStore.currentWorkspace && authStore.workspaces.length > 0) {
                authStore.currentWorkspace = authStore.workspaces[0];
            }

            return authStore.workspaces;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement des workspaces';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Initialize the current workspace from multiple sources (priority order):
     * 1. authStore.currentWorkspaceId (from user object)
     * 2. localStorage fallback
     * 3. First available workspace
     */
    const initializeCurrentWorkspace = async () => {
        loading.value = true;
        try {
            if (authStore.workspaces.length === 0) {
                await fetchWorkspaces();
            }

            let workspaceId = authStore.currentWorkspaceId
                || parseInt(localStorage.getItem('current_workspace_id') || '0')
                || authStore.workspaces[0]?.id
                || null;

            if (workspaceId) {
                const workspace = authStore.workspaces.find(w => w.id === workspaceId);
                if (workspace) {
                    authStore.currentWorkspace = workspace;
                    authStore.setCurrentWorkspace(workspaceId);
                    localStorage.setItem('current_workspace_id', workspaceId);
                }
            }

            return authStore.currentWorkspace;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur d\'initialisation';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Fetch a single workspace by ID.
     */
    const fetchWorkspace = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get(`/workspaces/${id}`);
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement du workspace';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Switch the active workspace and notify all listeners.
     */
    const selectWorkspace = async (workspace) => {
        const oldWorkspaceId = authStore.currentWorkspace?.id;
        const newWorkspaceId = workspace.id;

        if (oldWorkspaceId === newWorkspaceId) return;

        authStore.currentWorkspace = workspace;
        authStore.setCurrentWorkspace(newWorkspaceId);
        localStorage.setItem('current_workspace_id', newWorkspaceId);

        // Notify backend of the switch
        try {
            await api.post(`/workspaces/switch/${newWorkspaceId}`);
        } catch (err) {
            toast.warning('Erreur lors du switch workspace côté serveur');
        }

        // Rafraîchir les données complètes (permissions + subscription_summary) depuis le serveur
        try {
            const { data } = await api.get(`/workspaces/${newWorkspaceId}`);
            authStore.currentWorkspace = data.data ?? data;
        } catch (err) {
            // On garde l'objet de la liste en cas d'erreur réseau
        }

        // Broadcast change to all listeners
        const event = new CustomEvent('workspace-changed', {
            detail: { workspace: authStore.currentWorkspace, oldWorkspaceId, newWorkspaceId },
        });
        window.dispatchEvent(event);

        const invalid = [];
        workspaceChangeListeners.forEach(listener => {
            if (typeof listener === 'function') {
                try { listener(event); } catch (e) { /* ignore */ }
            } else {
                invalid.push(listener);
            }
        });
        invalid.forEach(l => workspaceChangeListeners.delete(l));
    };

    /**
     * Subscribe to workspace change events.
     * Returns an unsubscribe function — call it in onUnmounted.
     */
    const onWorkspaceChanged = (callback) => {
        if (typeof callback !== 'function') return () => {};

        const handler = (event) => { try { callback(event); } catch (e) { /* ignore */ } };
        workspaceChangeListeners.add(callback);
        window.addEventListener('workspace-changed', handler);

        return () => {
            workspaceChangeListeners.delete(callback);
            window.removeEventListener('workspace-changed', handler);
        };
    };

    /**
     * Create a new workspace and make it the active one.
     */
    const createWorkspace = async (data) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.post('/workspaces', data);
            const newWorkspace = response.data.data;
            authStore.workspaces.push(newWorkspace);
            await selectWorkspace(newWorkspace);
            return newWorkspace;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de la création du workspace';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Update a workspace and refresh local state.
     */
    const updateWorkspace = async (id, data) => {
        loading.value = true;
        error.value = null;
        try {
            let response;
            if (data instanceof FormData) {
                data.append('_method', 'PUT');
                response = await api.post(`/workspaces/${id}`, data);
            } else {
                response = await api.put(`/workspaces/${id}`, data);
            }
            const updated = response.data.data;
            const idx = authStore.workspaces.findIndex(w => w.id === id);
            if (idx !== -1) authStore.workspaces[idx] = updated;
            if (authStore.currentWorkspace?.id === id) authStore.currentWorkspace = updated;
            return updated;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de la mise à jour du workspace';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Delete a workspace and switch to another if it was the active one.
     */
    const deleteWorkspace = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            await api.delete(`/workspaces/${id}`);
            authStore.workspaces = authStore.workspaces.filter(w => w.id !== id);
            if (authStore.currentWorkspace?.id === id) {
                const next = authStore.workspaces[0] || null;
                if (next) {
                    await selectWorkspace(next);
                } else {
                    authStore.currentWorkspace = null;
                    authStore.setCurrentWorkspace(null);
                    localStorage.removeItem('current_workspace_id');
                }
            }
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de la suppression du workspace';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    // ==================== MEMBER MANAGEMENT ====================

    const fetchMembers = async (workspaceId) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get(`/workspaces/${workspaceId}/members`);
            return response.data.data || [];
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement des membres';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const fetchInvitations = async (workspaceId) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get(`/workspaces/${workspaceId}/members/invitations`);
            return response.data.data || [];
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement des invitations';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const fetchAllInvitations = async (filters = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get('/workspace-invitations/all', { params: filters });
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement des invitations';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const getInvitationStatistics = async () => {
        try {
            const response = await api.get('/workspace-invitations/statistics');
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement des statistiques';
            throw err;
        }
    };

    const inviteMembers = async (workspaceId, inviteData) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.post(`/workspaces/${workspaceId}/members/invite`, inviteData);
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de l\'envoi des invitations';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const resendInvitation = async (workspaceId, invitationId) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.post(`/workspaces/${workspaceId}/members/invitations/${invitationId}/resend`);
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du renvoi de l\'invitation';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const cancelInvitation = async (workspaceId, invitationId) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.delete(`/workspaces/${workspaceId}/members/invitations/${invitationId}`);
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de l\'annulation de l\'invitation';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const addMember = async (workspaceId, userData) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.post(`/workspaces/${workspaceId}/members`, userData);
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de l\'ajout du membre';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const removeMember = async (workspaceId, userId) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.delete(`/workspaces/${workspaceId}/members/${userId}`);
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du retrait du membre';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const updateMember = async (workspaceId, userId, data) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.put(`/workspaces/${workspaceId}/members/${userId}`, data);
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de la mise à jour du membre';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const updateMemberRole = async (workspaceId, userId, memberData) => {
        return updateMember(workspaceId, userId, memberData);
    };

    // ==================== PROJECTS / STATS ====================

    const fetchProjects = async (workspaceId, params = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get(`/workspaces/${workspaceId}/projets`, { params });
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement des projets';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const fetchStatistics = async (workspaceId) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get(`/workspaces/${workspaceId}/statistics`);
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du chargement des statistiques';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const archiveWorkspace = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.post(`/workspaces/${id}/archive`);
            const idx = authStore.workspaces.findIndex(w => w.id === id);
            if (idx !== -1) authStore.workspaces[idx].is_active = false;
            if (authStore.currentWorkspace?.id === id) authStore.currentWorkspace.is_active = false;
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de l\'archivage du workspace';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const unarchiveWorkspace = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.post(`/workspaces/${id}/unarchive`);
            const idx = authStore.workspaces.findIndex(w => w.id === id);
            if (idx !== -1) authStore.workspaces[idx].is_active = true;
            if (authStore.currentWorkspace?.id === id) authStore.currentWorkspace.is_active = true;
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors de la restauration du workspace';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const transferOwnership = async (workspaceId, newOwnerId) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.post(`/workspaces/${workspaceId}/transfer-ownership`, {
                new_owner_id: newOwnerId,
            });
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erreur lors du transfert de propriété';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    // ==================== HELPERS ====================

    /** Returns workspace-level permissions for a member using the pre-computed user_permissions object. */
    const getMemberPermissions = (member) => {
        const workspace = authStore.currentWorkspace;
        const perms = workspace?.user_permissions ?? {};

        return {
            can_create_projects: perms.can_create_project ?? false,
            can_invite_members:  perms.can_invite_members ?? false,
            can_manage_settings: perms.can_manage_workspace_settings ?? false,
        };
    };

    const canManageMembers = (workspace, user) => {
        if (!workspace || !user) return false;
        if (workspace.owner_id === user.id) return true;
        return workspace.user_permissions?.can_invite_members ?? false;
    };

    /** Available contextual roles for workspace membership */
    const getAvailableRoles = () => [
        { value: 'manager',       label: 'Manager',       description: 'N2 validator, sees all projects' },
        { value: 'cadre',         label: 'Cadre',         description: 'N1 validator, manages activities' },
        { value: 'collaborateur', label: 'Collaborateur', description: 'Executes tasks' },
        { value: 'stagiaire',     label: 'Stagiaire',     description: 'Intern, limited access' },
        { value: 'observateur',   label: 'Observateur',   description: 'Read-only' },
    ];

    const getRoleLabel = (role) => ({
        owner:         'Propriétaire',
        manager:       'Manager',
        cadre:         'Cadre',
        collaborateur: 'Collaborateur',
        stagiaire:     'Stagiaire',
        observateur:   'Observateur',
    }[role] || role);

    const getRoleColor = (role) => ({
        owner:         'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
        manager:       'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        cadre:         'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
        collaborateur: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        stagiaire:     'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        observateur:   'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    }[role] || 'bg-gray-100 text-gray-800');

    // ==================== COMPUTED ====================

    const hasWorkspaces     = computed(() => authStore.workspaces.length > 0);
    const currentWorkspaceId   = computed(() => authStore.currentWorkspace?.id || null);
    const currentWorkspaceName = computed(() => authStore.currentWorkspace?.nom || '');

    // Kept for backward compatibility — components that used initializeWorkspace
    const initializeWorkspace = initializeCurrentWorkspace;

    return {
        // State (backed by Pinia — cleared on logout)
        currentWorkspace,
        workspaces,
        loading,
        error,

        // Computed
        hasWorkspaces,
        currentWorkspaceId,
        currentWorkspaceName,

        // Core methods
        initializeCurrentWorkspace,
        initializeWorkspace,
        fetchWorkspaces,
        fetchWorkspace,
        selectWorkspace,
        createWorkspace,
        updateWorkspace,
        deleteWorkspace,
        onWorkspaceChanged,

        // Member management
        fetchMembers,
        fetchInvitations,
        fetchAllInvitations,
        getInvitationStatistics,
        inviteMembers,
        resendInvitation,
        cancelInvitation,
        addMember,
        removeMember,
        updateMember,
        updateMemberRole,

        // Projects / stats
        fetchProjects,
        fetchStatistics,
        archiveWorkspace,
        unarchiveWorkspace,
        transferOwnership,

        // Helpers
        getMemberPermissions,
        canManageMembers,
        getAvailableRoles,
        getRoleLabel,
        getRoleColor,
    };
}
