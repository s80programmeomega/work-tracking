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
 * Remove a member from workspace
 */
  const removeMember = async (workspaceId, userId) => {
    loading.value = true;
    error.value = null;

    try {
      await api.delete(`/workspaces/${workspaceId}/members/${userId}`);
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
  };
}