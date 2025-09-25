import axios from 'axios'

const API_BASE = '/api'

export const projetApi = {
  // Get all projects with filters
  getAll(filters = {}) {
    return axios.get(`${API_BASE}/projets`, { params: filters })
  },

  // Get single project
  get(id) {
    return axios.get(`${API_BASE}/projets/${id}`)
  },

  // Create new project
  create(data) {
    return axios.post(`${API_BASE}/projets`, data)
  },

  // Update project
  update(id, data) {
    return axios.put(`${API_BASE}/projets/${id}`, data)
  },

  // Delete project
  delete(id) {
    return axios.delete(`${API_BASE}/projets/${id}`)
  },

  // Get dashboard data
  getDashboard() {
    return axios.get(`${API_BASE}/projets-dashboard`)
  },

  // Get available responsables
  getResponsables() {
    return axios.get(`${API_BASE}/responsables`)
  }
}

export default projetApi