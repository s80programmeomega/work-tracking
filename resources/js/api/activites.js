import api from './axios'

const API_URL = '/activites'

export default {
  // List and filter
  async getAll(filters = {}) {
    return api.get(`${API_URL}/all/activity/`, { params: filters })
  },

  async getForProjet(projetId, filters = {}) {
    return api.get(`${API_URL}/projet/${projetId}`, { params: filters })
  },

  async getMyActivites(filters = {}) {
    return api.get(`${API_URL}/my-activites`, { params: filters })
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

  async duplicate(id, overrides = {}) {
    return api.post(`${API_URL}/${id}/duplicate`, overrides)
  },

  async reorder(orderedIds) {
    return api.post(`${API_URL}/reorder`, { ordered_ids: orderedIds })
  },
}
