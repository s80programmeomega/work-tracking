import { computed } from 'vue';
import { useAuthStore } from '@/stores/authStore';
import { storeToRefs } from 'pinia';

export function useAuth() {
    const authStore = useAuthStore();
    const { user, isAuthenticated, loading, error } = storeToRefs(authStore);

    const hasRole = (role) => authStore.hasRole(role);
    const hasAnyRole = (roles) => authStore.hasAnyRole(roles);
    const hasPermission = (permission) => authStore.hasPermission(permission);
    const hasAnyPermission = (permissions) => authStore.hasAnyPermission(permissions);

    return {
        // State
        user,
        isAuthenticated,
        loading,
        error,

        // Getters
        userRoles: computed(() => authStore.userRoles),
        userPermissions: computed(() => authStore.userPermissions),
        isSuperAdmin: computed(() => authStore.isSuperAdmin),

        // Methods
        login: authStore.login,
        register: authStore.register,
        logout: authStore.logout,
        fetchUser: authStore.fetchUser,

        // Helpers
        hasRole,
        hasAnyRole,
        hasPermission,
        hasAnyPermission,
    };
}
