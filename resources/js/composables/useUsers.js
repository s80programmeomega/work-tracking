import { computed } from 'vue'
import { useUserStore } from '@/stores/userStore'
import { useAuthStore } from '@/stores/authStore'
import api from '@/api/axios'

import { storeToRefs } from 'pinia'

export function useUsers() {
  const userStore = useUserStore()
  const authStore = useAuthStore()

  const {
    users,
    currentUser,
    selectedUser,
    loading,
    error,
    pagination,
    filters,
  } = storeToRefs(userStore)



  const fetchProfile = async () => {
    loading.value = true
    error.value = null

    try {
      const response = await api.get('/users/profile')
      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement du profil'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateProfile = async (data) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.put('/users/profile', data)

      // Update auth store
      if (response.data.data) {
        authStore.setUser(response.data.data)
      }

      return response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la mise à jour'
      throw err
    } finally {
      loading.value = false
    }
  }

  const changePassword = async (data) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.post('/users/change-password', data)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du changement de mot de passe'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    // State
    users,
    currentUser,
    selectedUser,
    loading,
    error,
    pagination,
    filters,

    // Getters
    activeUsers: computed(() => userStore.activeUsers),
    totalUsers: computed(() => userStore.totalUsers),
    isLoading: computed(() => userStore.isLoading),

    // Actions
    fetchUsers: userStore.fetchUsers,
    searchUsers: userStore.searchUsers,
    fetchUser: userStore.fetchUser,
    createUser: userStore.createUser,
    updateUser: userStore.updateUser,
    deleteUser: userStore.deleteUser,
    toggleActiveStatus: userStore.toggleActiveStatus,
    setFilters: userStore.setFilters,
    resetFilters: userStore.resetFilters,
    clearError: userStore.clearError,

    // Helper
    usersByRole: userStore.usersByRole,

    fetchProfile,
    updateProfile,
    changePassword,
  }
}
