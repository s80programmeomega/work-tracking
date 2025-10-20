import { defineStore } from 'pinia';
import { authAPI } from '@/api/auth';
import router from '@/router';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user')) || null,
        token: localStorage.getItem('auth_token') || null,
        isAuthenticated: !!localStorage.getItem('auth_token'),
        loading: false,
        error: null,
    }),

    getters: {
        currentUser: (state) => state.user,

        userRoles: (state) => {
            // Support both role field (string) and roles collection (Spatie)
            if (state.user?.role) {
                return [state.user.role];
            }
            return state.user?.roles?.map(r => r.name) || [];
        },

        userPermissions: (state) => {
            const rolePerms = state.user?.roles?.flatMap(r => r.permissions) || [];
            const directPerms = state.user?.permissions || [];
            return [...rolePerms, ...directPerms].map(p => p.name);
        },

        hasRole: (state) => (role) => {
            // Check both role field and roles collection
            if (state.user?.role === role) return true;
            return state.user?.roles?.some(r => r.name === role) || false;
        },

        hasAnyRole: (state) => (roles) => {
            // Check role field first
            if (state.user?.role && roles.includes(state.user.role)) return true;
            // Then check roles collection
            return roles.some(role => state.user?.roles?.some(r => r.name === role));
        },

        hasPermission: (state) => (permission) => {
            const allPerms = [
                ...state.user?.roles?.flatMap(r => r.permissions) || [],
                ...state.user?.permissions || []
            ];
            return allPerms.some(p => p.name === permission);
        },

        hasAnyPermission: (state) => (permissions) => {
            const allPerms = [
                ...state.user?.roles?.flatMap(r => r.permissions) || [],
                ...state.user?.permissions || []
            ];
            return permissions.some(perm =>
                allPerms.some(p => p.name === perm)
            );
        },

        isSuperAdmin: (state) => {
            return state.user?.roles?.some(r => r.name === 'super_admin') || false;
        },
    },

    actions: {
        async register(data) {
            this.loading = true;
            this.error = null;

            try {
                const response = await authAPI.register(data);
                router.push('/signin');
                return response.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Registration failed';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async login(credentials) {
            this.loading = true;
            this.error = null;

            try {
                const response = await authAPI.login(credentials);
                const { user, token } = response.data.data;

                this.user = user;
                this.token = token;
                this.isAuthenticated = true;

                localStorage.setItem('user', JSON.stringify(user));
                localStorage.setItem('auth_token', token);

                router.push('/');
                return response.data;
            } catch (error) {
                // Set error message
                const errorMessage = error.response?.status === 401
                    ? 'Invalid email or password'
                    : error.response?.data?.message || error.response?.data?.error || 'Login failed';

                this.error = errorMessage;

                // Clear error after 5 seconds
                setTimeout(() => {
                    this.error = null;
                }, 5000);

                throw error;
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            this.loading = true;

            try {
                await authAPI.logout();
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                this.user = null;
                this.token = null;
                this.isAuthenticated = false;

                localStorage.removeItem('user');
                localStorage.removeItem('auth_token');

                router.push('/signin');
                this.loading = false;
            }
        },

        async fetchUser() {
            if (!this.token) return;

            this.loading = true;

            try {
                const response = await authAPI.getUser();
                this.user = response.data.data;
                localStorage.setItem('user', JSON.stringify(this.user));
            } catch (error) {
                this.logout();
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async refreshToken() {
            try {
                const response = await authAPI.refreshToken();
                this.token = response.data.data.token;
                localStorage.setItem('auth_token', this.token);
            } catch (error) {
                this.logout();
                throw error;
            }
        },
    },
});
