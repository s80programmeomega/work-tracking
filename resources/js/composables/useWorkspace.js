// resources\js\composables\useWorkspace.js
import { ref, computed } from 'vue';
import api from '@/api/axios';

const currentWorkspace = ref(null);
const workspaces = ref([]);
const loading = ref(false);
const error = ref(null);


export function useWorkspace() {

  /**
   * Fetch all workspaces for the current user
   */
  const fetchWorkspaces = async (params = {}) => {
    loading.value = true;
    error.value = null;

    try {
      const response = await api.get('/workspaces', { params });

      // Si c'est paginé
      if (response.data.data && Array.isArray(response.data.data)) {
        workspaces.value = response.data.data;
        return response.data; // Retourne toute la réponse (avec pagination)
      }

      // Si ce n'est pas paginé
      workspaces.value = response.data.data || response.data;

      // Set first workspace as current if none selected
      if (!currentWorkspace.value && workspaces.value.length > 0) {
        currentWorkspace.value = workspaces.value[0];
        localStorage.setItem('current_workspace_id', workspaces.value[0].id);
      }

      return workspaces.value;
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement des workspaces';
      console.error('Error fetching workspaces:', err);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Fetch a specific workspace
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
   * Select a workspace as current
   */
  const selectWorkspace = (workspace) => {
    currentWorkspace.value = workspace;
    localStorage.setItem('current_workspace_id', workspace.id);
  };

  /**
   * Create a new workspace
   */
  const createWorkspace = async (data) => {
    loading.value = true;
    error.value = null;

    try {
      const config = {};

      if (data instanceof FormData) {
        config.headers = {
          'Content-Type': 'multipart/form-data'
        };
      }

      const response = await api.post('/workspaces', data, config);
      const newWorkspace = response.data.data;

      workspaces.value.push(newWorkspace);
      selectWorkspace(newWorkspace);

      return newWorkspace;
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la création du workspace';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Update a workspace
   */
  /**
   * Update a workspace (version corrigée)
   */
  const updateWorkspace = async (id, data) => {
    loading.value = true;
    error.value = null;

    try {
      let response;

      // Si c'est FormData, utiliser POST avec _method
      if (data instanceof FormData) {
        data.append('_method', 'PUT');

        // ✅ Debug: Afficher le contenu du FormData
        console.log('FormData envoyé:');
        for (let [key, value] of data.entries()) {
          if (value instanceof File) {
            console.log(key, ':', value.name, value.type, value.size);
          } else {
            console.log(key, ':', value);
          }
        }

        const config = {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        };

        response = await api.post(`/workspaces/${id}`, data, config);
      } else {
        // Requête JSON normale
        response = await api.put(`/workspaces/${id}`, data);
      }

      const updatedWorkspace = response.data.data;

      // Update in list
      const index = workspaces.value.findIndex(w => w.id === id);
      if (index !== -1) {
        workspaces.value[index] = updatedWorkspace;
      }

      // Update current if it's the same
      if (currentWorkspace.value?.id === id) {
        currentWorkspace.value = updatedWorkspace;
      }

      return updatedWorkspace;
    } catch (err) {
      console.error('Erreur lors de la mise à jour:', err);
      console.error('Response data:', err.response?.data);
      error.value = err.response?.data?.message || 'Erreur lors de la mise à jour du workspace';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Delete a workspace
   */
  const deleteWorkspace = async (id) => {
    loading.value = true;
    error.value = null;

    try {
      await api.delete(`/workspaces/${id}`);

      // Remove from list
      workspaces.value = workspaces.value.filter(w => w.id !== id);

      // Select another workspace if current was deleted
      if (currentWorkspace.value?.id === id) {
        currentWorkspace.value = workspaces.value[0] || null;
        if (currentWorkspace.value) {
          localStorage.setItem('current_workspace_id', currentWorkspace.value.id);
        } else {
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


  /**
   * Fetch workspace members
   */
  const fetchMembers = async (workspaceId) => {
    loading.value = true;
    error.value = null;

    try {
      const response = await api.get(`/workspaces/${workspaceId}/members`);
      return response.data.data;
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
    // alert(  'fetchInvitations called');
    try {
      const response = await api.get(`/workspaces/${workspaceId}/invitations`);
      return response.data.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement des invitations';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
 * Fetch all invitations across all workspaces (for admin/super_admin)
 */
  const fetchAllInvitations = async (filters = {}) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.get('/workspace-invitations/all', { params: filters })
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement des invitations'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Get invitation statistics
   */
  const getInvitationStatistics = async () => {
    try {
      const response = await api.get('/workspace-invitations/statistics')
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement des statistiques'
      throw err
    }
  }

  /**
   * Invite members to workspace
   */
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

  /**
   * Resend invitation
   */
  const resendInvitation = async (workspaceId, invitationId) => {
    loading.value = true;
    error.value = null;

    try {
      const response = await api.post(`/workspaces/${workspaceId}/invitations/${invitationId}/resend`);
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du renvoi de l\'invitation';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Cancel invitation
   */
  const cancelInvitation = async (workspaceId, invitationId) => {
    loading.value = true;
    error.value = null;

    try {
      const response = await api.delete(`/workspaces/${workspaceId}/invitations/${invitationId}`);
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de l\'annulation de l\'invitation';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Update workspace member role and permissions
   */
  const updateMemberRole = async (workspaceId, userId, memberData) => {
    loading.value = true;
    error.value = null;

    try {
      const response = await api.put(`/workspaces/${workspaceId}/members/${userId}`, memberData);
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la mise à jour du membre';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
    * Add a member to workspace
    */
  const addMember = async (workspaceId, userData) => {
    loading.value = true;
    error.value = null;

    try {
      const response = await api.post(
        `/workspaces/${workspaceId}/members`,
        userData
      );
      return response.data.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de l\'ajout du membre';
      throw err;
    } finally {
      loading.value = false;
    }
  };


  /**
     * Remove member from workspace
     */
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

  /**
  * Update member role/permissions
  */
  const updateMember = async (workspaceId, userId, data) => {
    loading.value = true;
    error.value = null;

    try {
      const response = await api.put(
        `/workspaces/${workspaceId}/members/${userId}`,
        data
      );
      return response.data.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la mise à jour du membre';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
     * Fetch workspace projects
  */
  const fetchProjects = async (workspaceId, params = {}) => {
    loading.value = true;
    error.value = null;

    try {
      const response = await api.get(`/workspaces/${workspaceId}/projets`, { params });
      console.log(response);
      
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement des projets';
      throw err;
    } finally {
      loading.value = false;
    }
  };


  // Fetch workspace statistics
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

  /**
   * Archive a workspace
   */
  const archiveWorkspace = async (id) => {
    loading.value = true;
    error.value = null;

    try {
      const response = await api.post(`/workspaces/${id}/archive`);

      // Update in list
      const index = workspaces.value.findIndex(w => w.id === id);
      if (index !== -1) {
        workspaces.value[index].is_active = false;
      }

      // Update current if it's the same
      if (currentWorkspace.value?.id === id) {
        currentWorkspace.value.is_active = false;
      }

      return response.data.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de l\'archivage du workspace';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Unarchive a workspace
   */
  const unarchiveWorkspace = async (id) => {
    loading.value = true;
    error.value = null;

    try {
      const response = await api.post(`/workspaces/${id}/unarchive`);

      // Update in list
      const index = workspaces.value.findIndex(w => w.id === id);
      if (index !== -1) {
        workspaces.value[index].is_active = true;
      }

      // Update current if it's the same
      if (currentWorkspace.value?.id === id) {
        currentWorkspace.value.is_active = true;
      }

      return response.data.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la restauration du workspace';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
  * Transfer workspace ownership
  */
  const transferOwnership = async (workspaceId, newOwnerId) => {
    loading.value = true;
    error.value = null;

    try {
      const response = await api.post(`/workspaces/${workspaceId}/transfer-ownership`, {
        new_owner_id: newOwnerId
      });
      return response.data.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du transfert de propriété';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
 * Initialize workspace from localStorage
 */
  const initializeWorkspace = async () => {
    const savedWorkspaceId = localStorage.getItem('current_workspace_id');

    await fetchWorkspaces();

    if (savedWorkspaceId) {
      const workspace = workspaces.value.find(
        w => w.id === parseInt(savedWorkspaceId)
      );
      if (workspace) {
        currentWorkspace.value = workspace;
      }
    }
  };

  /**
   * Get member permissions and role
   */
  const getMemberPermissions = (member) => {
    const pivot = member.pivot || {};
    let permissions = {};

    // Si les permissions sont stockées dans un champ JSON
    if (pivot.permissions) {
      // Si c'est le format "all"
      if ((pivot.permissions === '["all"]') || (pivot.permissions === ['all'])) {
        permissions = {
          can_create_projects: true,
          can_invite_members: true,
          can_manage_settings: true,
        };
      }
      // Si c'est un objet de permissions
      else if (typeof pivot.permissions === 'object') {
        permissions = pivot.permissions;
      }
      // Si c'est une chaîne JSON
      else if (typeof pivot.permissions === 'string') {
        try {
          const decoded = JSON.parse(pivot.permissions);
          permissions = typeof decoded === 'object' ? decoded : {};
        } catch (e) {
          console.warn('Invalid permissions format:', pivot.permissions);
          permissions = {};
        }
      }
    }

    // Si les permissions sont stockées dans des champs séparés (fallback)
    return {
      can_create_projects: permissions.can_create_projects || pivot.can_create_projects || false,
      can_invite_members: permissions.can_invite_members || pivot.can_invite_members || false,
      can_manage_settings: permissions.can_manage_settings || pivot.can_manage_settings || false,
    };
  };

  /**
  * Check if user can manage workspace members
  */
  const canManageMembers = (workspace, user) => {
    if (!workspace || !user) return false;

    // Le propriétaire peut tout gérer
    if (workspace.owner_id === user.id) {
      return true;
    }

    // Vérifier les permissions via le pivot
    const member = workspace.members?.find(m => m.id === user.id);
    if (!member || !member.pivot) return false;

    const role = member.pivot.role;
    return ['owner', 'super_admin', 'admin'].includes(role);
  };

  /**
   * Get available roles with labels
   */
  const getAvailableRoles = () => {
    return [
      { value: 'admin', label: 'Administrateur', description: 'Accès complet à la gestion du workspace' },
      { value: 'member', label: 'Membre', description: 'Peut participer aux projets et activités' },
      { value: 'viewer', label: 'Observateur', description: 'Accès en lecture seule' },
    ];
  };

  /**
   * Get role label
   */
  const getRoleLabel = (role) => {
    const roles = {
      owner: 'Propriétaire',
      super_admin: 'Super Admin',
      admin: 'Administrateur',
      member: 'Membre',
      viewer: 'Observateur',
    };
    return roles[role] || role;
  };

  /**
   * Get role color classes
   */
  const getRoleColor = (role) => {
    const colors = {
      owner: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
      super_admin: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
      admin: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
      member: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
      viewer: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    };
    return colors[role] || colors.viewer;
  };

  // Computed properties
  const hasWorkspaces = computed(() => workspaces.value.length > 0);

  const currentWorkspaceId = computed(() => currentWorkspace.value?.id || null);

  const currentWorkspaceName = computed(() => currentWorkspace.value?.nom || '');

  const isWorkspaceOwner = computed(() => {
    // TODO: Get current user from auth store
    // return currentWorkspace.value?.owner_id === currentUser.value?.id;
    return false;
  });

  return {
    // State
    currentWorkspace,
    workspaces,
    loading,
    error,

    // Computed
    hasWorkspaces,
    currentWorkspaceId,
    currentWorkspaceName,
    isWorkspaceOwner,

    // Methods
    fetchWorkspaces,
    fetchWorkspace,
    selectWorkspace,
    createWorkspace,
    updateWorkspace,
    deleteWorkspace,
    fetchMembers,
    addMember,
    removeMember,
    updateMember,
    fetchProjects,
    fetchStatistics,
    archiveWorkspace,
    unarchiveWorkspace,
    transferOwnership,
    initializeWorkspace,

    // Nouvelles méthodes pour la gestion des membres
    fetchInvitations,
    inviteMembers,
    resendInvitation,
    cancelInvitation,
    updateMemberRole,
    getMemberPermissions,
    canManageMembers,
    getAvailableRoles,
    getRoleLabel,
    getRoleColor,
  };
}