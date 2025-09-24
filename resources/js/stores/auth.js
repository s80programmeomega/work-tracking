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
    }
  },

  actions: {
    async login(credentials) {
      this.loading = true

      try {
        // Get CSRF token first
        await axios.get('/sanctum/csrf-cookie')

        const response = await axios.post('/api/login', credentials)

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

        return response.data
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