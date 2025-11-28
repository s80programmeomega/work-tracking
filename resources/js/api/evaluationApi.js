// resources/js/api/evaluationApi.js

import api from './axios'

/**
 * 📊 Service API pour la gestion des évaluations
 */
export const evaluationApi = {
  /**
   * 📋 Récupérer les résultats en attente de validation
   */
  async getPendingValidations() {
    const { data } = await api.get('/evaluations/resultats/en-attente')
    return data
  },

  /**
   * 📊 Récupérer mes responsabilités (tous les résultats consultables)
   */
  async getMyResponsibilities() {
    const { data } = await api.get('/evaluations/mes-responsabilites')
    return data
  },

  /**
   * ✅ Valider un résultat (N1)
   */
  async validateN1(resultatId, commentaire = null) {
    const { data } = await api.post(`/evaluations/resultats/${resultatId}/validate-n1`, {
      commentaire
    })
    return data
  },

  /**
   * ✅ Valider un résultat (N2)
   */
  async validateN2(resultatId, commentaire = null) {
    const { data } = await api.post(`/evaluations/resultats/${resultatId}/validate-n2`, {
      commentaire
    })
    return data
  },

  /**
   * ❌ Rejeter un résultat
   */
  async reject(resultatId, commentaire, level) {
    const { data } = await api.post(`/evaluations/resultats/${resultatId}/reject`, {
      commentaire,
      level
    })
    return data
  },

  /**
   * 🔍 Vérifier mes permissions sur un résultat
   */
  async checkPermissions(resultatId) {
    const { data } = await api.get(`/evaluations/resultats/${resultatId}/permissions`)
    return data
  },

  /**
   * 📜 Récupérer l'historique des validations
   */
  async getHistory(filters = {}) {
    const { data } = await api.get('/evaluations/history', { params: filters })
    return data
  },

  /**
   * 👁️ Consulter un résultat
   */
  async getResultat(tacheId, resultatId) {
    const { data } = await api.get(`/taches/${tacheId}/resultats/${resultatId}`)
    return data
  },

  /**
   * 📁 Récupérer les documents d'un résultat
   */
  async getDocuments(tacheId, resultatId) {
    const { data } = await api.get(`/taches/${tacheId}/resultats/${resultatId}/documents`)
    return data
  },

  /**
   * 🔍 Prévisualiser un document
   */
  async viewDocument(tacheId, resultatId, documentId) {
    return api.get(
      `/taches/${tacheId}/resultats/${resultatId}/documents/${documentId}/view`,
      { responseType: 'blob' }
    )
  },

  /**
   * 📥 Télécharger un document
   */
  async downloadDocument(tacheId, resultatId, documentId) {
    return api.get(
      `/taches/${tacheId}/resultats/${resultatId}/documents/${documentId}/download`,
      { responseType: 'blob' }
    )
  }
}

export default evaluationApi