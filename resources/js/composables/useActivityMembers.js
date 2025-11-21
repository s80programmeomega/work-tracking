// resources/js/composables/useActivityMembers.js - VERSION CORRIGÉE ET OPTIMISÉE
import { ref, computed } from 'vue'
import api from '@/api/axios'

/**
 * Composable pour gérer les membres d'activités
 * Gère le chargement et la mise en cache des membres
 */
export function useActivityMembers() {
  // ==================== ÉTAT ====================
  const loadingMembers = ref(false)
  const activityMembers = ref([])
  const membersCache = ref(new Map()) // Cache pour éviter les requêtes multiples

  // ==================== COMPUTED ====================
  
  /**
   * Liste des membres disponibles pour assignation
   * Filtre les membres inactifs
   */
  const availableMembers = computed(() => {
    if (!Array.isArray(activityMembers.value)) {
      console.warn('⚠️ activityMembers.value n\'est pas un tableau:', activityMembers.value)
      return []
    }
    
    return activityMembers.value.filter(member => {
      return member.is_active !== false
    })
  })

  // ==================== MÉTHODES PRIVÉES ====================

  /**
   * Normaliser la structure de réponse API
   * Gère différents formats de réponse
   */
  const normalizeApiResponse = (responseData) => {
    // Cas 1: Réponse directe en tableau
    if (Array.isArray(responseData)) {
      return responseData
    }
    
    // Cas 2: Objet avec propriété data
    if (responseData && Array.isArray(responseData.data)) {
      return responseData.data
    }
    
    // Cas 3: Objet avec propriété members
    if (responseData && Array.isArray(responseData.members)) {
      return responseData.members
    }
    
    // Cas 4: Chercher le premier tableau dans l'objet
    if (responseData && typeof responseData === 'object') {
      const possibleArrays = Object.values(responseData).filter(val => Array.isArray(val))
      if (possibleArrays.length > 0) {
        return possibleArrays[0]
      }
    }
    
    // Fallback: tableau vide
    console.warn('⚠️ Format de réponse non reconnu, retour tableau vide')
    return []
  }

  /**
   * Vérifier si les membres sont en cache
   */
  const getCachedMembers = (key) => {
    const cached = membersCache.value.get(key)
    if (cached) {
      const isExpired = Date.now() - cached.timestamp > 5 * 60 * 1000 // 5 minutes
      if (!isExpired) {
        console.log('✅ Membres récupérés du cache:', key)
        return cached.data
      } else {
        console.log('⏰ Cache expiré pour:', key)
        membersCache.value.delete(key)
      }
    }
    return null
  }

  /**
   * Mettre en cache les membres
   */
  const cacheMembers = (key, data) => {
    membersCache.value.set(key, {
      data,
      timestamp: Date.now()
    })
  }

  // ==================== MÉTHODES PUBLIQUES ====================

  /**
   * Charger les membres d'une activité spécifique
   * Essaie plusieurs endpoints possibles avec fallback
   */
  const loadActivityMembers = async (activiteId) => {
    if (!activiteId) {
      console.warn('⚠️ Aucun ID d\'activité fourni')
      activityMembers.value = []
      return []
    }

    // Vérifier le cache
    const cacheKey = `activity_${activiteId}`
    const cachedData = getCachedMembers(cacheKey)
    if (cachedData) {
      activityMembers.value = cachedData
      return cachedData
    }

    loadingMembers.value = true
    
    try {
      console.log('👥 Chargement des membres pour activité:', activiteId)
      
      let response
      let members = []
      
      // Essai 1: Endpoint dédié aux membres
      try {
        response = await api.get(`/activites/${activiteId}/membres`)
        members = normalizeApiResponse(response.data)
        console.log('✅ Membres chargés via /membres:', members.length)
      } catch (error) {
        if (error.response?.status === 404) {
          console.log('⚠️ Endpoint /membres non disponible')
        } else {
          throw error
        }
      }
      
      // Essai 2: Endpoint available-members
      if (members.length === 0) {
        try {
          response = await api.get(`/activites/${activiteId}/available-members`)
          members = normalizeApiResponse(response.data)
          console.log('✅ Membres chargés via /available-members:', members.length)
        } catch (error) {
          if (error.response?.status === 404) {
            console.log('⚠️ Endpoint /available-members non disponible')
          } else {
            throw error
          }
        }
      }
      
      // Essai 3: Charger l'activité complète avec membres inclus
      if (members.length === 0) {
        try {
          response = await api.get(`/activites/${activiteId}?with_members=true`)
          const activiteData = response.data.data || response.data
          members = activiteData.membres || activiteData.members || []
          console.log('✅ Membres chargés via activité complète:', members.length)
        } catch (error) {
          console.error('❌ Impossible de charger l\'activité complète')
          throw error
        }
      }
      
      // Validation finale
      if (!Array.isArray(members)) {
        console.warn('⚠️ Les membres ne sont pas un tableau, conversion')
        members = []
      }
      
      activityMembers.value = members
      cacheMembers(cacheKey, members)
      
      console.log('✅ Chargement terminé:', {
        count: members.length,
        cached: true,
        sample: members.slice(0, 2).map(m => ({ id: m.id, nom: m.nom || m.name }))
      })
      
      return members
      
    } catch (error) {
      console.error('❌ Erreur chargement membres d\'activité:', error)
      console.error('Détails:', error.response?.data)
      activityMembers.value = []
      throw error
    } finally {
      loadingMembers.value = false
    }
  }

  /**
   * Charger les membres depuis le projet parent
   * Utilisé comme fallback quand les membres d'activité ne sont pas disponibles
   */
  const loadProjectMembers = async (projetId) => {
    if (!projetId) {
      console.warn('⚠️ Aucun ID de projet fourni')
      activityMembers.value = []
      return []
    }

    // Vérifier le cache
    const cacheKey = `project_${projetId}`
    const cachedData = getCachedMembers(cacheKey)
    if (cachedData) {
      activityMembers.value = cachedData
      return cachedData
    }

    loadingMembers.value = true
    
    try {
      console.log('👥 Chargement des membres du projet:', projetId)
      
      const response = await api.get(`/projets/${projetId}/membres`)
      const members = normalizeApiResponse(response.data)
      
      if (!Array.isArray(members)) {
        console.warn('⚠️ Les membres projet ne sont pas un tableau')
        activityMembers.value = []
        return []
      }
      
      activityMembers.value = members
      cacheMembers(cacheKey, members)
      
      console.log('✅ Membres projet chargés:', {
        count: members.length,
        cached: true
      })
      
      return members
      
    } catch (error) {
      console.error('❌ Erreur chargement membres projet:', error)
      console.error('Détails:', error.response?.data)
      activityMembers.value = []
      throw error
    } finally {
      loadingMembers.value = false
    }
  }

  /**
   * Recharger les membres (ignore le cache)
   */
  const refreshMembers = async (activiteId) => {
    const cacheKey = `activity_${activiteId}`
    membersCache.value.delete(cacheKey)
    return loadActivityMembers(activiteId)
  }

  /**
   * Vider tout le cache
   */
  const clearCache = () => {
    membersCache.value.clear()
    console.log('🗑️ Cache des membres vidé')
  }

  /**
   * Obtenir un membre spécifique par ID
   */
  const getMemberById = (memberId) => {
    return activityMembers.value.find(m => m.id === memberId)
  }

  /**
   * Vérifier si un utilisateur est membre
   */
  const isMember = (userId) => {
    return activityMembers.value.some(m => m.id === userId)
  }

  // ==================== RETOUR ====================
  
  return {
    // État
    loadingMembers,
    activityMembers,
    availableMembers,
    
    // Méthodes principales
    loadActivityMembers,
    loadProjectMembers,
    refreshMembers,
    
    // Utilitaires
    clearCache,
    getMemberById,
    isMember
  }
}