// resources/js/stores/auth.js
import { defineStore } from 'pinia'
import axios from 'axios'

/**
 * Auth store (Pinia)
 * - Respecte la nouvelle architecture (is_super_admin, workspace_members, projet_user pivots)
 * - Centralise axios via `api`
 * - Token kept in memory (this.token) + fallback localStorage
 * - Auto refresh token before expiration (based on JWT exp if present)
 *
 * NOTE: Assumptions (défensives) :
 * - L'endpoint /api/user retourne idéalement :
 *   { id, nom, ..., is_super_admin, workspace_members: [...], projet_user: [...], permissions: [...] }
 * - Si ton backend retourne d'autres clés, adapte les accès ci-dessous en conséquence.
 */

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Accept': 'application/json'
  },
  withCredentials: true // utile si refresh token est en cookie HttpOnly côté serveur
})

// --- helper: decode token expiration ---
function getJwtExpiry(token) {
  if (!token) return null
  try {
    const parts = token.split('.')
    if (parts.length !== 3) return null
    const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')))
    if (!payload.exp) return null
    // exp is in seconds
    return payload.exp * 1000
  } catch (e) {
    return null
  }
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: null, // preferred: keep in memory
    isAuthenticated: false,
    loading: false,
    error: null,
    _refreshTimerId: null, // internal interval id
    _isRefreshing: false,  // avoid concurrent refreshes
        current_workspace_id: localStorage.getItem('current_workspace_id')

  }),

  getters: {
    isLoggedIn: (s) => s.isAuthenticated && !!s.user,
    // Super admin global (users.is_super_admin)
    isSuperAdmin: (s) => !!s.user?.is_super_admin,
    currentWorkspaceId: (state) => state.current_workspace_id,

    // Retourne workspace membership pivot si présent
    _workspaceMemberById: (s) => (workspaceId) => {
      // API should return e.g. user.workspace_members [{ workspace_id, role, permissions }]
      if (!s.user) return null
      const members = s.user.workspace_members || s.user.workspaces || []
      return members.find(m => Number(m.workspace_id ?? m.id) === Number(workspaceId)) || null
    },

    // Check workspace role(s) — roles peut être string ou array
    hasWorkspaceRole: (s) => (workspaceId, roles) => {
      if (!s.user) return false
      if (s.isSuperAdmin) return true // super admin bypass
      const pivot = this._workspaceMemberById(workspaceId)
      if (!pivot) return false
      const role = pivot.role
      const wanted = Array.isArray(roles) ? roles : [roles]
      return wanted.includes(role)
    },

    // Vérifie si user peut voir tous les projets d'un workspace
    canViewAllWorkspaceProjects: (s) => (workspaceId) => {
      if (!s.user) return false
      if (s.isSuperAdmin) return true
      // Owner property in workspace_pivot or workspace.owner_id etc.
      const pivot = this._workspaceMemberById(workspaceId)
      if (pivot?.role === 'owner' || pivot?.role === 'admin') return true
      // Also allow explicit permission in pivot.permissions JSON
      const perms = pivot?.permissions || {}
      if (perms.can_view_all_projects === true) return true
      return false
    },

    // Project membership helpers (check projet_user pivot in user payload)
    isProjectMember: (s) => (projectId) => {
      if (!s.user) return false
      if (s.isSuperAdmin) return true
      const p = (s.user.projet_user || s.user.projects || []).find(x => Number(x.projet_id ?? x.id) === Number(projectId))
      return !!p
    },

    getProjectPivot: (s) => (projectId) => {
      if (!s.user) return null
      return (s.user.projet_user || s.user.projects || []).find(x => Number(x.projet_id ?? x.id) === Number(projectId)) || null
    },

    // Vérifie permission générale (liste `user.permissions` si ton API la fournit)
    hasPermission: (s) => (permission) => {
      if (!s.user) return false
      if (s.isSuperAdmin) return true
      const perms = s.user.permissions || []
      return perms.some(p => (p.name || p) === permission)
    }
  },

  actions: {
    // configure axios headers
    _setApiToken(token) {
      if (token) {
        api.defaults.headers.common['Authorization'] = `Bearer ${token}`
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}` // legacy code compatibility
      } else {
        delete api.defaults.headers.common['Authorization']
        delete axios.defaults.headers.common['Authorization']
      }
    },

    // start auto-refresh based on token exp
    _startAutoRefresh(token) {
      this._stopAutoRefresh()
      if (!token) return
      const expiry = getJwtExpiry(token)
      if (!expiry) return // no jwt exp present — can't schedule reliably

      // refresh a few seconds before expiry
      const refreshBeforeMs = 60 * 1000 // 1 minute
      const scheduleAt = Math.max(1000, expiry - Date.now() - refreshBeforeMs)

      // Single timeout (instead of interval) to avoid drift
      this._refreshTimerId = setTimeout(async () => {
        try {
          await this.refreshToken()
        } catch (e) {
          // If refresh fails, logout (handled in refreshToken)
        }
      }, scheduleAt)
    },

    _stopAutoRefresh() {
      if (this._refreshTimerId) {
        clearTimeout(this._refreshTimerId)
        this._refreshTimerId = null
      }
    },

     // ✅ ACTION POUR METTRE À JOUR LE WORKSPACE COURANT
    setCurrentWorkspace(workspaceId) {
      this.current_workspace_id = workspaceId;
      localStorage.setItem('current_workspace_id', workspaceId);
      
      // Mettre à jour l'utilisateur si nécessaire
      if (this.user) {
        this.user.current_workspace_id = workspaceId;
      }
    },

     // ✅ ACTION POUR METTRE À JOUR LES DONNÉES UTILISATEUR AVEC WORKSPACE
    setUser(userData) {
      this.user = userData;
      
      // Définir le workspace courant depuis les données utilisateur
      if (userData.current_workspace_id) {
        this.setCurrentWorkspace(userData.current_workspace_id);
      }
    },
    // --- Public actions ---

    /**
     * login(credentials)
     * Expected backend response:
     * {
     *   token: '...access token...',
     *   user: { id, ..., is_super_admin, workspace_members: [...], projet_user: [...], permissions: [...] },
     *   // optionally: expires_at or refresh token handled by cookie
     * }
     */
    async login(credentials) {
      this.loading = true
      this.error = null
      try {
        // CSRF cookie if using sanctum
        await axios.get('/sanctum/csrf-cookie').catch(() => {})

        const res = await api.post('/auth/login', credentials)
        const data = res.data

        // Defensive: adapt to your API shape
        const token = data.token || data.access_token || data.data?.token
        const user = data.user || data.data?.user || data.data

        if (!token || !user) {
          throw new Error('Réponse d\'auth incorrecte : vérifier l\'API')
        }

        // store in memory
        this.token = token
        this.user = user
        this.isAuthenticated = true

        // persist token if desired (fallback)
        try {
          localStorage.setItem('auth_token', token)
        } catch (e) { /* ignore storage errors */ }

        this._setApiToken(token)
        this._startAutoRefresh(token)

        return { user, token }
      } catch (error) {
        this.error = error?.response?.data?.message || error.message || 'Login failed'
        await this.logout()
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * logout()
     * Calls backend logout (if any) then clears local state
     */
    async logout() {
      this.loading = true
      try {
        if (this.token) {
          // Try server logout (may clear refresh cookie)
          await api.post('/auth/logout').catch(() => {})
        }
      } catch (e) {
        // ignore, proceed to clear local state
      } finally {
        // Clear everything locally
        this._clearAuthState()
        this.loading = false
      }
    },

    _clearAuthState() {
      this.user = null
      this.token = null
      this.isAuthenticated = false
      this.error = null
      this._stopAutoRefresh()
      try { localStorage.removeItem('auth_token') } catch (e) {}
      this._setApiToken(null)
    },

    /**
     * fetchUser()
     * Récupère les données utilisateur (doit inclure workspace_members, projet_user, permissions si possible)
     */
    async fetchUser() {
      if (!this.token) return null
      this.loading = true
      try {
        this._setApiToken(this.token)
        const res = await api.get('/user')
        // adapt selon le format : res.data ou res.data.data
        const payload = res.data?.data ?? res.data
        this.user = payload
        this.isAuthenticated = true
        return this.user
      } catch (error) {
        // si on reçoit 401 ou autre, essayer refresh une fois (interceptor aussi gère)
        try {
          await this.refreshToken()
          // retry fetch
          const res2 = await api.get('/user')
          const payload2 = res2.data?.data ?? res2.data
          this.user = payload2
          this.isAuthenticated = true
          return this.user
        } catch (e) {
          await this.logout()
          throw error
        }
      } finally {
        this.loading = false
      }
    },

    /**
     * refreshToken()
     * - idéal : backend stocke refresh token en cookie HttpOnly et renvoie nouvel access token via /auth/refresh
     * - action rend l'access token dans this.token et relance fetchUser si nécessaire
     */
    async refreshToken() {
      if (this._isRefreshing) return
      this._isRefreshing = true
      try {
        // endpoint à adapter si besoin
        const res = await api.post('/auth/refresh') // <-- adapte si ton endpoint diffère
        const newToken = res.data?.token || res.data?.access_token || res.data?.data?.token
        if (!newToken) {
          throw new Error('No token returned from refresh endpoint')
        }

        this.token = newToken
        try { localStorage.setItem('auth_token', newToken) } catch (e) {}
        this._setApiToken(newToken)
        this._startAutoRefresh(newToken)
        // optionally refresh user payload
        await this.fetchUser().catch(() => {})
        return newToken
      } catch (error) {
        // échec de refresh → logout
        await this._clearAuthState()
        throw error
      } finally {
        this._isRefreshing = false
      }
    },

    /**
     * initializeAuth()
     * à appeler au bootstrap de l'app (main.js)
     */
    initializeAuth() {
      // 1) Prefer token in memory; else localStorage fallback
      const savedToken = localStorage.getItem('auth_token')
      if (savedToken) {
        this.token = savedToken
        this._setApiToken(savedToken)
        // try to fetch user; if fails, attempt refresh once (fetchUser handles that)
        this.fetchUser().catch(() => {
          // fetchUser already logs out on failure
        })
        // start auto refresh as best-effort
        this._startAutoRefresh(savedToken)
      }
    },

    /**
     * Utility pour vérifier rapidement si user a un role sur workspaceId
     * Exposed pour usage dans composants (ex: sidebar)
     */
    hasWorkspaceRoleQuick(workspaceId, roles) {
      return this.hasWorkspaceRole(workspaceId, roles)
    },

    /**
     * Check project-level permission quickly using projet pivot
     * permissionFlag: 'can_edit' | 'can_delete' | 'can_invite'
     */
    hasProjectPermission(projectId, permissionFlag) {
      if (!this.user) return false
      if (this.isSuperAdmin) return true
      const pivot = (this.user.projet_user || this.user.projects || []).find(x => Number(x.projet_id ?? x.id) === Number(projectId))
      if (!pivot) return false
      // owner/admin roles in pivot also grant rights
      if (['owner','admin'].includes(pivot.role)) return true
      return !!pivot[permissionFlag]
    }
  }
})
