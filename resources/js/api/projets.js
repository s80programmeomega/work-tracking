import api from './axios'

const API_URL = '/projets'

export default {
  // List and filter
  async getAll(filters = {}) {
    return api.get(API_URL, { params: filters })
  },

  async getMyProjets(filters = {}) {
    return api.get(`${API_URL}/my-projets`, { params: filters })
  },

  async getDashboardStats() {
    return api.get(`${API_URL}/dashboard-stats`)
  },

  // CRUD operations
  async getById(id) {
    return api.get(`${API_URL}/${id}`)
  },

  async create(data) {
    return api.post(API_URL, data)
  },

  async update(id, data) {
    return api.put(`${API_URL}/${id}`, data)
  },

  async delete(id) {
    return api.delete(`${API_URL}/${id}`)
  },

  // Actions
  async archive(id) {
    return api.post(`${API_URL}/${id}/archive`)
  },

  async unarchive(id) {
    return api.post(`${API_URL}/${id}/unarchive`)
  },

  async complete(id) {
    return api.post(`${API_URL}/${id}/complete`)
  },

  async clone(id, overrides = {}) {
    return api.post(`${API_URL}/${id}/clone`, overrides)
  },

  async toggleFavorite(id) {
    return api.post(`${API_URL}/${id}/toggle-favorite`)
  },

  // Members management
  async addMember(projetId, memberData) {
    return api.post(`${API_URL}/${projetId}/members`, memberData)
  },

  async updateMember(projetId, userId, permissions) {
    return api.put(`${API_URL}/${projetId}/members/${userId}`, permissions)
  },

  async removeMember(projetId, userId) {
    return api.delete(`${API_URL}/${projetId}/members/${userId}`)
  },
}
