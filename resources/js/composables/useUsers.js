import { computed } from 'vue'
import { useUserStore } from '@/stores/userStore'
import { storeToRefs } from 'pinia'

export function useUsers() {
  const userStore = useUserStore()

  const {
    users,
    currentUser,
    selectedUser,
    loading,
    error,
    pagination,
    filters,
  } = storeToRefs(userStore)

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
  }
}
