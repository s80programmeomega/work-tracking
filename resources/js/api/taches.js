// resources/js/api/taches.js - VERSION CORRIGÉE
import api from './axios'

export default {
  /**
   * ✅ Récupérer toutes les tâches avec filtres
   */
  getAll(filters = {}) {
    return api.get('/taches', { params: filters })
  },

  /**
   * ✅ Récupérer le Kanban pour une activité
   */
  getForActivite(activiteId) {
    console.log('🌐 API: Appel GET /taches/activite/' + activiteId + '/kanban')
    return api.get(`/taches/activite/${activiteId}/kanban`)
      .then(response => {
        console.log('✅ API: Réponse reçue', response.data)
        return response
      })
      .catch(error => {
        console.error('❌ API: Erreur', error.response?.data || error.message)
        throw error
      })
  },

  /**
   * ✅ Mes tâches
   */
  getMyTaches() {
    return api.get('/taches/mes-taches')
  },

  /**
   * ✅ Tâches assignées à moi
   */
  getAssignedToMe() {
    return api.get('/taches/assignees')
  },

  /**
   * ✅ Tâches en attente de validation
   */
  getPending() {
    return api.get('/taches/en-attente')
  },

  /**
   * ✅ Tâches en retard
   */
  getOverdue() {
    return api.get('/taches/en-retard')
  },

  /**
   * ✅ Détails d'une tâche
   */
  get(id) {
    return api.get(`/taches/${id}`)
  },

  /**
   * ✅ Créer une tâche
   */
  create(data) {
    console.log('🌐 API: Création tâche', data)
    return api.post('/taches', data)
      .then(response => {
        console.log('✅ API: Tâche créée', response.data)
        return response
      })
      .catch(error => {
        console.error('❌ API: Erreur création', error.response?.data || error.message)
        throw error
      })
  },

  /**
   * ✅ Mettre à jour une tâche
   */
  update(id, data) {
    return api.put(`/taches/${id}`, data)
  },

  /**
   * ✅ Supprimer une tâche
   */
  delete(id) {
    return api.delete(`/taches/${id}`)
  },

  /**
   * ✅ Marquer comme terminé
   */
  complete(id) {
    return api.post(`/taches/${id}/complete`)
  },

  /**
   * ✅ Valider N1
   */
  validateN1(id, commentaire = null) {
    return api.post(`/taches/${id}/validate-n1`, { commentaire })
  },

  /**
   * ✅ Valider N2
   */
  validateN2(id, commentaire = null) {
    return api.post(`/taches/${id}/validate-n2`, { commentaire })
  },

  /**
   * ✅ Déplacer une tâche (Kanban)
   */
  move(id, statut, position) {
    return api.post(`/taches/${id}/move`, { statut, position })
  },

  /**
   * ✅ Archiver
   */
  archive(id) {
    return api.post(`/taches/${id}/archive`)
  },

  /**
   * ✅ Désarchiver
   */
  unarchive(id) {
    return api.post(`/taches/${id}/unarchive`)
  },

  /**
   * ✅ Dupliquer
   */
  duplicate(id) {
    return api.post(`/taches/${id}/duplicate`)
  },

  /**
   * ✅ Assigner un utilisateur
   */
  assignUser(tacheId, userId, data = {}) {
    return api.post(`/taches/${tacheId}/assignees`, { user_id: userId, ...data })
  },

  /**
   * ✅ Désassigner un utilisateur
   */
  unassignUser(tacheId, userId) {
    return api.delete(`/taches/${tacheId}/assignees/${userId}`)
  },

  /**
   * ✅ Mettre à jour la progression
   */
  updateProgress(id, tauxRealisation) {
    return api.put(`/taches/${id}`, { taux_realisation: tauxRealisation })
  },

  /**
   * ✅ Sous-tâches
   */
  getSubTasks(id) {
    return api.get(`/taches/${id}/sous-taches`)
  },

  /**
   * ✅ Créer sous-tâche
   */
  createSubTask(parentId, data) {
    return api.post(`/taches/${parentId}/sous-taches`, data)
  },

  /**
   * ✅ Rapport hebdomadaire
   */
  getWeeklyReport(userId = null, weekNumber = null, year = null) {
    const params = {}
    if (weekNumber) params.week_number = weekNumber
    if (year) params.year = year

    const url = userId 
      ? `/evaluations/rapport-hebdomadaire/${userId}`
      : '/evaluations/mon-rapport-hebdomadaire'

    return api.get(url, { params })
  },

  /**
   * ✅ Performance d'équipe
   */
  getTeamPerformance(activiteId, weekNumber = null, year = null) {
    const params = {}
    if (weekNumber) params.week_number = weekNumber
    if (year) params.year = year

    return api.get(`/evaluations/performance-equipe/${activiteId}`, { params })
  },

  /**
   * ✅ Dashboard d'évaluation
   */
  getEvaluationDashboard() {
    return api.get('/evaluations/dashboard')
  },

  /**
   * ✅ Vérifier les permissions
   */
  checkPermissions(activiteId) {
    return api.get(`/activites/${activiteId}/check-permissions`)
  }
}