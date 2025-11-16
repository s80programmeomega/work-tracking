// resources\js\stores\auth.js
import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('auth_token') || null,
    isAuthenticated: false,
    loading: false
  }),

  getters: {
    isLoggedIn: (state) => state.isAuthenticated && state.user !== null,
    hasRole: (state) => (role) => {
      return state.user?.role === role
    },
    hasRoleLevel: (state) => (role) => {
      if (!state.user) return false

      const roleHierarchy = {
        'stagiaire': 1,
        'cadre': 2,
        'responsable_n2': 3,
        'responsable_n1': 4,
        'manager': 5,
        'super_admin': 6,
      }

      const userLevel = roleHierarchy[state.user.role] || 0
      const requiredLevel = roleHierarchy[role] || 0

      return userLevel >= requiredLevel
    },
    hasPermission: (state) => (permission) => {
      if (!state.user) return false

      // 1️⃣ Vérifie le champ is_super_admin (BDD)
      if (state.user?.is_super_admin) return true;

      // 2️⃣ Vérifie le champ role string
      if (state.user?.role === 'super_admin') return true;

      // 3️⃣ Vérifie la collection roles
      if (state.user?.roles) {
        return state.user.roles.some(r => (r.name || r) === 'super_admin');
      }

      return false;

      // Check if user has specific permission via Spatie Permission
      if (state.user.permissions && state.user.permissions.length > 0) {
        return state.user.permissions.some(perm => perm.name === permission)
      }

      // Fallback: Check permissions based on role hierarchy
      return state.hasPermissionByRole(permission)
    },
    hasPermissionByRole: (state) => (permission) => {
      if (!state.user) return false

      const rolePermissions = {
        'super_admin': ['*'], // All permissions
        'admin': [
          'auth.login', 'auth.logout', 'profile.view', 'profile.edit', 'profile.change_password',
          'team.view', 'team.create', 'team.edit', 'team.delete', 'team.manage_members', 'team.invite_members',
          'user.view', 'user.create', 'user.edit', 'user.assign_role',
          'projet.view', 'projet.create', 'projet.edit', 'projet.delete', 'projet.assign',
          'tache.view', 'tache.create', 'tache.edit', 'tache.assign', 'tache.validate', 'tache.change_status',
          'rapport.view', 'rapport.create', 'rapport.export',
        ],
        'member': [
          'auth.login', 'auth.logout', 'profile.view', 'profile.edit', 'profile.change_password',
          'team.view', 'team.manage_members',
          'user.view',
          'projet.view', 'projet.edit',
          'tache.view', 'tache.create', 'tache.edit', 'tache.assign', 'tache.validate', 'tache.change_status',
          'rapport.view', 'rapport.create',
        ],
        'member': [
          'auth.login', 'auth.logout', 'profile.view', 'profile.edit', 'profile.change_password',
          'team.view',
          'user.view',
          'projet.view', 'projet.edit',
          'tache.view', 'tache.create', 'tache.edit', 'tache.assign', 'tache.change_status',
          'rapport.view',
        ],
        'cadre': [
          'auth.login', 'auth.logout', 'profile.view', 'profile.edit', 'profile.change_password',
          'team.view',
          'user.view',
          'projet.view',
          'tache.view', 'tache.edit', 'tache.change_status',
          'rapport.view',
        ],
        'stagiaire': [
          'auth.login', 'auth.logout', 'profile.view', 'profile.edit', 'profile.change_password',
          'team.view',
          'projet.view',
          'tache.view', 'tache.change_status',
        ],
      }

      const userPermissions = rolePermissions[state.user.role] || []
      return userPermissions.includes('*') || userPermissions.includes(permission)
    }
  },

  actions: {
    async login(credentials) {
      this.loading = true

      try {
        // Get CSRF token first
        await axios.get('/sanctum/csrf-cookie')

        const response = await axios.post('/api/auth/login', credentials)

        if (response.data.token) {
          this.token = response.data.token
          this.user = response.data.user
          this.isAuthenticated = true

          localStorage.setItem('auth_token', this.token)
          axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
        }

        return response.data
      } catch (error) {
        this.logout()
        throw error
      } finally {
        this.loading = false
      }
    },

    async register(userData) {
      this.loading = true

      try {
        await axios.get('/sanctum/csrf-cookie')
        const response = await axios.post('/api/register', userData)
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async logout() {
      this.loading = true

      try {
        if (this.token) {
          await axios.post('/api/logout')
        }
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        this.user = null
        this.token = null
        this.isAuthenticated = false

        localStorage.removeItem('auth_token')
        delete axios.defaults.headers.common['Authorization']

        this.loading = false
      }
    },

    async fetchUser() {
      if (!this.token) return null

      this.loading = true

      try {
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
        const response = await axios.get('/api/user')

        this.user = response.data
        this.isAuthenticated = true

        return this.user
      } catch (error) {
        this.logout()
        throw error
      } finally {
        this.loading = false
      }
    },

    async forgotPassword(email) {
      this.loading = true

      try {
        await axios.get('/sanctum/csrf-cookie')
        const response = await axios.post('/api/forgot-password', { email })
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async resetPassword(data) {
      this.loading = true

      try {
        await axios.get('/sanctum/csrf-cookie')
        const response = await axios.post('/api/reset-password', data)
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    initializeAuth() {
      if (this.token) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
        this.fetchUser().catch(() => {
          this.logout()
        })
      }
    }
  }
})