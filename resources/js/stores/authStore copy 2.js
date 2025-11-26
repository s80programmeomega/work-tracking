// resources\js\stores\authStore.js
import { defineStore } from 'pinia';
import { authAPI } from '@/api/auth';
import router from '@/router';
import axios from 'axios';
import { i18n } from '@/locales';

/**
 * STORE D'AUTHENTIFICATION - Système Hiérarchique
 * 
 * Structure des permissions :
 * - Workspace (Niveau 1) : owner, admin, member
 * - Projet (Niveau 2) : responsable, membre (+ pivot permissions)
 * - Activité (Niveau 3) : responsable, membre (+ pivot permissions)
 * - Tâche (Niveau 4) : assigné (+ pivot permissions)
 * 
 * Super Admin : Accès total à tout
 */

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user')) || null,
        token: localStorage.getItem('auth_token') || null,
        isAuthenticated: !!localStorage.getItem('auth_token'),
        loading: false,
        error: null,
        language: localStorage.getItem('user_language') || 'fr',
        tokenExpiry: null, // timestamp pour le rafraîchissement automatique
        refreshInterval: null, // ID du setInterval pour auto-refresh
    }),

    getters: {
        /**
         * Retourne l'utilisateur courant
         */
        currentUser: (state) => state.user,


        // ✅ CORRECTION: Retourne les rôles avec vérifications strictes
        userRoles: (state) => {
            if (!state.user) return [];

            // Si role simple (string)
            if (state.user.role && typeof state.user.role === 'string') {
                return [state.user.role];
            }

            // Si roles (array)
            if (Array.isArray(state.user.roles)) {
                return state.user.roles.map(r => {
                    if (typeof r === 'string') return r;
                    if (r && typeof r === 'object' && r.name) return r.name;
                    return null;
                }).filter(Boolean); // Retire les null
            }

            return [];
        },

        isSuperAdmin: (state) => {
            // 1️⃣ Vérifie le champ is_super_admin (BDD)
            if (state.user?.is_super_admin) return true;

            // 2️⃣ Vérifie le champ role string
            if (state.user?.role === 'super_admin') return true;

            // 3️⃣ Vérifie la collection roles
            if (state.user?.roles) {
                return state.user.roles.some(r => (r.name || r) === 'super_admin');
            }

            return false;
        },


        hasRole: (state) => (role) => state.userRoles.includes(role),
        hasAnyRole: (state) => (roles) => roles.some(r => state.userRoles.includes(r)),
        hasPermission: (state) => (perm) => state.userPermissions.includes(perm),
        hasAnyPermission: (state) => (perms) => perms.some(p => state.userPermissions.includes(p)),

        currentLanguage: (state) => state.language,
        isFrench: (state) => state.language === 'fr',
        isEnglish: (state) => state.language === 'en',

    },

    actions: {
        // ---------------------------
        // CONFIGURATION AXIOS
        // ---------------------------
        setAxiosToken(token) {
            if (token) {
                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            } else {
                delete axios.defaults.headers.common['Authorization'];
            }
        },

        // ---------------------------
        // INSCRIPTION
        // ---------------------------
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


        // ---------------------------
        // CONNEXION
        // ---------------------------
        async login(credentials) {
            this.loading = true;
            this.error = null;
            try {
                const response = await authAPI.login(credentials);
                const { user, token, expires_at } = response.data.data;

                this.user = user;
                this.token = token;
                this.isAuthenticated = true;
                this.tokenExpiry = new Date(expires_at).getTime();

                localStorage.setItem('user', JSON.stringify(user));
                localStorage.setItem('auth_token', token);
                localStorage.setItem('user_language', user.language || 'fr');

                this.setAxiosToken(token);

                // Mise à jour de la langue
                if (user.language) this.setLanguage(user.language);

                // Auto refresh token
                this.startTokenAutoRefresh();

                // Redirection après login
                const invitationToken = router.currentRoute.value.query.invitation;
                if (invitationToken) {
                    router.push(`/accept-invitation/${invitationToken}`);
                } else {
                    router.push('/');
                }

                return response.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Login failed';
                setTimeout(() => (this.error = null), 5000);
                throw error;
            } finally {
                this.loading = false;
            }
        },


        // ---------------------------
        // DÉCONNEXION
        // ---------------------------
        async logout() {
            this.loading = true;
            try {
                await authAPI.logout();
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                this.clearAuth();
                router.push('/signin');
                this.loading = false;
            }
        },

        clearAuth() {
            this.user = null;
            this.token = null;
            this.isAuthenticated = false;
            this.tokenExpiry = null;
            this.stopTokenAutoRefresh();
            localStorage.removeItem('user');
            localStorage.removeItem('auth_token');
            this.setAxiosToken(null);
        },


        // ---------------------------
        // RÉCUPÉRATION USER
        // ---------------------------
        async fetchUser() {
            if (!this.token) return;
            this.loading = true;
            try {
                const response = await authAPI.getUser();
                this.user = response.data.data;
                localStorage.setItem('user', JSON.stringify(this.user));
                if (this.user.language) this.setLanguage(this.user.language);
            } catch (error) {
                this.logout();
                throw error;
            } finally {
                this.loading = false;
            }
        },

        // ---------------------------
        // RAFRAÎCHISSEMENT TOKEN
        // ---------------------------
        async refreshToken() {
            try {
                const response = await authAPI.refreshToken();
                const { token, expires_at } = response.data.data;
                this.token = token;
                this.tokenExpiry = new Date(expires_at).getTime();
                localStorage.setItem('auth_token', token);
                this.setAxiosToken(token);
            } catch (error) {
                this.logout();
                throw error;
            }
        },
        startTokenAutoRefresh() {
            this.stopTokenAutoRefresh();
            const refreshBefore = 60 * 1000; // 1 min avant expiration
            this.refreshInterval = setInterval(() => {
                if (!this.tokenExpiry) return;
                const now = Date.now();
                if (this.tokenExpiry - now <= refreshBefore) this.refreshToken();
            }, 30 * 1000); // vérifie toutes les 30 sec
        },

        stopTokenAutoRefresh() {
            if (this.refreshInterval) clearInterval(this.refreshInterval);
        },

        // ---------------------------
        // LANGUE
        // ---------------------------
        async setLanguage(language) {
            if (!['fr', 'en'].includes(language)) return;
            i18n.global.locale.value = language;
            this.language = language;
            localStorage.setItem('user_language', language);
            document.documentElement.lang = language;
            if (this.isAuthenticated) {
                await authAPI.updateLanguage({ language }).catch(console.error);
                if (this.user) {
                    this.user.language = language;
                    localStorage.setItem('user', JSON.stringify(this.user));
                }
            }
            window.dispatchEvent(new Event('languageChanged'));
        },

        // ---------------------------
        // INITIALISATION
        // ---------------------------
        initialize() {
            this.setAxiosToken(this.token);
            if (this.isAuthenticated) this.startTokenAutoRefresh();
            if (this.isAuthenticated && !this.user) this.fetchUser();
            this.setLanguage(localStorage.getItem('user_language') || 'fr');
        },


        // ---------------------------
        // MISE À JOUR WORKSPACE / USER
        // ---------------------------
        setCurrentWorkspace(workspaceId) {
            if (!this.user) return;
            this.user.current_workspace_id = workspaceId;
            localStorage.setItem('user', JSON.stringify(this.user));
        },

        setUser(userData) {
            this.user = userData;
            localStorage.setItem('user', JSON.stringify(userData));
        },
    },
});