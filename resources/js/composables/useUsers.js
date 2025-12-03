import { computed } from 'vue'
import { useUserStore } from '@/stores/userStore'
import { useAuthStore } from '@/stores/authStore'

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
      const response = await axios.get('/api/users/profile')
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
      const response = await axios.put('/api/users/profile', data, {
        headers: {
          'Content-Type': data instanceof FormData ? 'multipart/form-data' : 'application/json'
        }
      })

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
      const response = await axios.post('/api/users/change-password', data)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du changement de mot de passe'
      throw err
    } finally {
      loading.value = false
    }
  }

  const exportProfileData = async () => {
    try {
      const response = await axios.get('/api/users/export', {
        responseType: 'blob'
      })

      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `profile-export-${Date.now()}.json`)
      document.body.appendChild(link)
      link.click()
      link.remove()
    } catch (err) {
      throw new Error('Erreur lors de l\'export des données')
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
    fetchProfile: userStore.fetchProfile,
    updateProfile: userStore.updateProfile,
    changePassword: userStore.changePassword,
    toggleActiveStatus: userStore.toggleActiveStatus,
    setFilters: userStore.setFilters,
    resetFilters: userStore.resetFilters,
    clearError: userStore.clearError,

    // Helper
    usersByRole: userStore.usersByRole,

    fetchProfile,
    updateProfile,
    changePassword,
    exportProfileData,
  }
}
