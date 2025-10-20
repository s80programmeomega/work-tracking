import api from './axios'

export default {
  // Get all tasks with filters
  getAll(filters = {}) {
    return api.get('/taches', { params: filters })
  },

  // Get tasks for a specific activity (Kanban format)
  getForActivite(activiteId) {
    return api.get(`/taches/activite/${activiteId}`)
  },

  // Get tasks assigned to current user
  getMyTaches() {
    return api.get('/taches/my-taches')
  },

  // Get a specific task
  get(id) {
    return api.get(`/taches/${id}`)
  },

  // Create a new task
  create(data) {
    return api.post('/taches', data)
  },

  // Update a task
  update(id, data) {
    return api.put(`/taches/${id}`, data)
  },

  // Delete a task
  delete(id) {
    return api.delete(`/taches/${id}`)
  },

  // Move task to different status/position (Kanban drag & drop)
  move(id, statut, ordre) {
    return api.post(`/taches/${id}/move`, { statut, ordre })
  },

  // Reorder task within same status
  reorder(id, ordre) {
    return api.post(`/taches/${id}/reorder`, { ordre })
  },

  // Duplicate a task
  duplicate(id) {
    return api.post(`/taches/${id}/duplicate`)
  },

  // Archive a task
  archive(id) {
    return api.post(`/taches/${id}/archive`)
  },

  // Validate task by superior
  validate(id) {
    return api.post(`/taches/${id}/validate`)
  },

  // Assign user to task
  assignUser(id, userId) {
    return api.post(`/taches/${id}/assign`, { user_id: userId })
  },

  // Unassign user from task
  unassignUser(id, userId) {
    return api.post(`/taches/${id}/unassign`, { user_id: userId })
  },

  // Update task progress
  updateProgress(id, tauxRealisation) {
    return api.post(`/taches/${id}/progress`, { taux_realisation: tauxRealisation })
  }
}
