import { defineStore } from 'pinia'
import { usersAPI } from '@/api/users'

export const useUserStore = defineStore('users', {
  state: () => ({
    users: [],
    currentUser: null,
    selectedUser: null,
    loading: false,
    error: null,
    pagination: {
      currentPage: 1,
      lastPage: 1,
      perPage: 15,
      total: 0,
    },
    filters: {
      search: '',
      role: null,
      team_id: null,
      is_active: null,
      sort_by: 'created_at',
      sort_direction: 'desc',
    },
  }),

  getters: {
    activeUsers: (state) => state.users.filter(u => u.is_active),

    usersByRole: (state) => (role) =>
      state.users.filter(u => u.role === role),

    totalUsers: (state) => state.pagination.total,

    isLoading: (state) => state.loading,
  },

  actions: {
    /**
     * Fetch users with filters
     */
    async fetchUsers(page = 1) {
      this.loading = true
      this.error = null

      try {
        const params = {
          ...this.filters,
          page,
          per_page: this.pagination.perPage,
        }

        const { data } = await usersAPI.getUsers(params)

        this.users = data.data
        this.pagination = {
          currentPage: data.meta.current_page,
          lastPage: data.meta.last_page,
          perPage: data.meta.per_page,
          total: data.meta.total,
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch users'
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Search users
     */
    async searchUsers(query) {
      try {
        const { data } = await usersAPI.searchUsers(query)
        return data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Search failed'
        throw error
      }
    },

    /**
     * Fetch single user
     */
    async fetchUser(userId) {
      this.loading = true
      this.error = null

      try {
        const { data } = await usersAPI.getUser(userId)
        this.selectedUser = data.data
        return data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch user'
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Create new user
     */
    async createUser(userData) {
      this.loading = true
      this.error = null

      try {
        const { data } = await usersAPI.createUser(userData)
        this.users.unshift(data.data)
        this.pagination.total++
        return data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create user'
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Update user
     */
    async updateUser(userId, userData) {
      this.loading = true
      this.error = null

      try {
        const { data } = await usersAPI.updateUser(userId, userData)

        // Update in list
        const index = this.users.findIndex(u => u.id === userId)
        if (index !== -1) {
          this.users[index] = data.data
        }

        // Update selected if same
        if (this.selectedUser?.id === userId) {
          this.selectedUser = data.data
        }

        return data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update user'
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Delete user
     */
    async deleteUser(userId) {
      this.loading = true
      this.error = null

      try {
        await usersAPI.deleteUser(userId)

        // Remove from list
        this.users = this.users.filter(u => u.id !== userId)
        this.pagination.total--

        // Clear selected if same
        if (this.selectedUser?.id === userId) {
          this.selectedUser = null
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to delete user'
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Fetch current user profile
     */
    async fetchProfile() {
      this.loading = true
      this.error = null

      try {
        const { data } = await usersAPI.getProfile()
        this.currentUser = data.data
        return data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch profile'
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Update current user profile
     */
    async updateProfile(profileData) {
      this.loading = true
      this.error = null

      try {
        const { data } = await usersAPI.updateProfile(profileData)
        this.currentUser = data.data
        return data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update profile'
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Change password
     */
    async changePassword(passwordData) {
      this.loading = true
      this.error = null

      try {
        await usersAPI.changePassword(passwordData)
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to change password'
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Toggle user active status
     */
    async toggleActiveStatus(userId) {
      this.loading = true
      this.error = null

      try {
        const { data } = await usersAPI.toggleActive(userId)

        // Update in list
        const index = this.users.findIndex(u => u.id === userId)
        if (index !== -1) {
          this.users[index] = data.data
        }

        return data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update status'
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Set filters
     */
    setFilters(filters) {
      this.filters = { ...this.filters, ...filters }
    },

    /**
     * Reset filters
     */
    resetFilters() {
      this.filters = {
        search: '',
        role: null,
        team_id: null,
        is_active: null,
        sort_by: 'created_at',
        sort_direction: 'desc',
      }
    },

    /**
     * Clear error
     */
    clearError() {
      this.error = null
    },
  },
})
