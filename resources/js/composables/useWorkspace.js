// resources\js\composables\useWorkspace.js
import { ref, computed, watch } from 'vue';
import api from '@/api/axios';
import { useAuthStore } from '@/stores/auth';

const currentWorkspace = ref(null);
const workspaces = ref([]);
const loading = ref(false);
const error = ref(null);

// ✅ Event bus pour la communication entre composants
const workspaceChangeListeners = new Set();

export function useWorkspace() {
  const authStore = useAuthStore();

  /**
     * ✅ Initialiser le workspace courant depuis plusieurs sources
     */
  const initializeCurrentWorkspace = async () => {
    loading.value = true;
    try {
      // 1. Charger les workspaces disponibles
      if (workspaces.value.length === 0) {
        await fetchWorkspaces();
      }

      // 2. Déterminer le workspace courant (ordre de priorité)
      let workspaceId = null;

      // a) Depuis le store auth
      if (authStore.currentWorkspaceId) {
        workspaceId = authStore.currentWorkspaceId;
      }
      // b) Depuis localStorage
      else if (localStorage.getItem('current_workspace_id')) {
        workspaceId = parseInt(localStorage.getItem('current_workspace_id'));
      }
      // c) Premier workspace disponible
      else if (workspaces.value.length > 0) {
        workspaceId = workspaces.value[0].id;
      }

      // 3. Définir le workspace courant
      if (workspaceId) {
        const workspace = workspaces.value.find(w => w.id === workspaceId);
        if (workspace) {
          currentWorkspace.value = workspace;
          authStore.setCurrentWorkspace(workspaceId);
          localStorage.setItem('current_workspace_id', workspaceId);
        }
      }

      return currentWorkspace.value;
    } catch (err) {
      console.error('Erreur lors de l\'initialisation du workspace:', err);
      error.value = err.response?.data?.message || 'Erreur d\'initialisation';
      throw err;
    } finally {
      loading.value = false;
    }
  };

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
      } else {
        workspaces.value = response.data.data || response.data;
      }

      return workspaces.value;
      // // Si ce n'est pas paginé
      // workspaces.value = response.data.data || response.data;

      // // Set first workspace as current if none selected
      // if (!currentWorkspace.value && workspaces.value.length > 0) {
      //   currentWorkspace.value = workspaces.value[0];
      //   localStorage.setItem('current_workspace_id', workspaces.value[0].id);
      // }

      // return workspaces.value;
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
   * ✅ Sélectionner un workspace et notifier tous les composants
   */
  const selectWorkspace = async (workspace) => {
    const oldWorkspaceId = currentWorkspace.value?.id;
    const newWorkspaceId = workspace.id;

    // Ne rien faire si c'est le même workspace
    if (oldWorkspaceId === newWorkspaceId) {
      return;
    }

    // Mettre à jour le workspace courant
    currentWorkspace.value = workspace;
    authStore.setCurrentWorkspace(newWorkspaceId);
    localStorage.setItem('current_workspace_id', newWorkspaceId);

    // ✅ Notifier le backend
    try {
      await api.post(`/workspaces/switch/${newWorkspaceId}`);
    } catch (err) {
      console.warn('Erreur lors du switch workspace côté serveur:', err);
    }

    // ✅ Notifier tous les listeners
    const event = new CustomEvent('workspace-changed', {
      detail: {
        workspace,
        oldWorkspaceId,
        newWorkspaceId
      }
    });
    window.dispatchEvent(event);

    // ✅ Notifier les listeners directs - AVEC VALIDATION
    const listenersToRemove = [];
    workspaceChangeListeners.forEach(listener => {
      if (typeof listener === 'function') {
        try {
          listener(event);
        } catch (error) {
          console.error('Error in workspace change listener:', error);
        }
      } else {
        // Marquer les listeners invalides pour suppression
        listenersToRemove.push(listener);
      }
    });

    // Nettoyer les listeners invalides
    listenersToRemove.forEach(invalidListener => {
      workspaceChangeListeners.delete(invalidListener);
    });

    // ✅ Déclencher l'événement global
    window.dispatchEvent(event);
  };

  /**
  * ✅ S'abonner aux changements de workspace
  */
  const onWorkspaceChanged = (callback) => {
    // Validation du callback
    if (typeof callback !== 'function') {
      console.error('onWorkspaceChanged: callback must be a function', callback);
      return () => { }; // Retourne une fonction vide si le callback n'est pas valide
    }

    const handler = (event) => {
      try {
        callback(event);
      } catch (error) {
        console.error('Error in workspace change callback:', error);
      }
    };

    // Ajouter le callback original au Set pour la notification directe
    workspaceChangeListeners.add(callback);

    // Écouter l'événement global
    window.addEventListener('workspace-changed', handler);

    // Retourner la fonction de nettoyage
    return () => {
      workspaceChangeListeners.delete(callback);
      window.removeEventListener('workspace-changed', handler);
    };
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
   * Update a workspace
   */
  const updateWorkspace = async (id, data) => {
    loading.value = true;
    error.value = null;

    try {
      let response;

      // Si c'est FormData, utiliser POST avec _method
      if (data instanceof FormData) {
        data.append('_method', 'PUT');

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
        const newWorkspace = workspaces.value[0] || null;
        if (newWorkspace) {
          await selectWorkspace(newWorkspace);
        } else {
          currentWorkspace.value = null;
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


  /**
   * Fetch workspace members
   */
const fetchMembers = async (workspaceId) => {
  loading.value = true
  error.value = null

  try {
    const response = await api.get(`/workspaces/${workspaceId}/members`)
    
    console.log('✅ Membres chargés:', response.data)
    
    // Les membres sont déjà formatés avec permissions et statistiques
    const members = response.data.data || []
    
    // Validation des données
    members.forEach(member => {
      if (!member.pivot) {
        console.warn('⚠️ Membre sans pivot:', member)
        member.pivot = {
          role: 'viewer',
          permissions: {
            can_create_projects: false,
            can_invite_members: false,
            can_manage_settings: false,
          }
        }
      }
      
      if (!member.pivot.permissions) {
        member.pivot.permissions = {
          can_create_projects: false,
          can_invite_members: false,
          can_manage_settings: false,
        }
      }
      
      if (!member.statistics) {
        member.statistics = {
          projets_count: 0,
          taches_count: 0,
          taches_completees: 0,
          taux_completion: 0,
        }
      }
      
      if (!member.projets) {
        member.projets = []
      }
    })
    
    return members
  } catch (err) {
    console.error('❌ Erreur chargement membres:', err)
    error.value = err.response?.data?.message || 'Erreur lors du chargement des membres'
    throw err
  } finally {
    loading.value = false
  }
}

const fetchInvitations = async (workspaceId) => {
  loading.value = true
  error.value = null
  
  try {
    const response = await api.get(`/workspaces/${workspaceId}/invitations`)
    
    console.log('✅ Invitations chargées:', response.data)
    
    // La réponse contient déjà les données formatées du backend
    return response.data.data || []
  } catch (err) {
    console.error('❌ Erreur chargement invitations:', err)
    error.value = err.response?.data?.message || 'Erreur lors du chargement des invitations'
    throw err
  } finally {
    loading.value = false
  }
}

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
  loading.value = true
  error.value = null

  try {
    console.log('📧 Envoi invitations:', {
      workspaceId,
      emails: inviteData.emails,
      role: inviteData.role,
      send_email: inviteData.send_email
    })

    const response = await api.post(`/workspaces/${workspaceId}/members/invite`, inviteData)
    
    console.log('✅ Réponse invitations:', response.data)
    
    // Afficher un résumé dans la console
    if (response.data.data) {
      const { success_count, error_count, invitations, errors } = response.data.data
      
      console.log(`✅ Invitations réussies: ${success_count}`)
      if (error_count > 0) {
        console.warn(`⚠️ Invitations échouées: ${error_count}`)
        errors.forEach(err => {
          console.warn(`  - ${err.email}: ${err.message}`)
        })
      }
      
      invitations.forEach(inv => {
        console.log(`  ✓ ${inv.email} ${inv.user_exists ? '(utilisateur existant)' : '(nouvel utilisateur)'}`)
      })
    }
    
    return response.data
  } catch (err) {
    console.error('❌ Erreur invitations:', err)
    error.value = err.response?.data?.message || 'Erreur lors de l\'envoi des invitations'
    throw err
  } finally {
    loading.value = false
  }
}

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
  // Validation de base
  if (!member || !member.pivot) {
    console.warn('⚠️ Member sans pivot:', member)
    return {
      can_create_projects: false,
      can_invite_members: false,
      can_manage_settings: false,
    }
  }

  const pivot = member.pivot
  let permissions = pivot.permissions || {}

  // Si c'est une string, essayer de parser
  if (typeof permissions === 'string') {
    try {
      permissions = JSON.parse(permissions)
    } catch (e) {
      console.error('❌ Erreur parsing permissions:', permissions)
      permissions = {}
    }
  }

  // Si c'est le format "all"
  if (permissions === 'all' || 
      (Array.isArray(permissions) && permissions[0] === 'all') ||
      (typeof permissions === 'string' && permissions === '["all"]')) {
    return {
      can_create_projects: true,
      can_invite_members: true,
      can_manage_settings: true,
    }
  }

  // Format normal
  return {
    can_create_projects: permissions.can_create_projects ?? false,
    can_invite_members: permissions.can_invite_members ?? false,
    can_manage_settings: permissions.can_manage_settings ?? false,
  }
}

  /**
  * Check if user can manage workspace members
  */
  const canManageMembers = (workspace, user) => {
    if (!workspace || !user) return false;

    // Le propriétaire peut tout gérer
    if (workspace.owner_id === user.id) {
      return true;
    }

    console.log('canManageMembers',workspace);
    
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
    initializeCurrentWorkspace,
    fetchWorkspaces,
    fetchWorkspace,
    selectWorkspace,
    createWorkspace,
    updateWorkspace,
    deleteWorkspace,
    addMember,
    removeMember,
    updateMember,
    fetchProjects,
    fetchStatistics,
    archiveWorkspace,
    unarchiveWorkspace,
    transferOwnership,
    initializeWorkspace,
    onWorkspaceChanged,

    // Nouvelles méthodes pour la gestion des membres
    fetchMembers,
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