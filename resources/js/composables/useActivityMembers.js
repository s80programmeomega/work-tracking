// resources/js/composables/useActivityMembers.js - VERSION CORRIGÉE
import { ref, computed } from 'vue'
import api from '@/api/axios'

export function useActivityMembers() {
  const loadingMembers = ref(false)
  const activityMembers = ref([])

  /**
   * Charger les membres d'une activité spécifique
   */
  const loadActivityMembers = async (activiteId) => {
    if (!activiteId) {
      activityMembers.value = []
      return []
    }

    loadingMembers.value = true
    try {
      console.log('👥 Chargement des membres pour activité:', activiteId)
      
      // ✅ CORRECTION : Essayer plusieurs endpoints possibles
      let response
      
      try {
        // Essayer d'abord l'endpoint des membres d'activité
        response = await api.get(`/activites/${activiteId}/membres`)
      } catch (error) {
        console.log('❌ Endpoint /membres non disponible, essai /available-members')
        // Fallback vers available-members
        response = await api.get(`/activites/${activiteId}/available-members`)
      }
      
      // ✅ CORRECTION : Gestion robuste de la structure de réponse
      const responseData = response.data
      
      if (Array.isArray(responseData)) {
        // Si la réponse est directement un tableau
        activityMembers.value = responseData
      } else if (responseData && Array.isArray(responseData.data)) {
        // Si la réponse a une propriété data qui est un tableau
        activityMembers.value = responseData.data
      } else if (responseData && typeof responseData === 'object') {
        // Si c'est un objet, essayer de trouver un tableau dedans
        const possibleArrays = Object.values(responseData).filter(val => Array.isArray(val))
        activityMembers.value = possibleArrays.length > 0 ? possibleArrays[0] : []
      } else {
        // Fallback sûr
        activityMembers.value = []
      }
      
      console.log('✅ Membres chargés:', {
        count: activityMembers.value.length,
        structure: 'Array',
        sample: activityMembers.value.slice(0, 2)
      })
      
      return activityMembers.value
      
    } catch (error) {
      console.error('❌ Erreur chargement membres:', error)
      console.error('Response:', error.response?.data)
      
      // ✅ CORRECTION : Toujours garantir que c'est un tableau
      activityMembers.value = []
      throw error
    } finally {
      loadingMembers.value = false
    }
  }

  /**
   * Charger les membres depuis le projet parent
   */
  const loadProjectMembers = async (projetId) => {
    if (!projetId) {
      activityMembers.value = []
      return []
    }

    loadingMembers.value = true
    try {
      console.log('👥 Chargement des membres du projet:', projetId)
      
      const response = await api.get(`/projets/${projetId}/membres`)
      const responseData = response.data
      
      // ✅ CORRECTION : Même logique de gestion de structure
      if (Array.isArray(responseData)) {
        activityMembers.value = responseData
      } else if (responseData && Array.isArray(responseData.data)) {
        activityMembers.value = responseData.data
      } else {
        activityMembers.value = []
      }
      
      console.log('✅ Membres projet chargés:', activityMembers.value.length)
      return activityMembers.value
      
    } catch (error) {
      console.error('❌ Erreur chargement membres projet:', error)
      activityMembers.value = []
      throw error
    } finally {
      loadingMembers.value = false
    }
  }

  /**
   * Filtrer les membres disponibles pour assignation
   */
  const getAvailableMembers = computed(() => {
    // ✅ CORRECTION : Vérification robuste que c'est un tableau
    if (!Array.isArray(activityMembers.value)) {
      console.warn('⚠️ activityMembers.value n\'est pas un tableau:', activityMembers.value)
      return []
    }
    
    return activityMembers.value.filter(member => {
      // Ici vous pouvez ajouter des filtres supplémentaires si nécessaire
      return member.is_active !== false // Exclure les membres inactifs
    })
  })

  return {
    loadingMembers,
    activityMembers,
    availableMembers: getAvailableMembers,
    loadActivityMembers,
    loadProjectMembers
  }
}