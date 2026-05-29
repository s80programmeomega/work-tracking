import api from './axios'

export const usersAPI = {
  /**
   * Get paginated users list
   */
  async getUsers(params = {}) {
    return api.get('/users', { params })
  },

  /**
   * Search users
   */
  async searchUsers(query, limit = 10) {
    return api.get('/users/search', {
      params: { q: query, limit }
    })
  },

  /**
   * Get specific user
   */
  async getUser(userId) {
    return api.get(`/users/${userId}`)
  },

  /**
   * Create new user
   */
  async createUser(userData) {
    return api.post('/users', userData)
  },

  /**
   * Update user
   */
  async updateUser(userId, userData) {
    return api.put(`/users/${userId}`, userData)
  },

  /**
   * Delete user
   */
  async deleteUser(userId) {
    return api.delete(`/users/${userId}`)
  },

  /**
   * Get current user profile
   */
  async getProfile() {
    return api.get('/users/profile')
  },

  /**
   * Update current user profile
   */
  async updateProfile(profileData) {
    // Handle file upload
    if (profileData.avatar instanceof File) {
      const formData = new FormData()
      Object.keys(profileData).forEach(key => {
        formData.append(key, profileData[key])
      })
      return api.post('/users/profile', formData)
    }

    return api.put('/users/profile', profileData)
  },

  /**
   * Change password
   */
  async changePassword(passwordData) {
    return api.post('/users/change-password', passwordData)
  },

  /**
   * Toggle user active status
   */
  async toggleActive(userId) {
    return api.post(`/users/${userId}/toggle-active`)
  },

  /**
   * Get user activity log
   */
  async getUserActivity(userId) {
    return api.get(`/users/${userId}/activity`)
  },
}
